<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_request_approvals_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_request_approvals';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('purchases_request_approvals');
        $users_table = $this->db->prefixTable('users');
        $where = "";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $company_id = $this->_get_clean_value($options, "company_id");
        if ($company_id) {
            $where .= " AND $table.company_id=$company_id";
        }

        $request_id = $this->_get_clean_value($options, "request_id");
        if ($request_id) {
            $where .= " AND $table.request_id=$request_id";
        }

        $approval_type = $this->_get_clean_value($options, "approval_type");
        if ($approval_type) {
            $where .= " AND $table.approval_type='$approval_type'";
        }

        $approved = $this->_get_clean_value($options, "approved");
        if ($approved !== null && $approved !== "") {
            $where .= " AND $table.approved=" . (int)$approved;
        }

        $sql = "SELECT $table.*,
            CONCAT(approved_user.first_name, ' ', approved_user.last_name) AS approved_by_name
        FROM $table
        LEFT JOIN $users_table AS approved_user ON approved_user.id=$table.approved_by
        WHERE $table.deleted=0 $where
        ORDER BY $table.id ASC";

        return $this->db->query($sql);
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
