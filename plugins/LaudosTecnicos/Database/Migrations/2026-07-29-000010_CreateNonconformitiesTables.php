<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNonconformitiesTables extends Migration
{
    public function up()
    {
        $this->createNonconformitiesTable();
        $this->createRiskMatrixTable();
        $this->createActionPlansTable();
    }

    public function down()
    {
        $tables = array(
            'laudo_action_plans',
            'laudo_risk_matrix',
            'laudo_nonconformities',
        );

        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createNonconformitiesTable()
    {
        $table = $this->db->prefixTable('laudo_nonconformities');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'code' => array('type' => 'VARCHAR', 'constraint' => 120),
            'title' => array('type' => 'VARCHAR', 'constraint' => 190),
            'description' => array('type' => 'TEXT', 'null' => true),
            'client_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'inspection_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'location_text' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'sector' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'equipment_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'checklist_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'checklist_item_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'norm_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'evidence_json' => array('type' => 'LONGTEXT', 'null' => true),
            'photos_json' => array('type' => 'LONGTEXT', 'null' => true),
            'classification' => array('type' => 'VARCHAR', 'constraint' => 60, 'default' => 'observacao'),
            'probability' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'impact' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'risk_level' => array('type' => 'VARCHAR', 'constraint' => 60, 'null' => true),
            'risk_color' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true),
            'recommendation' => array('type' => 'TEXT', 'null' => true),
            'suggested_deadline' => array('type' => 'DATE', 'null' => true),
            'responsible_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'validator_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'open'),
            'identified_at' => array('type' => 'DATETIME', 'null' => true),
            'corrected_at' => array('type' => 'DATETIME', 'null' => true),
            'correction_evidence_json' => array('type' => 'LONGTEXT', 'null' => true),
            'correction_comments' => array('type' => 'TEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->addKey('client_id');
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('inspection_id');
        $this->forge->addKey('status');
        $this->forge->addKey('classification');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_nonconformities', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createRiskMatrixTable()
    {
        $table = $this->db->prefixTable('laudo_risk_matrix');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'probability' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'impact' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'result' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'classification' => array('type' => 'VARCHAR', 'constraint' => 60, 'default' => 'observacao'),
            'color' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true),
            'suggested_deadline_days' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'is_default' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('category_id');
        $this->forge->addKey('probability');
        $this->forge->addKey('impact');
        $this->forge->addKey('classification');
        $this->forge->addKey('is_default');
        $this->forge->addKey('is_active');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_risk_matrix', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createActionPlansTable()
    {
        $table = $this->db->prefixTable('laudo_action_plans');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'nonconformity_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'code' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'action' => array('type' => 'TEXT', 'null' => true),
            'motive' => array('type' => 'TEXT', 'null' => true),
            'location_text' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'responsible_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'company_name' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'method' => array('type' => 'TEXT', 'null' => true),
            'deadline' => array('type' => 'DATE', 'null' => true),
            'estimated_cost' => array('type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true),
            'priority' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'medium'),
            'status' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'draft'),
            'evidence_json' => array('type' => 'LONGTEXT', 'null' => true),
            'completion_date' => array('type' => 'DATETIME', 'null' => true),
            'validator_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'auto_create_task' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'task_sync_enabled' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'what_field' => array('type' => 'TEXT', 'null' => true),
            'why_field' => array('type' => 'TEXT', 'null' => true),
            'where_field' => array('type' => 'TEXT', 'null' => true),
            'when_field' => array('type' => 'TEXT', 'null' => true),
            'who_field' => array('type' => 'TEXT', 'null' => true),
            'how_field' => array('type' => 'TEXT', 'null' => true),
            'how_much_field' => array('type' => 'TEXT', 'null' => true),
            'task_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'task_sync_hash' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true),
            'task_sync_source' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true),
            'task_synced_at' => array('type' => 'DATETIME', 'null' => true),
            'last_sync_payload_json' => array('type' => 'LONGTEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('nonconformity_id');
        $this->forge->addKey('task_id');
        $this->forge->addKey('status');
        $this->forge->addKey('priority');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_action_plans', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }
}
