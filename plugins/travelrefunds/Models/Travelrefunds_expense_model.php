<?php

namespace travelrefunds\Models;

use App\Models\Crud_model;

class Travelrefunds_expense_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'travelrefunds_expenses';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('travelrefunds_expenses');
        $trips = $this->db->prefixTable('travelrefunds_trips');
        $projects = $this->db->prefixTable('projects');
        $clients = $this->db->prefixTable('clients');
        $users = $this->db->prefixTable('users');
        $categories = $this->db->prefixTable('travelrefunds_categories');

        $builder = $this->db->table($table . ' e');
        $builder->select('e.*, t.title AS trip_title, p.title AS project_title, cl.company_name AS client_name, c.name AS category_name, c.name AS category_title, CONCAT(u.first_name, " ", u.last_name) AS employee_name, CONCAT(a.first_name, " ", a.last_name) AS approver_name');
        $builder->join($trips . ' t', 't.id = e.trip_id AND t.deleted = 0', 'left');
        $builder->join($projects . ' p', 'p.id = t.project_id AND p.deleted = 0', 'left');
        $builder->join($clients . ' cl', 'cl.id = t.client_id AND cl.deleted = 0', 'left');
        $builder->join($categories . ' c', 'c.id = e.category_id AND c.deleted = 0', 'left');
        $builder->join($users . ' u', 'u.id = e.employee_id AND u.deleted = 0', 'left');
        $builder->join($users . ' a', 'a.id = e.approved_by AND a.deleted = 0', 'left');
        $builder->where('e.deleted', 0);

        $trip_id = $this->_get_clean_value($options, 'trip_id');
        if ($trip_id) {
            $builder->where('e.trip_id', $trip_id);
        }

        $employee_id = $this->_get_clean_value($options, 'employee_id');
        if ($employee_id) {
            $builder->where('e.employee_id', $employee_id);
        }

        $project_id = $this->_get_clean_value($options, 'project_id');
        if ($project_id) {
            $builder->where('t.project_id', $project_id);
        }

        $client_id = $this->_get_clean_value($options, 'client_id');
        if ($client_id) {
            $builder->where('t.client_id', $client_id);
        }

        $category_id = $this->_get_clean_value($options, 'category_id');
        if ($category_id) {
            $builder->where('e.category_id', $category_id);
        }

        $status = $this->_get_clean_value($options, 'status');
        if ($status) {
            $builder->where('e.status', $status);
        }

        $status_not = $this->_get_clean_value($options, 'status_not');
        if ($status_not) {
            $builder->where('e.status !=', $status_not);
        }

        $start_date = $this->_get_clean_value($options, 'start_date');
        if ($start_date) {
            $builder->where('e.expense_date >=', $start_date);
        }

        $end_date = $this->_get_clean_value($options, 'end_date');
        if ($end_date) {
            $builder->where('e.expense_date <=', $end_date);
        }

        $search = trim((string) $this->_get_clean_value($options, 'search_by'));
        if ($search !== '') {
            $builder->groupStart()
                ->like('e.description', $search)
                ->orLike('e.invoice_number', $search)
                ->orLike('e.supplier_name', $search)
                ->orLike('e.vendor', $search)
                ->orLike('c.name', $search)
                ->groupEnd();
        }

        $builder->orderBy('e.expense_date', 'DESC');
        return $builder->get();
    }
}
