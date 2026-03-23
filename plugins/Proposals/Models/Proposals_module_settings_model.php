<?php

namespace Proposals\Models;

use App\Models\Crud_model;

class Proposals_module_settings_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'proposals_module_settings_custom';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('proposals_module_settings_custom');
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $table.company_id=$company_id";
        }

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 $where";
        return $this->db->query($sql);
    }

    public function get_settings($company_id)
    {
        $company_id = (int)$company_id;
        try {
            $query = $this->get_details(array("company_id" => $company_id));
            $row = ($query && method_exists($query, 'getRow')) ? $query->getRow() : null;
            if ($row) {
                return $row;
            }
        } catch (\Throwable $e) {
            return (object)array(
                "default_commission_type" => "percent",
                "default_commission_value" => 0,
                "default_markup_percent" => 0,
                "taxes_json" => "[]",
                "taxes_base" => "total_sale"
            );
        }

        return (object)array(
            "default_commission_type" => "percent",
            "default_commission_value" => 0,
            "default_markup_percent" => 0,
            "taxes_json" => "[]",
            "taxes_base" => "total_sale"
        );
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
