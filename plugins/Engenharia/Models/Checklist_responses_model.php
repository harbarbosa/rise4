<?php

namespace Engenharia\Models;

class Checklist_responses_model extends EngenhariaBaseModel
{
    protected $table = 'eng_checklist_responses';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function save_response(int $laudo_id, int $item_id, array $data, int $user_id): int
    {
        $where = array('laudo_id' => $laudo_id, 'item_id' => $item_id, 'deleted' => 0);
        $existing = $this->db->table($this->db->prefixTable($this->table))->where($where)->get(1)->getRow();
        $data['answered_by'] = $user_id;
        $data['answered_at'] = $this->now();
        $data['updated_at'] = $this->now();

        if ($existing) {
            $this->db->table($this->db->prefixTable($this->table))->where('id', $existing->id)->update($data);
            return (int) $existing->id;
        }

        $data = array_merge($where, $data, array('created_at' => $this->now()));
        $this->db->table($this->db->prefixTable($this->table))->insert($data);
        return (int) $this->db->insertID();
    }

    public function forLaudo(int $laudo_id)
    {
        return $this->db->table($this->db->prefixTable($this->table))->where('laudo_id', $laudo_id)->where('deleted', 0)->get();
    }
}
