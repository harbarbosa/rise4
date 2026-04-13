<?php

namespace Organizador\Models;

use App\Models\Crud_model;

class My_task_reminders_model extends Crud_model
{
    protected $table = 'my_task_reminders';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $reminders_table = $this->db->prefixTable('my_task_reminders');
        $users_table = $this->db->prefixTable('users');

        $where = "WHERE $reminders_table.deleted=0";

        $task_id = (int) get_array_value($options, 'task_id');
        if ($task_id) {
            $where .= " AND $reminders_table.task_id=$task_id";
        }

        $created_by = (int) get_array_value($options, 'created_by');
        if ($created_by) {
            $where .= " AND $reminders_table.created_by=$created_by";
        }

        $sql = "SELECT $reminders_table.*,
                CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) AS created_by_user,
                $users_table.image AS created_by_avatar
            FROM $reminders_table
            LEFT JOIN $users_table ON $users_table.id = $reminders_table.created_by
            $where
            ORDER BY $reminders_table.remind_at ASC, $reminders_table.id DESC";

        return $this->db->query($sql);
    }

    public function delete_by_task($task_id)
    {
        return $this->db->table($this->table)->where('task_id', (int) $task_id)->update(array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        ));
    }
}
