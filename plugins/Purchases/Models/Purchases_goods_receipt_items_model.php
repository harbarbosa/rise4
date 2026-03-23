<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_goods_receipt_items_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_goods_receipt_items';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_goods_receipt_items');
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $table.company_id=$company_id";
        }

        $receipt_id = $this->_get_clean_value($options, "receipt_id");
        if ($receipt_id) {
            $where .= " AND $table.receipt_id=$receipt_id";
        }

        $order_item_id = $this->_get_clean_value($options, "order_item_id");
        if ($order_item_id) {
            $where .= " AND $table.order_item_id=$order_item_id";
        }

        $item_id = $this->_get_clean_value($options, "item_id");
        if ($item_id) {
            $where .= " AND $table.item_id=$item_id";
        }

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.id ASC";
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
