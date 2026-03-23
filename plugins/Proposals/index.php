<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Proposals
  Description: Proposals dashboard and base module.
  Version: 0.1.0
  Requires at least: 3.9.0
  Author: Internal
*/

use App\Controllers\Security_Controller;

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $ci = new Security_Controller(false);
    if (!isset($ci->login_user) || $ci->login_user->user_type !== "staff") {
        return $sidebar_menu;
    }

    $permissions = $ci->login_user->permissions ?? array();
    $has_access = $ci->login_user->is_admin
        || get_array_value($permissions, 'proposals_view') == '1'
        || get_array_value($permissions, 'proposals_manage') == '1'
        || get_array_value($permissions, 'proposals_export_pdf') == '1'
        || get_array_value($permissions, 'proposals_settings_manage') == '1';
    if (!$has_access) {
        return $sidebar_menu;
    }

    if (!isset($sidebar_menu["proposals"])) {
        $sidebar_menu["proposals"] = array(
            "name" => "proposals_menu",
            "url" => "propostas",
            "class" => "file-text",
            "position" => 7,
        );
    }

    $submenu = array(
        "proposals_dashboard" => array("name" => "proposals_dashboard", "url" => "propostas", "class" => "home")
    );

    $can_settings = $ci->login_user->is_admin || get_array_value($permissions, 'proposals_settings_manage') == '1';
    if ($can_settings) {
        $submenu["proposals_settings"] = array("name" => "proposals_settings", "url" => "propostas/settings", "class" => "settings");
    }

    $sidebar_menu["proposals"]["submenu"] = $submenu;

    if (!isset($sidebar_menu["cadastro"])) {
        $sidebar_menu["cadastro"] = array(
            "name" => "cadastro",
            "url" => "#",
            "class" => "book",
            "position" => 7,
            "submenu" => array()
        );
    }
    if (!isset($sidebar_menu["cadastro"]["submenu"]) || !is_array($sidebar_menu["cadastro"]["submenu"])) {
        $sidebar_menu["cadastro"]["submenu"] = array();
    }
    if (!isset($sidebar_menu["cadastro"]["submenu"]["cadastro_products"])) {
        $sidebar_menu["cadastro"]["submenu"]["cadastro_products"] = array(
            "name" => "proposals_products",
            "url" => "propostas/products",
            "class" => "package"
        );
    }

    return $sidebar_menu;
});

app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $ci = new Security_Controller(false);
    $login_user = $ci->login_user ?? null;
    if (!$login_user) {
        return $settings_menu;
    }

    $can_manage_settings = $login_user->is_admin
        || get_array_value($login_user->permissions ?? array(), "can_manage_all_kinds_of_settings");
    if (!$can_manage_settings) {
        return $settings_menu;
    }

    $settings_menu["app_settings"][] = array(
        "name" => "company_data",
        "url" => "company_data_settings"
    );

    return $settings_menu;
});

app_hooks()->add_action('app_hook_role_permissions_extension', function ($hook_data = null) {
    try {
        $request = \Config\Services::request();
        $role_id = (int)$request->getUri()->getSegment(3);
        $permissions = array();
        if ($role_id) {
            $Roles_model = model('App\\Models\\Roles_model');
            $role = $Roles_model->get_one($role_id);
            $permissions = $role && $role->permissions ? unserialize($role->permissions) : array();
        }
        if (!is_array($permissions)) {
            $permissions = array();
        }

        $view_path = PLUGINPATH . 'Proposals/Views/permissions/role_permissions.php';
        if (file_exists($view_path)) {
            include $view_path;
            if (!defined('PROPOSALS_ROLE_PERMISSIONS_RENDERED')) {
                define('PROPOSALS_ROLE_PERMISSIONS_RENDERED', true);
            }
        } else {
            log_message('error', '[Proposals] role_permissions view not found at ' . $view_path);
        }
    } catch (\Throwable $e) {
        log_message('error', '[Proposals] Permissions hook error: ' . $e->getMessage());
    }
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = \Config\Services::request();
    $permissions['proposals_view'] = $request->getPost('proposals_view') ? '1' : '';
    $permissions['proposals_manage'] = $request->getPost('proposals_manage') ? '1' : '';
    $permissions['proposals_export_pdf'] = $request->getPost('proposals_export_pdf') ? '1' : '';
    $permissions['proposals_settings_manage'] = $request->getPost('proposals_settings_manage') ? '1' : '';
    return $permissions;
});

