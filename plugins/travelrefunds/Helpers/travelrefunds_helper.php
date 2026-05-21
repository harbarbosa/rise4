<?php

if (!function_exists('travelrefunds_can_access_module')) {
    function travelrefunds_can_access_module($login_user)
    {
        if (!$login_user) {
            return false;
        }

        if (!empty($login_user->is_admin)) {
            return true;
        }

        $permissions = $login_user->permissions ?? array();
        $keys = array(
            'travelrefunds_view',
            'travelrefunds_create',
            'travelrefunds_edit',
            'travelrefunds_delete',
            'travelrefunds_approve',
            'travelrefunds_manage_settings',
        );

        foreach ($keys as $key) {
            if (get_array_value($permissions, $key) == '1') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('travelrefunds_status_label')) {
    function travelrefunds_status_label($status)
    {
        $labels = array(
            'draft' => 'Rascunho',
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            'paid' => 'Pago',
            'cancelled' => 'Cancelado',
            'planned' => 'Planejada',
            'in_progress' => 'Em andamento',
            'completed' => 'Concluida',
        );

        return get_array_value($labels, $status) ?: ucfirst((string) $status);
    }
}

if (!function_exists('travelrefunds_permission_label')) {
    function travelrefunds_permission_label($key)
    {
        $labels = array(
            'travelrefunds_view' => 'Visualizar viagens e reembolsos',
            'travelrefunds_create' => 'Criar viagens e reembolsos',
            'travelrefunds_edit' => 'Editar viagens e reembolsos',
            'travelrefunds_delete' => 'Excluir viagens e reembolsos',
            'travelrefunds_approve' => 'Aprovar reembolsos',
            'travelrefunds_manage_settings' => 'Gerenciar configuracoes',
        );

        return get_array_value($labels, $key) ?: $key;
    }
}

if (!function_exists('travelrefunds_currency')) {
    function travelrefunds_currency($value)
    {
        $settings_model = model('travelrefunds\\Models\\TravelRefundsSettings_model', false);
        $currency = $settings_model ? $settings_model->get_setting('travelrefunds_default_currency_symbol') : '';
        if (!$currency) {
            $currency = get_setting('default_currency_symbol') ?: '';
        }
        return to_currency((float) $value, $currency);
    }
}
