<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Distributors_model extends Crud_model
{
    protected $table = 'fv_distributors';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('title', 'legal_name', 'document', 'aneel_code', 'acronym', 'state_code', 'external_slug', 'source', 'agent_type', 'is_synced', 'raw_payload', 'origin_hash', 'notes', 'sync_notes', 'active', 'show_in_registration', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_distributors');
        $tariffs_table = $this->db->prefixTable('fv_tariffs');
        if (!$this->db->tableExists($table)) {
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }

        $where = "WHERE $table.deleted=0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $active_only = get_array_value($options, 'active_only');
        if ($active_only !== null && $active_only !== '') {
            $where .= " AND $table.active=" . (int) $active_only;
        }

        $state_code = strtoupper(trim((string) get_array_value($options, 'state_code')));
        if ($state_code !== '') {
            $where .= " AND $table.state_code=" . $this->db->escape($state_code);
        }

        $external_slug = trim((string) get_array_value($options, 'external_slug'));
        if ($external_slug !== '') {
            $where .= " AND $table.external_slug=" . $this->db->escape($external_slug);
        }

        $source = trim((string) get_array_value($options, 'source'));
        if ($source !== '') {
            $where .= " AND $table.source=" . $this->db->escape($source);
        }

        $document = preg_replace('/\D+/', '', (string) get_array_value($options, 'document'));
        if ($document !== '') {
            $where .= " AND $table.document=" . $this->db->escape($document);
        }

        $aneel_code = trim((string) get_array_value($options, 'aneel_code'));
        if ($aneel_code !== '') {
            $where .= " AND $table.aneel_code=" . $this->db->escape($aneel_code);
        }

        $agent_type = trim((string) get_array_value($options, 'agent_type'));
        if ($agent_type !== '') {
            $where .= " AND $table.agent_type=" . $this->db->escape($agent_type);
        }

        $show_in_registration = get_array_value($options, 'show_in_registration');
        if ($show_in_registration !== null && $show_in_registration !== '') {
            $where .= " AND $table.show_in_registration=" . (int) $show_in_registration;
        }

        $available_only = get_array_value($options, 'available_only');
        if ($available_only) {
            $where .= " AND $table.active=1 AND $table.show_in_registration=1";
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND (" . implode(" OR ", array(
                "$table.title LIKE '%$search%' ESCAPE '!'",
                "$table.legal_name LIKE '%$search%' ESCAPE '!'",
                "$table.document LIKE '%$search%' ESCAPE '!'",
                "$table.aneel_code LIKE '%$search%' ESCAPE '!'",
                "$table.acronym LIKE '%$search%' ESCAPE '!'",
                "$table.state_code LIKE '%$search%' ESCAPE '!'",
                "$table.external_slug LIKE '%$search%' ESCAPE '!'",
                "$table.notes LIKE '%$search%' ESCAPE '!'"
            )) . ")";
        }

        $current_tariff_subquery = "0";
        if ($this->db->tableExists($tariffs_table)) {
            $current_tariff_subquery = "(SELECT COUNT(1) FROM $tariffs_table
                WHERE $tariffs_table.deleted=0
                AND $tariffs_table.distributor_id=$table.id
                AND $tariffs_table.active=1
                AND ($tariffs_table.is_current=1 OR (($tariffs_table.valid_from IS NULL OR $tariffs_table.valid_from <= CURDATE()) AND ($tariffs_table.valid_to IS NULL OR $tariffs_table.valid_to >= CURDATE()))))";
        }

        if ($available_only) {
            $where .= " AND {$current_tariff_subquery} > 0";
        }

        try {
            return $this->db->query("SELECT $table.*, {$current_tariff_subquery} AS current_tariff_count FROM $table $where ORDER BY $table.title ASC");
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Distributors query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_dropdown()
    {
        $result = array("" => "-");
        foreach ($this->get_details(array('active_only' => 1))->getResult() as $row) {
            $result[$row->id] = $row->title;
        }
        return $result;
    }

    public function get_by_external_slug($slug = '')
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }

        return $this->get_details(array(
            'external_slug' => $slug,
        ))->getRow();
    }

    public function find_by_title_and_uf($title = '', $state_code = '')
    {
        $title = trim((string) $title);
        $state_code = strtoupper(trim((string) $state_code));
        if ($title === '') {
            return null;
        }

        $table = $this->db->prefixTable($this->table);
        if (!$this->db->tableExists($table)) {
            return null;
        }

        $sql = "SELECT * FROM {$table}
            WHERE deleted=0
            AND title=" . $this->db->escape($title);
        if ($state_code !== '') {
            $sql .= " AND state_code=" . $this->db->escape($state_code);
        }
        $sql .= " ORDER BY id DESC LIMIT 1";

        try {
            return $this->db->query($sql)->getRow();
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Distributor match query error: ' . $e->getMessage());
            return null;
        }
    }

    public function get_available_distributors($state_code = null)
    {
        $options = array(
            'active_only' => 1,
            'show_in_registration' => 1,
            'available_only' => 1,
        );

        $state_code = strtoupper(trim((string) $state_code));
        if ($state_code !== '') {
            $options['state_code'] = $state_code;
        }

        $query = $this->get_details($options);
        return $query ? $query->getResult() : array();
    }

    public function find_existing_distributor($data = array())
    {
        $table = $this->db->prefixTable($this->table);
        if (!$this->db->tableExists($table)) {
            return null;
        }

        $aneel_code = trim((string) get_array_value($data, 'aneel_code'));
        if ($aneel_code !== '') {
            $row = $this->db->table($table)->where('deleted', 0)->where('aneel_code', $aneel_code)->get()->getRow();
            if ($row) {
                return $row;
            }
        }

        $document = preg_replace('/\D+/', '', (string) get_array_value($data, 'document'));
        if ($document !== '') {
            $row = $this->db->table($table)->where('deleted', 0)->where('document', $document)->get()->getRow();
            if ($row) {
                return $row;
            }
        }

        $acronym = $this->_normalize_key(get_array_value($data, 'acronym'));
        if ($acronym !== '') {
            $rows = $this->db->table($table)->where('deleted', 0)->get()->getResult();
            foreach ($rows as $row) {
                if ($this->_normalize_key($row->acronym ?? '') === $acronym) {
                    return $row;
                }
            }
        }

        $name = $this->_normalize_key(get_array_value($data, 'title') ?: get_array_value($data, 'name'));
        if ($name !== '') {
            $rows = $this->db->table($table)->where('deleted', 0)->get()->getResult();
            foreach ($rows as $row) {
                if ($this->_normalize_key($row->title ?? '') === $name || $this->_normalize_key($row->legal_name ?? '') === $name) {
                    return $row;
                }
            }
        }

        return null;
    }

    public function sync_display_flags()
    {
        $table = $this->db->prefixTable($this->table);
        $tariffs_table = $this->db->prefixTable('fv_tariffs');
        if (!$this->db->tableExists($table) || !$this->db->tableExists($tariffs_table)) {
            return false;
        }
        if (!$this->db->fieldExists('show_in_registration', $table)) {
            return false;
        }
        $tariffs_has_is_current = $this->db->fieldExists('is_current', $tariffs_table);

        $manual_sources = array('manual', 'local');
        $this->db->table($table)
            ->where('deleted', 0)
            ->whereNotIn('source', $manual_sources)
            ->update(array(
                'show_in_registration' => 0,
                'updated_at' => get_my_local_time(),
            ));

        $sql = "UPDATE {$table} d
            SET d.show_in_registration = 1,
                d.updated_at = " . $this->db->escape(get_my_local_time()) . "
            WHERE d.deleted = 0
            AND d.active = 1
            AND d.source NOT IN ('manual', 'local')
            AND EXISTS (
                SELECT 1 FROM {$tariffs_table} t
                WHERE t.deleted = 0
                AND t.distributor_id = d.id
                AND t.active = 1
                AND (" . ($tariffs_has_is_current ? "t.is_current = 1 OR " : "") . "((t.valid_from IS NULL OR t.valid_from <= CURDATE()) AND (t.valid_to IS NULL OR t.valid_to >= CURDATE())))
            )";

        return $this->db->query($sql);
    }

    private function _normalize_key($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false && $ascii !== '') {
            $value = $ascii;
        }

        return preg_replace('/[^A-Z0-9]+/', '', $value);
    }
}
