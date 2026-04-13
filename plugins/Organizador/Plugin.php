<?php

namespace Organizador;

use App\Controllers\Security_Controller;
use Organizador\Models\My_task_phases_model;

class Plugin
{
    public static function register()
    {
        self::registerMenus();
        self::registerPermissions();
        self::registerNotificationHooks();
        self::registerEmailTemplates();
        self::registerCronHooks();
    }

    public static function taskStatuses()
    {
        try {
            $phases_model = model(My_task_phases_model::class);
            $dropdown = $phases_model->get_dropdown();
            if ($dropdown) {
                return $dropdown;
            }
        } catch (\Throwable $e) {
        }

        return array(
            'pending' => app_lang('organizador_status_pending'),
            'in_progress' => app_lang('organizador_status_in_progress'),
            'done' => app_lang('organizador_status_done'),
            'canceled' => app_lang('organizador_status_canceled'),
        );
    }

    public static function taskPriorities()
    {
        return array(
            'low' => app_lang('organizador_priority_low'),
            'medium' => app_lang('organizador_priority_medium'),
            'high' => app_lang('organizador_priority_high'),
            'urgent' => app_lang('organizador_priority_urgent'),
        );
    }

    public static function publicApiEnabled()
    {
        return (bool) get_setting('organizador_public_api_enabled');
    }

    public static function syncToEventsCalendarEnabled()
    {
        return (bool) get_setting('organizador_sync_to_events_calendar');
    }

    public static function publicApiToken()
    {
        return trim((string) get_setting('organizador_public_api_token'));
    }

    public static function generatePublicApiToken($bytes = 24)
    {
        try {
            return bin2hex(random_bytes((int) $bytes));
        } catch (\Throwable $e) {
            return sha1(uniqid((string) mt_rand(), true) . microtime(true));
        }
    }

    public static function canAccessModule($login_user)
    {
        if (!$login_user) {
            return false;
        }

        if (!isset($login_user->permissions)) {
            return (bool) $login_user->is_admin;
        }

        return $login_user->is_admin
            || get_array_value($login_user->permissions, 'mytasks_view') == '1'
            || get_array_value($login_user->permissions, 'mytasks_view_all') == '1'
            || get_array_value($login_user->permissions, 'mytasks_add') == '1'
            || get_array_value($login_user->permissions, 'mytasks_edit') == '1'
            || get_array_value($login_user->permissions, 'mytasks_delete') == '1'
            || get_array_value($login_user->permissions, 'mytasks_manage_categories') == '1'
            || get_array_value($login_user->permissions, 'mytasks_manage_tags') == '1'
            || get_array_value($login_user->permissions, 'mytasks_manage_phases') == '1'
            || get_array_value($login_user->permissions, 'mytasks_manage_settings') == '1';
    }

    public static function canManageSettings($login_user)
    {
        return $login_user && ($login_user->is_admin || get_array_value($login_user->permissions ?? array(), 'mytasks_manage_settings') == '1');
    }

    public static function canManageCategories($login_user)
    {
        return $login_user && ($login_user->is_admin || get_array_value($login_user->permissions ?? array(), 'mytasks_manage_categories') == '1');
    }

    public static function canManageTags($login_user)
    {
        return $login_user && ($login_user->is_admin || get_array_value($login_user->permissions ?? array(), 'mytasks_manage_tags') == '1');
    }

    public static function canManagePhases($login_user)
    {
        return $login_user && ($login_user->is_admin || get_array_value($login_user->permissions ?? array(), 'mytasks_manage_phases') == '1');
    }

    public static function canViewAllTasks($login_user)
    {
        return $login_user && ($login_user->is_admin || get_array_value($login_user->permissions ?? array(), 'mytasks_view_all') == '1');
    }

    public static function canAddTasks($login_user)
    {
        return $login_user && ($login_user->is_admin || get_array_value($login_user->permissions ?? array(), 'mytasks_add') == '1');
    }

    public static function canEditTasks($login_user)
    {
        return $login_user && ($login_user->is_admin || get_array_value($login_user->permissions ?? array(), 'mytasks_edit') == '1');
    }

    public static function canDeleteTasks($login_user)
    {
        return $login_user && ($login_user->is_admin || get_array_value($login_user->permissions ?? array(), 'mytasks_delete') == '1');
    }

