<?php

namespace LaudosTecnicos\Models;

class LaudoTypes_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_types';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_active_dropdown($include_blank = true)
    {
        $options = array();
        if ($include_blank) {
            $options[''] = '-';
        }

        if (!$this->hasTable()) {
            return $options;
        }

        $rows = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('is_active', 1)
            ->orderBy('sort', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            $options[$row->id] = $row->name;
        }

        return $options;
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $types_table = $this->db->prefixTable($this->table);
        $categories_table = $this->db->prefixTable('laudo_categories');
        $where = " WHERE $types_table.deleted = 0";

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($types_table.name LIKE '%$search%' OR $types_table.code LIKE '%$search%' OR $types_table.description LIKE '%$search%' OR $types_table.prefix LIKE '%$search%')";
        }

        $status = get_array_value($options, 'status');
        if ($status !== '' && $status !== null) {
            $where .= " AND $types_table.is_active=" . (int) $status;
        }

        $category_id = (int) get_array_value($options, 'category_id');
        if ($category_id) {
            $where .= " AND $types_table.category_id=" . $category_id;
        }

        return $this->queryOrEmpty("SELECT $types_table.*, $categories_table.name AS category_name
            FROM $types_table
            LEFT JOIN $categories_table ON $categories_table.id = $types_table.category_id AND $categories_table.deleted = 0
            $where
            ORDER BY $types_table.sort ASC, $types_table.name ASC");
    }

    public function is_in_use(int $id): bool
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $count = $this->db->table($this->db->prefixTable('laudos'))->where('type_id', $id)->where('deleted', 0)->countAllResults();
        return $count > 0;
    }

    public function toggle_status(int $id)
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $row = $this->get_one($id);
        if (!$row || !$row->id) {
            return false;
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('id', $id)
            ->update(array(
                'is_active' => empty($row->is_active) ? 1 : 0,
                'updated_at' => get_current_utc_time(),
            ));
    }

    public function save_from_post(array $data, ?int $id = null)
    {
        $data['updated_at'] = get_current_utc_time();
        if ($id) {
            return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($data);
        }
        $data['created_at'] = get_current_utc_time();
        return $this->db->table($this->db->prefixTable($this->table))->insert($data);
    }

    public function delete($id = 0, $undo = false)
    {
        $id = (int) $id;
        if (!$id || $this->is_in_use($id)) {
            return false;
        }

        return parent::delete($id, $undo);
    }
}
