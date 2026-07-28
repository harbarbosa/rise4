<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/**
 * Instalador do Plugin Laudos Técnicos
 * 
 * Executa:
 * 1. Database/install.php - Cria/atualiza tabelas
 * 2. Database/upgrade_v2.php - Status, Transições, Histórico
 * 3. Database/upgrade_v3.php - Campos avançados de laudos
 * 4. Database/upgrade_v4.php - Templates e campos dinâmicos
 * 5. Database/seed.php - Insere dados iniciais
 */

// 1. Instalação base das tabelas
$install_file = __DIR__ . '/Database/install.php';
if (file_exists($install_file)) {
    include $install_file;
}

// 2. Upgrade v2 - Status, Transições, Histórico
$upgrade_file = __DIR__ . '/Database/upgrade_v2.php';
if (file_exists($upgrade_file)) {
    include $upgrade_file;
}

// 3. Upgrade v3 - Campos avançados de laudos
$upgrade_v3_file = __DIR__ . '/Database/upgrade_v3.php';
if (file_exists($upgrade_v3_file)) {
    include $upgrade_v3_file;
}

// 4. Upgrade v4 - Templates e campos dinâmicos
$upgrade_v4_file = __DIR__ . '/Database/upgrade_v4.php';
if (file_exists($upgrade_v4_file)) {
    include $upgrade_v4_file;
}

// 6. Upgrade v5 - Checklists, Medições, Equipamentos, Normas
$upgrade_v5_file = __DIR__ . '/Database/upgrade_v5.php';
if (file_exists($upgrade_v5_file)) {
    include $upgrade_v5_file;
}

// 7. Upgrade v6 - Inspeções, Agenda, Fotos
$upgrade_v6_file = __DIR__ . '/Database/upgrade_v6.php';
if (file_exists($upgrade_v6_file)) {
    include $upgrade_v6_file;
}

// 8. Upgrade v7 - Não Conformidades, Riscos e Planos de Ação
$upgrade_v7_file = __DIR__ . '/Database/upgrade_v7.php';
if (file_exists($upgrade_v7_file)) {
    include $upgrade_v7_file;
}

// 9. Upgrade v8 - Revisão, Aprovação, Assinaturas e Versionamento
$upgrade_v8_file = __DIR__ . '/Database/upgrade_v8.php';
if (file_exists($upgrade_v8_file)) {
    include $upgrade_v8_file;
}

// 10. Upgrade v9 - HTML/PDF, Portal do Cliente e Validação
$upgrade_v9_file = __DIR__ . '/Database/upgrade_v9.php';
if (file_exists($upgrade_v9_file)) {
    include $upgrade_v9_file;
}

// 11. Seed - Dados iniciais (categorias e tipos)
$seed_file = __DIR__ . '/Database/seed.php';
if (file_exists($seed_file)) {
    include $seed_file;
}

return array(
    'success' => true,
    'message' => 'Plugin Laudos Técnicos instalado com sucesso'
);