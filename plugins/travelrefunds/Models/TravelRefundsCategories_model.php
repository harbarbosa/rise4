<?php

namespace travelrefunds\Models;

use App\Models\Crud_model;

class TravelRefundsCategories_model extends Crud_model
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
        $builder = $this->db->table($table);
        $builder->where('deleted', 0);
        $builder->orderBy('sort', 'ASC');
        $builder->orderBy('title', 'ASC');
        return $builder->get();
    }
}
