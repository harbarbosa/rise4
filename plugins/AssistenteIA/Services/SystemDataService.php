<?php

namespace AssistenteIA\Services;

class SystemDataService
{
    public function quickAnswer(string $message, $loginUser): ?string
    {
        $text = mb_strtolower($message);

        if (preg_match('/\b(projetos?|project)\b/u', $text) && preg_match('/\b(aberto|abertos|em aberto|ativos?)\b/u', $text)) {
            return $this->openProjects($loginUser);
        }

        if (preg_match('/\b(clientes?|cadastros?)\b/u', $text) && preg_match('/\b(quantos?|quantidade|cadastrad)/u', $text)) {
            return $this->clientsCount($loginUser);
        }

        if (preg_match('/\b(compras?|ordens? de compra)\b/u', $text) && preg_match('/\b(aberta|abertas|aberto|abertos|pendente|pendentes)\b/u', $text)) {
            if (!$this->purchaseAccess($loginUser)) return 'Você não possui permissão para consultar compras.';
            $rows = \model('Purchases\\Models\\Purchases_orders_model')->get_details(['status' => 'open', 'limit' => 50]);
            $total = is_array($rows) && isset($rows['recordsTotal']) ? (int)$rows['recordsTotal'] : (is_object($rows) && method_exists($rows, 'getNumRows') ? $rows->getNumRows() : 0);
            return 'Você tem ' . $total . ' compra(s) em aberto.';
        }

        if (preg_match('/\b(laudo|laudos|relatório técnico|relatorio tecnico)\b/u', $text) && preg_match('/\b(engenharia|elétrico|eletrico|spda|aberto|aberta|revisão|revisao)\b/u', $text)) {
            if (!$this->engineeringAccess($loginUser)) return 'Você não possui permissão para consultar laudos de engenharia.';
            $rows = \model('Engenharia\\Models\\Laudos_model')->get_details();
            $total = 0;
            foreach ($rows->getResult() as $row) if (in_array($row->status, ['draft', 'in_review'], true)) $total++;
            return 'Você tem ' . $total . ' laudo(s) de engenharia em aberto.';
        }

        if (preg_match('/\b(documentos?|ged)\b/u', $text) && preg_match('/\b(funcionári|funcionari|colaborador|vencid|expirad)/u', $text)) {
            if (!$this->gedAccess($loginUser)) return 'Você não possui permissão para consultar documentos do GED.';
            $rows = \model('GED\\Models\\Ged_documents_model')->get_details(['owner_type' => 'employee', 'expiration_scope' => 'overdue'])->getResult();
            if (!$rows) return 'Não há documentos vencidos de funcionários.';
            $names = [];
            foreach ($rows as $row) $names[] = trim(($row->employee_name ?? 'Funcionário') . ' — ' . ($row->document_type_name ?? $row->title ?? 'Documento') . ' (' . ($row->expiration_date ?? 'sem data') . ')');
            return 'Encontrei ' . count($rows) . ' documento(s) vencido(s) de funcionários: ' . implode('; ', array_slice($names, 0, 20)) . '.';
        }

        return null;
    }

    private function openProjects($user): string
    {
        if (!$this->hasProjectAccess($user)) {
            return 'Você não possui permissão para consultar projetos.';
        }

        $options = [];
        if (!$this->canManageAllProjects($user)) {
            $options['user_id'] = (int)$user->id;
        }

        $info = \model('App\\Models\\Projects_model')->count_project_status($options);
        return 'Você tem ' . (int)($info->open ?? 0) . ' projeto(s) aberto(s).';
    }

    private function clientsCount($user): string
    {
        if (!$this->hasClientAccess($user)) {
            return 'Você não possui permissão para consultar clientes.';
        }

        $options = ['limit' => 1];
        $permission = \get_array_value($user->permissions ?? [], 'client');
        if (!$this->isAdmin($user) && $permission === 'own') {
            $options['show_own_clients_only_user_id'] = (int)$user->id;
        } elseif (!$this->isAdmin($user) && $permission === 'specific') {
            $options['client_groups'] = \get_array_value($user->permissions ?? [], 'client_specific');
        }

        $result = \model('App\\Models\\Clients_model')->get_details($options);
        $total = is_array($result) ? (int)($result['recordsTotal'] ?? 0) : 0;
        return 'Você tem ' . $total . ' cliente(s) cadastrado(s) acessível(is) ao seu usuário.';
    }

    private function hasProjectAccess($user): bool
    {
        if ($this->isAdmin($user) || \get_array_value($user->permissions ?? [], 'can_manage_all_projects') === '1') return true;
        return in_array(\get_array_value($user->permissions ?? [], 'project'), ['all', 'read_only', 'own', 'own_project_members', 'specific'], true);
    }

    private function hasClientAccess($user): bool
    {
        if ($this->isAdmin($user)) return true;
        return in_array(\get_array_value($user->permissions ?? [], 'client'), ['all', 'read_only', 'own', 'specific'], true);
    }

    private function canManageAllProjects($user): bool
    {
        return $this->isAdmin($user) || \get_array_value($user->permissions ?? [], 'can_manage_all_projects') === '1' || \get_array_value($user->permissions ?? [], 'project') === 'all';
    }

    private function isAdmin($user): bool
    {
        return $user && !empty($user->is_admin);
    }

    private function purchaseAccess($user): bool
    {
        return $this->isAdmin($user)
            || \get_array_value($user->permissions ?? [], 'purchases_view') === '1'
            || \get_array_value($user->permissions ?? [], 'purchases_manage') === '1'
            || \get_array_value($user->permissions ?? [], 'purchases_approve') === '1';
    }

    private function engineeringAccess($user): bool
    {
        return $this->isAdmin($user)
            || \get_array_value($user->permissions ?? [], 'engenharia_access') === '1'
            || \get_array_value($user->permissions ?? [], 'engenharia_view_laudos') === '1';
    }

    private function gedAccess($user): bool
    {
        return $this->isAdmin($user)
            || \get_array_value($user->permissions ?? [], 'ged_access') === '1'
            || \get_array_value($user->permissions ?? [], 'ged_view_documents') === '1';
    }

}
