<?php

namespace OrdemServico\Models;

use App\Models\Crud_model;

class OrdemServico_model extends Crud_model
{
    public function __construct($db = null)
    {
        parent::__construct('os_ordens', $db);
    }

    // Centralize field filtering and save logic (Demo-style)
    public function save_from_post(array $data, int $id = 0)
    {
        // Normalize data types
        $ints = ['cliente_id', 'tecnico_id', 'tipo_id', 'motivo_id', 'project_id', 'task_id', 'contract_id', 'created_by'];
        foreach ($ints as $k) {
            if (array_key_exists($k, $data)) {
                $v = $data[$k];
                $data[$k] = ($v === '' || $v === null) ? null : (int)$v;
            }
        }

        $now = get_my_local_time();
        if ($id) {
            $data['updated_at'] = $now;
        } else {
            // ensure defaults for insert
            $data['created_at'] = $now;
            if (empty($data['data_abertura'])) {
                $data['data_abertura'] = get_my_local_time('Y-m-d');
            }
        }

        // Filter keys to actual table columns to be schema-safe
        try {
            $fields = $this->db->getFieldNames($this->table);
            if (is_array($fields)) {
                $data = array_intersect_key($data, array_flip($fields));
            }
        } catch (\Throwable $e) {
            // keep data as-is; ci_save will fail if invalid
        }

        return $this->ci_save($data, $id);
    }

    // Return result set with joined client/technician names
    public function get_details(array $options = [])
    {
        $os_table = $this->table; // already prefixed
        $clients_table = $this->db->prefixTable('clients');
        $users_table = $this->db->prefixTable('users');
        $tipos_table = $this->db->prefixTable('os_tipos');
        $motivos_table = $this->db->prefixTable('os_motivos');

        $where = "{$os_table}.deleted=0";
        if (!empty($options['id'])) {
            $id = (int)$options['id'];
            $where .= " AND {$os_table}.id=" . $id;
        }

        $sql = "SELECT {$os_table}.*,
                       {$clients_table}.company_name AS client_name,
                       TRIM(CONCAT(COALESCE({$users_table}.first_name,''),' ',COALESCE({$users_table}.last_name,''))) AS tech_name,
                       {$tipos_table}.title AS tipo_title,
                       {$motivos_table}.title AS motivo_title
                FROM {$os_table}
                LEFT JOIN {$clients_table} ON {$clients_table}.id = {$os_table}.cliente_id
                LEFT JOIN {$users_table} ON {$users_table}.id = {$os_table}.tecnico_id
                LEFT JOIN {$tipos_table} ON {$tipos_table}.id = {$os_table}.tipo_id
                LEFT JOIN {$motivos_table} ON {$motivos_table}.id = {$os_table}.motivo_id
                WHERE {$where}
                ORDER BY {$os_table}.id DESC";

        try {
            $q = $this->db->query($sql);
            if ($q) { return $q; }
        } catch (\Throwable $e) {
            // fall back to minimal details if joined tables are missing
        }

        // Fallback: no joins (avoids fatal when tables missing)
        $fallback = "SELECT {$os_table}.*,
                            NULL AS client_name,
                            NULL AS tech_name,
                            NULL AS tipo_title,
                            NULL AS motivo_title
                     FROM {$os_table}
                     WHERE {$where}
                     ORDER BY {$os_table}.id DESC";
        return $this->db->query($fallback);
    }
}
