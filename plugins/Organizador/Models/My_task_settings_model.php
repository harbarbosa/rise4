<?php

namespace Organizador\Models;

use App\Models\Crud_model;

class My_task_settings_model extends Crud_model
{
    protected $table = 'my_task_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_setting($setting_name, $default = '')
    {
        $setting = $this->get_one_where(array('setting_name' => $setting_name, 'deleted' => 0));
        return $setting && $setting->id ? $setting->setting_value : $default;
    }

    public function save_setting($setting_name, $setting_value)
    {
        $existing = $this->get_one_where(array('setting_name' => $setting_name));
        $data = array(
            'setting_name' => $setting_name,
            'setting_value' => is_array($setting_value) ? serialize($setting_value) : $setting_value,
            'deleted' => 0,
        );

        return $this->ci_save($data, $existing && $existing->id ? $existing->id : 0);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('my_task_settings');
        $where = "WHERE $table.deleted=0";
        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $setting_name = get_array_value($options, 'setting_name');
        if ($setting_name) {
            $where .= " AND $table.setting_name=" . $this->db->escape($setting_name);
        }

        return $this->db->query("SELECT * FROM $table $where ORDER BY $table.setting_name ASC");
    }

    public function get_all_settings()
    {
        return $this->get_details(array())->getResult();
    }
}
