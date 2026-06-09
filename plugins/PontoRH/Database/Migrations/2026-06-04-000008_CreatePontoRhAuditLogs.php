<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhAuditLogs extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_audit_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'entity_type' => array('type' => 'VARCHAR', 'constraint' => 80),
            'entity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'action' => array('type' => 'VARCHAR', 'constraint' => 40),
            'description' => array('type' => 'TEXT', 'null' => true),
            'payload_json' => array('type' => 'LONGTEXT', 'null' => true),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'default' => ''),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'system'),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'logged'),
            'hash' => array('type' => 'CHAR', 'constraint' => 64),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('entity_type');
        $this->forge->addKey('status');
        $this->forge->addKey('hash', false, true);
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_audit_logs', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_audit_logs', true);
    }
}
