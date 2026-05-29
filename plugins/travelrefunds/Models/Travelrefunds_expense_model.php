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
        if (!$this->db->tableExists($table) || !$this->db->tableExists($trips)) {
            return $this->db->query("SELECT 1 AS __empty WHERE 1=0");
        }
        $expense_fields = $this->db->getFieldNames($table);
        $has_project_id = in_array('project_id', $expense_fields, true);
        $has_employee_id = in_array('employee_id', $expense_fields, true);
        $has_approved_by = in_array('approved_by', $expense_fields, true);
        $has_status = in_array('status', $expense_fields, true);
        $has_expense_date = in_array('expense_date', $expense_fields, true);
        $has_vendor = in_array('vendor', $expense_fields, true);
        $has_supplier_name = in_array('supplier_name', $expense_fields, true);

        $builder = $this->db->table($table . ' e');
        $project_select = $has_project_id ? 'COALESCE(ep.title, p.title) AS project_title' : 'p.title AS project_title';
        $select_parts = ['e.*', 't.title AS trip_title', $project_select, 'cl.company_name AS client_name', 'c.name AS category_name', 'c.name AS category_title'];
        if ($has_employee_id) {
            $select_parts[] = 'CONCAT(u.first_name, " ", u.last_name) AS employee_name';
        }
        if ($has_approved_by) {
            $select_parts[] = 'CONCAT(a.first_name, " ", a.last_name) AS approver_name';
        }
        $builder->select(implode(', ', $select_parts));
        $builder->join($trips . ' t', 't.id = e.trip_id AND t.deleted = 0', 'left');
        if ($has_project_id) {
            $builder->join($projects . ' ep', 'ep.id = e.project_id AND ep.deleted = 0', 'left');
        }
        $builder->join($projects . ' p', 'p.id = t.project_id AND p.deleted = 0', 'left');
        $builder->join($clients . ' cl', 'cl.id = t.client_id AND cl.deleted = 0', 'left');
        $builder->join($categories . ' c', 'c.id = e.category_id AND c.deleted = 0', 'left');
        if ($has_employee_id) {
            $builder->join($users . ' u', 'u.id = e.employee_id AND u.deleted = 0', 'left');
        }
        if ($has_approved_by) {
            $builder->join($users . ' a', 'a.id = e.approved_by AND a.deleted = 0', 'left');
        }
        if (in_array('deleted', $expense_fields, true)) {
            $builder->where('e.deleted', 0);
        }

        $trip_id = $this->_get_clean_value($options, 'trip_id');
        if ($trip_id) {
            $builder->where('e.trip_id', $trip_id);
        }

        $employee_id = $this->_get_clean_value($options, 'employee_id');
        if ($employee_id && $has_employee_id) {
            $builder->where('e.employee_id', $employee_id);
        }

        $project_id = $this->_get_clean_value($options, 'project_id');
        if ($project_id) {
            if ($has_project_id) {
                $builder->groupStart()
                    ->where('e.project_id', $project_id)
                    ->orWhere('t.project_id', $project_id)
                    ->groupEnd();
            } else {
                $builder->where('t.project_id', $project_id);
            }
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
        if ($status && $has_status) {
            $builder->where('e.status', $status);
        }

        $status_not = $this->_get_clean_value($options, 'status_not');
        if ($status_not && $has_status) {
            $builder->where('e.status !=', $status_not);
        }

        $start_date = $this->_get_clean_value($options, 'start_date');
        if ($start_date && $has_expense_date) {
            $builder->where('e.expense_date >=', $start_date);
        }

        $end_date = $this->_get_clean_value($options, 'end_date');
        if ($end_date && $has_expense_date) {
            $builder->where('e.expense_date <=', $end_date);
        }

        $search = trim((string) $this->_get_clean_value($options, 'search_by'));
        if ($search !== '') {
            $builder->groupStart()
                ->like('e.description', $search)
                ->orLike('e.invoice_number', $search)
                ->orLike($has_supplier_name ? 'e.supplier_name' : 'e.description', $search)
                ->orLike($has_vendor ? 'e.vendor' : 'e.description', $search)
                ->orLike('c.name', $search)
                ->groupEnd();
        }

        $builder->orderBy('e.expense_date', 'DESC');
        return $builder->get();
    }
}
