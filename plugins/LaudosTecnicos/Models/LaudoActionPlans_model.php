<?php

namespace LaudosTecnicos\Models;

class LaudoActionPlans_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_action_plans';

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
        $nc = $this->db->prefixTable('laudo_nonconformities');
        $clients = $this->db->prefixTable('clients');
        $laudos = $this->db->prefixTable('laudos');
        $users = $this->db->prefixTable('users');
        $tasks = $this->db->prefixTable('tasks');

        $where = " WHERE $table.deleted = 0";
        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status = '" . $this->db->escapeString($status) . "'";
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.code LIKE '%$search%' OR $table.action LIKE '%$search%' OR $table.motive LIKE '%$search%' OR $nc.title LIKE '%$search%')";
        }

        return $this->queryOrEmpty("SELECT $table.*,
                $nc.code AS nc_code,
                $nc.title AS nc_title,
                $nc.status AS nc_status,
                c.company_name AS client_name,
                l.title AS laudo_title,
                CONCAT(IFNULL(r.first_name, ''), ' ', IFNULL(r.last_name, '')) AS responsible_name,
                CONCAT(IFNULL(v.first_name, ''), ' ', IFNULL(v.last_name, '')) AS validator_name,
                t.title AS task_title,
                t.deadline AS task_deadline
            FROM $table
            INNER JOIN $nc ON $nc.id = $table.nonconformity_id AND $nc.deleted = 0
            LEFT JOIN $clients c ON c.id = $nc.client_id AND c.deleted = 0
            LEFT JOIN $laudos l ON l.id = $nc.laudo_id AND l.deleted = 0
            LEFT JOIN $users r ON r.id = $table.responsible_id AND r.deleted = 0
            LEFT JOIN $users v ON v.id = $table.validator_id AND v.deleted = 0
            LEFT JOIN $tasks t ON t.id = $table.task_id AND t.deleted = 0
            $where
            ORDER BY $table.deadline ASC, $table.id DESC");
    }

    public function save_from_post(array $data, ?int $id = null)
    {
        if ($id) {
            $current = $this->get_one($id);
            if ($current && $current->id) {
                foreach (array('task_id', 'task_sync_hash', 'task_sync_source', 'task_synced_at', 'last_sync_payload_json') as $field) {
                    if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
                        $data[$field] = $current->$field ?? null;
                    }
                }
            }
        }

        $data = $this->normalize($data);
        $data['updated_at'] = get_current_utc_time();

        if ($id) {
            return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($data);
        }

        $data['created_at'] = get_current_utc_time();
        $inserted = $this->db->table($this->db->prefixTable($this->table))->insert($data);
        return $inserted ? $this->db->insertID() : false;
    }

    public function get_by_nc(int $nonconformity_id)
    {
        if (!$this->hasTable() || !$nonconformity_id) {
            return array();
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('nonconformity_id', $nonconformity_id)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function sync_task_from_plan(array $payload)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $plan_id = (int) get_array_value($payload, 'plan_id');
        if (!$plan_id) {
            return false;
        }

        $plan = $this->get_one($plan_id);
        if (!$plan || !$plan->id) {
            return false;
        }

        $task_model = model('App\\Models\\Tasks_model');
        if (!$task_model) {
            return false;
        }

        $task_data = array(
            'title' => trim((string) (get_array_value($payload, 'task_title') ?: $plan->action ?: $plan->code ?: 'Plano de ação')),
            'description' => trim((string) (get_array_value($payload, 'task_description') ?: $plan->motive ?: $plan->what_field ?: '')),
            'client_id' => (int) get_array_value($payload, 'client_id'),
            'project_id' => (int) get_array_value($payload, 'project_id'),
            'assigned_to' => (int) (get_array_value($payload, 'assigned_to') ?: $plan->responsible_id),
            'priority_id' => (int) (get_array_value($payload, 'priority_id') ?: 0),
            'deadline' => trim((string) (get_array_value($payload, 'deadline') ?: $plan->deadline)),
            'status_id' => (int) (get_array_value($payload, 'status_id') ?: 1),
            'created_by' => (int) (get_array_value($payload, 'created_by') ?: 0),
        );

        $task_id = (int) $plan->task_id;
        if (!$task_id) {
            $task_id = (int) $task_model->ci_save($task_data);
            if (!$task_id) {
                return false;
            }
        } else {
            $task_model->ci_save($task_data, $task_id);
        }

        $hash = sha1(laudostecnicos_safe_json($task_data));
        $this->db->table($this->db->prefixTable($this->table))->where('id', $plan_id)->update(array(
            'task_id' => $task_id ?: $plan->task_id,
            'task_sync_hash' => $hash,
            'task_sync_source' => get_array_value($payload, 'sync_source') ?: 'plan',
            'task_synced_at' => get_current_utc_time(),
            'last_sync_payload_json' => laudostecnicos_safe_json($task_data),
            'updated_at' => get_current_utc_time(),
        ));

        return true;
    }

    public function sync_from_task(array $task_data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $task_id = (int) get_array_value($task_data, 'task_id');
        if (!$task_id) {
            return false;
        }

        $plan = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('task_id', $task_id)
            ->get()
            ->getRow();

        if (!$plan || !$plan->id) {
            return false;
        }

        $task_title = trim((string) get_array_value($task_data, 'title'));
        $task_description = trim((string) get_array_value($task_data, 'description'));
        $payload = array(
            'action' => $task_title ?: $plan->action,
            'motive' => $task_description ?: $plan->motive,
            'deadline' => trim((string) get_array_value($task_data, 'deadline')) ?: $plan->deadline,
            'responsible_id' => (int) get_array_value($task_data, 'assigned_to') ?: $plan->responsible_id,
            'priority' => $this->mapPriority((int) get_array_value($task_data, 'priority_id')),
            'status' => $this->mapTaskStatus((int) get_array_value($task_data, 'status_id')),
            'task_sync_source' => 'task',
            'task_sync_hash' => sha1(laudostecnicos_safe_json($task_data)),
            'task_synced_at' => get_current_utc_time(),
            'last_sync_payload_json' => laudostecnicos_safe_json($task_data),
            'updated_at' => get_current_utc_time(),
        );

        return (bool) $this->db->table($this->db->prefixTable($this->table))->where('id', (int) $plan->id)->update($payload);
    }

    public function create_task_from_plan(int $plan_id, array $overrides = array())
    {
        return $this->sync_task_from_plan(array_merge($overrides, array('plan_id' => $plan_id, 'sync_source' => 'plan')));
    }

    public function get_dashboard_stats(array $options = array()): array
    {
        if (!$this->hasTable()) {
            return array('open' => 0, 'late' => 0, 'done' => 0, 'validated' => 0);
        }

        $table = $this->db->prefixTable($this->table);
        $nc = $this->db->prefixTable('laudo_nonconformities');
        $where = " WHERE $table.deleted = 0";
        $client_id = (int) get_array_value($options, 'client_id');
        if ($client_id) {
            $where .= " AND $nc.client_id = $client_id";
        }

        $row = $this->db->query("SELECT
                SUM(CASE WHEN $table.status IN ('draft','open','in_progress','waiting') THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN $table.deadline IS NOT NULL AND $table.deadline < CURDATE() AND $table.status NOT IN ('done','validated','canceled') THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN $table.status = 'done' THEN 1 ELSE 0 END) AS done_count,
                SUM(CASE WHEN $table.status = 'validated' THEN 1 ELSE 0 END) AS validated_count
            FROM $table
            INNER JOIN $nc ON $nc.id = $table.nonconformity_id AND $nc.deleted = 0
            $where")->getRow();

        return array(
            'open' => (int) ($row->open_count ?? 0),
            'late' => (int) ($row->late_count ?? 0),
            'done' => (int) ($row->done_count ?? 0),
            'validated' => (int) ($row->validated_count ?? 0),
        );
    }

    private function normalize(array $data): array
    {
        $evidence = get_array_value($data, 'evidence_json');

        return array(
            'nonconformity_id' => (int) get_array_value($data, 'nonconformity_id'),
            'code' => trim((string) get_array_value($data, 'code')),
            'action' => trim((string) get_array_value($data, 'action')),
            'motive' => trim((string) get_array_value($data, 'motive')),
            'location_text' => trim((string) get_array_value($data, 'location_text')),
            'responsible_id' => (int) get_array_value($data, 'responsible_id') ?: null,
            'company_name' => trim((string) get_array_value($data, 'company_name')),
            'method' => trim((string) get_array_value($data, 'method')),
            'deadline' => trim((string) get_array_value($data, 'deadline')),
            'estimated_cost' => (float) get_array_value($data, 'estimated_cost') ?: null,
            'priority' => trim((string) get_array_value($data, 'priority')) ?: 'medium',
            'status' => trim((string) get_array_value($data, 'status')) ?: 'draft',
            'evidence_json' => is_array($evidence) ? laudostecnicos_safe_json($evidence) : trim((string) $evidence),
            'completion_date' => trim((string) get_array_value($data, 'completion_date')),
            'validator_id' => (int) get_array_value($data, 'validator_id') ?: null,
            'auto_create_task' => get_array_value($data, 'auto_create_task') ? 1 : 0,
            'task_sync_enabled' => get_array_value($data, 'task_sync_enabled') ? 1 : 0,
            'what_field' => trim((string) get_array_value($data, 'what_field')),
            'why_field' => trim((string) get_array_value($data, 'why_field')),
            'where_field' => trim((string) get_array_value($data, 'where_field')),
            'when_field' => trim((string) get_array_value($data, 'when_field')),
            'who_field' => trim((string) get_array_value($data, 'who_field')),
            'how_field' => trim((string) get_array_value($data, 'how_field')),
            'how_much_field' => trim((string) get_array_value($data, 'how_much_field')),
            'task_id' => (int) get_array_value($data, 'task_id') ?: null,
            'task_sync_hash' => trim((string) get_array_value($data, 'task_sync_hash')),
            'task_sync_source' => trim((string) get_array_value($data, 'task_sync_source')),
            'task_synced_at' => trim((string) get_array_value($data, 'task_synced_at')),
            'last_sync_payload_json' => trim((string) get_array_value($data, 'last_sync_payload_json')),
            'created_by' => (int) get_array_value($data, 'created_by') ?: null,
            'updated_by' => (int) get_array_value($data, 'updated_by') ?: null,
            'deleted' => 0,
        );
    }

    private function mapPriority(int $priority_id): string
    {
        return match ($priority_id) {
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            4 => 'critical',
            default => 'medium',
        };
    }

    private function mapTaskStatus(int $status_id): string
    {
        return match ($status_id) {
            1 => 'open',
            2 => 'in_progress',
            3 => 'done',
            default => 'open',
        };
    }
}
