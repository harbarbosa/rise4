<?php

namespace GED\Models;

class Ged_settings_model extends GedBaseModel
{
    protected $table = 'ged_settings';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_all_settings()
    {
        $rows = $this->get_all_where(array(), 100000, 0, 'setting_name')->getResult();
        $settings = array();
        foreach ($rows as $row) {
            $settings[$row->setting_name] = $row->setting_value;
        }
        return $settings;
    }

    public function get_setting_with_default($name, $default = '')
    {
        $value = $this->get_value($name, $default);
        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    public function get_boolean($name, $default = false)
    {
        return (string) $this->get_value($name, $default ? '1' : '0') === '1';
    }

    public function get_integer($name, $default = 0)
    {
        return (int) $this->get_value($name, (string) $default);
    }

    public function get_value($name, $default = '')
    {
        $row = $this->db->table($this->table)
            ->where('setting_name', $name)
            ->get()
            ->getRow();

        if ($row && isset($row->setting_value)) {
            return $row->setting_value;
        }

        return $default;
    }

    public function set_setting($name, $value)
    {
        $existing = $this->db->table($this->table)->where('setting_name', $name)->get()->getRow();
        $payload = array(
            'setting_name' => $name,
            'setting_value' => $value,
            'updated_at' => get_my_local_time(),
        );

        if ($existing && isset($existing->id)) {
            return $this->ci_save($payload, (int) $existing->id);
        }

        $payload['created_at'] = get_my_local_time();
        return $this->ci_save($payload, 0);
    }

    public function save_settings(array $settings)
    {
        $success = true;
        foreach ($settings as $name => $value) {
            $success = $this->set_setting($name, $value) && $success;
        }

        return $success;
    }
}
