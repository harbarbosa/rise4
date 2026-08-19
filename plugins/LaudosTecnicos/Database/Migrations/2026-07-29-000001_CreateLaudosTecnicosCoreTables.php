<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaudosTecnicosCoreTables extends Migration
{
    public function up()
    {
        $this->createCategoriesTable();
        $this->createTypesTable();
        $this->createLaudosTable();
        $this->createAuditLogsTable();
    }

    public function down()
    {
        $tables = array(
            'laudo_audit_logs',
            'laudos',
            'laudo_types',
            'laudo_categories',
        );

        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createCategoriesTable()
    {
        $table = $this->db->prefixTable('laudo_categories');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'description' => array('type' => 'TEXT', 'null' => true),
            'color' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true),
            'icon' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->addKey('is_active');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_categories', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createTypesTable()
    {
        $table = $this->db->prefixTable('laudo_types');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'description' => array('type' => 'TEXT', 'null' => true),
            'prefix' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true),
            'default_template_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'validity_days' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'require_technical_responsible' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'require_review' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'require_approval' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'require_signature' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'require_inspection' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'require_calibrated_equipment' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'allow_mobile' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->addKey('category_id');
        $this->forge->addKey('is_active');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_types', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createLaudosTable()
    {
        $table = $this->db->prefixTable('laudos');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'number' => array('type' => 'VARCHAR', 'constraint' => 100),
            'custom_code' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'revision' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => '00'),
            'title' => array('type' => 'VARCHAR', 'constraint' => 190),
            'type_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'client_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'project_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'contact_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'responsible_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'reviewer_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'approver_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 50, 'default' => 'draft'),
            'priority' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'),
            'request_date' => array('type' => 'DATE', 'null' => true),
            'inspection_date' => array('type' => 'DATE', 'null' => true),
            'issue_date' => array('type' => 'DATE', 'null' => true),
            'validity_date' => array('type' => 'DATE', 'null' => true),
            'objective' => array('type' => 'TEXT', 'null' => true),
            'scope' => array('type' => 'TEXT', 'null' => true),
            'methodology' => array('type' => 'TEXT', 'null' => true),
            'results' => array('type' => 'TEXT', 'null' => true),
            'diagnosis' => array('type' => 'TEXT', 'null' => true),
            'conclusion' => array('type' => 'TEXT', 'null' => true),
            'recommendations' => array('type' => 'TEXT', 'null' => true),
            'internal_notes' => array('type' => 'TEXT', 'null' => true),
            'is_template_based' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('number', false, true);
        $this->forge->addKey('status');
        $this->forge->addKey('type_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('client_id');
        $this->forge->addKey('project_id');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudos', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createAuditLogsTable()
    {
        $table = $this->db->prefixTable('laudo_audit_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'entity_type' => array('type' => 'VARCHAR', 'constraint' => 100),
            'entity_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
            'action' => array('type' => 'VARCHAR', 'constraint' => 120),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'web'),
            'old_values_json' => array('type' => 'LONGTEXT', 'null' => true),
            'new_values_json' => array('type' => 'LONGTEXT', 'null' => true),
            'description' => array('type' => 'TEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('entity_type');
        $this->forge->addKey('entity_id');
        $this->forge->addKey('action');
        $this->forge->addKey('user_id');
        $this->forge->createTable('laudo_audit_logs', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }
}
