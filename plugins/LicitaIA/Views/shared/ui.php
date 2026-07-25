<?php

if (!function_exists('licitaia_status_badge_class')) {
    function licitaia_status_badge_class($status)
    {
        $map = array(
            'new' => 'info',
            'analyzing' => 'warning',
            'waiting_decision' => 'secondary',
            'participate' => 'success',
            'not_participate' => 'dark',
            'proposal_in_progress' => 'primary',
            'sent' => 'primary',
            'won' => 'success',
            'lost' => 'danger',
            'canceled' => 'secondary',
        );

        return get_array_value($map, $status, 'secondary');
    }
}

if (!function_exists('licitaia_risk_badge_class')) {
    function licitaia_risk_badge_class($risk_level)
    {
        $map = array(
            'baixo' => 'success',
            'medio' => 'warning',
            'médio' => 'warning',
            'alto' => 'danger',
        );

        return get_array_value($map, mb_strtolower(trim((string) $risk_level)), 'secondary');
    }
}

if (!function_exists('licitaia_recommendation_badge_class')) {
    function licitaia_recommendation_badge_class($recommendation)
    {
        $map = array(
            'participar' => 'success',
            'analisar_melhor' => 'warning',
            'nao_participar' => 'danger',
            'não_participar' => 'danger',
        );

        return get_array_value($map, mb_strtolower(trim((string) $recommendation)), 'secondary');
    }
}

if (!function_exists('licitaia_badge_html')) {
    function licitaia_badge_html($label, $class = 'secondary', $icon = '')
    {
        $icon_html = $icon !== '' ? "<i data-feather='" . esc($icon) . "' class='icon-14 me-1'></i>" : '';
        return '<span class="badge bg-' . esc($class) . ' d-inline-flex align-items-center">' . $icon_html . esc($label) . '</span>';
    }
}
