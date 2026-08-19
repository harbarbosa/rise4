<?php

namespace LaudosTecnicos\Models;

class LaudoSettings_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_settings';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_all_settings()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $rows = $this->db->table($this->db->prefixTable($this->table))->get()->getResult();
        $settings = array();

        foreach ($rows as $row) {
            $settings[$row->setting_name] = $row->setting_value;
        }

        return $settings;
    }

    public function get_all_settings_with_defaults()
    {
        return array_merge(laudostecnicos_default_settings(), $this->get_all_settings());
    }

    public function get_setting($name, $default = '')
    {
        $name = trim((string) $name);
        if ($name === '' || !$this->hasTable()) {
            return $default;
        }

        $row = $this->db->table($this->db->prefixTable($this->table))
            ->where('setting_name', $name)
            ->get()
            ->getRow();

        return $row ? (string) $row->setting_value : $default;
    }

    public function save_setting($name, $value)
    {
        $name = trim((string) $name);
        if ($name === '' || !$this->hasTable()) {
            return false;
        }

        $table = $this->db->prefixTable($this->table);
        $row = $this->db->table($table)->where('setting_name', $name)->get()->getRow();
        $data = array(
            'setting_name' => $name,
            'setting_value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => get_current_utc_time(),
        );

        if ($row) {
            return $this->db->table($table)->where('setting_name', $name)->update($data);
        }

        $data['created_at'] = get_current_utc_time();
        return $this->db->table($table)->insert($data);
    }
}
