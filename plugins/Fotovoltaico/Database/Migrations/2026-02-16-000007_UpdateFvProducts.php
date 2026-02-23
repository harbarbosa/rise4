<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para ajustar tabela de produtos (renomear e novos campos).
 */
class UpdateFvProducts extends Migration
{
    public function up()
    {
        $db = $this->db;
        $old_table = $db->prefixTable('fv_product');
        $new_table = $db->prefixTable('fv_products');

        if ($db->tableExists($old_table) && !$db->tableExists($new_table)) {
            $db->query("RENAME TABLE `{$old_table}` TO `{$new_table}`");
        }

        if (!$db->tableExists($new_table)) {
            return;
        }

        $fields = $db->getFieldNames($new_table);
        $add = function ($name, $sql) use ($db, $new_table, $fields) {
            if (!in_array($name, $fields)) {
                $db->query("ALTER TABLE `{$new_table}` {$sql}");
            }
        };

        $add('power_w', "ADD `power_w` DECIMAL(10,2) NULL AFTER `sku`");
        $add('warranty_years', "ADD `warranty_years` INT NULL AFTER `price`");
        if (in_array('specs', $fields) && !in_array('specs_json', $fields)) {
            $db->query("ALTER TABLE `{$new_table}` CHANGE `specs` `specs_json` LONGTEXT NULL");
        } else {
            $add('specs_json', "ADD `specs_json` LONGTEXT NULL AFTER `datasheet_url`");
        }
        $add('is_active', "ADD `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `specs_json`");
        $add('created_by', "ADD `created_by` INT NULL AFTER `is_active`");

        // Ajustar tamanho de colunas se necessário
        $db->query("ALTER TABLE `{$new_table}` MODIFY `model` VARCHAR(160) NULL");
        $db->query("ALTER TABLE `{$new_table}` MODIFY `cost` DECIMAL(14,2) NOT NULL DEFAULT 0");
        $db->query("ALTER TABLE `{$new_table}` MODIFY `price` DECIMAL(14,2) NOT NULL DEFAULT 0");

        // Ajustar enum de type
        $db->query("ALTER TABLE `{$new_table}` MODIFY `type` ENUM('module','inverter','service','structure','stringbox','cable','other') NOT NULL DEFAULT 'module'");

        // Índices (checar antes de criar)
        $indexes = $db->query("SHOW INDEX FROM `{$new_table}`")->getResult();
        $existing = array();
        foreach ($indexes as $idx) {
            $existing[$idx->Key_name] = true;
        }
        if (!isset($existing['idx_type'])) {
            $db->query("CREATE INDEX idx_type ON `{$new_table}` (`type`)");
        }
        if (!isset($existing['idx_brand_model'])) {
            $db->query("CREATE INDEX idx_brand_model ON `{$new_table}` (`brand`, `model`)");
        }
        if (!isset($existing['idx_active'])) {
            $db->query("CREATE INDEX idx_active ON `{$new_table}` (`is_active`)");
        }
    }

    public function down()
    {
        // Sem rollback automático seguro para renomeio/alterações.
    }
}
