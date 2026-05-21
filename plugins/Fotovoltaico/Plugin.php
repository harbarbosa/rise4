<?php

namespace Fotovoltaico;

use App\Controllers\Security_Controller;

class Plugin
{
    private static $schema_checked = false;

    private static function permissionMap()
    {
        return array(
            'dashboard' => array('fotovoltaico_view', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'products_view' => array('fotovoltaico_products_view', 'fotovoltaico_products_manage', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'products_manage' => array('fotovoltaico_products_manage', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'kits_view' => array('fotovoltaico_kits_view', 'fotovoltaico_kits_manage', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'kits_manage' => array('fotovoltaico_kits_manage', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'proposals_view' => array('fotovoltaico_proposals_view', 'fotovoltaico_proposals_create', 'fotovoltaico_proposals_manage', 'fotovoltaico_proposals_approve', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'proposals_create' => array('fotovoltaico_proposals_create', 'fotovoltaico_proposals_manage', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'proposals_manage' => array('fotovoltaico_proposals_manage', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'proposals_approve' => array('fotovoltaico_proposals_approve', 'fotovoltaico_admin'),
            'tariffs_view' => array('fotovoltaico_tariffs_view', 'fotovoltaico_tariffs_manage', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'tariffs_manage' => array('fotovoltaico_tariffs_manage', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'integrations_view' => array('fotovoltaico_integrations_view', 'fotovoltaico_integrations_manage', 'fotovoltaico_admin'),
            'integrations_manage' => array('fotovoltaico_integrations_manage', 'fotovoltaico_admin'),
            'belenus_view' => array('fotovoltaico_belenus_view', 'fotovoltaico_belenus_manage', 'fotovoltaico_integrations_view', 'fotovoltaico_integrations_manage', 'fotovoltaico_admin'),
            'belenus_manage' => array('fotovoltaico_belenus_manage', 'fotovoltaico_integrations_manage', 'fotovoltaico_admin'),
            'pdf_generate' => array('fotovoltaico_pdf_generate', 'fotovoltaico_proposals_manage', 'fotovoltaico_proposals_approve', 'fotovoltaico_manage', 'fotovoltaico_admin'),
            'audit_view' => array('fotovoltaico_audit_view', 'fotovoltaico_admin'),
            'settings' => array('fotovoltaico_admin'),
        );
    }

    public static function register()
    {
        self::registerMenus();
        self::registerPermissions();
    }

    public static function runMigrations()
    {
        try {
            $migrations = service('migrations');
            $migrations->setNamespace('Fotovoltaico');
            $migrations->latest();
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Migration hook error: ' . $e->getMessage());
        }

        self::runLocalMigrations();
        self::ensureCriticalTables();
        self::ensureAneelImportSchema();
    }

    public static function ensureSchema()
    {
        if (self::$schema_checked) {
            return;
        }

        self::$schema_checked = true;

        try {
            $db = db_connect();
            $required_tables = array(
                $db->prefixTable('fv_product_categories'),
                $db->prefixTable('fv_products'),
                $db->prefixTable('fv_kits'),
                $db->prefixTable('fv_kit_items'),
                $db->prefixTable('fv_distributors'),
                $db->prefixTable('fv_tariffs'),
                $db->prefixTable('fv_proposals'),
                $db->prefixTable('fv_proposal_versions'),
                $db->prefixTable('fv_proposal_snapshots'),
                    $db->prefixTable('fv_integration_logs'),
                    $db->prefixTable('fv_external_cache'),
                    $db->prefixTable('fv_audit_logs'),
                    $db->prefixTable('fv_settings'),
                    $db->prefixTable('fv_import_logs'),
                    $db->prefixTable('fv_belenus_cache'),
                    $db->prefixTable('fv_belenus_import_logs'),
                );

            foreach ($required_tables as $table) {
                if (!$db->tableExists($table)) {
                    self::runMigrations();
                    break;
                }
            }

            self::ensureAneelImportSchema();
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Schema guard error: ' . $e->getMessage());
        }
    }

    public static function ensureAneelImportSchema()
    {
        try {
            $db = db_connect();
            $distributors = $db->prefixTable('fv_distributors');
            $tariffs = $db->prefixTable('fv_tariffs');
            $import_logs = $db->prefixTable('fv_import_logs');
            $charset = $db->charset ?: 'utf8';
            $collation = $db->DBCollat ?: 'utf8_general_ci';
            $charset_sql = "DEFAULT CHARSET={$charset} COLLATE={$collation}";

            if ($db->tableExists($distributors)) {
                self::ensureColumns($db, $distributors, array(
                    'aneel_code' => "ALTER TABLE `{$distributors}` ADD `aneel_code` VARCHAR(50) NULL AFTER `document`",
                    'agent_type' => "ALTER TABLE `{$distributors}` ADD `agent_type` VARCHAR(30) NOT NULL DEFAULT 'desconhecido' AFTER `source`",
                    'show_in_registration' => "ALTER TABLE `{$distributors}` ADD `show_in_registration` TINYINT(1) NOT NULL DEFAULT 1 AFTER `active`",
                    'origin_hash' => "ALTER TABLE `{$distributors}` ADD `origin_hash` VARCHAR(64) NULL AFTER `raw_payload`",
                    'sync_notes' => "ALTER TABLE `{$distributors}` ADD `sync_notes` TEXT NULL AFTER `notes`",
                ));
            }

            if ($db->tableExists($tariffs)) {
                self::ensureColumns($db, $tariffs, array(
                    'tariff_class' => "ALTER TABLE `{$tariffs}` ADD `tariff_class` VARCHAR(120) NULL AFTER `subgroup`",
                    'tariff_subclass' => "ALTER TABLE `{$tariffs}` ADD `tariff_subclass` VARCHAR(120) NULL AFTER `tariff_class`",
                    'group_name' => "ALTER TABLE `{$tariffs}` ADD `group_name` VARCHAR(40) NULL AFTER `tariff_subclass`",
                    'time_slot' => "ALTER TABLE `{$tariffs}` ADD `time_slot` VARCHAR(80) NULL AFTER `group_name`",
                    'unit' => "ALTER TABLE `{$tariffs}` ADD `unit` VARCHAR(40) NULL AFTER `time_slot`",
                    'resolution' => "ALTER TABLE `{$tariffs}` ADD `resolution` VARCHAR(255) NULL AFTER `unit`",
                    'tariff_detail' => "ALTER TABLE `{$tariffs}` ADD `tariff_detail` VARCHAR(120) NULL AFTER `resolution`",
                    'tariff_base' => "ALTER TABLE `{$tariffs}` ADD `tariff_base` VARCHAR(120) NULL AFTER `tariff_detail`",
                    'source' => "ALTER TABLE `{$tariffs}` ADD `source` VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER `flag_value`",
                    'origin_hash' => "ALTER TABLE `{$tariffs}` ADD `origin_hash` VARCHAR(64) NULL AFTER `source`",
                    'sync_notes' => "ALTER TABLE `{$tariffs}` ADD `sync_notes` TEXT NULL AFTER `notes`",
                    'is_current' => "ALTER TABLE `{$tariffs}` ADD `is_current` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`",
                ));
            }

            if (!$db->tableExists($import_logs)) {
                $db->query("CREATE TABLE IF NOT EXISTS `{$import_logs}` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `import_type` VARCHAR(80) NOT NULL,
                    `source_type` VARCHAR(20) NOT NULL DEFAULT 'url',
                    `source_path` VARCHAR(255) NULL DEFAULT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'completed',
                    `rows_read` INT(11) NOT NULL DEFAULT 0,
                    `created_count` INT(11) NOT NULL DEFAULT 0,
                    `updated_count` INT(11) NOT NULL DEFAULT 0,
                    `ignored_count` INT(11) NOT NULL DEFAULT 0,
                    `error_count` INT(11) NOT NULL DEFAULT 0,
                    `errors_json` LONGTEXT NULL,
                    `summary_json` LONGTEXT NULL,
                    `started_at` DATETIME NULL DEFAULT NULL,
                    `finished_at` DATETIME NULL DEFAULT NULL,
                    `created_by` INT(11) NULL DEFAULT NULL,
                    `created_at` DATETIME NULL DEFAULT NULL,
                    `updated_at` DATETIME NULL DEFAULT NULL,
                    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    KEY `import_type` (`import_type`),
                    KEY `status` (`status`),
                    KEY `started_at` (`started_at`)
                ) ENGINE=InnoDB {$charset_sql}");
            }
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] ANEEL schema fallback error: ' . $e->getMessage());
        }
    }

    private static function runLocalMigrations()
    {
        $migrations = array(
            '2026-04-17-000001_CreateFotovoltaicoCatalog.php' => 'Fotovoltaico\\Database\\Migrations\\CreateFotovoltaicoCatalog',
            '2026-04-17-000002_CreateFotovoltaicoProducts.php' => 'Fotovoltaico\\Database\\Migrations\\CreateFotovoltaicoProducts',
            '2026-04-17-000003_CreateFotovoltaicoKits.php' => 'Fotovoltaico\\Database\\Migrations\\CreateFotovoltaicoKits',
            '2026-04-17-000004_CreateFotovoltaicoProposals.php' => 'Fotovoltaico\\Database\\Migrations\\CreateFotovoltaicoProposals',
            '2026-04-17-000005_CreateFotovoltaicoOperationalTables.php' => 'Fotovoltaico\\Database\\Migrations\\CreateFotovoltaicoOperationalTables',
            '2026-04-17-000006_UpdateFotovoltaicoDistributorsTariffs.php' => 'Fotovoltaico\\Database\\Migrations\\UpdateFotovoltaicoDistributorsTariffs',
            '2026-04-17-000007_UpdateFotovoltaicoProposalsForVersioning.php' => 'Fotovoltaico\\Database\\Migrations\\UpdateFotovoltaicoProposalsForVersioning',
            '2026-04-20-000008_AddEnergyApiIntegrationSupport.php' => 'Fotovoltaico\\Database\\Migrations\\AddEnergyApiIntegrationSupport',
            '2026-04-20-000009_AddAneelImportSupport.php' => 'Fotovoltaico\\Database\\Migrations\\AddAneelImportSupport',
            '2026-05-19-000010_AddBelenusIntegrationSupport.php' => 'Fotovoltaico\\Database\\Migrations\\AddBelenusIntegrationSupport',
        );

        foreach ($migrations as $file => $class) {
            try {
                require_once __DIR__ . '/Database/Migrations/' . $file;
                if (class_exists($class)) {
                    (new $class())->up();
                }
            } catch (\Throwable $e) {
                log_message('error', '[Fotovoltaico] Local migration error in ' . $file . ': ' . $e->getMessage());
            }
        }
    }

    private static function ensureCriticalTables()
    {
        try {
            $db = db_connect();
            $charset = $db->charset ?: 'utf8';
            $collation = $db->DBCollat ?: 'utf8_general_ci';
            $charset_sql = "DEFAULT CHARSET={$charset} COLLATE={$collation}";

            $proposals = $db->prefixTable('fv_proposals');
            if (!$db->tableExists($proposals)) {
                $db->query("CREATE TABLE IF NOT EXISTS `{$proposals}` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `proposal_code` VARCHAR(80) NOT NULL,
                    `client_id` INT(11) NULL DEFAULT NULL,
                    `lead_id` INT(11) NULL DEFAULT NULL,
                    `contact_id` INT(11) NULL DEFAULT NULL,
                    `project_id` INT(11) NULL DEFAULT NULL,
                    `distributor_id` INT(11) NULL DEFAULT NULL,
                    `consumer_unit` VARCHAR(190) NULL DEFAULT NULL,
                    `consumption_avg` DECIMAL(16,3) NOT NULL DEFAULT 0,
                    `current_version` INT(11) NOT NULL DEFAULT 1,
                    `wizard_step` VARCHAR(60) NULL DEFAULT NULL,
                    `wizard_data_json` LONGTEXT NULL,
                    `title` VARCHAR(190) NOT NULL,
                    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
                    `currency` VARCHAR(10) NOT NULL DEFAULT 'BRL',
                    `subtotal` DECIMAL(16,2) NOT NULL DEFAULT 0,
                    `discount_total` DECIMAL(16,2) NOT NULL DEFAULT 0,
                    `tax_total` DECIMAL(16,2) NOT NULL DEFAULT 0,
                    `total` DECIMAL(16,2) NOT NULL DEFAULT 0,
                    `issue_date` DATE NULL DEFAULT NULL,
                    `valid_until` DATE NULL DEFAULT NULL,
                    `notes` TEXT NULL,
                    `metadata_json` LONGTEXT NULL,
                    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_by` INT(11) NULL DEFAULT NULL,
                    `created_at` DATETIME NULL DEFAULT NULL,
                    `updated_at` DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `proposal_code` (`proposal_code`),
                    KEY `client_id` (`client_id`),
                    KEY `lead_id` (`lead_id`),
                    KEY `contact_id` (`contact_id`),
                    KEY `distributor_id` (`distributor_id`),
                    KEY `status` (`status`)
                ) ENGINE=InnoDB {$charset_sql}");
            } else {
                self::ensureColumns($db, $proposals, array(
                    'contact_id' => "ALTER TABLE `{$proposals}` ADD `contact_id` INT(11) NULL DEFAULT NULL AFTER `lead_id`",
                    'distributor_id' => "ALTER TABLE `{$proposals}` ADD `distributor_id` INT(11) NULL DEFAULT NULL AFTER `project_id`",
                    'consumer_unit' => "ALTER TABLE `{$proposals}` ADD `consumer_unit` VARCHAR(190) NULL DEFAULT NULL AFTER `distributor_id`",
                    'consumption_avg' => "ALTER TABLE `{$proposals}` ADD `consumption_avg` DECIMAL(16,3) NOT NULL DEFAULT 0 AFTER `consumer_unit`",
                    'current_version' => "ALTER TABLE `{$proposals}` ADD `current_version` INT(11) NOT NULL DEFAULT 1 AFTER `consumption_avg`",
                    'wizard_step' => "ALTER TABLE `{$proposals}` ADD `wizard_step` VARCHAR(60) NULL DEFAULT NULL AFTER `current_version`",
                    'wizard_data_json' => "ALTER TABLE `{$proposals}` ADD `wizard_data_json` LONGTEXT NULL AFTER `wizard_step`",
                ));
            }

            $versions = $db->prefixTable('fv_proposal_versions');
            if (!$db->tableExists($versions)) {
                $db->query("CREATE TABLE IF NOT EXISTS `{$versions}` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `proposal_id` INT(11) NOT NULL,
                    `version_number` INT(11) NOT NULL DEFAULT 1,
                    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
                    `subtotal` DECIMAL(16,2) NOT NULL DEFAULT 0,
                    `discount_total` DECIMAL(16,2) NOT NULL DEFAULT 0,
                    `tax_total` DECIMAL(16,2) NOT NULL DEFAULT 0,
                    `total` DECIMAL(16,2) NOT NULL DEFAULT 0,
                    `result_json` LONGTEXT NULL,
                    `payload_json` LONGTEXT NULL,
                    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_by` INT(11) NULL DEFAULT NULL,
                    `created_at` DATETIME NULL DEFAULT NULL,
                    `updated_at` DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `proposal_version_unique` (`proposal_id`, `version_number`),
                    KEY `proposal_id` (`proposal_id`),
                    KEY `status` (`status`)
                ) ENGINE=InnoDB {$charset_sql}");
            }

            $snapshots = $db->prefixTable('fv_proposal_snapshots');
            if (!$db->tableExists($snapshots)) {
                $db->query("CREATE TABLE IF NOT EXISTS `{$snapshots}` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `proposal_id` INT(11) NOT NULL,
                    `proposal_version_id` INT(11) NULL DEFAULT NULL,
                    `snapshot_json` LONGTEXT NOT NULL,
                    `snapshot_hash` VARCHAR(64) NULL DEFAULT NULL,
                    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_by` INT(11) NULL DEFAULT NULL,
                    `created_at` DATETIME NULL DEFAULT NULL,
                    `updated_at` DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `proposal_id` (`proposal_id`),
                    KEY `proposal_version_id` (`proposal_version_id`)
                ) ENGINE=InnoDB {$charset_sql}");
            }
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Critical schema fallback error: ' . $e->getMessage());
        }
    }

    private static function ensureColumns($db, $table, $columns)
    {
        $existing_fields = array();
        try {
            $existing_fields = $db->getFieldNames($table);
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Unable to inspect columns for ' . $table . ': ' . $e->getMessage());
        }

        $normalized_fields = array();
        if (is_array($existing_fields)) {
            foreach ($existing_fields as $field) {
                $normalized_fields[strtolower((string) $field)] = true;
            }
        }

        foreach ($columns as $column => $sql) {
            try {
                if (!isset($normalized_fields[strtolower((string) $column)])) {
                    $db->query($sql);
                    $normalized_fields[strtolower((string) $column)] = true;
                }
            } catch (\Throwable $e) {
                if (stripos($e->getMessage(), 'Duplicate column name') !== false) {
                    $normalized_fields[strtolower((string) $column)] = true;
                    continue;
                }
                log_message('error', '[Fotovoltaico] Column fallback error for ' . $table . '.' . $column . ': ' . $e->getMessage());
            }
        }
    }

    public static function canAccessModule($login_user)
    {
        return self::hasAny($login_user, array(
            'fotovoltaico_view',
            'fotovoltaico_manage',
            'fotovoltaico_products_view',
            'fotovoltaico_products_manage',
            'fotovoltaico_kits_view',
            'fotovoltaico_kits_manage',
            'fotovoltaico_proposals_view',
            'fotovoltaico_proposals_create',
            'fotovoltaico_proposals_manage',
            'fotovoltaico_proposals_approve',
            'fotovoltaico_tariffs_view',
            'fotovoltaico_tariffs_manage',
            'fotovoltaico_integrations_view',
            'fotovoltaico_integrations_manage',
            'fotovoltaico_belenus_view',
            'fotovoltaico_belenus_manage',
            'fotovoltaico_pdf_generate',
            'fotovoltaico_audit_view',
            'fotovoltaico_admin'
        ));
    }

    public static function canViewDashboard($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['dashboard']);
    }

    public static function canViewProducts($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['products_view']);
    }

    public static function canManageProducts($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['products_manage']);
    }

    public static function canViewKits($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['kits_view']);
    }

    public static function canManageKits($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['kits_manage']);
    }

    public static function canViewProposals($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['proposals_view']);
    }

    public static function canCreateProposals($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['proposals_create']);
    }

    public static function canManageProposals($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['proposals_manage']);
    }

    public static function canApproveProposals($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['proposals_approve']);
    }

    public static function canViewDistributors($login_user)
    {
        return self::hasAny($login_user, array('fotovoltaico_tariffs_view', 'fotovoltaico_tariffs_manage', 'fotovoltaico_admin'));
    }

    public static function canViewTariffs($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['tariffs_view']);
    }

    public static function canManageTariffs($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['tariffs_manage']);
    }

    public static function canViewIntegrations($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['integrations_view']);
    }

    public static function canManageIntegrations($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['integrations_manage']);
    }

    public static function canViewBelenus($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['belenus_view']);
    }

    public static function canManageBelenus($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['belenus_manage']);
    }

    public static function canGeneratePdf($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['pdf_generate']);
    }

    public static function canViewAudit($login_user)
    {
        return self::hasAny($login_user, self::permissionMap()['audit_view']);
    }

    public static function canManageSettings($login_user)
    {
        return self::hasAny($login_user, array('fotovoltaico_admin'));
    }

    private static function hasAny($login_user, $permission_keys)
    {
        if (!$login_user) {
            return false;
        }

        if (!empty($login_user->is_admin)) {
            return true;
        }

        $permissions = $login_user->permissions ?? array();
        foreach ($permission_keys as $key) {
            if (get_array_value($permissions, $key) == '1') {
                return true;
            }
        }

        return false;
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

            $menu_items = array(
                'fotovoltaico_dashboard' => array(
                    'permission' => 'dashboard',
                    'item' => array(
                        'name' => 'fotovoltaico_dashboard',
                        'url' => 'fotovoltaico',
                        'class' => 'home'
                    )
                ),
                'fotovoltaico_products' => array(
                    'permission' => 'products_view',
                    'item' => array(
                        'name' => 'fotovoltaico_products',
                        'url' => 'fotovoltaico/products',
                        'class' => 'package'
                    )
                ),
                'fotovoltaico_kits' => array(
                    'permission' => 'kits_view',
                    'item' => array(
                        'name' => 'fotovoltaico_kits',
                        'url' => 'fotovoltaico/kits',
                        'class' => 'grid'
                    )
                ),
                'fotovoltaico_proposals' => array(
                    'permission' => 'proposals_view',
                    'item' => array(
                        'name' => 'fotovoltaico_proposals',
                        'url' => 'fotovoltaico/proposals',
                        'class' => 'file-text'
                    )
                ),
                'fotovoltaico_distributors' => array(
                    'permission' => 'tariffs_view',
                    'item' => array(
                        'name' => 'fotovoltaico_distributors',
                        'url' => 'fotovoltaico/distributors',
                        'class' => 'truck'
                    )
                ),
                'fotovoltaico_tariffs' => array(
                    'permission' => 'tariffs_view',
                    'item' => array(
                        'name' => 'fotovoltaico_tariffs',
                        'url' => 'fotovoltaico/tariffs',
                        'class' => 'dollar-sign'
                    )
                ),
                'fotovoltaico_integrations' => array(
                    'permission' => 'integrations_view',
                    'item' => array(
                        'name' => 'fotovoltaico_integrations',
                        'url' => 'fotovoltaico/integrations',
                        'class' => 'link'
                    )
                ),
                'fotovoltaico_belenus' => array(
                    'permission' => 'belenus_view',
                    'item' => array(
                        'name' => 'fotovoltaico_belenus',
                        'url' => 'fotovoltaico/belenus/settings',
                        'class' => 'shuffle'
                    )
                ),
                'fotovoltaico_audit' => array(
                    'permission' => 'audit_view',
                    'item' => array(
                        'name' => 'fotovoltaico_audit',
                        'url' => 'fotovoltaico/audit',
                        'class' => 'clipboard'
                    )
                ),
                'fotovoltaico_settings' => array(
                    'permission' => 'settings',
                    'item' => array(
                        'name' => 'fotovoltaico_settings',
                        'url' => 'fotovoltaico/settings',
                        'class' => 'settings'
                    )
                )
            );

            foreach ($menu_items as $key => $menu_item) {
                if (self::canSeeMenuItem($login_user, get_array_value($menu_item, 'permission'))) {
                    $submenu[$key] = get_array_value($menu_item, 'item');
                }
            }

            if (!$submenu) {
                return $sidebar_menu;
            }

            $sidebar_menu['fotovoltaico'] = array(
                'name' => 'fotovoltaico_menu',
                'url' => 'fotovoltaico',
                'class' => 'sun',
                'position' => 8,
                'submenu' => $submenu,
                'sub_pages' => array(
                    'fotovoltaico/index',
                    'fotovoltaico/dashboard',
                    'fotovoltaico/products',
                    'fotovoltaico/products/view',
                    'fotovoltaico/product_categories',
                    'fotovoltaico/product_categories/modal_form',
                    'fotovoltaico/kits',
                    'fotovoltaico/kits/view',
                    'fotovoltaico/proposals',
                    'fotovoltaico/proposals/view',
                    'fotovoltaico/proposal_wizard/start',
                    'fotovoltaico/proposal_wizard/step',
                    'fotovoltaico/distributors',
                    'fotovoltaico/tariffs',
                    'fotovoltaico/integrations',
                    'fotovoltaico/belenus/settings',
                    'fotovoltaico/belenus/products',
                    'fotovoltaico/belenus/kits',
                    'fotovoltaico/belenus/logs',
                    'fotovoltaico/audit',
                    'fotovoltaico/settings'
                )
            );

            return $sidebar_menu;
        });
    }

    private static function canSeeMenuItem($login_user, $permission)
    {
        switch ($permission) {
            case 'dashboard':
                return self::canViewDashboard($login_user);
            case 'products_view':
                return self::canViewProducts($login_user);
            case 'kits_view':
                return self::canViewKits($login_user);
            case 'proposals_view':
                return self::canViewProposals($login_user);
            case 'distributors':
                return self::canViewDistributors($login_user);
            case 'tariffs_view':
                return self::canViewTariffs($login_user);
            case 'integrations_view':
                return self::canViewIntegrations($login_user);
            case 'audit_view':
                return self::canViewAudit($login_user);
            case 'settings':
                return self::canManageSettings($login_user);
            default:
                return false;
        }
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

                $view_path = PLUGINPATH . 'Fotovoltaico/Views/permissions/role_permissions.php';
                if (file_exists($view_path)) {
                    include $view_path;
                    if (!defined('FOTOVOLTAICO_ROLE_PERMISSIONS_RENDERED')) {
                        define('FOTOVOLTAICO_ROLE_PERMISSIONS_RENDERED', true);
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', '[Fotovoltaico] Permissions hook error: ' . $e->getMessage());
            }
        });

        app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
            $request = \Config\Services::request();
            $permissions['fotovoltaico_view'] = $request->getPost('fotovoltaico_view') ? '1' : '';
            $permissions['fotovoltaico_manage'] = $request->getPost('fotovoltaico_manage') ? '1' : '';
            $permissions['fotovoltaico_products_view'] = $request->getPost('fotovoltaico_products_view') ? '1' : '';
            $permissions['fotovoltaico_products_manage'] = $request->getPost('fotovoltaico_products_manage') ? '1' : '';
            $permissions['fotovoltaico_kits_view'] = $request->getPost('fotovoltaico_kits_view') ? '1' : '';
            $permissions['fotovoltaico_kits_manage'] = $request->getPost('fotovoltaico_kits_manage') ? '1' : '';
            $permissions['fotovoltaico_proposals_view'] = $request->getPost('fotovoltaico_proposals_view') ? '1' : '';
            $permissions['fotovoltaico_proposals_create'] = $request->getPost('fotovoltaico_proposals_create') ? '1' : '';
            $permissions['fotovoltaico_proposals_manage'] = $request->getPost('fotovoltaico_proposals_manage') ? '1' : '';
            $permissions['fotovoltaico_proposals_approve'] = $request->getPost('fotovoltaico_proposals_approve') ? '1' : '';
            $permissions['fotovoltaico_tariffs_view'] = $request->getPost('fotovoltaico_tariffs_view') ? '1' : '';
            $permissions['fotovoltaico_tariffs_manage'] = $request->getPost('fotovoltaico_tariffs_manage') ? '1' : '';
            $permissions['fotovoltaico_integrations_view'] = $request->getPost('fotovoltaico_integrations_view') ? '1' : '';
            $permissions['fotovoltaico_integrations_manage'] = $request->getPost('fotovoltaico_integrations_manage') ? '1' : '';
            $permissions['fotovoltaico_belenus_view'] = $request->getPost('fotovoltaico_belenus_view') ? '1' : '';
            $permissions['fotovoltaico_belenus_manage'] = $request->getPost('fotovoltaico_belenus_manage') ? '1' : '';
            $permissions['fotovoltaico_pdf_generate'] = $request->getPost('fotovoltaico_pdf_generate') ? '1' : '';
            $permissions['fotovoltaico_audit_view'] = $request->getPost('fotovoltaico_audit_view') ? '1' : '';
            $permissions['fotovoltaico_admin'] = $request->getPost('fotovoltaico_admin') ? '1' : '';

            return $permissions;
        });
    }
}
