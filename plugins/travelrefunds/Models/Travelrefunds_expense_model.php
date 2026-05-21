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
        $users = $this->db->prefixTable('users');
        $categories = $this->db->prefixTable('travelrefunds_categories');

        $builder = $this->db->table($table . ' e');
        $builder->select('e.*, t.title AS trip_title, c.name AS category_name, c.name AS category_title, CONCAT(u.first_name, " ", u.last_name) AS employee_name, CONCAT(a.first_name, " ", a.last_name) AS approver_name');
        $builder->join($trips . ' t', 't.id = e.trip_id AND t.deleted = 0', 'left');
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

        $category_id = $this->_get_clean_value($options, 'category_id');
        if ($category_id) {
            $builder->where('e.category_id', $category_id);
        }

        $status = $this->_get_clean_value($options, 'status');
        if ($status) {
            $builder->where('e.status', $status);
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
