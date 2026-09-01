<?php

namespace AssistenteIA\Services;

class ToolRegistry
{
    public function availableForCurrentUser($loginUser): array
    {
        $entities = [];
        $isAdmin = $loginUser && !empty($loginUser->is_admin);
        if ($isAdmin || \get_array_value($loginUser->permissions ?? [], 'can_manage_all_projects') === '1' || \get_array_value($loginUser->permissions ?? [], 'project')) $entities[] = 'projects';
        if ($isAdmin || in_array(\get_array_value($loginUser->permissions ?? [], 'client'), ['all', 'read_only', 'own', 'specific'], true)) $entities[] = 'clients';
        if ($isAdmin || \get_array_value($loginUser->permissions ?? [], 'purchases_view') === '1' || \get_array_value($loginUser->permissions ?? [], 'purchases_manage') === '1' || \get_array_value($loginUser->permissions ?? [], 'purchases_approve') === '1') $entities[] = 'purchases_orders';
        if ($isAdmin || \get_array_value($loginUser->permissions ?? [], 'engenharia_access') === '1' || \get_array_value($loginUser->permissions ?? [], 'engenharia_view_laudos') === '1') $entities[] = 'engineering_reports';
        if ($isAdmin || \get_array_value($loginUser->permissions ?? [], 'ged_access') === '1' || \get_array_value($loginUser->permissions ?? [], 'ged_view_documents') === '1') $entities[] = 'ged_employee_documents';
        $entities = array_merge($entities, (new KnowledgeSyncService())->resourcesForUser($loginUser));
        if (!$entities) return [];

        return [['type' => 'function', 'function' => [
            'name' => 'consultar_sistema',
            'description' => 'Consulta dados atuais do RISE CRM. Use somente uma entidade autorizada e informe o status quando a pergunta mencionar aberto, pendente, aprovado ou outro estado.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'entity' => ['type' => 'string', 'enum' => $entities],
                    'status' => ['type' => 'string', 'description' => 'Status solicitado, se houver.'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                    'filters' => ['type' => 'object', 'description' => 'Filtros simples por campos encontrados no schema da entidade.'],
                ],
                'required' => ['entity'],
                'additionalProperties' => false,
            ],
        ]]];
    }

    private function definition(string $name, string $description): array
    {
        return ['type' => 'function', 'function' => [
            'name' => $name,
            'description' => $description,
            'parameters' => ['type' => 'object', 'properties' => [], 'additionalProperties' => false],
        ]];
    }
}
