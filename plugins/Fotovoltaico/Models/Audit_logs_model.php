<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Audit_logs_model extends Crud_model
{
    protected $table = 'fv_audit_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('entity_type', 'entity_id', 'action', 'old_json', 'new_json', 'changes_json', 'ip_address', 'user_agent', 'created_by', 'created_at', 'updated_at', 'deleted');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_audit_logs');
        $users_table = $this->db->prefixTable('users');
        $where = "WHERE $table.deleted=0";

        $entity_type = trim((string) get_array_value($options, 'entity_type'));
        if ($entity_type !== '') {
            $where .= " AND $table.entity_type=" . $this->db->escape($entity_type);
        }

        $entity_id = (int) get_array_value($options, 'entity_id');
        if ($entity_id) {
            $where .= " AND $table.entity_id=$entity_id";
        }

        $action = trim((string) get_array_value($options, 'action'));
        if ($action !== '') {
            $where .= " AND $table.action=" . $this->db->escape($action);
        }

        $sql = "SELECT $table.*, CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) AS created_by_name
            FROM $table
            LEFT JOIN $users_table ON $users_table.id=$table.created_by AND $users_table.deleted=0
            $where
            ORDER BY $table.id DESC";

        try {
            return $this->db->query($sql);
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Audit logs query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function register_audit($data = array())
    {
        $payload = array(
            'entity_type' => get_array_value($data, 'entity_type'),
            'entity_id' => get_array_value($data, 'entity_id'),
            'action' => get_array_value($data, 'action'),
            'old_json' => get_array_value($data, 'old_json'),
            'new_json' => get_array_value($data, 'new_json'),
            'changes_json' => get_array_value($data, 'changes_json'),
            'ip_address' => get_array_value($data, 'ip_address'),
            'user_agent' => get_array_value($data, 'user_agent'),
            'created_by' => get_array_value($data, 'created_by'),
            'created_at' => get_array_value($data, 'created_at') ?: get_current_utc_time(),
            'updated_at' => get_array_value($data, 'updated_at') ?: get_current_utc_time(),
            'deleted' => 0,
        );

        return $this->ci_save($payload) ? true : false;
    }
}
