<?php

namespace LicitaIA\Models;

class Document_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_documents';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_by_opportunity($opportunity_id)
    {
        $opportunity_id = (int) $opportunity_id;
        if (!$opportunity_id || !$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT d.id, d.opportunity_id, d.file_name, d.original_file_name, d.file_path, d.mime_type, d.file_size,
                       d.source_url, d.extracted_text, d.status, d.created_by, d.created_at, d.updated_at, d.deleted,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name
                FROM {$table} d
                LEFT JOIN {$users_table} u ON u.id = d.created_by
                WHERE d.deleted = 0 AND d.opportunity_id = {$opportunity_id}
                ORDER BY d.id ASC";

        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id)
    {
        $id = (int) $id;
        if (!$id || !$this->hasTable()) {
            return null;
        }

        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT d.id, d.opportunity_id, d.file_name, d.original_file_name, d.file_path, d.mime_type, d.file_size,
                       d.source_url, d.extracted_text, d.status, d.created_by, d.created_at, d.updated_at, d.deleted,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name
                FROM {$table} d
                LEFT JOIN {$users_table} u ON u.id = d.created_by
                WHERE d.deleted = 0 AND d.id = {$id}
                LIMIT 1";

        $row = $this->db->query($sql)->getRow();
        return $row ?: null;
    }

    public function save_extracted_text($document_id, $text)
    {
        $document_id = (int) $document_id;
        if (!$document_id || !$this->hasTable()) {
            return false;
        }

        $text = $this->normalizeExtractedText($text);
        $data = array(
            'extracted_text' => trim((string) $text),
            'status' => trim((string) (trim((string) $text) !== '' ? 'text_extracted' : 'pending_extraction')),
            'updated_at' => get_my_local_time(),
        );

        return $this->ci_save($data, $document_id);
    }

    private function normalizeExtractedText($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        if (stripos($text, '%PDF-') !== false) {
            return '';
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $sample = function_exists('mb_substr') ? mb_substr($text, 0, 1000, 'UTF-8') : substr($text, 0, 1000);
        $sample_length = function_exists('mb_strlen') ? max(1, mb_strlen($sample, 'UTF-8')) : max(1, strlen($sample));
        $printable_count = preg_match_all('/[^\p{C}]/u', $sample, $matches);
        if ($printable_count === false || (($printable_count / $sample_length) < 0.55)) {
            return '';
        }

        return $text;
    }
}
