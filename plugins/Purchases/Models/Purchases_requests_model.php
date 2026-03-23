<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_requests_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_requests';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_requests');
        $projects_table = $this->db->prefixTable('projects');
        $users_table = $this->db->prefixTable('users');
        $os_table = $this->db->prefixTable('os_ordens');
        $has_os_table = false;

        try {
            $like = $this->db->query("SHOW TABLES LIKE '" . $os_table . "'");
            $has_os_table = ($like && method_exists($like, 'getResult') && count($like->getResult()) > 0);
        } catch (\Throwable $e) {
            $has_os_table = false;
        }
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $table.company_id=$company_id";
        }

        $requested_by = $this->_get_clean_value($options, "requested_by");
        if ($requested_by) {
            $where .= " AND $table.requested_by=$requested_by";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $status_in = get_array_value($options, "status_in");
        if (is_array($status_in) && $status_in) {
            $escaped = array();
            foreach ($status_in as $status_value) {
                $escaped[] = $this->db->escape($status_value);
            }
            $where .= " AND $table.status IN (" . implode(",", $escaped) . ")";
        }

        $visibility_user_id = $this->_get_clean_value($options, "visibility_user_id");
        if ($visibility_user_id) {
            $visibility_user_id = (int)$visibility_user_id;
            $where .= " AND ($table.status!='draft' OR $table.requested_by=$visibility_user_id OR $table.created_by=$visibility_user_id)";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $table.project_id=$project_id";
        }

        $request_code = $this->_get_clean_value($options, "request_code");
        if ($request_code) {
            $request_code = $this->db->escapeLikeString($request_code);
            $where .= " AND $table.request_code LIKE '%$request_code%' ESCAPE '!'";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.request_code LIKE '%$search%' ESCAPE '!' OR $table.note LIKE '%$search%' ESCAPE '!')";
        }

        $start_date = $this->_get_clean_value($options, "start_date");
        $end_date = $this->_get_clean_value($options, "end_date");
        if ($start_date && $end_date) {
            $where .= " AND ($table.created_at BETWEEN '$start_date' AND '$end_date')";
        } else if ($start_date) {
            $where .= " AND $table.created_at >= '$start_date'";
        } else if ($end_date) {
            $where .= " AND $table.created_at <= '$end_date'";
        }

        $select = "$table.*,
            $projects_table.title AS project_title,
            CONCAT(requested_by_user.first_name, ' ', requested_by_user.last_name) AS requested_by_name,
            CONCAT(approved_by_user.first_name, ' ', approved_by_user.last_name) AS approved_by_name";

        if ($has_os_table) {
            $select .= ", $os_table.titulo AS os_title";
        }

        $sql = "SELECT $select
        FROM $table
        LEFT JOIN $projects_table ON $projects_table.id=$table.project_id
        LEFT JOIN $users_table AS requested_by_user ON requested_by_user.id=$table.requested_by
        LEFT JOIN $users_table AS approved_by_user ON approved_by_user.id=$table.approved_by";

        if ($has_os_table) {
            $sql .= " LEFT JOIN $os_table ON $os_table.id=$table.os_id";
        }

        $sql .= " WHERE $table.deleted=0 $where
        ORDER BY $table.id DESC";

        return $this->db->query($sql);
    }

    public function get_next_request_code_data($company_id = 0)
    {
        $table = $this->db->prefixTable('purchases_requests');
        $company_id = (int)$company_id;
        $sql = "SELECT MAX($table.request_code_number) AS max_number FROM $table WHERE $table.deleted=0 AND $table.company_id=$company_id";
        $row = $this->db->query($sql)->getRow();
        $next_number = $row && $row->max_number ? ((int)$row->max_number + 1) : 1;
        $request_code = 'RC-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);

        return array(
            'request_code_number' => $next_number,
            'request_code' => $request_code
        );
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }

        return $this->ci_save($row, $id) ? true : false;
    }

    public function delete($id = 0, $undo = false)
    {
        return parent::delete($id, $undo);
    }
}
