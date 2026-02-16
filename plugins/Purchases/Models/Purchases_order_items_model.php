<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_order_items_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_order_items';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_order_items');
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $table.company_id=$company_id";
        }

        $order_id = $this->_get_clean_value($options, "order_id");
        if ($order_id) {
            $where .= " AND $table.order_id=$order_id";
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
