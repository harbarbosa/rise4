<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBelenusIntegrationSupport extends Migration
{
    public function up()
    {
        $this->ensureProductColumns();
        $this->ensureKitColumns();
        $this->ensureCacheTable();
        $this->ensureImportLogTable();
        $this->ensureIntegrationLogColumns();
    }

    public function down()
    {
        $cache_table = $this->db->prefixTable('fv_belenus_cache');
        $import_logs_table = $this->db->prefixTable('fv_belenus_import_logs');
        if ($this->db->tableExists($cache_table)) {
            $this->forge->dropTable('fv_belenus_cache', true);
        }
        if ($this->db->tableExists($import_logs_table)) {
            $this->forge->dropTable('fv_belenus_import_logs', true);
        }
    }

    private function ensureProductColumns()
    {
        $table = $this->db->prefixTable('fv_products');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = $this->db->getFieldNames($table);
        if (!is_array($fields)) {
            return;
        }

        $columns = array(
            'external_provider' => "ALTER TABLE `{$table}` ADD `external_provider` VARCHAR(50) NULL DEFAULT NULL AFTER `technical_specs_json`",
            'external_id' => "ALTER TABLE `{$table}` ADD `external_id` VARCHAR(80) NULL DEFAULT NULL AFTER `external_provider`",
            'external_payload_json' => "ALTER TABLE `{$table}` ADD `external_payload_json` LONGTEXT NULL AFTER `external_id`",
            'promotional_price' => "ALTER TABLE `{$table}` ADD `promotional_price` DECIMAL(16,2) NOT NULL DEFAULT 0 AFTER `sale_price`",
            'stock' => "ALTER TABLE `{$table}` ADD `stock` DECIMAL(16,4) NOT NULL DEFAULT 0 AFTER `promotional_price`",
            'last_price_sync_at' => "ALTER TABLE `{$table}` ADD `last_price_sync_at` DATETIME NULL DEFAULT NULL AFTER `stock`",
            'last_sync_at' => "ALTER TABLE `{$table}` ADD `last_sync_at` DATETIME NULL DEFAULT NULL AFTER `last_price_sync_at`",
            'last_import_at' => "ALTER TABLE `{$table}` ADD `last_import_at` DATETIME NULL DEFAULT NULL AFTER `last_sync_at`",
        );

        foreach ($columns as $column => $sql) {
            if (!in_array($column, $fields, true)) {
                $this->db->query($sql);
            }
        }
    }

    private function ensureKitColumns()
    {
        $table = $this->db->prefixTable('fv_kits');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = $this->db->getFieldNames($table);
        if (!is_array($fields)) {
            return;
        }

        $columns = array(
            'external_provider' => "ALTER TABLE `{$table}` ADD `external_provider` VARCHAR(50) NULL DEFAULT NULL AFTER `notes`",
            'external_id' => "ALTER TABLE `{$table}` ADD `external_id` VARCHAR(80) NULL DEFAULT NULL AFTER `external_provider`",
            'external_payload_json' => "ALTER TABLE `{$table}` ADD `external_payload_json` LONGTEXT NULL AFTER `external_id`",
            'promotional_price' => "ALTER TABLE `{$table}` ADD `promotional_price` DECIMAL(16,2) NOT NULL DEFAULT 0 AFTER `total_price`",
            'stock' => "ALTER TABLE `{$table}` ADD `stock` DECIMAL(16,4) NOT NULL DEFAULT 0 AFTER `promotional_price`",
            'last_price_sync_at' => "ALTER TABLE `{$table}` ADD `last_price_sync_at` DATETIME NULL DEFAULT NULL AFTER `stock`",
            'last_sync_at' => "ALTER TABLE `{$table}` ADD `last_sync_at` DATETIME NULL DEFAULT NULL AFTER `last_price_sync_at`",
            'last_import_at' => "ALTER TABLE `{$table}` ADD `last_import_at` DATETIME NULL DEFAULT NULL AFTER `last_sync_at`",
        );

        foreach ($columns as $column => $sql) {
            if (!in_array($column, $fields, true)) {
                $this->db->query($sql);
            }
        }
    }

    private function ensureCacheTable()
    {
        $table = $this->db->prefixTable('fv_belenus_cache');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'cache_key' => array('type' => 'VARCHAR', 'constraint' => 255),
            'cache_type' => array('type' => 'VARCHAR', 'constraint' => 80),
            'payload_json' => array('type' => 'LONGTEXT', 'null' => true),
            'expires_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('cache_key');
        $this->forge->addKey('cache_type');
        $this->forge->createTable('fv_belenus_cache', true, array('ENGINE' => 'InnoDB'));
    }

    private function ensureImportLogTable()
    {
        $table = $this->db->prefixTable('fv_belenus_import_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'provider' => array('type' => 'VARCHAR', 'constraint' => 50, 'default' => 'belenus'),
            'entity_type' => array('type' => 'VARCHAR', 'constraint' => 30),
            'external_id' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'default' => null),
            'local_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'action' => array('type' => 'VARCHAR', 'constraint' => 30),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'completed'),
            'message' => array('type' => 'TEXT', 'null' => true),
            'payload_json' => array('type' => 'LONGTEXT', 'null' => true),
            'response_json' => array('type' => 'LONGTEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addKey('id', true);
        $this->forge->addKey('provider');
        $this->forge->addKey('entity_type');
        $this->forge->addKey('external_id');
        $this->forge->addKey('local_id');
        $this->forge->addKey('action');
        $this->forge->createTable('fv_belenus_import_logs', true, array('ENGINE' => 'InnoDB'));
    }

    private function ensureIntegrationLogColumns()
    {
        $table = $this->db->prefixTable('fv_integration_logs');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = $this->db->getFieldNames($table);
        if (!is_array($fields)) {
            return;
        }

        if (!in_array('latency_ms', $fields, true)) {
            $this->db->query("ALTER TABLE `{$table}` ADD `latency_ms` INT(11) NULL DEFAULT NULL AFTER `http_status`");
        }
    }
}
