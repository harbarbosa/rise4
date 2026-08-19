<?php

namespace LaudosTecnicos\Models;

class LaudoEquipments_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_equipments';

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
            $where .= " AND ($table.name LIKE '%$search%' OR $table.model LIKE '%$search%' OR $table.serial_number LIKE '%$search%' OR $table.patrimony_number LIKE '%$search%')";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status = '" . $this->db->escapeString($status) . "'";
        }

        return $this->queryOrEmpty("SELECT $table.* FROM $table $where ORDER BY $table.name ASC");
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
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            $options[$row->id] = $row->name;
        }

        return $options;
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

    public function calibration_status($equipment): string
    {
        if (!$equipment) {
            return 'invalid';
        }

        if ((string) ($equipment->status ?? '') === 'blocked') {
            return 'blocked';
        }

        if ((string) ($equipment->status ?? '') === 'maintenance') {
            return 'maintenance';
        }

        $next = !empty($equipment->next_calibration) ? strtotime((string) $equipment->next_calibration) : null;
        if (!$next) {
            return 'unknown';
        }

        if ($next < time()) {
            return 'expired';
        }

        $due_soon = strtotime('+30 days');
        if ($next <= $due_soon) {
            return 'due';
        }

        return 'valid';
    }

    public function is_valid_for_use(int $id): bool
    {
        $row = $this->get_one($id);
        if (!$row || !$row->id) {
            return false;
        }

        return in_array($this->calibration_status($row), array('valid', 'due', 'unknown'), true);
    }

    public function is_in_use(int $id): bool
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $measurements = $this->db->prefixTable('laudo_measurements');
        return $this->db->table($measurements)->where('equipment_id', $id)->where('deleted', 0)->countAllResults() > 0;
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
