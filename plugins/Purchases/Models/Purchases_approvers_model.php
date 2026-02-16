<?php

namespace Purchases\Models;

use App\Models\Crud_model;

class Purchases_approvers_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'purchases_approvers';
        parent::__construct($this->table);
    }

    public function get_one_by_user($user_id, $company_id)
    {
        $table = $this->db->prefixTable('purchases_approvers');
        $user_id = (int)$user_id;
        $company_id = (int)$company_id;

        $sql = "SELECT $table.* FROM $table WHERE $table.deleted=0 AND $table.user_id=$user_id AND $table.company_id=$company_id LIMIT 1";
        $query = $this->db->query($sql);
        if (!$query || !method_exists($query, 'getRow')) {
            return null;
        }

        return $query->getRow();
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
