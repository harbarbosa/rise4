<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_settings_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_settings';
        parent::__construct($this->table);
    }

    public function get_setting($key, $company_id = 0)
    {
        $table = $this->db->prefixTable('purchases_settings');
        $key = $this->_get_clean_value($key);
        $company_id = (int)$company_id;

        $sql = "SELECT $table.setting_value FROM $table WHERE $table.deleted=0 AND $table.setting_key='$key' AND $table.company_id IN (0, $company_id) ORDER BY $table.company_id DESC LIMIT 1";
        $row = $this->db->query($sql)->getRow();
        return $row ? $row->setting_value : "";
    }

    public function save_setting($key, $value, $company_id = 0)
    {
        $table = $this->db->prefixTable('purchases_settings');
        $key = $this->_get_clean_value($key);
        $company_id = (int)$company_id;

        $row = $this->db->query("SELECT id FROM $table WHERE $table.deleted=0 AND $table.setting_key='$key' AND $table.company_id=$company_id")->getRow();
        $data = array(
            "company_id" => $company_id,
            "setting_key" => $key,
            "setting_value" => $value
        );

        if ($row && $row->id) {
            return $this->ci_save($data, (int)$row->id);
        }

        return $this->ci_save($data, 0);
    }
}
