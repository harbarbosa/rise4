<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEnergyApiIntegrationSupport extends Migration
{
    public function up()
    {
        $distributors_table = $this->db->prefixTable('fv_distributors');
        if ($this->db->tableExists($distributors_table)) {
            $fields = $this->db->getFieldNames($distributors_table);
            if (is_array($fields)) {
                if (!in_array('external_slug', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `external_slug` VARCHAR(190) NULL AFTER `state_code`");
                }
                if (!in_array('source', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `source` VARCHAR(30) NOT NULL DEFAULT 'local' AFTER `external_slug`");
                }
                if (!in_array('is_synced', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `is_synced` TINYINT(1) NOT NULL DEFAULT 0 AFTER `source`");
                }
                if (!in_array('raw_payload', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `raw_payload` LONGTEXT NULL AFTER `is_synced`");
                }
            }
        }

        $logs_table = $this->db->prefixTable('fv_integration_logs');
        if ($this->db->tableExists($logs_table)) {
            $fields = $this->db->getFieldNames($logs_table);
            if (is_array($fields) && !in_array('latency_ms', $fields, true)) {
                $this->db->query("ALTER TABLE `{$logs_table}` ADD `latency_ms` INT(11) NULL DEFAULT NULL AFTER `http_status`");
            }
        }

        $cache_table = $this->db->prefixTable('fv_external_cache');
        if (!$this->db->tableExists($cache_table)) {
            $this->forge->addField(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ),
                'provider' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => false,
                ),
                'cache_key' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                ),
                'endpoint' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ),
                'request_hash' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ),
                'payload_json' => array(
                    'type' => 'LONGTEXT',
                    'null' => true,
                ),
                'expires_at' => array(
                    'type' => 'DATETIME',
                    'null' => true,
                ),
                'created_by' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => true,
                ),
                'updated_at' => array(
                    'type' => 'DATETIME',
                    'null' => true,
                ),
                'deleted' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ),
            ));
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('cache_key');
            $this->forge->addKey('provider');
            $this->forge->addKey('endpoint');
            $this->forge->createTable('fv_external_cache', true);
        }
    }

    public function down()
    {
        $cache_table = $this->db->prefixTable('fv_external_cache');
        if ($this->db->tableExists($cache_table)) {
            $this->forge->dropTable('fv_external_cache', true);
        }
    }
}
