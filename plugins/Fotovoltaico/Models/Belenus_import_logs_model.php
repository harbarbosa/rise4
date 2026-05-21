<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Belenus_import_logs_model extends Crud_model
{
    protected $table = 'fv_belenus_import_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('provider', 'entity_type', 'external_id', 'local_id', 'action', 'status', 'message', 'payload_json', 'response_json', 'created_by', 'created_at', 'updated_at', 'deleted');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function register_log($data = array())
    {
        $payload = array(
            'provider' => get_array_value($data, 'provider') ?: 'belenus',
            'entity_type' => get_array_value($data, 'entity_type'),
            'external_id' => get_array_value($data, 'external_id'),
            'local_id' => get_array_value($data, 'local_id'),
            'action' => get_array_value($data, 'action'),
            'status' => get_array_value($data, 'status') ?: 'completed',
            'message' => get_array_value($data, 'message'),
            'payload_json' => get_array_value($data, 'payload_json'),
            'response_json' => get_array_value($data, 'response_json'),
            'created_by' => get_array_value($data, 'created_by'),
            'created_at' => get_array_value($data, 'created_at') ?: get_current_utc_time(),
            'updated_at' => get_array_value($data, 'updated_at') ?: get_current_utc_time(),
            'deleted' => 0,
        );

        return $this->ci_save($payload);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $where = "WHERE $table.deleted=0";

        $provider = trim((string) get_array_value($options, 'provider'));
        if ($provider !== '') {
            $where .= " AND $table.provider=" . $this->db->escape($provider);
        }

        $entity_type = trim((string) get_array_value($options, 'entity_type'));
        if ($entity_type !== '') {
            $where .= " AND $table.entity_type=" . $this->db->escape($entity_type);
        }

        try {
            return $this->db->query("SELECT * FROM $table $where ORDER BY $table.id DESC");
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Belenus import logs query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }
}
