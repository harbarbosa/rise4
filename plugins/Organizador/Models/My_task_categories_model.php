<?php

namespace Organizador\Models;

use App\Models\Crud_model;

class My_task_categories_model extends Crud_model
{
    protected $table = 'my_task_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('my_task_categories');
        $where = "WHERE $table.deleted=0";
        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        return $this->db->query("SELECT * FROM $table $where ORDER BY $table.sort ASC, $table.title ASC");
    }

    public function get_dropdown()
    {
        $result = array("" => "-");
        foreach ($this->get_details()->getResult() as $row) {
            $result[$row->id] = $row->title;
        }
        return $result;
    }
}
