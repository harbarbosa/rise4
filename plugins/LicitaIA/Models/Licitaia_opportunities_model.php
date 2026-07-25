<?php

namespace LicitaIA\Models;

class Licitaia_opportunities_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_opportunities';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $opportunities_table = $this->db->prefixTable($this->table);
        $sources_table = $this->db->prefixTable('licitaia_sources');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT o.id, o.title, o.description, o.edital_number, o.submission_deadline, o.publication_date, o.opening_date,
                       o.status, o.ai_status, o.source_id, o.jurisdiction, o.city, o.state, o.estimated_value, o.document_url,
                       o.notes, o.created_by, o.created_at, o.updated_at, o.deleted,
                       s.name AS source_name,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name
                FROM {$opportunities_table} o
                LEFT JOIN {$sources_table} s ON s.id = o.source_id
                LEFT JOIN {$users_table} u ON u.id = o.created_by
                WHERE o.deleted = 0";

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $sql .= ' AND o.status = ' . $this->db->escape($status);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (o.title LIKE '%{$search}%' ESCAPE '!' OR o.edital_number LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= ' ORDER BY o.id DESC';

        return $this->queryOrEmpty($sql);
    }

    public function get_recent_opportunities($limit = 5)
    {
        if (!$this->hasTable()) {
            return array();
        }

        $limit = max(1, (int) $limit);
        $result = $this->db->query($this->get_details_sql() . ' LIMIT ' . $limit);
        return $result ? $result->getResult() : array();
    }

    public function get_dashboard_summary()
    {
        if (!$this->hasTable()) {
            return (object) array(
                'total' => 0,
                'new' => 0,
                'analyzing' => 0,
                'qualified' => 0,
                'sources' => 0,
            );
        }

        $table = $this->db->prefixTable($this->table);
        $sources_table = $this->db->prefixTable('licitaia_sources');
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
                    SUM(CASE WHEN status = 'analyzing' THEN 1 ELSE 0 END) AS analyzing,
                    SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) AS qualified,
                    (SELECT COUNT(*) FROM {$sources_table} s WHERE s.deleted = 0) AS sources
                FROM {$table} o
                WHERE o.deleted = 0";

        $row = $this->db->query($sql)->getRow();
        return $row ?: (object) array('total' => 0, 'new_count' => 0, 'analyzing' => 0, 'qualified' => 0, 'sources' => 0);
    }

    public function get_active_dropdown($include_blank = true)
    {
        $dropdown = array();
        if ($include_blank) {
            $dropdown[''] = '-';
        }

        foreach ($this->get_all_where(array('deleted' => 0), 1000, 0, 'title', 'id, title')->getResult() as $row) {
            $dropdown[$row->id] = $row->title;
        }

        return $dropdown;
    }

    private function get_details_sql()
    {
        $opportunities_table = $this->db->prefixTable($this->table);
        $sources_table = $this->db->prefixTable('licitaia_sources');
        $users_table = $this->db->prefixTable('users');

        return "SELECT o.id, o.title, o.description, o.edital_number, o.submission_deadline, o.publication_date, o.opening_date,
                       o.status, o.ai_status, o.source_id, o.jurisdiction, o.city, o.state, o.estimated_value, o.document_url,
                       o.notes, o.created_by, o.created_at, o.updated_at, o.deleted,
                       s.name AS source_name,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name
                FROM {$opportunities_table} o
                LEFT JOIN {$sources_table} s ON s.id = o.source_id
                LEFT JOIN {$users_table} u ON u.id = o.created_by
                WHERE o.deleted = 0
                ORDER BY o.id DESC";
    }
}
