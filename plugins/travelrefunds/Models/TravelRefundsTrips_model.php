<?php

namespace travelrefunds\Models;

use App\Models\Crud_model;

class TravelRefundsTrips_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'travelrefunds_trips';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('travelrefunds_trips');
        $users = $this->db->prefixTable('users');
        $projects = $this->db->prefixTable('projects');

        $builder = $this->db->table($table . ' t');
        $builder->select('t.*, CONCAT(u.first_name, " ", u.last_name) AS employee_name, p.title AS project_title');
        $builder->join($users . ' u', 'u.id = t.employee_id AND u.deleted = 0', 'left');
        $builder->join($projects . ' p', 'p.id = t.project_id AND p.deleted = 0', 'left');
        $builder->where('t.deleted', 0);

        $employee_id = $this->_get_clean_value($options, 'employee_id');
        if ($employee_id) {
            $builder->where('t.employee_id', $employee_id);
        }

        $status = $this->_get_clean_value($options, 'status');
        if ($status) {
            $builder->where('t.status', $status);
        }

        $search = trim((string) $this->_get_clean_value($options, 'search_by'));
        if ($search !== '') {
            $builder->groupStart()
                ->like('t.title', $search)
                ->orLike('t.destination', $search)
                ->orLike('t.purpose', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->groupEnd();
        }

        $builder->orderBy('t.departure_date', 'DESC');
        return $builder->get();
    }
}
