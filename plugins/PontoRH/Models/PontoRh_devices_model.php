<?php

namespace PontoRH\Models;

class PontoRh_devices_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_devices';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $devices_table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT d.*,
                    CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS team_member_name
                FROM {$devices_table} d
                LEFT JOIN {$users_table} u ON u.id = d.team_member_id
                WHERE d.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $sql .= ' AND d.id = ' . $id;
        }

        $team_member_id = (int) get_array_value($options, 'team_member_id');
        if ($team_member_id) {
            $sql .= ' AND d.team_member_id = ' . $team_member_id;
        }

        $device_id = trim((string) get_array_value($options, 'device_id'));
        if ($device_id !== '') {
            $sql .= ' AND d.serial_number = ' . $this->db->escape($device_id);
        }

        $sql .= ' ORDER BY d.last_seen_at DESC, d.id DESC';
        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details($id = 0)
    {
        $result = $this->get_details(array('id' => $id));
        $row = $result ? $result->getRow() : null;
        return $row ?: null;
    }

    public function get_by_member_and_device($team_member_id, $device_id)
    {
        $result = $this->get_details(array(
            'team_member_id' => (int) $team_member_id,
            'device_id' => $device_id,
        ));
        return $result ? $result->getRow() : null;
    }

    public function save_device(array $data, $id = 0)
    {
        return $this->ci_save($data, $id);
    }
}
