<?php

namespace PontoRH\Models;

class PontoRh_treatment_history_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_treatment_history';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log_action(array $data)
    {
        if (!$this->hasTable()) {
            return true;
        }

        $payload = array(
            'treatment_case_id' => get_array_value($data, 'treatment_case_id'),
            'team_member_id' => get_array_value($data, 'team_member_id'),
            'user_id' => get_array_value($data, 'user_id'),
            'action' => get_array_value($data, 'action'),
            'old_value_json' => get_array_value($data, 'old_value_json'),
            'new_value_json' => get_array_value($data, 'new_value_json'),
            'justification' => get_array_value($data, 'justification'),
            'ip_address' => get_array_value($data, 'ip_address', ''),
            'source' => get_array_value($data, 'source', 'manual'),
            'status' => get_array_value($data, 'status', 'logged'),
            'created_by' => get_array_value($data, 'created_by'),
            'created_at' => get_array_value($data, 'created_at', get_current_utc_time()),
            'updated_at' => null,
            'deleted' => 0,
        );

        $payload['hash'] = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '|' . microtime(true));
        return $this->ci_save($payload);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT h.*,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name,
                    CONCAT(TRIM(COALESCE(cu.first_name, '')), ' ', TRIM(COALESCE(cu.last_name, ''))) AS creator_name
                FROM {$table} h
                LEFT JOIN {$users_table} u ON u.id = h.team_member_id
                LEFT JOIN {$users_table} cu ON cu.id = h.created_by
                WHERE h.deleted = 0";

        $case_id = (int) get_array_value($options, 'treatment_case_id');
        if ($case_id) {
            $sql .= ' AND h.treatment_case_id = ' . $case_id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND h.team_member_id = ' . $team_member_id;
        }

        $action = trim((string) get_array_value($options, 'action'));
        if ($action !== '') {
            $sql .= ' AND h.action = ' . $this->db->escape($action);
        }

        $sql .= ' ORDER BY h.created_at DESC, h.id DESC';
        return $this->queryOrEmpty($sql);
    }
}
