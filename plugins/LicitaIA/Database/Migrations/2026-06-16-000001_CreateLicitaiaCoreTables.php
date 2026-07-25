<?php

namespace LicitaIA\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLicitaiaCoreTables extends Migration
{
    public function up()
    {
        $charset = $this->db->charset ?: 'utf8mb4';
        $collation = $this->db->DBCollat ?: 'utf8mb4_unicode_ci';

        $tables = array(
            'licitaia_settings' => array(
                'fields' => array(
                    'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                    'setting_name' => array('type' => 'VARCHAR', 'constraint' => 190),
                    'setting_value' => array('type' => 'LONGTEXT', 'null' => true),
                    'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                    'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                ),
                'keys' => array(
                    array('setting_name', false, true),
                ),
            ),
            'licitaia_sources' => array(
                'fields' => array(
                    'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                    'name' => array('type' => 'VARCHAR', 'constraint' => 190),
                    'source_type' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'portal'),
                    'url' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'city' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'state' => array('type' => 'VARCHAR', 'constraint' => 2, 'null' => true, 'default' => null),
                    'search_frequency' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'manual'),
                    'base_url' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'api_endpoint' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                    'notes' => array('type' => 'TEXT', 'null' => true),
                    'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'last_search_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'last_search_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                ),
                'keys' => array(
                    array('name', false, false),
                    array('source_type'),
                    array('active'),
                    array('search_frequency'),
                    array('created_by'),
                ),
            ),
            'licitaia_opportunities' => array(
                'fields' => array(
                    'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                    'title' => array('type' => 'VARCHAR', 'constraint' => 255),
                    'description' => array('type' => 'LONGTEXT', 'null' => true),
                    'public_agency' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'public_body' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'edital_number' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'notice_number' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'process_number' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'modality' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'object' => array('type' => 'LONGTEXT', 'null' => true),
                    'object_description' => array('type' => 'LONGTEXT', 'null' => true),
                    'submission_deadline' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'default' => null),
                    'publication_date' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'default' => null),
                    'opening_date' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'default' => null),
                    'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'new'),
                    'ai_status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'),
                    'source_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'responsible_user_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'jurisdiction' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'city' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'state' => array('type' => 'VARCHAR', 'constraint' => 2, 'null' => true, 'default' => null),
                    'estimated_value' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                    'document_url' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'original_link' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'source_url' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'notes' => array('type' => 'TEXT', 'null' => true),
                    'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                ),
                'keys' => array(
                    array('title'),
                    array('public_agency'),
                    array('status'),
                    array('ai_status'),
                    array('source_id'),
                    array('responsible_user_id'),
                    array('created_by'),
                ),
            ),
            'licitaia_keywords' => array(
                'fields' => array(
                    'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                    'keyword' => array('type' => 'VARCHAR', 'constraint' => 190),
                    'category' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'keyword_type' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'include'),
                    'weight' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                    'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                    'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                ),
                'keys' => array(
                    array('keyword', false, true),
                    array('category'),
                    array('active'),
                ),
            ),
            'licitaia_checklist_items' => array(
                'fields' => array(
                    'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                    'opportunity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'item_name' => array('type' => 'VARCHAR', 'constraint' => 255),
                    'category' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'description' => array('type' => 'LONGTEXT', 'null' => true),
                    'is_required' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                    'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'open'),
                    'notes' => array('type' => 'TEXT', 'null' => true),
                    'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
                    'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                    'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                ),
                'keys' => array(
                    array('opportunity_id'),
                    array('item_name'),
                    array('category'),
                    array('status'),
                ),
            ),
            'licitaia_ai_analyses' => array(
                'fields' => array(
                    'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                    'opportunity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'model_name' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
                    'prompt_version' => array('type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'default' => null),
                    'request_json' => array('type' => 'LONGTEXT', 'null' => true),
                    'response_json' => array('type' => 'LONGTEXT', 'null' => true),
                    'summary' => array('type' => 'LONGTEXT', 'null' => true),
                    'ai_risks' => array('type' => 'LONGTEXT', 'null' => true),
                    'ai_requirements' => array('type' => 'LONGTEXT', 'null' => true),
                    'ai_recommendation' => array('type' => 'LONGTEXT', 'null' => true),
                    'technical_score' => array('type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0),
                    'risk_level' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null),
                    'recommendation' => array('type' => 'LONGTEXT', 'null' => true),
                    'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'),
                    'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                ),
                'keys' => array(
                    array('opportunity_id'),
                    array('status'),
                ),
            ),
            'licitaia_reports' => array(
                'fields' => array(
                    'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                    'opportunity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'report_type' => array('type' => 'VARCHAR', 'constraint' => 60, 'default' => 'technical_opinion'),
                    'title' => array('type' => 'VARCHAR', 'constraint' => 255),
                    'file_path' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'file_name' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
                    'generated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                    'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                    'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                ),
                'keys' => array(
                    array('opportunity_id'),
                    array('report_type'),
                ),
            ),
        );

        foreach ($tables as $table_name => $definition) {
            $table = $this->db->prefixTable($table_name);
            if ($this->db->tableExists($table)) {
                continue;
            }

            $this->forge->addField($definition['fields']);
            $this->forge->addKey('id', true);
            foreach ($definition['keys'] as $key) {
                $this->forge->addKey(...$key);
            }
            $this->forge->createTable($table_name, true, array(
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => $charset,
                'COLLATE' => $collation,
            ));
        }
    }

    public function down()
    {
        $this->forge->dropTable('licitaia_reports', true);
        $this->forge->dropTable('licitaia_ai_analyses', true);
        $this->forge->dropTable('licitaia_checklist_items', true);
        $this->forge->dropTable('licitaia_keywords', true);
        $this->forge->dropTable('licitaia_opportunities', true);
        $this->forge->dropTable('licitaia_sources', true);
        $this->forge->dropTable('licitaia_settings', true);
    }
}
