<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Project_materials_model extends Crud_model
{
    protected $table = 'pa_project_materials';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function sync_from_proposal($project_id, $proposal_id)
    {
        $project_id = (int)$project_id;
        $proposal_id = (int)$proposal_id;
        $table = $this->db->prefixTable('pa_project_materials');
        $proposal_items = $this->db->prefixTable('proposal_items_custom');
        $items = $this->db->prefixTable('items');
        if (!$project_id || !$proposal_id || !$this->db->tableExists($table) || !$this->db->tableExists($proposal_items)) {
            return false;
        }

        $select = "$proposal_items.id AS proposal_item_id, $proposal_items.item_id, $proposal_items.description_override, $proposal_items.qty";
        $join = '';
        if ($this->db->tableExists($items)) {
            $select .= ", $items.title AS item_title, $items.unit_type AS item_unit";
            $join = " LEFT JOIN $items ON $items.id=$proposal_items.item_id";
        } else {
            $select .= ", NULL AS item_title, NULL AS item_unit";
        }
        $query = $this->db->query("SELECT $select FROM $proposal_items $join WHERE $proposal_items.deleted=0 AND $proposal_items.proposal_id=$proposal_id AND $proposal_items.in_memory=1 AND ($proposal_items.item_type='material' OR $proposal_items.item_type IS NULL) ORDER BY $proposal_items.id");
        if (!$query) {
            return false;
        }

        $grouped = array();
        foreach ($query->getResult() as $row) {
            $item_id = (int)($row->item_id ?? 0);
            $description = trim((string)($row->description_override ?: $row->item_title));
            if (!$description) {
                $description = 'Material #' . (int)$row->proposal_item_id;
            }
            $unit = trim((string)($row->item_unit ?? '')) ?: 'UN';
            $key = $item_id ? "item:$item_id:" . strtolower($unit) : 'text:' . hash('sha256', strtolower($description . '|' . $unit));
            if (!isset($grouped[$key])) {
                $grouped[$key] = array('project_id' => $project_id, 'proposal_id' => $proposal_id, 'proposal_item_id' => (int)$row->proposal_item_id, 'item_id' => $item_id ?: null, 'description' => $description, 'unit' => $unit, 'planned_quantity' => 0, 'additional_quantity' => 0, 'source' => 'proposal', 'source_key' => $key, 'created_at' => get_my_local_time(), 'updated_at' => get_my_local_time(), 'deleted' => 0);
            }
            $grouped[$key]['planned_quantity'] += (float)$row->qty;
        }

        foreach ($grouped as $key => $data) {
            $q = $this->db->table($table)->select('id,additional_quantity')->where('project_id', $project_id)->where('source_key', $key)->get();
            $existing = $q ? $q->getRow() : null;
            if ($existing) {
                unset($data['created_at']);
                $data['additional_quantity'] = (float)$existing->additional_quantity;
                $this->ci_save($data, (int)$existing->id);
            } else {
                $this->ci_save($data, 0);
            }
        }
        return true;
    }

    public function get_project_materials($project_id)
    {
        $project_id = (int)$project_id;
        $m = $this->db->prefixTable('pa_project_materials');
        $a = $this->db->prefixTable('pa_project_task_materials');
        $l = $this->db->prefixTable('pa_project_material_request_items');
        $r = $this->db->prefixTable('purchases_requests');
        if (!$this->db->tableExists($m)) return array();
        $requested = ($this->db->tableExists($l) && $this->db->tableExists($r)) ? "(SELECT COALESCE(SUM(x.quantity),0) FROM $l x INNER JOIN $r pr ON pr.id=x.purchase_request_id WHERE x.project_material_id=$m.id AND x.deleted=0 AND pr.deleted=0 AND pr.status NOT IN ('rejected','cancelled'))" : '0';
        $q = $this->db->query("SELECT $m.*, COALESCE(SUM($a.quantity),0) allocated_quantity, $requested requested_quantity FROM $m LEFT JOIN $a ON $a.project_material_id=$m.id AND $a.deleted=0 WHERE $m.project_id=$project_id AND $m.deleted=0 GROUP BY $m.id ORDER BY $m.description");
        return $q ? $q->getResult() : array();
    }

    public function get_allocations($project_id, $task_id = 0)
    {
        $project_id = (int)$project_id; $task_id = (int)$task_id;
        $a = $this->db->prefixTable('pa_project_task_materials');
        $m = $this->db->prefixTable('pa_project_materials');
        $t = $this->db->prefixTable('tasks');
        $l = $this->db->prefixTable('pa_project_material_request_items');
        $r = $this->db->prefixTable('purchases_requests');
        if (!$this->db->tableExists($a)) return array();
        $where_task = $task_id ? " AND $a.task_id=$task_id" : '';
        $requested = ($this->db->tableExists($l) && $this->db->tableExists($r)) ? "(SELECT COALESCE(SUM(x.quantity),0) FROM $l x INNER JOIN $r pr ON pr.id=x.purchase_request_id WHERE x.allocation_id=$a.id AND x.deleted=0 AND pr.deleted=0 AND pr.status NOT IN ('rejected','cancelled'))" : '0';
        $q = $this->db->query("SELECT $a.*, $m.description, $m.unit, $m.item_id, $t.title task_title, $requested requested_quantity FROM $a INNER JOIN $m ON $m.id=$a.project_material_id AND $m.deleted=0 INNER JOIN $t ON $t.id=$a.task_id AND $t.deleted=0 WHERE $a.project_id=$project_id AND $a.deleted=0 $where_task ORDER BY $t.title,$m.description");
        return $q ? $q->getResult() : array();
    }

    public function get_requested_quantity($allocation_id)
    {
        $allocation_id = (int)$allocation_id;
        $l = $this->db->prefixTable('pa_project_material_request_items');
        $r = $this->db->prefixTable('purchases_requests');
        if (!$allocation_id || !$this->db->tableExists($l) || !$this->db->tableExists($r)) return 0;
        $q = $this->db->query("SELECT COALESCE(SUM(x.quantity),0) total FROM $l x INNER JOIN $r pr ON pr.id=x.purchase_request_id WHERE x.allocation_id=$allocation_id AND x.deleted=0 AND pr.deleted=0 AND pr.status NOT IN ('rejected','cancelled')");
        $row = $q ? $q->getRow() : null;
        return $row ? (float)$row->total : 0;
    }
}
