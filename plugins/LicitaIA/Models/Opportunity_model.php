<?php

namespace LicitaIA\Models;

class Opportunity_model extends LicitaiaBaseModel
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

        return $this->queryOrEmpty($this->buildDetailsSql($options));
    }

    public function get_kanban_details($options = array())
    {
        $rows = $this->get_details($options)->getResult();
        $grouped = array(
            'new' => array(),
            'analyzing' => array(),
            'waiting_decision' => array(),
            'participate' => array(),
            'not_participate' => array(),
            'proposal_in_progress' => array(),
            'sent' => array(),
            'won' => array(),
            'lost' => array(),
            'canceled' => array(),
        );

        foreach ($rows as $row) {
            $status = $row->status ?: 'new';
            if (!isset($grouped[$status])) {
                $grouped[$status] = array();
            }
            $grouped[$status][] = $row;
        }

        return $grouped;
    }

    public function count_by_status()
    {
        if (!$this->hasTable()) {
            return array(
                'new' => 0,
                'analyzing' => 0,
                'waiting_decision' => 0,
                'participate' => 0,
                'not_participate' => 0,
                'proposal_in_progress' => 0,
                'sent' => 0,
                'won' => 0,
                'lost' => 0,
                'canceled' => 0,
                'total' => 0,
            );
        }

        $table = $this->db->prefixTable($this->table);
        $rows = $this->db->query("SELECT CASE WHEN status = 'qualified' THEN 'participate' WHEN status = 'ignored' THEN 'not_participate' ELSE status END AS status, COUNT(*) AS total FROM {$table} WHERE deleted = 0 GROUP BY CASE WHEN status = 'qualified' THEN 'participate' WHEN status = 'ignored' THEN 'not_participate' ELSE status END")->getResult();
        $counts = array(
            'new' => 0,
            'analyzing' => 0,
            'waiting_decision' => 0,
            'participate' => 0,
            'not_participate' => 0,
            'proposal_in_progress' => 0,
            'sent' => 0,
            'won' => 0,
            'lost' => 0,
            'canceled' => 0,
            'total' => 0,
        );

        foreach ($rows as $row) {
            $status = $row->status ?: 'new';
            if ($status === 'qualified') {
                $status = 'participate';
            } elseif ($status === 'ignored') {
                $status = 'not_participate';
            }

            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status] = (int) $row->total;
            $counts['total'] += (int) $row->total;
        }

        return $counts;
    }

    public function get_due_soon($days = 7)
    {
        if (!$this->hasTable()) {
            return array();
        }

        $days = max(1, (int) $days);
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT o.id, o.title, o.edital_number, o.submission_deadline, o.status, o.ai_status, o.created_at
                FROM {$table} o
                WHERE o.deleted = 0
                  AND o.status NOT IN ('won', 'qualified', 'lost', 'canceled', 'not_participate', 'ignored')
                  AND IFNULL(STR_TO_DATE(o.submission_deadline, '%Y-%m-%d'), STR_TO_DATE(o.submission_deadline, '%d/%m/%Y')) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
                ORDER BY IFNULL(STR_TO_DATE(o.submission_deadline, '%Y-%m-%d'), STR_TO_DATE(o.submission_deadline, '%d/%m/%Y')) ASC";

        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_recent_opportunities($limit = 5)
    {
        if (!$this->hasTable()) {
            return array();
        }

        $limit = max(1, (int) $limit);
        $sql = $this->buildDetailsSql(array()) . ' LIMIT ' . $limit;
        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_imported_today()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT o.id, o.title, o.public_agency, o.notice_number, o.process_number, o.modality, o.object_description, o.city, o.state, o.estimated_value, o.opening_date, o.submission_deadline, o.source_url, o.status, o.ai_status, o.responsible_user_id, o.created_at
                FROM {$table} o
                WHERE o.deleted = 0
                  AND DATE(o.created_at) = CURDATE()
                ORDER BY o.created_at DESC";

        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_opening_due_soon($days = 7)
    {
        if (!$this->hasTable()) {
            return array();
        }

        $days = max(1, (int) $days);
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT o.id, o.title, o.public_agency, o.notice_number, o.process_number, o.modality, o.object_description, o.city, o.state, o.estimated_value, o.opening_date, o.submission_deadline, o.source_url, o.status, o.ai_status, o.responsible_user_id, o.created_at
                FROM {$table} o
                WHERE o.deleted = 0
                  AND o.status NOT IN ('won', 'lost', 'canceled', 'not_participate', 'ignored')
                  AND COALESCE(STR_TO_DATE(o.opening_date, '%Y-%m-%d'), STR_TO_DATE(o.opening_date, '%d/%m/%Y')) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
                ORDER BY COALESCE(STR_TO_DATE(o.opening_date, '%Y-%m-%d'), STR_TO_DATE(o.opening_date, '%d/%m/%Y')) ASC";

        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_submission_due_soon($days = 7)
    {
        if (!$this->hasTable()) {
            return array();
        }

        $days = max(1, (int) $days);
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT o.id, o.title, o.public_agency, o.notice_number, o.process_number, o.modality, o.object_description, o.city, o.state, o.estimated_value, o.opening_date, o.submission_deadline, o.source_url, o.status, o.ai_status, o.responsible_user_id, o.created_at
                FROM {$table} o
                WHERE o.deleted = 0
                  AND o.status NOT IN ('won', 'lost', 'canceled', 'not_participate', 'ignored')
                  AND COALESCE(STR_TO_DATE(o.submission_deadline, '%Y-%m-%d'), STR_TO_DATE(o.submission_deadline, '%d/%m/%Y')) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
                ORDER BY COALESCE(STR_TO_DATE(o.submission_deadline, '%Y-%m-%d'), STR_TO_DATE(o.submission_deadline, '%d/%m/%Y')) ASC";

        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_without_responsible()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT o.id, o.title, o.public_agency, o.notice_number, o.process_number, o.modality, o.object_description, o.city, o.state, o.estimated_value, o.opening_date, o.submission_deadline, o.source_url, o.status, o.ai_status, o.responsible_user_id, o.created_at
                FROM {$table} o
                WHERE o.deleted = 0
                  AND (o.responsible_user_id IS NULL OR o.responsible_user_id = 0)
                ORDER BY o.created_at DESC";

        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_without_ai_analysis()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT o.id, o.title, o.public_agency, o.notice_number, o.process_number, o.modality, o.object_description, o.city, o.state, o.estimated_value, o.opening_date, o.submission_deadline, o.source_url, o.status, o.ai_status, o.responsible_user_id, o.created_at
                FROM {$table} o
                WHERE o.deleted = 0
                  AND (o.ai_status IS NULL OR o.ai_status <> 'completed' OR o.ai_summary IS NULL OR o.ai_summary = '')
                ORDER BY o.created_at DESC";

        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_with_pending_checklist()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $checklist_table = $this->db->prefixTable('licitaia_opportunity_checklist');
        $sql = "SELECT DISTINCT o.id, o.title, o.public_agency, o.notice_number, o.process_number, o.modality, o.object_description, o.city, o.state, o.estimated_value, o.opening_date, o.submission_deadline, o.source_url, o.status, o.ai_status, o.responsible_user_id, o.created_at
                FROM {$table} o
                INNER JOIN {$checklist_table} c ON c.opportunity_id = o.id AND c.deleted = 0 AND c.status IN ('pending', 'open')
                WHERE o.deleted = 0
                ORDER BY o.created_at DESC";

        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_participate_without_proposal()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT o.id, o.title, o.public_agency, o.notice_number, o.process_number, o.modality, o.object_description, o.city, o.state, o.estimated_value, o.opening_date, o.submission_deadline, o.source_url, o.status, o.ai_status, o.responsible_user_id, o.created_at
                FROM {$table} o
                WHERE o.deleted = 0
                  AND o.status = 'participate'
                ORDER BY o.created_at DESC";

        $result = $this->db->query($sql);
        return $result ? $result->getResult() : array();
    }

    public function get_dashboard_summary()
    {
        if (!$this->hasTable()) {
        return (object) array(
                'total' => 0,
                'new' => 0,
                'analyzing' => 0,
                'participate' => 0,
                'not_participate' => 0,
                'won' => 0,
                'lost' => 0,
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
                    SUM(CASE WHEN status IN ('participate', 'qualified') THEN 1 ELSE 0 END) AS participate,
                    SUM(CASE WHEN status IN ('not_participate', 'ignored') THEN 1 ELSE 0 END) AS not_participate,
                    SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) AS won,
                    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) AS lost,
                    SUM(CASE WHEN status IN ('participate', 'qualified') THEN 1 ELSE 0 END) AS qualified,
                    (SELECT COUNT(*) FROM {$sources_table} s WHERE s.deleted = 0) AS sources
                FROM {$table} o
                WHERE o.deleted = 0";

        $row = $this->db->query($sql)->getRow();
        return $row ?: (object) array('total' => 0, 'new_count' => 0, 'analyzing' => 0, 'participate' => 0, 'not_participate' => 0, 'won' => 0, 'lost' => 0, 'qualified' => 0, 'sources' => 0);
    }

    public function get_active_dropdown($include_blank = true)
    {
        $dropdown = array();
        if ($include_blank) {
            $dropdown[''] = '-';
        }

        if (!$this->hasTable()) {
            return $dropdown;
        }

        foreach ($this->get_all_where(array('deleted' => 0), 1000, 0, 'title', 'id, title')->getResult() as $row) {
            $dropdown[$row->id] = $row->title;
        }

        return $dropdown;
    }

    public function update_ai_result($id, $data)
    {
        $id = (int) $id;
        if (!$id || !$this->hasTable()) {
            return false;
        }

        $payload = is_array($data) ? $data : array();
        $update = array(
            'ai_status' => trim((string) (get_array_value($payload, 'ai_status') ?: 'completed')),
            'ai_result_json' => $this->normalizePayload(get_array_value($payload, 'ai_result_json', get_array_value($payload, 'result_json', array()))),
            'ai_summary' => trim((string) get_array_value($payload, 'ai_summary', get_array_value($payload, 'summary', ''))),
            'ai_risks' => $this->normalizePayload(get_array_value($payload, 'ai_risks', array())),
            'ai_requirements' => $this->normalizePayload(get_array_value($payload, 'ai_requirements', array())),
            'ai_recommendation' => trim((string) get_array_value($payload, 'ai_recommendation', get_array_value($payload, 'recommendation_text', ''))),
            'technical_score' => (float) get_array_value($payload, 'technical_score', 0),
            'risk_level' => trim((string) get_array_value($payload, 'risk_level', '')),
            'recommendation' => trim((string) get_array_value($payload, 'recommendation', get_array_value($payload, 'recommendation_value', ''))),
            'ai_analyzed_at' => get_array_value($payload, 'ai_analyzed_at', get_my_local_time()),
            'updated_at' => get_my_local_time(),
        );

        if (array_key_exists('status', $payload) && trim((string) $payload['status']) !== '') {
            $update['status'] = trim((string) $payload['status']);
        }

        return $this->ci_save($update, $id);
    }

    public function update_status($id, $status, $responsible_user_id = null)
    {
        $id = (int) $id;
        $status = trim((string) $status);
        if (!$id || !$status || !$this->hasTable()) {
            return false;
        }

        $update = array(
            'status' => $status,
            'updated_at' => get_my_local_time(),
        );

        if ($responsible_user_id !== null) {
            $update['responsible_user_id'] = (int) $responsible_user_id ?: null;
        }

        return $this->ci_save($update, $id);
    }

    public function find_duplicate($notice_number = '', $public_agency = '', $source_url = '')
    {
        if (!$this->hasTable()) {
            return null;
        }

        $table = $this->db->prefixTable($this->table);
        $builder = $this->db->table($table)->where('deleted', 0);

        $notice_number = trim((string) $notice_number);
        $public_agency = trim((string) $public_agency);
        $source_url = trim((string) $source_url);

        $conditions = array();
        if ($source_url !== '') {
            $conditions[] = array('original_link' => $source_url);
            $conditions[] = array('document_url' => $source_url);
            $conditions[] = array('source_url' => $source_url);
        }

        if ($notice_number !== '' && $public_agency !== '') {
            $conditions[] = array(
                'edital_number' => $notice_number,
                'public_body' => $public_agency,
            );
            $conditions[] = array(
                'notice_number' => $notice_number,
                'public_agency' => $public_agency,
            );
        }

        if (!$conditions) {
            return null;
        }

        $builder->groupStart();
        foreach ($conditions as $index => $condition) {
            if ($index === 0) {
                $builder->groupStart();
                foreach ($condition as $field => $value) {
                    $builder->where($field, $value);
                }
                $builder->groupEnd();
                continue;
            }

            $builder->orGroupStart();
            foreach ($condition as $field => $value) {
                $builder->where($field, $value);
            }
            $builder->groupEnd();
        }
        $builder->groupEnd();

        $row = $builder->select('id, title, public_body, edital_number, process_number, original_link, document_url')
            ->limit(1)
            ->get()
            ->getRow();

        return $row ?: null;
    }

    private function normalizePayload($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function buildDetailsSql($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $sources_table = $this->db->prefixTable('licitaia_sources');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT o.id, o.title, o.description, o.public_body, o.edital_number, o.process_number, o.modality, o.object,
                       o.public_agency, o.notice_number, o.object_description, o.source_url,
                       o.submission_deadline, o.publication_date, o.opening_date,
                       CASE WHEN o.status = 'qualified' THEN 'participate' WHEN o.status = 'ignored' THEN 'not_participate' ELSE o.status END AS status,
                       o.ai_status, o.source_id, o.responsible_user_id, o.jurisdiction, o.city, o.state, o.estimated_value, o.document_url, o.original_link,
                       o.ai_result_json, o.ai_summary, o.ai_risks, o.ai_requirements, o.ai_recommendation, o.technical_score, o.risk_level, o.recommendation, o.ai_analyzed_at, o.last_search_at, o.last_search_by,
                       o.notes, o.created_by, o.created_at, o.updated_at, o.deleted,
                       s.name AS source_name,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name,
                       CONCAT(TRIM(COALESCE(r.first_name, '')), ' ', TRIM(COALESCE(r.last_name, ''))) AS responsible_name
                FROM {$table} o
                LEFT JOIN {$sources_table} s ON s.id = o.source_id
                LEFT JOIN {$users_table} u ON u.id = o.created_by
                LEFT JOIN {$users_table} r ON r.id = o.responsible_user_id
                WHERE o.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id > 0) {
            $sql .= ' AND o.id = ' . $id;
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            if ($status === 'won') {
                $sql .= " AND o.status = 'won'";
            } elseif ($status === 'participate') {
                $sql .= " AND o.status IN ('participate', 'qualified')";
            } elseif ($status === 'not_participate') {
                $sql .= " AND o.status IN ('not_participate', 'ignored')";
            } else {
                $sql .= ' AND o.status = ' . $this->db->escape($status);
            }
        }

        $ai_status = trim((string) get_array_value($options, 'ai_status'));
        if ($ai_status !== '') {
            $sql .= ' AND o.ai_status = ' . $this->db->escape($ai_status);
        }

        $source_id = (int) get_array_value($options, 'source_id');
        if ($source_id > 0) {
            $sql .= ' AND o.source_id = ' . $source_id;
        }

        $responsible_user_id = (int) get_array_value($options, 'responsible_user_id');
        if ($responsible_user_id > 0) {
            $sql .= ' AND o.responsible_user_id = ' . $responsible_user_id;
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (o.title LIKE '%{$search}%' ESCAPE '!'"
                . " OR o.description LIKE '%{$search}%' ESCAPE '!'"
                . " OR o.public_body LIKE '%{$search}%' ESCAPE '!'"
                . " OR o.edital_number LIKE '%{$search}%' ESCAPE '!'"
                . " OR o.process_number LIKE '%{$search}%' ESCAPE '!'"
                . " OR o.modality LIKE '%{$search}%' ESCAPE '!'"
                . " OR o.object LIKE '%{$search}%' ESCAPE '!'"
                . " OR o.city LIKE '%{$search}%' ESCAPE '!'"
                . " OR o.state LIKE '%{$search}%' ESCAPE '!')";
        }

        $date_from = trim((string) get_array_value($options, 'date_from'));
        if ($date_from !== '') {
            $sql .= ' AND o.submission_deadline >= ' . $this->db->escape($date_from);
        }

        $date_to = trim((string) get_array_value($options, 'date_to'));
        if ($date_to !== '') {
            $sql .= ' AND o.submission_deadline <= ' . $this->db->escape($date_to);
        }

        return $sql . ' ORDER BY o.id DESC';
    }
}
