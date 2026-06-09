<?php

namespace PontoRH\Models;

class PontoRh_adjustments_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_adjustment_requests';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $adjustments_table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT a.*,
                    a.request_date AS adjustment_date,
                    a.requested_time AS adjustment_time,
                    a.adjustment_type AS type,
                    a.reason AS justification,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name,
                    CONCAT(TRIM(COALESCE(cu.first_name, '')), ' ', TRIM(COALESCE(cu.last_name, ''))) AS creator_name,
                    CONCAT(TRIM(COALESCE(ru.first_name, '')), ' ', TRIM(COALESCE(ru.last_name, ''))) AS reviewer_name
                FROM {$adjustments_table} a
                LEFT JOIN {$users_table} u ON u.id = a.team_member_id
                LEFT JOIN {$users_table} cu ON cu.id = a.created_by
                LEFT JOIN {$users_table} ru ON ru.id = a.reviewed_by
                WHERE a.deleted = 0";

        $sql .= $this->getScopeWhere($options, 'a');

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND a.id = ' . $id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND a.team_member_id = ' . $team_member_id;
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $sql .= ' AND a.status = ' . $this->db->escape($status);
        }

        $adjustment_type = trim((string) get_array_value($options, 'adjustment_type'));
        if ($adjustment_type !== '') {
            $sql .= ' AND a.adjustment_type = ' . $this->db->escape($adjustment_type);
        }

        $date_from = trim((string) get_array_value($options, 'date_from'));
        if ($date_from !== '') {
            $sql .= ' AND a.request_date >= ' . $this->db->escape($date_from);
        }

        $date_to = trim((string) get_array_value($options, 'date_to'));
        if ($date_to !== '') {
            $sql .= ' AND a.request_date <= ' . $this->db->escape($date_to);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (a.reason LIKE '%{$search}%' ESCAPE '!'"
                . " OR a.adjustment_type LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.first_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR u.last_name LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= ' ORDER BY a.request_date DESC, a.requested_time DESC, a.id DESC';
        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id = 0, $options = array())
    {
        $options['id'] = $id;
        $result = $this->get_details($options);
        $row = $result ? $result->getRow() : null;
        return $row ?: null;
    }

    public function get_pending_count($options = array())
    {
        if (!$this->hasTable()) {
            return 0;
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT COUNT(*) AS total FROM {$table} a WHERE a.deleted = 0" . $this->getScopeWhere($options, 'a') . " AND a.status = 'pending'";
        $row = $this->db->query($sql)->getRow();
        return (int) ($row->total ?? 0);
    }

    private function getScopeWhere($options = array(), $alias = 'a')
    {
        $scope = get_array_value($options, 'scope');
        $current_user_id = (int) get_array_value($options, 'current_user_id');
        $team_member_ids = get_array_value($options, 'team_member_ids');
        $team_member_id = (int) get_array_value($options, 'team_member_id');

        if ($scope === 'all' || !$scope) {
            return '';
        }

        if ($scope === 'own') {
            return $current_user_id ? ' AND ' . $alias . '.team_member_id = ' . $current_user_id : '';
        }

        if (is_array($team_member_ids)) {
            $team_member_ids = array_values(array_filter(array_map('intval', $team_member_ids)));
        } else {
            $team_member_ids = array();
        }

        if ($team_member_id) {
            if ($team_member_ids && in_array($team_member_id, $team_member_ids, true)) {
                return ' AND ' . $alias . '.team_member_id = ' . $team_member_id;
            }

            return ' AND ' . $alias . '.team_member_id = 0';
        }

        if ($team_member_ids) {
            return ' AND ' . $alias . '.team_member_id IN (' . implode(',', $team_member_ids) . ')';
        }

        return $current_user_id ? ' AND ' . $alias . '.team_member_id = ' . $current_user_id : '';
    }
}
