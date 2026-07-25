<?php

namespace LicitaIA\Models;

class Opportunity_checklist_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_opportunity_checklist';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function create_default_checklist($opportunity_id)
    {
        $opportunity_id = (int) $opportunity_id;
        if (!$opportunity_id || !$this->hasTable()) {
            return false;
        }

        $checklist_model = model(Checklist_item_model::class);
        $items = $checklist_model->get_active_items();
        if (!$items) {
            return true;
        }

        $existing_rows = $this->get_by_opportunity($opportunity_id)->getResult();
        $existing_ids = array();
        foreach ($existing_rows as $existing) {
            $existing_ids[(int) $existing->checklist_item_id] = true;
        }

        $now = get_my_local_time();
        foreach ($items as $item) {
            if (isset($existing_ids[(int) $item->id])) {
                continue;
            }

            $this->db->table($this->db->prefixTable($this->table))->insert(array(
                'opportunity_id' => $opportunity_id,
                'checklist_item_id' => (int) $item->id,
                'item_name_snapshot' => (string) ($item->item_name ?? ''),
                'status' => 'pending',
                'notes' => null,
                'document_id' => null,
                'sort' => (int) ($item->sort ?? 0),
                'created_by' => $this->currentUserId(),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            ));
        }

        return true;
    }

    public function get_by_opportunity($opportunity_id)
    {
        $opportunity_id = (int) $opportunity_id;
        if (!$opportunity_id || !$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $items_table = $this->db->prefixTable('licitaia_checklist_items');
        $documents_table = $this->db->prefixTable('licitaia_documents');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT oc.id, oc.opportunity_id, oc.checklist_item_id, oc.item_name_snapshot,
                       CASE
                           WHEN oc.status IN ('open', 'pending') THEN 'pending'
                           WHEN oc.status IN ('validated', 'done', 'approved', 'completed') THEN 'validated'
                           WHEN oc.status IN ('not_applicable', 'not applicable', 'nao_aplicavel') THEN 'not_applicable'
                           ELSE oc.status
                       END AS status,
                       oc.notes, oc.document_id, oc.sort,
                       oc.completed_by, oc.completed_at, oc.created_by, oc.created_at, oc.updated_at, oc.deleted,
                       ci.item_name AS checklist_item_name, ci.category, ci.description, ci.is_required, ci.active,
                       d.file_name AS document_file_name, d.original_file_name AS document_original_file_name,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name
                FROM {$table} oc
                LEFT JOIN {$items_table} ci ON ci.id = oc.checklist_item_id
                LEFT JOIN {$documents_table} d ON d.id = oc.document_id
                LEFT JOIN {$users_table} u ON u.id = oc.created_by
                WHERE oc.deleted = 0 AND oc.opportunity_id = {$opportunity_id}
                ORDER BY oc.sort ASC, oc.id ASC";

        return $this->queryOrEmpty($sql);
    }

    public function update_status($id, $status, $notes = null)
    {
        $id = (int) $id;
        if (!$id || !$this->hasTable()) {
            return false;
        }

        $status = trim((string) $status);
        $status = $this->normalizeStatus($status);
        $update = array(
            'status' => $status ?: 'pending',
            'notes' => $notes === null ? null : trim((string) $notes),
            'completed_at' => in_array($status, array('completed', 'done', 'approved'), true) ? get_my_local_time() : null,
            'updated_at' => get_my_local_time(),
        );

        if (in_array($status, array('completed', 'done', 'approved'), true)) {
            $update['completed_by'] = $this->currentUserId();
        }

        return $this->ci_save($update, $id);
    }

    public function update_item($id, $data)
    {
        $id = (int) $id;
        if (!$id || !$this->hasTable()) {
            return false;
        }

        $payload = is_array($data) ? $data : array();
        $status = $this->normalizeStatus(get_array_value($payload, 'status', 'pending'));
        $update = array(
            'status' => $status,
            'notes' => trim((string) get_array_value($payload, 'notes', '')),
            'document_id' => (int) get_array_value($payload, 'document_id', 0) ?: null,
            'updated_at' => get_my_local_time(),
        );

        if ($status === 'validated' || $status === 'sent' || $status === 'separated') {
            $update['completed_at'] = in_array($status, array('validated', 'sent'), true) ? get_my_local_time() : null;
            $update['completed_by'] = in_array($status, array('validated', 'sent'), true) ? $this->currentUserId() : null;
        }

        return $this->ci_save($update, $id);
    }

    public function get_progress($opportunity_id)
    {
        $items = $this->get_by_opportunity($opportunity_id)->getResult();
        $total = count($items);
        if ($total === 0) {
            return array('percent' => 0, 'total' => 0, 'done' => 0, 'pending' => 0);
        }

        $done_statuses = array('separated', 'validated', 'sent', 'not_applicable');
        $done = 0;
        foreach ($items as $item) {
            if (in_array($this->normalizeStatus((string) $item->status), $done_statuses, true)) {
                $done++;
            }
        }

        return array(
            'percent' => (int) round(($done / $total) * 100),
            'total' => $total,
            'done' => $done,
            'pending' => $total - $done,
        );
    }

    private function normalizeStatus($status)
    {
        $status = trim((string) $status);
        $map = array(
            'open' => 'pending',
            'pending' => 'pending',
            'separated' => 'separated',
            'validated' => 'validated',
            'sent' => 'sent',
            'not_applicable' => 'not_applicable',
            'not applicable' => 'not_applicable',
            'nao_aplicavel' => 'not_applicable',
            'completed' => 'validated',
            'done' => 'validated',
            'approved' => 'validated',
        );

        return get_array_value($map, $status, 'pending');
    }

    private function currentUserId()
    {
        try {
            $users_model = model('App\\Models\\Users_model');
            return (int) $users_model->login_user_id();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