    public static function sendTaskNotification($event, $actor_user_id, $options = array())
    {
        $enable_internal = (bool) get_setting('organizador_enable_internal_notifications');
        $enable_email = (bool) get_setting('organizador_enable_email_notifications');

        if (!$enable_internal && !$enable_email) {
            return false;
        }

        $options = (array) $options;
        $db = db_connect('default');
        $notification_settings_table = $db->prefixTable('notification_settings');
        $restore = null;

        $setting = $db->table($notification_settings_table)->where('event', $event)->get()->getRow();
        if ($setting) {
            $restore = array(
                'enable_web' => (int) $setting->enable_web,
                'enable_email' => (int) $setting->enable_email,
            );

            $update = array(
                'enable_web' => $enable_internal ? 1 : 0,
                'enable_email' => $enable_email ? 1 : 0,
            );

            if (array_key_exists('email_notification', $options) && !$options['email_notification']) {
                $update['enable_email'] = 0;
            }

            $db->table($notification_settings_table)->where('event', $event)->update($update);
        }

        $notifications_model = model('App\\Models\\Notifications_model');
        $result = $notifications_model->create_notification($event, $actor_user_id, $options);

        if ($restore !== null) {
            $db->table($notification_settings_table)->where('event', $event)->update($restore);
        }

        return $result;
    }

    public static function logNotification($task_id, $user_id, $event, $channel, $is_sent = 1)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('my_task_notifications');
        if (!$db->tableExists($table)) {
            return false;
        }

