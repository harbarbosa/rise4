CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_ordens` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cliente_id` INT(11) NULL,
  `tecnico_id` INT(11) NULL,
  `titulo` VARCHAR(255) NULL,
  `tipo_id` INT(11) NULL,
  `motivo_id` INT(11) NULL,
  `project_id` INT(11) NULL,
  `task_id` INT(11) NULL,
  `contract_id` INT(11) NULL,
  `status` VARCHAR(50) NULL,
  `data_abertura` DATE NULL,
  `data_fechamento` DATE NULL,
  `descricao` TEXT NULL,
  `valor_total` DECIMAL(15,2) DEFAULT 0,
  `created_by` INT(11) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Atendimentos (appointments/visits) vinculados à OS
CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_atendimentos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `os_id` INT(11) NOT NULL,
  `start_datetime` DATETIME NULL,
  `end_datetime` DATETIME NULL,
  `files` TEXT NULL,
  `notes` TEXT NULL,
  `created_by` INT(11) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`os_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Membros de equipe vinculados a cada atendimento
CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_atendimentos_members` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `atendimento_id` INT(11) NOT NULL,
  `member_id` INT(11) NOT NULL,
  `created_at` DATETIME NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`atendimento_id`),
  INDEX (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_tipos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_motivos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_categorias` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments for OS
CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_comments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `os_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `comment` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`os_id`),
  INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Serviços catalogáveis
CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_servicos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo` VARCHAR(20) NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `categoria_receita` INT(11) NULL,
  `custo` DECIMAL(10,2) DEFAULT 0,
  `margem` DECIMAL(10,2) DEFAULT 0,
  `valor_venda` DECIMAL(10,2) DEFAULT 0,
  `servico_locacao` TINYINT(1) DEFAULT 0,
  `bloquear_inadimplencia` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`categoria_receita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Itens de serviços vinculados à OS
CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_services_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `os_id` INT(11) NOT NULL,
  `service_id` INT(11) NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `quantidade` DECIMAL(10,2) DEFAULT 0,
  `unidade` VARCHAR(20) DEFAULT 'UN',
  `valor_unitario` DECIMAL(10,2) DEFAULT 0,
  `desconto` DECIMAL(10,2) DEFAULT 0,
  `valor_total` DECIMAL(10,2) DEFAULT 0,
  `tipo_cobranca` VARCHAR(20) DEFAULT 'cobrado',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`os_id`),
  INDEX (`tipo_cobranca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Itens de produtos vinculados à OS
CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_products_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `os_id` INT(11) NOT NULL,
  `product_id` INT(11) NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `quantidade` DECIMAL(10,2) DEFAULT 0,
  `unidade` VARCHAR(20) DEFAULT 'UN',
  `valor_unitario` DECIMAL(10,2) DEFAULT 0,
  `desconto` DECIMAL(10,2) DEFAULT 0,
  `valor_total` DECIMAL(10,2) DEFAULT 0,
  `tipo_cobranca` VARCHAR(20) DEFAULT 'cobrado',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`os_id`),
  INDEX (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Arquivos da OS (similar a project_files)
CREATE TABLE IF NOT EXISTS `{{DB_PREFIX}}os_files` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `os_id` INT(11) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `original_file_name` VARCHAR(255) NULL,
  `file_id` VARCHAR(255) NULL,
  `service_type` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `file_size` BIGINT NULL,
  `category_id` INT(11) NULL,
  `uploaded_by` INT(11) NULL,
  `created_at` DATETIME NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX (`os_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
