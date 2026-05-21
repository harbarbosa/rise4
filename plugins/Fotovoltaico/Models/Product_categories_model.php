<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Product_categories_model extends Crud_model
{
    protected $table = 'fv_product_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('title', 'slug', 'description', 'color', 'sort', 'active', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_product_categories');
        $where = "WHERE $table.deleted=0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.title LIKE '%$search%' ESCAPE '!' OR $table.slug LIKE '%$search%' ESCAPE '!')";
        }

        $active_only = get_array_value($options, 'active_only');
        if ($active_only !== null && $active_only !== '') {
            $where .= " AND $table.active=" . (int) $active_only;
        }

        try {
            return $this->db->query("SELECT * FROM $table $where ORDER BY $table.sort ASC, $table.title ASC");
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Product categories query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
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
