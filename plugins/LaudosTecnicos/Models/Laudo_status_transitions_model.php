<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_status_transitions_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudo_status_transitions';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $status_table = $this->db->prefixTable('laudo_status');
        $where = "";

        $from_status_id = $this->_get_clean_value($options, "from_status_id");
        if ($from_status_id) {
            $where .= " AND $table.from_status_id=$from_status_id";
        }

        $to_status_id = $this->_get_clean_value($options, "to_status_id");
        if ($to_status_id) {
            $where .= " AND $table.to_status_id=$to_status_id";
        }

        $sql = "SELECT $table.*, 
            from_status.name as from_status_name, from_status.code as from_status_code,
            to_status.name as to_status_name, to_status.code as to_status_code
        FROM $table 
        LEFT JOIN $status_table as from_status ON from_status.id = $table.from_status_id
        LEFT JOIN $status_table as to_status ON to_status.id = $table.to_status_id
        WHERE $table.deleted=0 $where
        ORDER BY $table.sort_order ASC";

        return $this->db->query($sql);
    }

    public function can_transition($from_status_code, $to_status_code)
    {
        $status_model = model(Laudo_status_model::class);
        
        $from_status = $status_model->get_by_code($from_status_code);
        $to_status = $status_model->get_by_code($to_status_code);
        
        if (!$from_status || !$to_status) {
            return false;
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT COUNT(*) as count FROM $table 
            WHERE from_status_id={$from_status->id} 
            AND to_status_id={$to_status->id} 
            AND deleted=0";
        
        $result = $this->db->query($sql)->getRow();
        return $result && $result->count > 0;
    }

    public function get_transitions_from($from_status_code)
    {
        $status_model = model(Laudo_status_model::class);
        $from_status = $status_model->get_by_code($from_status_code);
        
        if (!$from_status) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $status_table = $this->db->prefixTable('laudo_status');
        
        $sql = "SELECT $table.*, to_status.name as to_status_name, to_status.code as to_status_code, to_status.color as to_status_color
        FROM $table 
        LEFT JOIN $status_table as to_status ON to_status.id = $table.to_status_id
        WHERE $table.from_status_id={$from_status->id} AND $table.deleted=0
        ORDER BY $table.sort_order ASC";
        
        return $this->db->query($sql)->getResult();
    }

    public function get_all_as_dropdown()
    {
        $transitions = $this->get_details()->getResult();
        $result = array();
        
        foreach ($transitions as $t) {
            $key = $t->from_status_code . '->' . $t->to_status_code;
            $result[$key] = $t->from_status_name . ' → ' . $t->to_status_name;
        }
        
        return $result;
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