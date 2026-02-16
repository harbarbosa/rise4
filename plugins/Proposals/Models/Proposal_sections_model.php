<?php

namespace Proposals\Models;

use App\Models\Crud_model;

class Proposal_sections_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'proposal_sections_custom';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('proposal_sections_custom');
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $proposal_id = $this->_get_clean_value($options, "proposal_id");
        if ($proposal_id) {
            $where .= " AND $table.proposal_id=$proposal_id";
        }

        $parent_id = $this->_get_clean_value($options, "parent_id");
        if ($parent_id) {
            $where .= " AND $table.parent_id=$parent_id";
        }

        $sql = "SELECT $table.*
        FROM $table
        WHERE $table.deleted=0 $where
        ORDER BY $table.sort ASC, $table.id ASC";

        return $this->db->query($sql);
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
