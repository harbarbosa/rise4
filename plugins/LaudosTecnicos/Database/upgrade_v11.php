<?php

/**
 * Migration: Relatórios, Dashboard, Biblioteca de Prompts e Automações
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// ==================== 1. BIBLIOTECA DE PROMPTS ====================
$prompts_table = $dbprefix . 'laudo_prompts';
if (!$db->tableExists($prompts_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$prompts_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `code` VARCHAR(50) NOT NULL,
        `category` VARCHAR(30) NOT NULL,
        `prompt_template` TEXT NOT NULL,
        `variables` JSON NULL,
        `description` TEXT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_by` INT(11) NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `code` (`code`),
        KEY `category` (`category`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_prompts";
    
    // Inserir prompts padrão
    $prompts = array(
        array('name' => 'Organizar Anotações', 'code' => 'organize_notes', 'category' => 'editing', 
            'prompt_template' => 'Organize as anotações de campo de forma clara e profissional para um laudo técnico.\n\nAnotações: {{anotacoes_campo}}\n\nRetorne o texto organizado com estrutura adequada.'),
        array('name' => 'Criar Objetivo', 'code' => 'create_objective', 'category' => 'creation',
            'prompt_template' => 'Elabore o objetivo deste laudo técnico de forma clara e objetiva.\n\nCliente: {{cliente}}\nTipo de Laudo: {{tipo_laudo}}\nEscopo: {{escopo}}\n\nRetorne apenas o objetivo em 1-2 páragrafos.'),
        array('name' => 'Criar Escopo', 'code' => 'create_scope', 'category' => 'creation',
            'prompt_template' => 'Defina o escopo desta inspeção técnica de forma detalhada.\n\nCliente: {{cliente}}\nTipo de Laudo: {{tipo_laudo}}\n\nConsidere:\n- Checklists: {{checklists}}\n- Normas aplicáveis: {{normas}}\n\nRetorne o escopo detalhado.'),
        array('name' => 'Criar Metodologia', 'code' => 'create_methodology', 'category' => 'creation',
            'prompt_template' => 'Descreva a metodologia aplicada nesta inspeção técnica.\n\nTipo de Laudo: {{tipo_laudo}}\nEscopo: {{escopo}}\nMedições realizadas: {{medicoes}}\n\nRetorne a metodologia em formato técnico.'),
        array('name' => 'Elaborar Diagnóstico', 'code' => 'create_diagnosis', 'category' => 'creation',
            'prompt_template' => 'Elabore o diagnóstico técnico com base nos dados coletados.\n\nCliente: {{cliente}}\nAnotações: {{anotacoes_campo}}\nChecklists: {{checklists}}\nMedições: {{medicoes}}\nNão Conformidades: {{nao_conformidades}}\n\nRetorne o diagnóstico detalhado.'),
        array('name' => 'Criar Conclusão', 'code' => 'create_conclusion', 'category' => 'creation',
            'prompt_template' => 'Redija a conclusão técnica do laudo.\n\nCliente: {{cliente}}\nTipo: {{tipo_laudo}}\nObjetivo: {{objetivo}}\nDiagnóstico: [use informações coletadas]\n\nRetorne a conclusão técnica.'),
        array('name' => 'Criar Recomendações', 'code' => 'create_recommendations', 'category' => 'creation',
            'prompt_template' => 'Sugira recomendações técnicas baseadas nos achados.\n\nNão Conformidades: {{nao_conformidades}}\nObjetivo: {{objetivo}}\n\nRetorne lista de recomendações priorizadas.'),
        array('name' => 'Resumo Executivo', 'code' => 'executive_summary', 'category' => 'creation',
            'prompt_template' => 'Crie um resumo executivo para a direção do cliente.\n\nCliente: {{cliente}}\nTipo: {{tipo_laudo}}\nObjetivo: {{objetivo}}\nEscopo: {{escopo}}\nNão Conformidades: {{nao_conformidades}}\n\nRetorne em máximo 300 palavras.'),
        array('name' => 'Descrever Fotografia', 'code' => 'describe_photo', 'category' => 'analysis',
            'prompt_template' => 'Descreva tecnicamente a seguinte imagem para documentação de laudo:\n\n[Descrição provided by user]\n\nRetorne descrição técnica objetiva.'),
        array('name' => 'Verificar Lacunas', 'code' => 'check_gaps', 'category' => 'analysis',
            'prompt_template' => 'Analise o laudo e identifique possíveis lacunas ou informações faltantes.\n\nLaudo: {{tipo_laudo}}\nChecklists respondidos: {{checklists}}\nMedições: {{medicoes}}\nFotografias: {{fotografas}}\n\nRetorne lista de lacunas identificadas.')
    );
    
    foreach ($prompts as &$p) {
        preg_match_all('/\{\{(\w+)\}\}/', $p['prompt_template'], $matches);
        $p['variables'] = json_encode(array_unique($matches[1] ?? []));
        $p['created_at'] = get_my_local_time();
        $db->insert($prompts_table, $p);
    }
    $results[] = "Prompts padrão inseridos";
}

// ==================== 2. RELATÓRIOS PERSONALIZADOS ====================
$reports_table = $dbprefix . 'laudo_reports';
if (!$db->tableExists($reports_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$reports_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `type` VARCHAR(30) NOT NULL,
        `config` JSON NULL,
        `created_by` INT(11) NULL,
        `created_at` DATETIME NULL,
        `last_run_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `type` (`type`),
        KEY `created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_reports";
    
    // Relatórios padrão
    $default_reports = array(
        array('name' => 'Laudos por Período', 'type' => 'laudos_period', 'config' => json_encode(array('date_field' => 'created_at', 'group_by' => 'month'))),
        array('name' => 'Laudos por Cliente', 'type' => 'laudos_client', 'config' => json_encode(array('group_by' => 'client_id'))),
        array('name' => 'Laudos por Status', 'type' => 'laudos_status', 'config' => json_encode(array('group_by' => 'status'))),
        array('name' => 'Laudos por Tipo', 'type' => 'laudos_type', 'config' => json_encode(array('group_by' => 'laudo_type_id'))),
        array('name' => 'Laudos Vencidos', 'type' => 'laudos_overdue', 'config' => json_encode(array('status' => 'expired'))),
        array('name' => 'Não Conformidades', 'type' => 'nonconformities', 'config' => json_encode(array('group_by' => 'classification'))),
        array('name' => 'Planos de Ação', 'type' => 'action_plans', 'config' => json_encode(array('group_by' => 'status'))),
        array('name' => 'Inspeções Improdutivas', 'type' => 'inspections_unproductive', 'config' => json_encode(array('status' => 'unproductive'))),
        array('name' => 'Equipamentos sem Calibração', 'type' => 'equipment_calibration', 'config' => json_encode(array('calibration_status' => 'expired'))),
        array('name' => 'Produtividade', 'type' => 'productivity', 'config' => json_encode(array('metrics' => array('total', 'avg_time'))))
    );
    
    foreach ($default_reports as $r) {
        $r['created_at'] = get_my_local_time();
        $db->insert($reports_table, $r);
    }
    $results[] = "Relatórios padrão inseridos";
}

// ==================== 3. AUTOMAÇÕES (Crons) ====================
$automations_table = $dbprefix . 'laudo_automations';
if (!$db->tableExists($automations_table)) {
    $sql = "CREATE TABLE IF NOT EXISTS `$automations_table` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `code` VARCHAR(50) NOT NULL,
        `description` TEXT NULL,
        `schedule` VARCHAR(100) NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `config` JSON NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `last_run_at` DATETIME NULL,
        `created_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `code` (`code`),
        KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $db->query($sql);
    $results[] = "Criada tabela laudo_automations";
    
    // Automações padrão
    $automations = array(
        array('name' => 'Laudos próximos do vencimento', 'code' => 'laudos_expiring_soon', 'description' => 'Notifica 30 dias antes do vencimento', 'schedule' => '0 8 * * *', 'action' => 'notify_expiring', 'config' => json_encode(array('days_before' => 30, 'notify_users' => true))),
        array('name' => 'Laudos vencidos', 'code' => 'laudos_expired', 'description' => 'Marca laudos vencidos', 'schedule' => '0 9 * * *', 'action' => 'mark_expired', 'config' => json_encode(array('notify_users' => true))),
        array('name' => 'Visitas próximas', 'code' => 'inspections_upcoming', 'description' => 'Notifica 24h antes', 'schedule' => '0 18 * * *', 'action' => 'notify_upcoming', 'config' => json_encode(array('hours_before' => 24))),
        array('name' => 'Visitas atrasadas', 'code' => 'inspections_overdue', 'description' => 'Marca visitas atrasadas', 'schedule' => '0 10 * * *', 'action' => 'mark_overdue', 'config' => json_encode(array('notify_users' => true))),
        array('name' => 'Calibração próxima', 'code' => 'calibration_expiring', 'description' => 'Notifica 15 dias antes', 'schedule' => '0 7 * * *', 'action' => 'notify_calibration', 'config' => json_encode(array('days_before' => 15))),
        array('name' => 'Calibração vencida', 'code' => 'calibration_expired', 'description' => 'Marca equipamentos sem calibração', 'schedule' => '0 8 * * 1', 'action' => 'mark_calibration_expired', 'config' => json_encode(array('notify_users' => true))),
        array('name' => 'Planos de ação vencidos', 'code' => 'action_plans_overdue', 'description' => 'Marca planos vencidos', 'schedule' => '0 9 * * *', 'action' => 'notify_plans_overdue', 'config' => json_encode(array('notify_responsible' => true))),
        array('name' => 'Links expirados', 'code' => 'shares_expired', 'description' => 'Desativa links compartilhados expirados', 'schedule' => '0 0 * * *', 'action' => 'revoke_expired_shares', 'config' => json_encode(array())),
        array('name' => 'Limpeza de tokens', 'code' => 'cleanup_tokens', 'description' => 'Remove tokens expirados', 'schedule' => '0 2 * * 0', 'action' => 'cleanup_expired_tokens', 'config' => json_encode(array('days_old' => 30)))
    );
    
    foreach ($automations as $a) {
        $a['created_at'] = get_my_local_time();
        $a['is_active'] = 1;
        $db->insert($automations_table, $a);
    }
    $results[] = "Automações padrão inseridas";
}

return array('success' => true, 'results' => $results);