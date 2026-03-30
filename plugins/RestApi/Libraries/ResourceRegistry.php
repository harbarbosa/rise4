<?php

namespace RestApi\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RestApi\Config\Resources;

class ResourceRegistry
{
    protected BaseConnection $db;
    protected Resources $config;
    protected ?array $resources = null;

    public function __construct(?BaseConnection $db = null, ?Resources $config = null)
    {
        $this->db = $db ?: Database::connect('default');
        $this->config = $config ?: new Resources();
    }

    public function all(): array
    {
        if ($this->resources !== null) {
            return $this->resources;
        }

        $resources = [];
        $dbPrefix = $this->db->getPrefix() ?: '';

        foreach ($this->db->listTables() as $table) {
            $resourceName = $this->normalizeResourceName($table, $dbPrefix);

            if (!$resourceName || in_array($resourceName, $this->config->excluded_tables, true)) {
                continue;
            }

            $columns = $this->db->getFieldNames($table);
            if (!$columns) {
                continue;
            }

            $resources[$resourceName] = [
                'resource' => $resourceName,
                'table' => $table,
                'module' => $this->detectModule($resourceName),
                'primary_key' => $this->getPrimaryKey($table) ?: 'id',
                'columns' => $columns,
                'has_deleted_flag' => in_array('deleted', $columns, true),
                'route' => get_uri("api/" . $resourceName)
            ];
        }

        ksort($resources);
        $this->resources = $resources;

        return $resources;
    }

    public function get(string $resource): ?array
    {
        $resources = $this->all();
        return $resources[$resource] ?? null;
    }

    protected function normalizeResourceName(string $table, string $dbPrefix): string
    {
        if ($dbPrefix && str_starts_with($table, $dbPrefix)) {
            return substr($table, strlen($dbPrefix));
        }

        return $table;
    }

    protected function getPrimaryKey(string $table): ?string
    {
        $result = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'")->getResult();
        if (!$result) {
            return null;
        }

        return $result[0]->Column_name ?? null;
    }

    protected function detectModule(string $resource): string
    {
        if (str_starts_with($resource, 'purchases_')) {
            return 'Purchases';
        }

        if (str_starts_with($resource, 'proposal') || str_starts_with($resource, 'proposals')) {
            return 'Proposals';
        }

        if (str_starts_with($resource, 'projectanalizer_') || str_starts_with($resource, 'pa_')) {
            return 'ProjectAnalizer';
        }

        if (str_starts_with($resource, 'os_')) {
            return 'OrdemServico';
        }

        if (str_starts_with($resource, 'contaazul_')) {
            return 'ContaAzul';
        }

        return 'Core';
    }
}
