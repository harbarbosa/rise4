<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhDevices extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_devices');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'serial_number' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null),
            'token' => array('type' => 'CHAR', 'constraint' => 64),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'default' => ''),
            'latitude' => array('type' => 'DECIMAL', 'constraint' => '10,8', 'default' => '0.00000000'),
            'longitude' => array('type' => 'DECIMAL', 'constraint' => '11,8', 'default' => '0.00000000'),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'manual'),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'),
            'hash' => array('type' => 'CHAR', 'constraint' => 64),
            'last_seen_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('hash', false, true);
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_devices', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_devices', true);
    }
}
