<?php

namespace PontoRH\Models;

use App\Models\Crud_model;

class PontoRhBaseModel extends Crud_model
{
    public function __construct($table = null)
    {
        parent::__construct($table);
    }

    protected function hasTable(?string $table = null): bool
    {
        $table = $table ?: $this->table;
        if (!$table) {
            return false;
        }

        return $this->db->tableExists($this->db->prefixTable($table));
    }

    protected function emptyResult()
    {
        return $this->db->query('SELECT 1 AS empty_result FROM (SELECT 1) AS tmp WHERE 1 = 0');
    }

    protected function queryOrEmpty(string $sql)
    {
        return $this->hasTable() ? $this->db->query($sql) : $this->emptyResult();
    }
}
