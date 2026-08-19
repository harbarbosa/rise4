<?php

namespace LaudosTecnicos\Models;

class LaudoDocuments_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_document_versions';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function create_version(array $data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = array(
            'laudo_id' => (int) (get_array_value($data, 'laudo_id') ?: 0),
            'variant' => trim((string) (get_array_value($data, 'variant') ?: 'full')),
            'document_code' => trim((string) (get_array_value($data, 'document_code') ?: '')),
            'public_key' => trim((string) (get_array_value($data, 'public_key') ?: '')),
            'document_hash' => trim((string) (get_array_value($data, 'document_hash') ?: '')),
            'html_snapshot' => get_array_value($data, 'html_snapshot') ?: '',
            'pdf_path' => get_array_value($data, 'pdf_path') ?: '',
            'pdf_file_name' => get_array_value($data, 'pdf_file_name') ?: '',
            'issued_at' => get_array_value($data, 'issued_at') ?: get_current_utc_time(),
            'issued_by' => (int) (get_array_value($data, 'issued_by') ?: 0),
            'status_snapshot' => get_array_value($data, 'status_snapshot') ?: '',
            'revision_snapshot' => get_array_value($data, 'revision_snapshot') ?: '',
            'visibility' => get_array_value($data, 'visibility') ?: 'internal',
            'qr_payload' => get_array_value($data, 'qr_payload') ?: '',
            'share_token' => get_array_value($data, 'share_token') ?: '',
            'share_expires_at' => get_array_value($data, 'share_expires_at') ?: null,
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'updated_by' => (int) (get_array_value($data, 'updated_by') ?: 0),
            'deleted' => 0,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
        );

        return $this->db->table($this->db->prefixTable($this->table))->insert($payload) ? $this->db->insertID() : false;
    }

    public function get_latest_version(int $laudo_id)
    {
        if (!$this->hasTable() || !$laudo_id) {
            return null;
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('laudo_id', $laudo_id)
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();
    }

    public function get_version(int $version_id)
    {
        if (!$this->hasTable() || !$version_id) {
            return null;
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('id', $version_id)
            ->where('deleted', 0)
            ->get()
            ->getRow();
    }

    public function get_version_by_key(int $laudo_id, string $public_key)
    {
        if (!$this->hasTable() || !$laudo_id || trim($public_key) === '') {
            return null;
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('laudo_id', $laudo_id)
            ->where('public_key', trim($public_key))
            ->where('deleted', 0)
            ->get()
            ->getRow();
    }

    public function create_share_link(array $data)
    {
        $table = $this->db->prefixTable('laudo_document_shares');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        $payload = array(
            'laudo_id' => (int) (get_array_value($data, 'laudo_id') ?: 0),
            'document_version_id' => (int) (get_array_value($data, 'document_version_id') ?: 0),
            'share_token' => trim((string) (get_array_value($data, 'share_token') ?: laudostecnicos_generate_token(24))),
            'password_hash' => trim((string) (get_array_value($data, 'password_hash') ?: '')),
            'visitor_label' => trim((string) (get_array_value($data, 'visitor_label') ?: '')),
            'expires_at' => get_array_value($data, 'expires_at') ?: null,
            'max_accesses' => (int) (get_array_value($data, 'max_accesses') ?: 0),
            'allow_download' => !empty(get_array_value($data, 'allow_download')) ? 1 : 0,
            'allow_comments' => !empty(get_array_value($data, 'allow_comments')) ? 1 : 0,
            'require_visitor_id' => !empty(get_array_value($data, 'require_visitor_id')) ? 1 : 0,
            'is_active' => 1,
            'access_count' => 0,
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'updated_by' => (int) (get_array_value($data, 'updated_by') ?: 0),
            'deleted' => 0,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
        );

        $inserted = $this->db->table($table)->insert($payload);
        return $inserted ? $this->db->insertID() : false;
    }

    public function get_share_by_token(string $token)
    {
        $table = $this->db->prefixTable('laudo_document_shares');
        if (!$this->db->tableExists($table)) {
            return null;
        }

        return $this->db->table($table)
            ->where('share_token', trim($token))
            ->where('deleted', 0)
            ->get()
            ->getRow();
    }

    public function increment_share_access(int $share_id, bool $was_download = false)
    {
        $table = $this->db->prefixTable('laudo_document_shares');
        if (!$this->db->tableExists($table) || !$share_id) {
            return false;
        }

        // Apply the configured limit atomically so concurrent requests cannot
        // consume the last access more than once.
        $this->db->query("UPDATE $table SET access_count = access_count + 1, updated_at = ? WHERE id = ? AND deleted = 0 AND is_active = 1 AND (max_accesses IS NULL OR max_accesses <= 0 OR access_count < max_accesses)", array(get_current_utc_time(), $share_id));
        return $this->db->affectedRows() > 0;
    }

    public function share_access_available($share): bool
    {
        if (!$share || empty($share->id) || !empty($share->deleted) || empty($share->is_active) || !empty($share->revoked_at)) {
            return false;
        }

        if (!empty($share->expires_at) && strtotime((string) $share->expires_at) < time()) {
            return false;
        }

        return empty($share->max_accesses) || (int) $share->access_count < (int) $share->max_accesses;
    }

    public function log_access(array $data)
    {
        $table = $this->db->prefixTable('laudo_document_access_logs');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        return $this->db->table($table)->insert(array(
            'laudo_id' => (int) (get_array_value($data, 'laudo_id') ?: 0),
            'document_version_id' => (int) (get_array_value($data, 'document_version_id') ?: 0),
            'share_id' => (int) (get_array_value($data, 'share_id') ?: 0),
            'visitor_label' => trim((string) (get_array_value($data, 'visitor_label') ?: '')),
            'event_type' => trim((string) (get_array_value($data, 'event_type') ?: 'view')),
            'document_variant' => trim((string) (get_array_value($data, 'document_variant') ?: 'full')),
            'downloaded' => !empty(get_array_value($data, 'downloaded')) ? 1 : 0,
            'commented' => !empty(get_array_value($data, 'commented')) ? 1 : 0,
            'visitor_id' => trim((string) (get_array_value($data, 'visitor_id') ?: '')),
            'ip_address' => trim((string) (get_array_value($data, 'ip_address') ?: '')),
            'user_agent' => trim((string) (get_array_value($data, 'user_agent') ?: '')),
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'created_at' => get_current_utc_time(),
        ));
    }

    public function save_feedback(array $data)
    {
        $table = $this->db->prefixTable('laudo_document_feedbacks');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        return $this->db->table($table)->insert(array(
            'laudo_id' => (int) (get_array_value($data, 'laudo_id') ?: 0),
            'document_version_id' => (int) (get_array_value($data, 'document_version_id') ?: 0),
            'share_id' => (int) (get_array_value($data, 'share_id') ?: 0),
            'action' => trim((string) (get_array_value($data, 'action') ?: 'comment')),
            'comment' => trim((string) (get_array_value($data, 'comment') ?: '')),
            'evidence_json' => get_array_value($data, 'evidence_json') ?: '[]',
            'visitor_label' => trim((string) (get_array_value($data, 'visitor_label') ?: '')),
            'visitor_email' => trim((string) (get_array_value($data, 'visitor_email') ?: '')),
            'accepted' => !empty(get_array_value($data, 'accepted')) ? 1 : 0,
            'rejected' => !empty(get_array_value($data, 'rejected')) ? 1 : 0,
            'ip_address' => trim((string) (get_array_value($data, 'ip_address') ?: '')),
            'user_agent' => trim((string) (get_array_value($data, 'user_agent') ?: '')),
            'created_by' => (int) (get_array_value($data, 'created_by') ?: 0),
            'created_at' => get_current_utc_time(),
        ));
    }

    public function get_feedbacks(int $document_version_id)
    {
        $table = $this->db->prefixTable('laudo_document_feedbacks');
        if (!$this->db->tableExists($table) || !$document_version_id) {
            return array();
        }

        return $this->db->table($table)
            ->where('document_version_id', $document_version_id)
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult() ?: array();
    }

    public function get_portal_documents_for_client(int $client_id)
    {
        if (!$this->hasTable() || !$client_id) {
            return array();
        }

        $documents = $this->db->prefixTable('laudo_document_versions');
        $shares = $this->db->prefixTable('laudo_document_shares');
        $laudos = $this->db->prefixTable('laudos');
        $types = $this->db->prefixTable('laudo_types');
        $categories = $this->db->prefixTable('laudo_categories');
        $clients = $this->db->prefixTable('clients');
        $statuses = $this->db->prefixTable('laudo_statuses');

        $sql = "SELECT $documents.*, $laudos.number AS laudo_number, $laudos.title AS laudo_title, $laudos.revision AS laudo_revision,
                $laudos.client_id, $types.name AS type_name, $categories.name AS category_name, $clients.company_name AS client_name,
                $statuses.name AS status_name, $statuses.color AS status_color, $statuses.icon AS status_icon,
                $documents.public_key AS public_key, $shares.share_token, $shares.allow_download, $shares.allow_comments, $shares.require_visitor_id, $shares.expires_at, $shares.visitor_label, $shares.password_hash, $shares.is_active AS share_is_active
            FROM $documents
            INNER JOIN $laudos ON $laudos.id = $documents.laudo_id AND $laudos.deleted = 0
            LEFT JOIN $types ON $types.id = $laudos.type_id AND $types.deleted = 0
            LEFT JOIN $categories ON $categories.id = $laudos.category_id AND $categories.deleted = 0
            LEFT JOIN $clients ON $clients.id = $laudos.client_id AND $clients.deleted = 0
            LEFT JOIN $statuses ON $statuses.code = $laudos.status AND $statuses.deleted = 0
            INNER JOIN $shares ON $shares.document_version_id = $documents.id AND $shares.deleted = 0
            WHERE $documents.deleted = 0 AND $laudos.client_id = " . (int) $client_id . "
            ORDER BY $documents.id DESC";

        return $this->queryOrEmpty($sql)->getResult();
    }

    public function consume_access(int $share_id)
    {
        return $this->increment_share_access($share_id, false);
    }
}
