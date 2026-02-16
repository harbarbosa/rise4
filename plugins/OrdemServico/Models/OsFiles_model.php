<?php

namespace OrdemServico\Models;

use App\Models\Crud_model;

class OsFiles_model extends Crud_model
{
    public function __construct($db = null)
    {
        parent::__construct('os_files', $db);
    }

    public function get_details($options = [])
    {
        $table = $this->db->prefixTable('os_files');
        $users = $this->db->prefixTable('users');
        $where = "WHERE {$table}.deleted=0";
        $os_id = $this->_get_clean_value($options, 'os_id');
        $id = $this->_get_clean_value($options, 'id');
        if ($os_id) { $where .= " AND {$table}.os_id={$os_id}"; }
        if ($id) { $where .= " AND {$table}.id={$id}"; }
        $sql = "SELECT {$table}.*, CONCAT({$users}.first_name,' ',{$users}.last_name) AS uploaded_by_name, {$users}.image AS uploaded_by_image FROM {$table} LEFT JOIN {$users} ON {$users}.id={$table}.uploaded_by {$where} ORDER BY {$table}.id DESC";
        return $this->db->query($sql);
    }
}

