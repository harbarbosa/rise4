CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_suppliers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NULL,
  `phone` varchar(50) NULL,
  `tax_id` varchar(100) NULL,
  `address` text NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_suppliers_company` (`company_id`),
  KEY `idx_purchases_suppliers_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_transportadoras` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NULL,
  `phone` varchar(50) NULL,
  `tax_id` varchar(100) NULL,
  `address` text NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_transportadoras_company` (`company_id`),
  KEY `idx_purchases_transportadoras_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_requests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `request_code_number` int(11) NULL,
  `request_code` varchar(50) NULL,
  `project_id` int(11) NULL,
  `client_id` int(11) NULL,
  `os_id` int(11) NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `cost_center` varchar(255) NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'medium',
  `requester_id` int(11) NULL,
  `requested_by` int(11) NULL,
  `supplier_id` int(11) NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `request_date` datetime NULL,
  `needed_by` date NULL,
  `note` text NULL,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `submitted_at` datetime NULL,
  `approved_by` int(11) NULL,
  `approved_at` datetime NULL,
  `rejected_by` int(11) NULL,
  `rejected_at` datetime NULL,
  `rejected_reason` text NULL,
  `converted_by` int(11) NULL,
  `converted_at` datetime NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_requests_company` (`company_id`),
  KEY `idx_purchases_requests_request_code` (`request_code`),
  KEY `idx_purchases_requests_project` (`project_id`),
  KEY `idx_purchases_requests_client` (`client_id`),
  KEY `idx_purchases_requests_os` (`os_id`),
  KEY `idx_purchases_requests_internal` (`is_internal`),
  KEY `idx_purchases_requests_supplier` (`supplier_id`),
  KEY `idx_purchases_requests_status` (`status`),
  KEY `idx_purchases_requests_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_request_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `request_id` int(11) NOT NULL,
  `item_id` int(11) NULL,
  `description` text NULL,
  `unit` varchar(50) NULL,
  `desired_date` date NULL,
  `note` text NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `rate` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_request_items_company` (`company_id`),
  KEY `idx_purchases_request_items_request` (`request_id`),
  KEY `idx_purchases_request_items_item` (`item_id`),
  KEY `idx_purchases_request_items_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `request_id` int(11) NULL,
  `po_code_number` int(11) NULL,
  `po_code` varchar(50) NULL,
  `supplier_id` int(11) NULL,
  `project_id` int(11) NULL,
  `cost_center` varchar(255) NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `order_date` datetime NULL,
  `expected_delivery_date` date NULL,
  `delivery_address` text NULL,
  `payment_terms` varchar(255) NULL,
  `expected_date` date NULL,
  `note` text NULL,
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_orders_company` (`company_id`),
  KEY `idx_purchases_orders_request` (`request_id`),
  KEY `idx_purchases_orders_supplier` (`supplier_id`),
  KEY `idx_purchases_orders_status` (`status`),
  KEY `idx_purchases_orders_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_order_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NULL,
  `description` text NULL,
  `unit` varchar(50) NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `rate` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_order_items_company` (`company_id`),
  KEY `idx_purchases_order_items_order` (`order_id`),
  KEY `idx_purchases_order_items_item` (`item_id`),
  KEY `idx_purchases_order_items_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_goods_receipts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `order_id` int(11) NOT NULL,
  `received_by` int(11) NULL,
  `nf_number` varchar(100) NULL,
  `status` varchar(50) NOT NULL DEFAULT 'received',
  `receipt_date` datetime NULL,
  `note` text NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_goods_receipts_company` (`company_id`),
  KEY `idx_purchases_goods_receipts_order` (`order_id`),
  KEY `idx_purchases_goods_receipts_status` (`status`),
  KEY `idx_purchases_goods_receipts_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_goods_receipt_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `receipt_id` int(11) NOT NULL,
  `order_item_id` int(11) NULL,
  `item_id` int(11) NULL,
  `description` text NULL,
  `unit` varchar(50) NULL,
  `quantity_received` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `note` text NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_goods_receipt_items_company` (`company_id`),
  KEY `idx_purchases_goods_receipt_items_receipt` (`receipt_id`),
  KEY `idx_purchases_goods_receipt_items_order_item` (`order_item_id`),
  KEY `idx_purchases_goods_receipt_items_item` (`item_id`),
  KEY `idx_purchases_goods_receipt_items_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `context_type` varchar(50) NOT NULL,
  `context_id` int(11) NOT NULL,
  `old_status` varchar(50) NULL,
  `new_status` varchar(50) NULL,
  `note` text NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_logs_company` (`company_id`),
  KEY `idx_purchases_logs_context` (`context_type`, `context_id`),
  KEY `idx_purchases_logs_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_attachments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `context_type` varchar(50) NOT NULL,
  `context_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_file_name` varchar(255) NULL,
  `file_size` int(11) NULL,
  `mime_type` varchar(100) NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_attachments_company` (`company_id`),
  KEY `idx_purchases_attachments_context` (`context_type`, `context_id`),
  KEY `idx_purchases_attachments_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_quotations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `request_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `winner_supplier_id` int(11) NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_quotations_company` (`company_id`),
  KEY `idx_purchases_quotations_request` (`request_id`),
  KEY `idx_purchases_quotations_status` (`status`),
  KEY `idx_purchases_quotations_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_request_approvals` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `request_id` int(11) NOT NULL,
  `approval_type` varchar(50) NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(11) NULL,
  `approved_at` datetime NULL,
  `comment` text NULL,
  `approval_limit_used` decimal(15,2) NULL,
  `total_value_at_approval` decimal(15,2) NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_request_approvals_company` (`company_id`),
  KEY `idx_purchases_request_approvals_request` (`request_id`),
  KEY `idx_purchases_request_approvals_type` (`approval_type`),
  KEY `idx_purchases_request_approvals_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_approvers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `financial_limit` decimal(15,2) NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_approvers_company` (`company_id`),
  KEY `idx_purchases_approvers_user` (`user_id`),
  KEY `idx_purchases_approvers_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_request_task_links_custom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `created_by` int(11) NULL,
  `created_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_request_task_links_request` (`request_id`),
  KEY `idx_purchases_request_task_links_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_request_reminder_links_custom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `created_by` int(11) NULL,
  `created_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_request_reminder_links_request` (`request_id`),
  KEY `idx_purchases_request_reminder_links_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_settings_company` (`company_id`),
  KEY `idx_purchases_settings_key` (`setting_key`),
  KEY `idx_purchases_settings_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_quotation_suppliers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `quotation_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_quotation_suppliers_company` (`company_id`),
  KEY `idx_purchases_quotation_suppliers_quotation` (`quotation_id`),
  KEY `idx_purchases_quotation_suppliers_supplier` (`supplier_id`),
  KEY `idx_purchases_quotation_suppliers_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_quotation_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `quotation_id` int(11) NOT NULL,
  `request_item_id` int(11) NOT NULL,
  `qty` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_quotation_items_company` (`company_id`),
  KEY `idx_purchases_quotation_items_quotation` (`quotation_id`),
  KEY `idx_purchases_quotation_items_request_item` (`request_item_id`),
  KEY `idx_purchases_quotation_items_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}purchases_quotation_item_prices` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `quotation_id` int(11) NOT NULL,
  `request_item_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `lead_time_days` int(11) NULL,
  `delivery_date` date NULL,
  `freight_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_terms` varchar(255) NULL,
  `notes` text NULL,
  `is_winner` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NULL,
  `created_by` int(11) NULL,
  `updated_at` datetime NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchases_quotation_prices_company` (`company_id`),
  KEY `idx_purchases_quotation_prices_quotation` (`quotation_id`),
  KEY `idx_purchases_quotation_prices_request_item` (`request_item_id`),
  KEY `idx_purchases_quotation_prices_supplier` (`supplier_id`),
  KEY `idx_purchases_quotation_prices_deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
