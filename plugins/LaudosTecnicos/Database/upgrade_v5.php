<?php

/**
 * Migration: Checklists, Medições, Equipamentos e Normas
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// ==================== 1. BIBLIOTECA DE CHECKLISTS ====================
$checklists_table = $dbprefix . 'laudo_checklists';
if (!$db->tableExists($checklists_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$checklists_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `code` VARCHAR(50) NOT NULL,
        `category` VARCHAR(100) NULL,
        `laudo_type_id` INT(11) NULL,
        `description` TEXT NULL,
        `version` INT(11) NOT NULL DEFAULT 1,
        `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
        `responsible_id` INT(11) NULL,
        `published_at` DATETIME NULL,
        `created_by` INT(11) NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `updated_by` INT(11) NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `code_version` (`code`, `version`),
        KEY `laudo_type_id` (`laudo_type_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_checklists";
}

// Itens do checklist
$checklist_items_table = $dbprefix . 'laudo_checklist_items';
if (!$db->tableExists($checklist_items_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$checklist_items_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `checklist_id` INT(11) NOT NULL,
        `group_name` VARCHAR(100) NULL,
        `code` VARCHAR(50) NULL,
        `question` TEXT NOT NULL,
        `guidance` TEXT NULL,
        `response_type` VARCHAR(30) NOT NULL DEFAULT 'conforme_nao_conforme',
        `expected_answer` VARCHAR(100) NULL,
        `severity` VARCHAR(20) NOT NULL DEFAULT 'medium',
        `weight` DECIMAL(5,2) NOT NULL DEFAULT 1.0,
        `is_required` TINYINT(1) NOT NULL DEFAULT 0,
        `evidence_required` TINYINT(1) NOT NULL DEFAULT 0,
        `photo_required` TINYINT(1) NOT NULL DEFAULT 0,
        `measurement_required` TINYINT(1) NOT NULL DEFAULT 0,
        `observation_required` TINYINT(1) NOT NULL DEFAULT 0,
        `standard_code` VARCHAR(50) NULL,
        `generates_nc` TINYINT(1) NOT NULL DEFAULT 0,
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `checklist_id` (`checklist_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_checklist_items";
}

// Respostas dos checklists
$checklist_answers_table = $dbprefix . 'laudo_checklist_answers';
if (!$db->tableExists($checklist_answers_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$checklist_answers_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `checklist_id` INT(11) NULL,
        `item_id` INT(11) NOT NULL,
        `response` VARCHAR(100) NULL,
        `observation` TEXT NULL,
        `user_id` INT(11) NOT NULL,
        `answered_at` DATETIME NOT NULL,
        `origin` VARCHAR(20) NOT NULL DEFAULT 'web',
        `photos` TEXT NULL,
        `nc_id` INT(11) NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `item_id` (`item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_checklist_answers";
}

// ==================== 2. TIPOS DE MEDIÇÃO ====================
$measurement_types_table = $dbprefix . 'laudo_measurement_types';
if (!$db->tableExists($measurement_types_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$measurement_types_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `magnitude` VARCHAR(50) NOT NULL,
        `unit` VARCHAR(20) NOT NULL,
        `min_value` DECIMAL(12,4) NULL,
        `max_value` DECIMAL(12,4) NULL,
        `reference_value` DECIMAL(12,4) NULL,
        `tolerance` DECIMAL(12,4) NULL,
        `decimal_places` INT(11) NOT NULL DEFAULT 2,
        `auto_classify` TINYINT(1) NOT NULL DEFAULT 1,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `magnitude` (`magnitude`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_measurement_types";
    
    // Inserir tipos de medição padrão
    $measurements = array(
        array('name' => 'Resistência de Aterramento', 'magnitude' => 'Resistência', 'unit' => 'Ω', 'min_value' => 0, 'max_value' => 100, 'reference_value' => 10, 'tolerance' => 20, 'decimal_places' => 2),
        array('name' => 'Continuidade Elétrica', 'magnitude' => 'Resistência', 'unit' => 'Ω', 'min_value' => 0, 'max_value' => 1, 'reference_value' => 0.5, 'tolerance' => 0.1, 'decimal_places' => 3),
        array('name' => 'Tensão Elétrica', 'magnitude' => 'Tensão', 'unit' => 'V', 'min_value' => 0, 'max_value' => 500, 'reference_value' => 220, 'tolerance' => 10, 'decimal_places' => 1),
        array('name' => 'Corrente Elétrica', 'magnitude' => 'Corrente', 'unit' => 'A', 'min_value' => 0, 'max_value' => 1000, 'reference_value' => 100, 'tolerance' => 10, 'decimal_places' => 2),
        array('name' => 'Potência', 'magnitude' => 'Potência', 'unit' => 'W', 'min_value' => 0, 'max_value' => 100000, 'reference_value' => 1000, 'tolerance' => 100, 'decimal_places' => 0),
        array('name' => 'Temperatura', 'magnitude' => 'Temperatura', 'unit' => '°C', 'min_value' => -20, 'max_value' => 200, 'reference_value' => 40, 'tolerance' => 10, 'decimal_places' => 1),
        array('name' => 'Resistência de Isolação', 'magnitude' => 'Resistência', 'unit' => 'MΩ', 'min_value' => 0, 'max_value' => 1000, 'reference_value' => 100, 'tolerance' => 10, 'decimal_places' => 1),
        array('name' => 'Iluminância', 'magnitude' => 'Iluminância', 'unit' => 'lux', 'min_value' => 0, 'max_value' => 10000, 'reference_value' => 500, 'tolerance' => 50, 'decimal_places' => 0),
        array('name' => 'Atenuação Óptica', 'magnitude' => 'Atenuação', 'unit' => 'dB', 'min_value' => 0, 'max_value' => 10, 'reference_value' => 0.5, 'tolerance' => 0.2, 'decimal_places' => 2),
        array('name' => 'Potência Óptica', 'magnitude' => 'Potência', 'unit' => 'dBm', 'min_value' => -30, 'max_value' => 10, 'reference_value' => 0, 'tolerance' => 3, 'decimal_places' => 2),
    );
    
    foreach ($measurements as $m) {
        $m['created_at'] = get_my_local_time();
        $db->insert($measurement_types_table, $m);
    }
    $results[] = "Inseridos " . count($measurements) . " tipos de medição padrão";
}

// Registro de medições
$measurements_table = $dbprefix . 'laudo_measurements';
if (!$db->tableExists($measurements_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$measurements_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `measurement_type_id` INT(11) NOT NULL,
        `value` DECIMAL(12,4) NOT NULL,
        `unit` VARCHAR(20) NOT NULL,
        `result` VARCHAR(20) NOT NULL,
        `measured_at` DATETIME NOT NULL,
        `location` VARCHAR(255) NULL,
        `equipment_id` INT(11) NULL,
        `responsible_id` INT(11) NULL,
        `photo` VARCHAR(500) NULL,
        `observation` TEXT NULL,
        `gps_lat` DECIMAL(10,8) NULL,
        `gps_lng` DECIMAL(11,8) NULL,
        `checklist_item_id` INT(11) NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `measurement_type_id` (`measurement_type_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_measurements";
}

// ==================== 3. EQUIPAMENTOS ====================
$equipment_table = $dbprefix . 'laudo_equipment';
if (!$db->tableExists($equipment_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$equipment_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `type` VARCHAR(100) NOT NULL,
        `manufacturer` VARCHAR(100) NULL,
        `model` VARCHAR(100) NULL,
        `serial_number` VARCHAR(100) NULL,
        `patrimony` VARCHAR(50) NULL,
        `acquisition_date` DATE NULL,
        `last_calibration` DATE NULL,
        `next_calibration` DATE NULL,
        `certificate_path` VARCHAR(500) NULL,
        `lab_name` VARCHAR(255) NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'active',
        `observations` TEXT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `type` (`type`),
        KEY `status` (`status`),
        KEY `next_calibration` (`next_calibration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_equipment";
}

// ==================== 4. NORMAS TÉCNICAS ====================
$standards_table = $dbprefix . 'laudo_standards';
if (!$db->tableExists($standards_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$standards_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(50) NOT NULL,
        `title` VARCHAR(500) NOT NULL,
        `institution` VARCHAR(100) NOT NULL,
        `category` VARCHAR(100) NULL,
        `edition` VARCHAR(20) NULL,
        `year` VARCHAR(4) NULL,
        `description` TEXT NULL,
        `link` VARCHAR(500) NULL,
        `file_path` VARCHAR(500) NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'active',
        `observations` TEXT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`code`),
        KEY `institution` (`institution`),
        KEY `category` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_standards";
    
    // Inserir normas padrão
    $standards = array(
        array('code' => 'NBR 5419', 'title' => 'Proteção contra descargas atmosféricas', 'institution' => 'ABNT', 'category' => 'SPDA', 'year' => '2015'),
        array('code' => 'NBR 5410', 'title' => 'Instalações elétricas de baixa tensão', 'institution' => 'ABNT', 'category' => 'Elétrica', 'year' => '2020'),
        array('code' => 'NBR IEC 62040', 'title' => 'Sistemas de alimentação ininterrupta', 'institution' => 'ABNT', 'category' => 'Elétrica', 'year' => '2016'),
        array('code' => 'NBR 14039', 'title' => 'Instalações elétricas de média tensão', 'institution' => 'ABNT', 'category' => 'Elétrica', 'year' => '2005'),
        array('code' => 'NBR 13534', 'title' => 'Instalações elétricas em atmosferas explosivas', 'institution' => 'ABNT', 'category' => 'Elétrica', 'year' => '2011'),
        array('code' => 'NBR 11875', 'title' => 'Portões automáticos para veículos', 'institution' => 'ABNT', 'category' => 'Automação', 'year' => '2010'),
        array('code' => 'NBR 9050', 'title' => 'Acessibilidade a edificações', 'institution' => 'ABNT', 'category' => 'Acessibilidade', 'year' => '2020'),
        array('code' => 'NBR 14655', 'title' => 'Elevadores elétricos de passageiros', 'institution' => 'ABNT', 'category' => 'Elevadores', 'year' => '2014'),
        array('code' => 'NBR NM 313', 'title' => 'Medidores de energia elétrica', 'institution' => 'ABNT', 'category' => 'Medição', 'year' => '2007'),
        array('code' => 'NBR IEC 60825', 'title' => 'Segurança de produtos a laser', 'institution' => 'IEC', 'category' => 'Segurança', 'year' => '2014'),
    );
    
    foreach ($standards as $s) {
        $s['created_at'] = get_my_local_time();
        $s['status'] = 'active';
        $db->insert($standards_table, $s);
    }
    $results[] = "Inseridas " . count($standards) . " normas padrão";
}

// Vincular normas a tipos de laudo
$standard_types_table = $dbprefix . 'laudo_standard_types';
if (!$db->tableExists($standard_types_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$standard_types_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `standard_id` INT(11) NOT NULL,
        `laudo_type_id` INT(11) NOT NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `standard_type` (`standard_id`, `laudo_type_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_standard_types";
}

return array(
    'success' => true,
    'results' => $results
);