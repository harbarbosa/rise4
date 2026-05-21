<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Import_logs_model extends Crud_model
{
    protected $table = 'fv_import_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('import_type', 'source_type', 'source_path', 'status', 'rows_read', 'created_count', 'updated_count', 'ignored_count', 'error_count', 'errors_json', 'summary_json', 'started_at', 'finished_at', 'created_by', 'created_at', 'updated_at', 'deleted');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function register_log($data = array())
    {
        $table = $this->db->prefixTable($this->table);
        if (!$this->db->tableExists($table)) {
            return false;
        }

        $payload = array(
            'import_type' => trim((string) get_array_value($data, 'import_type')),
            'source_type' => trim((string) get_array_value($data, 'source_type')),
            'source_path' => trim((string) get_array_value($data, 'source_path')),
            'status' => trim((string) get_array_value($data, 'status')),
            'rows_read' => (int) get_array_value($data, 'rows_read'),
            'created_count' => (int) get_array_value($data, 'created_count'),
            'updated_count' => (int) get_array_value($data, 'updated_count'),
            'ignored_count' => (int) get_array_value($data, 'ignored_count'),
            'error_count' => (int) get_array_value($data, 'error_count'),
            'errors_json' => get_array_value($data, 'errors_json'),
            'summary_json' => get_array_value($data, 'summary_json'),
            'started_at' => get_array_value($data, 'started_at') ?: get_current_utc_time(),
            'finished_at' => get_array_value($data, 'finished_at') ?: get_current_utc_time(),
            'created_by' => get_array_value($data, 'created_by'),
            'created_at' => get_array_value($data, 'created_at') ?: get_current_utc_time(),
            'updated_at' => get_array_value($data, 'updated_at') ?: get_current_utc_time(),
            'deleted' => 0,
        );

        return $this->ci_save($payload);
    }
}
