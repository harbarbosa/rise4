<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhRecords extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_records');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11),
            'user_id' => array('type' => 'INT', 'constraint' => 11),
            'work_schedule_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null),
            'device_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null),
            'location_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null),
            'date' => array('type' => 'DATE'),
            'punch_time' => array('type' => 'DATETIME'),
            'punch_type' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'in'),
            'latitude' => array('type' => 'DECIMAL', 'constraint' => '10,8', 'default' => '0.00000000'),
            'longitude' => array('type' => 'DECIMAL', 'constraint' => '11,8', 'default' => '0.00000000'),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'default' => ''),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'manual'),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'),
            'hash' => array('type' => 'CHAR', 'constraint' => 64),
            'work_date' => array('type' => 'DATE', 'null' => true, 'default' => null),
            'check_in' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'check_out' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'break_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'minutes_worked' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'shift_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null),
            'notes' => array('type' => 'TEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('punch_time');
        $this->forge->addKey('date');
        $this->forge->addKey('status');
        $this->forge->addKey('hash', false, true);
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('work_schedule_id', 'pontorh_work_schedules', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('device_id', 'pontorh_devices', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('location_id', 'pontorh_locations', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_records', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_records', true);
    }
}
