<?php

function frota_can_access($user): bool
{
    if (!$user) {
        return false;
    }
    if (!empty($user->is_admin)) {
        return true;
    }
    $permissions = $user->permissions ?? [];
    foreach (['frota_view','frota_manage','frota_fueling','frota_issue','frota_maintenance'] as $key) {
        if (get_array_value($permissions, $key) == '1') {
            return true;
        }
    }
    return false;
}

function frota_money($value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function frota_status_badge(string $status): string
{
    $map = [
        'active' => ['Ativo','success'], 'inactive' => ['Inativo','secondary'], 'maintenance' => ['Em manutenção','warning'],
        'open' => ['Aberta','danger'], 'in_progress' => ['Em andamento','warning'], 'resolved' => ['Resolvida','success'],
        'scheduled' => ['Agendada','info'], 'completed' => ['Concluída','success'], 'cancelled' => ['Cancelada','secondary'],
    ];
    [$label,$class] = $map[$status] ?? [ucfirst(str_replace('_',' ',$status)),'secondary'];
    return '<span class="badge bg-' . $class . '">' . esc($label) . '</span>';
}