app_hooks()->add_action('app_hook_data_insert', function ($hook_data) {
    try {
        if (!$hook_data || !is_array($hook_data)) {
            return;
        }

        $table = get_array_value($hook_data, 'table_without_prefix');
        if ($table !== 'tasks' && $table !== 'events') {
            return;
        }

        $request = \Config\Services::request();
        $proposal_id = (int)$request->getPost('plugin_proposal_id');
        if (!$proposal_id) {
            $referer = (string)$request->getHeaderLine('Referer');
            if ($referer && preg_match("~\\/propostas\\/view\\/(\\d+)~", $referer, $matches)) {
                $proposal_id = (int)get_array_value($matches, 1);
            }
        }
        if (!$proposal_id) {
            return;
        }

        $db = db_connect('default');
        $proposal_table = $db->prefixTable('proposals_custom');
        $proposal = $db->table($proposal_table)->select('id')->where('id', $proposal_id)->where('deleted', 0)->get()->getRow();
        if (!$proposal) {
            return;
        }

        $created_by = null;
        try {
            $ci = new Security_Controller(false);
            $created_by = $ci->login_user->id ?? null;
        } catch (\Throwable $e) {
            $created_by = null;
        }

        if ($table === 'tasks') {
            $link_table = $db->prefixTable('proposal_task_links_custom');
            if ($db->tableExists($link_table)) {
                $db->table($link_table)->insert(array(
                    'proposal_id' => $proposal_id,
                    'task_id' => (int)get_array_value($hook_data, 'id'),
                    'created_by' => $created_by,
                    'created_at' => get_my_local_time(),
                    'deleted' => 0
                ));
            }
        } elseif ($table === 'events' && $request->getPost('type') === 'reminder') {
            $link_table = $db->prefixTable('proposal_reminder_links_custom');
            if ($db->tableExists($link_table)) {
                $db->table($link_table)->insert(array(
                    'proposal_id' => $proposal_id,
                    'event_id' => (int)get_array_value($hook_data, 'id'),
                    'created_by' => $created_by,
                    'created_at' => get_my_local_time(),
                    'deleted' => 0
                ));
            }
        }

        $prefix = "PR-" . str_pad($proposal_id, 6, "0", STR_PAD_LEFT) . " - ";
        $title = (string)get_array_value($hook_data, 'data', array())['title'] ?? '';
        if ($title && strpos($title, $prefix) !== 0) {
            if ($table === 'tasks') {
                $tasks_table = $db->prefixTable('tasks');
                $db->table($tasks_table)->where('id', (int)get_array_value($hook_data, 'id'))->update(array(
                    'title' => $prefix . $title
                ));
            } elseif ($table === 'events') {
                $events_table = $db->prefixTable('events');
                $db->table($events_table)->where('id', (int)get_array_value($hook_data, 'id'))->update(array(
                    'title' => $prefix . $title
                ));
            }
        }
    } catch (\Throwable $e) {
        log_message('error', '[Proposals] Link hook error: ' . $e->getMessage());
    }
});

app_hooks()->add_action('app_hook_data_delete', function ($hook_data) {
    try {
        if (!$hook_data || !is_array($hook_data)) {
            return;
        }

        $table = get_array_value($hook_data, 'table_without_prefix');
        if ($table !== 'tasks' && $table !== 'events') {
            return;
        }

        $id = (int)get_array_value($hook_data, 'id');
        if (!$id) {
            return;
        }

        $db = db_connect('default');
        if ($table === 'tasks') {
            $link_table = $db->prefixTable('proposal_task_links_custom');
            if ($db->tableExists($link_table)) {
                $db->table($link_table)->where('task_id', $id)->update(array('deleted' => 1));
            }
        } else {
            $link_table = $db->prefixTable('proposal_reminder_links_custom');
            if ($db->tableExists($link_table)) {
                $db->table($link_table)->where('event_id', $id)->update(array('deleted' => 1));
            }
        }
    } catch (\Throwable $e) {
        log_message('error', '[Proposals] Link delete hook error: ' . $e->getMessage());
    }
});

app_hooks()->add_action('app_hook_task_view_right_panel_extension', function () {
    try {
        $request = \Config\Services::request();
        $task_id = (int)($request->getPost('id') ?? $request->getPost('task_id') ?? 0);
        if (!$task_id) {
            $task_id = (int)$request->getUri()->getSegment(3);
        }
        if (!$task_id) {
            return;
        }

        $db = db_connect('default');
        $link_table = $db->prefixTable('proposal_task_links_custom');
        if (!$db->tableExists($link_table)) {
            return;
        }

        $proposal_rows = $db->table($link_table)
            ->select('proposal_id')
            ->where('task_id', $task_id)
            ->where('deleted', 0)
            ->get()
            ->getResult();
        if (!$proposal_rows) {
            return;
        }

        $proposal_table = $db->prefixTable('proposals_custom');
        $links_html = "";
        foreach ($proposal_rows as $row) {
            $proposal_id = (int)($row->proposal_id ?? 0);
            if (!$proposal_id) {
                continue;
            }

            $proposal = $db->table($proposal_table)
                ->select('id,title')
                ->where('id', $proposal_id)
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$proposal) {
                continue;
            }

            $label = "#" . $proposal->id;
            if (!empty($proposal->title)) {
                $label .= " - " . $proposal->title;
            }

            $links_html .= "<div>" . anchor(get_uri("propostas/view/" . $proposal->id), esc($label)) . "</div>";
        }

        if ($links_html) {
            echo "<div class='col-md-12 mb15'><strong>" . app_lang('proposals_menu') . ":</strong> " . $links_html . "</div>";
        }
    } catch (\Throwable $e) {
        log_message('error', '[Proposals] task view hook error: ' . $e->getMessage());
    }
});

register_installation_hook('Proposals', function () {
    return require __DIR__ . '/install.php';
});

register_update_hook('Proposals', function () {
    $result = require __DIR__ . '/install.php';
    $template = new \App\Libraries\Template(false);
    echo $template->view('Proposals\\Views\\update_result', array("result" => $result));
});
