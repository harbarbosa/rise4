<?php

namespace PontoRH\Models;

class PontoRh_settings_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_settings';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_setting($name, $default = '')
    {
        $name = trim((string) $name);
        if ($name === '') {
            return $default;
        }

        if (!$this->hasTable()) {
            return $default;
        }

        $row = $this->db->table($this->db->prefixTable($this->table))
            ->where('setting_name', $name)
            ->get()
            ->getRow();

        return $row ? (string) $row->setting_value : $default;
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

    public function get_defaults()
    {
        return array(
            'workday_start' => '08:00',
            'workday_end' => '18:00',
            'default_break_minutes' => '60',
            'allow_manual_adjustments' => '1',
            'mirror_default_range_days' => '31',
            'reports_default_range_days' => '31',
            'require_gps' => '1',
            'require_selfie' => '0',
            'allow_offline_marking' => '0',
            'allowed_radius_meters' => '200',
            'default_tolerance_minutes' => '10',
            'bank_hours_enabled' => '1',
            'google_maps_api_key' => '',
        );
    }

    public function get_all_settings_with_defaults()
    {
        return array_merge($this->get_defaults(), $this->get_all_settings());
    }

    public function save_setting($name, $value)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return false;
        }

        if (!$this->hasTable()) {
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
