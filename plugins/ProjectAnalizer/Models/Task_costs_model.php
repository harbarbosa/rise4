<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Task_costs_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "projectanalizer_task_costs";
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        $where = "";
        $scope_conditions = array();

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $scope_conditions[] = "$table.project_id=$project_id";
        }

        $task_id = $this->_get_clean_value($options, "task_id");
        if ($task_id) {
            $scope_conditions[] = "$table.task_id=$task_id";
        }

        $task_ids = get_array_value($options, "task_ids");
        if ($task_ids && is_array($task_ids)) {
            $task_ids = array_values(array_filter(array_map("intval", $task_ids)));
            if (count($task_ids)) {
                $scope_conditions[] = "$table.task_id IN (" . implode(",", $task_ids) . ")";
            }
        }

        if ($scope_conditions) {
            $where .= " AND (" . implode(" OR ", $scope_conditions) . ")";
        }

        $cost_type = $this->_get_clean_value($options, "cost_type");
        if ($cost_type) {
            $where .= " AND $table.cost_type='$cost_type'";
        }

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.id ASC";
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
