<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFotovoltaicoOperationalTables extends Migration
{
    public function up()
    {
        $tariffs_table = $this->db->prefixTable('fv_tariffs');
        if (!$this->db->tableExists($tariffs_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'distributor_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'utility_name' => array('type' => 'VARCHAR', 'constraint' => 190),
                'region' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                'state' => array('type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'default' => null),
                'tariff_class' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                'energy_rate' => array('type' => 'DECIMAL', 'constraint' => '16,6', 'default' => 0),
                'demand_rate' => array('type' => 'DECIMAL', 'constraint' => '16,6', 'default' => 0),
                'availability_rate' => array('type' => 'DECIMAL', 'constraint' => '16,6', 'default' => 0),
                'tax_rate' => array('type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0),
                'valid_from' => array('type' => 'DATE', 'null' => true, 'default' => null),
                'valid_to' => array('type' => 'DATE', 'null' => true, 'default' => null),
                'notes' => array('type' => 'TEXT', 'null' => true),
                'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('distributor_id');
            $this->forge->addKey('utility_name');
            $this->forge->addKey('valid_from');
            $this->forge->addKey('valid_to');
            $this->forge->createTable('fv_tariffs', true, array('ENGINE' => 'InnoDB'));
        }

        $insolation_cache_table = $this->db->prefixTable('fv_insolation_cache');
        if (!$this->db->tableExists($insolation_cache_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'cache_key' => array('type' => 'VARCHAR', 'constraint' => 190),
                'provider' => array('type' => 'VARCHAR', 'constraint' => 120),
                'location_label' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'default' => null),
                'latitude' => array('type' => 'DECIMAL', 'constraint' => '10,6', 'null' => true, 'default' => null),
                'longitude' => array('type' => 'DECIMAL', 'constraint' => '10,6', 'null' => true, 'default' => null),
                'year' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'month' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'payload_json' => array('type' => 'LONGTEXT', 'null' => true),
                'expires_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'fetched_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('cache_key', false, true);
            $this->forge->addKey('provider');
            $this->forge->addKey('latitude');
            $this->forge->addKey('longitude');
            $this->forge->createTable('fv_insolation_cache', true, array('ENGINE' => 'InnoDB'));
        }

        $integration_logs_table = $this->db->prefixTable('fv_integration_logs');
        if (!$this->db->tableExists($integration_logs_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'provider' => array('type' => 'VARCHAR', 'constraint' => 120),
                'endpoint' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                'method' => array('type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'default' => null),
                'request_json' => array('type' => 'LONGTEXT', 'null' => true),
                'response_json' => array('type' => 'LONGTEXT', 'null' => true),
                'http_status' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'cache_hit' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'success' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'error_message' => array('type' => 'TEXT', 'null' => true),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('provider');
            $this->forge->addKey('http_status');
            $this->forge->addKey('cache_hit');
            $this->forge->createTable('fv_integration_logs', true, array('ENGINE' => 'InnoDB'));
        }

        $audit_logs_table = $this->db->prefixTable('fv_audit_logs');
        if (!$this->db->tableExists($audit_logs_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'entity_type' => array('type' => 'VARCHAR', 'constraint' => 120),
                'entity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'action' => array('type' => 'VARCHAR', 'constraint' => 80),
                'old_json' => array('type' => 'LONGTEXT', 'null' => true),
                'new_json' => array('type' => 'LONGTEXT', 'null' => true),
                'changes_json' => array('type' => 'LONGTEXT', 'null' => true),
                'ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'null' => true, 'default' => null),
                'user_agent' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('entity_type');
            $this->forge->addKey('entity_id');
            $this->forge->addKey('action');
            $this->forge->addKey('created_by');
            $this->forge->createTable('fv_audit_logs', true, array('ENGINE' => 'InnoDB'));
        }

        $settings_table = $this->db->prefixTable('fv_settings');
        if (!$this->db->tableExists($settings_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'setting_name' => array('type' => 'VARCHAR', 'constraint' => 190),
                'setting_value' => array('type' => 'LONGTEXT', 'null' => true),
                'setting_type' => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => 'app'),
                'group_name' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('setting_name', false, true);
            $this->forge->addKey('setting_type');
            $this->forge->addKey('group_name');
            $this->forge->createTable('fv_settings', true, array('ENGINE' => 'InnoDB'));
        }
    }

    public function down()
    {
        $this->forge->dropTable('fv_settings', true);
        $this->forge->dropTable('fv_audit_logs', true);
        $this->forge->dropTable('fv_integration_logs', true);
        $this->forge->dropTable('fv_insolation_cache', true);
        $this->forge->dropTable('fv_tariffs', true);
    }
}