        return $db->table($table)->insert(array(
            'task_id' => (int) $task_id,
            'user_id' => (int) $user_id,
            'event' => $event,
            'channel' => $channel,
            'is_sent' => (int) $is_sent,
            'sent_at' => $is_sent ? get_current_utc_time() : null,
            'created_at' => get_current_utc_time(),
        ));
    }

    public static function syncTaskEventToEventsCalendar($task_or_id)
    {
        if (!self::syncToEventsCalendarEnabled()) {
            return false;
        }

        $tasks_model = model('Organizador\\Models\\My_tasks_model');
        $task = is_object($task_or_id) ? $task_or_id : $tasks_model->get_one_with_details((int) $task_or_id);
        if (!$task || empty($task->id)) {
            return false;
        }

        $db = db_connect('default');
        $events_table = $db->prefixTable('events');
        if (!$db->tableExists($events_table)) {
            return false;
        }

        $end = self::normalizeTaskDateTime($task->due_date);
        if (!$end) {
            self::deleteTaskEventFromEventsCalendar($task->id);
            return false;
        }

        $end_date = substr($end, 0, 10);
        $end_time = substr($end, 11, 8);

        if (($end_time === '00:00:00' || !$end_time)) {
            $end_time = '23:59:59';
        }

        $share_with = 'all';
        if (!empty($task->assigned_to)) {
            $share_with = 'all,member:' . (int) $task->assigned_to;
        }

        $event_data = array(
            'title' => $task->title,
            'description' => $task->description,
            'start_date' => $end_date,
            'start_time' => substr($end, 11, 8) ?: '00:00:00',
            'end_date' => $end_date,
            'end_time' => $end_time,
            'location' => 'Organizador',
            'labels' => $task->labels ?? '',
            'color' => self::priorityColor($task->priority ?? 'medium'),
            'created_by' => (int) ($task->created_by ?: 0),
            'share_with' => $share_with,
            'recurring' => 0,
            'repeat_every' => 0,
            'repeat_type' => null,
            'no_of_cycles' => 0,
            'client_id' => 0,
            'type' => 'event',
            'task_id' => (int) $task->id,
            'project_id' => 0,
            'lead_id' => 0,
            'ticket_id' => 0,
            'proposal_id' => 0,
            'contract_id' => 0,
            'subscription_id' => 0,
            'invoice_id' => 0,
            'order_id' => 0,
            'estimate_id' => 0,
            'related_user_id' => 0,
            'files' => '',
            'deleted' => 0,
        );

        $existing = $db->table($events_table)
            ->select('id')
            ->where('task_id', (int) $task->id)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        $events_model = model('App\\Models\\Events_model');
        $save_data = $event_data;
        if ($existing && isset($existing->id)) {
            return (bool) $events_model->ci_save($save_data, (int) $existing->id);
        }

        return (bool) $events_model->ci_save($save_data);
    }

    public static function deleteTaskEventFromEventsCalendar($task_id)
    {
        if (!self::syncToEventsCalendarEnabled()) {
            return false;
        }

        $db = db_connect('default');
        $events_table = $db->prefixTable('events');
        if (!$db->tableExists($events_table)) {
            return false;
        }

        $existing = $db->table($events_table)
            ->select('id')
            ->where('task_id', (int) $task_id)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        if (!$existing || !isset($existing->id)) {
            return false;
        }

        $events_model = model('App\\Models\\Events_model');
        return (bool) $events_model->delete((int) $existing->id);
    }

    public static function normalizeTaskDateTime($datetime)
    {
        $datetime = trim((string) $datetime);
        if ($datetime === '') {
            return '';
        }

        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    public static function priorityColor($priority)
    {
        $map = array(
            'low' => '#0d6efd',
            'medium' => '#4f46e5',
            'high' => '#d97706',
            'urgent' => '#dc3545',
        );

        return get_array_value($map, $priority) ?: '#6c757d';
    }

    public static function runReminders()
    {
        if (!get_setting('organizador_enable_auto_reminders') && !get_setting('organizador_enable_overdue_alerts')) {
            return;
        }

        $tasks_model = model('Organizador\\Models\\My_tasks_model');
        $login_user = null;
        try {
            $ci = new Security_Controller(false);
            $login_user = $ci->login_user ?? null;
        } catch (\Throwable $e) {
            $login_user = null;
        }

        $hours_before_due = (int) get_setting('organizador_reminder_hours_before_due');
        if ($hours_before_due <= 0) {
            $hours_before_due = 24;
        }

        $due_tasks = get_setting('organizador_enable_auto_reminders') ? $tasks_model->get_due_tasks($hours_before_due)->getResult() : array();
        foreach ($due_tasks as $task) {
            if ($task->reminder_sent_at && date('Y-m-d', strtotime($task->reminder_sent_at)) === date('Y-m-d')) {
                continue;
            }

            $options = array(
                'task_id' => $task->id,
                'project_id' => $task->project_id ?? null,
                'assigned_to' => $task->notify_assigned_to ? ($task->assigned_to ?? null) : null,
                'creator_id' => $task->notify_creator ? ($task->created_by ?? null) : null,
                'email_notification' => $task->email_notification,
            );
            self::sendTaskNotification('organizador_task_due_soon', $task->created_by ?: 1, $options);
            self::logNotification($task->id, (int) $task->assigned_to, 'organizador_task_due_soon', 'system', 1);
            $tasks_model->mark_reminder_sent($task->id);
        }

        if (get_setting('organizador_enable_overdue_alerts')) {
            $overdue_tasks = $tasks_model->get_overdue_tasks()->getResult();
            foreach ($overdue_tasks as $task) {
                if ($task->reminder_sent_at && date('Y-m-d', strtotime($task->reminder_sent_at)) === date('Y-m-d')) {
                    continue;
                }

                $options = array(
                    'task_id' => $task->id,
                    'project_id' => $task->project_id ?? null,
                    'assigned_to' => $task->notify_assigned_to ? ($task->assigned_to ?? null) : null,
                    'creator_id' => $task->notify_creator ? ($task->created_by ?? null) : null,
                    'email_notification' => $task->email_notification,
                );
                self::sendTaskNotification('organizador_task_overdue', $task->created_by ?: 1, $options);
                self::logNotification($task->id, (int) $task->assigned_to, 'organizador_task_overdue', 'system', 1);
                $tasks_model->mark_reminder_sent($task->id);
            }
        }
    }

    private static function registerMenus()
    {
        app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
            $ci = new Security_Controller(false);
            if (!isset($ci->login_user) || $ci->login_user->user_type !== 'staff') {
                return $sidebar_menu;
            }

            if (!self::canAccessModule($ci->login_user)) {
                return $sidebar_menu;
            }

            if (!isset($sidebar_menu['organizador'])) {
                $sidebar_menu['organizador'] = array(
                    'name' => 'organizador_menu',
                    'url' => 'organizador',
                    'class' => 'check-square',
                    'position' => 8,
                );
            }

            $submenu = array(
                'organizador_dashboard' => array('name' => 'organizador_dashboard', 'url' => 'organizador', 'class' => 'home'),
                'organizador_tasks' => array('name' => 'organizador_tasks', 'url' => 'organizador/tasks', 'class' => 'list'),
                'organizador_kanban' => array('name' => 'organizador_kanban', 'url' => 'organizador/kanban', 'class' => 'columns'),
                'organizador_calendar' => array('name' => 'organizador_calendar', 'url' => 'organizador/calendar', 'class' => 'calendar'),
            );

            if (self::canManageCategories($ci->login_user)) {
                $submenu['organizador_categories'] = array('name' => 'organizador_categories', 'url' => 'organizador/categories', 'class' => 'tag');
            }

            if (self::canManageTags($ci->login_user)) {
                $submenu['organizador_tags'] = array('name' => 'organizador_tags', 'url' => 'organizador/tags', 'class' => 'tag');
            }

            if (self::canManagePhases($ci->login_user)) {
                $submenu['organizador_phases'] = array('name' => 'organizador_phases', 'url' => 'organizador/phases', 'class' => 'columns');
            }

            if (self::canManageSettings($ci->login_user)) {
                $submenu['organizador_settings'] = array('name' => 'organizador_settings', 'url' => 'organizador/settings', 'class' => 'settings');
            }

            $sidebar_menu['organizador']['submenu'] = $submenu;

            return $sidebar_menu;
        });

        app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
            $ci = new Security_Controller(false);
            $login_user = $ci->login_user ?? null;
            if (!$login_user || !($login_user->is_admin || self::canManageSettings($login_user))) {
                return $settings_menu;
            }

            $settings_menu['plugins'][] = array(
                'name' => 'organizador_settings',
                'url' => 'organizador/settings',
            );

            return $settings_menu;
        });
    }

    private static function registerPermissions()
    {
        app_hooks()->add_action('app_hook_role_permissions_extension', function () {
            try {
                $request = \Config\Services::request();
                $role_id = (int) $request->getUri()->getSegment(3);
                $permissions = array();
                if ($role_id) {
                    $roles_model = model('App\\Models\\Roles_model');
                    $role = $roles_model->get_one($role_id);
                    $permissions = $role && $role->permissions ? unserialize($role->permissions) : array();
                }
                if (!is_array($permissions)) {
                    $permissions = array();
                }

                $view_path = PLUGINPATH . 'Organizador/Views/permissions/role_permissions.php';
                if (file_exists($view_path)) {
                    include $view_path;
                    if (!defined('ORGANIZADOR_ROLE_PERMISSIONS_RENDERED')) {
                        define('ORGANIZADOR_ROLE_PERMISSIONS_RENDERED', true);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[Organizador] role permissions hook error: ' . $e->getMessage());
            }
        });

        app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
            $request = \Config\Services::request();
            $permissions['mytasks_view'] = $request->getPost('mytasks_view') ? '1' : '';
            $permissions['mytasks_add'] = $request->getPost('mytasks_add') ? '1' : '';
            $permissions['mytasks_edit'] = $request->getPost('mytasks_edit') ? '1' : '';
            $permissions['mytasks_delete'] = $request->getPost('mytasks_delete') ? '1' : '';
            $permissions['mytasks_view_all'] = $request->getPost('mytasks_view_all') ? '1' : '';
            $permissions['mytasks_manage_categories'] = $request->getPost('mytasks_manage_categories') ? '1' : '';
            $permissions['mytasks_manage_tags'] = $request->getPost('mytasks_manage_tags') ? '1' : '';
            $permissions['mytasks_manage_phases'] = $request->getPost('mytasks_manage_phases') ? '1' : '';
            $permissions['mytasks_manage_settings'] = $request->getPost('mytasks_manage_settings') ? '1' : '';

            return $permissions;
        });
    }

    private static function registerNotificationHooks()
    {
        app_hooks()->add_filter('app_filter_notification_config', function ($events) {
            $task_link = function ($options) {
                $task_id = 0;
                if (is_object($options) && isset($options->task_id)) {
                    $task_id = (int) $options->task_id;
                } elseif (is_array($options) && isset($options['task_id'])) {
                    $task_id = (int) $options['task_id'];
                }

                $url = $task_id ? get_uri('organizador/tasks/view/' . $task_id) : get_uri('organizador/tasks');
                return array('url' => $url);
            };

            foreach (array(
                'organizador_task_created',
                'organizador_task_assigned',
                'organizador_task_updated',
                'organizador_task_due_soon',
                'organizador_task_overdue',
                'organizador_task_completed',
                'organizador_task_reminder',
            ) as $event) {
                $events[$event] = array(
                    'notify_to' => array('team_members', 'team'),
                    'info' => $task_link,
                );
            }

            return $events;
        });

        app_hooks()->add_filter('app_filter_create_notification_where_query', function ($where_queries, $hook_data) {
            $event = get_array_value($hook_data, 'event');
            if (strpos($event, 'organizador_task_') !== 0) {
                return $where_queries;
            }

            $options = get_array_value($hook_data, 'options');
            $assigned_to = (int) get_array_value($options, 'assigned_to');
            $creator_id = (int) get_array_value($options, 'creator_id');
            $users_table = db_connect('default')->prefixTable('users');
            $target = array();

            if ($assigned_to) {
                $target[] = " OR $users_table.id = {$assigned_to} ";
            }
            if ($creator_id) {
                $target[] = " OR $users_table.id = {$creator_id} ";
            }

            return array_merge($where_queries, $target);
        });

        app_hooks()->add_filter('app_filter_notification_description', function ($descriptions, $notification) {
            if (!$notification || strpos($notification->event, 'organizador_task_') !== 0) {
                return $descriptions;
            }

            $tasks_model = model('Organizador\\Models\\My_tasks_model');
            $task = $tasks_model->get_one((int) $notification->task_id);
            if ($task && $task->id) {
                $descriptions[] = '<div><strong>' . app_lang('organizador_task_title') . ':</strong> ' . esc($task->title) . '</div>';
                if ($task->due_date) {
                    $descriptions[] = '<div><strong>' . app_lang('organizador_due_date') . ':</strong> ' . format_to_datetime($task->due_date) . '</div>';
                }
                if ($task->priority) {
                    $descriptions[] = '<div><strong>' . app_lang('organizador_priority') . ':</strong> ' . app_lang('organizador_priority_' . $task->priority) . '</div>';
                }
            }

            return $descriptions;
        });

        app_hooks()->add_filter('app_filter_send_email_notification', function ($email_info) {
            $notification = get_array_value($email_info, 'notification');
            if (!$notification || strpos($notification->event, 'organizador_task_') !== 0) {
                return $email_info;
            }

            $tasks_model = model('Organizador\\Models\\My_tasks_model');
            $task = $tasks_model->get_one((int) $notification->task_id);
            if (!$task || !$task->id) {
                return $email_info;
            }

            $parser_data = get_array_value($email_info, 'parser_data');
            $parser_data = is_array($parser_data) ? $parser_data : array();
            $parser_data['TASK_TITLE'] = $task->title;
            $parser_data['TASK_DESCRIPTION'] = $task->description;
            $parser_data['TASK_STATUS'] = $task->status_title ?? app_lang('organizador_status_' . $task->status);
            $parser_data['TASK_PRIORITY'] = app_lang('organizador_priority_' . $task->priority);
            $parser_data['TASK_URL'] = get_uri('organizador/tasks/view/' . $task->id);
            $parser_data['TASK_DUE_DATE'] = $task->due_date ? format_to_datetime($task->due_date) : '';
            $parser_data['TASK_CATEGORY'] = $task->category_title ? $task->category_title : '';

            $subject = app_lang('organizador_email_subject_' . str_replace('organizador_task_', '', $notification->event));
            if (!$subject) {
                $subject = app_lang('organizador_email_subject_default');
            }

            $message = app_lang('organizador_email_message_' . str_replace('organizador_task_', '', $notification->event));
            if (!$message) {
                $message = app_lang('organizador_email_message_default');
            }

            $parser = service('parser');
            $subject = $parser->setData($parser_data)->renderString($subject);
            $message = $parser->setData($parser_data)->renderString($message);

            return array(
                'notification' => $notification,
                'parser_data' => $parser_data,
                'subject' => $subject,
                'message' => $message,
                'email_options' => array('mailtype' => 'html'),
            );
        });
    }

    private static function registerEmailTemplates()
    {
        app_hooks()->add_filter('app_filter_email_templates', function ($templates) {
            if (!isset($templates['organizador']) || !is_array($templates['organizador'])) {
                $templates['organizador'] = array();
            }

            foreach (array(
                'organizador_task_created',
                'organizador_task_assigned',
                'organizador_task_updated',
                'organizador_task_due_soon',
                'organizador_task_overdue',
                'organizador_task_completed',
                'organizador_task_reminder',
            ) as $template_name) {
                $templates['organizador'][$template_name] = array(
                    'TASK_TITLE',
                    'TASK_DESCRIPTION',
                    'TASK_STATUS',
                    'TASK_PRIORITY',
                    'TASK_CATEGORY',
                    'TASK_DUE_DATE',
                    'TASK_URL',
                    'APP_TITLE',
                    'COMPANY_NAME',
                    'LOGO_URL',
                    'SIGNATURE',
                    'RECIPIENTS_EMAIL_ADDRESS',
                );
            }

            return $templates;
        });
    }

    private static function registerCronHooks()
    {
        app_hooks()->add_action('app_hook_after_cron_run', function () {
            try {
                self::runReminders();
            } catch (\Throwable $e) {
                log_message('error', '[Organizador] cron reminder hook error: ' . $e->getMessage());
            }
        });
    }
}
