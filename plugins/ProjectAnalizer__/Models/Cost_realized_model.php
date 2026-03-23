<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Cost_realized_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "projectanalizer_cost_realized";
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

        $task_id = $this->_get_clean_value($options, "task_id");
        if ($task_id) {
            $where .= " AND $table.task_id=$task_id";
        }

        $cost_type = $this->_get_clean_value($options, "cost_type");
        if ($cost_type) {
            $where .= " AND $table.cost_type='$cost_type'";
        }

        $date_gte = $this->_get_clean_value($options, "date_gte");
        if ($date_gte) {
            $where .= " AND $table.date>='$date_gte'";
        }

        $date_lte = $this->_get_clean_value($options, "date_lte");
        if ($date_lte) {
            $where .= " AND $table.date<='$date_lte'";
        }

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.date DESC, $table.id DESC";
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
