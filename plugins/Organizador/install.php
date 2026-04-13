<?php

$db = db_connect('default');
$prefix = $db->getPrefix();

$tables = array(
    'my_task_tags' => "CREATE TABLE IF NOT EXISTS `{$prefix}my_task_tags` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(190) NOT NULL,
        `color` VARCHAR(20) NULL DEFAULT NULL,
        `sort` INT(11) NOT NULL DEFAULT 0,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `title` (`title`),
        KEY `sort` (`sort`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'my_task_phases' => "CREATE TABLE IF NOT EXISTS `{$prefix}my_task_phases` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `key_name` VARCHAR(80) NOT NULL,
        `title` VARCHAR(190) NOT NULL,
        `color` VARCHAR(20) NULL DEFAULT NULL,
        `sort` INT(11) NOT NULL DEFAULT 0,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `key_name` (`key_name`),
        KEY `sort` (`sort`),
        KEY `title` (`title`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'my_task_categories' => "CREATE TABLE IF NOT EXISTS `{$prefix}my_task_categories` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(190) NOT NULL,
        `color` VARCHAR(20) NULL DEFAULT NULL,
        `sort` INT(11) NOT NULL DEFAULT 0,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `title` (`title`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'my_tasks' => "CREATE TABLE IF NOT EXISTS `{$prefix}my_tasks` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `priority` VARCHAR(20) NOT NULL DEFAULT 'medium',
        `category_id` INT(11) NULL DEFAULT NULL,
        `assigned_to` INT(11) NULL DEFAULT NULL,
        `created_by` INT(11) NULL DEFAULT NULL,
        `start_date` DATETIME NULL DEFAULT NULL,
        `due_date` DATETIME NULL DEFAULT NULL,
        `reminder_at` DATETIME NULL DEFAULT NULL,
        `reminder_before_value` INT(11) NULL DEFAULT NULL,
        `reminder_before_unit` VARCHAR(20) NULL DEFAULT NULL,
        `position` INT(11) NOT NULL DEFAULT 0,
        `is_favorite` TINYINT(1) NOT NULL DEFAULT 0,
        `labels` TEXT NULL,
        `notify_assigned_to` TINYINT(1) NOT NULL DEFAULT 1,
        `notify_creator` TINYINT(1) NOT NULL DEFAULT 1,
        `email_notification` TINYINT(1) NOT NULL DEFAULT 1,
        `reminder_sent_at` DATETIME NULL DEFAULT NULL,
        `completed_at` DATETIME NULL DEFAULT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `status` (`status`),
        KEY `priority` (`priority`),
        KEY `assigned_to` (`assigned_to`),
        KEY `created_by` (`created_by`),
        KEY `category_id` (`category_id`),
        KEY `due_date` (`due_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'my_task_settings' => "CREATE TABLE IF NOT EXISTS `{$prefix}my_task_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_name` VARCHAR(190) NOT NULL,
        `setting_value` LONGTEXT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_name` (`setting_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'my_task_notifications' => "CREATE TABLE IF NOT EXISTS `{$prefix}my_task_notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `task_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL DEFAULT 0,
        `event` VARCHAR(80) NOT NULL,
        `channel` VARCHAR(20) NOT NULL DEFAULT 'system',
        `is_sent` TINYINT(1) NOT NULL DEFAULT 1,
        `sent_at` DATETIME NULL DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `task_id` (`task_id`),
        KEY `user_id` (`user_id`),
        KEY `event` (`event`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'my_task_comments' => "CREATE TABLE IF NOT EXISTS `{$prefix}my_task_comments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `task_id` INT(11) NOT NULL,
        `description` LONGTEXT NULL,
        `files` LONGTEXT NULL,
        `created_by` INT(11) NOT NULL DEFAULT 0,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `task_id` (`task_id`),
        KEY `created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    'my_task_reminders' => "CREATE TABLE IF NOT EXISTS `{$prefix}my_task_reminders` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `task_id` INT(11) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `remind_at` DATETIME NOT NULL,
        `created_by` INT(11) NOT NULL DEFAULT 0,
        `is_done` TINYINT(1) NOT NULL DEFAULT 0,
        `deleted` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `task_id` (`task_id`),
        KEY `created_by` (`created_by`),
        KEY `remind_at` (`remind_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
);

foreach ($tables as $sql) {
    $db->query($sql);
}

$tasks_table = $prefix . 'my_tasks';
if ($db->tableExists($tasks_table)) {
    if (!$db->fieldExists('reminder_before_value', $tasks_table)) {
        $db->query("ALTER TABLE `{$tasks_table}` ADD COLUMN `reminder_before_value` INT(11) NULL DEFAULT NULL AFTER `reminder_at`");
    }
    if (!$db->fieldExists('reminder_before_unit', $tasks_table)) {
        $db->query("ALTER TABLE `{$tasks_table}` ADD COLUMN `reminder_before_unit` VARCHAR(20) NULL DEFAULT NULL AFTER `reminder_before_value`");
    }
}

$settings = array(
    'organizador_enable_internal_notifications' => '1',
    'organizador_enable_email_notifications' => '1',
    'organizador_enable_auto_reminders' => '1',
    'organizador_enable_overdue_alerts' => '1',
    'organizador_reminder_hours_before_due' => '24',
    'organizador_sync_to_events_calendar' => '1',
    'organizador_public_api_enabled' => '1',
);

foreach ($settings as $name => $value) {
    $exists = $db->table($prefix . 'settings')->where('setting_name', $name)->where('deleted', 0)->get()->getRow();
    if (!$exists) {
        $db->table($prefix . 'settings')->insert(array(
            'setting_name' => $name,
            'setting_value' => $value,
            'type' => 'app',
            'deleted' => 0,
        ));
    } else {
        $db->table($prefix . 'settings')->where('setting_name', $name)->update(array(
            'setting_value' => $value,
            'type' => 'app',
        ));
    }
}

$public_api_token_setting = 'organizador_public_api_token';
$existing_public_api_token = $db->table($prefix . 'settings')
    ->where('setting_name', $public_api_token_setting)
    ->where('deleted', 0)
    ->get()
    ->getRow();

if (!$existing_public_api_token || empty($existing_public_api_token->setting_value)) {
    $token = bin2hex(random_bytes(24));
    if ($existing_public_api_token && isset($existing_public_api_token->id)) {
        $db->table($prefix . 'settings')->where('id', $existing_public_api_token->id)->update(array(
            'setting_value' => $token,
            'type' => 'app',
        ));
    } else {
        $db->table($prefix . 'settings')->insert(array(
            'setting_name' => $public_api_token_setting,
            'setting_value' => $token,
            'type' => 'app',
            'deleted' => 0,
        ));
    }
}

$categories_table = $prefix . 'my_task_categories';
if ($db->table($categories_table)->countAllResults() == 0) {
    $default_categories = array(
        array('title' => 'General', 'color' => '#6c757d', 'sort' => 1, 'deleted' => 0),
        array('title' => 'Work', 'color' => '#0d6efd', 'sort' => 2, 'deleted' => 0),
        array('title' => 'Personal', 'color' => '#198754', 'sort' => 3, 'deleted' => 0),
        array('title' => 'Urgent', 'color' => '#dc3545', 'sort' => 4, 'deleted' => 0),
    );

    foreach ($default_categories as $category) {
        $category['created_at'] = get_current_utc_time();
        $category['updated_at'] = get_current_utc_time();
        $db->table($categories_table)->insert($category);
    }
}

$phases_table = $prefix . 'my_task_phases';
if ($db->table($phases_table)->countAllResults() == 0) {
    $default_phases = array(
        array('key_name' => 'pending', 'title' => 'Pendente', 'color' => '#6c757d', 'sort' => 1, 'deleted' => 0),
        array('key_name' => 'in_progress', 'title' => 'Em andamento', 'color' => '#0d6efd', 'sort' => 2, 'deleted' => 0),
        array('key_name' => 'done', 'title' => 'Concluída', 'color' => '#198754', 'sort' => 3, 'deleted' => 0),
        array('key_name' => 'canceled', 'title' => 'Cancelada', 'color' => '#dc3545', 'sort' => 4, 'deleted' => 0),
    );

    foreach ($default_phases as $phase) {
        $phase['created_at'] = get_current_utc_time();
        $phase['updated_at'] = get_current_utc_time();
        $db->table($phases_table)->insert($phase);
    }
}

$notification_events = array(
    'organizador_task_created',
    'organizador_task_assigned',
    'organizador_task_updated',
    'organizador_task_due_soon',
    'organizador_task_overdue',
    'organizador_task_completed',
    'organizador_task_reminder',
);

foreach ($notification_events as $sort => $event) {
    $exists = $db->table($prefix . 'notification_settings')->where('event', $event)->where('deleted', 0)->get()->getRow();
    if (!$exists) {
        $db->table($prefix . 'notification_settings')->insert(array(
            'event' => $event,
            'category' => 'organizador',
            'enable_web' => 1,
            'enable_email' => 1,
            'enable_slack' => 0,
            'notify_to_team' => '',
            'notify_to_team_members' => '',
            'notify_to_terms' => '',
            'sort' => 100 + $sort,
            'deleted' => 0,
        ));
    }
}
