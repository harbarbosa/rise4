<?php

namespace GED\Models;

class Ged_suppliers_model extends GedBaseModel
{
    protected $table = 'ged_suppliers';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $sql = "SELECT s.*
                FROM {$this->table} s
                WHERE 1=1";

        $id = get_array_value($options, 'id');
        if ($id) {
            $sql .= " AND s.id=" . (int) $id;
        }

        $is_active = get_array_value($options, 'is_active');
        if ($is_active !== null && $is_active !== '') {
            $sql .= " AND s.is_active=" . (int) $is_active;
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (s.name LIKE '%{$search}%' ESCAPE '!'"
                . " OR s.portal_url LIKE '%{$search}%' ESCAPE '!'"
                . " OR s.contact_name LIKE '%{$search}%' ESCAPE '!'"
                . " OR s.contact_email LIKE '%{$search}%' ESCAPE '!'"
                . " OR s.contact_phone LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= " ORDER BY s.is_active DESC, s.name ASC";

        return $this->db->query($sql);
    }

    public function get_dropdown()
    {
        $result = array();
        $rows = $this->get_details()->getResult();
        foreach ($rows as $row) {
            if ((int) $row->is_active === 1) {
                $result[$row->id] = $row->name;
            }
        }

        return $result;
    }

    public function name_exists_active($name, $ignore_id = 0)
    {
        $builder = $this->db->table($this->table);
        $builder->select('id');
        $builder->where('is_active', 1);
        $builder->where('LOWER(name) = ' . $this->db->escape(mb_strtolower(trim((string) $name))), null, false);

        $ignore_id = (int) $ignore_id;
        if ($ignore_id) {
            $builder->where('id !=', $ignore_id);
        }

        $row = $builder->get(1)->getRow();
        return (bool) $row;
    }

    public function has_documents_or_submissions($id)
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $documents_table = $this->db->prefixTable('ged_documents');
        $submissions_table = $this->db->prefixTable('ged_document_submissions');

        $documents_count = 0;
        if ($this->db->tableExists($documents_table)) {
            $documents_row = $this->db->table($documents_table)
                ->select('COUNT(*) AS total')
                ->where('supplier_id', $id)
                ->where('deleted_at', null)
                ->get()
                ->getRow();
            $documents_count = $documents_row ? (int) $documents_row->total : 0;
        }

        $submissions_count = 0;
        if ($this->db->tableExists($submissions_table)) {
            $submissions_row = $this->db->table($submissions_table)
                ->select('COUNT(*) AS total')
                ->where('supplier_id', $id)
                ->where('deleted_at', null)
                ->get()
                ->getRow();
            $submissions_count = $submissions_row ? (int) $submissions_row->total : 0;
        }

        return ($documents_count > 0) || ($submissions_count > 0);
    }

    public function save_supplier($data, $id = 0)
    {
        return $this->ci_save($data, $id);
    }
}
