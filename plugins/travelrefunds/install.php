<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

function travelrefunds_install()
{
    $db = db_connect('default');
    $prefix = get_db_prefix();

    $tables = array(
        'travelrefunds_trips' => "CREATE TABLE IF NOT EXISTS `{$prefix}travelrefunds_trips` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `employee_id` INT(11) NOT NULL,
            `project_id` INT(11) DEFAULT NULL,
            `client_id` INT(11) DEFAULT NULL,
            `title` VARCHAR(255) NOT NULL,
            `destination` VARCHAR(255) DEFAULT NULL,
            `start_date` DATE DEFAULT NULL,
            `end_date` DATE DEFAULT NULL,
            `purpose` TEXT DEFAULT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
            `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `approved_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            `departure_date` DATE DEFAULT NULL,
            `return_date` DATE DEFAULT NULL,
            `estimated_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `actual_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        'travelrefunds_expenses' => "CREATE TABLE IF NOT EXISTS `{$prefix}travelrefunds_expenses` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `trip_id` INT(11) NOT NULL,
            `category_id` INT(11) NOT NULL,
            `employee_id` INT(11) DEFAULT NULL,
            `expense_date` DATE DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `payment_method` VARCHAR(100) DEFAULT NULL,
            `has_invoice` TINYINT(1) NOT NULL DEFAULT 0,
            `invoice_number` VARCHAR(100) DEFAULT NULL,
            `supplier_name` VARCHAR(255) DEFAULT NULL,
            `attachment_id` INT(11) DEFAULT NULL,
            `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `rejection_reason` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            `vendor` VARCHAR(255) DEFAULT NULL,
            `receipt_number` VARCHAR(100) DEFAULT NULL,
            `receipt_file` VARCHAR(255) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `approved_by` INT(11) DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `paid_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        'travelrefunds_categories' => "CREATE TABLE IF NOT EXISTS `{$prefix}travelrefunds_categories` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `requires_invoice` TINYINT(1) NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            `title` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort` INT(11) NOT NULL DEFAULT 0,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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

    $this_trips_table = $prefix . 'travelrefunds_trips';
    $this_expenses_table = $prefix . 'travelrefunds_expenses';
    $this_categories_table = $prefix . 'travelrefunds_categories';

    travelrefunds_ensure_columns($db, $this_trips_table, array(
        'project_id' => 'INT(11) DEFAULT NULL',
        'client_id' => 'INT(11) DEFAULT NULL',
        'start_date' => 'DATE DEFAULT NULL',
        'end_date' => 'DATE DEFAULT NULL',
        'total_amount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0',
        'approved_amount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0',
        'departure_date' => 'DATE DEFAULT NULL',
        'return_date' => 'DATE DEFAULT NULL',
        'estimated_amount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0',
        'actual_amount' => 'DECIMAL(15,2) NOT NULL DEFAULT 0',
    ));

    travelrefunds_ensure_columns($db, $this_expenses_table, array(
        'payment_method' => 'VARCHAR(100) DEFAULT NULL',
        'has_invoice' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'invoice_number' => 'VARCHAR(100) DEFAULT NULL',
        'supplier_name' => 'VARCHAR(255) DEFAULT NULL',
        'attachment_id' => 'INT(11) DEFAULT NULL',
        'rejection_reason' => 'TEXT DEFAULT NULL',
        'employee_id' => 'INT(11) DEFAULT NULL',
        'vendor' => 'VARCHAR(255) DEFAULT NULL',
        'receipt_number' => 'VARCHAR(100) DEFAULT NULL',
        'receipt_file' => 'VARCHAR(255) DEFAULT NULL',
        'notes' => 'TEXT DEFAULT NULL',
        'approved_by' => 'INT(11) DEFAULT NULL',
        'approved_at' => 'DATETIME DEFAULT NULL',
        'paid_at' => 'DATETIME DEFAULT NULL',
    ));

    travelrefunds_ensure_columns($db, $this_categories_table, array(
        'name' => 'VARCHAR(255) NOT NULL',
        'requires_invoice' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'sort_order' => 'INT(11) NOT NULL DEFAULT 0',
        'title' => 'VARCHAR(255) DEFAULT NULL',
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'sort' => 'INT(11) NOT NULL DEFAULT 0',
    ));

    travelrefunds_sync_compatibility_fields($db, $this_categories_table);

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

    $default_categories = array(
        'Combustível' => 1,
        'Pedágio' => 0,
        'Hotel' => 1,
        'Alimentação' => 1,
        'Estacionamento' => 0,
        'Transporte' => 0,
        'Outros' => 0,
    );

    $sort_order = 1;
    foreach ($default_categories as $title => $requires_invoice) {
        $exists = $db->table($prefix . 'travelrefunds_categories')->where('deleted', 0)->groupStart()->where('name', $title)->orWhere('title', $title)->groupEnd()->get()->getRow();
        if (!$exists) {
            $db->table($prefix . 'travelrefunds_categories')->insert(array(
                'name' => $title,
                'title' => $title,
                'description' => null,
                'requires_invoice' => $requires_invoice ? 1 : 0,
                'active' => 1,
                'is_active' => 1,
                'sort_order' => $sort_order,
                'sort' => $sort_order,
                'created_by' => 0,
            ));
        }
        $sort_order++;
    }

    travelrefunds_migrate_old_expenses($db, $prefix);
    travelrefunds_sync_compatibility_fields($db, $this_categories_table);
}

function travelrefunds_ensure_columns($db, $table, $columns)
{
    if (!$db->tableExists($table)) {
        return;
    }

    $existing = $db->getFieldNames($table);
    foreach ($columns as $name => $definition) {
        if (!in_array($name, $existing, true)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$name}` {$definition}");
        }
    }
}

function travelrefunds_sync_compatibility_fields($db, $table)
{
    if (!$db->tableExists($table)) {
        return;
    }

    $db->query("UPDATE `{$table}` SET `name` = COALESCE(NULLIF(`name`, ''), `title`), `title` = COALESCE(NULLIF(`title`, ''), `name`)");
    $db->query("UPDATE `{$table}` SET `active` = COALESCE(`active`, `is_active`, 1), `is_active` = COALESCE(`is_active`, `active`, 1)");
    $db->query("UPDATE `{$table}` SET `sort_order` = COALESCE(`sort_order`, `sort`, 0), `sort` = COALESCE(`sort`, `sort_order`, 0)");
}

function travelrefunds_migrate_old_expenses($db, $prefix)
{
    $old_table = $prefix . 'travelrefunds_reimbursements';
    $new_table = $prefix . 'travelrefunds_expenses';

    if (!$db->tableExists($old_table) || !$db->tableExists($new_table)) {
        return;
    }

    $new_count = $db->table($new_table)->countAllResults();
    if ($new_count > 0) {
        return;
    }

    $rows = $db->table($old_table)->where('deleted', 0)->get()->getResultArray();
    if (!$rows) {
        return;
    }

    foreach ($rows as $row) {
        $db->table($new_table)->insert(array(
            'trip_id' => get_array_value($row, 'trip_id'),
            'category_id' => get_array_value($row, 'category_id'),
            'employee_id' => get_array_value($row, 'employee_id'),
            'expense_date' => get_array_value($row, 'expense_date'),
            'description' => get_array_value($row, 'description'),
            'amount' => get_array_value($row, 'amount') ?: 0,
            'payment_method' => get_array_value($row, 'payment_method'),
            'has_invoice' => ((get_array_value($row, 'receipt_number') || get_array_value($row, 'receipt_file')) ? 1 : 0),
            'invoice_number' => get_array_value($row, 'receipt_number'),
            'supplier_name' => get_array_value($row, 'vendor'),
            'attachment_id' => null,
            'status' => get_array_value($row, 'status') ?: 'pending',
            'rejection_reason' => null,
            'created_by' => get_array_value($row, 'created_by'),
            'created_at' => get_array_value($row, 'created_at'),
            'updated_at' => get_array_value($row, 'updated_at'),
            'deleted' => get_array_value($row, 'deleted') ?: 0,
            'vendor' => get_array_value($row, 'vendor'),
            'receipt_number' => get_array_value($row, 'receipt_number'),
            'receipt_file' => get_array_value($row, 'receipt_file'),
            'notes' => get_array_value($row, 'notes'),
            'approved_by' => get_array_value($row, 'approved_by'),
            'approved_at' => get_array_value($row, 'approved_at'),
            'paid_at' => get_array_value($row, 'paid_at'),
        ));
    }
}
