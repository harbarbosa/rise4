<?php

namespace LicitaIA\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicitaiaExtendedSchema extends Migration
{
    public function up()
    {
        $charset = $this->db->charset ?: 'utf8mb4';
        $collation = $this->db->DBCollat ?: 'utf8mb4_unicode_ci';

        $this->ensureOpportunityColumns();
        $this->ensureSourceColumns();
        $this->ensureKeywordColumns();
        $this->ensureChecklistColumns();

        $this->ensureDocumentsTable($charset, $collation);
        $this->ensureOpportunityChecklistTable($charset, $collation);
        $this->ensureAiLogsTable($charset, $collation);
        $this->ensureSearchLogsTable($charset, $collation);
    }

    public function down()
    {
        $this->forge->dropTable('licitaia_search_logs', true);
        $this->forge->dropTable('licitaia_ai_logs', true);
        $this->forge->dropTable('licitaia_opportunity_checklist', true);
        $this->forge->dropTable('licitaia_documents', true);
    }

    private function ensureOpportunityColumns()
    {
        $table = $this->db->prefixTable('licitaia_opportunities');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $columns = array(
            'ai_result_json' => "ALTER TABLE `{$table}` ADD `ai_result_json` LONGTEXT NULL AFTER `notes`",
            'ai_summary' => "ALTER TABLE `{$table}` ADD `ai_summary` LONGTEXT NULL AFTER `ai_result_json`",
            'ai_risks' => "ALTER TABLE `{$table}` ADD `ai_risks` LONGTEXT NULL AFTER `ai_summary`",
            'ai_requirements' => "ALTER TABLE `{$table}` ADD `ai_requirements` LONGTEXT NULL AFTER `ai_risks`",
            'ai_recommendation' => "ALTER TABLE `{$table}` ADD `ai_recommendation` LONGTEXT NULL AFTER `ai_requirements`",
            'technical_score' => "ALTER TABLE `{$table}` ADD `technical_score` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `ai_recommendation`",
            'risk_level' => "ALTER TABLE `{$table}` ADD `risk_level` VARCHAR(20) NULL DEFAULT NULL AFTER `technical_score`",
            'recommendation' => "ALTER TABLE `{$table}` ADD `recommendation` LONGTEXT NULL AFTER `risk_level`",
            'ai_analyzed_at' => "ALTER TABLE `{$table}` ADD `ai_analyzed_at` DATETIME NULL DEFAULT NULL AFTER `recommendation`",
            'last_search_at' => "ALTER TABLE `{$table}` ADD `last_search_at` DATETIME NULL DEFAULT NULL AFTER `ai_analyzed_at`",
            'last_search_by' => "ALTER TABLE `{$table}` ADD `last_search_by` INT(11) NULL DEFAULT NULL AFTER `last_search_at`",
            'public_agency' => "ALTER TABLE `{$table}` ADD `public_agency` VARCHAR(255) NULL DEFAULT NULL AFTER `description`",
            'public_body' => "ALTER TABLE `{$table}` ADD `public_body` VARCHAR(255) NULL DEFAULT NULL AFTER `description`",
            'notice_number' => "ALTER TABLE `{$table}` ADD `notice_number` VARCHAR(120) NULL DEFAULT NULL AFTER `edital_number`",
            'process_number' => "ALTER TABLE `{$table}` ADD `process_number` VARCHAR(120) NULL DEFAULT NULL AFTER `edital_number`",
            'modality' => "ALTER TABLE `{$table}` ADD `modality` VARCHAR(120) NULL DEFAULT NULL AFTER `process_number`",
            'object' => "ALTER TABLE `{$table}` ADD `object` LONGTEXT NULL AFTER `modality`",
            'object_description' => "ALTER TABLE `{$table}` ADD `object_description` LONGTEXT NULL AFTER `object`",
            'responsible_user_id' => "ALTER TABLE `{$table}` ADD `responsible_user_id` INT(11) NULL DEFAULT NULL AFTER `source_id`",
            'original_link' => "ALTER TABLE `{$table}` ADD `original_link` VARCHAR(255) NULL DEFAULT NULL AFTER `document_url`",
            'source_url' => "ALTER TABLE `{$table}` ADD `source_url` VARCHAR(255) NULL DEFAULT NULL AFTER `original_link`",
        );

        $this->ensureColumns($table, $columns);
    }

    private function ensureSourceColumns()
    {
        $table = $this->db->prefixTable('licitaia_sources');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $columns = array(
            'url' => "ALTER TABLE `{$table}` ADD `url` VARCHAR(255) NULL DEFAULT NULL AFTER `source_type`",
            'city' => "ALTER TABLE `{$table}` ADD `city` VARCHAR(120) NULL DEFAULT NULL AFTER `url`",
            'state' => "ALTER TABLE `{$table}` ADD `state` VARCHAR(2) NULL DEFAULT NULL AFTER `city`",
            'search_frequency' => "ALTER TABLE `{$table}` ADD `search_frequency` VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER `state`",
            'last_search_at' => "ALTER TABLE `{$table}` ADD `last_search_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`",
            'last_search_by' => "ALTER TABLE `{$table}` ADD `last_search_by` INT(11) NULL DEFAULT NULL AFTER `last_search_at`",
            'last_search_query' => "ALTER TABLE `{$table}` ADD `last_search_query` LONGTEXT NULL AFTER `last_search_by`",
        );

        $this->ensureColumns($table, $columns);
    }

