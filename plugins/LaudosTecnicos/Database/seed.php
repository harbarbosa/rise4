<?php

/**
 * Seed: Dados iniciais - Categorias e Tipos de Laudo
 */

$db = db_connect('default');
$dbprefix = get_db_prefix();

$results = array();

// ==================== CATEGORIAS ====================
$categories_table = $dbprefix . 'laudo_categories';

// Verificar se já existem categorias
$existing_categories = $db->query("SELECT COUNT(*) as count FROM {$categories_table} WHERE deleted=0")->getRow();
$has_categories = $existing_categories && $existing_categories->count > 0;

if (!$has_categories) {
    $categories = array(
        array('name' => 'Engenharia Elétrica', 'code' => 'ELETRICA', 'description' => 'Laudos e inspeções de instalações elétricas em geral', 'color' => '#f59e0b', 'icon' => 'zap', 'sort_order' => 1, 'status' => 1),
        array('name' => 'SPDA', 'code' => 'SPDA', 'description' => 'Sistema de Proteção contra Descargas Atmosféricas', 'color' => '#ef4444', 'icon' => 'cloud-lightning', 'sort_order' => 2, 'status' => 1),
        array('name' => 'Segurança Eletrônica', 'code' => 'SEGURANCA', 'description' => 'Sistemas de alarme, monitoramento e controle de acesso', 'color' => '#8b5cf6', 'icon' => 'shield', 'sort_order' => 3, 'status' => 1),
        array('name' => 'CFTV', 'code' => 'CFTV', 'description' => 'Circuito Fechado de Televisão', 'color' => '#06b6d4', 'icon' => 'video', 'sort_order' => 4, 'status' => 1),
        array('name' => 'Controle de Acesso', 'code' => 'ACESSO', 'description' => 'Sistemas de controle de acesso físico', 'color' => '#10b981', 'icon' => 'lock', 'sort_order' => 5, 'status' => 1),
        array('name' => 'Redes e Cabeamento', 'code' => 'REDES', 'description' => 'Cabeamento estruturado e infraestrutura de rede', 'color' => '#3b82f6', 'icon' => 'network', 'sort_order' => 6, 'status' => 1),
        array('name' => 'Fibra Óptica', 'code' => 'FIBRA', 'description' => 'Infraestrutura e certificação de fibra óptica', 'color' => '#f97316', 'icon' => 'radio', 'sort_order' => 7, 'status' => 1),
        array('name' => 'Energia Fotovoltaica', 'code' => 'FV', 'description' => 'Sistemas de energia solar fotovoltaica', 'color' => '#eab308', 'icon' => 'sun', 'sort_order' => 8, 'status' => 1),
        array('name' => 'Manutenção', 'code' => 'MANUTENCAO', 'description' => 'Relatórios de manutenção preventiva e corretiva', 'color' => '#64748b', 'icon' => 'tool', 'sort_order' => 9, 'status' => 1),
        array('name' => 'Inspeções', 'code' => 'INSPEÇÃO', 'description' => 'Inspeções técnicas e vistorias', 'color' => '#ec4899', 'icon' => 'search', 'sort_order' => 10, 'status' => 1),
        array('name' => 'Pareceres Técnicos', 'code' => 'PARECER', 'description' => 'Pareceres e atestados técnicos', 'color' => '#14b8a6', 'icon' => 'file-text', 'sort_order' => 11, 'status' => 1),
        array('name' => 'Termografia', 'code' => 'TERMO', 'description' => 'Análise termográfica de instalações', 'color' => '#dc2626', 'icon' => 'thermometer', 'sort_order' => 12, 'status' => 1),
    );

    foreach ($categories as $cat) {
        $cat['created_at'] = get_my_local_time();
        $cat['created_by'] = 1; // admin
        $db->insert($categories_table, $cat);
    }
    $results[] = count($categories) . ' categorias inseridas';
}

// ==================== TIPOS ====================
$types_table = $dbprefix . 'laudo_types';

// Buscar IDs das categorias
$category_ids = array();
$cat_data = $db->query("SELECT id, code FROM {$categories_table} WHERE deleted=0")->getResult();
foreach ($cat_data as $c) {
    $category_ids[$c->code] = $c->id;
}

