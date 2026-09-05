<?php

function frota_install(): void
{
    $db = db_connect('default');
    $prefix = $db->getPrefix();

    $queries = [];
    $queries[] = "CREATE TABLE IF NOT EXISTS `{$prefix}frota_vehicles` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `plate` varchar(20) NOT NULL,
      `prefix` varchar(50) DEFAULT NULL,
      `make` varchar(100) DEFAULT NULL,
      `model` varchar(120) NOT NULL,
      `year` varchar(10) DEFAULT NULL,
      `fuel_type` varchar(40) DEFAULT NULL,
      `current_odometer` decimal(12,1) NOT NULL DEFAULT 0,
      `next_service_odometer` decimal(12,1) DEFAULT NULL,
      `next_service_date` date DEFAULT NULL,
      `status` varchar(30) NOT NULL DEFAULT 'active',
      `assigned_user_id` int unsigned DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_at` datetime DEFAULT NULL,
      `updated_at` datetime DEFAULT NULL,
      `deleted` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`), UNIQUE KEY `plate` (`plate`), KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $queries[] = "CREATE TABLE IF NOT EXISTS `{$prefix}frota_fuelings` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `vehicle_id` int unsigned NOT NULL,
      `user_id` int unsigned DEFAULT NULL,
      `fueling_at` datetime NOT NULL,
      `odometer` decimal(12,1) NOT NULL,
      `liters` decimal(10,3) NOT NULL,
      `unit_price` decimal(10,3) DEFAULT NULL,
      `total_amount` decimal(12,2) NOT NULL,
      `fuel_type` varchar(40) DEFAULT NULL,
      `station` varchar(150) DEFAULT NULL,
      `receipt_url` text DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_at` datetime DEFAULT NULL,
      `deleted` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`), KEY `vehicle_id` (`vehicle_id`), KEY `fueling_at` (`fueling_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $queries[] = "CREATE TABLE IF NOT EXISTS `{$prefix}frota_maintenances` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `vehicle_id` int unsigned NOT NULL,
      `type` varchar(30) NOT NULL DEFAULT 'preventive',
      `description` text NOT NULL,
      `supplier` varchar(150) DEFAULT NULL,
      `odometer` decimal(12,1) DEFAULT NULL,
      `service_date` date NOT NULL,
      `next_service_odometer` decimal(12,1) DEFAULT NULL,
      `next_service_date` date DEFAULT NULL,
      `cost` decimal(12,2) NOT NULL DEFAULT 0,
      `status` varchar(30) NOT NULL DEFAULT 'scheduled',
      `created_by` int unsigned DEFAULT NULL,
      `completed_at` datetime DEFAULT NULL,
      `created_at` datetime DEFAULT NULL,
      `deleted` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`), KEY `vehicle_id` (`vehicle_id`), KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $queries[] = "CREATE TABLE IF NOT EXISTS `{$prefix}frota_issues` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `vehicle_id` int unsigned NOT NULL,
      `title` varchar(180) NOT NULL,
      `description` text NOT NULL,
      `severity` varchar(20) NOT NULL DEFAULT 'medium',
      `status` varchar(30) NOT NULL DEFAULT 'open',
      `odometer` decimal(12,1) DEFAULT NULL,
      `reported_by` int unsigned DEFAULT NULL,
      `assigned_to` int unsigned DEFAULT NULL,
      `reported_at` datetime NOT NULL,
      `resolved_at` datetime DEFAULT NULL,
      `resolution` text DEFAULT NULL,
      `photo_url` text DEFAULT NULL,
      `created_at` datetime DEFAULT NULL,
      `deleted` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`), KEY `vehicle_id` (`vehicle_id`), KEY `status` (`status`), KEY `severity` (`severity`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $queries[] = "CREATE TABLE IF NOT EXISTS `{$prefix}frota_maintenance_issue_links` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `maintenance_id` int unsigned NOT NULL,
      `issue_id` int unsigned NOT NULL,
      `created_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `maintenance_issue_unique` (`maintenance_id`,`issue_id`),
      KEY `maintenance_id` (`maintenance_id`),
      KEY `issue_id` (`issue_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    foreach ($queries as $sql) {
        $db->query($sql);
    }
}
