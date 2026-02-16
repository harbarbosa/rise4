<?php

namespace Proposals\Models;

use App\Models\Crud_model;

class Proposal_items_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'proposal_items_custom';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('proposal_items_custom');
        $items_table = $this->db->prefixTable('items');
        $services_table = $this->db->prefixTable('os_servicos');
        $has_services = $this->_table_exists($services_table);
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $proposal_id = $this->_get_clean_value($options, "proposal_id");
        if ($proposal_id) {
            $where .= " AND $table.proposal_id=$proposal_id";
        }

        $section_id = $this->_get_clean_value($options, "section_id");
        if ($section_id) {
            $where .= " AND $table.section_id=$section_id";
        }

        $in_memory = $this->_get_clean_value($options, "in_memory");
        if ($in_memory !== null && $in_memory !== "") {
            $in_memory = (int)$in_memory;
            $where .= " AND $table.in_memory=$in_memory";
        }

        $show_in_proposal = $this->_get_clean_value($options, "show_in_proposal");
        if ($show_in_proposal !== null && $show_in_proposal !== "") {
            $show_in_proposal = (int)$show_in_proposal;
            $where .= " AND $table.show_in_proposal=$show_in_proposal";
        }

        $select_title = $has_services
            ? "CASE WHEN $table.item_type='service' THEN $services_table.descricao ELSE $items_table.title END AS item_title"
            : "$items_table.title AS item_title";
        $select_unit = "CASE WHEN $table.item_type='service' THEN '' ELSE $items_table.unit_type END AS item_unit";

        $sql = "SELECT $table.*, $select_title, $select_unit
        FROM $table
        LEFT JOIN $items_table ON $items_table.id=$table.item_id";

        if ($has_services) {
            $sql .= " LEFT JOIN $services_table ON $services_table.id=$table.item_id";
        }

        $sql .= " WHERE $table.deleted=0 $where
        ORDER BY $table.sort ASC, $table.id ASC";

        return $this->db->query($sql);
    }

    private function _table_exists($table)
    {
        $query = $this->db->query("SHOW TABLES LIKE " . $this->db->escape($table));
        return $query && $query->getRow() ? true : false;
    }

    public function get_one($id = 0)
    {
        return parent::get_one($id);
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
