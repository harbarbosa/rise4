<?php

namespace PontoRH\Models;

class PontoRh_shifts_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_work_schedules';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $shifts_table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');
        $members_table = $this->db->prefixTable('pontorh_work_schedule_members');

        $members_sql = "(SELECT GROUP_CONCAT(CONCAT(TRIM(COALESCE(mu.first_name, '')), ' ', TRIM(COALESCE(mu.last_name, ''))) ORDER BY mu.first_name SEPARATOR ', ')
                        FROM {$members_table} wsm
                        LEFT JOIN {$users_table} mu ON mu.id = wsm.team_member_id
                        WHERE wsm.deleted = 0 AND wsm.work_schedule_id = s.id) AS team_members_name";

        $sql = "SELECT s.*,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name,
                    {$members_sql}
                FROM {$shifts_table} s
                LEFT JOIN {$users_table} u ON u.id = s.team_member_id
                WHERE s.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND s.id = ' . $id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND (s.team_member_id = ' . $team_member_id . ' OR EXISTS (SELECT 1 FROM ' . $members_table . ' wsm WHERE wsm.deleted = 0 AND wsm.work_schedule_id = s.id AND wsm.team_member_id = ' . $team_member_id . '))';
        }

        $active = get_array_value($options, 'active');
        if ($active !== null && $active !== '') {
            $sql .= ' AND s.active = ' . (int) $active;
        }

        $schedule_type = trim((string) get_array_value($options, 'schedule_type'));
        if ($schedule_type !== '') {
            $sql .= ' AND s.schedule_type = ' . $this->db->escape($schedule_type);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (s.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR s.description LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.first_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.last_name LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= ' ORDER BY s.active DESC, s.name ASC';
        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id = 0)
    {
        $row = $this->get_details(array('id' => $id))->getRow();
        return $row ?: null;
    }

    public function get_active_dropdown($include_blank = true)
    {
        $dropdown = array();
        if ($include_blank) {
            $dropdown[''] = '-';
        }

        $result = $this->get_details(array('active' => 1));
        foreach ($result ? $result->getResult() : array() as $row) {
            $label = $row->name;
            if (!empty($row->team_members_name)) {
                $label .= ' - ' . $row->team_members_name;
            } else if (!empty($row->team_member_name)) {
                $label .= ' - ' . $row->team_member_name;
            }
            $dropdown[$row->id] = $label;
        }

        return $dropdown;
    }

    public function get_member_ids(int $schedule_id): array
    {
        $members_model = model('PontoRH\\Models\\PontoRh_work_schedule_members_model');
        return $members_model->get_member_ids_by_schedule($schedule_id);
    }

    public function sync_members(int $schedule_id, array $team_member_ids, int $created_by): bool
    {
        $members_model = model('PontoRH\\Models\\PontoRh_work_schedule_members_model');
        return $members_model->sync_members($schedule_id, $team_member_ids, $created_by);
    }

    public function get_active_schedule_for_member($team_member_id)
    {
        $team_member_id = (int) $team_member_id;
        if (!$team_member_id) {
            return null;
        }

        $shifts_table = $this->db->prefixTable($this->table);
        $members_table = $this->db->prefixTable('pontorh_work_schedule_members');

        $sql = "SELECT s.*
                FROM {$shifts_table} s
                WHERE s.deleted = 0 AND s.active = 1
                AND (
                    s.team_member_id = " . $team_member_id . "
                    OR EXISTS (
                        SELECT 1
                        FROM {$members_table} wsm
                        WHERE wsm.deleted = 0
                        AND wsm.work_schedule_id = s.id
                        AND wsm.team_member_id = " . $team_member_id . "
                    )
                )
                ORDER BY s.id DESC";

        $row = $this->queryOrEmpty($sql)->getRow();
        return $row ?: null;
    }
}
