<?php

namespace ProjectAnalizer\Models;

use CodeIgniter\Model;

class Photos_model extends Model
{
    protected $table = 'rise_projectanalizer_photos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['timelog_id', 'file_name', 'file_path', 'uploaded_by', 'created_at'];

    public function ensureTableExists(): bool
    {
        if ($this->db->tableExists($this->table)) {
            return true;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `timelog_id` INT(11) NOT NULL,
            `file_name` VARCHAR(255) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `uploaded_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `timelog_id` (`timelog_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        return $this->db->tableExists($this->table);
    }

    public function get_by_timelog($timelog_id)
    {
        if (!$this->ensureTableExists()) {
            return [];
        }

        $timelog_id = (int)$timelog_id;
        if ($timelog_id <= 0) {
            return [];
        }

        return $this->where('timelog_id', $timelog_id)->findAll();
    }
}
