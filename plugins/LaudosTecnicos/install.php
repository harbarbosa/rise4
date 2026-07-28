<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/**
 * Instalador do Plugin Laudos Técnicos
 */

$install_file = __DIR__ . '/Database/install.php';
if (file_exists($install_file)) {
    require $install_file;
    return;
}

return array(
    'success' => true,
    'message' => 'Plugin Laudos Técnicos instalado com sucesso'
);