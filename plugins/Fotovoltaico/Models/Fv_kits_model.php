<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

/**
 * Model para acessar kits fotovoltaicos.
 */
class Fv_kits_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'fv_kits';
        parent::__construct($this->table);
    }

    /**
     * Retorna lista de kits com filtros.
     */
    public function get_list($filters = array())
    {
        $table = $this->db->prefixTable($this->table);
        if (!$this->db->tableExists($table)) {
            return array();
        }

        $builder = $this->db->table($table);

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $builder->where('is_active', (int)$filters['is_active']);
        }

        if (!empty($filters['q'])) {
            $q = trim((string)$filters['q']);
            $builder->groupStart()
                ->like('name', $q)
                ->orLike('description', $q)
                ->groupEnd();
        }

        return $builder->orderBy('id', 'DESC')->get()->getResultArray();
    }

    /**
     * Cria kit.
     */
    public function create($data)
    {
        return $this->ci_save($data);
    }

    /**
     * Atualiza kit.
     */
    public function update($id = null, $row = null): bool
    {
        $result = $this->ci_save($row, $id);
        return $result ? true : false;
    }

    /**
     * Ativa/desativa kit.
     */
    public function toggle_active($id, $is_active)
    {
        $table = $this->db->prefixTable($this->table);
        return $this->db->table($table)->where('id', (int)$id)->update(['is_active' => (int)$is_active]);
    }
}
