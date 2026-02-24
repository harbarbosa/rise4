<?php

use App\Controllers\Settings;

defined('PLUGINPATH') or exit('No direct script access allowed');






/*
  Plugin Name: ProjectAnalizer
  Description: Este é um plugin para analise de resultados dos projetos.
  Version: 1.0
  Requires at least: 3.0
  Author: Henrique Barbpsa
  Author URL: https://www.alfahp.com.br
 */

//add menu item to left menu

    
app_hooks()->add_action('app_hook_after_signin', function () {
 
    $project_tabs = 'projectanalizer,etapas,tasks,tasks_kanban,evolution_project,evolucao_ff,notes,files,comments,teamactivities';
 

    $save_setting = new \App\Models\Settings_model();

    $save_setting->save_setting('project_tab_order', $project_tabs);


       


});

// Garantir que a tab nativa (gantt/evolutivo) fique oculta e que a tab
// "evolucao_ff" exista mesmo sem precisar sair/entrar no sistema.
app_hooks()->add_action('app_hook_before_app_access', function () {
    try {
        $desired_default = 'projectanalizer,etapas,tasks,tasks_kanban,evolution_project,evolucao_ff,notes,files,comments,teamactivities';

        $normalize = function ($value) use ($desired_default) {
            $value = is_string($value) ? trim($value) : "";
            if (!$value) {
                return $desired_default;
            }

            $tabs = array_values(array_filter(array_map("trim", explode(",", $value))));

            // Remover a tab nativa de gantt/evolutivo (se existir)
            $tabs = array_values(array_diff($tabs, array("gantt")));

            // Garantir que a nova tab exista
            if (!in_array("evolucao_ff", $tabs, true)) {
                $pos = array_search("etapas", $tabs, true);
                if ($pos === false) {
                    $tabs[] = "evolucao_ff";
                } else {
                    array_splice($tabs, $pos + 1, 0, array("evolucao_ff"));
                }
            }
            if (!in_array("evolution_project", $tabs, true)) {
                $pos = array_search("evolucao_ff", $tabs, true);
                if ($pos === false) {
                    $tabs[] = "evolution_project";
                } else {
                    array_splice($tabs, $pos + 1, 0, array("evolution_project"));
                }
            }

            // Se não tiver nenhuma das tabs do plugin, forçar o padrão do plugin
            if (!in_array("projectanalizer", $tabs, true)) {
                return $desired_default;
            }

            return implode(",", $tabs);
        };

        $Settings_model = new \App\Models\Settings_model();

        $current_staff = get_setting("project_tab_order");
        $normalized_staff = $normalize($current_staff);
        if ($normalized_staff !== $current_staff) {
            $Settings_model->save_setting("project_tab_order", $normalized_staff);
        }

        $current_client = get_setting("project_tab_order_of_clients");
        if ($current_client) {
            $normalized_client = $normalize($current_client);
            if ($normalized_client !== $current_client) {
                $Settings_model->save_setting("project_tab_order_of_clients", $normalized_client);
            }
        }
    } catch (\Throwable $e) {
        log_message("error", "[ProjectAnalizer] Failed to normalize project tabs: " . $e->getMessage());
    }
});

