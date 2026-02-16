<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_quotations_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_quotations';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_quotations');
        $requests_table = $this->db->prefixTable('purchases_requests');
        $suppliers_table = $this->db->prefixTable('purchases_suppliers');
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

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $sql = "SELECT $table.*,
            $requests_table.request_code AS request_code,
            $suppliers_table.name AS winner_supplier_name
        FROM $table
        LEFT JOIN $requests_table ON $requests_table.id=$table.request_id
        LEFT JOIN $suppliers_table ON $suppliers_table.id=$table.winner_supplier_id
        WHERE $table.deleted=0 $where
        ORDER BY $table.id DESC";

        return $this->db->query($sql);
    }

    public function get_one_by_request($request_id, $company_id)
    {
        $table = $this->db->prefixTable('purchases_quotations');
        $request_id = (int)$request_id;
        $company_id = (int)$company_id;
        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 AND $table.request_id=$request_id AND $table.company_id=$company_id LIMIT 1";
        $query = $this->db->query($sql);
        if (!$query || !method_exists($query, 'getRow')) {
            return null;
        }

        return $query->getRow();
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
