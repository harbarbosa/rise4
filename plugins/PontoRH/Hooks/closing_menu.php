<?php

use App\Controllers\Security_Controller;

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    try {
        $ci = new Security_Controller(false);
        $login_user = $ci->login_user ?? null;
        if (!$login_user || $login_user->user_type !== 'staff') {
            return $sidebar_menu;
        }
        if (!\PontoRH\Plugin::canViewReportsScope($login_user) && !\PontoRH\Plugin::canManageSettingsScope($login_user) && !\PontoRH\Plugin::canAdmin($login_user)) {
            return $sidebar_menu;
        }
        if (empty($sidebar_menu['pontorh']) || !is_array($sidebar_menu['pontorh'])) {
            return $sidebar_menu;
        }
        $submenu = $sidebar_menu['pontorh']['submenu'] ?? array();
        $submenu['pontorh_closing'] = array('name' => 'pontorh_closing', 'url' => 'pontorh/fechamento', 'class' => 'lock');
        $sidebar_menu['pontorh']['submenu'] = $submenu;
        $sub_pages = $sidebar_menu['pontorh']['sub_pages'] ?? array();
        if (!in_array('pontorh/fechamento', $sub_pages, true)) {
            $sub_pages[] = 'pontorh/fechamento';
        }
        $sidebar_menu['pontorh']['sub_pages'] = $sub_pages;
    } catch (\Throwable $e) {
        log_message('error', '[PontoRH] Closing menu hook error: ' . $e->getMessage());
    }
    return $sidebar_menu;
});
