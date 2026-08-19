<?php

if (!function_exists('engenharia_permission_keys')) {
    function engenharia_permission_keys()
    {
        return array(
            'engenharia_access',
            'engenharia_view_laudos',
            'engenharia_create_laudos',
            'engenharia_edit_laudos',
            'engenharia_inspect_laudos',
            'engenharia_review_laudos',
            'engenharia_finalize_laudos',
            'engenharia_reopen_laudos',
            'engenharia_delete_laudos',
            'engenharia_manage_checklists',
            'engenharia_manage_templates',
            'engenharia_manage_settings',
        );
    }
}

if (!function_exists('engenharia_default_settings')) {
    function engenharia_default_settings()
    {
        return array(
            'module_name' => 'Engenharia',
            'module_enabled' => '1',
            'default_laudo_type' => 'ELETRICO',
        );
    }
}

if (!function_exists('engenharia_default_types')) {
    function engenharia_default_types()
    {
        return array(
            array('name' => 'Laudo Elétrico', 'code' => 'ELETRICO', 'prefix' => 'ELE-', 'is_enabled' => 1),
            array('name' => 'Laudo SPDA', 'code' => 'SPDA', 'prefix' => 'SPDA-', 'is_enabled' => 1),
            array('name' => 'Análise de Risco PDA', 'code' => 'PDA', 'prefix' => 'PDA-', 'is_enabled' => 0),
        );
    }
}

if (!function_exists('engenharia_status_labels')) {
    function engenharia_status_labels()
    {
        return array(
            'draft' => app_lang('engenharia_status_draft'),
            'scheduled' => app_lang('engenharia_status_scheduled'),
            'inspection' => app_lang('engenharia_status_inspection'),
            'awaiting_information' => app_lang('engenharia_status_awaiting_information'),
            'review' => app_lang('engenharia_status_review'),
            'finalized' => app_lang('engenharia_status_finalized'),
            'canceled' => app_lang('engenharia_status_canceled'),
        );
    }
}

if (!function_exists('engenharia_status_dropdown')) {
    function engenharia_status_dropdown()
    {
        return array('' => '-') + engenharia_status_labels();
    }
}

if (!function_exists('engenharia_status_badge')) {
    function engenharia_status_badge($status)
    {
        $classes = array(
            'draft' => 'bg-secondary', 'scheduled' => 'bg-info', 'inspection' => 'bg-primary',
            'awaiting_information' => 'bg-warning text-dark', 'review' => 'bg-warning text-dark',
            'finalized' => 'bg-success', 'canceled' => 'bg-danger',
        );
        $label = engenharia_status_labels()[$status] ?? $status;
        return "<span class='badge " . ($classes[$status] ?? 'bg-secondary') . "'>" . esc($label) . '</span>';
    }
}

if (!function_exists('engenharia_app_table_options')) {
    function engenharia_app_table_options(array $dropdown)
    {
        $options = array();
        foreach ($dropdown as $id => $text) {
            $options[] = array('id' => (string) $id, 'text' => $text);
        }
        return $options;
    }
}
