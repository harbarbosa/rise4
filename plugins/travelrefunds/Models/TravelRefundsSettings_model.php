<?php

namespace travelrefunds\Models;

use App\Models\Crud_model;

class TravelRefundsSettings_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'travelrefunds_settings';
        parent::__construct($this->table);
    }

    public function get_setting($name, $default = '')
    {
        $row = $this->get_one_where(array('setting_name' => $name));
        if ($row && isset($row->id) && $row->id && isset($row->setting_value)) {
            return $row->setting_value;
        }

        return $default;
    }

    public function save_setting($name, $value)
    {
        $row = $this->get_one_where(array('setting_name' => $name));
        $payload = array(
            'setting_name' => $name,
            'setting_value' => $value,
        );

        if ($row && $row->id) {
            return $this->update_where($payload, array('id' => $row->id));
        }

        return $this->ci_save($payload);
    }

    public function get_all_settings()
    {
        return $this->get_all()->getResult();
    }
}