// Fallback visual: caso algum cache ainda mostre a tab gantt, esconder via CSS.
app_hooks()->add_action('app_hook_head_extension', function () {
    echo "<style>#project-tabs a[data-bs-target='#project-gantt-section']{display:none !important;}</style>";
    ?>
    <script type="text/javascript">
        (function () {
            var syncing = false;

            function isProjectModalOpen($modal) {
                return $modal.find("#project-form").length > 0;
            }

            function rebuildCostCenterSelect($select, items) {
                if (!$select.length) {
                    return;
                }
                var current = $select.val();
                $select.empty();
                $select.append($("<option/>").val("").text("-"));
                (items || []).forEach(function (item) {
                    $select.append($("<option/>").val(item.id).text(item.title));
                });
                if (current) {
                    $select.val(current);
                }
                if ($select.hasClass("select2-hidden-accessible")) {
                    $select.trigger("change.select2");
                }
            }

            function syncCostCenters($modal) {
                if (syncing) {
                    return;
                }
                syncing = true;

                $.ajax({
                    url: "<?php echo get_uri('projectanalizer/cost_centers/sync'); ?>",
                    type: "POST",
                    dataType: "json"
                }).done(function (res) {
                    if (res && res.cost_centers) {
                        rebuildCostCenterSelect($modal.find("#cost_center_id"), res.cost_centers);
                    }
                }).always(function () {
                    syncing = false;
                });
            }

            $(document).on("shown.bs.modal", "#ajaxModal", function () {
                var $modal = $(this);
                if (isProjectModalOpen($modal)) {
                    syncCostCenters($modal);
                }
            });
        })();
    </script>
    <?php
});

app_hooks()->add_filter('app_filter_staff_left_menu', function ($sidebar_menu) {
    $sidebar_menu["projectanalizer"] = array(
        "name" => "projectanalizer",
        "url" => "projectanalizer",
        "class" => "bar-chart-2",
        "position" => 3,
    );

    return $sidebar_menu;
});

app_hooks()->add_filter('app_filter_team_members_project_details_tab', function ($project_tabs_of_hook_of_staff, $project_id = 0) {
    $project_tabs_of_hook_of_staff["projectanalizer"] = "projectanalizer/overview/".$project_id;
    $project_tabs_of_hook_of_staff["tasks"] = "projectanalizer/tasks/".$project_id;
    $project_tabs_of_hook_of_staff["etapas"] = "projectanalizer/etapas/".$project_id;
    // rota conforme padrão solicitado: /projects/{project_id}/projectanalizer/evolucao
    $project_tabs_of_hook_of_staff["evolucao_ff"] = "projectanalizer/evolucao/".$project_id;
    $project_tabs_of_hook_of_staff["evolution_project"] = "projectanalizer/evolution_project/".$project_id;
    $project_tabs_of_hook_of_staff["teamactivities"] = "projectanalizer/timesheets/".$project_id;
    $project_tabs_of_hook_of_staff["project_items"] = "projectanalizer/projectitens/".$project_id;
    //$project_tabs_of_hook_of_staff["my_tab_another_title_with_available_language_key_value"] = "my_plugin/my_another_tab_url";

    return $project_tabs_of_hook_of_staff;
});



//add admin setting menu item
app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu["plugins"][] = array("name" => "projectanalizer", "url" => "projectanalizer_settings");
    return $settings_menu;
});

