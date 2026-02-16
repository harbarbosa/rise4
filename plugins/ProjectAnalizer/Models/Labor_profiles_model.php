<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Labor_profiles_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "pa_labor_profiles";
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

        $active = $this->_get_clean_value($options, "active");
        if ($active !== "" && $active !== null) {
            $active = (int)$active;
            $where .= " AND $table.active=$active";
        }

        $name = $this->_get_clean_value($options, "name");
        if ($name) {
            $name = $this->db->escapeString($name);
            $where .= " AND $table.name LIKE '%{$name}%'";
        }

        $sql = "SELECT $table.* FROM $table WHERE 1=1 $where ORDER BY $table.name ASC, $table.id ASC";
        return $this->db->query($sql);
    }

    public function get_active_profiles()
    {
        return $this->get_details(array("active" => 1));
    }

    public function create_profile($data)
    {
        if (!$this->_validate_profile($data)) {
            return false;
        }

        $data_ref = $data;
        return $this->ci_save($data_ref, 0);
    }

    public function update_profile($id, $data)
    {
        if (!$id || !$this->_validate_profile($data, true)) {
            return false;
        }

        $data_ref = $data;
        return $this->ci_save($data_ref, (int)$id);
    }

    public function delete_profile($id)
    {
        $id = (int)$id;
        if (!$id) {
            return false;
        }

        $table = $this->db->prefixTable($this->table_without_prefix);
        return $this->db->table($table)->where("id", $id)->delete();
    }

    private function _validate_profile($data, $is_update = false)
    {
        if (!$is_update) {
            $hourly_cost = get_array_value($data, "hourly_cost");
            if ($hourly_cost === null || !is_numeric($hourly_cost) || (float)$hourly_cost <= 0) {
                return false;
            }
        }

        if (array_key_exists("hourly_cost", $data)) {
            $hourly_cost = get_array_value($data, "hourly_cost");
            if ($hourly_cost === null || !is_numeric($hourly_cost) || (float)$hourly_cost <= 0) {
                return false;
            }
        }

        return true;
    }
}
