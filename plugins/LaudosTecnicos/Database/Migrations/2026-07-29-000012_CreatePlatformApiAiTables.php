<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlatformApiAiTables extends Migration
{
    public function up()
    {
        $this->createApiDevicesTable();
        $this->createApiTokensTable();
        $this->createApiRequestLogsTable();
        $this->createRecordSyncsTable();
        $this->createAiPromptsTable();
        $this->createAiUsageLogsTable();
    }

    public function down()
    {
        foreach (array(
            'laudo_ai_usage_logs',
            'laudo_ai_prompts',
            'laudo_record_syncs',
            'laudo_api_request_logs',
            'laudo_api_tokens',
            'laudo_api_devices',
        ) as $table) {
            $this->forge->dropTable($this->db->prefixTable($table), true);
        }
    }

    private function createApiDevicesTable(): void
    {
        $table = $this->db->prefixTable('laudo_api_devices');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'device_uuid' => array('type' => 'VARCHAR', 'constraint' => 80),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'device_name' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'platform' => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => true),
            'app_version' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true),
            'push_token' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'last_ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'null' => true),
            'expires_at' => array('type' => 'DATETIME', 'null' => true),
            'last_seen_at' => array('type' => 'DATETIME', 'null' => true),
            'last_sync_at' => array('type' => 'DATETIME', 'null' => true),
            'sync_cursor' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'revoked_at' => array('type' => 'DATETIME', 'null' => true),
            'revoked_reason' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => array('type' => 'DATETIME', 'null' => true),
            'updated_at' => array('type' => 'DATETIME', 'null' => true),
        ));
        $this->forge->addKey('id', true);
        $this->forge->addKey('device_uuid', false, true);
        $this->forge->createTable($table, true);
    }

    private function createApiTokensTable(): void
    {
        $table = $this->db->prefixTable('laudo_api_tokens');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'device_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'token_type' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'access'),
            'token_hash' => array('type' => 'VARCHAR', 'constraint' => 255),
            'refresh_token_hash' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'expires_at' => array('type' => 'DATETIME', 'null' => true),
            'refresh_expires_at' => array('type' => 'DATETIME', 'null' => true),
            'revoked_at' => array('type' => 'DATETIME', 'null' => true),
            'revoked_reason' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'last_used_at' => array('type' => 'DATETIME', 'null' => true),
            'scope_json' => array('type' => 'LONGTEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => array('type' => 'DATETIME', 'null' => true),
            'updated_at' => array('type' => 'DATETIME', 'null' => true),
        ));
        $this->forge->addKey('id', true);
        $this->forge->addKey('token_type');
        $this->forge->createTable($table, true);
    }

    private function createApiRequestLogsTable(): void
    {
        $table = $this->db->prefixTable('laudo_api_request_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'device_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'method' => array('type' => 'VARCHAR', 'constraint' => 10, 'default' => 'GET'),
            'endpoint' => array('type' => 'VARCHAR', 'constraint' => 255),
            'status_code' => array('type' => 'INT', 'constraint' => 11, 'default' => 200),
            'request_json' => array('type' => 'LONGTEXT', 'null' => true),
            'response_json' => array('type' => 'LONGTEXT', 'null' => true),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'null' => true),
            'user_agent' => array('type' => 'TEXT', 'null' => true),
            'created_at' => array('type' => 'DATETIME', 'null' => true),
        ));
        $this->forge->addKey('id', true);
        $this->forge->createTable($table, true);
    }

    private function createRecordSyncsTable(): void
    {
        $table = $this->db->prefixTable('laudo_record_syncs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'entity_type' => array('type' => 'VARCHAR', 'constraint' => 80),
            'entity_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
            'uuid' => array('type' => 'VARCHAR', 'constraint' => 80),
            'local_created_at' => array('type' => 'DATETIME', 'null' => true),
            'local_updated_at' => array('type' => 'DATETIME', 'null' => true),
            'server_updated_at' => array('type' => 'DATETIME', 'null' => true),
            'version' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'device_uuid' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'sync_status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'synced'),
            'record_hash' => array('type' => 'VARCHAR', 'constraint' => 128, 'null' => true),
            'payload_json' => array('type' => 'LONGTEXT', 'null' => true),
            'created_at' => array('type' => 'DATETIME', 'null' => true),
            'updated_at' => array('type' => 'DATETIME', 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addKey('id', true);
        $this->forge->addKey('uuid', false, true);
        $this->forge->addKey(array('entity_type', 'entity_id'));
        $this->forge->createTable($table, true);
    }

    private function createAiPromptsTable(): void
    {
        $table = $this->db->prefixTable('laudo_ai_prompts');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'description' => array('type' => 'TEXT', 'null' => true),
            'template_text' => array('type' => 'LONGTEXT', 'null' => true),
            'category' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'version' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => array('type' => 'DATETIME', 'null' => true),
            'updated_at' => array('type' => 'DATETIME', 'null' => true),
        ));
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->createTable($table, true);
    }

    private function createAiUsageLogsTable(): void
    {
        $table = $this->db->prefixTable('laudo_ai_usage_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'prompt_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'resource_type' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'resource_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'provider' => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => true),
            'model' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'prompt_text' => array('type' => 'LONGTEXT', 'null' => true),
            'response_text' => array('type' => 'LONGTEXT', 'null' => true),
            'tokens_input' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'tokens_output' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'temperature' => array('type' => 'DECIMAL', 'constraint' => '4,2', 'default' => 0),
            'meta_json' => array('type' => 'LONGTEXT', 'null' => true),
            'created_at' => array('type' => 'DATETIME', 'null' => true),
        ));
        $this->forge->addKey('id', true);
        $this->forge->createTable($table, true);
    }
}
