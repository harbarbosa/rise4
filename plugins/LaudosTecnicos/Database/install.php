<?php

/**
 * Migration: Criar tabelas do Laudos Técnicos
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

// Verificar se as tabelas já existem
if ($db->tableExists($dbprefix . 'laudos_tecnicos')) {
    return array(
        'success' => true,
        'message' => 'Tabelas do Laudos Técnicos já existem'
    );
}

$sql = "
-- Tabela de tipos de laudo
CREATE TABLE IF NOT EXISTS `{$dbprefix}laudo_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `prefix` VARCHAR(20) NULL,
    `require_inspection` TINYINT(1) NOT NULL DEFAULT 0,
    `require_approval` TINYINT(1) NOT NULL DEFAULT 1,
    `validity_days` INT(11) NULL,
    `show_in_client_portal` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `created_by` (`created_by`),
    KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de categorias de laudo
CREATE TABLE IF NOT EXISTS `{$dbprefix}laudo_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `color` VARCHAR(20) NULL DEFAULT '#3788d8',
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `created_by` (`created_by`),
    KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela principal de laudos
CREATE TABLE IF NOT EXISTS `{$dbprefix}laudos_tecnicos` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `laudo_type_id` INT(11) NULL,
    `category_id` INT(11) NULL,
    `client_id` INT(11) NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `version` INT(11) NOT NULL DEFAULT 1,
    `proposal_id` INT(11) NULL,
    `project_id` INT(11) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(255) NULL,
    `state` VARCHAR(50) NULL,
    `technician_id` INT(11) NULL,
    `reviewer_id` INT(11) NULL,
    `approver_id` INT(11) NULL,
    `inspection_date` DATE NULL,
    `issue_date` DATE NULL,
    `valid_until` DATE NULL,
    `file_path` VARCHAR(500) NULL,
    `observations` TEXT NULL,
    `internal_notes` TEXT NULL,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `laudo_type_id` (`laudo_type_id`),
    KEY `client_id` (`client_id`),
    KEY `status` (`status`),
    KEY `technician_id` (`technician_id`),
    KEY `created_by` (`created_by`),
    KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de configurações do módulo
CREATE TABLE IF NOT EXISTS `{$dbprefix}laudo_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NULL,
    `module_name` VARCHAR(255) NOT NULL DEFAULT 'Laudos Técnicos',
    `laudo_prefix` VARCHAR(20) NOT NULL DEFAULT 'LAU',
    `number_format` VARCHAR(50) NOT NULL DEFAULT '{PREFIX}-{YEAR}{MONTH}{SEQUENCE}',
    `next_number` INT(11) NOT NULL DEFAULT 1,
    `logo_path` VARCHAR(500) NULL,
    `primary_color` VARCHAR(20) NOT NULL DEFAULT '#3788d8',
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'America/Sao_Paulo',
    `language` VARCHAR(10) NOT NULL DEFAULT 'pt-BR',
    `date_format` VARCHAR(20) NOT NULL DEFAULT 'd/m/Y',
    `module_active` TINYINT(1) NOT NULL DEFAULT 1,
    `enable_detailed_logs` TINYINT(1) NOT NULL DEFAULT 0,
    `default_validity_days` INT(11) NOT NULL DEFAULT 365,
    `require_inspection` TINYINT(1) NOT NULL DEFAULT 1,
    `require_approval` TINYINT(1) NOT NULL DEFAULT 1,
    `auto_notify_client` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de logs de auditoria
CREATE TABLE IF NOT EXISTS `{$dbprefix}laudo_audit_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `laudo_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `old_data` TEXT NULL,
    `new_data` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `laudo_id` (`laudo_id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de templates
CREATE TABLE IF NOT EXISTS `{$dbprefix}laudo_templates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `laudo_type_id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `content` LONGTEXT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `laudo_type_id` (`laudo_type_id`),
    KEY `created_by` (`created_by`),
    KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de inspeções
CREATE TABLE IF NOT EXISTS `{$dbprefix}laudo_inspections` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `laudo_id` INT(11) NOT NULL,
    `technician_id` INT(11) NOT NULL,
    `inspection_date` DATETIME NOT NULL,
    `address` TEXT NULL,
    `city` VARCHAR(255) NULL,
    `state` VARCHAR(50) NULL,
    `weather_conditions` VARCHAR(100) NULL,
    `findings` TEXT NULL,
    `recommendations` TEXT NULL,
    `photos` TEXT NULL,
    `signature_path` VARCHAR(500) NULL,
    `signature_name` VARCHAR(255) NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'scheduled',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `laudo_id` (`laudo_id`),
    KEY `technician_id` (`technician_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$statements = array_filter(array_map('trim', explode(';', $sql)));
$errors = array();
$tables = array();

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    $query_ok = $db->query($statement);
    
    if (preg_match("/CREATE TABLE/i", $statement)) {
        if (preg_match("/`(\w+)`/", $statement, $match)) {
            $tables[] = $match[1];
        }
    }
    
    if (!$query_ok) {
        $errors[] = 'Erro: ' . $db->error() . ' - ' . substr($statement, 0, 100);
    }
}

// Inserir configurações padrão
$company_id = get_company_id();
$settings_data = array(
    'company_id' => $company_id,
    'module_name' => 'Laudos Técnicos',
    'laudo_prefix' => 'LAU',
    'number_format' => '{PREFIX}-{YEAR}{MONTH}{SEQUENCE}',
    'next_number' => 1,
    'primary_color' => '#3788d8',
    'timezone' => 'America/Sao_Paulo',
    'language' => 'pt-BR',
    'date_format' => 'd/m/Y',
    'module_active' => 1,
    'enable_detailed_logs' => 0,
    'default_validity_days' => 365,
    'require_inspection' => 1,
    'require_approval' => 1,
    'auto_notify_client' => 1,
    'created_by' => isset($this->login_user->id) ? $this->login_user->id : null,
    'created_at' => get_my_local_time()
);

$db->insert($dbprefix . 'laudo_settings', $settings_data);

// Inserir tipos de laudo padrão
$default_types = array(
    array('name' => 'Laudo Técnico de Instação Elétrica', 'description' => 'Laudo técnico para instalações elétricas', 'prefix' => 'LIE', 'require_inspection' => 1),
    array('name' => 'Laudo Técnico de SPDA', 'description' => 'Laudo do sistema de proteção contra descargas atmosféricas', 'prefix' => 'LSPDA', 'require_inspection' => 1),
    array('name' => 'Laudo de Aterramento', 'description' => 'Laudo técnico de sistemas de aterramento', 'prefix' => 'LAT', 'require_inspection' => 1),
    array('name' => 'Laudo de Conformidade NR-10', 'description' => 'Laudo de conformidade conforme norma NR-10', 'prefix' => 'LNR10', 'require_inspection' => 1),
    array('name' => 'Laudo de Gás Refrigerante', 'description' => 'Laudo técnico para sistemas de refrigeração', 'prefix' => 'LGR', 'require_inspection' => 1),
    array('name' => 'Laudo de Estrutura Metálica', 'description' => 'Laudo técnico de estruturas metálicas', 'prefix' => 'LEM', 'require_inspection' => 1),
    array('name' => 'Laudo de磐石', 'description' => 'Laudo técnico de磐石', 'prefix' => 'LPC', 'require_inspection' => 1),
    array('name' => 'Laudo de Sistemas de Ventilação', 'description' => 'Laudo de sistemas de ventilação e exaustão', 'prefix' => 'LSV', 'require_inspection' => 1),
    array('name' => 'Laudo Técnico Geral', 'description' => 'Laudo técnico genérico', 'prefix' => 'LTG', 'require_inspection' => 0),
);

foreach ($default_types as $type) {
    $type_data = array(
        'name' => $type['name'],
        'description' => $type['description'],
        'prefix' => $type['prefix'],
        'require_inspection' => $type['require_inspection'],
        'require_approval' => 1,
        'validity_days' => 365,
        'created_by' => null,
        'created_at' => get_my_local_time()
    );
    $db->insert($dbprefix . 'laudo_types', $type_data);
}

// Inserir categorias padrão
$default_categories = array(
    array('name' => 'Elétrico', 'color' => '#f39c12'),
    array('name' => 'Estrutural', 'color' => '#e74c3c'),
    array('name' => 'Hidrossanitário', 'color' => '#3498db'),
    array('name' => 'Incêndio', 'color' => '#9b59b6'),
    array('name' => 'Ambiental', 'color' => '#27ae60'),
    array('name' => 'Segurança do Trabalho', 'color' => '#e67e22'),
    array('name' => 'Manutenção', 'color' => '#1abc9c'),
    array('name' => 'Outros', 'color' => '#95a5a6'),
);

foreach ($default_categories as $cat) {
    $cat_data = array(
        'name' => $cat['name'],
        'description' => '',
        'color' => $cat['color'],
        'created_by' => null,
        'created_at' => get_my_local_time()
    );
    $db->insert($dbprefix . 'laudo_categories', $cat_data);
}

$result = array(
    'success' => count($errors) === 0,
    'tables' => $tables,
    'errors' => $errors
);

return $result;