<?php

namespace AssistenteIA\Services;

class LiveQueryService
{
    public function execute(string $entity, array $arguments, $user): array
    {
        $limit = min(max((int)($arguments['limit'] ?? 20), 1), 50);
        $status = strtolower(trim((string)($arguments['status'] ?? '')));

        if (str_starts_with($entity, 'module:')) {
            return $this->queryModule(substr($entity, 7), $arguments, $user, $limit, $status);
        }

        if (str_starts_with($entity, 'auto:')) {
            if (!$this->admin($user)) throw new \RuntimeException('A consulta automática desta entidade exige permissão administrativa até que o módulo tenha um adaptador de permissões.');
            $table = substr($entity, 5);
            $db = \db_connect();
            if (!in_array($table, $db->listTables(), true)) throw new \RuntimeException('Tabela não encontrada no inventário atual.');
            $builder = $db->table($table);
            $filters = is_array($arguments['filters'] ?? null) ? $arguments['filters'] : [];
            $columns = $db->getFieldNames($table);
            foreach ($filters as $field => $value) {
                if (in_array($field, $columns, true) && is_scalar($value)) $builder->where($field, $value);
            }
            return $this->normalize($builder->limit($limit)->get());
        }

        if ($entity === 'projects') {
            if (!$this->projectAccess($user)) throw new \RuntimeException('Usuário sem permissão para consultar projetos.');
            $options = ['limit' => $limit];
            if (!$this->allProjects($user)) $options['user_id'] = (int)$user->id;
            if (in_array($status, ['aberto', 'abertos', 'open', 'ativo', 'ativos'], true)) $options['status_ids'] = '1';
            $rows = \model('App\\Models\\Projects_model')->get_details($options);
            return $this->normalize($rows);
        }

        if ($entity === 'clients') {
            if (!$this->clientAccess($user)) throw new \RuntimeException('Usuário sem permissão para consultar clientes.');
            $rows = \model('App\\Models\\Clients_model')->get_details(['limit' => $limit]);
            return $this->normalize($rows);
        }

        if ($entity === 'purchases_orders') {
            if (!$this->purchaseAccess($user)) throw new \RuntimeException('Usuário sem permissão para consultar compras.');
            $options = ['limit' => $limit];
            if (in_array($status, ['aberto', 'abertos', 'open'], true)) $options['status'] = 'open';
            $rows = \model('Purchases\\Models\\Purchases_orders_model')->get_details($options);
            return $this->normalize($rows);
        }

        if ($entity === 'engineering_reports') {
            if (!$this->engineeringAccess($user)) throw new \RuntimeException('Usuário sem permissão para consultar laudos de engenharia.');
            $options = [];
            if (in_array($status, ['aberto', 'abertos', 'open'], true)) {
                $drafts = \model('Engenharia\\Models\\Laudos_model')->get_details(['status' => 'draft'])->getResult();
                $reviews = \model('Engenharia\\Models\\Laudos_model')->get_details(['status' => 'in_review'])->getResult();
                return $this->normalize(array_merge($drafts, $reviews));
            } elseif (in_array($status, ['emitido', 'emitidos', 'issued'], true)) {
                $options['status'] = 'issued';
            }
            $rows = \model('Engenharia\\Models\\Laudos_model')->get_details($options);
            return $this->normalize($rows);
        }

        if ($entity === 'ged_employee_documents') {
            if (!$this->gedAccess($user)) throw new \RuntimeException('Usuário sem permissão para consultar documentos do GED.');
            $options = ['owner_type' => 'employee'];
            if (in_array($status, ['vencido', 'vencidos', 'expired', 'overdue'], true)) $options['expiration_scope'] = 'overdue';
            $rows = \model('GED\\Models\\Ged_documents_model')->get_details($options);
            return $this->normalize($rows);
        }

        throw new \RuntimeException('Entidade não disponível para consulta.');
    }

    private function queryModule(string $plugin, array $arguments, $user, int $limit, string $status): array
    {
        $knowledge = new KnowledgeSyncService();
        if (!$knowledge->canAccessModule($plugin, $user)) throw new \RuntimeException('Usuário sem permissão para consultar este módulo.');
        $filters = is_array($arguments['filters'] ?? null) ? $arguments['filters'] : [];
        $tables = $knowledge->moduleTables($plugin);
        $sources = [];
        $items = [];
        $db = \db_connect();
        foreach ($tables as $table) {
            $columns = $db->getFieldNames($table);
            if (!$columns) continue;
            $builder = $db->table($table);
            foreach ($filters as $field => $value) {
                if (in_array($field, $columns, true) && is_scalar($value)) $builder->where($field, $value);
            }
            if ($status !== '' && in_array('status', $columns, true)) $builder->where('status', $status);
            if (in_array('deleted', $columns, true)) $builder->where('deleted', 0);
            $rows = $builder->limit($limit)->get()->getResultArray();
            if ($rows) {
                $sources[$table] = ['total' => count($rows), 'items' => $rows];
                foreach ($rows as $row) $items[] = array_merge(['_source_table' => $table], $row);
            }
        }
        return ['total' => count($items), 'items' => array_slice($items, 0, $limit), 'sources' => $sources, 'module' => $plugin];
    }

    private function normalize($result): array
    {
        if (is_array($result) && isset($result['data'])) $result = $result['data'];
        if (is_object($result) && method_exists($result, 'getResultArray')) $result = $result->getResultArray();
        if (is_object($result) && method_exists($result, 'getResult')) $result = $result->getResult();
        if (!is_array($result)) $result = [];
        return ['total' => count($result), 'items' => array_slice(array_map(static fn($item) => (array)$item, $result), 0, 50)];
    }

    private function projectAccess($u): bool { return $this->admin($u) || \get_array_value($u->permissions ?? [], 'can_manage_all_projects') === '1' || \get_array_value($u->permissions ?? [], 'project'); }
    private function allProjects($u): bool { return $this->admin($u) || \get_array_value($u->permissions ?? [], 'can_manage_all_projects') === '1' || \get_array_value($u->permissions ?? [], 'project') === 'all'; }
    private function clientAccess($u): bool { return $this->admin($u) || in_array(\get_array_value($u->permissions ?? [], 'client'), ['all', 'read_only', 'own', 'specific'], true); }
    private function purchaseAccess($u): bool { return $this->admin($u) || \get_array_value($u->permissions ?? [], 'purchases_view') === '1' || \get_array_value($u->permissions ?? [], 'purchases_manage') === '1' || \get_array_value($u->permissions ?? [], 'purchases_approve') === '1'; }
    private function engineeringAccess($u): bool { return $this->admin($u) || \get_array_value($u->permissions ?? [], 'engenharia_access') === '1' || \get_array_value($u->permissions ?? [], 'engenharia_view_laudos') === '1'; }
    private function gedAccess($u): bool { return $this->admin($u) || \get_array_value($u->permissions ?? [], 'ged_access') === '1' || \get_array_value($u->permissions ?? [], 'ged_view_documents') === '1'; }
    private function admin($u): bool { return $u && !empty($u->is_admin); }
}
