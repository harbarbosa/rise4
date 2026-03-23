<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Project_snapshots_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "projectanalizer_project_snapshots";
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $table.project_id=$project_id";
        }

        $ref_date = $this->_get_clean_value($options, "ref_date");
        if ($ref_date) {
            $where .= " AND $table.ref_date='$ref_date'";
        }

        $date_from = $this->_get_clean_value($options, "date_from");
        if ($date_from) {
            $where .= " AND $table.ref_date>='$date_from'";
        }

        $date_to = $this->_get_clean_value($options, "date_to");
        if ($date_to) {
            $where .= " AND $table.ref_date<='$date_to'";
        }

        $order = $this->_get_clean_value($options, "order");
        $order = $order === "ASC" ? "ASC" : "DESC";

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.ref_date $order, $table.id $order";
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