// Verificar se já existem tipos
$existing_types = $db->query("SELECT COUNT(*) as count FROM {$types_table} WHERE deleted=0")->getRow();
$has_types = $existing_types && $existing_types->count > 0;

if (!$has_types) {
    $types = array(
        array('name' => 'Laudo de SPDA', 'code' => 'LAUDO_SPDA', 'category_id' => $category_ids['SPDA'] ?? null, 'prefix' => 'SPDA', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Inspeção de SPDA', 'code' => 'INSP_SPDA', 'category_id' => $category_ids['SPDA'] ?? null, 'prefix' => 'INSP-SPDA', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 0, 'require_signature' => 0, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Laudo de Instalações Elétricas', 'code' => 'LAUDO_ELETRICA', 'category_id' => $category_ids['ELETRICA'] ?? null, 'prefix' => 'LIE', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Laudo de Aterramento', 'code' => 'LAUDO_ATERRAMENTO', 'category_id' => $category_ids['ELETRICA'] ?? null, 'prefix' => 'LRA', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Laudo Termográfico', 'code' => 'LAUDO_TERMO', 'category_id' => $category_ids['TERMO'] ?? null, 'prefix' => 'LTR', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Laudo de CFTV', 'code' => 'LAUDO_CFTV', 'category_id' => $category_ids['CFTV'] ?? null, 'prefix' => 'LCFTV', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Laudo de Controle de Acesso', 'code' => 'LAUDO_ACESSO', 'category_id' => $category_ids['ACESSO'] ?? null, 'prefix' => 'LCA', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Laudo de Cabeamento Estruturado', 'code' => 'LAUDO_REDES', 'category_id' => $category_ids['REDES'] ?? null, 'prefix' => 'LCE', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Laudo de Fibra Óptica', 'code' => 'LAUDO_FIBRA', 'category_id' => $category_ids['FIBRA'] ?? null, 'prefix' => 'LFO', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Laudo de Energia Fotovoltaica', 'code' => 'LAUDO_FV', 'category_id' => $category_ids['FV'] ?? null, 'prefix' => 'LFV', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Relatório de Manutenção Preventiva', 'code' => 'RMP', 'category_id' => $category_ids['MANUTENCAO'] ?? null, 'prefix' => 'RMP', 'validity_days' => 180, 'require_technician' => 1, 'require_review' => 0, 'require_approval' => 0, 'require_signature' => 0, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Relatório de Manutenção Corretiva', 'code' => 'RMC', 'category_id' => $category_ids['MANUTENCAO'] ?? null, 'prefix' => 'RMC', 'validity_days' => 90, 'require_technician' => 1, 'require_review' => 0, 'require_approval' => 0, 'require_signature' => 0, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Relatório Fotográfico', 'code' => 'RFT', 'category_id' => $category_ids['INSPEÇÃO'] ?? null, 'prefix' => 'RFT', 'validity_days' => 180, 'require_technician' => 1, 'require_review' => 0, 'require_approval' => 0, 'require_signature' => 0, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Relatório de Visita Técnica', 'code' => 'RVT', 'category_id' => $category_ids['INSPEÇÃO'] ?? null, 'prefix' => 'RVT', 'validity_days' => 180, 'require_technician' => 1, 'require_review' => 0, 'require_approval' => 0, 'require_signature' => 0, 'require_inspection' => 1, 'status' => 1),
        array('name' => 'Parecer Técnico', 'code' => 'PARECER', 'category_id' => $category_ids['PARECER'] ?? null, 'prefix' => 'PT', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 1, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 0, 'status' => 1),
        array('name' => 'Termo de Aceite', 'code' => 'TERMO_ACEITE', 'category_id' => $category_ids['PARECER'] ?? null, 'prefix' => 'TA', 'validity_days' => 365, 'require_technician' => 1, 'require_review' => 0, 'require_approval' => 1, 'require_signature' => 1, 'require_inspection' => 0, 'status' => 1),
    );

    foreach ($types as $type) {
        $type['created_at'] = get_my_local_time();
        $type['created_by'] = 1;
        $db->insert($types_table, $type);
    }
    $results[] = count($types) . ' tipos de laudo inseridos';
}

return array(
    'success' => true,
    'results' => $results
);