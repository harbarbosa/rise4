<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para adicionar colunas de integração em fv_products.
 */
class UpdateFvProductsIntegrations extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_products');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = $this->db->getFieldNames($table);
        $add = function ($name, $sql) use ($table, $fields) {
            if (!in_array($name, $fields)) {
                $this->db->query("ALTER TABLE `{$table}` {$sql}");
            }
        };

        $add('source', "ADD `source` ENUM('manual','cec','import') NOT NULL DEFAULT 'manual' AFTER `created_by`");
        $add('source_ref', "ADD `source_ref` VARCHAR(120) NULL AFTER `source`");
        $add('last_synced_at', "ADD `last_synced_at` DATETIME NULL AFTER `source_ref`");
        $add('external_hash', "ADD `external_hash` VARCHAR(64) NULL AFTER `last_synced_at`");

        // Índices
        $indexes = $this->db->query("SHOW INDEX FROM `{$table}`")->getResult();
        $existing = array();
        foreach ($indexes as $idx) {
            $existing[$idx->Key_name] = true;
        }
        if (!isset($existing['idx_source_ref'])) {
            $this->db->query("CREATE INDEX idx_source_ref ON `{$table}` (`source`, `source_ref`)");
        }
        if (!isset($existing['idx_brand_model_type'])) {
            $this->db->query("CREATE INDEX idx_brand_model_type ON `{$table}` (`type`, `brand`, `model`)");
        }
    }

    public function down()
    {
        // Sem rollback automático
    }
}
