<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Insolation_cache_model extends Crud_model
{
    protected $table = 'fv_insolation_cache';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('cache_key', 'provider', 'location_label', 'latitude', 'longitude', 'year', 'month', 'payload_json', 'expires_at', 'fetched_at', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_insolation_cache');
        $where = "WHERE $table.deleted=0";

        $cache_key = trim((string) get_array_value($options, 'cache_key'));
        if ($cache_key !== '') {
            $where .= " AND $table.cache_key=" . $this->db->escape($cache_key);
        }

        $provider = trim((string) get_array_value($options, 'provider'));
        if ($provider !== '') {
            $where .= " AND $table.provider=" . $this->db->escape($provider);
        }

        try {
            return $this->db->query("SELECT * FROM $table $where ORDER BY $table.id DESC");
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Insolation cache query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_cache_by_key($cache_key)
    {
        return $this->get_details(array('cache_key' => (string) $cache_key))->getRow();
    }
}
