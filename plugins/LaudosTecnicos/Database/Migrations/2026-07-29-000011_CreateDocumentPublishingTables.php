<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentPublishingTables extends Migration
{
    public function up()
    {
        $this->createDocumentVersionsTable();
        $this->createDocumentSharesTable();
        $this->createDocumentAccessLogsTable();
        $this->createDocumentFeedbacksTable();
    }

    public function down()
    {
        foreach (array(
            'laudo_document_feedbacks',
            'laudo_document_access_logs',
            'laudo_document_shares',
            'laudo_document_versions',
        ) as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createDocumentVersionsTable()
    {
        $table = $this->db->prefixTable('laudo_document_versions');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'variant' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'full'),
            'document_code' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'public_key' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'document_hash' => array('type' => 'VARCHAR', 'constraint' => 128, 'null' => true),
            'html_snapshot' => array('type' => 'LONGTEXT', 'null' => true),
            'pdf_path' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'pdf_file_name' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'issued_at' => array('type' => 'DATETIME', 'null' => true),
            'issued_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'status_snapshot' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'revision_snapshot' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true),
            'visibility' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'internal'),
            'qr_payload' => array('type' => 'TEXT', 'null' => true),
            'share_token' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'share_expires_at' => array('type' => 'DATETIME', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('variant');
        $this->forge->addKey('public_key');
        $this->forge->addKey('share_token');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_document_versions', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    private function createDocumentSharesTable()
    {
        $table = $this->db->prefixTable('laudo_document_shares');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'document_version_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'share_token' => array('type' => 'VARCHAR', 'constraint' => 80),
            'password_hash' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'visitor_label' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'expires_at' => array('type' => 'DATETIME', 'null' => true),
            'max_accesses' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'access_count' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'allow_download' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'allow_comments' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'require_visitor_id' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'revoked_at' => array('type' => 'DATETIME', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('document_version_id');
        $this->forge->addKey('share_token', false, true);
        $this->forge->addKey('is_active');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_document_shares', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    private function createDocumentAccessLogsTable()
    {
        $table = $this->db->prefixTable('laudo_document_access_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'document_version_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'share_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'visitor_label' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'visitor_id' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'event_type' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'view'),
            'document_variant' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'full'),
            'downloaded' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'commented' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true),
            'user_agent' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('document_version_id');
        $this->forge->addKey('share_id');
        $this->forge->addKey('event_type');
        $this->forge->addKey('downloaded');
        $this->forge->createTable('laudo_document_access_logs', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    private function createDocumentFeedbacksTable()
    {
        $table = $this->db->prefixTable('laudo_document_feedbacks');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'document_version_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'share_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'action' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'comment'),
            'comment' => array('type' => 'TEXT', 'null' => true),
            'evidence_json' => array('type' => 'LONGTEXT', 'null' => true),
            'visitor_label' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'visitor_email' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'accepted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'rejected' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true),
            'user_agent' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('document_version_id');
        $this->forge->addKey('share_id');
        $this->forge->addKey('action');
        $this->forge->createTable('laudo_document_feedbacks', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }
}
