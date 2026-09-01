<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Task_materials_model extends Crud_model
{
    protected $table = "pa_task_materials";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table_without_prefix);
        $items = $this->db->prefixTable("proposal_items_custom");
        $catalog = $this->db->prefixTable("items");
        $where = "";
        foreach (array("task_id", "project_id") as $field) {
            $value = $this->_get_clean_value($options, $field);
            if ($value !== null && $value !== "") {
                $where .= " AND $table.$field=" . (int) $value;
            }
        }

        return $this->db->query("SELECT $table.*, $items.item_type, $items.item_id,
            COALESCE(NULLIF($items.description_override, ''), $catalog.title) AS material_description,
            $items.qty AS proposal_qty, $catalog.unit_type AS item_unit, $items.section_id
            FROM $table LEFT JOIN $items ON $items.id=$table.proposal_item_id
            LEFT JOIN $catalog ON $catalog.id=$items.item_id
            WHERE $table.deleted=0 $where ORDER BY $table.id ASC");
    }

    public function upsert_task_materials($task_id, $project_id, $items)
    {
        return $this->_upsert_rows($task_id, $project_id, $items);
    }

    private function _upsert_rows($task_id, $project_id, $items)
    {
        $task_id = (int) $task_id;
        $project_id = (int) $project_id;
        $existing = $this->get_details(array("task_id" => $task_id))->getResult();
        $existing_ids = array_map(function ($row) { return (int) $row->id; }, $existing);
        $kept = array();
        $this->db->transStart();
        foreach ((array) $items as $item) {
            if (!is_array($item) || !(int) get_array_value($item, "proposal_item_id")) {
                continue;
            }
            $data = array(
                "project_id" => $project_id,
                "task_id" => $task_id,
                "proposal_item_id" => (int) get_array_value($item, "proposal_item_id"),
                "quantity" => is_numeric(get_array_value($item, "quantity")) ? (float) get_array_value($item, "quantity") : 0,
                "notes" => clean_data(get_array_value($item, "notes"))
            );
            if ($data["quantity"] <= 0) { continue; }
            $id = (int) get_array_value($item, "id");
            if ($id && in_array($id, $existing_ids, true)) {
                $this->ci_save($data, $id);
                $kept[] = $id;
            } else {
                $this->ci_save($data, 0);
            }
        }
        $remove = array_diff($existing_ids, $kept);
        if ($remove) { $this->db->table($this->db->prefixTable($this->table_without_prefix))->whereIn("id", $remove)->delete(); }
        $this->db->transComplete();
        return $this->db->transStatus();
    }
}
