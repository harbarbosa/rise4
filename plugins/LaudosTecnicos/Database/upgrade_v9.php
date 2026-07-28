<?php

/**
 * Migration: HTML/PDF, Portal do Cliente e Validação
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// ==================== 1. CONFIGURAÇÕES DE DOCUMENTO ====================
$config_table = $dbprefix . 'laudo_document_config';
if (!$db->tableExists($config_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$config_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NULL,
        `logo_file` VARCHAR(500) NULL,
        `client_logo_file` VARCHAR(500) NULL,
        `primary_color` VARCHAR(7) DEFAULT '#007bff',
        `secondary_color` VARCHAR(7) DEFAULT '#6c757d',
        `font_family` VARCHAR(100) DEFAULT 'Arial, sans-serif',
        `font_size` INT(11) DEFAULT 12,
        `margin_top` INT(11) DEFAULT 20,
        `margin_bottom` INT(11) DEFAULT 20,
        `margin_left` INT(11) DEFAULT 20,
        `margin_right` INT(11) DEFAULT 20,
        `header_html` TEXT NULL,
        `footer_html` TEXT NULL,
        `watermark_text` VARCHAR(255) NULL,
        `watermark_color` VARCHAR(7) DEFAULT '#cccccc',
        `confidentiality_text` TEXT NULL,
        `show_cover` TINYINT(1) DEFAULT 1,
        `show_toc` TINYINT(1) DEFAULT 1,
        `show_page_numbers` TINYINT(1) DEFAULT 1,
        `paper_size` VARCHAR(20) DEFAULT 'A4',
        `orientation` VARCHAR(10) DEFAULT 'portrait',
        `show_qrcode` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_document_config";
}

// ==================== 2. COMPARTILHAMENTOS ====================
$shares_table = $dbprefix . 'laudo_shares';
if (!$db->tableExists($shares_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$shares_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `version` INT(11) NOT NULL,
        `token` VARCHAR(64) NOT NULL,
        `password` VARCHAR(255) NULL,
        `expires_at` DATETIME NULL,
        `max_accesses` INT(11) NULL,
        `current_accesses` INT(11) DEFAULT 0,
        `allow_download` TINYINT(1) DEFAULT 1,
        `allow_comments` TINYINT(1) DEFAULT 0,
        `visitor_name` VARCHAR(255) NULL,
        `visitor_email` VARCHAR(255) NULL,
        `active` TINYINT(1) DEFAULT 1,
        `created_by` INT(11) NOT NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `token` (`token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_shares";
}

// ==================== 3. ACESSOS AO COMPARTILHAMENTO ====================
$access_logs_table = $dbprefix . 'laudo_share_logs';
if (!$db->tableExists($access_logs_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$access_logs_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `share_id` INT(11) NOT NULL,
        `action` VARCHAR(30) NOT NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` VARCHAR(500) NULL,
        `visited_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `share_id` (`share_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_share_logs";
}

// ==================== 4. RESPOSTAS DO CLIENTE ====================
$client_responses_table = $dbprefix . 'laudo_client_responses';
if (!$db->tableExists($client_responses_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$client_responses_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `type` VARCHAR(30) NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `comment` TEXT NULL,
        `client_id` INT(11) NULL,
        `client_name` VARCHAR(255) NULL,
        `client_email` VARCHAR(255) NULL,
        `received_at` DATETIME NULL,
        `responded_at` DATETIME NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_client_responses";
}

// ==================== 5. EVIDÊNCIAS DO CLIENTE ====================
$client_evidence_table = $dbprefix . 'laudo_client_evidence';
if (!$db->tableExists($client_evidence_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$client_evidence_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `nc_id` INT(11) NULL,
        `file_path` VARCHAR(500) NOT NULL,
        `description` TEXT NULL,
        `uploaded_by` INT(11) NOT NULL,
        `uploaded_at` DATETIME NOT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_client_evidence";
}

// ==================== 6. NOTIFICAÇÕES ====================
$notifications_table = $dbprefix . 'laudo_notifications';
if (!$db->tableExists($notifications_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$notifications_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `type` VARCHAR(50) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `read` TINYINT(1) DEFAULT 0,
        `user_id` INT(11) NULL,
        `sent_to` VARCHAR(255) NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_notifications";
}

// ==================== 7. ATUALIZAR TABELA DE VERSÕES ====================
$versions_table = $dbprefix . 'laudo_versions';
$version_columns = $db->getFieldNames($versions_table);

if (!in_array('pdf_file', $version_columns)) {
    $db->query("ALTER TABLE `$versions_table` ADD COLUMN `pdf_file` VARCHAR(500) NULL AFTER `pdf_file`");
    $results[] = "Adicionada coluna pdf_file";
}

if (!in_array('qrcode_file', $version_columns)) {
    $db->query("ALTER TABLE `$versions_table` ADD COLUMN `qrcode_file` VARCHAR(500) NULL AFTER `pdf_file`");
    $results[] = "Adicionada coluna qrcode_file";
}

if (!in_array('validation_url', $version_columns)) {
    $db->query("ALTER TABLE `$versions_table` ADD COLUMN `validation_url` VARCHAR(500) NULL AFTER `qrcode_file`");
    $results[] = "Adicionada coluna validation_url";
}

return array(
    'success' => true,
    'results' => $results
);