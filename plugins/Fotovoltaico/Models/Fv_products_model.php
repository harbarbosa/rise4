<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

/**
 * Model para acessar produtos fotovoltaicos.
 */
class Fv_products_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'fv_products';
        parent::__construct($this->table);
    }

    /**
     * Retorna lista com filtros e paginação.
     */
    public function get_list($filters = array(), $limit = 50, $offset = 0)
    {
        $filters = is_array($filters) ? $filters : array();
        $builder = $this->db_builder;
        $builder->select('*');

        if (!empty($filters['type'])) {
            $builder->where('type', $filters['type']);
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $builder->where('is_active', (int)$filters['is_active']);
        }
        if (!empty($filters['brand'])) {
            $builder->where('brand', $filters['brand']);
        }
        if (!empty($filters['q'])) {
            $q = $this->db->escapeLikeString($filters['q']);
            $builder->groupStart()
                ->like('brand', $q)
                ->orLike('model', $q)
                ->orLike('sku', $q)
                ->groupEnd();
        }

        $builder->orderBy('id', 'DESC');
        $builder->limit((int)$limit, (int)$offset);
        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['specs'] = $this->_decode_specs($row['specs_json'] ?? null);
        }

        return $rows;
    }

    /**
     * Retorna um produto por ID.
     */
    public function get_one($id = 0)
    {
        $row = parent::get_one($id);
        if ($row && isset($row->specs_json)) {
            $row->specs = $this->_decode_specs($row->specs_json);
        }
        return $row;
    }

    /**
     * Cria produto.
     */
    public function create($data)
    {
        return $this->ci_save($data);
    }

    /**
     * Atualiza produto.
     */
    public function update($id = null, $row = null): bool
    {
        return (bool)$this->ci_save($row, $id);
    }

    /**
     * Ativa/desativa produto.
     */
    public function soft_toggle_active($id, $is_active)
    {
        return $this->ci_save(array('is_active' => (int)$is_active), $id);
    }

    /**
     * Inserção em lote.
     */
    public function bulk_insert($rows)
    {
        if (!$rows || !is_array($rows)) {
            return 0;
        }
        return $this->db_builder->insertBatch($rows);
    }

    private function _decode_specs($json)
    {
        if (!$json) {
            return null;
        }
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
