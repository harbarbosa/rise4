<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Products_model extends Crud_model
{
    protected $table = 'fv_products';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('category_id', 'distributor_id', 'product_type', 'sku', 'title', 'description', 'brand', 'model', 'unit', 'warranty', 'power_rating', 'efficiency', 'voltage', 'cost_price', 'sale_price', 'promotional_price', 'stock', 'tax_rate', 'technical_specs_json', 'external_provider', 'external_id', 'external_payload_json', 'last_price_sync_at', 'last_sync_at', 'last_import_at', 'active', 'sort', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_products');
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

        $product_type = trim((string) get_array_value($options, 'product_type'));
        if ($product_type !== '') {
            $where .= " AND $table.product_type=" . $this->db->escape($product_type);
        }

        $active_only = get_array_value($options, 'active_only');
        if ($active_only !== null && $active_only !== '') {
            $where .= " AND $table.active=" . (int) $active_only;
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND (" . implode(" OR ", array(
                "$table.title LIKE '%$search%' ESCAPE '!'",
                "$table.sku LIKE '%$search%' ESCAPE '!'",
                "$table.brand LIKE '%$search%' ESCAPE '!'",
                "$table.model LIKE '%$search%' ESCAPE '!'"
            )) . ")";
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
            log_message('error', '[Fotovoltaico] Products query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_products_by_category($category_id, $active_only = true)
    {
        $options = array(
            'category_id' => (int) $category_id,
        );

        if ($active_only) {
            $options['active_only'] = 1;
        }

        return $this->get_details($options);
    }

    public function get_product_types()
    {
        return array(
            'modulo' => 'Módulo',
            'inversor' => 'Inversor',
            'estrutura' => 'Estrutura',
            'servico' => 'Serviço',
        );
    }

    public function get_by_external_reference($external_provider = '', $external_id = '', $sku = '', $title = '', $brand = '')
    {
        $table = $this->db->prefixTable('fv_products');
        $external_provider = trim((string) $external_provider);
        $external_id = trim((string) $external_id);
        $sku = trim((string) $sku);
        $title = trim((string) $title);
        $brand = trim((string) $brand);

        if ($external_provider !== '' && $external_id !== '') {
            $sql = "SELECT * FROM $table WHERE deleted=0 AND external_provider=" . $this->db->escape($external_provider) . " AND external_id=" . $this->db->escape($external_id) . " LIMIT 1";
            $row = $this->db->query($sql)->getRow();
            if ($row) {
                return $row;
            }
        }

        if ($sku !== '') {
            $sql = "SELECT * FROM $table WHERE deleted=0 AND sku=" . $this->db->escape($sku) . " LIMIT 1";
            $row = $this->db->query($sql)->getRow();
            if ($row) {
                return $row;
            }
        }

        if ($title !== '') {
            $sql = "SELECT * FROM $table WHERE deleted=0 AND title=" . $this->db->escape($title);
            if ($brand !== '') {
                $sql .= " AND brand=" . $this->db->escape($brand);
            }
            $sql .= " LIMIT 1";
            return $this->db->query($sql)->getRow();
        }

        return null;
    }
}
