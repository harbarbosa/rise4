<?php

namespace LaudosTecnicos\Models;

class LaudoStatuses_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_statuses';

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
            $where .= " AND ($table.name LIKE '%$search%' OR $table.code LIKE '%$search%')";
        }

        $status = get_array_value($options, 'status');
        if ($status !== '' && $status !== null) {
            $where .= " AND $table.is_active=" . (int) $status;
        }

        return $this->queryOrEmpty("SELECT $table.* FROM $table $where ORDER BY $table.sort ASC, $table.name ASC");
    }

    public function get_status_by_code(string $code)
    {
        if (!$this->hasTable()) {
            return null;
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('code', trim($code))
            ->where('deleted', 0)
            ->get()
            ->getRow();
    }

    public function get_dropdown($include_blank = true)
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
            $options[$row->code] = $row->name;
        }

        return $options;
    }

    public function is_in_use(int $id): bool
    {
        $status = $this->get_one($id);
        if (!$status || !$status->code) {
            return false;
        }

        $code = (string) $status->code;
        $db = $this->db;
        $laudos_table = $db->prefixTable('laudos');
        $transitions_table = $db->prefixTable('laudo_status_transitions');
        $history_table = $db->prefixTable('laudo_status_history');

        if ($db->table($laudos_table)->where('status', $code)->where('deleted', 0)->countAllResults() > 0) {
            return true;
        }

        if ($db->table($transitions_table)->groupStart()->where('from_status_code', $code)->orWhere('to_status_code', $code)->groupEnd()->where('deleted', 0)->countAllResults() > 0) {
            return true;
        }

        return $db->table($history_table)->groupStart()->where('from_status_code', $code)->orWhere('to_status_code', $code)->groupEnd()->countAllResults() > 0;
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
