<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

function travelrefunds_install()
{
    $db = db_connect('default');
    $prefix = get_db_prefix();

    $tables = array(
        'travelrefunds_trips' => "CREATE TABLE IF NOT EXISTS `{$prefix}travelrefunds_trips` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `employee_id` INT(11) DEFAULT NULL,
            `project_id` INT(11) DEFAULT NULL,
            `destination` VARCHAR(255) DEFAULT NULL,
            `purpose` TEXT DEFAULT NULL,
            `departure_date` DATE DEFAULT NULL,
            `return_date` DATE DEFAULT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
            `estimated_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `actual_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        'travelrefunds_reimbursements' => "CREATE TABLE IF NOT EXISTS `{$prefix}travelrefunds_reimbursements` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `trip_id` INT(11) DEFAULT NULL,
            `employee_id` INT(11) DEFAULT NULL,
            `category_id` INT(11) DEFAULT NULL,
            `expense_date` DATE DEFAULT NULL,
            `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `vendor` VARCHAR(255) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `receipt_number` VARCHAR(100) DEFAULT NULL,
            `receipt_file` VARCHAR(255) DEFAULT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `approved_by` INT(11) DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `paid_at` DATETIME DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        'travelrefunds_categories' => "CREATE TABLE IF NOT EXISTS `{$prefix}travelrefunds_categories` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort` INT(11) NOT NULL DEFAULT 0,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        'travelrefunds_approval_logs' => "CREATE TABLE IF NOT EXISTS `{$prefix}travelrefunds_approval_logs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `reimbursement_id` INT(11) NOT NULL,
            `approver_id` INT(11) NOT NULL,
            `action` VARCHAR(50) NOT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        'travelrefunds_settings' => "CREATE TABLE IF NOT EXISTS `{$prefix}travelrefunds_settings` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `setting_name` VARCHAR(191) NOT NULL,
            `setting_value` LONGTEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_name_unique` (`setting_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    foreach ($tables as $sql) {
        $db->query($sql);
    }

    $defaults = array(
        'travelrefunds_enabled' => '1',
        'travelrefunds_default_currency_symbol' => get_setting('default_currency_symbol') ?: '$',
        'travelrefunds_allow_public_receipts' => '0',
    );

    foreach ($defaults as $name => $value) {
        $exists = $db->table($prefix . 'travelrefunds_settings')->where('setting_name', $name)->get()->getRow();
        if ($exists) {
            $db->table($prefix . 'travelrefunds_settings')->where('id', $exists->id)->update(array('setting_value' => $value));
            continue;
        }

        $db->table($prefix . 'travelrefunds_settings')->insert(array(
            'setting_name' => $name,
            'setting_value' => $value,
        ));
    }

    $default_categories = array('Transporte', 'Hospedagem', 'Alimentacao', 'Pedagio', 'Combustivel');
    foreach ($default_categories as $index => $title) {
        $exists = $db->table($prefix . 'travelrefunds_categories')->where('title', $title)->where('deleted', 0)->get()->getRow();
        if (!$exists) {
            $db->table($prefix . 'travelrefunds_categories')->insert(array(
                'title' => $title,
                'is_active' => 1,
                'sort' => $index + 1,
                'created_by' => 0,
            ));
        }
    }
}
