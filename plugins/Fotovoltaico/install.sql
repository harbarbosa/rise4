CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_projects` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT(11) NULL,
  `title` VARCHAR(255) NOT NULL,
  `status` ENUM('draft','sent','won','lost') NOT NULL DEFAULT 'draft',
  `city` VARCHAR(120) NULL,
  `state` VARCHAR(2) NULL,
  `lat` DECIMAL(10,7) NULL,
  `lon` DECIMAL(10,7) NULL,
  `created_by` INT(11) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('module','inverter','service','structure','stringbox','cable','other') NOT NULL DEFAULT 'module',
  `brand` VARCHAR(120) NOT NULL,
  `model` VARCHAR(160) NOT NULL,
  `sku` VARCHAR(80) NULL,
  `power_w` DECIMAL(10,2) NULL,
  `cost` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `price` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `warranty_years` INT NULL,
  `datasheet_url` VARCHAR(255) NULL,
  `specs_json` LONGTEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `source` ENUM('manual','cec','import') NOT NULL DEFAULT 'manual',
  `source_ref` VARCHAR(120) NULL,
  `last_synced_at` DATETIME NULL,
  `external_hash` VARCHAR(64) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_brand_model` (`brand`, `model`),
  KEY `idx_active` (`is_active`),
  KEY `idx_source_ref` (`source`, `source_ref`),
  KEY `idx_brand_model_type` (`type`, `brand`, `model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_integrations_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(50) NOT NULL,
  `settings_json` LONGTEXT NULL,
  `updated_by` INT(11) NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_integrations_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(50) NOT NULL,
  `run_id` VARCHAR(40) NOT NULL,
  `started_at` DATETIME NULL,
  `finished_at` DATETIME NULL,
  `status` ENUM('running','success','failed') NOT NULL DEFAULT 'running',
  `summary_json` LONGTEXT NULL,
  `error_message` TEXT NULL,
  `created_by` INT(11) NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `provider` (`provider`),
  KEY `run_id` (`run_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_kits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `default_losses_percent` DECIMAL(6,2) NOT NULL DEFAULT 14.00,
  `default_markup_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT(11) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_kit_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kit_id` INT(11) NOT NULL,
  `product_id` INT(11) NULL,
  `item_type` ENUM('product','custom') NOT NULL DEFAULT 'product',
  `name` VARCHAR(200) NULL,
  `description` VARCHAR(255) NULL,
  `qty` DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  `unit` VARCHAR(20) NULL,
  `cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `price` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `is_optional` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `rule_json` LONGTEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kit` (`kit_id`),
  KEY `idx_sort` (`kit_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_utilities` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(190) NOT NULL,
  `uf` VARCHAR(2) NULL,
  `code` VARCHAR(60) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uf` (`uf`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_tariffs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `utility_id` INT(11) NOT NULL,
  `group_type` ENUM('A','B') NOT NULL DEFAULT 'B',
  `modality` VARCHAR(120) NULL,
  `te` DECIMAL(16,6) NOT NULL DEFAULT 0,
  `tusd` DECIMAL(16,6) NOT NULL DEFAULT 0,
  `te_value` DECIMAL(16,6) NOT NULL DEFAULT 0,
  `tusd_value` DECIMAL(16,6) NOT NULL DEFAULT 0,
  `flags_value` DECIMAL(16,6) NOT NULL DEFAULT 0,
  `other` JSON NULL,
  `valid_from` DATE NULL,
  `valid_to` DATE NULL,
  PRIMARY KEY (`id`),
  KEY `utility_id` (`utility_id`),
  KEY `group_type` (`group_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_electrical_designs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_version_id` INT(11) NULL,
  `kit_id` INT(11) NULL,
  `module_product_id` INT(11) NULL,
  `inverter_product_id` INT(11) NULL,
  `module_qty_total` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `design_json` LONGTEXT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `kit_id` (`kit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_energy_results_12m` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_version_id` INT(11) NOT NULL,
  `month` TINYINT(2) NOT NULL,
  `irradiation_kwh_kwp` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `energy_generated_kwh` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `energy_offset_kwh` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `savings_value` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `project_version_id` (`project_version_id`),
  KEY `month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_energy_results_25y` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_version_id` INT(11) NOT NULL,
  `year` TINYINT(2) NOT NULL,
  `energy_generated_kwh` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `tariff_value` DECIMAL(16,6) NOT NULL DEFAULT 0.000000,
  `annual_savings` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `cumulative_savings` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
  `degradation_factor` DECIMAL(10,6) NOT NULL DEFAULT 1.000000,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `project_version_id` (`project_version_id`),
  KEY `year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_financial_results` (
  `project_version_id` INT(11) NOT NULL,
  `investment_value` DECIMAL(16,2) NOT NULL DEFAULT 0.00,
  `annual_savings_year1` DECIMAL(16,2) NOT NULL DEFAULT 0.00,
  `payback_years` INT(11) NOT NULL DEFAULT 0,
  `payback_months` INT(11) NOT NULL DEFAULT 0,
  `irr_percent` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `npv_value` DECIMAL(16,2) NOT NULL DEFAULT 0.00,
  `economia_media_mensal_lei_14300` DECIMAL(16,2) NOT NULL DEFAULT 0.00,
  `payback_ano_lei_14300` DECIMAL(10,4) NOT NULL DEFAULT 0,
  `total_economizado_25_anos_lei_14300` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`project_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_regulatory_profiles` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `rules_json` LONGTEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_project_regulatory_snapshots` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_version_id` INT(11) NOT NULL,
  `profile_id` INT(11) NULL,
  `snapshot_json` LONGTEXT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `project_version_id` (`project_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_project_tariff_snapshots` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_version_id` INT(11) NOT NULL,
  `utility_id` INT(11) NULL,
  `tariff_id` INT(11) NULL,
  `snapshot_json` LONGTEXT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `project_version_id` (`project_version_id`),
  KEY `utility_id` (`utility_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_irradiation_cache` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(20) NOT NULL,
  `lat` DECIMAL(10,6) NOT NULL,
  `lon` DECIMAL(10,6) NOT NULL,
  `monthly_json` LONGTEXT NOT NULL,
  `annual_value` DECIMAL(12,4) NULL,
  `created_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `provider_lat_lon` (`provider`, `lat`, `lon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_project_irradiation_snapshots` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_version_id` INT(11) NOT NULL,
  `provider` VARCHAR(20) NULL,
  `lat` DECIMAL(10,6) NULL,
  `lon` DECIMAL(10,6) NULL,
  `monthly_json` LONGTEXT NULL,
  `annual_value` DECIMAL(12,4) NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `project_version_id` (`project_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_proposals` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_version_id` INT(11) NOT NULL,
  `pdf_path` VARCHAR(255) NOT NULL,
  `total_value` DECIMAL(16,2) NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `project_version_id` (`project_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}fv_project_assistant_data` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_version_id` INT(11) NOT NULL,
  `cep` VARCHAR(20) NULL,
  `consumption_kwh_month` DECIMAL(12,2) NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `project_version_id` (`project_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
