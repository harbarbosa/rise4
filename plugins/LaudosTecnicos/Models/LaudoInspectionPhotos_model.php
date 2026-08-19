<?php

namespace LaudosTecnicos\Models;

class LaudoInspectionPhotos_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_inspection_photos';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_by_inspection(int $inspection_id)
    {
        if (!$this->hasTable() || !$inspection_id) {
            return array();
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('inspection_id', $inspection_id)
            ->where('deleted', 0)
            ->orderBy('is_cover', 'DESC')
            ->orderBy('sort', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();
    }

    public function save_photo(array $data)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = array(
            'inspection_id' => (int) get_array_value($data, 'inspection_id'),
            'laudo_id' => (int) get_array_value($data, 'laudo_id') ?: null,
            'file_path' => trim((string) get_array_value($data, 'file_path')),
            'thumbnail_path' => trim((string) get_array_value($data, 'thumbnail_path')),
            'original_file_name' => trim((string) get_array_value($data, 'original_file_name')),
            'caption' => trim((string) get_array_value($data, 'caption')),
            'photo_number' => (int) (get_array_value($data, 'photo_number') ?: 1),
            'taken_at' => trim((string) get_array_value($data, 'taken_at')) ?: get_current_utc_time(),
            'user_id' => (int) get_array_value($data, 'user_id') ?: null,
            'latitude' => get_array_value($data, 'latitude') !== '' ? get_array_value($data, 'latitude') : null,
            'longitude' => get_array_value($data, 'longitude') !== '' ? get_array_value($data, 'longitude') : null,
            'location_text' => trim((string) get_array_value($data, 'location_text')),
            'sector' => trim((string) get_array_value($data, 'sector')),
            'equipment_id' => (int) get_array_value($data, 'equipment_id') ?: null,
            'checklist_id' => (int) get_array_value($data, 'checklist_id') ?: null,
            'measurement_id' => (int) get_array_value($data, 'measurement_id') ?: null,
            'nonconformity_id' => (int) get_array_value($data, 'nonconformity_id') ?: null,
            'observation' => trim((string) get_array_value($data, 'observation')),
            'hash_value' => trim((string) get_array_value($data, 'hash_value')),
            'is_cover' => get_array_value($data, 'is_cover') ? 1 : 0,
            'is_before' => get_array_value($data, 'is_before') ? 1 : 0,
            'is_after' => get_array_value($data, 'is_after') ? 1 : 0,
            'sort' => (int) (get_array_value($data, 'sort') ?: 0),
            'metadata_json' => laudostecnicos_safe_json(get_array_value($data, 'metadata', array())),
            'created_by' => (int) get_array_value($data, 'created_by') ?: null,
            'updated_by' => (int) get_array_value($data, 'updated_by') ?: null,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        return $this->db->table($this->db->prefixTable($this->table))->insert($payload);
    }

    public function set_cover(int $inspection_id, int $photo_id)
    {
        if (!$this->hasTable() || !$inspection_id || !$photo_id) {
            return false;
        }

        $table = $this->db->prefixTable($this->table);
        $this->db->table($table)->where('inspection_id', $inspection_id)->update(array('is_cover' => 0, 'updated_at' => get_current_utc_time()));
        return $this->db->table($table)->where('id', $photo_id)->where('inspection_id', $inspection_id)->update(array('is_cover' => 1, 'updated_at' => get_current_utc_time()));
    }

    public function reorder(int $inspection_id, array $ordered_ids)
    {
        if (!$this->hasTable() || !$inspection_id) {
            return false;
        }

        $table = $this->db->prefixTable($this->table);
        $order = 1;
        foreach (array_map('intval', $ordered_ids) as $photo_id) {
            if (!$photo_id) {
                continue;
            }

            $this->db->table($table)->where('id', $photo_id)->where('inspection_id', $inspection_id)->update(array('sort' => $order++, 'updated_at' => get_current_utc_time()));
        }

        return true;
    }
}
