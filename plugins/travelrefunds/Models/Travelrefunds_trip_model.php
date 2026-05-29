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
        if (!$this->db->tableExists($table)) {
            return $this->db->query("SELECT 1 AS __empty WHERE 1=0");
        }

        $fields = $this->db->getFieldNames($table) ?: [];
        $has_employee_id = in_array('employee_id', $fields, true);
        $has_project_id = in_array('project_id', $fields, true);
        $has_client_id = in_array('client_id', $fields, true);
        $has_status = in_array('status', $fields, true);
        $has_start_date = in_array('start_date', $fields, true);
        $has_departure_date = in_array('departure_date', $fields, true);
        $has_end_date = in_array('end_date', $fields, true);
        $has_return_date = in_array('return_date', $fields, true);

        $builder = $this->db->table($table . ' t');
        $select_parts = ['t.*'];
        if ($has_employee_id) {
            $select_parts[] = 'CONCAT(u.first_name, " ", u.last_name) AS employee_name';
        }
        if ($has_project_id) {
            $select_parts[] = 'p.title AS project_title';
        }
        if ($has_client_id) {
            $select_parts[] = 'c.company_name AS client_name';
        }
        $builder->select(implode(', ', $select_parts));
        if ($has_employee_id) {
            $builder->join($users . ' u', 'u.id = t.employee_id AND u.deleted = 0', 'left');
        }
        if ($has_project_id) {
            $builder->join($projects . ' p', 'p.id = t.project_id AND p.deleted = 0', 'left');
        }
        if ($has_client_id) {
            $builder->join($clients . ' c', 'c.id = t.client_id AND c.deleted = 0', 'left');
        }
        if (in_array('deleted', $fields, true)) {
            $builder->where('t.deleted', 0);
        }

        $employee_id = $this->_get_clean_value($options, 'employee_id');
        if ($employee_id && $has_employee_id) {
            $builder->where('t.employee_id', $employee_id);
        }

        $project_id = $this->_get_clean_value($options, 'project_id');
        if ($project_id && $has_project_id) {
            $builder->where('t.project_id', $project_id);
        }

        $client_id = $this->_get_clean_value($options, 'client_id');
        if ($client_id && $has_client_id) {
            $builder->where('t.client_id', $client_id);
        }

        $trip_id = $this->_get_clean_value($options, 'id');
        if ($trip_id) {
            $builder->where('t.id', $trip_id);
        }

        $status = $this->_get_clean_value($options, 'status');
        if ($status && $has_status) {
            $builder->where('t.status', $status);
        }

        $status_not = $this->_get_clean_value($options, 'status_not');
        if ($status_not && $has_status) {
            $builder->where('t.status !=', $status_not);
        }

        $start_date = $this->_get_clean_value($options, 'start_date');
        if ($start_date && ($has_start_date || $has_departure_date)) {
            if ($has_start_date && $has_departure_date) {
                $builder->where('COALESCE(t.start_date, t.departure_date) >=', $start_date);
            } elseif ($has_start_date) {
                $builder->where('t.start_date >=', $start_date);
            } else {
                $builder->where('t.departure_date >=', $start_date);
            }
        }

        $end_date = $this->_get_clean_value($options, 'end_date');
        if ($end_date && ($has_end_date || $has_return_date)) {
            if ($has_end_date && $has_return_date) {
                $builder->where('COALESCE(t.end_date, t.return_date) <=', $end_date);
            } elseif ($has_end_date) {
                $builder->where('t.end_date <=', $end_date);
            } else {
                $builder->where('t.return_date <=', $end_date);
            }
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

        if ($has_start_date && $has_departure_date) {
            $builder->orderBy('COALESCE(t.start_date, t.departure_date)', 'DESC', false);
        } elseif ($has_start_date) {
            $builder->orderBy('t.start_date', 'DESC');
        } elseif ($has_departure_date) {
            $builder->orderBy('t.departure_date', 'DESC');
        } else {
            $builder->orderBy('t.id', 'DESC');
        }
        return $builder->get();
    }
}
