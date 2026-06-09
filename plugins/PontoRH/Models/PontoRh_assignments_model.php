<?php

namespace PontoRH\Models;

class PontoRh_assignments_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_schedule_days';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $assignments_table = $this->db->prefixTable($this->table);
        $shifts_table = $this->db->prefixTable('pontorh_work_schedules');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT a.*,
                    s.name AS shift_name,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name
                FROM {$assignments_table} a
                LEFT JOIN {$shifts_table} s ON s.id = a.work_schedule_id
                LEFT JOIN {$users_table} u ON u.id = a.team_member_id
                WHERE a.deleted = 0";

        $work_schedule_id = (int) get_array_value($options, 'work_schedule_id');
        if ($work_schedule_id) {
            $sql .= ' AND a.work_schedule_id = ' . $work_schedule_id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND a.team_member_id = ' . $team_member_id;
        }

        $day_of_week = get_array_value($options, 'day_of_week');
        if ($day_of_week !== null && $day_of_week !== '') {
            $sql .= ' AND a.day_of_week = ' . (int) $day_of_week;
        }

        $sql .= ' ORDER BY a.active DESC, a.id DESC';
        return $this->queryOrEmpty($sql);
    }

    public function get_active_shift_for_member($team_member_id)
    {
        $result = $this->get_details(array('team_member_id' => $team_member_id));
        $row = $result ? $result->getRow() : null;
        return $row ?: null;
    }

    public function get_member_workdays($team_member_id, $work_schedule_id = 0)
    {
        $options = array('team_member_id' => (int) $team_member_id);
        if ($work_schedule_id) {
            $options['work_schedule_id'] = (int) $work_schedule_id;
        }

        $result = $this->get_details($options);
        $rows = $result ? $result->getResult() : array();
        $days = array();
        foreach ($rows as $row) {
            $day = (int) $row->day_of_week;
            if ($day >= 1 && $day <= 7) {
                $days[] = $day;
            }
        }

        return array_values(array_unique($days));
    }
}
