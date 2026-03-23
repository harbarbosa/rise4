<?php

// helpers simples para settings
if (!function_exists('add_setting')) {
    function add_setting($name, $value = '')
    {
        if (!setting_exists($name)) {
            $db = db_connect('default');
            $builder = $db->table(get_db_prefix() . 'settings');
            $builder->insert([
                'setting_name' => $name,
                'setting_value' => $value,
            ]);
        }
    }
}

if (!function_exists('setting_exists')) {
    function setting_exists($name)
    {
        $db = db_connect('default');
        $builder = $db->table(get_db_prefix() . 'settings');
        return $builder->where('setting_name', $name)->countAllResults() > 0;
    }
}

// settings de credenciais e tokens (ambiente único)
add_setting('contaazul_client_id', '');
add_setting('contaazul_client_secret', '');
add_setting('contaazul_redirect_uri', '');
add_setting('contaazul_scope', 'openid profile aws.cognito.signin.user.admin');
add_setting('contaazul_access_token', '');
add_setting('contaazul_refresh_token', '');
add_setting('contaazul_token_expires_at', '');
add_setting('contaazul_cron_key', '');
add_setting('contaazul_sync_on_create', '0');

// tabela de histórico de execuções
$db = db_connect('default');
$prefix = get_db_prefix();
$sql = "CREATE TABLE IF NOT EXISTS `{$prefix}contaazul_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source` VARCHAR(20) NOT NULL DEFAULT 'manual',
    `run_at` DATETIME NOT NULL,
    `imported` INT NOT NULL DEFAULT 0,
    `updated` INT NOT NULL DEFAULT 0,
    `errors` TEXT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";
$db->query($sql);

// adiciona coluna id_conta_azul
// tabela de centros de custo do Conta Azul
$sql = "CREATE TABLE IF NOT EXISTS `{$prefix}contaazul_cost_centers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ca_id` VARCHAR(100) NULL,
    `code` VARCHAR(100) NULL,
    `title` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX (`ca_id`),
    INDEX (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";
$db->query($sql);

// adiciona coluna id_conta_azul na tabela clients, se não existir
$clientsTable = $prefix . 'clients';
$columnExists = $db->query("SHOW COLUMNS FROM `{$clientsTable}` LIKE 'id_conta_azul'")->getResult();
if (empty($columnExists)) {
    $db->query("ALTER TABLE `{$clientsTable}` ADD COLUMN `id_conta_azul` VARCHAR(100) NULL DEFAULT NULL");
}

