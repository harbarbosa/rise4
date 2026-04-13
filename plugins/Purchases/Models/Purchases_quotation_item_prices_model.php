<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_quotation_item_prices_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_quotation_item_prices';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_quotation_item_prices');
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

        $quotation_id = $this->_get_clean_value($options, "quotation_id");
        if ($quotation_id) {
            $where .= " AND $table.quotation_id=$quotation_id";
        }

        $quotation_item_id = $this->_get_clean_value($options, "quotation_item_id");
        if ($quotation_item_id) {
            $where .= " AND $table.quotation_item_id=$quotation_item_id";
        }

        $request_item_id = $this->_get_clean_value($options, "request_item_id");
        if ($request_item_id) {
            $where .= " AND $table.request_item_id=$request_item_id";
        }

        $supplier_id = $this->_get_clean_value($options, "supplier_id");
        if ($supplier_id) {
            $where .= " AND $table.supplier_id=$supplier_id";
        }

        $sql = "SELECT $table.*,
            $suppliers_table.name AS supplier_name
        FROM $table
        LEFT JOIN $suppliers_table ON $suppliers_table.id=$table.supplier_id
        WHERE $table.deleted=0 $where
        ORDER BY $table.id ASC";

        return $this->db->query($sql);
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
