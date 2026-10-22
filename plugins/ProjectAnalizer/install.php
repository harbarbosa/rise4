<?php

$db = db_connect('default');
$dbprefix = $db->getPrefix();
$journeyTableExisted = $db->tableExists("pa_project_journeys");

$result = array(
    "success" => true,
    "tables" => array(),
    "errors" => array()
);

$statements = array(
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_tools` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `active_deleted` (`active`, `deleted`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_task_materials` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) UNSIGNED NOT NULL,
        `task_id` INT(11) UNSIGNED NOT NULL,
        `proposal_item_id` INT(11) UNSIGNED NOT NULL,
        `quantity` DECIMAL(16,4) NOT NULL DEFAULT 0,
        `notes` TEXT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `project_id` (`project_id`),
        KEY `task_id` (`task_id`),
        KEY `proposal_item_id` (`proposal_item_id`),
        KEY `task_deleted` (`task_id`, `deleted`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_task_tools` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) UNSIGNED NOT NULL,
        `task_id` INT(11) UNSIGNED NOT NULL,
        `tool_id` INT(11) UNSIGNED NOT NULL,
        `quantity` DECIMAL(16,4) NOT NULL DEFAULT 1,
        `requirement` TEXT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `project_id` (`project_id`),
        KEY `task_id` (`task_id`),
        KEY `tool_id` (`tool_id`),
        KEY `task_deleted` (`task_id`, `deleted`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_project_materials` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `project_id` INT(11) UNSIGNED NOT NULL, `proposal_id` INT(11) UNSIGNED NULL, `proposal_item_id` INT(11) UNSIGNED NULL, `item_id` INT(11) UNSIGNED NULL,
        `description` VARCHAR(500) NOT NULL, `unit` VARCHAR(50) NOT NULL DEFAULT 'UN', `planned_quantity` DECIMAL(16,4) NOT NULL DEFAULT 0, `additional_quantity` DECIMAL(16,4) NOT NULL DEFAULT 0, `stock_quantity` DECIMAL(16,4) NOT NULL DEFAULT 0,
        `source` VARCHAR(20) NOT NULL DEFAULT 'proposal', `source_key` VARCHAR(255) NOT NULL, `created_by` INT(11) NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`), UNIQUE KEY `project_source` (`project_id`,`source_key`), KEY `project_id` (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_project_task_materials` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `project_id` INT(11) UNSIGNED NOT NULL, `project_material_id` INT(11) UNSIGNED NOT NULL, `task_id` INT(11) UNSIGNED NOT NULL, `quantity` DECIMAL(16,4) NOT NULL DEFAULT 0,
        `notes` TEXT NULL, `created_by` INT(11) NULL, `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`), UNIQUE KEY `task_material` (`task_id`,`project_material_id`), KEY `project_id` (`project_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_project_material_request_items` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `project_id` INT(11) UNSIGNED NOT NULL, `task_id` INT(11) UNSIGNED NOT NULL, `project_material_id` INT(11) UNSIGNED NOT NULL, `allocation_id` INT(11) UNSIGNED NOT NULL,
        `purchase_request_id` INT(11) UNSIGNED NOT NULL, `purchase_request_item_id` INT(11) UNSIGNED NOT NULL, `quantity` DECIMAL(16,4) NOT NULL DEFAULT 0,
        `created_by` INT(11) NULL, `created_at` DATETIME NULL, `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`), KEY `allocation_id` (`allocation_id`), KEY `purchase_request_id` (`purchase_request_id`), KEY `project_task` (`project_id`,`task_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_project_journeys` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) UNSIGNED NOT NULL,
        `manager_id` INT(11) UNSIGNED NULL,
        `current_phase` TINYINT(2) UNSIGNED NOT NULL DEFAULT 1,
        `status` VARCHAR(20) NOT NULL DEFAULT 'active',
        `legacy_unlocked` TINYINT(1) NOT NULL DEFAULT 0,
        `created_by` INT(11) UNSIGNED NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`), UNIQUE KEY `project_id` (`project_id`), KEY `manager_id` (`manager_id`), KEY `phase_status` (`current_phase`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_project_journey_history` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) UNSIGNED NOT NULL,
        `journey_id` INT(11) UNSIGNED NOT NULL,
        `action` VARCHAR(40) NOT NULL,
        `phase_from` TINYINT(2) UNSIGNED NULL,
        `phase_to` TINYINT(2) UNSIGNED NULL,
        `details` TEXT NULL,
        `created_by` INT(11) UNSIGNED NULL,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`), KEY `project_id` (`project_id`), KEY `journey_id` (`journey_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_task_journey_status` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) UNSIGNED NOT NULL,
        `task_id` INT(11) UNSIGNED NOT NULL,
        `blocked` TINYINT(1) NOT NULL DEFAULT 0,
        `blocker_reason` TEXT NULL,
        `blocked_by` INT(11) UNSIGNED NULL,
        `blocked_at` DATETIME NULL,
        `released` TINYINT(1) NOT NULL DEFAULT 0,
        `release_notes` TEXT NULL,
        `released_by` INT(11) UNSIGNED NULL,
        `released_at` DATETIME NULL,
        `created_by` INT(11) UNSIGNED NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `task_id` (`task_id`),
        KEY `project_id` (`project_id`),
        KEY `project_released` (`project_id`, `released`),
        KEY `project_blocked` (`project_id`, `blocked`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_project_material_usage` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) UNSIGNED NOT NULL,
        `task_id` INT(11) UNSIGNED NOT NULL,
        `project_material_id` INT(11) UNSIGNED NOT NULL,
        `allocation_id` INT(11) UNSIGNED NOT NULL,
        `quantity` DECIMAL(16,4) NOT NULL DEFAULT 0,
        `notes` TEXT NULL,
        `used_by` INT(11) UNSIGNED NULL,
        `used_at` DATETIME NULL,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `project_id` (`project_id`),
        KEY `task_id` (`task_id`),
        KEY `project_material_id` (`project_material_id`),
        KEY `allocation_id` (`allocation_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS `{$dbprefix}pa_project_material_deliveries` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) UNSIGNED NOT NULL,
        `task_id` INT(11) UNSIGNED NOT NULL,
        `project_material_id` INT(11) UNSIGNED NOT NULL,
        `allocation_id` INT(11) UNSIGNED NOT NULL,
        `quantity` DECIMAL(16,4) NOT NULL DEFAULT 0,
        `notes` TEXT NULL,
        `delivered_by` INT(11) UNSIGNED NULL,
        `delivered_at` DATETIME NULL,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `project_id` (`project_id`),
        KEY `task_id` (`task_id`),
        KEY `project_material_id` (`project_material_id`),
        KEY `allocation_id` (`allocation_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"

);

