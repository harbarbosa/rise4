<?php

namespace LaudosTecnicos;

use App\Controllers\Security_Controller;

class Plugin
{
    private static bool $schema_checked = false;

    public static function register()
    {
        self::runMigrations();
        self::registerMenus();
        self::registerPermissions();
        self::registerNotificationHooks();
        self::registerAutomationHooks();
        self::ensureDefaultSettings();
    }

    public static function runMigrations()
    {
        if (self::$schema_checked) {
            return;
        }

        self::$schema_checked = true;

        try {
            $migrations = service('migrations');
            $migrations->setNamespace('LaudosTecnicos');
            $migrations->latest();
        } catch (\Throwable $e) {
            log_message('error', '[LaudosTecnicos] Migration hook error: ' . $e->getMessage());
        }
    }

    public static function canAccessModule($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_access',
            'laudostecnicos_view_dashboard',
            'laudostecnicos_view_laudos',
            'laudostecnicos_create_laudos',
            'laudostecnicos_edit_laudos',
            'laudostecnicos_change_status',
            'laudostecnicos_delete_drafts',
            'laudostecnicos_manage_categories',
            'laudostecnicos_manage_types',
            'laudostecnicos_manage_statuses',
            'laudostecnicos_manage_transitions',
            'laudostecnicos_manage_templates',
            'laudostecnicos_manage_checklists',
            'laudostecnicos_manage_measurements',
            'laudostecnicos_manage_equipments',
            'laudostecnicos_manage_norms',
            'laudostecnicos_manage_inspections',
            'laudostecnicos_manage_nonconformities',
            'laudostecnicos_manage_risk_matrix',
            'laudostecnicos_manage_action_plans',
            'laudostecnicos_manage_settings',
            'laudostecnicos_manage_api',
            'laudostecnicos_manage_ai',
            'laudostecnicos_view_reports',
            'laudostecnicos_manage_reports',
            'laudostecnicos_manage_automations',
        ));
    }

    public static function canViewDashboard($login_user)
    {
        return self::canAccessModule($login_user);
    }

    public static function canViewLaudos($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_view_laudos',
            'laudostecnicos_create_laudos',
            'laudostecnicos_edit_laudos',
            'laudostecnicos_change_status',
            'laudostecnicos_access',
        ));
    }

    public static function canManageLaudos($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_create_laudos',
            'laudostecnicos_edit_laudos',
            'laudostecnicos_change_status',
            'laudostecnicos_delete_drafts',
            'laudostecnicos_access',
        ));
    }

    public static function canCreateLaudos($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_create_laudos',
            'laudostecnicos_edit_laudos',
            'laudostecnicos_access',
        ));
    }

    public static function canEditLaudos($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_edit_laudos',
            'laudostecnicos_access',
        ));
    }

    public static function canDeleteDrafts($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_delete_drafts',
            'laudostecnicos_edit_laudos',
            'laudostecnicos_access',
        ));
    }

    public static function canChangeStatus($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_change_status',
            'laudostecnicos_edit_laudos',
            'laudostecnicos_access',
        ));
    }

    public static function canManageTypes($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_types',
            'laudostecnicos_access',
        ));
    }

    public static function canManageStatuses($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_statuses',
            'laudostecnicos_access',
        ));
    }

    public static function canManageTransitions($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_transitions',
            'laudostecnicos_manage_statuses',
            'laudostecnicos_access',
        ));
    }

    public static function canManageTemplates($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_templates',
            'laudostecnicos_access',
        ));
    }

    public static function canManageChecklists($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_checklists',
            'laudostecnicos_manage_templates',
            'laudostecnicos_access',
        ));
    }

    public static function canManageMeasurements($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_measurements',
            'laudostecnicos_manage_checklists',
            'laudostecnicos_access',
        ));
    }

    public static function canManageEquipments($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_equipments',
            'laudostecnicos_manage_measurements',
            'laudostecnicos_access',
        ));
    }

    public static function canManageNorms($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_norms',
            'laudostecnicos_manage_checklists',
            'laudostecnicos_access',
        ));
    }

    public static function canManageInspections($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_inspections',
            'laudostecnicos_view_inspections',
            'laudostecnicos_access',
        ));
    }

    public static function canManageNonconformities($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_nonconformities',
            'laudostecnicos_access',
        ));
    }

    public static function canManageRiskMatrix($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_risk_matrix',
            'laudostecnicos_manage_nonconformities',
            'laudostecnicos_access',
        ));
    }

    public static function canManageActionPlans($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_action_plans',
            'laudostecnicos_manage_nonconformities',
            'laudostecnicos_access',
        ));
    }

    public static function canManageSettings($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_settings',
            'laudostecnicos_access',
        ));
    }

    public static function canManageApi($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_api',
            'laudostecnicos_manage_settings',
            'laudostecnicos_access',
        ));
    }

    public static function canManageAi($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_ai',
            'laudostecnicos_manage_settings',
            'laudostecnicos_access',
        ));
    }

    public static function canViewReports($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_view_reports',
            'laudostecnicos_manage_reports',
            'laudostecnicos_access',
        ));
    }

    public static function canManageReports($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_reports',
            'laudostecnicos_view_reports',
            'laudostecnicos_access',
        ));
    }

    public static function canManageAutomations($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_automations',
            'laudostecnicos_manage_reports',
            'laudostecnicos_manage_api',
            'laudostecnicos_access',
        ));
    }

    public static function canViewInspections($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_view_inspections',
            'laudostecnicos_access',
        ));
    }

    public static function canManageCategories($login_user)
    {
        return self::hasAnyPermission($login_user, array(
            'laudostecnicos_manage_categories',
            'laudostecnicos_manage_types',
            'laudostecnicos_access',
        ));
    }

    private static function registerMenus()
    {
        app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
            $ci = new Security_Controller(false);
            $login_user = $ci->login_user ?? null;

            if (!$login_user || $login_user->user_type !== 'staff' || !self::canAccessModule($login_user)) {
                return $sidebar_menu;
            }

            $submenu = array();

            if (self::canViewDashboard($login_user)) {
                $submenu['laudostecnicos_dashboard'] = array('name' => 'laudostecnicos_dashboard', 'url' => 'laudostecnicos', 'class' => 'home');
            }

            if (self::canViewLaudos($login_user)) {
                $submenu['laudostecnicos_laudos'] = array('name' => 'laudostecnicos_laudos', 'url' => 'laudostecnicos/laudos', 'class' => 'file-text');
            }

            if (self::canManageCategories($login_user) || self::canManageTypes($login_user)) {
                $submenu['laudostecnicos_types'] = array('name' => 'laudostecnicos_types', 'url' => 'laudostecnicos/tipos', 'class' => 'tag');
                $submenu['laudostecnicos_categories'] = array('name' => 'laudostecnicos_categories', 'url' => 'laudostecnicos/categorias', 'class' => 'layers');
            }

            if (self::canManageStatuses($login_user) || self::canManageTransitions($login_user)) {
                $submenu['laudostecnicos_statuses'] = array('name' => 'laudostecnicos_statuses', 'url' => 'laudostecnicos/statuses', 'class' => 'shuffle');
                $submenu['laudostecnicos_transitions'] = array('name' => 'laudostecnicos_transitions', 'url' => 'laudostecnicos/transitions', 'class' => 'git-merge');
                $submenu['laudostecnicos_status_history'] = array('name' => 'laudostecnicos_status_history', 'url' => 'laudostecnicos/historico-status', 'class' => 'clock');
            }

            if (self::canManageTemplates($login_user)) {
                $submenu['laudostecnicos_templates'] = array('name' => 'laudostecnicos_templates', 'url' => 'laudostecnicos/templates', 'class' => 'layout');
            }

            if (self::canManageChecklists($login_user) || self::canManageMeasurements($login_user) || self::canManageEquipments($login_user) || self::canManageNorms($login_user)) {
                $submenu['laudostecnicos_checklists'] = array('name' => 'laudostecnicos_checklists', 'url' => 'laudostecnicos/checklists', 'class' => 'check-square');
                $submenu['laudostecnicos_measurement_types'] = array('name' => 'laudostecnicos_measurement_types', 'url' => 'laudostecnicos/tipos-medicao', 'class' => 'sliders');
                $submenu['laudostecnicos_measurements'] = array('name' => 'laudostecnicos_measurements', 'url' => 'laudostecnicos/medicoes', 'class' => 'activity');
                $submenu['laudostecnicos_equipments'] = array('name' => 'laudostecnicos_equipments', 'url' => 'laudostecnicos/equipamentos', 'class' => 'tool');
                $submenu['laudostecnicos_norms'] = array('name' => 'laudostecnicos_norms', 'url' => 'laudostecnicos/normas', 'class' => 'book');
            }

            if (self::canViewInspections($login_user)) {
                $submenu['laudostecnicos_inspections'] = array('name' => 'laudostecnicos_inspections', 'url' => 'laudostecnicos/inspecoes', 'class' => 'check-square');
            }

            if (self::canManageNonconformities($login_user) || self::canManageRiskMatrix($login_user) || self::canManageActionPlans($login_user)) {
                $submenu['laudostecnicos_nonconformities'] = array('name' => 'laudostecnicos_nonconformities', 'url' => 'laudostecnicos/nao-conformidades', 'class' => 'alert-triangle');
            }

            if (self::canManageSettings($login_user)) {
                $submenu['laudostecnicos_settings'] = array('name' => 'laudostecnicos_settings', 'url' => 'laudostecnicos/configuracoes', 'class' => 'settings');
            }

            if (self::canManageAi($login_user)) {
                $submenu['laudostecnicos_ai'] = array('name' => 'laudostecnicos_ai', 'url' => 'laudostecnicos/ia', 'class' => 'cpu');
            }

            if (self::canViewReports($login_user) || self::canManageReports($login_user)) {
                $submenu['laudostecnicos_reports'] = array('name' => 'laudostecnicos_reports', 'url' => 'laudostecnicos/relatorios', 'class' => 'bar-chart');
            }

            if (!$submenu) {
                return $sidebar_menu;
            }

            $sidebar_menu['laudostecnicos'] = array(
                'name' => 'laudostecnicos_menu',
                'url' => 'laudostecnicos',
                'class' => 'clipboard',
                'position' => 8,
                'submenu' => $submenu,
                'sub_pages' => array(
                    'laudostecnicos/index',
                    'laudostecnicos/laudos',
                    'laudostecnicos/laudos/context',
                    'laudostecnicos/laudos/view',
                    'laudostecnicos/laudos/modal_form',
                    'laudostecnicos/laudos/save',
                    'laudostecnicos/laudos/delete',
                    'laudostecnicos/laudos/duplicate',
                    'laudostecnicos/laudos/change_status',
                    'laudostecnicos/laudos/list_data',
                    'laudostecnicos/tipos',
                    'laudostecnicos/categorias',
                    'laudostecnicos/statuses',
                    'laudostecnicos/transitions',
                    'laudostecnicos/historico-status',
                    'laudostecnicos/templates',
                    'laudostecnicos/templates/list_data',
                    'laudostecnicos/templates/modal_form',
                    'laudostecnicos/templates/save',
                    'laudostecnicos/templates/new_version',
                    'laudostecnicos/templates/preview',
                    'laudostecnicos/templates/publish',
                    'laudostecnicos/templates/archive',
                    'laudostecnicos/templates/toggle_status',
                    'laudostecnicos/templates/delete',
                    'laudostecnicos/checklists',
                    'laudostecnicos/checklists/list_data',
                    'laudostecnicos/checklists/modal_form',
                    'laudostecnicos/checklists/save',
                    'laudostecnicos/checklists/duplicate',
                    'laudostecnicos/checklists/publish',
                    'laudostecnicos/checklists/archive',
                    'laudostecnicos/checklists/toggle_status',
                    'laudostecnicos/checklists/delete',
                    'laudostecnicos/checklists/export',
                    'laudostecnicos/checklists/import',
                    'laudostecnicos/checklists/progress',
                    'laudostecnicos/checklists/save_responses',
                    'laudostecnicos/tipos-medicao',
                    'laudostecnicos/tipos-medicao/list_data',
                    'laudostecnicos/tipos-medicao/modal_form',
                    'laudostecnicos/tipos-medicao/save',
                    'laudostecnicos/tipos-medicao/toggle_status',
                    'laudostecnicos/tipos-medicao/delete',
                    'laudostecnicos/medicoes',
                    'laudostecnicos/medicoes/list_data',
                    'laudostecnicos/medicoes/modal_form',
                    'laudostecnicos/medicoes/save',
                    'laudostecnicos/equipamentos',
                    'laudostecnicos/equipamentos/list_data',
                    'laudostecnicos/equipamentos/modal_form',
                    'laudostecnicos/equipamentos/save',
                    'laudostecnicos/equipamentos/toggle_status',
                    'laudostecnicos/equipamentos/delete',
                    'laudostecnicos/normas',
                    'laudostecnicos/normas/list_data',
                    'laudostecnicos/normas/modal_form',
                    'laudostecnicos/normas/save',
                    'laudostecnicos/normas/toggle_status',
                    'laudostecnicos/normas/delete',
                    'laudostecnicos/normas/link',
                    'laudostecnicos/inspecoes',
                    'laudostecnicos/inspecoes/list_data',
                    'laudostecnicos/inspecoes/modal_form',
                    'laudostecnicos/inspecoes/save',
                    'laudostecnicos/inspecoes/agenda',
                    'laudostecnicos/inspecoes/agenda_events',
                    'laudostecnicos/inspecoes/view',
                    'laudostecnicos/inspecoes/checkin',
                    'laudostecnicos/inspecoes/checkout',
                    'laudostecnicos/inspecoes/start',
                    'laudostecnicos/inspecoes/pause',
                    'laudostecnicos/inspecoes/finish',
                    'laudostecnicos/inspecoes/improductive',
                    'laudostecnicos/inspecoes/upload_photo',
                    'laudostecnicos/inspecoes/set_cover',
                    'laudostecnicos/inspecoes/reorder_photos',
                    'laudostecnicos/inspecoes/delete_photo',
                    'laudostecnicos/inspecoes/validate_completion',
                    'laudostecnicos/nao-conformidades',
                    'laudostecnicos/nao-conformidades/list_data',
                    'laudostecnicos/nao-conformidades/plans_list_data',
                    'laudostecnicos/nao-conformidades/matrix_list_data',
                    'laudostecnicos/nao-conformidades/modal_form',
                    'laudostecnicos/nao-conformidades/plan_modal_form',
                    'laudostecnicos/nao-conformidades/matrix_modal_form',
                    'laudostecnicos/nao-conformidades/save',
                    'laudostecnicos/nao-conformidades/save_plan',
                    'laudostecnicos/nao-conformidades/save_matrix',
                    'laudostecnicos/nao-conformidades/create_task',
                    'laudostecnicos/nao-conformidades/sync_task',
                    'laudostecnicos/nao-conformidades/validate',
                    'laudostecnicos/nao-conformidades/delete',
                    'laudostecnicos/configuracoes',
                    'laudostecnicos/configuracoes/save',
                    'laudostecnicos/ia',
                    'laudostecnicos/ia/save_settings',
                    'laudostecnicos/ia/save_prompt',
                    'laudostecnicos/ia/generate',
                    'laudostecnicos/relatorios',
                    'laudostecnicos/relatorios/print',
                    'laudostecnicos/relatorios/pdf',
                    'laudostecnicos/relatorios/export-csv',
                    'laudostecnicos/relatorios/export-xls',
                    'api/laudos/v1',
                ),
            );

            return $sidebar_menu;
        });

        app_hooks()->add_filter('app_filter_client_details_ajax_tab', function ($tabs, $client_id) {
            $login_user = (new Security_Controller(false))->login_user ?? null;
            if (!$login_user || $login_user->user_type !== 'staff' || !self::canViewLaudos($login_user)) {
                return $tabs;
            }

            $tabs['laudostecnicos_client_laudos'] = array(
                'title' => app_lang('laudostecnicos_laudos'),
                'url' => get_uri('laudostecnicos/laudos/context/client/' . (int) $client_id),
                'target' => 'laudostecnicos-client-laudos',
            );

            return $tabs;
        }, 10, 2);

        app_hooks()->add_filter('app_filter_team_members_project_details_tab', function ($tabs, $project_id) {
            $login_user = (new Security_Controller(false))->login_user ?? null;
            if (!$login_user || $login_user->user_type !== 'staff' || !self::canViewLaudos($login_user)) {
                return $tabs;
            }

            $tabs['laudostecnicos_project_laudos'] = array(
                'title' => app_lang('laudostecnicos_laudos'),
                'url' => get_uri('laudostecnicos/laudos/context/project/' . (int) $project_id),
                'target' => 'laudostecnicos-project-laudos',
            );

            return $tabs;
        }, 10, 2);
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

                $view_path = PLUGINPATH . 'LaudosTecnicos/Views/permissions/role_permissions.php';
                if (file_exists($view_path)) {
                    include $view_path;
                    if (!defined('LAUDOSTECNICOS_ROLE_PERMISSIONS_RENDERED')) {
                        define('LAUDOSTECNICOS_ROLE_PERMISSIONS_RENDERED', true);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[LaudosTecnicos] Permissions hook error: ' . $e->getMessage());
            }
        });

        app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
            $request = \Config\Services::request();
            $keys = laudostecnicos_permission_keys();

            foreach ($keys as $key) {
                $permissions[$key] = $request->getPost($key) ? '1' : '';
            }

            return $permissions;
        });
    }

    private static function ensureDefaultSettings()
    {
        try {
            $db = db_connect('default');
            $table = $db->prefixTable('laudo_settings');
            if (!$db->tableExists($table)) {
                return;
            }

            $defaults = laudostecnicos_default_settings();
            foreach ($defaults as $key => $value) {
                $exists = $db->table($table)->where('setting_name', $key)->get()->getRow();
                if ($exists) {
                    continue;
                }

                $db->table($table)->insert(array(
                    'setting_name' => $key,
                    'setting_value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => \get_current_utc_time(),
                    'updated_at' => \get_current_utc_time(),
                ));
            }
        } catch (\Throwable $e) {
            log_message('error', '[LaudosTecnicos] Default settings seed error: ' . $e->getMessage());
        }
    }

    private static function registerNotificationHooks()
    {
        app_hooks()->add_filter('app_filter_notification_config', function ($events) {
            $laudo_link = function ($options) {
                $laudo_id = 0;
                if (is_object($options) && isset($options->laudo_id)) {
                    $laudo_id = (int) $options->laudo_id;
                } elseif (is_array($options) && isset($options['laudo_id'])) {
                    $laudo_id = (int) $options['laudo_id'];
                }

                $url = $laudo_id ? get_uri('laudostecnicos/laudos/view/' . $laudo_id) : get_uri('laudostecnicos/laudos');
                return array('url' => $url);
            };

            $portal_link = function ($options) {
                $share_token = '';
                if (is_object($options) && isset($options->share_token)) {
                    $share_token = (string) $options->share_token;
                } elseif (is_array($options) && isset($options['share_token'])) {
                    $share_token = (string) $options['share_token'];
                }

                $url = $share_token ? get_uri('laudostecnicos/laudos/share/' . $share_token) : get_uri('laudostecnicos/portal');
                return array('url' => $url);
            };

            foreach (array(
                'laudo_document_emitted',
                'laudo_document_sent',
                'laudo_document_viewed',
                'laudo_document_downloaded',
                'laudo_document_accepted',
                'laudo_document_rejected',
                'laudo_document_feedback_added',
                'laudo_document_link_expiring',
            ) as $event) {
                $events[$event] = array(
                    'notify_to' => array('team_members', 'team'),
                    'info' => in_array($event, array('laudo_document_viewed', 'laudo_document_downloaded', 'laudo_document_accepted', 'laudo_document_rejected', 'laudo_document_feedback_added', 'laudo_document_link_expiring'), true) ? $portal_link : $laudo_link,
                );
            }

            return $events;
        });

        app_hooks()->add_filter('app_filter_notification_description', function ($descriptions, $notification) {
            if (!$notification || strpos($notification->event, 'laudo_document_') !== 0) {
                return $descriptions;
            }

            $laudo_id = (int) ($notification->laudo_id ?? 0);
            if ($laudo_id) {
                try {
                    $laudo = model('LaudosTecnicos\\Models\\Laudos_model')->get_one_with_details($laudo_id);
                    if ($laudo && $laudo->id) {
                        $descriptions[] = '<div><strong>Laudo:</strong> ' . esc(($laudo->number ?? '#') . ' - ' . ($laudo->title ?? '')) . '</div>';
                        $descriptions[] = '<div><strong>Cliente:</strong> ' . esc($laudo->client_name ?? '-') . '</div>';
                    }
                } catch (\Throwable $e) {
                    log_message('error', '[LaudosTecnicos] notification description hook error: ' . $e->getMessage());
                }
            }

            if (!empty($notification->share_token)) {
                $descriptions[] = '<div><strong>Link:</strong> ' . esc((string) $notification->share_token) . '</div>';
            }

            if (!empty($notification->comment)) {
                $descriptions[] = '<div><strong>Comentario:</strong> ' . esc((string) $notification->comment) . '</div>';
            }

            return $descriptions;
        });

        app_hooks()->add_filter('app_filter_email_templates', function ($templates) {
            if (!isset($templates['laudostecnicos']) || !is_array($templates['laudostecnicos'])) {
                $templates['laudostecnicos'] = array();
            }

            foreach (array(
                'laudo_document_emitted',
                'laudo_document_sent',
                'laudo_document_viewed',
                'laudo_document_downloaded',
                'laudo_document_accepted',
                'laudo_document_rejected',
                'laudo_document_feedback_added',
                'laudo_document_link_expiring',
            ) as $template_name) {
                $templates['laudostecnicos'][$template_name] = array(
                    'LAUDO_NUMBER',
                    'LAUDO_TITLE',
                    'LAUDO_REVISION',
                    'LAUDO_CLIENT',
                    'LAUDO_TYPE',
                    'LAUDO_STATUS',
                    'LAUDO_URL',
                    'PUBLIC_URL',
                    'SHARE_URL',
                    'COMMENT',
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

    private static function registerAutomationHooks()
    {
        app_hooks()->add_action('app_hook_after_cron_run', function () {
            try {
                self::runAutomations();
            } catch (\Throwable $e) {
                log_message('error', '[LaudosTecnicos] automation cron hook error: ' . $e->getMessage());
            }
        });
    }

    private static function runAutomations()
    {
        try {
            $db = db_connect('default');
            $laudos_table = $db->prefixTable('laudos');
            $equipments_table = $db->prefixTable('laudo_equipments');
            $shares_table = $db->prefixTable('laudo_document_shares');
            $plans_table = $db->prefixTable('laudo_action_plans');

            $expiring_laudos = 0;
            if ($db->tableExists($laudos_table)) {
                $expiring_laudos = (int) $db->query("SELECT COUNT(*) AS total FROM $laudos_table WHERE deleted = 0 AND validity_date IS NOT NULL AND validity_date >= CURDATE() AND validity_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->getRow()->total;
            }

            $expired_equipment = 0;
            if ($db->tableExists($equipments_table)) {
                $rows = $db->table($equipments_table)->where('deleted', 0)->get()->getResult();
                $model = model('LaudosTecnicos\\Models\\LaudoEquipments_model');
                foreach ($rows as $row) {
                    if ($model && $model->calibration_status($row) === 'expired') {
                        $expired_equipment++;
                    }
                }
            }

            $expired_links = 0;
            if ($db->tableExists($shares_table)) {
                $expired_links = (int) $db->query("SELECT COUNT(*) AS total FROM $shares_table WHERE deleted = 0 AND expires_at IS NOT NULL AND expires_at < " . $db->escape(\get_current_utc_time()))->getRow()->total;
            }

            $late_plans = 0;
            if ($db->tableExists($plans_table)) {
                $late_plans = (int) $db->query("SELECT COUNT(*) AS total FROM $plans_table WHERE deleted = 0 AND deadline IS NOT NULL AND deadline < CURDATE() AND status NOT IN ('done','validated','canceled')")->getRow()->total;
            }

            log_message('info', '[LaudosTecnicos] automation summary: expiring_laudos=' . $expiring_laudos . ', expired_equipment=' . $expired_equipment . ', expired_links=' . $expired_links . ', late_plans=' . $late_plans);
        } catch (\Throwable $e) {
            log_message('error', '[LaudosTecnicos] automation summary error: ' . $e->getMessage());
        }
    }

    private static function hasAnyPermission($login_user, $keys)
    {
        if (!$login_user) {
            return false;
        }

        if (!empty($login_user->is_admin)) {
            return true;
        }

        $permissions = $login_user->permissions ?? array();
        foreach ((array) $keys as $key) {
            if (get_array_value($permissions, $key) == '1') {
                return true;
            }
        }

        return false;
    }
}
