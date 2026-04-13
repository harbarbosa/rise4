<?php

namespace Organizador\Models;

use CodeIgniter\Model;

class My_task_notifications_model extends Model
{
    protected $table = 'my_task_notifications';
    protected $primaryKey = 'id';
    protected $allowedFields = array('task_id', 'user_id', 'event', 'channel', 'is_sent', 'sent_at', 'created_at');

    public function save_log($data)
    {
        return $this->insert($data);
    }

    public function get_last_for_task($task_id, $event)
    {
        return $this->where('task_id', (int) $task_id)
            ->where('event', $event)
            ->orderBy('id', 'DESC')
            ->first();
    }
}
