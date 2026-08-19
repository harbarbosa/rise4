<?php

namespace LaudosTecnicos\Models;

class LaudoNorms_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_norms';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $where = " WHERE $table.deleted = 0";

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.code LIKE '%$search%' OR $table.title LIKE '%$search%' OR $table.institution LIKE '%$search%')";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status = '" . $this->db->escapeString($status) . "'";
        }

        return $this->queryOrEmpty("SELECT $table.* FROM $table $where ORDER BY $table.code ASC, $table.title ASC");
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

    public function toggle_status(int $id)
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $row = $this->get_one($id);
        if (!$row || !$row->id) {
            return false;
        }

        $new_status = ((string) ($row->status ?? 'inactive')) === 'active' ? 'inactive' : 'active';
        return $this->db->table($this->db->prefixTable($this->table))
            ->where('id', $id)
            ->update(array('status' => $new_status, 'updated_at' => get_current_utc_time()));
    }

    public function is_in_use(int $id): bool
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $links = $this->db->prefixTable('laudo_norm_links');
        return $this->db->table($links)->where('norm_id', $id)->where('deleted', 0)->countAllResults() > 0;
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
