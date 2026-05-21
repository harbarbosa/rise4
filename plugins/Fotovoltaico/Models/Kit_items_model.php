<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Kit_items_model extends Crud_model
{
    protected $table = 'fv_kit_items';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('kit_id', 'product_id', 'quantity', 'unit_price', 'unit_cost', 'total_price', 'total_cost', 'notes', 'sort', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_kit_items');
        $products_table = $this->db->prefixTable('fv_products');
        $where = "WHERE $table.deleted=0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $kit_id = (int) get_array_value($options, 'kit_id');
        if ($kit_id) {
            $where .= " AND $table.kit_id=$kit_id";
        }

        $product_id = (int) get_array_value($options, 'product_id');
        if ($product_id) {
            $where .= " AND $table.product_id=$product_id";
        }

        $sql = "SELECT $table.*, $products_table.title AS product_title, $products_table.product_type, $products_table.sku, $products_table.cost_price, $products_table.sale_price
        FROM $table
        LEFT JOIN $products_table ON $products_table.id=$table.product_id AND $products_table.deleted=0
        $where
        ORDER BY $table.sort ASC, $table.id ASC";

        try {
            return $this->db->query($sql);
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Kit items query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_items_by_kit($kit_id)
    {
        return $this->get_details(array('kit_id' => (int) $kit_id));
    }

    public function get_item_totals($kit_id)
    {
        $items = $this->get_items_by_kit($kit_id)->getResult();
        $total_cost = 0;
        $total_price = 0;
        foreach ($items as $item) {
            $total_cost += (float) $item->total_cost;
            $total_price += (float) $item->total_price;
        }

        return array(
            'total_cost' => $total_cost,
            'total_price' => $total_price,
            'margin_value' => $total_price - $total_cost,
            'margin_percent' => $total_price > 0 ? (($total_price - $total_cost) / $total_price) * 100 : 0,
        );
    }

    public function replace_kit_items($kit_id, array $items)
    {
        $kit_id = (int) $kit_id;
        if (!$kit_id) {
            return false;
        }

        $existing = $this->get_items_by_kit($kit_id)->getResult();
        foreach ($existing as $item) {
            $this->delete($item->id);
        }

        foreach ($items as $item) {
            $payload = array(
                'kit_id' => $kit_id,
                'product_id' => (int) get_array_value($item, 'product_id'),
                'quantity' => (float) get_array_value($item, 'quantity'),
                'unit_price' => (float) get_array_value($item, 'unit_price'),
                'unit_cost' => (float) get_array_value($item, 'unit_cost'),
                'total_price' => (float) get_array_value($item, 'total_price'),
                'total_cost' => (float) get_array_value($item, 'total_cost'),
                'notes' => get_array_value($item, 'notes'),
                'sort' => (int) get_array_value($item, 'sort'),
                'created_by' => get_array_value($item, 'created_by'),
                'created_at' => get_array_value($item, 'created_at') ?: get_my_local_time(),
                'updated_at' => get_my_local_time(),
                'deleted' => 0,
            );
            $this->ci_save($payload);
        }

        return true;
    }
}
