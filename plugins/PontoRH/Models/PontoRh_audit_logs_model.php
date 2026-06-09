<?php

namespace PontoRH\Models;

class PontoRh_audit_logs_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_audit_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log_action($data)
    {
        if (!$this->hasTable()) {
            return true;
        }

        $payload = array(
            'team_member_id' => get_array_value($data, 'team_member_id'),
            'user_id' => get_array_value($data, 'user_id'),
            'entity_type' => get_array_value($data, 'entity_type'),
            'entity_id' => get_array_value($data, 'entity_id'),
            'action' => get_array_value($data, 'action'),
            'description' => get_array_value($data, 'description'),
            'payload_json' => get_array_value($data, 'payload_json'),
            'ip_address' => get_array_value($data, 'ip_address', ''),
            'source' => get_array_value($data, 'source', 'system'),
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

        $sql = "SELECT a.*,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name,
                    CONCAT(TRIM(COALESCE(cu.first_name, '')), ' ', TRIM(COALESCE(cu.last_name, ''))) AS creator_name
                FROM {$table} a
                LEFT JOIN {$users_table} u ON u.id = a.team_member_id
                LEFT JOIN {$users_table} cu ON cu.id = a.created_by
                WHERE a.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND a.id = ' . $id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND a.team_member_id = ' . $team_member_id;
        }

        $user_id = (int) get_array_value($options, 'user_id');
        if ($user_id) {
            $sql .= ' AND a.user_id = ' . $user_id;
        }

        $entity_type = trim((string) get_array_value($options, 'entity_type'));
        if ($entity_type !== '') {
            $sql .= ' AND a.entity_type = ' . $this->db->escape($entity_type);
        }

        $action = trim((string) get_array_value($options, 'action'));
        if ($action !== '') {
            $sql .= ' AND a.action = ' . $this->db->escape($action);
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $sql .= ' AND a.status = ' . $this->db->escape($status);
        }

        $date_from = trim((string) get_array_value($options, 'date_from'));
        if ($date_from !== '') {
            $sql .= ' AND DATE(a.created_at) >= ' . $this->db->escape($date_from);
        }

        $date_to = trim((string) get_array_value($options, 'date_to'));
        if ($date_to !== '') {
            $sql .= ' AND DATE(a.created_at) <= ' . $this->db->escape($date_to);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (a.description LIKE '%{$search}%' ESCAPE '!'"
                . " OR a.action LIKE '%{$search}%' ESCAPE '!'"
                . " OR a.entity_type LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.first_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.last_name LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= ' ORDER BY a.created_at DESC, a.id DESC';
        return $this->queryOrEmpty($sql);
    }
}