//install dependencies
register_installation_hook("ProjectAnalizer", function ($item_purchase_code) {
    

    $project_tabs = 'projectanalizer,etapas,tasks,tasks_kanban,evolution_project,evolucao_ff,notes,files,comments,teamactivities';
 

    $save_setting = new \App\Models\Settings_model();

    $save_setting->save_setting('project_tab_order', $project_tabs);
   
    if (!get_setting("projectanalizer_cron_key")) {
        $save_setting->save_setting("projectanalizer_cron_key", "");
    }

    $this_is_required = true;
    if (!$this_is_required) {
        echo json_encode(array("success" => false, "message" => "This is required!"));
        exit();
    }

    $db = db_connect('default');
        $dbprefix = get_db_prefix();

        // 1️⃣ — ADD CUSTOM FIELD "Custo Horas"
        $sql = "INSERT INTO `" . $dbprefix . "custom_fields` 
                (title, title_language_key, placeholder_language_key, show_in_embedded_form, placeholder, template_variable_name, options, field_type, related_to, sort, required, add_filter, show_in_table, show_in_invoice, show_in_estimate, show_in_contract, show_in_order, show_in_proposal, visible_to_admins_only, hide_from_clients, disable_editing_by_clients, show_on_kanban_card, deleted, show_in_subscription)
                VALUES 
                ('Custo Horas', 'hour_salary', 'hour_salary', 0, 'Custo de Horas', 'HOUR_SALARY', '', 'number', 'team_members', 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
                ON DUPLICATE KEY UPDATE title = VALUES(title);";
        $db->query($sql);

        // 2️⃣ — ADD NOVAS COLUNAS EM rise_project_time
        $columns = [
            "atividade_realizada VARCHAR(5000) NULL",
            "observacoes VARCHAR(5000) NULL",
            "tempo_manha VARCHAR(50) NULL",
            "tempo_tarde VARCHAR(50) NULL",
            "tempo_noite VARCHAR(50) NULL"
        ];

        foreach ($columns as $column) {
            $check_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "project_time` LIKE '" . explode(" ", $column)[0] . "'")->getResult();
            if (empty($check_column)) {
                $db->query("ALTER TABLE `" . $dbprefix . "project_time` ADD COLUMN " . $column . ";");
            }
        }
        // 3) ADD CENTRO DE CUSTO NA TABELA projects
        $project_cost_center_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "projects` LIKE 'cost_center_id'")->getResult();
        if (empty($project_cost_center_column)) {
            $db->query("ALTER TABLE `" . $dbprefix . "projects` ADD COLUMN `cost_center_id` INT(11) NULL DEFAULT NULL;");
        }
        // 9) ADD DURACAO NA TABELA tasks
        $task_duration_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "tasks` LIKE 'duration_days'")->getResult();
        if (empty($task_duration_column)) {
            $db->query("ALTER TABLE `" . $dbprefix . "tasks` ADD COLUMN `duration_days` INT(11) NULL DEFAULT NULL;");
        }
        // 8) ADD PERCENTUAL EXECUTADO NA TABELA project_time
        $timelog_percentage_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "project_time` LIKE 'percentage_executed'")->getResult();
        if (empty($timelog_percentage_column)) {
            $db->query("ALTER TABLE `" . $dbprefix . "project_time` ADD COLUMN `percentage_executed` DECIMAL(5,2) NULL DEFAULT NULL;");
        }
        // 5) ADD PERCENTUAL NA TABELA milestones
        $milestone_percentage_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "milestones` LIKE 'percentage'")->getResult();
        if (empty($milestone_percentage_column)) {
            $db->query("ALTER TABLE `" . $dbprefix . "milestones` ADD COLUMN `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0;");
        }
        // 6) ADD PERCENTUAL NA TABELA tasks
        $task_percentage_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "tasks` LIKE 'percentage'")->getResult();
        if (empty($task_percentage_column)) {
            $db->query("ALTER TABLE `" . $dbprefix . "tasks` ADD COLUMN `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0;");
        }
        // 7) ADD PERCENTUAL EXECUTADO NA TABELA team_activities
        $team_activities_table = $dbprefix . "team_activities";
        if ($db->tableExists($team_activities_table)) {
            $activity_query = $db->query("SHOW COLUMNS FROM `" . $team_activities_table . "` LIKE 'percentage_executed'");
            $activity_percentage_column = $activity_query ? $activity_query->getResult() : array();
            if (empty($activity_percentage_column)) {
                $db->query("ALTER TABLE `" . $team_activities_table . "` ADD COLUMN `percentage_executed` DECIMAL(5,2) NULL DEFAULT NULL;");
            }
        }
        // 3️⃣ — ALTERAR USER_ID PARA VARCHAR(255)
        $check_column_type = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "project_time` LIKE 'user_id'")->getRow();
        if ($check_column_type && strpos($check_column_type->Type, 'varchar') === false) {
            $db->query("ALTER TABLE `" . $dbprefix . "project_time` MODIFY `user_id` VARCHAR(255) NULL;");
        }

        // 4️⃣ — CRIAR TABELA DE FOTOS (rise_projectanalizer_photos)
        $sql_table = "CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_photos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `timelog_id` INT(11) NOT NULL,
            `file_name` VARCHAR(255) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `uploaded_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->query($sql_table);
        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_task_metrics` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `task_id` INT(11) NOT NULL,
            `weight` DECIMAL(5,2) NOT NULL DEFAULT 1,
            `baseline_start` DATETIME NULL,
            `baseline_end` DATETIME NULL,
            `baseline_duration_days` INT(11) NULL,
            `distribution_type` VARCHAR(25) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_task_costs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `task_id` INT(11) NOT NULL,
            `cost_type` VARCHAR(25) NOT NULL,
            `planned_value` DECIMAL(20,4) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_cost_realized` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `task_id` INT(11) NULL,
            `cost_type` VARCHAR(25) NOT NULL,
            `date` DATE NOT NULL,
            `value` DECIMAL(20,4) NOT NULL DEFAULT 0,
            `description` TEXT NULL,
            `reference` VARCHAR(255) NULL,
            `created_by` INT(11) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_project_snapshots` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `ref_date` DATE NOT NULL,
            `planned_physical_percent` DECIMAL(5,2) NULL,
            `actual_physical_percent` DECIMAL(5,2) NULL,
            `planned_financial_value` DECIMAL(20,4) NULL,
            `realized_financial_value` DECIMAL(20,4) NULL,
            `spi` DECIMAL(10,4) NULL,
            `cpi` DECIMAL(10,4) NULL,
            `forecast_end_date` DATE NULL,
            `delay_days` INT(11) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_task_cashflow_manual` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `task_id` INT(11) NOT NULL,
            `date` DATE NOT NULL,
            `value` DECIMAL(20,4) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_project_reschedule_log` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `old_start` DATE NULL,
            `new_start` DATE NOT NULL,
            `mode` VARCHAR(20) NOT NULL,
            `apply_scope` VARCHAR(20) NOT NULL,
            `adjust_milestones` TINYINT(1) NOT NULL DEFAULT 0,
            `created_by` INT(11) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_audit_logs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `action` VARCHAR(50) NOT NULL,
            `details` TEXT NULL,
            `created_by` INT(11) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_revenue_planned` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `title` VARCHAR(190) NOT NULL,
            `planned_date` DATE NOT NULL,
            `planned_value` DECIMAL(16,2) NOT NULL DEFAULT 0,
            `percent_of_contract` DECIMAL(6,2) NULL DEFAULT NULL,
            `notes` TEXT NULL,
            `created_by` INT(11) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            INDEX `idx_pa_revenue_planned_project_date` (`project_id`, `planned_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_revenue_realized` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `project_id` INT(11) NOT NULL,
            `planned_id` INT(11) NULL,
            `realized_date` DATE NOT NULL,
            `realized_value` DECIMAL(16,2) NOT NULL DEFAULT 0,
            `document_ref` VARCHAR(190) NULL,
            `notes` TEXT NULL,
            `created_by` INT(11) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            INDEX `idx_pa_revenue_realized_project_date` (`project_id`, `realized_date`),
            INDEX `idx_pa_revenue_realized_planned` (`planned_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
   
    });

