<?php

namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Execution_schedule_model extends Crud_model
{
    protected $table = "projectanalizer_execution_schedule";
    protected $group_key_column_checked = false;
    protected $has_group_key_column = false;

    public function __construct()
    {
        parent::__construct($this->table);
        $this->ensure_group_key_column();
    }

    public function get_details($options = array())
    {
        $execution_schedule_table = $this->table;
        $projects_table = $this->db->prefixTable("projects");
        $users_table = $this->db->prefixTable("users");

        $where = "$execution_schedule_table.deleted = 0";

        $id = get_array_value($options, "id");
        if ($id) {
            $id = $this->_get_clean_value($id);
            $where .= " AND $execution_schedule_table.id = $id";
        }

        $project_id = get_array_value($options, "project_id");
        if ($project_id) {
            $project_id = $this->_get_clean_value($project_id);
            $where .= " AND $execution_schedule_table.project_id = $project_id";
        }

        $user_id = get_array_value($options, "user_id");
        if ($user_id) {
            $user_id = $this->_get_clean_value($user_id);
            $where .= " AND $execution_schedule_table.user_id = $user_id";
        }

        $start_date = get_array_value($options, "start_date");
        if ($start_date) {
            $start_date = $this->_get_clean_value($start_date);
            $where .= " AND $execution_schedule_table.end_date >= '$start_date'";
        }

        $end_date = get_array_value($options, "end_date");
        if ($end_date) {
            $end_date = $this->_get_clean_value($end_date);
            $where .= " AND $execution_schedule_table.start_date <= '$end_date'";
        }

        $sql = "SELECT $execution_schedule_table.*,
                       $projects_table.title AS project_title,
                       CONCAT($users_table.first_name, ' ', $users_table.last_name) AS member_name
                FROM $execution_schedule_table
                LEFT JOIN $projects_table ON $projects_table.id = $execution_schedule_table.project_id
                LEFT JOIN $users_table ON $users_table.id = $execution_schedule_table.user_id
                WHERE $where
                ORDER BY $execution_schedule_table.start_date ASC, $users_table.first_name ASC";

        return $this->db->query($sql);
    }

    public function get_group_rows($group_key = null, $fallback_id = 0, $include_deleted = false)
    {
        $execution_schedule_table = $this->table;
        $has_group_key_column = $this->ensure_group_key_column();

        if ($group_key && $has_group_key_column) {
            $group_key = $this->_get_clean_value($group_key);
            $where = "$execution_schedule_table.group_key = '$group_key'";
        } else {
            $fallback_id = $this->_get_clean_value($fallback_id);
            $where = "$execution_schedule_table.id = $fallback_id";
        }

        if (!$include_deleted) {
            $where .= " AND $execution_schedule_table.deleted = 0";
        }

        $sql = "SELECT *
                FROM $execution_schedule_table
                WHERE $where
                ORDER BY $execution_schedule_table.id ASC";

        return $this->db->query($sql)->getResult();
    }

    public function has_conflict($user_id, $start_date, $end_date, $exclude_id = 0, $exclude_group_key = null)
    {
        $execution_schedule_table = $this->table;
        $has_group_key_column = $this->ensure_group_key_column();

        $user_id = $this->_get_clean_value($user_id);
        $start_date = $this->_get_clean_value($start_date);
        $end_date = $this->_get_clean_value($end_date);
        $exclude_id = $this->_get_clean_value($exclude_id);
        $exclude_group_key = $exclude_group_key ? $this->_get_clean_value($exclude_group_key) : null;

        $where = "$execution_schedule_table.deleted = 0
            AND $execution_schedule_table.user_id = $user_id
            AND $execution_schedule_table.start_date <= '$end_date'
            AND $execution_schedule_table.end_date >= '$start_date'";

        if ($exclude_group_key && $has_group_key_column) {
            $where .= " AND ($execution_schedule_table.group_key IS NULL OR $execution_schedule_table.group_key <> '$exclude_group_key')";
        } elseif ($exclude_id) {
            $where .= " AND $execution_schedule_table.id <> $exclude_id";
        }

        $sql = "SELECT $execution_schedule_table.id
                FROM $execution_schedule_table
                WHERE $where
                LIMIT 1";

        return (bool) $this->db->query($sql)->getRow();
    }

    public function supports_group_key()
    {
        return $this->ensure_group_key_column();
    }

    private function ensure_group_key_column()
    {
        if ($this->group_key_column_checked) {
            return $this->has_group_key_column;
        }

        $this->group_key_column_checked = true;
        $table_name = $this->db->prefixTable($this->table);

        try {
            $column_info = $this->db->query("SHOW COLUMNS FROM `" . $table_name . "` LIKE 'group_key'")->getRow();
            if ($column_info) {
                $this->has_group_key_column = true;
                return true;
            }

            $this->db->query("ALTER TABLE `" . $table_name . "` ADD COLUMN `group_key` VARCHAR(50) NULL DEFAULT NULL AFTER `project_id`");
            $index_info = $this->db->query("SHOW INDEX FROM `" . $table_name . "` WHERE Key_name = 'idx_pa_execution_schedule_group_key'")->getRow();
            if (!$index_info) {
                $this->db->query("ALTER TABLE `" . $table_name . "` ADD INDEX `idx_pa_execution_schedule_group_key` (`group_key`)");
            }

            $this->has_group_key_column = true;
        } catch (\Throwable $e) {
            $this->has_group_key_column = false;
        }

        return $this->has_group_key_column;
    }
}
