<?php

namespace GED\Models;

class Ged_documents_model extends GedBaseModel
{
    protected $table = 'ged_documents';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $documents_table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT d.*,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$documents_table} d
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = d.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE d.deleted_at IS NULL";

        $id = get_array_value($options, 'id');
        if ($id) {
            $sql .= " AND d.id=" . (int) $id;
        }

        $document_type_id = get_array_value($options, 'document_type_id');
        if ($document_type_id) {
            $sql .= " AND d.document_type_id=" . (int) $document_type_id;
        }

        $owner_type = trim((string) get_array_value($options, 'owner_type'));
        if ($owner_type !== '') {
            $sql .= " AND d.owner_type=" . $this->db->escape($owner_type);
        }

        $employee_id = get_array_value($options, 'employee_id');
        if ($employee_id) {
            $sql .= " AND d.employee_id=" . (int) $employee_id;
        }

        $supplier_id = get_array_value($options, 'supplier_id');
        if ($supplier_id) {
            $sql .= " AND d.supplier_id=" . (int) $supplier_id;
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $sql .= " AND d.status=" . $this->db->escape($status);
        }

        $expiration_start = trim((string) get_array_value($options, 'expiration_start'));
        if ($expiration_start !== '') {
            $sql .= " AND d.expiration_date >= " . $this->db->escape($expiration_start);
        }

        $expiration_end = trim((string) get_array_value($options, 'expiration_end'));
        if ($expiration_end !== '') {
            $sql .= " AND d.expiration_date <= " . $this->db->escape($expiration_end);
        }

        $expiration_scope = trim((string) get_array_value($options, 'expiration_scope'));
        if ($expiration_scope === 'overdue') {
            $sql .= " AND dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date < CURDATE()";
        } elseif ($expiration_scope === 'expiring_30') {
            $sql .= " AND dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        } elseif ($expiration_scope === 'expiring_7') {
            $sql .= " AND dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (d.title LIKE '%{$search}%' ESCAPE '!'"
                . " OR d.notes LIKE '%{$search}%' ESCAPE '!'"
                . " OR dt.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR s.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR e.first_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR e.last_name LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= " ORDER BY
                    CASE
                        WHEN d.expiration_date IS NULL THEN 1
                        ELSE 0
                    END,
                    d.expiration_date ASC,
                    d.title ASC";

        return $this->db->query($sql);
    }

    public function get_one_with_details($id = 0)
    {
        $row = $this->get_details(array('id' => $id))->getRow();
        return $row ?: null;
    }

    public function get_dropdown()
    {
        $result = array();
        $rows = $this->get_details()->getResult();
        foreach ($rows as $row) {
            $result[$row->id] = $row->title;
        }

        return $result;
    }

    public function get_valid_documents_suggestion($owner_type = '', $owner_id = 0)
    {
        $options = array(
            'status' => 'valid',
        );

        $owner_type = trim((string) $owner_type);
        if ($owner_type !== '') {
            $options['owner_type'] = $owner_type;
        }

        $owner_id = (int) $owner_id;
        if ($owner_type === 'employee' && $owner_id > 0) {
            $options['employee_id'] = $owner_id;
        } elseif ($owner_type === 'supplier' && $owner_id > 0) {
            $options['supplier_id'] = $owner_id;
        }

        $suggestion = array(array('id' => '', 'text' => '-'));
        foreach ($this->get_details($options)->getResult() as $row) {
            $suggestion[] = array(
                'id' => $row->id,
                'text' => $row->title,
            );
        }

        return $suggestion;
    }

    public function get_dashboard_kpis()
    {
        $table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('ged_document_types');
        $sql = "SELECT
                    COUNT(*) AS total_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date < CURDATE() THEN 1 ELSE 0 END) AS expired_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_30_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS expiring_7_documents
                FROM {$table} d
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                WHERE d.deleted_at IS NULL";

        return $this->db->query($sql)->getRow();
    }

    public function get_expiring_documents($days = 30, $limit = 5)
    {
        $days = max(1, (int) $days);
        $limit = max(1, (int) $limit);

        $documents_table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT d.*,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$documents_table} d
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = d.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE d.deleted_at IS NULL
                    AND dt.has_expiration = 1
                    AND d.expiration_date IS NOT NULL
                    AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)
                ORDER BY d.expiration_date ASC
                LIMIT {$limit}";

        return $this->db->query($sql)->getResult();
    }

    public function get_expired_documents($limit = 5)
    {
        $limit = max(1, (int) $limit);

        $documents_table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT d.*,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$documents_table} d
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = d.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE d.deleted_at IS NULL
                    AND dt.has_expiration = 1
                    AND d.expiration_date IS NOT NULL
                    AND d.expiration_date < CURDATE()
                ORDER BY d.expiration_date ASC
                LIMIT {$limit}";

        return $this->db->query($sql)->getResult();
    }

    public function get_notification_candidates($max_days = 30)
    {
        $max_days = max(1, (int) $max_days);
        $documents_table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT d.*,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$documents_table} d
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = d.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE d.deleted_at IS NULL
                    AND d.status <> 'archived'
                    AND dt.has_expiration = 1
                    AND d.expiration_date IS NOT NULL
                    AND d.expiration_date <= DATE_ADD(CURDATE(), INTERVAL {$max_days} DAY)
                ORDER BY d.expiration_date ASC, d.id ASC";

        return $this->db->query($sql)->getResult();
    }

    public function get_report_documents($options = array())
    {
        $options = $this->_normalize_report_filters($options);
        return $this->get_details($options)->getResult();
    }

    public function get_report_documents_grouped_by_employee($options = array())
    {
        $options = $this->_normalize_report_filters($options);

        $documents_table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('ged_document_types');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT
                    d.employee_id,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS employee_name,
                    COUNT(*) AS total_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date < CURDATE() THEN 1 ELSE 0 END) AS expired_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_30_documents
                FROM {$documents_table} d
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$users_table} u ON u.id = d.employee_id
                WHERE d.deleted_at IS NULL
                    AND d.owner_type='employee'";

        $sql .= $this->_build_report_where_clause($options, 'd', 'dt');
        $sql .= " GROUP BY d.employee_id, employee_name
                  ORDER BY employee_name ASC";

        return $this->db->query($sql)->getResult();
    }

    public function get_report_documents_grouped_by_supplier($options = array())
    {
        $options = $this->_normalize_report_filters($options);

        $documents_table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');

        $sql = "SELECT
                    d.supplier_id,
                    s.name AS supplier_name,
                    COUNT(*) AS total_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date < CURDATE() THEN 1 ELSE 0 END) AS expired_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_30_documents
                FROM {$documents_table} d
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = d.supplier_id
                WHERE d.deleted_at IS NULL
                    AND d.owner_type='supplier'";

        $sql .= $this->_build_report_where_clause($options, 'd', 'dt');
        $sql .= " GROUP BY d.supplier_id, supplier_name
                  ORDER BY supplier_name ASC";

        return $this->db->query($sql)->getResult();
    }

    private function _normalize_report_filters($options)
    {
        return array(
            'document_type_id' => (int) get_array_value($options, 'document_type_id'),
            'employee_id' => (int) get_array_value($options, 'employee_id'),
            'supplier_id' => (int) get_array_value($options, 'supplier_id'),
            'status' => trim((string) get_array_value($options, 'status')),
            'expiration_scope' => trim((string) get_array_value($options, 'expiration_scope')),
            'expiration_start' => trim((string) get_array_value($options, 'expiration_start')),
            'expiration_end' => trim((string) get_array_value($options, 'expiration_end')),
        );
    }

    private function _build_report_where_clause($options, $doc_alias = 'd', $type_alias = 'dt')
    {
        $sql = '';

        if (!empty($options['document_type_id'])) {
            $sql .= " AND {$doc_alias}.document_type_id=" . (int) $options['document_type_id'];
        }

        if (!empty($options['employee_id'])) {
            $sql .= " AND {$doc_alias}.employee_id=" . (int) $options['employee_id'];
        }

        if (!empty($options['supplier_id'])) {
            $sql .= " AND {$doc_alias}.supplier_id=" . (int) $options['supplier_id'];
        }

        if (!empty($options['status'])) {
            $sql .= " AND {$doc_alias}.status=" . $this->db->escape($options['status']);
        }

        if (!empty($options['expiration_start'])) {
            $sql .= " AND {$doc_alias}.expiration_date >= " . $this->db->escape($options['expiration_start']);
        }

        if (!empty($options['expiration_end'])) {
            $sql .= " AND {$doc_alias}.expiration_date <= " . $this->db->escape($options['expiration_end']);
        }

        if (!empty($options['expiration_scope'])) {
            if ($options['expiration_scope'] === 'overdue') {
                $sql .= " AND {$type_alias}.has_expiration = 1 AND {$doc_alias}.expiration_date IS NOT NULL AND {$doc_alias}.expiration_date < CURDATE()";
            } elseif ($options['expiration_scope'] === 'expiring_30') {
                $sql .= " AND {$type_alias}.has_expiration = 1 AND {$doc_alias}.expiration_date IS NOT NULL AND {$doc_alias}.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            } elseif ($options['expiration_scope'] === 'expiring_7') {
                $sql .= " AND {$type_alias}.has_expiration = 1 AND {$doc_alias}.expiration_date IS NOT NULL AND {$doc_alias}.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            }
        }

        return $sql;
    }

    public function save_document($data, $id = 0)
    {
        return $this->ci_save($data, $id);
    }
}