//add setting link to the plugin setting
app_hooks()->add_filter('app_filter_action_links_of_ProjectAnalizer', function () {
    $action_links_array = array(
        anchor(get_uri("projectanalizer"), "ProjectAnalizer"),
        anchor(get_uri("projectanalizer_settings"), "ProjectAnalizer settings"),
    );

    return $action_links_array;
});

//update plugin
register_update_hook("ProjectAnalizer", function () {
    $db = db_connect('default');
    $dbprefix = get_db_prefix();
    $messages = array();

    $project_tabs = 'projectanalizer,etapas,tasks,tasks_kanban,evolution_project,evolucao_ff,notes,files,comments,teamactivities';
    $save_setting = new \App\Models\Settings_model();
    $save_setting->save_setting('project_tab_order', $project_tabs);
    $messages[] = "Updated project_tab_order";
    if (!get_setting("projectanalizer_cron_key")) {
        $save_setting->save_setting("projectanalizer_cron_key", "");
        $messages[] = "Added projectanalizer_cron_key";
    }

    $milestone_percentage_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "milestones` LIKE 'percentage'")->getResult();
    if (empty($milestone_percentage_column)) {
        $db->query("ALTER TABLE `" . $dbprefix . "milestones` ADD COLUMN `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0;");
        $messages[] = "Added milestones.percentage";
    } else {
        $messages[] = "milestones.percentage already exists";
    }

    $task_percentage_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "tasks` LIKE 'percentage'")->getResult();
    if (empty($task_percentage_column)) {
        $db->query("ALTER TABLE `" . $dbprefix . "tasks` ADD COLUMN `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0;");
        $messages[] = "Added tasks.percentage";
    } else {
        $messages[] = "tasks.percentage already exists";
    }

    $task_duration_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "tasks` LIKE 'duration_days'")->getResult();
    if (empty($task_duration_column)) {
        $db->query("ALTER TABLE `" . $dbprefix . "tasks` ADD COLUMN `duration_days` INT(11) NULL DEFAULT NULL;");
        $messages[] = "Added tasks.duration_days";
    } else {
        $messages[] = "tasks.duration_days already exists";
    }

    $team_activities_table = $dbprefix . "team_activities";
    if ($db->tableExists($team_activities_table)) {
        $activity_query = $db->query("SHOW COLUMNS FROM `" . $team_activities_table . "` LIKE 'percentage_executed'");
        $activity_percentage_column = $activity_query ? $activity_query->getResult() : array();
        if (empty($activity_percentage_column)) {
            $db->query("ALTER TABLE `" . $team_activities_table . "` ADD COLUMN `percentage_executed` DECIMAL(5,2) NULL DEFAULT NULL;");
            $messages[] = "Added team_activities.percentage_executed";
        } else {
            $messages[] = "team_activities.percentage_executed already exists";
        }
    } else {
        $messages[] = "team_activities table not found (skip percentage_executed)";
    }

    $timelog_percentage_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "project_time` LIKE 'percentage_executed'")->getResult();
    if (empty($timelog_percentage_column)) {
        $db->query("ALTER TABLE `" . $dbprefix . "project_time` ADD COLUMN `percentage_executed` DECIMAL(5,2) NULL DEFAULT NULL;");
        $messages[] = "Added project_time.percentage_executed";
    } else {
        $messages[] = "project_time.percentage_executed already exists";
    }

    $project_cost_center_column = $db->query("SHOW COLUMNS FROM `" . $dbprefix . "projects` LIKE 'cost_center_id'")->getResult();
    if (empty($project_cost_center_column)) {
        $db->query("ALTER TABLE `" . $dbprefix . "projects` ADD COLUMN `cost_center_id` INT(11) NULL DEFAULT NULL;");
        $messages[] = "Added projects.cost_center_id";
    } else {
        $messages[] = "projects.cost_center_id already exists";
    }

    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_task_metrics` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `task_id` INT(11) NOT NULL,
        `weight` DECIMAL(5,2) NOT NULL DEFAULT 1,
        `baseline_start` DATETIME NULL,
        `baseline_end` DATETIME NULL,
        `baseline_duration_days` INT(11) NULL,
        `distribution_type` VARCHAR(25) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_task_costs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `task_id` INT(11) NOT NULL,
        `cost_type` VARCHAR(25) NOT NULL,
        `planned_value` DECIMAL(20,4) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "pa_labor_profiles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(190) NOT NULL,
        `hourly_cost` DECIMAL(16,2) NOT NULL DEFAULT 0,
        `default_hours_per_day` DECIMAL(6,2) NOT NULL DEFAULT 8,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_pa_labor_profiles_name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "pa_task_labor_profiles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `task_id` INT(11) NOT NULL,
        `labor_profile_id` INT(11) NOT NULL,
        `qty_people` DECIMAL(8,2) NOT NULL DEFAULT 1,
        `hours_per_day` DECIMAL(6,2) NULL DEFAULT NULL,
        `notes` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_pa_task_labor_project` (`project_id`),
        INDEX `idx_pa_task_labor_task` (`task_id`),
        INDEX `idx_pa_task_labor_profile` (`labor_profile_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $custom_fields_table = $dbprefix . "custom_fields";
    $existing_field = $db->query("SELECT id FROM `$custom_fields_table` WHERE related_to='team_members' AND title_language_key='labor_profile' AND deleted=0 LIMIT 1")->getRow();
    if (!$existing_field) {
        $max_sort = $db->query("SELECT MAX(sort) AS max_sort FROM `$custom_fields_table` WHERE related_to='team_members' AND deleted=0")->getRow();
        $sort = $max_sort && $max_sort->max_sort ? ((int)$max_sort->max_sort + 1) : 1;
        $db->query("INSERT INTO `$custom_fields_table`
            (title, title_language_key, placeholder_language_key, show_in_embedded_form, placeholder, template_variable_name, options, field_type, related_to, sort, required, add_filter, show_in_table, show_in_invoice, show_in_estimate, show_in_contract, show_in_order, show_in_proposal, visible_to_admins_only, hide_from_clients, disable_editing_by_clients, show_on_kanban_card, deleted, show_in_subscription)
            VALUES
            ('Perfil de mao de obra', 'labor_profile', 'labor_profile', 0, '', 'LABOR_PROFILE', '', 'select', 'team_members', {$sort}, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);");
    }

    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_cost_realized` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `task_id` INT(11) NULL,
        `cost_type` VARCHAR(25) NOT NULL,
        `date` DATE NOT NULL,
        `value` DECIMAL(20,4) NOT NULL DEFAULT 0,
        `description` TEXT NULL,
        `reference` VARCHAR(255) NULL,
        `created_by` INT(11) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_project_snapshots` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `ref_date` DATE NOT NULL,
        `planned_physical_percent` DECIMAL(5,2) NULL,
        `actual_physical_percent` DECIMAL(5,2) NULL,
        `planned_financial_value` DECIMAL(20,4) NULL,
        `realized_financial_value` DECIMAL(20,4) NULL,
        `spi` DECIMAL(10,4) NULL,
        `cpi` DECIMAL(10,4) NULL,
        `forecast_end_date` DATE NULL,
        `delay_days` INT(11) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_task_cashflow_manual` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `task_id` INT(11) NOT NULL,
        `date` DATE NOT NULL,
        `value` DECIMAL(20,4) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_project_reschedule_log` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `old_start` DATE NULL,
        `new_start` DATE NOT NULL,
        `mode` VARCHAR(20) NOT NULL,
        `apply_scope` VARCHAR(20) NOT NULL,
        `adjust_milestones` TINYINT(1) NOT NULL DEFAULT 0,
        `clamp_enabled` TINYINT(1) NOT NULL DEFAULT 1,
        `sequenced_enabled` TINYINT(1) NOT NULL DEFAULT 0,
        `created_by` INT(11) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_audit_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `details` TEXT NULL,
        `created_by` INT(11) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_revenue_planned` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `title` VARCHAR(190) NOT NULL,
        `planned_date` DATE NOT NULL,
        `planned_value` DECIMAL(16,2) NOT NULL DEFAULT 0,
        `percent_of_contract` DECIMAL(6,2) NULL DEFAULT NULL,
        `notes` TEXT NULL,
        `created_by` INT(11) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        INDEX `idx_pa_revenue_planned_project_date` (`project_id`, `planned_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $messages[] = "Ensured projectanalizer_revenue_planned";

    $db->query("CREATE TABLE IF NOT EXISTS `" . $dbprefix . "projectanalizer_revenue_realized` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `planned_id` INT(11) NULL,
        `realized_date` DATE NOT NULL,
        `realized_value` DECIMAL(16,2) NOT NULL DEFAULT 0,
        `document_ref` VARCHAR(190) NULL,
        `notes` TEXT NULL,
        `created_by` INT(11) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        INDEX `idx_pa_revenue_realized_project_date` (`project_id`, `realized_date`),
        INDEX `idx_pa_revenue_realized_planned` (`planned_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $messages[] = "Ensured projectanalizer_revenue_realized";

    // Garantir colunas de compatibilidade (soft delete) e defaults, caso as tabelas já existam.
    $tables_to_check = array(
        "projectanalizer_task_metrics" => array(
            "deleted" => "ALTER TABLE `" . $dbprefix . "projectanalizer_task_metrics` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0;",
            "weight" => "ALTER TABLE `" . $dbprefix . "projectanalizer_task_metrics` MODIFY `weight` DECIMAL(5,2) NOT NULL DEFAULT 1;"
        ),
        "projectanalizer_task_costs" => array(
            "deleted" => "ALTER TABLE `" . $dbprefix . "projectanalizer_task_costs` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0;"
        ),
        "projectanalizer_cost_realized" => array(
            "deleted" => "ALTER TABLE `" . $dbprefix . "projectanalizer_cost_realized` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0;"
        ),
        "projectanalizer_project_snapshots" => array(
            "deleted" => "ALTER TABLE `" . $dbprefix . "projectanalizer_project_snapshots` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0;"
        ),
        "projectanalizer_project_reschedule_log" => array(
            "deleted" => "ALTER TABLE `" . $dbprefix . "projectanalizer_project_reschedule_log` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0;",
            "clamp_enabled" => "ALTER TABLE `" . $dbprefix . "projectanalizer_project_reschedule_log` ADD COLUMN `clamp_enabled` TINYINT(1) NOT NULL DEFAULT 1;",
            "sequenced_enabled" => "ALTER TABLE `" . $dbprefix . "projectanalizer_project_reschedule_log` ADD COLUMN `sequenced_enabled` TINYINT(1) NOT NULL DEFAULT 0;"
        ),
        "projectanalizer_audit_logs" => array(
            "deleted" => "ALTER TABLE `" . $dbprefix . "projectanalizer_audit_logs` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0;"
        ),
        "projectanalizer_task_cashflow_manual" => array(
            "deleted" => "ALTER TABLE `" . $dbprefix . "projectanalizer_task_cashflow_manual` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0;"
        )
    );

    foreach ($tables_to_check as $table_short => $columns) {
        foreach ($columns as $column => $alter_sql) {
            $col_info = $db->query("SHOW COLUMNS FROM `" . $dbprefix . $table_short . "` LIKE '" . $column . "'")->getRow();
            if (!$col_info) {
                $db->query($alter_sql);
                $messages[] = "Added {$table_short}.{$column}";
                continue;
            }

            if ($column === "weight") {
                $current_default = isset($col_info->Default) ? $col_info->Default : null;
                $default_ok = is_numeric($current_default) && ((float)$current_default) == 1.0;

                if (!$default_ok) {
                    $db->query("ALTER TABLE `" . $dbprefix . "projectanalizer_task_metrics` MODIFY `weight` DECIMAL(5,2) NOT NULL DEFAULT 1;");
                    $messages[] = "Fixed projectanalizer_task_metrics.weight default";
                }
            }
        }
    }

    echo "ProjectAnalizer update<br />";
    echo implode("<br />", $messages);
});

//uninstallation: remove data from database
register_uninstallation_hook("ProjectAnalizer", function () {
    $dbprefix = get_db_prefix();
    $db = db_connect('default');

    $sql_query = "DROP TABLE IF EXISTS `" . $dbprefix . "projectanalizer_settings`;";
    $db->query($sql_query);

    $db->query("DROP TABLE IF EXISTS `" . $dbprefix . "projectanalizer_revenue_realized`;");
    $db->query("DROP TABLE IF EXISTS `" . $dbprefix . "projectanalizer_revenue_planned`;");
});
