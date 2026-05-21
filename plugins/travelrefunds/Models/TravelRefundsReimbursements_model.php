<?php

namespace travelrefunds\Models;

use App\Models\Crud_model;

class TravelRefundsReimbursements_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'travelrefunds_reimbursements';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('travelrefunds_reimbursements');
        $trips = $this->db->prefixTable('travelrefunds_trips');
        $users = $this->db->prefixTable('users');
        $categories = $this->db->prefixTable('travelrefunds_categories');

        $builder = $this->db->table($table . ' r');
        $builder->select('r.*, t.title AS trip_title, c.title AS category_title, CONCAT(u.first_name, " ", u.last_name) AS employee_name, CONCAT(a.first_name, " ", a.last_name) AS approver_name');
        $builder->join($trips . ' t', 't.id = r.trip_id AND t.deleted = 0', 'left');
        $builder->join($categories . ' c', 'c.id = r.category_id AND c.deleted = 0', 'left');
        $builder->join($users . ' u', 'u.id = r.employee_id AND u.deleted = 0', 'left');
        $builder->join($users . ' a', 'a.id = r.approved_by AND a.deleted = 0', 'left');
        $builder->where('r.deleted', 0);

        $trip_id = $this->_get_clean_value($options, 'trip_id');
        if ($trip_id) {
            $builder->where('r.trip_id', $trip_id);
        }

        $employee_id = $this->_get_clean_value($options, 'employee_id');
        if ($employee_id) {
            $builder->where('r.employee_id', $employee_id);
        }

        $status = $this->_get_clean_value($options, 'status');
        if ($status) {
            $builder->where('r.status', $status);
        }

        $builder->orderBy('r.expense_date', 'DESC');
        return $builder->get();
    }
}
