<?php

/**
 * Migration: Inspeções, Agenda, Fotos e Coleta em Campo
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// ==================== 1. INSPEÇÕES ====================
$inspections_table = $dbprefix . 'laudo_inspections';
if (!$db->tableExists($inspections_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$inspections_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `code` VARCHAR(50) NULL,
        `client_id` INT(11) NULL,
        `unit_id` INT(11) NULL,
        `location` VARCHAR(255) NULL,
        `address` TEXT NULL,
        `inspection_type` VARCHAR(50) NULL,
        `scheduled_date` DATE NOT NULL,
        `scheduled_time` TIME NULL,
        `duration_minutes` INT(11) NULL,
        `responsible_id` INT(11) NULL,
        `team_ids` TEXT NULL,
        `vehicle` VARCHAR(100) NULL,
        `equipment_ids` TEXT NULL,
        `observations` TEXT NULL,
        `status` VARCHAR(30) NOT NULL DEFAULT 'planned',
        `checkin_at` DATETIME NULL,
        `checkin_lat` DECIMAL(10,8) NULL,
        `checkin_lng` DECIMAL(11,8) NULL,
        `checkin_accuracy` DECIMAL(8,2) NULL,
        `checkin_distance` DECIMAL(8,2) NULL,
        `checkout_at` DATETIME NULL,
        `started_at` DATETIME NULL,
        `paused_at` DATETIME NULL,
        `resumed_at` DATETIME NULL,
        `completed_at` DATETIME NULL,
        `result_notes` TEXT NULL,
        `created_by` INT(11) NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `client_id` (`client_id`),
        KEY `scheduled_date` (`scheduled_date`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_inspections";
}

// ==================== 2. FOTOGRAFIAS ====================
$photos_table = $dbprefix . 'laudo_photos';
if (!$db->tableExists($photos_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$photos_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `inspection_id` INT(11) NULL,
        `checklist_item_id` INT(11) NULL,
        `measurement_id` INT(11) NULL,
        `nc_id` INT(11) NULL,
        `original_file` VARCHAR(500) NOT NULL,
        `thumbnail_file` VARCHAR(500) NULL,
        `caption` VARCHAR(255) NULL,
        `photo_number` INT(11) NOT NULL,
        `taken_at` DATETIME NOT NULL,
        `user_id` INT(11) NOT NULL,
        `gps_lat` DECIMAL(10,8) NULL,
        `gps_lng` DECIMAL(11,8) NULL,
        `location_name` VARCHAR(255) NULL,
        `sector` VARCHAR(100) NULL,
        `equipment_id` INT(11) NULL,
        `observation` TEXT NULL,
        `file_hash` VARCHAR(64) NULL,
        `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
        `is_before` TINYINT(1) NOT NULL DEFAULT 0,
        `is_after` TINYINT(1) NOT NULL DEFAULT 0,
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `inspection_id` (`inspection_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_photos";
}

// ==================== 3. INSPEÇÕES IMPRODUTIVAS ====================
$unproductive_table = $dbprefix . 'laudo_inspection_unproductive';
if (!$db->tableExists($unproductive_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$unproductive_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `inspection_id` INT(11) NOT NULL,
        `reason` VARCHAR(100) NOT NULL,
        `description` TEXT NULL,
        `client_responsible` VARCHAR(255) NULL,
        `suggested_date` DATE NULL,
        `costs` DECIMAL(12,2) NULL,
        `travel_costs` DECIMAL(12,2) NULL,
        `comments` TEXT NULL,
        `signature_file` VARCHAR(500) NULL,
        `registered_by` INT(11) NOT NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `inspection_id` (`inspection_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_inspection_unproductive";
}

// ==================== 4. PENDÊNCIAS ====================
$pendencies_table = $dbprefix . 'laudo_inspection_pendencies';
if (!$db->tableExists($pendencies_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$pendencies_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `inspection_id` INT(11) NOT NULL,
        `type` VARCHAR(30) NOT NULL,
        `description` TEXT NOT NULL,
        `is_blocking` TINYINT(1) NOT NULL DEFAULT 0,
        `resolved` TINYINT(1) NOT NULL DEFAULT 0,
        `resolved_at` DATETIME NULL,
        `resolved_by` INT(11) NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `inspection_id` (`inspection_id`),
        KEY `resolved` (`resolved`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_inspection_pendencies";
}

// ==================== 5. VEÍCULOS (opcional) ====================
$vehicles_table = $dbprefix . 'laudo_vehicles';
if (!$db->tableExists($vehicles_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$vehicles_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `plate` VARCHAR(20) NOT NULL,
        `model` VARCHAR(100) NULL,
        `year` VARCHAR(4) NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'available',
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `plate` (`plate`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_vehicles";
}

return array(
    'success' => true,
    'results' => $results
);