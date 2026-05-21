<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Integration_logs_model extends Crud_model
{
    protected $table = 'fv_integration_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('provider', 'endpoint', 'method', 'request_json', 'response_json', 'http_status', 'latency_ms', 'cache_hit', 'success', 'error_message', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_integration_logs');
        $where = "WHERE $table.deleted=0";

        $provider = trim((string) get_array_value($options, 'provider'));
        if ($provider !== '') {
            $where .= " AND $table.provider=" . $this->db->escape($provider);
        }

        $http_status = get_array_value($options, 'http_status');
        if ($http_status !== null && $http_status !== '') {
            $where .= " AND $table.http_status=" . (int) $http_status;
        }

        try {
            return $this->db->query("SELECT * FROM $table $where ORDER BY $table.id DESC");
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Integration logs query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function register_log($data = array())
    {
        $payload = array(
            'provider' => get_array_value($data, 'provider'),
            'endpoint' => get_array_value($data, 'endpoint'),
            'method' => get_array_value($data, 'method'),
            'request_json' => get_array_value($data, 'request_json'),
            'response_json' => get_array_value($data, 'response_json'),
            'http_status' => get_array_value($data, 'http_status'),
            'latency_ms' => get_array_value($data, 'latency_ms'),
            'cache_hit' => get_array_value($data, 'cache_hit') ? 1 : 0,
            'success' => get_array_value($data, 'success') ? 1 : 0,
            'error_message' => get_array_value($data, 'error_message'),
            'created_by' => get_array_value($data, 'created_by'),
            'created_at' => get_array_value($data, 'created_at') ?: get_current_utc_time(),
            'updated_at' => get_array_value($data, 'updated_at') ?: get_current_utc_time(),
            'deleted' => 0,
        );

        return $this->ci_save($payload) ? true : false;
    }
}
