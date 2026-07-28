<?php

/**
 * Migration: Campos Avançados para Laudos
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

$table = $dbprefix . 'laudos_tecnicos';

// Verificar campos existentes
$fields = $db->getFieldNames($table);

// Campos a adicionar
$new_fields = array(
    // Identificação
    'laudo_number' => "VARCHAR(50) NULL",
    'custom_code' => "VARCHAR(50) NULL",
    'revision' => "INT(11) NOT NULL DEFAULT 1",
    'priority' => "VARCHAR(20) NULL DEFAULT 'normal'",
    
    // Cliente/Projeto
    'contact_id' => "INT(11) NULL",
    'contract_id' => "INT(11) NULL",
    'work_order_id' => "INT(11) NULL",
    'unit_id' => "INT(11) NULL",
    'location' => "VARCHAR(255) NULL",
    
    // Datas
    'request_date' => "DATE NULL",
    'scheduled_date' => "DATETIME NULL",
    'start_inspection_date' => "DATETIME NULL",
    'end_inspection_date' => "DATETIME NULL",
    
    // Responsáveis
    'commercial_responsible_id' => "INT(11) NULL",
    'inspection_team' => "TEXT NULL",
    
    // Conteúdo técnico
    'objective' => "TEXT NULL",
    'scope' => "TEXT NULL",
    'methodology' => "TEXT NULL",
    'assumptions' => "TEXT NULL",
    'limitations' => "TEXT NULL",
    'installation_description' => "TEXT NULL",
    'results' => "TEXT NULL",
    'diagnosis' => "TEXT NULL",
    'conclusion' => "TEXT NULL",
    'recommendations' => "TEXT NULL",
    
    // Informações complementares
    'tags' => "VARCHAR(500) NULL",
    'cost_center' => "VARCHAR(100) NULL",
    'proposal_number' => "VARCHAR(50) NULL",
    'contract_number' => "VARCHAR(50) NULL",
    'external_reference' => "VARCHAR(255) NULL",
    'confidentiality' => "TINYINT(1) NOT NULL DEFAULT 0",
    'client_observations' => "TEXT NULL",
    
    // Status info
    'previous_status' => "VARCHAR(30) NULL",
    'current_status_changed_at' => "DATETIME NULL",
    
    // Controle
    'signature_data' => "TEXT NULL",
    'signature_date' => "DATETIME NULL",
    'approved_at' => "DATETIME NULL",
    'rejected_at' => "DATETIME NULL",
    'rejection_reason' => "TEXT NULL"
);

foreach ($new_fields as $field => $definition) {
    if (!in_array($field, $fields)) {
        $db->query("ALTER TABLE `$table` ADD COLUMN `$field` $definition");
        $results[] = "Adicionado campo: $field";
    }
}

// Adicionar índices
$indexes = array(
    'laudo_number' => "INDEX `laudo_number` (`laudo_number`)",
    'client_id' => "INDEX `client_id` (`client_id`)",
    'project_id' => "INDEX `project_id` (`project_id`)",
    'status' => "INDEX `status` (`status`)",
    'request_date' => "INDEX `request_date` (`request_date`)",
    'valid_until' => "INDEX `valid_until` (`valid_until`)"
);

foreach ($indexes as $field => $index_def) {
    // Verificar se índice existe
    $index_check = $db->query("SHOW INDEX FROM `$table` WHERE Key_name = '{$field}_2'")->getRow();
    if (!$index_check) {
        try {
            $db->query("ALTER TABLE `$table` ADD $index_def");
        } catch (\Exception $e) {
            // Índice pode já existir com nome diferente
        }
    }
}

return array(
    'success' => true,
    'results' => $results
);