<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_dashboard_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_requests';
        parent::__construct($this->table);
    }

    public function get_kpis($options = array())
    {
        $company_id = (int)get_array_value($options, "company_id");
        $approved_since = get_array_value($options, "approved_since");
        $receipts_since = get_array_value($options, "receipts_since");

        $requests_table = $this->db->prefixTable('purchases_requests');
        $orders_table = $this->db->prefixTable('purchases_orders');
        $receipts_table = $this->db->prefixTable('purchases_goods_receipts');

        $approved_since_sql = $approved_since ? $this->db->escape($approved_since) : $this->db->escape(date('Y-m-d', strtotime('-30 days')));
        $receipts_since_sql = $receipts_since ? $this->db->escape($receipts_since) : $this->db->escape(date('Y-m-d', strtotime('-30 days')));

        $sql = "SELECT
            (SELECT COUNT(*) FROM $requests_table WHERE $requests_table.deleted=0 AND $requests_table.company_id=$company_id AND $requests_table.status='awaiting_approval') AS pending_requests,
            (SELECT COUNT(*) FROM $requests_table WHERE $requests_table.deleted=0 AND $requests_table.company_id=$company_id AND $requests_table.status='approved_for_po' AND $requests_table.updated_at >= $approved_since_sql) AS approved_last_30,
            (SELECT COUNT(*) FROM $orders_table WHERE $orders_table.deleted=0 AND $orders_table.company_id=$company_id AND $orders_table.status IN ('open','sent','partial_received')) AS open_orders,
            (SELECT IFNULL(SUM($orders_table.total),0) FROM $orders_table WHERE $orders_table.deleted=0 AND $orders_table.company_id=$company_id AND $orders_table.status IN ('open','sent','partial_received')) AS open_orders_total,
            (SELECT COUNT(*) FROM $receipts_table WHERE $receipts_table.deleted=0 AND $receipts_table.company_id=$company_id AND COALESCE($receipts_table.receipt_date,$receipts_table.created_at) >= $receipts_since_sql) AS receipts_last_30";

        $row = $this->db->query($sql)->getRow();
        return $row ? $row : (object) array(
            "pending_requests" => 0,
            "approved_last_30" => 0,
            "open_orders" => 0,
            "open_orders_total" => 0,
            "receipts_last_30" => 0
        );
    }

    public function get_pending_requests($options = array(), $limit = 10)
    {
        $company_id = (int)get_array_value($options, "company_id");
        $requests_table = $this->db->prefixTable('purchases_requests');
        $projects_table = $this->db->prefixTable('projects');
        $users_table = $this->db->prefixTable('users');
        $os_table = $this->db->prefixTable('os_ordens');

        $has_os_table = false;
        try {
            $like = $this->db->query("SHOW TABLES LIKE '" . $os_table . "'");
            $has_os_table = ($like && method_exists($like, 'getResult') && count($like->getResult()) > 0);
        } catch (\Throwable $e) {
            $has_os_table = false;
        }

        $select = "$requests_table.*, $projects_table.title AS project_title,
            CONCAT(requested_by_user.first_name, ' ', requested_by_user.last_name) AS requested_by_name";
        if ($has_os_table) {
            $select .= ", $os_table.titulo AS os_title";
        }

        $sql = "SELECT $select
            FROM $requests_table
            LEFT JOIN $projects_table ON $projects_table.id=$requests_table.project_id
            LEFT JOIN $users_table AS requested_by_user ON requested_by_user.id=$requests_table.requested_by";
        if ($has_os_table) {
            $sql .= " LEFT JOIN $os_table ON $os_table.id=$requests_table.os_id";
        }

        $sql .= " WHERE $requests_table.deleted=0 AND $requests_table.company_id=$company_id AND $requests_table.status='awaiting_approval'
            ORDER BY $requests_table.id DESC
            LIMIT " . (int)$limit;

        return $this->db->query($sql)->getResult();
    }

    public function get_open_purchase_orders($options = array(), $limit = 10)
    {
        $company_id = (int)get_array_value($options, "company_id");
        $orders_table = $this->db->prefixTable('purchases_orders');
        $suppliers_table = $this->db->prefixTable('purchases_suppliers');
        $projects_table = $this->db->prefixTable('projects');

        $sql = "SELECT $orders_table.*,
            $suppliers_table.name AS supplier_name,
            $projects_table.title AS project_title
            FROM $orders_table
            LEFT JOIN $suppliers_table ON $suppliers_table.id=$orders_table.supplier_id
            LEFT JOIN $projects_table ON $projects_table.id=$orders_table.project_id
            WHERE $orders_table.deleted=0 AND $orders_table.company_id=$company_id AND $orders_table.status IN ('open','sent','partial_received')
            ORDER BY $orders_table.id DESC
            LIMIT " . (int)$limit;

        return $this->db->query($sql)->getResult();
    }
}
