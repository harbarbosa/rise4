<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para criar tabelas de integrações (settings e logs).
 */
class CreateFvIntegrationsTables extends Migration
{
    public function up()
    {
        $settings = $this->db->prefixTable('fv_integrations_settings');
        if (!$this->db->tableExists($settings)) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'provider' => ['type' => 'VARCHAR', 'constraint' => 50],
                'settings_json' => ['type' => 'LONGTEXT', 'null' => true],
                'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('provider');
            $this->forge->createTable('fv_integrations_settings', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4'
            ]);
        }

        $logs = $this->db->prefixTable('fv_integrations_logs');
        if (!$this->db->tableExists($logs)) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'provider' => ['type' => 'VARCHAR', 'constraint' => 50],
                'run_id' => ['type' => 'VARCHAR', 'constraint' => 40],
                'started_at' => ['type' => 'DATETIME', 'null' => true],
                'finished_at' => ['type' => 'DATETIME', 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['running','success','failed'], 'default' => 'running'],
                'summary_json' => ['type' => 'LONGTEXT', 'null' => true],
                'error_message' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('provider');
            $this->forge->addKey('run_id');
            $this->forge->createTable('fv_integrations_logs', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4'
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('fv_integrations_logs', true);
        $this->forge->dropTable('fv_integrations_settings', true);
    }
}
