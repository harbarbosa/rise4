<?php

namespace PontoRH\Models;

class PontoRh_monthly_summaries_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_monthly_summaries';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT m.*,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name
                FROM {$table} m
                LEFT JOIN {$users_table} u ON u.id = m.team_member_id
                WHERE m.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND m.id = ' . $id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND m.team_member_id = ' . $team_member_id;
        }

        $summary_year = (int) get_array_value($options, 'summary_year');
        if ($summary_year) {
            $sql .= ' AND m.summary_year = ' . $summary_year;
        }

        $summary_month = (int) get_array_value($options, 'summary_month');
        if ($summary_month) {
            $sql .= ' AND m.summary_month = ' . $summary_month;
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $sql .= ' AND m.status = ' . $this->db->escape($status);
        }

        $sql .= ' ORDER BY m.summary_year DESC, m.summary_month DESC, m.id DESC';
        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id = 0)
    {
        $result = $this->get_details(array('id' => $id));
        $row = $result ? $result->getRow() : null;
        return $row ?: null;
    }

    public function get_by_member_month($team_member_id, $summary_year, $summary_month)
    {
        $result = $this->get_details(array(
            'team_member_id' => (int) $team_member_id,
            'summary_year' => (int) $summary_year,
            'summary_month' => (int) $summary_month,
        ));
        return $result ? $result->getRow() : null;
    }

    public function upsert_summary(array $data)
    {
        $team_member_id = (int) get_array_value($data, 'team_member_id');
        $summary_year = (int) get_array_value($data, 'summary_year');
        $summary_month = (int) get_array_value($data, 'summary_month');

        if (!$team_member_id || !$summary_year || !$summary_month) {
            return false;
        }

        $existing = $this->get_by_member_month($team_member_id, $summary_year, $summary_month);
        if ($existing && !empty($existing->id)) {
            return $this->ci_save($data, (int) $existing->id);
        }

        return $this->ci_save($data);
    }
}
