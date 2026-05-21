<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Kits_model extends Crud_model
{
    protected $table = 'fv_kits';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('category_id', 'distributor_id', 'title', 'code', 'description', 'power_kwp', 'notes', 'status', 'total_cost', 'total_price', 'promotional_price', 'stock', 'margin_value', 'margin_percent', 'external_provider', 'external_id', 'external_payload_json', 'last_price_sync_at', 'last_sync_at', 'last_import_at', 'active', 'sort', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_kits');
        $categories_table = $this->db->prefixTable('fv_product_categories');
        $distributors_table = $this->db->prefixTable('fv_distributors');
        $where = "WHERE $table.deleted=0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $category_id = (int) get_array_value($options, 'category_id');
        if ($category_id) {
            $where .= " AND $table.category_id=$category_id";
        }

        $distributor_id = (int) get_array_value($options, 'distributor_id');
        if ($distributor_id) {
            $where .= " AND $table.distributor_id=$distributor_id";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status=" . $this->db->escape($status);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.title LIKE '%$search%' ESCAPE '!' OR $table.code LIKE '%$search%' ESCAPE '!')";
        }

        $sql = "SELECT $table.*,
            $categories_table.title AS category_title,
            $distributors_table.title AS distributor_title
        FROM $table
        LEFT JOIN $categories_table ON $categories_table.id=$table.category_id AND $categories_table.deleted=0
        LEFT JOIN $distributors_table ON $distributors_table.id=$table.distributor_id AND $distributors_table.deleted=0
        $where
        ORDER BY $table.sort ASC, $table.title ASC";

        try {
            return $this->db->query($sql);
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Kits query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_kit_with_items($kit_id)
    {
        $kit_id = (int) $kit_id;
        if (!$kit_id) {
            return null;
        }

        $kit = $this->get_details(array('id' => $kit_id))->getRow();
        if (!$kit) {
            return null;
        }

        $kit->items = model('Fotovoltaico\\Models\\Kit_items_model')->get_items_by_kit($kit_id)->getResult();
        return $kit;
    }

    public function recalculate_totals($kit_id)
    {
        $kit_id = (int) $kit_id;
        if (!$kit_id) {
            return false;
        }

        $Items = model('Fotovoltaico\\Models\\Kit_items_model');
        $items = $Items->get_items_by_kit($kit_id)->getResult();
        $total_cost = 0;
        $total_price = 0;

        foreach ($items as $item) {
            $total_cost += (float) $item->total_cost;
            $total_price += (float) $item->total_price;
        }

        $margin_value = $total_price - $total_cost;
        $margin_percent = $total_price > 0 ? (($margin_value / $total_price) * 100) : 0;

        $update_data = array(
            'total_cost' => $total_cost,
            'total_price' => $total_price,
            'margin_value' => $margin_value,
            'margin_percent' => $margin_percent,
            'updated_at' => get_my_local_time(),
        );

        return $this->ci_save($update_data, $kit_id);
    }

    public function get_by_external_reference($external_provider = '', $external_id = '', $code = '', $title = '')
    {
        $table = $this->db->prefixTable('fv_kits');
        $external_provider = trim((string) $external_provider);
        $external_id = trim((string) $external_id);
        $code = trim((string) $code);
        $title = trim((string) $title);

        if ($external_provider !== '' && $external_id !== '') {
            $sql = "SELECT * FROM $table WHERE deleted=0 AND external_provider=" . $this->db->escape($external_provider) . " AND external_id=" . $this->db->escape($external_id) . " LIMIT 1";
            $row = $this->db->query($sql)->getRow();
            if ($row) {
                return $row;
            }
        }

        if ($code !== '') {
            $sql = "SELECT * FROM $table WHERE deleted=0 AND code=" . $this->db->escape($code) . " LIMIT 1";
            $row = $this->db->query($sql)->getRow();
            if ($row) {
                return $row;
            }
        }

        if ($title !== '') {
            $sql = "SELECT * FROM $table WHERE deleted=0 AND title=" . $this->db->escape($title) . " LIMIT 1";
            return $this->db->query($sql)->getRow();
        }

        return null;
    }
}
