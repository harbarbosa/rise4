<?php

namespace PontoRH\Models;

class PontoRh_locations_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_locations';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $locations_table = $this->db->prefixTable($this->table);
        $sql = "SELECT l.*
                FROM {$locations_table} l
                WHERE l.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND l.id = ' . $id;
        }

        $active = get_array_value($options, 'active');
        if ($active !== null && $active !== '') {
            $sql .= ' AND l.active = ' . (int) $active;
        }

        $sql .= ' ORDER BY l.name ASC';
        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id = 0)
    {
        $row = $this->get_details(array('id' => $id))->getRow();
        return $row ?: null;
    }

    public function get_active_dropdown($include_blank = true)
    {
        $dropdown = array();
        if ($include_blank) {
            $dropdown[''] = '-';
        }

        $result = $this->get_details(array('active' => 1));
        foreach ($result ? $result->getResult() : array() as $row) {
            $dropdown[$row->id] = $row->name;
        }

        return $dropdown;
    }
}
