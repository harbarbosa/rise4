<?php

namespace travelrefunds\Models;

use App\Models\Crud_model;

class Travelrefunds_category_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'travelrefunds_categories';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('travelrefunds_categories');
        $builder = $this->db->table($table . ' c');
        $builder->where('c.deleted', 0);

        $active = $this->_get_clean_value($options, 'active');
        if ($active !== null && $active !== '') {
            $builder->where('c.active', $active);
        }

        $builder->orderBy('c.sort_order', 'ASC');
        $builder->orderBy('c.name', 'ASC');
        return $builder->get();
    }
}