    private function ensureKeywordColumns()
    {
        $table = $this->db->prefixTable('licitaia_keywords');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $columns = array(
            'keyword_type' => "ALTER TABLE `{$table}` ADD `keyword_type` VARCHAR(20) NOT NULL DEFAULT 'include' AFTER `category`",
            'weight' => "ALTER TABLE `{$table}` ADD `weight` INT(11) NOT NULL DEFAULT 0 AFTER `keyword_type`",
        );

        $this->ensureColumns($table, $columns);
    }

    private function ensureChecklistColumns()
    {
        $table = $this->db->prefixTable('licitaia_checklist_items');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $columns = array(
            'category' => "ALTER TABLE `{$table}` ADD `category` VARCHAR(120) NULL DEFAULT NULL AFTER `item_name`",
            'description' => "ALTER TABLE `{$table}` ADD `description` LONGTEXT NULL AFTER `category`",
            'active' => "ALTER TABLE `{$table}` ADD `active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`",
            'sort' => "ALTER TABLE `{$table}` ADD `sort` INT(11) NOT NULL DEFAULT 0 AFTER `active`",
        );

        $this->ensureColumns($table, $columns);
    }

    private function ensureDocumentsTable($charset, $collation)
    {
        $table = $this->db->prefixTable('licitaia_documents');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'opportunity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'file_name' => array('type' => 'VARCHAR', 'constraint' => 255),
            'original_file_name' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
            'file_path' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
            'mime_type' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
            'file_size' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0),
            'source_url' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
            'extracted_text' => array('type' => 'LONGTEXT', 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'uploaded'),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('opportunity_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_by');
        $this->forge->createTable('licitaia_documents', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => $charset,
            'COLLATE' => $collation,
        ));
    }

    private function ensureOpportunityChecklistTable($charset, $collation)
    {
        $table = $this->db->prefixTable('licitaia_opportunity_checklist');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'opportunity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'checklist_item_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'item_name_snapshot' => array('type' => 'VARCHAR', 'constraint' => 255),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'),
            'notes' => array('type' => 'TEXT', 'null' => true),
            'document_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'completed_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'completed_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('opportunity_id');
        $this->forge->addKey('checklist_item_id');
        $this->forge->addKey('status');
        $this->forge->addKey('document_id');
        $this->forge->addKey(array('opportunity_id', 'checklist_item_id'), false, true, 'licitaia_opp_checklist_unique');
        $this->forge->createTable('licitaia_opportunity_checklist', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => $charset,
            'COLLATE' => $collation,
        ));
    }

    private function ensureAiLogsTable($charset, $collation)
    {
        $table = $this->db->prefixTable('licitaia_ai_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'opportunity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'document_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'provider' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'default' => null),
            'model_name' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
            'request_type' => array('type' => 'VARCHAR', 'constraint' => 60, 'default' => 'analysis'),
            'request_json' => array('type' => 'LONGTEXT', 'null' => true),
            'response_json' => array('type' => 'LONGTEXT', 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'),
            'error_message' => array('type' => 'TEXT', 'null' => true),
            'tokens_input' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'tokens_output' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('opportunity_id');
        $this->forge->addKey('document_id');
        $this->forge->addKey('status');
        $this->forge->addKey('request_type');
        $this->forge->createTable('licitaia_ai_logs', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => $charset,
            'COLLATE' => $collation,
        ));
    }

    private function ensureSearchLogsTable($charset, $collation)
    {
        $table = $this->db->prefixTable('licitaia_search_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'source_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'opportunity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'query_text' => array('type' => 'LONGTEXT', 'null' => true),
            'filters_json' => array('type' => 'LONGTEXT', 'null' => true),
            'results_count' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'completed'),
            'response_json' => array('type' => 'LONGTEXT', 'null' => true),
            'error_message' => array('type' => 'TEXT', 'null' => true),
            'started_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'finished_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('source_id');
        $this->forge->addKey('opportunity_id');
        $this->forge->addKey('status');
        $this->forge->addKey('started_at');
        $this->forge->createTable('licitaia_search_logs', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => $charset,
            'COLLATE' => $collation,
        ));
    }

    private function ensureColumns($table, array $columns)
    {
        $existing_fields = array();
        try {
            $existing_fields = $this->db->getFieldNames($table);
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA] Unable to inspect columns for ' . $table . ': ' . $e->getMessage());
        }

        $existing_fields = array_change_key_case(array_flip((array) $existing_fields), CASE_LOWER);

        foreach ($columns as $column => $sql) {
            try {
                if (!isset($existing_fields[strtolower((string) $column)])) {
                    $this->db->query($sql);
                }
            } catch (\Throwable $e) {
                if (stripos($e->getMessage(), 'Duplicate column name') === false) {
                    log_message('error', '[LicitaIA] Column fallback error for ' . $table . '.' . $column . ': ' . $e->getMessage());
                }
            }
        }
    }
}
