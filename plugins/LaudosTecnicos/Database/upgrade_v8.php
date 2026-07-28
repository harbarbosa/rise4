<?php

/**
 * Migration: Revisão, Aprovação, Assinaturas e Versionamento
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// ==================== 1. RESPONSÁVEIS TÉCNICOS ====================
$technical_table = $dbprefix . 'laudo_technical_professionals';
if (!$db->tableExists($technical_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$technical_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `cpf` VARCHAR(14) NULL,
        `council_type` VARCHAR(20) NULL,
        `council_number` VARCHAR(50) NULL,
        `council_state` VARCHAR(2) NULL,
        `specialty` VARCHAR(100) NULL,
        `role` VARCHAR(100) NULL,
        `email` VARCHAR(255) NULL,
        `phone` VARCHAR(20) NULL,
        `signature_file` VARCHAR(500) NULL,
        `signature_type` VARCHAR(20) NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'active',
        `validity_start` DATE NULL,
        `validity_end` DATE NULL,
        `art_number` VARCHAR(50) NULL,
        `art_file` VARCHAR(500) NULL,
        `rrt_number` VARCHAR(50) NULL,
        `rrt_file` VARCHAR(500) NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_technical_professionals";
}

// ==================== 2. COMENTÁRIOS DE REVISÃO ====================
$review_comments_table = $dbprefix . 'laudo_review_comments';
if (!$db->tableExists($review_comments_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$review_comments_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `section_id` INT(11) NULL,
        `field_name` VARCHAR(100) NULL,
        `author_id` INT(11) NOT NULL,
        `text` TEXT NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'open',
        `priority` VARCHAR(20) NOT NULL DEFAULT 'normal',
        `created_at` DATETIME NOT NULL,
        `resolved_at` DATETIME NULL,
        `resolved_by` INT(11) NULL,
        `response` TEXT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_review_comments";
}

// ==================== 3. APROVAÇÕES ====================
$approvals_table = $dbprefix . 'laudo_approvals';
if (!$db->tableExists($approvals_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$approvals_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `version` INT(11) NOT NULL,
        `step` INT(11) NOT NULL DEFAULT 1,
        `approver_id` INT(11) NOT NULL,
        `decision` VARCHAR(20) NOT NULL,
        `comment` TEXT NULL,
        `version_hash` VARCHAR(64) NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` VARCHAR(500) NULL,
        `created_at` DATETIME NOT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `step` (`step`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_approvals";
}

// ==================== 4. ASSINATURAS ====================
$signatures_table = $dbprefix . 'laudo_signatures';
if (!$db->tableExists($signatures_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$signatures_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `version` INT(11) NOT NULL,
        `signer_name` VARCHAR(255) NOT NULL,
        `signer_document` VARCHAR(50) NULL,
        `signer_role` VARCHAR(50) NOT NULL,
        `signer_type` VARCHAR(20) NOT NULL,
        `signature_data` TEXT NULL,
        `signature_file` VARCHAR(500) NULL,
        `user_id` INT(11) NULL,
        `signed_at` DATETIME NOT NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` VARCHAR(500) NULL,
        `document_hash` VARCHAR(64) NULL,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `version` (`version`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_signatures";
}

// ==================== 5. VERSÕES ====================
$versions_table = $dbprefix . 'laudo_versions';
if (!$db->tableExists($versions_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$versions_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `version` INT(11) NOT NULL,
        `revision` INT(11) NOT NULL DEFAULT 0,
        `reason` VARCHAR(255) NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
        `content_json` TEXT NULL,
        `files_json` TEXT NULL,
        `pdf_file` VARCHAR(500) NULL,
        `document_hash` VARCHAR(64) NULL,
        `created_by` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL,
        `published_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `version` (`version`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_versions";
}

// ==================== 6. CONTEÚDO ANTERIOR (HISTÓRICO) ====================
$content_history_table = $dbprefix . 'laudo_content_history';
if (!$db->tableExists($content_history_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$content_history_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `field_name` VARCHAR(100) NOT NULL,
        `old_value` TEXT NULL,
        `new_value` TEXT NULL,
        `changed_by` INT(11) NOT NULL,
        `changed_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_content_history";
}

// ==================== 7. PENDÊNCIAS ====================
$pendencies_table = $dbprefix . 'laudo_pendencies';
if (!$db->tableExists($pendencies_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$pendencies_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `type` VARCHAR(30) NOT NULL,
        `description` TEXT NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `created_by` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL,
        `resolved_at` DATETIME NULL,
        `resolved_by` INT(11) NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_pendencies";
}

// ==================== 8. ATUALIZAR TABELA PRINCIPAL ====================
$laudos_table = $dbprefix . 'laudos_tecnicos';
$check_columns = $db->getFieldNames($laudos_table);

// Adicionar campos necessários
if (!in_array('technical_professional_id', $check_columns)) {
    $db->query("ALTER TABLE `$laudos_table` ADD COLUMN `technical_professional_id` INT(11) NULL AFTER `status`");
    $results[] = "Adicionada coluna technical_professional_id";
}

if (!in_array('current_version', $check_columns)) {
    $db->query("ALTER TABLE `$laudos_table` ADD COLUMN `current_version` INT(11) NOT NULL DEFAULT 1 AFTER `technical_professional_id`");
    $results[] = "Adicionada coluna current_version";
}

if (!in_array('approval_status', $check_columns)) {
    $db->query("ALTER TABLE `$laudos_table` ADD COLUMN `approval_status` VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER `current_version`");
    $results[] = "Adicionada coluna approval_status";
}

if (!in_array('review_status', $check_columns)) {
    $db->query("ALTER TABLE `$laudos_table` ADD COLUMN `review_status` VARCHAR(20) NOT NULL DEFAULT 'not_started' AFTER `approval_status`");
    $results[] = "Adicionada coluna review_status";
}

return array(
    'success' => true,
    'results' => $results
);