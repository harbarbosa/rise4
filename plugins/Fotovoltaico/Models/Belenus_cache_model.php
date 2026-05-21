<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Belenus_cache_model extends Crud_model
{
    protected $table = 'fv_belenus_cache';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('cache_key', 'cache_type', 'payload_json', 'expires_at', 'created_by', 'created_at', 'updated_at', 'deleted');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_valid_cache($cache_key = '')
    {
        $row = $this->get_by_cache_key($cache_key);
        if (!$row || !$row->id) {
            return null;
        }

        $expires_at = trim((string) ($row->expires_at ?? ''));
        if ($expires_at !== '' && strtotime($expires_at) < time()) {
            return null;
        }

        return $row;
    }

    public function get_by_cache_key($cache_key = '')
    {
        $cache_key = trim((string) $cache_key);
        if ($cache_key === '') {
            return null;
        }

        $table = $this->db->prefixTable($this->table);
        if (!$this->db->tableExists($table)) {
            return null;
        }

        $sql = "SELECT * FROM {$table}
            WHERE deleted=0
            AND cache_key=" . $this->db->escape($cache_key) . "
            ORDER BY id DESC
            LIMIT 1";

        try {
            return $this->db->query($sql)->getRow();
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Belenus cache query error: ' . $e->getMessage());
            return null;
        }
    }

    public function put_cache($data = array())
    {
        $cache_key = trim((string) get_array_value($data, 'cache_key'));
        if ($cache_key === '') {
            return false;
        }

        $existing = $this->get_by_cache_key($cache_key);
        $payload = array(
            'cache_key' => $cache_key,
            'cache_type' => trim((string) get_array_value($data, 'cache_type')),
            'payload_json' => get_array_value($data, 'payload_json'),
            'expires_at' => get_array_value($data, 'expires_at'),
            'created_by' => get_array_value($data, 'created_by'),
            'updated_at' => get_my_local_time(),
            'deleted' => 0,
        );

        if (!$existing || !$existing->id) {
            $payload['created_at'] = get_my_local_time();
        }

        return $this->ci_save($payload, $existing && $existing->id ? $existing->id : 0);
    }

    public function clear_all()
    {
        $table = $this->db->prefixTable($this->table);
        if (!$this->db->tableExists($table)) {
            return false;
        }

        return $this->db->table($table)->update(array(
            'deleted' => 1,
            'updated_at' => get_my_local_time(),
        ));
    }
}
