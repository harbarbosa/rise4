<?php

/**
 * Migration: Tabelas Avançadas do Laudos Técnicos
 * Inclui: Categorias expandidas, Status, Transições, Histórico
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// 1. Atualizar tabela de categorias se necessário
$categories_table = $dbprefix . 'laudo_categories';
if ($db->tableExists($categories_table)) {
    $fields = $db->getFieldNames($categories_table);
    
    if (!in_array('code', $fields)) {
        $db->query("ALTER TABLE `$categories_table` ADD COLUMN `code` VARCHAR(50) NULL AFTER `name`");
        $results[] = "Adicionado campo code em laudo_categories";
    }
    if (!in_array('icon', $fields)) {
        $db->query("ALTER TABLE `$categories_table` ADD COLUMN `icon` VARCHAR(50) NULL AFTER `code`");
        $results[] = "Adicionado campo icon em laudo_categories";
    }
    if (!in_array('sort_order', $fields)) {
        $db->query("ALTER TABLE `$categories_table` ADD COLUMN `sort_order` INT(11) NOT NULL DEFAULT 0 AFTER `icon`");
        $results[] = "Adicionado campo sort_order em laudo_categories";
    }
    if (!in_array('status', $fields)) {
        $db->query("ALTER TABLE `$categories_table` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sort_order`");
        $results[] = "Adicionado campo status em laudo_categories";
    }
}

// 2. Atualizar tabela de tipos se necessário
$types_table = $dbprefix . 'laudo_types';
if ($db->tableExists($types_table)) {
    $fields = $db->getFieldNames($types_table);
    
    if (!in_array('code', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `code` VARCHAR(50) NULL AFTER `name`");
        $results[] = "Adicionado campo code em laudo_types";
    }
    if (!in_array('category_id', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `category_id` INT(11) NULL AFTER `code`");
        $results[] = "Adicionado campo category_id em laudo_types";
    }
    if (!in_array('default_template_id', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `default_template_id` INT(11) NULL AFTER `require_approval`");
        $results[] = "Adicionado campo default_template_id em laudo_types";
    }
    if (!in_array('require_technician', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `require_technician` TINYINT(1) NOT NULL DEFAULT 0 AFTER `default_template_id`");
        $results[] = "Adicionado campo require_technician em laudo_types";
    }
    if (!in_array('require_review', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `require_review` TINYINT(1) NOT NULL DEFAULT 1 AFTER `require_technician`");
        $results[] = "Adicionado campo require_review em laudo_types";
    }
    if (!in_array('require_signature', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `require_signature` TINYINT(1) NOT NULL DEFAULT 0 AFTER `require_review`");
        $results[] = "Adicionado campo require_signature em laudo_types";
    }
    if (!in_array('require_equipment', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `require_equipment` TINYINT(1) NOT NULL DEFAULT 0 AFTER `require_signature`");
        $results[] = "Adicionado campo require_equipment em laudo_types";
    }
    if (!in_array('allow_mobile', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `allow_mobile` TINYINT(1) NOT NULL DEFAULT 1 AFTER `require_equipment`");
        $results[] = "Adicionado campo allow_mobile em laudo_types";
    }
    if (!in_array('sort_order', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `sort_order` INT(11) NOT NULL DEFAULT 0 AFTER `allow_mobile`");
        $results[] = "Adicionado campo sort_order em laudo_types";
    }
    if (!in_array('status', $fields)) {
        $db->query("ALTER TABLE `$types_table` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sort_order`");
        $results[] = "Adicionado campo status em laudo_types";
    }
}

// 3. Criar tabela de status
$status_table = $dbprefix . 'laudo_status';
if (!$db->tableExists($status_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$status_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `code` VARCHAR(50) NOT NULL UNIQUE,
        `description` TEXT NULL,
        `color` VARCHAR(20) NOT NULL DEFAULT '#3788d8',
        `icon` VARCHAR(50) NULL,
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `is_initial` TINYINT(1) NOT NULL DEFAULT 0,
        `is_final` TINYINT(1) NOT NULL DEFAULT 0,
        `is_cancel` TINYINT(1) NOT NULL DEFAULT 0,
        `allow_edit` TINYINT(1) NOT NULL DEFAULT 1,
        `allow_delete` TINYINT(1) NOT NULL DEFAULT 0,
        `allow_issue` TINYINT(1) NOT NULL DEFAULT 0,
        `require_comment` TINYINT(1) NOT NULL DEFAULT 0,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `code` (`code`),
        KEY `active` (`active`),
        KEY `sort_order` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_status";
}

// 4. Criar tabela de transições
$transitions_table = $dbprefix . 'laudo_status_transitions';
if (!$db->tableExists($transitions_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$transitions_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `from_status_id` INT(11) NOT NULL,
        `to_status_id` INT(11) NOT NULL,
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `allowed_roles` TEXT NULL,
        `required_permissions` VARCHAR(255) NULL,
        `require_comment` TINYINT(1) NOT NULL DEFAULT 0,
        `validations` TEXT NULL,
        `notify` TINYINT(1) NOT NULL DEFAULT 0,
        `create_task` TINYINT(1) NOT NULL DEFAULT 0,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `from_status_id` (`from_status_id`),
        KEY `to_status_id` (`to_status_id`),
        KEY `active` (`active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_status_transitions";
}

// 5. Criar tabela de histórico de status
$history_table = $dbprefix . 'laudo_status_history';
if (!$db->tableExists($history_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$history_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `from_status_id` INT(11) NULL,
        `to_status_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `comment` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `origin` VARCHAR(20) NOT NULL DEFAULT 'web',
        `created_at` DATETIME NOT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `user_id` (`user_id`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_status_history";
}

// 6. Inserir status padrão
$status_list = array(
    array('name' => 'Rascunho', 'code' => 'draft', 'color' => '#6c757d', 'icon' => 'file', 'sort_order' => 1, 'is_initial' => 1, 'allow_edit' => 1, 'allow_delete' => 1),
    array('name' => 'Solicitação Recebida', 'code' => 'requested', 'color' => '#0d6efd', 'icon' => 'inbox', 'sort_order' => 2),
    array('name' => 'Aguardando Agendamento', 'code' => 'waiting_schedule', 'color' => '#6c757d', 'icon' => 'calendar', 'sort_order' => 3),
    array('name' => 'Visita Agendada', 'code' => 'scheduled', 'color' => '#0dcaf0', 'icon' => 'calendar-check', 'sort_order' => 4),
    array('name' => 'Em Inspeção', 'code' => 'inspecting', 'color' => '#ffc107', 'icon' => 'search', 'sort_order' => 5),
    array('name' => 'Coleta Concluída', 'code' => 'collection_done', 'color' => '#fd7e14', 'icon' => 'check-circle', 'sort_order' => 6),
    array('name' => 'Aguardando Informações', 'code' => 'waiting_info', 'color' => '#6c757d', 'icon' => 'clock', 'sort_order' => 7),
    array('name' => 'Em Elaboração', 'code' => 'elaborating', 'color' => '#0d6efd', 'icon' => 'edit-3', 'sort_order' => 8),
    array('name' => 'Aguardando Revisão', 'code' => 'pending_review', 'color' => '#ffc107', 'icon' => 'eye', 'sort_order' => 9, 'require_review' => 1),
    array('name' => 'Em Correção', 'code' => 'correcting', 'color' => '#fd7e14', 'icon' => 'refresh-cw', 'sort_order' => 10),
    array('name' => 'Aguardando Aprovação', 'code' => 'pending_approval', 'color' => '#ffc107', 'icon' => 'check-square', 'sort_order' => 11),
    array('name' => 'Aprovado', 'code' => 'approved', 'color' => '#198754', 'icon' => 'check-circle', 'sort_order' => 12, 'is_final' => 0),
    array('name' => 'Assinado', 'code' => 'signed', 'color' => '#198754', 'icon' => 'pen-tool', 'sort_order' => 13, 'allow_issue' => 1),
    array('name' => 'Emitido', 'code' => 'issued', 'color' => '#198754', 'icon' => 'send', 'sort_order' => 14, 'is_final' => 1),
    array('name' => 'Enviado ao Cliente', 'code' => 'sent', 'color' => '#198754', 'icon' => 'mail', 'sort_order' => 15, 'is_final' => 1),
    array('name' => 'Aceito pelo Cliente', 'code' => 'accepted', 'color' => '#198754', 'icon' => 'thumbs-up', 'sort_order' => 16, 'is_final' => 1),
    array('name' => 'Reprovado', 'code' => 'rejected', 'color' => '#dc3545', 'icon' => 'thumbs-down', 'sort_order' => 17, 'is_cancel' => 1),
    array('name' => 'Vencido', 'code' => 'expired', 'color' => '#dc3545', 'icon' => 'alert-circle', 'sort_order' => 18, 'is_final' => 1),
    array('name' => 'Cancelado', 'code' => 'canceled', 'color' => '#6c757d', 'icon' => 'x-circle', 'sort_order' => 19, 'is_cancel' => 1, 'is_final' => 1),
);

foreach ($status_list as $status) {
    $exists = $db->query("SELECT id FROM {$status_table} WHERE code='{$status['code']}'")->getRow();
    if (!$exists) {
        $status['created_at'] = get_my_local_time();
        $db->insert($status_table, $status);
    }
}
$results[] = "Status padrão inseridos";

// 7. Inserir transições padrão
$transitions_list = array(
    array('from' => 'draft', 'to' => 'requested'),
    array('from' => 'draft', 'to' => 'canceled'),
    array('from' => 'requested', 'to' => 'waiting_schedule'),
    array('from' => 'requested', 'to' => 'canceled'),
    array('from' => 'waiting_schedule', 'to' => 'scheduled'),
    array('from' => 'waiting_schedule', 'to' => 'canceled'),
    array('from' => 'scheduled', 'to' => 'inspecting'),
    array('from' => 'scheduled', 'to' => 'waiting_schedule'),
    array('from' => 'scheduled', 'to' => 'canceled'),
    array('from' => 'inspecting', 'to' => 'collection_done'),
    array('from' => 'inspecting', 'to' => 'waiting_info'),
    array('from' => 'inspecting', 'to' => 'canceled'),
    array('from' => 'collection_done', 'to' => 'elaborating'),
    array('from' => 'collection_done', 'to' => 'waiting_info'),
    array('from' => 'waiting_info', 'to' => 'elaborating'),
    array('from' => 'waiting_info', 'to' => 'canceled'),
    array('from' => 'elaborating', 'to' => 'pending_review'),
    array('from' => 'elaborating', 'to' => 'canceled'),
    array('from' => 'pending_review', 'to' => 'correcting'),
    array('from' => 'pending_review', 'to' => 'pending_approval'),
    array('from' => 'correcting', 'to' => 'pending_review'),
    array('from' => 'correcting', 'to' => 'canceled'),
    array('from' => 'pending_approval', 'to' => 'approved'),
    array('from' => 'pending_approval', 'to' => 'correcting'),
    array('from' => 'pending_approval', 'to' => 'rejected'),
    array('from' => 'approved', 'to' => 'signed'),
    array('from' => 'approved', 'to' => 'pending_approval'),
    array('from' => 'signed', 'to' => 'issued'),
    array('from' => 'issued', 'to' => 'sent'),
    array('from' => 'sent', 'to' => 'accepted'),
    array('from' => 'sent', 'to' => 'rejected'),
    array('from' => 'accepted', 'to' => 'expired'),
    array('from' => 'expired', 'to' => 'issued'),
    array('from' => 'rejected', 'to' => 'correcting'),
    array('from' => 'rejected', 'to' => 'canceled'),
);

// Buscar IDs dos status
$status_ids = array();
$status_data = $db->query("SELECT id, code FROM {$status_table}")->getResult();
foreach ($status_data as $s) {
    $status_ids[$s->code] = $s->id;
}

$sort = 1;
foreach ($transitions_list as $t) {
    if (!isset($status_ids[$t['from']]) || !isset($status_ids[$t['to']])) continue;
    
    $exists = $db->query("SELECT id FROM {$transitions_table} WHERE from_status_id={$status_ids[$t['from']]} AND to_status_id={$status_ids[$t['to']]}")->getRow();
    if (!$exists) {
        $db->insert($transitions_table, array(
            'from_status_id' => $status_ids[$t['from']],
            'to_status_id' => $status_ids[$t['to']],
            'sort_order' => $sort++,
            'active' => 1,
            'created_at' => get_my_local_time()
        ));
    }
}
$results[] = "Transições padrão inseridas";

return array(
    'success' => true,
    'results' => $results
);