<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_reports_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_orders';
        parent::__construct($this->table);
    }

    public function get_purchases_by_period($options = array())
    {
        $orders_table = $this->db->prefixTable('purchases_orders');
        $suppliers_table = $this->db->prefixTable('purchases_suppliers');
        $projects_table = $this->db->prefixTable('projects');

        $where = "";
        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $orders_table.company_id=$company_id";
        }

        $supplier_id = $this->_get_clean_value($options, "supplier_id");
        if ($supplier_id) {
            $where .= " AND $orders_table.supplier_id=$supplier_id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $orders_table.project_id=$project_id";
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND $orders_table.order_date>='$start_date'";
        }

        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND $orders_table.order_date<='$end_date'";
        }

        $sql = "SELECT
            COALESCE($projects_table.title, $orders_table.cost_center, '-') AS project_title,
            COALESCE($suppliers_table.name, '-') AS supplier_name,
            COUNT($orders_table.id) AS orders_count,
            SUM($orders_table.total) AS total
        FROM $orders_table
        LEFT JOIN $suppliers_table ON $suppliers_table.id=$orders_table.supplier_id
        LEFT JOIN $projects_table ON $projects_table.id=$orders_table.project_id
        WHERE $orders_table.deleted=0 $where
        GROUP BY project_title, supplier_name
        ORDER BY total DESC";

        return $this->db->query($sql);
    }

    public function get_open_overdue($options = array())
    {
        $orders_table = $this->db->prefixTable('purchases_orders');
        $suppliers_table = $this->db->prefixTable('purchases_suppliers');
        $projects_table = $this->db->prefixTable('projects');

        $where = "";
        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $orders_table.company_id=$company_id";
        }

        $supplier_id = $this->_get_clean_value($options, "supplier_id");
        if ($supplier_id) {
            $where .= " AND $orders_table.supplier_id=$supplier_id";
        }

        $open_statuses = $this->_get_clean_value($options, "open_statuses");
        if ($open_statuses && is_array($open_statuses)) {
            $status_list = implode("','", array_map('addslashes', $open_statuses));
            $where .= " AND $orders_table.status IN ('$status_list')";
        }

        $sql = "SELECT $orders_table.*,
            $suppliers_table.name AS supplier_name,
            $projects_table.title AS project_title
        FROM $orders_table
        LEFT JOIN $suppliers_table ON $suppliers_table.id=$orders_table.supplier_id
        LEFT JOIN $projects_table ON $projects_table.id=$orders_table.project_id
        WHERE $orders_table.deleted=0 $where
        ORDER BY $orders_table.expected_delivery_date ASC, $orders_table.id DESC";

        return $this->db->query($sql);
    }

    public function get_top_items($options = array())
    {
        $orders_table = $this->db->prefixTable('purchases_orders');
        $items_table = $this->db->prefixTable('purchases_order_items');

        $where = "";
        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $orders_table.company_id=$company_id";
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND $orders_table.order_date>='$start_date'";
        }

        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND $orders_table.order_date<='$end_date'";
        }

        $limit = (int)$this->_get_clean_value($options, "limit");
        if (!$limit) {
            $limit = 10;
        }

        $sql = "SELECT
            $items_table.description,
            $items_table.unit,
            SUM($items_table.quantity) AS total_qty,
            SUM($items_table.total) AS total_amount
        FROM $items_table
        LEFT JOIN $orders_table ON $orders_table.id=$items_table.order_id
        WHERE $items_table.deleted=0 AND $orders_table.deleted=0 $where
        GROUP BY $items_table.description, $items_table.unit
        ORDER BY total_qty DESC
        LIMIT $limit";

        return $this->db->query($sql);
    }
}
