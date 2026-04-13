<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Purchases
  Description: Purchases dashboard and base module.
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
        || get_array_value($permissions, 'purchases_view') == '1'
        || get_array_value($permissions, 'purchases_manage') == '1'
        || get_array_value($permissions, 'purchases_approve') == '1'
        || get_array_value($permissions, 'purchases_financial_approve') == '1';
    if (!$has_access) {
        return $sidebar_menu;
    }

    if (!isset($sidebar_menu["purchases"])) {
        $sidebar_menu["purchases"] = array(
            "name" => "purchases_menu",
            "url" => "purchases",
            "class" => "shopping-cart",
            "position" => 6,
        );
    }

    $sidebar_menu["purchases"]["submenu"] = array(
        "purchases_dashboard" => array("name" => "purchases_dashboard", "url" => "purchases", "class" => "home"),
        "purchases_requests" => array("name" => "purchases_requests", "url" => "purchases_requests", "class" => "list"),
        "purchases_quotations" => array("name" => "purchases_quotations", "url" => "purchases_quotations", "class" => "layers"),
        "purchases_approvals" => array("name" => "purchases_approvals", "url" => "purchases_requests/approvals", "class" => "check-square"),
        "purchases_orders" => array("name" => "purchases_purchase_orders", "url" => "purchases_orders", "class" => "shopping-cart"),
        "purchases_reports" => array("name" => "purchases_reports", "url" => "purchases_reports", "class" => "bar-chart-2")
    );

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
    if (!isset($sidebar_menu["cadastro"]["submenu"]["cadastro_suppliers"])) {
        $sidebar_menu["cadastro"]["submenu"]["cadastro_suppliers"] = array(
            "name" => "purchases_suppliers",
            "url" => "purchases_suppliers",
            "class" => "users"
        );
    }
    if (!isset($sidebar_menu["cadastro"]["submenu"]["cadastro_transportadoras"])) {
        $sidebar_menu["cadastro"]["submenu"]["cadastro_transportadoras"] = array(
            "name" => "purchases_transportadoras",
            "url" => "purchases_transportadoras",
            "class" => "truck"
        );
    }

    return $sidebar_menu;
});

app_hooks()->add_action('app_hook_role_permissions_extension', function () {
    //mostrar uma configuração de função
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


        $view_path = PLUGINPATH . 'Purchases/Views/permissions/role_permissions.php';
        if (file_exists($view_path)) {
            $permissions = $permissions;
            include $view_path;
            if (!defined('PURCHASES_ROLE_PERMISSIONS_RENDERED')) {
                define('PURCHASES_ROLE_PERMISSIONS_RENDERED', true);
            }
        } else {
            log_message('error', '[Purchases] role_permissions view not found at ' . $view_path);
        }
    } catch (\Throwable $e) {

        log_message('error', '[Purchases] Permissions hook error: ' . $e->getMessage());
    }
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    $request = \Config\Services::request();
    $permissions['purchases_view'] = $request->getPost('purchases_view') ? '1' : '';
    $permissions['purchases_manage'] = $request->getPost('purchases_manage') ? '1' : '';
    $permissions['purchases_approve'] = $request->getPost('purchases_approve') ? '1' : '';
    $permissions['purchases_financial_approve'] = $request->getPost('purchases_financial_approve') ? '1' : '';
    $limit_value = $request->getPost('purchases_financial_limit');
    $limit_value = $limit_value ? unformat_currency($limit_value) : '';
    $permissions['purchases_financial_limit'] = $request->getPost('purchases_financial_approve') ? $limit_value : '';
    return $permissions;
});

app_hooks()->add_filter('app_filter_notification_config', function ($events) {
    $events["purchase_request_sent_for_quotation"] = array(
        "notify_to" => array("team_members", "team"),
        "info" => function ($options) {
            $request_id = 0;
            if (isset($options->estimate_request_id) && $options->estimate_request_id) {
                $request_id = (int)$options->estimate_request_id;
            }

            $url = $request_id ? get_uri("purchases_requests/view/" . $request_id) : get_uri("purchases_requests");
            return array("url" => $url);
        }
    );

    $request_events = array(
        "purchase_request_sent_to_quotation",
        "purchase_request_quotation_in_progress",
        "purchase_request_quotation_finalized",
        "purchase_request_awaiting_approval",
        "purchase_request_approval_partial",
        "purchase_request_approved_for_po",
        "purchase_request_rejected",
        "purchase_request_po_created",
        "purchase_request_po_sent",
        "purchase_request_partial_received",
        "purchase_request_received"
    );

    foreach ($request_events as $event) {
        $events[$event] = array(
            "notify_to" => array("team_members", "team"),
            "info" => function ($options) {
                $request_id = 0;
                if (isset($options->estimate_request_id) && $options->estimate_request_id) {
                    $request_id = (int)$options->estimate_request_id;
                }

                $url = $request_id ? get_uri("purchases_requests/view/" . $request_id) : get_uri("purchases_requests");
                return array("url" => $url);
            }
        );
    }

    return $events;
});

