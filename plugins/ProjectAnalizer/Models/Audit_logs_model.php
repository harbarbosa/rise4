<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Audit_logs_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "projectanalizer_audit_logs";
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        try {
            $db = db_connect("default");
            if (!$db->tableExists($table)) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }
        $where = "";

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $table.project_id=$project_id";
        }

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.id DESC";
        return $this->db->query($sql);
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }

        $data_ref = $row;
        return $this->ci_save($data_ref, $id) ? true : false;
    }

    public function delete($id = 0, $undo = false)
    {
        return parent::delete($id, $undo);
    }
}
