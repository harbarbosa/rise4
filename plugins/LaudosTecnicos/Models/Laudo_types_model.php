<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_types_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudo_types';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.name LIKE '%$search%' OR $table.description LIKE '%$search%')";
        }

        $sql = "SELECT * FROM $table WHERE deleted=0 $where ORDER BY $table.name ASC";
        return $this->db->query($sql);
    }

    public function get_dropdown()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT id, name FROM $table WHERE deleted=0 ORDER BY name ASC";
        $result = $this->db->query($sql)->getResult();
        
        $dropdown = array();
        foreach ($result as $row) {
            $dropdown[$row->id] = $row->name;
        }
        return $dropdown;
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        return parent::ci_save($data, $id);
    }
}