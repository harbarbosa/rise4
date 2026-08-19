<?php

namespace LaudosTecnicos\Models;

class LaudoChecklists_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_checklists';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $types = $this->db->prefixTable('laudo_types');
        $categories = $this->db->prefixTable('laudo_categories');
        $users = $this->db->prefixTable('users');
        $where = " WHERE $table.deleted = 0";

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.name LIKE '%$search%' OR $table.code LIKE '%$search%' OR $table.description LIKE '%$search%')";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status = '" . $this->db->escapeString($status) . "'";
        }

        $type_id = (int) get_array_value($options, 'type_id');
        if ($type_id) {
            $where .= " AND $table.type_id = $type_id";
        }

        $category_id = (int) get_array_value($options, 'category_id');
        if ($category_id) {
            $where .= " AND $table.category_id = $category_id";
        }

        return $this->queryOrEmpty("SELECT $table.*,
                $types.name AS type_name,
                $categories.name AS category_name,
                CONCAT(IFNULL(r.first_name, ''), ' ', IFNULL(r.last_name, '')) AS responsible_name
            FROM $table
            LEFT JOIN $types ON $types.id = $table.type_id AND $types.deleted = 0
            LEFT JOIN $categories ON $categories.id = $table.category_id AND $categories.deleted = 0
            LEFT JOIN $users r ON r.id = $table.responsible_id AND r.deleted = 0
            $where
            ORDER BY $table.is_default DESC, $table.version DESC, $table.name ASC");
    }

    public function get_one_with_structure(int $id)
    {
        $row = $this->get_one($id);
        if (!$row || !$row->id) {
            return null;
        }

        $row->structure = $this->decode_structure_json($row->structure_json ?? '');
        return $row;
    }

    public function get_default_for_type(int $type_id = 0, int $category_id = 0)
    {
        if (!$this->hasTable()) {
            return null;
        }

        $builder = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('is_active', 1)
            ->where('status', 'published');

        if ($type_id) {
            $builder->groupStart()->where('type_id', $type_id)->orWhere('is_default', 1)->groupEnd();
        }

        if ($category_id) {
            $builder->groupStart()->where('category_id', $category_id)->orWhere('is_default', 1)->groupEnd();
        }

        return $builder->orderBy('is_default', 'DESC')->orderBy('version', 'DESC')->orderBy('id', 'DESC')->get()->getRow();
    }

    public function save_versioned(array $data, array $structure = array(), ?int $id = null, bool $force_new_version = false)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = $this->normalize_payload($data, $structure);
        $payload['updated_at'] = get_current_utc_time();

        if (!empty($payload['is_default'])) {
            $this->clear_default_flag();
        }

        if (($payload['status'] ?? 'draft') === 'published' && empty($payload['published_at'])) {
            $payload['published_at'] = get_current_utc_time();
        }

        if ($id) {
            $current = $this->get_one_with_structure($id);
            if (!$current || !$current->id) {
                return false;
            }

            if (!$force_new_version && (string) ($current->status ?? '') === 'published') {
                return $this->clone_as_new_version((int) $current->id, $payload, $structure);
            }

            $payload['structure_json'] = $this->encode_structure_json($structure ?: $current->structure ?: array());
            $payload['version'] = (int) ($current->version ?? 1);
            $payload['created_by'] = (int) ($current->created_by ?? ($data['created_by'] ?? 0));

            return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($payload);
        }

        if (empty($payload['structure_json'])) {
            $payload['structure_json'] = $this->encode_structure_json($structure);
        }
        if (empty($payload['version'])) {
            $payload['version'] = 1;
        }
        if (empty($payload['status'])) {
            $payload['status'] = 'draft';
        }
        if (empty($payload['code'])) {
            $payload['code'] = $this->generate_code($payload['name'] ?? 'CHK');
        }

        $payload['created_at'] = get_current_utc_time();
        $inserted = $this->db->table($this->db->prefixTable($this->table))->insert($payload);
        return $inserted ? $this->db->insertID() : false;
    }

    public function duplicate(int $id, array $overrides = array())
    {
        $current = $this->get_one_with_structure($id);
        if (!$current || !$current->id) {
            return false;
        }

        $data = array_merge(array(
            'name' => $current->name,
            'code' => $current->code . '-COPY',
            'category_id' => $current->category_id,
            'type_id' => $current->type_id,
            'description' => $current->description,
            'version' => (int) ($current->version ?? 1) + 1,
            'status' => 'draft',
            'is_active' => 1,
            'is_default' => 0,
            'responsible_id' => $current->responsible_id,
            'published_at' => null,
            'created_by' => (int) get_array_value($overrides, 'created_by'),
            'updated_by' => (int) get_array_value($overrides, 'created_by'),
        ), $overrides);

        unset($data['id']);
        return $this->save_versioned($data, $current->structure ?: array(), null, true);
    }

    public function publish(int $id)
    {
        return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update(array('status' => 'published', 'published_at' => get_current_utc_time(), 'updated_at' => get_current_utc_time()));
    }

    public function archive(int $id)
    {
        return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update(array('status' => 'archived', 'updated_at' => get_current_utc_time()));
    }

    public function toggle_active(int $id)
    {
        $row = $this->get_one($id);
        if (!$row || !$row->id) {
            return false;
        }

        return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update(array('is_active' => empty($row->is_active) ? 1 : 0, 'updated_at' => get_current_utc_time()));
    }

    public function export_json(int $id): string
    {
        $row = $this->get_one_with_structure($id);
        return laudostecnicos_safe_json($row ?: array());
    }

    public function import_json(string $json, array $overrides = array())
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return false;
        }

        $structure = is_array(get_array_value($decoded, 'structure')) ? get_array_value($decoded, 'structure') : array();
        $data = array(
            'name' => get_array_value($decoded, 'name'),
            'code' => get_array_value($decoded, 'code'),
            'category_id' => get_array_value($decoded, 'category_id'),
            'type_id' => get_array_value($decoded, 'type_id'),
            'description' => get_array_value($decoded, 'description'),
            'version' => get_array_value($decoded, 'version') ?: 1,
            'status' => 'draft',
            'is_active' => 1,
            'is_default' => 0,
            'responsible_id' => get_array_value($decoded, 'responsible_id'),
            'created_by' => get_array_value($overrides, 'created_by'),
            'updated_by' => get_array_value($overrides, 'created_by'),
        );

        return $this->save_versioned($data, $structure, null, true);
    }

    public function is_in_use(int $id): bool
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $db = $this->db;
        $responses = $db->prefixTable('laudo_checklist_responses');
        $links = $db->prefixTable('laudo_norm_links');

        if ($db->table($responses)->where('checklist_id', $id)->where('deleted', 0)->countAllResults() > 0) {
            return true;
        }

        return $db->table($links)->where('entity_type', 'checklist')->where('entity_id', $id)->where('deleted', 0)->countAllResults() > 0;
    }

    public function save_from_post(array $data, ?int $id = null, array $structure = array())
    {
        return $this->save_versioned($data, $structure, $id, false);
    }

    public function delete($id = 0, $undo = false)
    {
        $id = (int) $id;
        if (!$id || $this->is_in_use($id)) {
            return false;
        }

        return parent::delete($id, $undo);
    }

    private function normalize_payload(array $data, array $structure = array())
    {
        $payload = array();
        $fields = array('name', 'code', 'description', 'category_id', 'type_id', 'version', 'status', 'is_active', 'is_default', 'responsible_id', 'structure_json', 'published_at');
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        foreach (array('category_id', 'type_id', 'version', 'is_active', 'is_default', 'responsible_id') as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $payload[$field] !== '' && $payload[$field] !== null ? (int) $payload[$field] : null;
            }
        }

        foreach (array('name', 'code', 'description', 'status', 'structure_json', 'published_at') as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                $payload[$field] = trim((string) $payload[$field]);
            }
        }

        if (!array_key_exists('status', $payload) || $payload['status'] === '') {
            $payload['status'] = 'draft';
        }
        if (!array_key_exists('is_active', $payload) || $payload['is_active'] === null) {
            $payload['is_active'] = 1;
        }
        if (!array_key_exists('is_default', $payload) || $payload['is_default'] === null) {
            $payload['is_default'] = 0;
        }
        if (!array_key_exists('version', $payload) || $payload['version'] < 1) {
            $payload['version'] = 1;
        }
        if (empty($payload['structure_json'])) {
            $payload['structure_json'] = $this->encode_structure_json($structure);
        }

        return $payload;
    }

    private function encode_structure_json(array $structure): string
    {
        return laudostecnicos_safe_json(array(
            'groups' => is_array(get_array_value($structure, 'groups')) ? get_array_value($structure, 'groups') : array(),
            'items' => is_array(get_array_value($structure, 'items')) ? get_array_value($structure, 'items') : array(),
        ));
    }

    private function decode_structure_json(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : array('groups' => array(), 'items' => array());
    }

    private function clear_default_flag()
    {
        return $this->db->table($this->db->prefixTable($this->table))->where('deleted', 0)->update(array('is_default' => 0, 'updated_at' => get_current_utc_time()));
    }

    private function clone_as_new_version(int $id, array $payload, array $structure)
    {
        $current = $this->get_one_with_structure($id);
        if (!$current || !$current->id) {
            return false;
        }

        $new_payload = array_merge(array(
            'name' => $payload['name'] ?? $current->name,
            'code' => $payload['code'] ?? $current->code,
            'description' => $payload['description'] ?? $current->description,
            'category_id' => $payload['category_id'] ?? $current->category_id,
            'type_id' => $payload['type_id'] ?? $current->type_id,
            'version' => (int) ($current->version ?? 1) + 1,
            'status' => 'draft',
            'is_active' => array_key_exists('is_active', $payload) ? (int) $payload['is_active'] : (int) ($current->is_active ?? 1),
            'is_default' => array_key_exists('is_default', $payload) ? (int) $payload['is_default'] : 0,
            'responsible_id' => $payload['responsible_id'] ?? $current->responsible_id,
            'structure_json' => $this->encode_structure_json($structure ?: $current->structure ?: array()),
            'published_at' => null,
            'created_by' => (int) ($payload['created_by'] ?? $current->created_by ?? 0),
            'updated_by' => (int) ($payload['updated_by'] ?? $current->updated_by ?? 0),
        ), $payload);

        unset($new_payload['id']);
        $new_payload['version'] = (int) ($current->version ?? 1) + 1;
        $new_payload['status'] = 'draft';
        $new_payload['published_at'] = null;
        $new_payload['created_at'] = get_current_utc_time();
        $new_payload['updated_at'] = get_current_utc_time();

        return $this->db->table($this->db->prefixTable($this->table))->insert($new_payload) ? $this->db->insertID() : false;
    }

    private function generate_code(string $name): string
    {
        $name = strtoupper(preg_replace('/[^A-Z0-9]+/', '-', trim($name)));
        $name = trim($name, '-');
        return ($name ?: 'CHK') . '-' . strtoupper(substr(sha1($name . microtime(true)), 0, 6));
    }
}
