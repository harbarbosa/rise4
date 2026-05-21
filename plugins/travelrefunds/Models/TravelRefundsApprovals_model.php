<?php

namespace travelrefunds\Models;

use App\Models\Crud_model;

class TravelRefundsApprovals_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'travelrefunds_approval_logs';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('travelrefunds_approval_logs');
        $builder = $this->db->table($table . ' a');
        $builder->where('a.deleted', 0);
        $builder->orderBy('a.created_at', 'DESC');
        return $builder->get();
    }
}
