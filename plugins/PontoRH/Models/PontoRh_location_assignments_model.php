<?php

namespace PontoRH\Models;

class PontoRh_location_assignments_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_location_assignments';

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
        $locations_table = $this->db->prefixTable('pontorh_locations');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT a.*,
                    l.name AS location_name,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name,
                    CONCAT(TRIM(COALESCE(cu.first_name, '')), ' ', TRIM(COALESCE(cu.last_name, ''))) AS creator_name
                FROM {$assignments_table} a
                LEFT JOIN {$locations_table} l ON l.id = a.location_id
                LEFT JOIN {$users_table} u ON u.id = a.team_member_id
                LEFT JOIN {$users_table} cu ON cu.id = a.created_by
                WHERE a.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND a.id = ' . $id;
        }

        $location_id = (int) get_array_value($options, 'location_id');
        if ($location_id) {
            $sql .= ' AND a.location_id = ' . $location_id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND a.team_member_id = ' . $team_member_id;
        }

        $active = get_array_value($options, 'active');
        if ($active !== null && $active !== '') {
            $sql .= ' AND a.active = ' . (int) $active;
        }

        $date = trim((string) get_array_value($options, 'date'));
        if ($date !== '') {
            $sql .= " AND a.week_start <= " . $this->db->escape($date) . " AND a.week_end >= " . $this->db->escape($date);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (l.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.first_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.last_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR a.notes LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= ' ORDER BY a.active DESC, a.week_start DESC, a.id DESC';
        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id = 0)
    {
        $row = $this->get_details(array('id' => (int) $id))->getRow();
        return $row ?: null;
    }

    public function get_location_ids_for_member(int $team_member_id, ?string $date = null): array
    {
        if (!$this->hasTable() || !$team_member_id) {
            return array();
        }

        $date = $date ?: date('Y-m-d');
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT a.location_id
                FROM {$table} a
                WHERE a.deleted = 0
                AND a.active = 1
                AND a.team_member_id = " . $team_member_id . "
                AND a.week_start <= " . $this->db->escape($date) . "
                AND a.week_end >= " . $this->db->escape($date) . "
                ORDER BY a.id DESC";

        $rows = $this->queryOrEmpty($sql)->getResult();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int) $row->location_id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function get_active_assignments_for_location(int $location_id)
    {
        return $this->get_details(array(
            'location_id' => $location_id,
            'active' => 1,
        ));
    }

    public function sync_assignments(int $location_id, array $team_member_ids, string $week_start, string $week_end, int $created_by, $active = 1, ?string $notes = null): bool
    {
        if (!$this->hasTable() || !$location_id) {
            return false;
        }

        $team_member_ids = array_values(array_unique(array_filter(array_map('intval', $team_member_ids))));
        if (!$team_member_ids) {
            return false;
        }

        $table = $this->db->prefixTable($this->table);
        $this->db->table($table)
            ->where('location_id', $location_id)
            ->where('week_start', $week_start)
            ->where('week_end', $week_end)
            ->update(array(
                'deleted' => 1,
                'updated_at' => get_current_utc_time(),
            ));

        foreach ($team_member_ids as $team_member_id) {
            $data = array(
                'location_id' => $location_id,
                'team_member_id' => $team_member_id,
                'week_start' => $week_start,
                'week_end' => $week_end,
                'active' => (int) $active ? 1 : 0,
                'notes' => $notes,
                'created_by' => $created_by,
                'created_at' => get_current_utc_time(),
                'updated_at' => get_current_utc_time(),
                'deleted' => 0,
            );

            $this->ci_save($data);
        }

        return true;
    }

    public function delete_assignments_by_location(int $location_id): bool
    {
        if (!$this->hasTable() || !$location_id) {
            return false;
        }

        return (bool) $this->db->table($this->db->prefixTable($this->table))
            ->where('location_id', $location_id)
            ->update(array(
                'deleted' => 1,
                'updated_at' => get_current_utc_time(),
            ));
    }
}
