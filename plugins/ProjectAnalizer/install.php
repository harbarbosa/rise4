<?php

$db = db_connect('default');
$dbprefix = $db->getPrefix();

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

foreach (array("pa_tools", "pa_task_materials", "pa_task_tools") as $table) {
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
