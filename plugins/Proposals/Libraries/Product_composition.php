<?php

namespace Proposals\Libraries;

class Product_composition
{
    private $db;
    private $components_table;
    private $proposal_components_table;
    private $items_table;
    private $proposal_items_table;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->components_table = $this->db->prefixTable('item_components_custom');
        $this->proposal_components_table = $this->db->prefixTable('proposal_item_components_custom');
        $this->items_table = $this->db->prefixTable('items');
        $this->proposal_items_table = $this->db->prefixTable('proposal_items_custom');
    }

    public function is_available()
    {
        return $this->db->tableExists($this->components_table)
            && $this->db->tableExists($this->proposal_components_table);
    }

    public function is_kit($item_id)
    {
        if (!$this->db->tableExists($this->components_table)) {
            return false;
        }

        return $this->db->table($this->components_table)
            ->where('parent_item_id', (int)$item_id)
            ->where('deleted', 0)
            ->countAllResults() > 0;
    }

    public function get_components($item_id)
    {
        if (!$this->db->tableExists($this->components_table)) {
            return array();
        }

        return $this->db->table($this->components_table . ' c')
            ->select('c.*, i.title AS component_title, i.unit_type AS component_unit')
            ->join($this->items_table . ' i', 'i.id=c.component_item_id', 'left')
            ->where('c.parent_item_id', (int)$item_id)
            ->where('c.deleted', 0)
            ->where('i.deleted', 0)
            ->orderBy('c.sort', 'ASC')
            ->orderBy('c.id', 'ASC')
            ->get()
            ->getResult();
    }

    public function save_components($parent_item_id, array $rows)
    {
        $parent_item_id = (int)$parent_item_id;
        if (!$parent_item_id || !$this->db->tableExists($this->components_table)) {
            return array('success' => false, 'message' => 'Estrutura de composição ainda não instalada.');
        }

        $normalized = array();
        $seen = array();
        foreach ($rows as $index => $row) {
            $component_id = (int)($row['component_item_id'] ?? 0);
            $qty = (float)($row['qty_per_unit'] ?? 0);
            $loss = max(0, (float)($row['loss_percent'] ?? 0));
            if (!$component_id || $qty <= 0) {
                continue;
            }
            if ($component_id === $parent_item_id) {
                return array('success' => false, 'message' => 'Um produto não pode compor ele mesmo.');
            }
            if (isset($seen[$component_id])) {
                return array('success' => false, 'message' => 'O mesmo componente foi informado mais de uma vez.');
            }
            if ($this->would_create_cycle($parent_item_id, $component_id)) {
                return array('success' => false, 'message' => 'A composição criaria um ciclo entre produtos/kit.');
            }
            $seen[$component_id] = true;
            $normalized[] = array(
                'parent_item_id' => $parent_item_id,
                'component_item_id' => $component_id,
                'qty_per_unit' => $qty,
                'reference_unit' => trim((string)($row['reference_unit'] ?? '')),
                'loss_percent' => $loss,
                'round_up' => !empty($row['round_up']) ? 1 : 0,
                'sort' => $index + 1,
                'created_at' => get_my_local_time(),
                'updated_at' => get_my_local_time(),
                'deleted' => 0
            );
        }

        $this->db->transStart();
        $this->db->table($this->components_table)->where('parent_item_id', $parent_item_id)->delete();
        if ($normalized) {
            $this->db->table($this->components_table)->insertBatch($normalized);
        }
        $this->db->transComplete();

        return array('success' => $this->db->transStatus(), 'message' => $this->db->transStatus() ? '' : 'Não foi possível salvar a composição.');
    }

    public function clear_components($parent_item_id)
    {
        if ($this->db->tableExists($this->components_table)) {
            $this->db->table($this->components_table)->where('parent_item_id', (int)$parent_item_id)->delete();
        }
    }

    public function sync_proposal_item($proposal_item_id)
    {
        $proposal_item_id = (int)$proposal_item_id;
        if (!$proposal_item_id || !$this->is_available()) {
            return;
        }

        $proposal_item = $this->db->table($this->proposal_items_table)
            ->where('id', $proposal_item_id)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        $this->db->table($this->proposal_components_table)->where('proposal_item_id', $proposal_item_id)->delete();
        if (!$proposal_item || $proposal_item->item_type === 'service' || !$proposal_item->item_id) {
            return;
        }

        $source_item_id = (int)$proposal_item->item_id;
        if (!$this->is_kit($source_item_id)) {
            return;
        }

        $expanded = array();
        $this->expand_item($source_item_id, (float)$proposal_item->qty, $expanded, array());
        if (!$expanded) {
            return;
        }

        $rows = array();
        foreach ($expanded as $component_id => $quantity) {
            $item = $this->db->table($this->items_table)
                ->select('id, title, unit_type, rate, cost')
                ->where('id', (int)$component_id)
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$item) {
                continue;
            }
            $unit_cost = isset($item->cost) && is_numeric($item->cost) ? (float)$item->cost : (float)$item->rate;
            $rows[] = array(
                'proposal_id' => (int)$proposal_item->proposal_id,
                'proposal_item_id' => $proposal_item_id,
                'source_item_id' => $source_item_id,
                'component_item_id' => (int)$component_id,
                'component_title' => $item->title,
                'unit_type' => $item->unit_type,
                'calculated_qty' => round((float)$quantity, 6),
                'unit_cost' => $unit_cost,
                'total_cost' => round($unit_cost * (float)$quantity, 4),
                'created_at' => get_my_local_time(),
                'updated_at' => get_my_local_time(),
                'deleted' => 0
            );
        }
        if ($rows) {
            $this->db->table($this->proposal_components_table)->insertBatch($rows);
        }
    }

    public function clear_proposal_item($proposal_item_id)
    {
        if ($this->db->tableExists($this->proposal_components_table)) {
            $this->db->table($this->proposal_components_table)->where('proposal_item_id', (int)$proposal_item_id)->delete();
        }
    }

    private function expand_item($item_id, $quantity, array &$result, array $stack)
    {
        $item_id = (int)$item_id;
        if (isset($stack[$item_id])) {
            return;
        }
        $stack[$item_id] = true;
        $components = $this->get_components($item_id);
        if (!$components) {
            $result[$item_id] = ($result[$item_id] ?? 0) + $quantity;
            return;
        }

        foreach ($components as $component) {
            $component_qty = $quantity * (float)$component->qty_per_unit;
            if ((float)$component->loss_percent > 0) {
                $component_qty *= (1 + ((float)$component->loss_percent / 100));
            }
            if ((int)$component->round_up === 1) {
                $component_qty = ceil($component_qty);
            }
            $this->expand_item((int)$component->component_item_id, $component_qty, $result, $stack);
        }
    }

    private function would_create_cycle($parent_item_id, $component_item_id)
    {
        if ($parent_item_id === $component_item_id) {
            return true;
        }
        return $this->has_path_to((int)$component_item_id, (int)$parent_item_id, array());
    }

    private function has_path_to($from_item_id, $target_item_id, array $visited)
    {
        if ($from_item_id === $target_item_id) {
            return true;
        }
        if (isset($visited[$from_item_id])) {
            return false;
        }
        $visited[$from_item_id] = true;
        $rows = $this->db->table($this->components_table)
            ->select('component_item_id')
            ->where('parent_item_id', $from_item_id)
            ->where('deleted', 0)
            ->get()
            ->getResult();
        foreach ($rows as $row) {
            if ($this->has_path_to((int)$row->component_item_id, $target_item_id, $visited)) {
                return true;
            }
        }
        return false;
    }
}
