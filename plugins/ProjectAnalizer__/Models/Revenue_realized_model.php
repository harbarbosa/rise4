<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Revenue_realized_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = "projectanalizer_revenue_realized";
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

        $planned_id = $this->_get_clean_value($options, "planned_id");
        if ($planned_id) {
            $where .= " AND $table.planned_id=$planned_id";
        }

        $date_gte = $this->_get_clean_value($options, "date_gte");
        if ($date_gte) {
            $where .= " AND $table.realized_date>='$date_gte'";
        }

        $date_lte = $this->_get_clean_value($options, "date_lte");
        if ($date_lte) {
            $where .= " AND $table.realized_date<='$date_lte'";
        }

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.realized_date DESC, $table.id DESC";
        return $this->db->query($sql);
    }

    public function get_realized_by_project($project_id)
    {
        return $this->get_details(array("project_id" => $project_id));
    }

    public function create_realized($data)
    {
        if (!$this->_validate_realized($data)) {
            return false;
        }
        $data_ref = $data;
        return $this->ci_save($data_ref, 0);
    }

    public function update_realized($id, $data)
    {
        if (!$id || !$this->_validate_realized($data, true)) {
            return false;
        }
        $data_ref = $data;
        return $this->ci_save($data_ref, $id);
    }

    public function delete_realized($id)
    {
        return parent::delete($id, false);
    }

    private function _validate_realized($data, $is_update = false)
    {
        $project_id = get_array_value($data, "project_id");
        if (!$project_id && !$is_update) {
            return false;
        }

        $realized_value = get_array_value($data, "realized_value");
        if ($realized_value === null || !is_numeric($realized_value) || (float)$realized_value <= 0) {
            return false;
        }

        $realized_date = get_array_value($data, "realized_date");
        if ($realized_date && !$this->_is_valid_date($realized_date)) {
            return false;
        }

        return true;
    }

    private function _is_valid_date($date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $d = \DateTime::createFromFormat("Y-m-d", $date);
        return $d && $d->format("Y-m-d") === $date;
    }
}
