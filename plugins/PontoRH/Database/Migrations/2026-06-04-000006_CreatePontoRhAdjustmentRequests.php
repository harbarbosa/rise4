<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhAdjustmentRequests extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_adjustment_requests');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11),
            'user_id' => array('type' => 'INT', 'constraint' => 11),
            'record_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null),
            'request_date' => array('type' => 'DATE'),
            'requested_time' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'adjustment_type' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'manual'),
            'requested_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'reason' => array('type' => 'TEXT', 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'),
            'reviewed_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'reviewed_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'default' => ''),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'manual'),
            'hash' => array('type' => 'CHAR', 'constraint' => 64),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('request_date');
        $this->forge->addKey('status');
        $this->forge->addKey('hash', false, true);
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('record_id', 'pontorh_records', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('reviewed_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_adjustment_requests', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_adjustment_requests', true);
    }
}
