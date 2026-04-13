<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_quotations_model extends Crud_model
{
    protected $table = null;
    private $field_names = null;

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
        $quotation_items_table = $this->db->prefixTable('purchases_quotation_items');
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

        $quotation_type = $this->_get_clean_value($options, "quotation_type");
        if ($quotation_type && $this->has_column('quotation_type')) {
            $where .= " AND $table.quotation_type=" . $this->db->escape($quotation_type);
        }

        $has_items_only = $this->_get_clean_value($options, "has_items_only");
        if ($has_items_only) {
            $where .= " AND EXISTS (SELECT 1 FROM $quotation_items_table WHERE $quotation_items_table.quotation_id=$table.id AND $quotation_items_table.deleted=0)";
        }

        $sql = "SELECT $table.*,
            $requests_table.request_code AS request_code,
            $suppliers_table.name AS winner_supplier_name,
            (
                SELECT COUNT(*)
                FROM $quotation_items_table
                WHERE $quotation_items_table.quotation_id=$table.id AND $quotation_items_table.deleted=0
            ) AS items_count
        FROM $table
        LEFT JOIN $requests_table ON $requests_table.id=$table.request_id
        LEFT JOIN $suppliers_table ON $suppliers_table.id=$table.winner_supplier_id
        WHERE $table.deleted=0 $where
        ORDER BY $table.id DESC";

        return $this->db->query($sql);
    }

    public function get_next_quotation_code_data($company_id = 0)
    {
        $table = $this->db->prefixTable('purchases_quotations');
        $company_id = (int)$company_id;
        if ($this->has_column('quotation_code_number')) {
            $sql = "SELECT MAX($table.quotation_code_number) AS max_number FROM $table WHERE $table.deleted=0 AND $table.company_id=$company_id";
            $row = $this->db->query($sql)->getRow();
            $next_number = $row && $row->max_number ? ((int)$row->max_number + 1) : 1;
        } else {
            $next_number = 1;
        }
        $quotation_code = 'CQ-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);

        return array(
            'quotation_code_number' => $next_number,
            'quotation_code' => $quotation_code
        );
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

    public function has_column($column_name)
    {
        if ($this->field_names === null) {
            $this->field_names = $this->db->getFieldNames($this->db->prefixTable($this->table));
            if (!is_array($this->field_names)) {
                $this->field_names = array();
            }
        }

        return in_array($column_name, $this->field_names, true);
    }
}
