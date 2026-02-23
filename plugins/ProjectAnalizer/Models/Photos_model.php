<?php

namespace ProjectAnalizer\Models;

use CodeIgniter\Model;

class Photos_model extends Model
{
    protected $table = 'rise_projectanalizer_photos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['timelog_id', 'file_name', 'file_path', 'uploaded_by', 'created_at'];

    public function get_by_timelog($timelog_id)
    {
        if (!$this->db->tableExists($this->table)) {
            return [];
        }

        $timelog_id = (int)$timelog_id;
        if ($timelog_id <= 0) {
            return [];
        }

        return $this->where('timelog_id', $timelog_id)->findAll();
    }
}
