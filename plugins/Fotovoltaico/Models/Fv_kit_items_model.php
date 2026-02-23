<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

/**
 * Model para acessar itens de kits fotovoltaicos.
 */
class Fv_kit_items_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'fv_kit_items';
        parent::__construct($this->table);
    }

    /**
     * Lista itens de um kit (com produtos).
     */
    public function get_by_kit($kit_id)
    {
        $items_table = $this->db->prefixTable($this->table);
        $products_table = $this->db->prefixTable('fv_products');

        if (!$this->db->tableExists($items_table)) {
            return array();
        }

        $select_specs = '';
        if ($this->db->fieldExists('specs_json', $products_table)) {
            $select_specs = ", {$products_table}.specs_json";
        } elseif ($this->db->fieldExists('specs', $products_table)) {
            $select_specs = ", {$products_table}.specs as specs_json";
        }

        $has_items = $this->db->table($items_table)
            ->select('id')
            ->where('kit_id', (int)$kit_id)
            ->limit(1)
            ->get()
            ->getRow();
        if (!$has_items) {
            return array();
        }

        $select = "{$items_table}.*, {$products_table}.brand, {$products_table}.model, {$products_table}.`type`, {$products_table}.power_w, {$products_table}.cost as product_cost, {$products_table}.price as product_price, {$products_table}.is_active as product_active{$select_specs}";

        return $this->db->table($items_table)
            ->select($select, false)
            ->join($products_table, "{$products_table}.id = {$items_table}.product_id", 'left')
            ->where("{$items_table}.kit_id", (int)$kit_id)
            ->orderBy("{$items_table}.sort_order", 'ASC')
            ->orderBy("{$items_table}.id", 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Adiciona item.
     */
    public function add_item($data)
    {
        return $this->ci_save($data);
    }

    /**
     * Atualiza item.
     */
    public function update_item($id, $data)
    {
        return $this->ci_save($data, $id);
    }

    /**
     * Exclui item.
     */
    public function delete_item($id)
    {
        $table = $this->db->prefixTable($this->table);
        return $this->db->table($table)->where('id', (int)$id)->delete();
    }

    /**
     * Reordena itens.
     */
    public function reorder_items($kit_id, $ordered_ids)
    {
        $table = $this->db->prefixTable($this->table);
        $sort = 1;
        foreach ($ordered_ids as $id) {
            $this->db->table($table)
                ->where('kit_id', (int)$kit_id)
                ->where('id', (int)$id)
                ->update(['sort_order' => $sort]);
            $sort++;
        }
        return true;
    }

    /**
     * Totais do kit.
     */
    public function get_totals($kit_id)
    {
        $items = $this->get_by_kit($kit_id);
        $cost_total = 0;
        $price_total = 0;
        $module_count = 0;
        $power_kwp = 0;
        $inverters = [];
        $item_count = count($items);

        foreach ($items as $item) {
            $qty = (float)($item['qty'] ?? 1);
            $item_type = $item['item_type'] ?? 'product';
            if ($item_type === 'product' && !empty($item['product_id'])) {
                $unit_cost = (float)($item['product_cost'] ?? 0);
                $unit_price = (float)($item['product_price'] ?? 0);
                $cost_total += $unit_cost * $qty;
                $price_total += $unit_price * $qty;

                if (($item['type'] ?? '') === 'module') {
                    $module_count += $qty;
                    $power_w = (float)($item['power_w'] ?? 0);
                    $power_kwp += ($power_w * $qty) / 1000;
                }
                if (($item['type'] ?? '') === 'inverter') {
                    $label = trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''));
                    if ($label) {
                        $inverters[] = $label;
                    }
                }
            } else {
                $unit_cost = (float)($item['cost'] ?? 0);
                $unit_price = (float)($item['price'] ?? 0);
                $cost_total += $unit_cost * $qty;
                $price_total += $unit_price * $qty;
            }
        }

        $markup = $cost_total > 0 ? (($price_total - $cost_total) / $cost_total) * 100 : 0;

        return [
            'cost_total' => $cost_total,
            'price_total' => $price_total,
            'module_count' => $module_count,
            'power_kwp' => $power_kwp,
            'inverters' => array_values(array_unique($inverters)),
            'markup_percent' => $markup,
            'item_count' => $item_count
        ];
    }
}