foreach ($statements as $statement) {
    try {
        if (!$db->query($statement)) {
            $result["success"] = false;
            $result["errors"][] = json_encode($db->error());
        }
    } catch (\Throwable $e) {
        $result["success"] = false;
        $result["errors"][] = $e->getMessage();
    }
}

// Na primeira instalação da jornada, projetos já existentes permanecem totalmente liberados.
if (!$journeyTableExisted) {
    try {
        $now = date("Y-m-d H:i:s");
        $db->query("INSERT INTO `{$dbprefix}pa_project_journeys`
            (`project_id`, `current_phase`, `status`, `legacy_unlocked`, `created_at`, `updated_at`, `deleted`)
            SELECT p.id, 7, 'active', 1, ?, ?, 0
            FROM `{$dbprefix}projects` p
            LEFT JOIN `{$dbprefix}pa_project_journeys` j ON j.project_id = p.id AND j.deleted = 0
            WHERE p.deleted = 0 AND j.id IS NULL", array($now, $now));
    } catch (\Throwable $e) {
        $result["success"] = false;
        $result["errors"][] = $e->getMessage();
    }
}

// Saldo físico informado para materiais já disponíveis no projeto.
try {
    $projectMaterialsTable = $dbprefix . "pa_project_materials";
    if (!$db->fieldExists("stock_quantity", $projectMaterialsTable)) {
        $db->query("ALTER TABLE `{$projectMaterialsTable}` ADD `stock_quantity` DECIMAL(16,4) NOT NULL DEFAULT 0 AFTER `additional_quantity`");
    }
} catch (\Throwable $e) {
    $result["success"] = false;
    $result["errors"][] = $e->getMessage();
}

// Mantém o vínculo entre o item requisitado, a ordem de compra e o recebimento.
try {
    $purchaseOrderItemsTable = $dbprefix . "purchases_order_items";
    if ($db->tableExists($purchaseOrderItemsTable) && !$db->fieldExists("request_item_id", $purchaseOrderItemsTable)) {
        $db->query("ALTER TABLE `{$purchaseOrderItemsTable}` ADD `request_item_id` INT(11) NULL AFTER `order_id`, ADD INDEX `request_item_id` (`request_item_id`)");
    }
} catch (\Throwable $e) {
    $result["success"] = false;
    $result["errors"][] = $e->getMessage();
}

// Campos de aprovação dos lançamentos de atividade (timesheets).
$projectTimeTable = $dbprefix . "project_time";
$approvalColumns = array(
    "percentage_executed" => "DECIMAL(8,2) NULL DEFAULT NULL",
    "approval_status" => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
    "approved_by" => "INT(11) NULL",
    "approved_at" => "DATETIME NULL"
);

foreach ($approvalColumns as $column => $definition) {
    try {
        if (!$db->fieldExists($column, $projectTimeTable)) {
            $db->query("ALTER TABLE `{$projectTimeTable}` ADD `{$column}` {$definition}");
        }
    } catch (\Throwable $e) {
        $result["success"] = false;
        $result["errors"][] = $e->getMessage();
    }
}

foreach (array("pa_tools", "pa_task_materials", "pa_task_tools", "pa_project_materials", "pa_project_task_materials", "pa_project_material_request_items", "pa_project_journeys", "pa_project_journey_history", "pa_task_journey_status", "pa_project_material_usage", "pa_project_material_deliveries") as $table) {
    $full_table = $dbprefix . $table;
    // Query the database directly because tableExists() can keep a cached list
    // populated by a previously executed plugin installer in the same request.
    $table_query = $db->query("SHOW TABLES LIKE ?", array($full_table));
    if ($table_query && $table_query->getNumRows() > 0) {
        $result["tables"][] = $full_table;
    } else {
        $result["success"] = false;
        $result["errors"][] = "Table not available: " . $full_table;
    }
}

return $result;
