<?php

namespace Organizador\Models;

use App\Models\Crud_model;

class My_task_comments_model extends Crud_model
{
    protected $table = 'my_task_comments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $comments_table = $this->db->prefixTable('my_task_comments');
        $users_table = $this->db->prefixTable('users');

        $where = "WHERE $comments_table.deleted=0";

        $task_id = (int) get_array_value($options, 'task_id');
        if ($task_id) {
            $where .= " AND $comments_table.task_id=$task_id";
        }

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $comments_table.id=$id";
        }

        $sql = "SELECT $comments_table.*,
                CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) AS created_by_user,
                $users_table.image AS created_by_avatar,
                $users_table.user_type
            FROM $comments_table
            LEFT JOIN $users_table ON $users_table.id = $comments_table.created_by
            $where
            ORDER BY $comments_table.created_at DESC, $comments_table.id DESC";

        return $this->db->query($sql);
    }

    public function delete_by_task($task_id)
    {
        $task_id = (int) $task_id;
        if (!$task_id) {
            return false;
        }

        $comments = $this->get_all_where(array('task_id' => $task_id, 'deleted' => 0))->getResult();
        foreach ($comments as $comment) {
            if (!empty($comment->files)) {
                delete_app_files(get_setting('timeline_file_path'), unserialize($comment->files));
            }
        }

        return $this->db->table($this->table)->where('task_id', $task_id)->update(array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        ));
    }
}