app_hooks()->add_filter('app_filter_notification_description', function ($descriptions, $notification) {
    if (!$notification || strpos($notification->event, "purchase_request_") !== 0) {
        return $descriptions;
    }

    $request_id = isset($notification->estimate_request_id) ? (int)$notification->estimate_request_id : 0;
    if (!$request_id) {
        return $descriptions;
    }

    $Requests_model = model('Purchases\\Models\\Purchases_requests_model');
    $request = $Requests_model->get_details(array("id" => $request_id))->getRow();
    if ($request) {
        $request_code = $request->request_code ? $request->request_code : ("#" . $request->id);
        $descriptions[] = "<div>" . app_lang("purchases_request_code") . ": " . $request_code . "</div>";
    }

    return $descriptions;
});

app_hooks()->add_filter('app_filter_email_templates', function ($templates) {
    if (!isset($templates["purchases"]) || !is_array($templates["purchases"])) {
        $templates["purchases"] = array();
    }

    $templates["purchases"]["purchase_request_sent_for_quotation"] = array(
        "REQUEST_CODE",
        "REQUEST_ID",
        "REQUEST_URL",
        "REQUEST_PRIORITY",
        "REQUEST_NOTE",
        "REQUESTED_BY",
        "APP_TITLE",
        "COMPANY_NAME",
        "LOGO_URL",
        "SIGNATURE",
        "RECIPIENTS_EMAIL_ADDRESS"
    );

    $templates["purchases"]["purchase_request_status_update"] = array(
        "REQUEST_CODE",
        "REQUEST_ID",
        "REQUEST_URL",
        "REQUEST_STATUS",
        "REQUEST_PRIORITY",
        "REQUEST_NOTE",
        "REQUESTED_BY",
        "APP_TITLE",
        "COMPANY_NAME",
        "LOGO_URL",
        "SIGNATURE",
        "RECIPIENTS_EMAIL_ADDRESS"
    );

    return $templates;
});

