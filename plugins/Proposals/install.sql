CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}proposals_custom` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NULL,
    `client_id` INT(11) NULL,
    `client_name` VARCHAR(255) NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `payment_terms` TEXT NULL,
    `observations` TEXT NULL,
    `validity_days` INT(11) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    `display_mode` VARCHAR(20) NOT NULL DEFAULT 'detailed',
    `commission_type` VARCHAR(20) NOT NULL DEFAULT 'percent',
    `commission_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `tax_product_percent` DECIMAL(8,2) NOT NULL DEFAULT 0,
    `tax_service_percent` DECIMAL(8,2) NOT NULL DEFAULT 0,
    `tax_service_only` TINYINT(1) NOT NULL DEFAULT 0,
    `taxes_snapshot_json` LONGTEXT NULL,
    `total_cost_material` DECIMAL(16,2) NOT NULL DEFAULT 0,
    `total_cost_service` DECIMAL(16,2) NOT NULL DEFAULT 0,
    `total_sale` DECIMAL(16,2) NOT NULL DEFAULT 0,
    `taxes_total` DECIMAL(16,2) NOT NULL DEFAULT 0,
    `commission_total` DECIMAL(16,2) NOT NULL DEFAULT 0,
    `profit_gross` DECIMAL(16,2) NOT NULL DEFAULT 0,
    `profit_net` DECIMAL(16,2) NOT NULL DEFAULT 0,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `company_id` (`company_id`),
    KEY `client_id` (`client_id`),
    KEY `status` (`status`),
    KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}proposal_sections_custom` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `proposal_id` INT(11) NOT NULL,
    `parent_id` INT(11) NULL,
    `title` VARCHAR(255) NOT NULL,
    `sort` INT(11) NOT NULL DEFAULT 0,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `proposal_id` (`proposal_id`),
    KEY `parent_id` (`parent_id`),
    KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}proposal_items_custom` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `proposal_id` INT(11) NOT NULL,
    `section_id` INT(11) NULL,
    `item_id` INT(11) NULL,
    `item_type` VARCHAR(20) NULL,
    `description_override` TEXT NULL,
    `cost_unit` DECIMAL(16,4) NOT NULL DEFAULT 0,
    `qty` DECIMAL(16,4) NOT NULL DEFAULT 0,
    `markup_percent` DECIMAL(8,2) NOT NULL DEFAULT 0,
    `sale_unit` DECIMAL(16,4) NOT NULL DEFAULT 0,
    `total` DECIMAL(16,2) NOT NULL DEFAULT 0,
    `show_in_proposal` TINYINT(1) NOT NULL DEFAULT 1,
    `show_values_in_proposal` TINYINT(1) NOT NULL DEFAULT 1,
    `in_memory` TINYINT(1) NOT NULL DEFAULT 1,
    `sort` INT(11) NOT NULL DEFAULT 0,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `proposal_id` (`proposal_id`),
    KEY `section_id` (`section_id`),
    KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}proposals_module_settings_custom` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NULL,
    `default_commission_type` VARCHAR(20) NOT NULL DEFAULT 'percent',
    `default_commission_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `default_markup_percent` DECIMAL(8,2) NOT NULL DEFAULT 0,
    `taxes_json` LONGTEXT NULL,
    `taxes_base` VARCHAR(20) NOT NULL DEFAULT 'total_sale',
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}proposal_snapshots_custom` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `proposal_id` INT(11) NOT NULL,
    `snapshot_json` LONGTEXT NULL,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `proposal_id` (`proposal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}proposal_task_links_custom` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `proposal_id` INT(11) NOT NULL,
    `task_id` INT(11) NOT NULL,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `proposal_id` (`proposal_id`),
    KEY `task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}proposal_reminder_links_custom` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `proposal_id` INT(11) NOT NULL,
    `event_id` INT(11) NOT NULL,
    `created_by` INT(11) NULL,
    `created_at` DATETIME NULL,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `proposal_id` (`proposal_id`),
    KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
