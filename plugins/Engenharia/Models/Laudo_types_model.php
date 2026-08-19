<?php

namespace Engenharia\Models;

class Laudo_types_model extends EngenhariaBaseModel
{
    protected $table = 'eng_types';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_enabled($include_disabled = false)
    {
        $builder = $this->db->table($this->db->prefixTable($this->table));
        $builder->where('deleted', 0);
        if (!$include_disabled) {
            $builder->where('is_enabled', 1);
        }

        return $builder->orderBy('sort', 'ASC')->orderBy('name', 'ASC')->get();
    }

    public function get_by_code(string $code)
    {
        return $this->db->table($this->db->prefixTable($this->table))
            ->where('code', trim($code))
            ->where('deleted', 0)
            ->get(1)
            ->getRow();
    }
}
