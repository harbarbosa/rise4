<?php

namespace GED\Models;

class Ged_document_submissions_model extends GedBaseModel
{
    protected $table = 'ged_document_submissions';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $submissions_table = $this->db->prefixTable($this->table);
        $documents_table = $this->db->prefixTable('ged_documents');
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT ds.*,
                    d.title AS document_title,
                    d.expiration_date AS document_expiration_date,
                    d.status AS document_status,
                    d.owner_type AS document_owner_type,
                    d.owner_id AS document_owner_id,
                    d.employee_id AS document_employee_id,
                    d.supplier_id AS document_supplier_id,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$submissions_table} ds
                LEFT JOIN {$documents_table} d ON d.id = ds.document_id
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = ds.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE ds.deleted_at IS NULL";

        $id = get_array_value($options, 'id');
        if ($id) {
            $sql .= " AND ds.id=" . (int) $id;
        }

        $document_id = get_array_value($options, 'document_id');
        if ($document_id) {
            $sql .= " AND ds.document_id=" . (int) $document_id;
        }

        $supplier_id = get_array_value($options, 'supplier_id');
        if ($supplier_id) {
            $sql .= " AND ds.supplier_id=" . (int) $supplier_id;
        }

        $portal_status = trim((string) get_array_value($options, 'portal_status'));
        if ($portal_status !== '') {
            $sql .= " AND ds.portal_status=" . $this->db->escape($portal_status);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (ds.portal_reference LIKE '%{$search}%' ESCAPE '!'"
                . " OR ds.notes LIKE '%{$search}%' ESCAPE '!'"
                . " OR d.title LIKE '%{$search}%' ESCAPE '!'"
                . " OR dt.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR s.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR e.first_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR e.last_name LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= " ORDER BY ds.submitted_at DESC, ds.id DESC";

        return $this->db->query($sql);
    }

    public function get_one_with_details($id = 0)
    {
        $row = $this->get_details(array('id' => $id))->getRow();
        return $row ?: null;
    }

    public function save_submission($data, $id = 0)
    {
        return $this->ci_save($data, $id);
    }

    public function portal_status_options()
    {
        return array('pending', 'submitted', 'approved', 'rejected', 'expired');
    }

    public function get_dashboard_summary()
    {
        $submissions_table = $this->db->prefixTable($this->table);
        $documents_table = $this->db->prefixTable('ged_documents');
        $types_table = $this->db->prefixTable('ged_document_types');

        $sql = "SELECT
                    SUM(CASE WHEN ds.portal_status='pending' THEN 1 ELSE 0 END) AS pending_submissions,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date < CURDATE() THEN 1 ELSE 0 END) AS submissions_with_expired_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS submissions_with_expiring_documents
                FROM {$submissions_table} ds
                LEFT JOIN {$documents_table} d ON d.id = ds.document_id
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                WHERE ds.deleted_at IS NULL";

        return $this->db->query($sql)->getRow();
    }

    public function get_pending_submissions($limit = 5)
    {
        $limit = max(1, (int) $limit);
        $submissions_table = $this->db->prefixTable($this->table);
        $documents_table = $this->db->prefixTable('ged_documents');
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT ds.*,
                    d.title AS document_title,
                    d.expiration_date AS document_expiration_date,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$submissions_table} ds
                LEFT JOIN {$documents_table} d ON d.id = ds.document_id
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = ds.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE ds.deleted_at IS NULL
                    AND ds.portal_status='pending'
                ORDER BY ds.submitted_at DESC, ds.id DESC
                LIMIT {$limit}";

        return $this->db->query($sql)->getResult();
    }

    public function get_submissions_with_expired_documents($limit = 5)
    {
        $limit = max(1, (int) $limit);
        $submissions_table = $this->db->prefixTable($this->table);
        $documents_table = $this->db->prefixTable('ged_documents');
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT ds.*,
                    d.title AS document_title,
                    d.expiration_date AS document_expiration_date,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$submissions_table} ds
                LEFT JOIN {$documents_table} d ON d.id = ds.document_id
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = ds.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE ds.deleted_at IS NULL
                    AND dt.has_expiration = 1
                    AND d.expiration_date IS NOT NULL
                    AND d.expiration_date < CURDATE()
                ORDER BY d.expiration_date ASC, ds.submitted_at DESC
                LIMIT {$limit}";

        return $this->db->query($sql)->getResult();
    }

    public function get_notification_candidates($max_days = 30)
    {
        $max_days = max(1, (int) $max_days);
        $submissions_table = $this->db->prefixTable($this->table);
        $documents_table = $this->db->prefixTable('ged_documents');
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT ds.*,
                    d.title AS document_title,
                    d.expiration_date AS document_expiration_date,
                    d.status AS document_status,
                    d.created_by AS document_created_by,
                    d.updated_by AS document_updated_by,
                    d.employee_id AS document_employee_id,
                    d.supplier_id AS document_supplier_id,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$submissions_table} ds
                LEFT JOIN {$documents_table} d ON d.id = ds.document_id
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = ds.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE ds.deleted_at IS NULL
                    AND d.deleted_at IS NULL
                    AND d.status <> 'archived'
                    AND dt.has_expiration = 1
                    AND d.expiration_date IS NOT NULL
                    AND d.expiration_date <= DATE_ADD(CURDATE(), INTERVAL {$max_days} DAY)
                ORDER BY d.expiration_date ASC, ds.submitted_at DESC, ds.id ASC";

        return $this->db->query($sql)->getResult();
    }

    public function get_report_submissions($options = array())
    {
        $options = $this->_normalize_report_filters($options);

        $submissions_table = $this->db->prefixTable($this->table);
        $documents_table = $this->db->prefixTable('ged_documents');
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT ds.*,
                    d.title AS document_title,
                    d.expiration_date AS document_expiration_date,
                    d.status AS document_status,
                    d.employee_id AS document_employee_id,
                    dt.name AS document_type_name,
                    dt.has_expiration AS document_type_has_expiration,
                    s.name AS supplier_name,
                    CONCAT(TRIM(COALESCE(e.first_name, '')), ' ', TRIM(COALESCE(e.last_name, ''))) AS employee_name
                FROM {$submissions_table} ds
                LEFT JOIN {$documents_table} d ON d.id = ds.document_id
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = ds.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE ds.deleted_at IS NULL";

        $sql .= $this->_build_report_where_clause($options, 'ds', 'd', 'dt');
        $sql .= " ORDER BY ds.submitted_at DESC, ds.id DESC";

        return $this->db->query($sql)->getResult();
    }

    public function get_report_pending_portal_submissions($options = array())
    {
        $options = $this->_normalize_report_filters($options);
        $options['portal_status'] = 'pending';
        return $this->get_report_submissions($options);
    }

    public function get_report_submissions_with_expired_documents($options = array())
    {
        $options = $this->_normalize_report_filters($options);
        $options['document_status'] = 'expired';
        return $this->get_report_submissions($options);
    }

    public function get_report_submissions_summary($options = array())
    {
        $options = $this->_normalize_report_filters($options);

        $submissions_table = $this->db->prefixTable($this->table);
        $documents_table = $this->db->prefixTable('ged_documents');
        $types_table = $this->db->prefixTable('ged_document_types');
        $suppliers_table = $this->db->prefixTable('ged_suppliers');
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT
                    COUNT(*) AS total_submissions,
                    SUM(CASE WHEN ds.portal_status='pending' THEN 1 ELSE 0 END) AS pending_submissions,
                    SUM(CASE WHEN ds.portal_status='submitted' THEN 1 ELSE 0 END) AS submitted_submissions,
                    SUM(CASE WHEN ds.portal_status='approved' THEN 1 ELSE 0 END) AS approved_submissions,
                    SUM(CASE WHEN ds.portal_status='rejected' THEN 1 ELSE 0 END) AS rejected_submissions,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date < CURDATE() THEN 1 ELSE 0 END) AS submissions_with_expired_documents,
                    SUM(CASE WHEN dt.has_expiration = 1 AND d.expiration_date IS NOT NULL AND d.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS submissions_with_expiring_documents
                FROM {$submissions_table} ds
                LEFT JOIN {$documents_table} d ON d.id = ds.document_id
                LEFT JOIN {$types_table} dt ON dt.id = d.document_type_id
                LEFT JOIN {$suppliers_table} s ON s.id = ds.supplier_id
                LEFT JOIN {$users_table} e ON e.id = d.employee_id
                WHERE ds.deleted_at IS NULL";

        $sql .= $this->_build_report_where_clause($options, 'ds', 'd', 'dt');

        return $this->db->query($sql)->getRow();
    }

    private function _normalize_report_filters($options)
    {
        return array(
            'document_type_id' => (int) get_array_value($options, 'document_type_id'),
            'employee_id' => (int) get_array_value($options, 'employee_id'),
            'supplier_id' => (int) get_array_value($options, 'supplier_id'),
            'document_status' => trim((string) get_array_value($options, 'document_status')),
            'portal_status' => trim((string) get_array_value($options, 'portal_status')),
            'expiration_start' => trim((string) get_array_value($options, 'expiration_start')),
            'expiration_end' => trim((string) get_array_value($options, 'expiration_end')),
        );
    }

    private function _build_report_where_clause($options, $submission_alias = 'ds', $document_alias = 'd', $type_alias = 'dt')
    {
        $sql = '';

        if (!empty($options['document_type_id'])) {
            $sql .= " AND {$document_alias}.document_type_id=" . (int) $options['document_type_id'];
        }

        if (!empty($options['employee_id'])) {
            $sql .= " AND {$document_alias}.employee_id=" . (int) $options['employee_id'];
        }

        if (!empty($options['supplier_id'])) {
            $sql .= " AND {$submission_alias}.supplier_id=" . (int) $options['supplier_id'];
        }

        if (!empty($options['document_status'])) {
            $sql .= " AND {$document_alias}.status=" . $this->db->escape($options['document_status']);
        }

        if (!empty($options['portal_status'])) {
            $sql .= " AND {$submission_alias}.portal_status=" . $this->db->escape($options['portal_status']);
        }

        if (!empty($options['expiration_start'])) {
            $sql .= " AND {$document_alias}.expiration_date >= " . $this->db->escape($options['expiration_start']);
        }

        if (!empty($options['expiration_end'])) {
            $sql .= " AND {$document_alias}.expiration_date <= " . $this->db->escape($options['expiration_end']);
        }

        return $sql;
    }
}
