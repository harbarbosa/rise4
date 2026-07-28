<?php

/**
 * Migration: Templates e Campos Dinâmicos
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// Adicionar campos de template na tabela de laudos se não existirem
$laudos_table = $dbprefix . 'laudos_tecnicos';
$laudos_fields = $db->getFieldNames($laudos_table);

if (!in_array('template_id', $laudos_fields)) {
    $db->query("ALTER TABLE `$laudos_table` ADD COLUMN `template_id` INT(11) NULL AFTER `project_id`");
    $results[] = "Adicionado campo template_id em laudos_tecnicos";
}
if (!in_array('template_version', $laudos_fields)) {
    $db->query("ALTER TABLE `$laudos_table` ADD COLUMN `template_version` INT(11) NULL AFTER `template_id`");
    $results[] = "Adicionado campo template_version em laudos_tecnicos";
}

// 1. Tabela de templates
$templates_table = $dbprefix . 'laudo_templates';
if (!$db->tableExists($templates_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$templates_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `code` VARCHAR(50) NOT NULL,
        `description` TEXT NULL,
        `laudo_type_id` INT(11) NULL,
        `category_id` INT(11) NULL,
        `version` INT(11) NOT NULL DEFAULT 1,
        `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
        `is_default` TINYINT(1) NOT NULL DEFAULT 0,
        `is_published` TINYINT(1) NOT NULL DEFAULT 0,
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
    $results[] = "Criada tabela laudo_templates";
}

// 2. Tabela de seções do template
$sections_table = $dbprefix . 'laudo_template_sections';
if (!$db->tableExists($sections_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$sections_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `template_id` INT(11) NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `code` VARCHAR(50) NOT NULL,
        `description` TEXT NULL,
        `section_type` VARCHAR(50) NOT NULL DEFAULT 'custom',
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `page_break` TINYINT(1) NOT NULL DEFAULT 0,
        `show_numbering` TINYINT(1) NOT NULL DEFAULT 1,
        `visible_web` TINYINT(1) NOT NULL DEFAULT 1,
        `visible_mobile` TINYINT(1) NOT NULL DEFAULT 1,
        `visible_pdf` TINYINT(1) NOT NULL DEFAULT 1,
        `is_required` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `template_id` (`template_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_template_sections";
}

// 3. Tabela de campos
$fields_table = $dbprefix . 'laudo_template_fields';
if (!$db->tableExists($fields_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$fields_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `section_id` INT(11) NOT NULL,
        `field_name` VARCHAR(100) NOT NULL,
        `field_key` VARCHAR(50) NOT NULL,
        `field_type` VARCHAR(30) NOT NULL,
        `label` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `placeholder` VARCHAR(255) NULL,
        `default_value` TEXT NULL,
        `is_required` TINYINT(1) NOT NULL DEFAULT 0,
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `width` VARCHAR(20) NOT NULL DEFAULT '100%',
        `validation_rules` TEXT NULL,
        `mask` VARCHAR(50) NULL,
        `help_text` TEXT NULL,
        `visible_web` TINYINT(1) NOT NULL DEFAULT 1,
        `visible_mobile` TINYINT(1) NOT NULL DEFAULT 1,
        `visible_pdf` TINYINT(1) NOT NULL DEFAULT 1,
        `read_only` TINYINT(1) NOT NULL DEFAULT 0,
        `options` TEXT NULL,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `section_id` (`section_id`),
        UNIQUE KEY `section_field_key` (`section_id`, `field_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_template_fields";
}

// 4. Tabela de regras condicionais
$rules_table = $dbprefix . 'laudo_template_rules';
if (!$db->tableExists($rules_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$rules_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `template_id` INT(11) NOT NULL,
        `field_id` INT(11) NULL,
        `section_id` INT(11) NULL,
        `rule_type` VARCHAR(30) NOT NULL,
        `condition_field` VARCHAR(50) NULL,
        `condition_operator` VARCHAR(20) NULL,
        `condition_value` TEXT NULL,
        `action` VARCHAR(30) NOT NULL,
        `action_target` VARCHAR(50) NULL,
        `action_value` TEXT NULL,
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `template_id` (`template_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_template_rules";
}

// 5. Tabela de dados do laudo (valores dos campos)
$laudo_data_table = $dbprefix . 'laudo_data';
if (!$db->tableExists($laudo_data_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$laudo_data_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `laudo_id` INT(11) NOT NULL,
        `template_id` INT(11) NULL,
        `template_version` INT(11) NULL,
        `section_id` INT(11) NULL,
        `field_id` INT(11) NULL,
        `field_key` VARCHAR(50) NULL,
        `field_value` TEXT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `laudo_id` (`laudo_id`),
        KEY `template_id` (`template_id`),
        KEY `field_key` (`field_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_data";
}

// 6. Inserir templates padrão
$types_table = $dbprefix . 'laudo_types';
$categories_table = $dbprefix . 'laudo_categories';

// Verificar se já existem templates
$existing = $db->query("SELECT COUNT(*) as count FROM {$templates_table} WHERE deleted=0")->getRow();
if (!$existing || $existing->count == 0) {
    // Buscar IDs de tipos e categorias
    $type_spda = $db->query("SELECT id FROM {$types_table} WHERE code='LAUDO_SPDA' LIMIT 1")->getRow();
    $type_eletrica = $db->query("SELECT id FROM {$types_table} WHERE code='LAUDO_ELETRICA' LIMIT 1")->getRow();
    $type_cftv = $db->query("SELECT id FROM {$types_table} WHERE code='LAUDO_CFTV' LIMIT 1")->getRow();
    
    // Template SPDA
    if ($type_spda) {
        $template_id = null;
        $db->insert($templates_table, array(
            'name' => 'Laudo de SPDA Padrão',
            'code' => 'LAUDO_SPDA_PADRAO',
            'description' => 'Template padrão para Laudos de Sistema de Proteção contra Descargas Atmosféricas',
            'laudo_type_id' => $type_spda->id,
            'version' => 1,
            'status' => 'published',
            'is_default' => 1,
            'is_published' => 1,
            'published_at' => get_my_local_time(),
            'created_at' => get_my_local_time(),
            'created_by' => 1
        ));
        $template_id = $db->insertID();
        
        // Seções padrão SPDA
        $sections = array(
            array('name' => 'Capa', 'code' => 'capa', 'section_type' => 'cover', 'sort_order' => 1),
            array('name' => 'Identificação', 'code' => 'identificacao', 'section_type' => 'identification', 'sort_order' => 2),
            array('name' => 'Objetivo', 'code' => 'objetivo', 'section_type' => 'objective', 'sort_order' => 3),
            array('name' => 'Escopo', 'code' => 'escopo', 'section_type' => 'scope', 'sort_order' => 4),
            array('name' => 'Metodologia', 'code' => 'metodologia', 'section_type' => 'methodology', 'sort_order' => 5),
            array('name' => 'Normas Técnicas', 'code' => 'normas', 'section_type' => 'standards', 'sort_order' => 6),
            array('name' => 'Descrição da Instalação', 'code' => 'descricao', 'section_type' => 'installation', 'sort_order' => 7),
            array('name' => 'Checklist de Verificação', 'code' => 'checklist', 'section_type' => 'checklist', 'sort_order' => 8),
            array('name' => 'Medições', 'code' => 'medicoes', 'section_type' => 'measurements', 'sort_order' => 9),
            array('name' => 'Não Conformidades', 'code' => 'nao_conformidades', 'section_type' => 'non_conformities', 'sort_order' => 10),
            array('name' => 'Recomendações', 'code' => 'recomendacoes', 'section_type' => 'recommendations', 'sort_order' => 11),
            array('name' => 'Conclusão', 'code' => 'conclusao', 'section_type' => 'conclusion', 'sort_order' => 12),
            array('name' => 'Assinaturas', 'code' => 'assinaturas', 'section_type' => 'signatures', 'sort_order' => 13, 'page_break' => 1)
        );
        
        foreach ($sections as $section) {
            $section['template_id'] = $template_id;
            $section['created_at'] = get_my_local_time();
            $db->insert($sections_table, $section);
        }
        $results[] = "Template SPDA padrão criado";
    }
    
    // Template Elétrico
    if ($type_eletrica) {
        $template_id = null;
        $db->insert($templates_table, array(
            'name' => 'Laudo de Instalações Elétricas Padrão',
            'code' => 'LAUDO_ELETRICA_PADRAO',
            'description' => 'Template padrão para Laudos de Instalações Elétricas',
            'laudo_type_id' => $type_eletrica->id,
            'version' => 1,
            'status' => 'published',
            'is_default' => 1,
            'is_published' => 1,
            'published_at' => get_my_local_time(),
            'created_at' => get_my_local_time(),
            'created_by' => 1
        ));
        $template_id = $db->insertID();
        
        $sections = array(
            array('name' => 'Capa', 'code' => 'capa', 'section_type' => 'cover', 'sort_order' => 1),
            array('name' => 'Identificação', 'code' => 'identificacao', 'section_type' => 'identification', 'sort_order' => 2),
            array('name' => 'Objetivo', 'code' => 'objetivo', 'section_type' => 'objective', 'sort_order' => 3),
            array('name' => 'Escopo', 'code' => 'escopo', 'section_type' => 'scope', 'sort_order' => 4),
            array('name' => 'Metodologia', 'code' => 'metodologia', 'section_type' => 'methodology', 'sort_order' => 5),
            array('name' => 'Normas Técnicas', 'code' => 'normas', 'section_type' => 'standards', 'sort_order' => 6),
            array('name' => 'Descrição da Instalação', 'code' => 'descricao', 'section_type' => 'installation', 'sort_order' => 7),
            array('name' => 'Medições', 'code' => 'medicoes', 'section_type' => 'measurements', 'sort_order' => 8),
            array('name' => 'Análise Termográfica', 'code' => 'termografia', 'section_type' => 'thermal', 'sort_order' => 9),
            array('name' => 'Não Conformidades', 'code' => 'nao_conformidades', 'section_type' => 'non_conformities', 'sort_order' => 10),
            array('name' => 'Recomendações', 'code' => 'recomendacoes', 'section_type' => 'recommendations', 'sort_order' => 11),
            array('name' => 'Conclusão', 'code' => 'conclusao', 'section_type' => 'conclusion', 'sort_order' => 12),
            array('name' => 'Assinaturas', 'code' => 'assinaturas', 'section_type' => 'signatures', 'sort_order' => 13, 'page_break' => 1)
        );
        
        foreach ($sections as $section) {
            $section['template_id'] = $template_id;
            $section['created_at'] = get_my_local_time();
            $db->insert($sections_table, $section);
        }
        $results[] = "Template Elétrico padrão criado";
    }
}

return array(
    'success' => true,
    'results' => $results
);