<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Task_tools_model extends Crud_model
{
    protected $table = "pa_task_tools";
    public function __construct() { parent::__construct($this->table); }
    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        $tools = $this->db->prefixTable("pa_tools");
        $where = "";
        foreach (array("task_id", "project_id") as $field) {
            $value = $this->_get_clean_value($options, $field);
            if ($value !== null && $value !== "") { $where .= " AND $table.$field=" . (int) $value; }
        }
        return $this->db->query("SELECT $table.*, $tools.name AS tool_name FROM $table LEFT JOIN $tools ON $tools.id=$table.tool_id WHERE $table.deleted=0 $where ORDER BY $table.id ASC");
    }
    public function upsert_task_tools($task_id, $project_id, $items)
    {
        $task_id = (int) $task_id; $project_id = (int) $project_id;
        $tools_model = model("ProjectAnalizer\\Models\\Tools_model");
        $existing = $this->get_details(array("task_id" => $task_id))->getResult();
        $existing_ids = array_map(function ($row) { return (int) $row->id; }, $existing); $kept = array();
        $this->db->transStart();
        foreach ((array) $items as $item) {
            if (!is_array($item)) { continue; }
            $tool_id = (int) get_array_value($item, "tool_id");
            if (!$tool_id) { $tool_id = $tools_model->find_or_create(get_array_value($item, "tool_name")); }
            if (!$tool_id) { continue; }
            $qty = is_numeric(get_array_value($item, "quantity")) ? (float) get_array_value($item, "quantity") : 1;
            if ($qty <= 0) { continue; }
            $data = array("project_id" => $project_id, "task_id" => $task_id, "tool_id" => $tool_id, "quantity" => $qty, "requirement" => clean_data(get_array_value($item, "requirement")));
            $id = (int) get_array_value($item, "id");
            if ($id && in_array($id, $existing_ids, true)) { $this->ci_save($data, $id); $kept[] = $id; } else { $this->ci_save($data, 0); }
        }
        $remove = array_diff($existing_ids, $kept);
        if ($remove) { $this->db->table($this->db->prefixTable($this->table_without_prefix))->whereIn("id", $remove)->delete(); }
        $this->db->transComplete(); return $this->db->transStatus();
    }
}
