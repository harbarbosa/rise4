<?php

namespace LaudosTecnicos\Models;

class LaudoStatusTransitions_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_status_transitions';

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
        $statuses_table = $this->db->prefixTable('laudo_statuses');
        $where = " WHERE $table.deleted = 0";

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.from_status_code LIKE '%$search%' OR $table.to_status_code LIKE '%$search%')";
        }

        $status = get_array_value($options, 'status');
        if ($status !== '' && $status !== null) {
            $where .= " AND $table.is_active=" . (int) $status;
        }

        return $this->queryOrEmpty("SELECT $table.*,
                fs.name AS from_status_name,
                fs.color AS from_status_color,
                ts.name AS to_status_name,
                ts.color AS to_status_color
            FROM $table
            LEFT JOIN $statuses_table fs ON fs.code = $table.from_status_code AND fs.deleted = 0
            LEFT JOIN $statuses_table ts ON ts.code = $table.to_status_code AND ts.deleted = 0
            $where
            ORDER BY $table.sort ASC, $table.id DESC");
    }

    public function get_allowed_transitions(string $from_status_code)
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $statuses_table = $this->db->prefixTable('laudo_statuses');
        $rows = $this->db->query("SELECT $table.*, ts.name AS to_status_name, ts.color AS to_status_color
            FROM $table
            LEFT JOIN $statuses_table ts ON ts.code = $table.to_status_code AND ts.deleted = 0
            WHERE $table.from_status_code = ? AND $table.deleted = 0 AND $table.is_active = 1
            ORDER BY $table.sort ASC, $table.id DESC", array(trim($from_status_code)))
            ->getResult();

        return $rows ?: array();
    }

    public function is_allowed(string $from_status_code, string $to_status_code): bool
    {
        if (!$this->hasTable()) {
            return false;
        }

        $row = $this->db->table($this->db->prefixTable($this->table))
            ->where('from_status_code', trim($from_status_code))
            ->where('to_status_code', trim($to_status_code))
            ->where('deleted', 0)
            ->where('is_active', 1)
            ->get()
            ->getRow();

        return (bool) $row;
    }

    public function is_in_use(int $id): bool
    {
        return false;
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
}
