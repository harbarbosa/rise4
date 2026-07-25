<?php

namespace LicitaIA\Models;

class Licitaia_reports_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_reports';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $reports_table = $this->db->prefixTable($this->table);
        $opportunities_table = $this->db->prefixTable('licitaia_opportunities');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT r.id, r.opportunity_id, r.report_type, r.title, r.file_path, r.file_name, r.generated_at, r.created_by, r.created_at, r.updated_at, r.deleted,
                       o.title AS opportunity_title,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name
                FROM {$reports_table} r
                LEFT JOIN {$opportunities_table} o ON o.id = r.opportunity_id
                LEFT JOIN {$users_table} u ON u.id = r.created_by
                WHERE r.deleted = 0
                ORDER BY r.id DESC";

        return $this->queryOrEmpty($sql);
    }

    public function get_latest_by_opportunity($opportunity_id, $report_type = 'technical_opinion')
    {
        $opportunity_id = (int) $opportunity_id;
        if (!$opportunity_id || !$this->hasTable()) {
            return null;
        }

        $table = $this->db->prefixTable($this->table);
        $row = $this->db->table($table)
            ->where('deleted', 0)
            ->where('opportunity_id', $opportunity_id)
            ->where('report_type', trim((string) $report_type) ?: 'technical_opinion')
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRow();

        return $row ?: null;
    }

    public function save_report_file($opportunity_id, array $data, $report_id = 0)
    {
        $opportunity_id = (int) $opportunity_id;
        if (!$opportunity_id || !$this->hasTable()) {
            return false;
        }

        $payload = array(
            'opportunity_id' => $opportunity_id,
            'report_type' => trim((string) get_array_value($data, 'report_type', 'technical_opinion')) ?: 'technical_opinion',
            'title' => trim((string) get_array_value($data, 'title', '')),
            'file_path' => trim((string) get_array_value($data, 'file_path', '')),
            'file_name' => trim((string) get_array_value($data, 'file_name', '')),
            'generated_at' => get_array_value($data, 'generated_at', get_my_local_time()),
            'created_by' => (int) get_array_value($data, 'created_by', 0) ?: null,
            'created_at' => get_array_value($data, 'created_at', get_my_local_time()),
            'updated_at' => get_array_value($data, 'updated_at', get_my_local_time()),
            'deleted' => 0,
        );

        $report_id = (int) $report_id;
        if ($report_id > 0) {
            return $this->ci_save($payload, $report_id);
        }

        $existing = $this->get_latest_by_opportunity($opportunity_id, $payload['report_type']);
        if ($existing && !empty($existing->id)) {
            return $this->ci_save($payload, (int) $existing->id);
        }

        return $this->ci_save($payload, 0);
    }
}