app_hooks()->add_filter('app_filter_send_email_notification', function ($email_info) {
    $notification = get_array_value($email_info, "notification");
    if (!$notification || strpos($notification->event, "purchase_request_") !== 0) {
        return $email_info;
    }

    $request_id = isset($notification->estimate_request_id) ? (int)$notification->estimate_request_id : 0;
    $Requests_model = model('Purchases\\Models\\Purchases_requests_model');
    $request = $request_id ? $Requests_model->get_details(array("id" => $request_id))->getRow() : null;

    $parser_data = get_array_value($email_info, "parser_data");
    $parser_data = is_array($parser_data) ? $parser_data : array();

    $request_code = $request && $request->request_code ? $request->request_code : ($request_id ? "#" . $request_id : "");
    $request_priority = $request && $request->priority ? app_lang("purchases_priority_" . $request->priority) : "";
    $requested_by = $request && $request->requested_by_name ? $request->requested_by_name : $notification->user_name;
    $request_note = $request && $request->note ? $request->note : "";
    $request_url = $request_id ? get_uri("purchases_requests/view/" . $request_id) : get_uri("purchases_requests");

    $parser_data["REQUEST_CODE"] = $request_code;
    $parser_data["REQUEST_ID"] = $request_id;
    $parser_data["REQUEST_URL"] = $request_url;
    $parser_data["REQUEST_PRIORITY"] = $request_priority;
    $parser_data["REQUEST_NOTE"] = $request_note;
    $parser_data["REQUESTED_BY"] = $requested_by;
    $parser_data["LOGO_URL"] = get_logo_url();

    $template_name = "purchase_request_sent_for_quotation";
    if ($notification->event !== "purchase_request_sent_for_quotation") {
        $template_name = "purchase_request_status_update";
    }

    $status_label = "";
    if ($notification->event !== "purchase_request_sent_for_quotation") {
        $status_key = str_replace("purchase_request_", "", $notification->event);
        $status_label = app_lang("purchases_status_" . $status_key);
    }

    $parser_data["REQUEST_STATUS"] = $status_label;

    $Email_templates_model = model('App\\Models\\Email_templates_model');
    $template = $Email_templates_model->get_final_template($template_name, true);
    if (!$template || !get_array_value($template, "message_default")) {
        return $email_info;
    }

    $user_language = get_array_value($email_info, "user_language");
    $parser_data["SIGNATURE"] = get_array_value($template, "signature_$user_language") ? get_array_value($template, "signature_$user_language") : get_array_value($template, "signature_default");

    $parser = \Config\Services::parser();
    $subject_template = get_array_value($template, "subject_$user_language") ? get_array_value($template, "subject_$user_language") : get_array_value($template, "subject_default");
    $message_template = get_array_value($template, "message_$user_language") ? get_array_value($template, "message_$user_language") : get_array_value($template, "message_default");

    $subject = $parser->setData($parser_data)->renderString($subject_template);
    $message = $parser->setData($parser_data)->renderString($message_template);

    return array(
        "subject" => $subject,
        "message" => $message
    );
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
        $request_id = (int)$request->getPost('plugin_purchase_request_id');
        if (!$request_id) {
            $referer = (string)$request->getHeaderLine('Referer');
            if ($referer && preg_match("~\\/purchases_requests\\/view\\/(\\d+)~", $referer, $matches)) {
                $request_id = (int)get_array_value($matches, 1);
            }
        }
        if (!$request_id) {
            return;
        }

        $db = db_connect('default');
        $requests_table = $db->prefixTable('purchases_requests');
        $request_row = $db->table($requests_table)->select('id')->where('id', $request_id)->where('deleted', 0)->get()->getRow();
        if (!$request_row) {
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
            $link_table = $db->prefixTable('purchases_request_task_links_custom');
            if ($db->tableExists($link_table)) {
                $db->table($link_table)->insert(array(
                    'request_id' => $request_id,
                    'task_id' => (int)get_array_value($hook_data, 'id'),
                    'created_by' => $created_by,
                    'created_at' => get_my_local_time(),
                    'deleted' => 0
                ));
            }
        } elseif ($table === 'events' && $request->getPost('type') === 'reminder') {
            $link_table = $db->prefixTable('purchases_request_reminder_links_custom');
            if ($db->tableExists($link_table)) {
                $db->table($link_table)->insert(array(
                    'request_id' => $request_id,
                    'event_id' => (int)get_array_value($hook_data, 'id'),
                    'created_by' => $created_by,
                    'created_at' => get_my_local_time(),
                    'deleted' => 0
                ));
            }
        }

        $prefix = "RC-" . str_pad($request_id, 6, "0", STR_PAD_LEFT) . " - ";
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
        log_message('error', '[Purchases] Link hook error: ' . $e->getMessage());
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
            $link_table = $db->prefixTable('purchases_request_task_links_custom');
            if ($db->tableExists($link_table)) {
                $db->table($link_table)->where('task_id', $id)->update(array('deleted' => 1));
            }
        } else {
            $link_table = $db->prefixTable('purchases_request_reminder_links_custom');
            if ($db->tableExists($link_table)) {
                $db->table($link_table)->where('event_id', $id)->update(array('deleted' => 1));
            }
        }
    } catch (\Throwable $e) {
        log_message('error', '[Purchases] Link delete hook error: ' . $e->getMessage());
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
        $link_table = $db->prefixTable('purchases_request_task_links_custom');
        if (!$db->tableExists($link_table)) {
            return;
        }

        $request_rows = $db->table($link_table)
            ->select('request_id')
            ->where('task_id', $task_id)
            ->where('deleted', 0)
            ->get()
            ->getResult();
        if (!$request_rows) {
            return;
        }

        $requests_table = $db->prefixTable('purchases_requests');
        $links_html = "";
        foreach ($request_rows as $row) {
            $request_id = (int)($row->request_id ?? 0);
            if (!$request_id) {
                continue;
            }

            $request_info = $db->table($requests_table)
                ->select('id,request_code')
                ->where('id', $request_id)
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$request_info) {
                continue;
            }

            $label = $request_info->request_code ? $request_info->request_code : ("RC-" . str_pad($request_info->id, 6, "0", STR_PAD_LEFT));
            $links_html .= "<div>" . anchor(get_uri("purchases_requests/view/" . $request_info->id), esc($label)) . "</div>";
        }

        if ($links_html) {
            echo "<div class='col-md-12 mb15'><strong>" . app_lang('purchases_requests') . ":</strong> " . $links_html . "</div>";
        }
    } catch (\Throwable $e) {
        log_message('error', '[Purchases] task view hook error: ' . $e->getMessage());
    }
});

register_installation_hook('Purchases', function () {
    require __DIR__ . '/install.php';
});

register_update_hook('Purchases', function () {
    $result = require __DIR__ . '/install.php';
    $template = new \App\Libraries\Template(false);
    echo $template->view('Purchases\\Views\\update_result', array("result" => $result));
});
