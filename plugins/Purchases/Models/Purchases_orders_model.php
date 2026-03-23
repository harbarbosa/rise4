<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_orders_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_orders';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_orders');
        $suppliers_table = $this->db->prefixTable('purchases_suppliers');
        $projects_table = $this->db->prefixTable('projects');
        $requests_table = $this->db->prefixTable('purchases_requests');
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $table.company_id=$company_id";
        }

        $request_id = $this->_get_clean_value($options, "request_id");
        if ($request_id) {
            $where .= " AND $table.request_id=$request_id";
        }

        $supplier_id = $this->_get_clean_value($options, "supplier_id");
        if ($supplier_id) {
            $where .= " AND $table.supplier_id=$supplier_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $sql = "SELECT $table.*,
            $suppliers_table.name AS supplier_name,
            $projects_table.title AS project_title,
            $requests_table.request_code AS request_code
        FROM $table
        LEFT JOIN $suppliers_table ON $suppliers_table.id=$table.supplier_id
        LEFT JOIN $projects_table ON $projects_table.id=$table.project_id
        LEFT JOIN $requests_table ON $requests_table.id=$table.request_id
        WHERE $table.deleted=0 $where
        ORDER BY $table.id DESC";
        return $this->db->query($sql);
    }

    public function get_next_po_code_data($company_id = 0)
    {
        $table = $this->db->prefixTable('purchases_orders');
        $company_id = (int)$company_id;
        $sql = "SELECT MAX($table.po_code_number) AS max_number FROM $table WHERE $table.deleted=0 AND $table.company_id=$company_id";
        $row = $this->db->query($sql)->getRow();
        $next_number = $row && $row->max_number ? ((int)$row->max_number + 1) : 1;
        $po_code = 'PO-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);

        return array(
            'po_code_number' => $next_number,
            'po_code' => $po_code
        );
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }

        return $this->ci_save($row, $id) ? true : false;
    }

    public function delete($id = 0, $undo = false)
    {
        return parent::delete($id, $undo);
    }
}
