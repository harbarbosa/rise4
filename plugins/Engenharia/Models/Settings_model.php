<?php

namespace Engenharia\Models;

class Settings_model extends EngenhariaBaseModel
{
    protected $table = 'eng_settings';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_all_settings(): array
    {
        $settings = array();
        foreach ($this->db->table($this->db->prefixTable($this->table))->get()->getResult() as $row) {
            $settings[$row->setting_name] = $row->setting_value;
        }

        return $settings;
    }

    public function save_value(string $name, string $value): bool
    {
        $table = $this->db->prefixTable($this->table);
        $existing = $this->db->table($table)->where('setting_name', $name)->get(1)->getRow();
        $data = array('setting_value' => $value, 'updated_at' => $this->now());
        if ($existing) {
            return $this->db->table($table)->where('id', $existing->id)->update($data);
        }

        $data['setting_name'] = $name;
        $data['created_at'] = $this->now();
        return $this->db->table($table)->insert($data);
    }
}
