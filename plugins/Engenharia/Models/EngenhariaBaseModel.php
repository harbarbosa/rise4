<?php

namespace Engenharia\Models;

use App\Models\Crud_model;

abstract class EngenhariaBaseModel extends Crud_model
{
    public function __construct($table = null, $db = null)
    {
        parent::__construct($table, $db);
    }

    protected function hasTable(?string $table = null): bool
    {
        $table = $table ?: $this->table_without_prefix;
        return $table ? $this->db->tableExists($this->db->prefixTable($table)) : false;
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

        return $this->db->query($sql) ?: $this->emptyResult();
    }

    protected function now(): string
    {
        return function_exists('get_current_utc_time') ? get_current_utc_time() : gmdate('Y-m-d H:i:s');
    }
}
