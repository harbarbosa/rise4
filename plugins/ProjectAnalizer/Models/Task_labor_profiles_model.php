<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Task_labor_profiles_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "pa_task_labor_profiles";
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        $profiles_table = $this->db->prefixTable("pa_labor_profiles");
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

        $labor_profile_id = $this->_get_clean_value($options, "labor_profile_id");
        if ($labor_profile_id) {
            $where .= " AND $table.labor_profile_id=$labor_profile_id";
        }

        $sql = "SELECT $table.*, "
            . "$profiles_table.name AS labor_profile_name, "
            . "$profiles_table.hourly_cost AS labor_hourly_cost, "
            . "$profiles_table.default_hours_per_day AS labor_default_hours_per_day "
            . "FROM $table "
            . "LEFT JOIN $profiles_table ON $profiles_table.id=$table.labor_profile_id "
            . "WHERE 1=1 $where ORDER BY $table.id ASC";

        return $this->db->query($sql);
    }

    public function get_task_profiles($task_id)
    {
        return $this->get_details(array("task_id" => $task_id));
    }

    public function create_task_profile($data)
    {
        if (!$this->_validate_task_profile($data)) {
            return false;
        }

        $data_ref = $data;
        return $this->ci_save($data_ref, 0);
    }

    public function update_task_profile($id, $data)
    {
        if (!$id || !$this->_validate_task_profile($data, true)) {
            return false;
        }

        $data_ref = $data;
        return $this->ci_save($data_ref, (int)$id);
    }

    public function delete_task_profile($id)
    {
        return parent::delete($id, false);
    }

    public function upsert_task_profiles($task_id, $items)
    {
        $task_id = (int)$task_id;
        if (!$task_id || !is_array($items)) {
            return false;
        }

        if (!count($items)) {
            return $this->_delete_task_profiles($task_id);
        }

        $project_id = $this->_resolve_project_id($items, $task_id);
        $existing_ids = $this->_get_task_profile_ids($task_id);
        $kept_ids = array();
        $this->db->transStart();

        foreach ($items as $item) {
            if (!is_array($item)) {
                $this->db->transRollback();
                return false;
            }

            $payload = $item;
            $payload["task_id"] = $task_id;
            if ($project_id && !isset($payload["project_id"])) {
                $payload["project_id"] = $project_id;
            }

            $id = get_array_value($payload, "id");
            if ($id) {
                $kept_ids[] = (int)$id;
                if (!$this->update_task_profile($id, $payload)) {
                    $this->db->transRollback();
                    return false;
                }
            } else {
                if (!$this->create_task_profile($payload)) {
                    $this->db->transRollback();
                    return false;
                }
            }
        }

        $remove_ids = array_diff($existing_ids, $kept_ids);
        if ($remove_ids) {
            if (!$this->_delete_task_profiles($task_id, $remove_ids)) {
                $this->db->transRollback();
                return false;
            }
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    private function _resolve_project_id($items, $task_id)
    {
        foreach ($items as $item) {
            if (is_array($item)) {
                $project_id = get_array_value($item, "project_id");
                if ($project_id) {
                    return (int)$project_id;
                }
            }
        }

        $tasks_table = $this->db->prefixTable("tasks");
        $row = $this->db->query("SELECT project_id FROM $tasks_table WHERE id=$task_id")->getRow();
        return $row && $row->project_id ? (int)$row->project_id : 0;
    }

    private function _get_task_profile_ids($task_id)
    {
        $task_id = (int)$task_id;
        if (!$task_id) {
            return array();
        }

        $table = $this->db->prefixTable($this->table_without_prefix);
        $rows = $this->db->query("SELECT id FROM $table WHERE task_id=$task_id")->getResult();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int)$row->id;
        }

        return $ids;
    }

    private function _delete_task_profiles($task_id, $ids = array())
    {
        $task_id = (int)$task_id;
        $table = $this->db->prefixTable($this->table_without_prefix);
        $builder = $this->db->table($table);
        $builder->where("task_id", $task_id);
        if ($ids) {
            $builder->whereIn("id", $ids);
        }

        return $builder->delete();
    }

    private function _validate_task_profile($data, $is_update = false)
    {
        if (!$is_update) {
            $project_id = get_array_value($data, "project_id");
            if (!$project_id) {
                return false;
            }

            $task_id = get_array_value($data, "task_id");
            if (!$task_id) {
                return false;
            }

            $labor_profile_id = get_array_value($data, "labor_profile_id");
            if (!$labor_profile_id) {
                return false;
            }
        }

        if (array_key_exists("labor_profile_id", $data)) {
            $labor_profile_id = get_array_value($data, "labor_profile_id");
            if (!$labor_profile_id) {
                return false;
            }
        }

        if (array_key_exists("qty_people", $data)) {
            $qty_people = get_array_value($data, "qty_people");
            if ($qty_people === null || !is_numeric($qty_people) || (float)$qty_people <= 0) {
                return false;
            }
        }

        if (array_key_exists("hours_per_day", $data)) {
            $hours_per_day = get_array_value($data, "hours_per_day");
            if ($hours_per_day !== null && $hours_per_day !== "") {
                if (!is_numeric($hours_per_day) || (float)$hours_per_day <= 0) {
                    return false;
                }
            }
        }

        return true;
    }
}
