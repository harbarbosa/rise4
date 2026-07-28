<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/**
 * Instalador do Plugin Laudos Técnicos
 * 
 * Executa:
 * 1. Database/install.php - Cria/atualiza tabelas
 * 2. Database/upgrade_v2.php - Status, Transições, Histórico
 * 3. Database/upgrade_v3.php - Campos avançados de laudos
 * 4. Database/seed.php - Insere dados iniciais
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

// 4. Seed - Dados iniciais (categorias e tipos)
$seed_file = __DIR__ . '/Database/seed.php';
if (file_exists($seed_file)) {
    include $seed_file;
}

return array(
    'success' => true,
    'message' => 'Plugin Laudos Técnicos instalado com sucesso'
);