<?php

namespace GED\Models;

class Ged_document_types_model extends GedBaseModel
{
    protected $table = 'ged_document_types';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $sql = "SELECT t.*
                FROM {$this->table} t
                WHERE 1=1";

        $id = get_array_value($options, 'id');
        if ($id) {
            $sql .= " AND t.id=" . (int) $id;
        }

        $status = get_array_value($options, 'is_active');
        if ($status !== null && $status !== '') {
            $sql .= " AND t.is_active=" . (int) $status;
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (t.name LIKE '%{$search}%' ESCAPE '!' OR t.description LIKE '%{$search}%' ESCAPE '!')";
        }

        $sql .= " ORDER BY t.is_active DESC, t.name ASC";

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

    public function has_documents($id)
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $table = $this->db->prefixTable('ged_documents');
        if (!$this->db->tableExists($table)) {
            return false;
        }

        $row = $this->db->table($table)
            ->select('COUNT(*) AS total')
            ->where('document_type_id', $id)
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        return $row && !empty($row->total);
    }

    public function save_type($data, $id = 0)
    {
        return $this->ci_save($data, $id);
    }
}
