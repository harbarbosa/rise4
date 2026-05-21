<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Settings_model extends Crud_model
{
    protected $table = 'fv_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('setting_name', 'setting_value', 'setting_type', 'group_name', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_setting($setting_name)
    {
        $setting_name = $this->_get_clean_value($setting_name);
        $table = $this->db->prefixTable('fv_settings');
        $result = $this->db->query("SELECT setting_value FROM $table WHERE deleted=0 AND setting_name=" . $this->db->escape($setting_name) . " LIMIT 1");

        if ($result && $result->getRow()) {
            return $result->getRow()->setting_value;
        }
    }

    public function save_setting($setting_name, $setting_value, $type = 'app')
    {
        $table = $this->db->prefixTable('fv_settings');
        $setting_name = $this->_get_clean_value($setting_name);

        $exists = $this->db->table($table)
            ->where('setting_name', $setting_name)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        $data = array(
            'setting_name' => $setting_name,
            'setting_value' => $setting_value,
            'setting_type' => $type,
            'updated_at' => get_current_utc_time()
        );

        if ($exists) {
            return $this->db->table($table)->where('id', $exists->id)->update($data);
        }

        $data['created_at'] = get_current_utc_time();
        $data['deleted'] = 0;
        return $this->db->table($table)->insert($data);
    }

    public function get_all_required_settings($user_id = 0)
    {
        $user_id = (int) $user_id;
        $table = $this->db->prefixTable('fv_settings');

        $sql = "SELECT $table.setting_name, $table.setting_value
        FROM $table
        WHERE $table.deleted=0 AND ($table.setting_type = 'app' OR ($table.setting_type ='user' AND $table.setting_name LIKE 'user_" . $user_id . "_%'))";

        return $this->db->query($sql);
    }
}
