<?php

namespace LaudosTecnicos\Models;

class LaudoAuditLogs_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_audit_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log_action(array $data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = array(
            'entity_type' => get_array_value($data, 'entity_type') ?: '',
            'entity_id' => (int) (get_array_value($data, 'entity_id') ?: 0),
            'action' => get_array_value($data, 'action') ?: '',
            'user_id' => (int) (get_array_value($data, 'user_id') ?: 0),
            'ip_address' => get_array_value($data, 'ip_address') ?: '',
            'source' => get_array_value($data, 'source') ?: 'web',
            'old_values_json' => get_array_value($data, 'old_values_json') ?: '',
            'new_values_json' => get_array_value($data, 'new_values_json') ?: '',
            'description' => get_array_value($data, 'description') ?: '',
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'created_at' => get_array_value($data, 'created_at') ?: get_current_utc_time(),
        );

        return $this->db->table($this->db->prefixTable($this->table))->insert($payload);
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $audit_table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');
        $where = " WHERE 1=1";

        $entity_type = trim((string) get_array_value($options, 'entity_type'));
        if ($entity_type !== '') {
            $where .= " AND $audit_table.entity_type = '" . $this->db->escapeString($entity_type) . "'";
        }

        $entity_id = (int) get_array_value($options, 'entity_id');
        if ($entity_id) {
            $where .= " AND $audit_table.entity_id = " . $entity_id;
        }

        return $this->queryOrEmpty("SELECT $audit_table.*,
                CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) AS user_name
            FROM $audit_table
            LEFT JOIN $users_table ON $users_table.id = $audit_table.user_id AND $users_table.deleted = 0
            $where
            ORDER BY $audit_table.created_at DESC");
    }
}
