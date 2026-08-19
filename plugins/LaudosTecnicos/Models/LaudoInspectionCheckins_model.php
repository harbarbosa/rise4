<?php

namespace LaudosTecnicos\Models;

class LaudoInspectionCheckins_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_inspection_checkins';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function log_checkin(array $data, string $check_type = 'checkin')
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = array(
            'inspection_id' => (int) get_array_value($data, 'inspection_id'),
            'laudo_id' => (int) get_array_value($data, 'laudo_id') ?: null,
            'check_type' => $check_type,
            'checked_at' => get_array_value($data, 'checked_at') ?: get_current_utc_time(),
            'latitude' => get_array_value($data, 'latitude') !== '' ? get_array_value($data, 'latitude') : null,
            'longitude' => get_array_value($data, 'longitude') !== '' ? get_array_value($data, 'longitude') : null,
            'accuracy' => get_array_value($data, 'accuracy') !== '' ? get_array_value($data, 'accuracy') : null,
            'user_id' => (int) get_array_value($data, 'user_id') ?: null,
            'device' => trim((string) get_array_value($data, 'device')),
            'distance_meters' => get_array_value($data, 'distance_meters') !== '' ? get_array_value($data, 'distance_meters') : null,
            'observation' => trim((string) get_array_value($data, 'observation')),
            'source' => trim((string) get_array_value($data, 'source')) ?: 'web',
            'ip_address' => trim((string) get_array_value($data, 'ip_address')),
            'created_by' => (int) get_array_value($data, 'created_by') ?: null,
            'updated_by' => (int) get_array_value($data, 'updated_by') ?: null,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        return $this->db->table($this->db->prefixTable($this->table))->insert($payload);
    }

    public function get_by_inspection(int $inspection_id)
    {
        if (!$this->hasTable() || !$inspection_id) {
            return array();
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('inspection_id', $inspection_id)
            ->where('deleted', 0)
            ->orderBy('checked_at', 'ASC')
            ->get()
            ->getResult();
    }
}
