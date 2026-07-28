<?php

/**
 * Migration: API, Offline, AI e Segurança
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// ==================== 1. DISPOSITIVOS MÓVEIS ====================
$devices_table = $dbprefix . 'laudo_devices';
if (!$db->tableExists($devices_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$devices_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `device_uuid` VARCHAR(100) NOT NULL,
        `device_name` VARCHAR(100) NULL,
        `device_type` VARCHAR(20) NULL,
        `os_version` VARCHAR(20) NULL,
        `app_version` VARCHAR(20) NULL,
        `push_token` VARCHAR(255) NULL,
        `refresh_token` VARCHAR(64) NULL,
        `refresh_expires_at` DATETIME NULL,
        `last_access_at` DATETIME NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `is_revoked` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `device_uuid` (`device_uuid`),
        KEY `refresh_token` (`refresh_token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_devices";
}

// ==================== 2. REGISTROS OFFLINE ====================
$offline_table = $dbprefix . 'laudo_offline_records';
if (!$db->tableExists($offline_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$offline_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `device_uuid` VARCHAR(100) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `table_name` VARCHAR(50) NOT NULL,
        `record_id` INT(11) NOT NULL,
        `record_uuid` VARCHAR(36) NOT NULL,
        `operation` VARCHAR(10) NOT NULL,
        `local_data` JSON NULL,
        `local_created_at` DATETIME NOT NULL,
        `local_updated_at` DATETIME NOT NULL,
        `server_data` JSON NULL,
        `server_updated_at` DATETIME NULL,
        `version` INT(11) DEFAULT 1,
        `sync_status` VARCHAR(20) DEFAULT 'pending',
        `conflict_data` JSON NULL,
        `resolved_at` DATETIME NULL,
        `hash` VARCHAR(64) NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `device_uuid` (`device_uuid`),
        KEY `record_uuid` (`record_uuid`),
        KEY `sync_status` (`sync_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_offline_records";
}

// ==================== 3. LOGS DE API ====================
$api_logs_table = $dbprefix . 'laudo_api_logs';
if (!$db->tableExists($api_logs_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$api_logs_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NULL,
        `device_id` INT(11) NULL,
        `method` VARCHAR(10) NOT NULL,
        `endpoint` VARCHAR(255) NOT NULL,
        `request_headers` JSON NULL,
        `request_body` JSON NULL,
        `response_status` INT(11) NOT NULL,
        `response_body` JSON NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` VARCHAR(500) NULL,
        `execution_time` INT(11) NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `endpoint` (`endpoint`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_api_logs";
}

// ==================== 4. CONFIGURAÇÕES DE IA ====================
$ai_config_table = $dbprefix . 'laudo_ai_config';
if (!$db->tableExists($ai_config_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$ai_config_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `provider` VARCHAR(30) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `api_url` VARCHAR(500) NOT NULL,
        `api_key` VARCHAR(500) NULL,
        `model` VARCHAR(100) NOT NULL,
        `temperature` DECIMAL(3,2) DEFAULT 0.7,
        `max_tokens` INT(11) DEFAULT 2000,
        `timeout` INT(11) DEFAULT 30,
        `system_prompt` TEXT NULL,
        `allowed_features` JSON NULL,
        `monthly_limit` INT(11) DEFAULT 1000,
        `current_usage` INT(11) DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `provider` (`provider`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_ai_config";
}

// ==================== 5. USO DE IA ====================
$ai_usage_table = $dbprefix . 'laudo_ai_usage';
if (!$db->tableExists($ai_usage_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$ai_usage_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `config_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `feature` VARCHAR(50) NOT NULL,
        `prompt` TEXT NULL,
        `response` TEXT NULL,
        `model` VARCHAR(100) NOT NULL,
        `tokens_used` INT(11) DEFAULT 0,
        `execution_time` INT(11) NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `feature` (`feature`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_ai_usage";
}

// ==================== 6. RELATÓRIOS ====================
$reports_table = $dbprefix . 'laudo_reports';
if (!$db->tableExists($reports_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$reports_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `type` VARCHAR(30) NOT NULL,
        `config` JSON NULL,
        `created_by` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL,
        `last_run_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8del4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_reports";
}

return array(
    'success' => true,
    'results' => $results
);