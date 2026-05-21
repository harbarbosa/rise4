<?php

namespace travelrefunds\Models;

use App\Models\Crud_model;

class Travelrefunds_trip_model extends Crud_model
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
        $clients = $this->db->prefixTable('clients');

        $builder = $this->db->table($table . ' t');
        $builder->select('t.*, CONCAT(u.first_name, " ", u.last_name) AS employee_name, p.title AS project_title, c.company_name AS client_name');
        $builder->join($users . ' u', 'u.id = t.employee_id AND u.deleted = 0', 'left');
        $builder->join($projects . ' p', 'p.id = t.project_id AND p.deleted = 0', 'left');
        $builder->join($clients . ' c', 'c.id = t.client_id AND c.deleted = 0', 'left');
        $builder->where('t.deleted', 0);

        $employee_id = $this->_get_clean_value($options, 'employee_id');
        if ($employee_id) {
            $builder->where('t.employee_id', $employee_id);
        }

        $project_id = $this->_get_clean_value($options, 'project_id');
        if ($project_id) {
            $builder->where('t.project_id', $project_id);
        }

        $client_id = $this->_get_clean_value($options, 'client_id');
        if ($client_id) {
            $builder->where('t.client_id', $client_id);
        }

        $status = $this->_get_clean_value($options, 'status');
        if ($status) {
            $builder->where('t.status', $status);
        }

        $status_not = $this->_get_clean_value($options, 'status_not');
        if ($status_not) {
            $builder->where('t.status !=', $status_not);
        }

        $start_date = $this->_get_clean_value($options, 'start_date');
        if ($start_date) {
            $builder->where('COALESCE(t.start_date, t.departure_date) >=', $start_date);
        }

        $end_date = $this->_get_clean_value($options, 'end_date');
        if ($end_date) {
            $builder->where('COALESCE(t.end_date, t.return_date) <=', $end_date);
        }

        $search = trim((string) $this->_get_clean_value($options, 'search_by'));
        if ($search !== '') {
            $builder->groupStart()
                ->like('t.title', $search)
                ->orLike('t.destination', $search)
                ->orLike('t.purpose', $search)
                ->orLike('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('c.company_name', $search)
                ->groupEnd();
        }

        $builder->orderBy('COALESCE(t.start_date, t.departure_date)', 'DESC', false);
        return $builder->get();
    }
}
