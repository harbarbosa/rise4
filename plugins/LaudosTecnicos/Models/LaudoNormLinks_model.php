<?php

namespace LaudosTecnicos\Models;

class LaudoNormLinks_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_norm_links';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_entity(string $entity_type, int $entity_id)
    {
        if (!$this->hasTable() || !$entity_type || !$entity_id) {
            return array();
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('entity_type', trim($entity_type))
            ->where('entity_id', $entity_id)
            ->where('deleted', 0)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();
    }

    public function sync_for_entity(string $entity_type, int $entity_id, array $norm_ids = array())
    {
        if (!$this->hasTable() || !$entity_type || !$entity_id) {
            return false;
        }

        $entity_type = trim($entity_type);
        $table = $this->db->prefixTable($this->table);
        $this->db->table($table)
            ->where('entity_type', $entity_type)
            ->where('entity_id', $entity_id)
            ->delete();

        $saved = 0;
        foreach (array_unique(array_filter(array_map('intval', $norm_ids))) as $norm_id) {
            if ($this->db->table($table)->insert(array(
                'norm_id' => $norm_id,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'notes' => '',
                'created_by' => 0,
                'updated_by' => 0,
                'created_at' => get_current_utc_time(),
                'updated_at' => get_current_utc_time(),
                'deleted' => 0,
            ))) {
                $saved++;
            }
        }

        return $saved;
    }

    public function link_norm(int $norm_id, string $entity_type, int $entity_id, string $notes = '')
    {
        if (!$this->hasTable() || !$norm_id || !$entity_type || !$entity_id) {
            return false;
        }

        return $this->db->table($this->db->prefixTable($this->table))->insert(array(
            'norm_id' => $norm_id,
            'entity_type' => trim($entity_type),
            'entity_id' => $entity_id,
            'notes' => trim($notes),
            'created_by' => 0,
            'updated_by' => 0,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        ));
    }
}
