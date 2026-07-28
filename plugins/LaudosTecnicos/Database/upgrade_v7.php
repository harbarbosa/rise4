<?php

/**
 * Migration: Não Conformidades, Riscos e Planos de Ação
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// ==================== 1. NÃO CONFORMIDADES ====================
$nc_table = $dbprefix . 'laudo_non_conformities';
if (!$db->tableExists($nc_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$nc_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(50) NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `client_id` INT(11) NULL,
        `laudo_id` INT(11) NULL,
        `inspection_id` INT(11) NULL,
        `location` VARCHAR(255) NULL,
        `sector` VARCHAR(100) NULL,
        `equipment_id` INT(11) NULL,
        `checklist_item_id` INT(11) NULL,
        `measurement_id` INT(11) NULL,
        `standard_code` VARCHAR(50) NULL,
        `classification` VARCHAR(30) NOT NULL DEFAULT 'moderate',
        `probability` INT(11) NOT NULL DEFAULT 1,
        `impact` INT(11) NOT NULL DEFAULT 1,
        `risk_level` INT(11) NOT NULL DEFAULT 1,
        `risk_color` VARCHAR(20) NULL,
        `recommendation` TEXT NULL,
        `suggested_deadline` DATE NULL,
        `responsible_id` INT(11) NULL,
        `status` VARCHAR(30) NOT NULL DEFAULT 'open',
        `identified_at` DATE NOT NULL,
        `corrected_at` DATE NULL,
        `correction_evidence` TEXT NULL,
        `validated_at` DATETIME NULL,
        `validated_by` INT(11) NULL,
        `created_by` INT(11) NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `status` (`status`),
        KEY `risk_level` (`risk_level`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_non_conformities";
}

// ==================== 2. EVIDÊNCIAS DA NC ====================
$nc_evidence_table = $dbprefix . 'laudo_nc_evidence';
if (!$db->tableExists($nc_evidence_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$nc_evidence_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nc_id` INT(11) NOT NULL,
        `type` VARCHAR(30) NOT NULL,
        `file_path` VARCHAR(500) NULL,
        `description` TEXT NULL,
        `taken_at` DATETIME NOT NULL,
        `user_id` INT(11) NOT NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `nc_id` (`nc_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_nc_evidence";
}

// ==================== 3. PLANOS DE AÇÃO ====================
$action_plan_table = $dbprefix . 'laudo_action_plans';
if (!$db->tableExists($action_plan_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$action_plan_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nc_id` INT(11) NOT NULL,
        `task_id` INT(11) NULL,
        `action` TEXT NOT NULL,
        `reason` TEXT NULL,
        `location` VARCHAR(255) NULL,
        `responsible_id` INT(11) NULL,
        `company_name` VARCHAR(255) NULL,
        `method` TEXT NULL,
        `deadline` DATE NOT NULL,
        `estimated_cost` DECIMAL(12,2) NULL,
        `real_cost` DECIMAL(12,2) NULL,
        `priority` VARCHAR(20) NOT NULL DEFAULT 'normal',
        `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
        `evidence` TEXT NULL,
        `completed_at` DATE NULL,
        `validated_at` DATETIME NULL,
        `validated_by` INT(11) NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `nc_id` (`nc_id`),
        KEY `task_id` (`task_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_action_plans";
}

// ==================== 4. MATRIZ DE RISCO ====================
$risk_matrix_table = $dbprefix . 'laudo_risk_matrix';
if (!$db->tableExists($risk_matrix_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$risk_matrix_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `category_id` INT(11) NULL,
        `probability` INT(11) NOT NULL,
        `impact` INT(11) NOT NULL,
        `result` INT(11) NOT NULL,
        `classification` VARCHAR(30) NOT NULL,
        `color` VARCHAR(20) NOT NULL,
        `deadline_days` INT(11) NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `category_id` (`category_id`),
        UNIQUE KEY `matrix_point` (`probability`, `impact`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_risk_matrix";
    
    // Inserir matriz de risco padrão
    $matrix = array(
        // Baixo risco (verde)
        array('name' => 'Muito Baixo', 'probability' => 1, 'impact' => 1, 'result' => 1, 'classification' => 'low', 'color' => '#198754', 'deadline_days' => 180),
        array('name' => 'Baixo', 'probability' => 1, 'impact' => 2, 'result' => 2, 'classification' => 'low', 'color' => '#198754', 'deadline_days' => 120),
        array('name' => 'Baixo', 'probability' => 2, 'impact' => 1, 'result' => 2, 'classification' => 'low', 'color' => '#198754', 'deadline_days' => 120),
        // Médio risco (amarelo)
        array('name' => 'Médio', 'probability' => 1, 'impact' => 3, 'result' => 3, 'classification' => 'moderate', 'color' => '#ffc107', 'deadline_days' => 90),
        array('name' => 'Médio', 'probability' => 2, 'impact' => 2, 'result' => 4, 'classification' => 'moderate', 'color' => '#ffc107', 'deadline_days' => 90),
        array('name' => 'Médio', 'probability' => 3, 'impact' => 1, 'result' => 3, 'classification' => 'moderate', 'color' => '#ffc107', 'deadline_days' => 90),
        // Alto risco (laranja)
        array('name' => 'Alto', 'probability' => 2, 'impact' => 3, 'result' => 6, 'classification' => 'high', 'color' => '#fd7e14', 'deadline_days' => 60),
        array('name' => 'Alto', 'probability' => 3, 'impact' => 2, 'result' => 6, 'classification' => 'high', 'color' => '#fd7e14', 'deadline_days' => 60),
        array('name' => 'Alto', 'probability' => 4, 'impact' => 1, 'result' => 4, 'classification' => 'high', 'color' => '#fd7e14', 'deadline_days' => 60),
        // Crítico (vermelho)
        array('name' => 'Crítico', 'probability' => 3, 'impact' => 3, 'result' => 9, 'classification' => 'critical', 'color' => '#dc3545', 'deadline_days' => 30),
        array('name' => 'Crítico', 'probability' => 4, 'impact' => 2, 'result' => 8, 'classification' => 'critical', 'color' => '#dc3545', 'deadline_days' => 30),
        array('name' => 'Crítico', 'probability' => 5, 'impact' => 1, 'result' => 5, 'classification' => 'critical', 'color' => '#dc3545', 'deadline_days' => 30),
        // Emergencial (roxo)
        array('name' => 'Emergencial', 'probability' => 4, 'impact' => 3, 'result' => 12, 'classification' => 'emergential', 'color' => '#6f42c1', 'deadline_days' => 7),
        array('name' => 'Emergencial', 'probability' => 5, 'impact' => 2, 'result' => 10, 'classification' => 'emergential', 'color' => '#6f42c1', 'deadline_days' => 7),
        array('name' => 'Emergencial', 'probability' => 5, 'impact' => 3, 'result' => 15, 'classification' => 'emergential', 'color' => '#6f42c1', 'deadline_days' => 3),
    );
    
    foreach ($matrix as $m) {
        $m['created_at'] = get_my_local_time();
        $db->insert($risk_matrix_table, $m);
    }
    $results[] = "Matriz de risco padrão inserida";
}

// ==================== 5. LOG DE VALIDAÇÃO ====================
$nc_log_table = $dbprefix . 'laudo_nc_logs';
if (!$db->tableExists($nc_log_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$nc_log_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nc_id` INT(11) NOT NULL,
        `action` VARCHAR(30) NOT NULL,
        `old_status` VARCHAR(30) NULL,
        `new_status` VARCHAR(30) NOT NULL,
        `comment` TEXT NULL,
        `user_id` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `nc_id` (`nc_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_nc_logs";
}

return array(
    'success' => true,
    'results' => $results
);