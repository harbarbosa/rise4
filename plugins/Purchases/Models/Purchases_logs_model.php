<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_logs_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_logs';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_logs');
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $table.company_id=$company_id";
        }

        $context_type = $this->_get_clean_value($options, "context_type");
        if ($context_type) {
            $where .= " AND $table.context_type='$context_type'";
        }

        $context_id = $this->_get_clean_value($options, "context_id");
        if ($context_id) {
            $where .= " AND $table.context_id=$context_id";
        }

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 $where ORDER BY $table.id DESC";
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
