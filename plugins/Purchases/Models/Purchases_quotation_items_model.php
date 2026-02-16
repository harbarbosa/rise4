<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_quotation_items_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_quotation_items';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_quotation_items');
        $request_items_table = $this->db->prefixTable('purchases_request_items');
        $items_table = $this->db->prefixTable('items');
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

        $sql = "SELECT $table.*,
            $request_items_table.description AS request_description,
            $request_items_table.unit AS request_unit,
            $request_items_table.item_id AS item_id,
            $request_items_table.desired_date AS request_desired_date,
            $items_table.title AS item_title
        FROM $table
        LEFT JOIN $request_items_table ON $request_items_table.id=$table.request_item_id
        LEFT JOIN $items_table ON $items_table.id=$request_items_table.item_id
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
