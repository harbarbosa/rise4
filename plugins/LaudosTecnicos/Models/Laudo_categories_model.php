<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_categories_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudo_categories';
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
            $where .= " AND ($table.name LIKE '%$search%' OR $table.code LIKE '%$search%' OR $table.description LIKE '%$search%')";
        }

        $status_filter = $this->_get_clean_value($options, "status");
        if ($status_filter !== null && $status_filter !== '') {
            $where .= " AND $table.status='" . ($status_filter ? '1' : '0') . "'";
        }

        $sql = "SELECT * FROM $table WHERE deleted=0 $where ORDER BY $table.sort_order ASC, $table.name ASC";
        return $this->db->query($sql);
    }

    public function get_dropdown()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT id, name FROM $table WHERE deleted=0 AND status=1 ORDER BY sort_order ASC, name ASC";
        $result = $this->db->query($sql)->getResult();
        
        $dropdown = array();
        foreach ($result as $row) {
            $dropdown[$row->id] = $row->name;
        }
        return $dropdown;
    }

    public function get_active()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE deleted=0 AND status=1 ORDER BY sort_order ASC, name ASC";
        return $this->db->query($sql)->getResult();
    }

    public function has_links($id)
    {
        $laudos_table = $this->db->prefixTable('laudos_tecnicos');
        $sql = "SELECT COUNT(*) as count FROM $laudos_table WHERE category_id=$id AND deleted=0";
        $result = $this->db->query($sql)->getRow();
        return $result && $result->count > 0;
    }

    public function get_next_sort()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT MAX(sort_order) as max_sort FROM $table WHERE deleted=0";
        $result = $this->db->query($sql)->getRow();
        return $result && $result->max_sort ? $result->max_sort + 1 : 1;
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = $this->get_next_sort();
            }
        }
        return parent::ci_save($data, $id) ? true : false;
    }
}