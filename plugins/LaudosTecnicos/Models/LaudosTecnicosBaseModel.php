<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

abstract class LaudosTecnicosBaseModel extends Crud_model
{
    public function __construct($table = null, $db = null)
    {
        parent::__construct($table, $db);
    }

    protected function hasTable(?string $table = null): bool
    {
        $table = $table ?: $this->table_without_prefix;
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
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $result = $this->db->query($sql);
        return $result ?: $this->emptyResult();
    }
}
