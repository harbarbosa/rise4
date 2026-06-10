<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhLocationAssignments extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_location_assignments');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'location_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'week_start' => array('type' => 'DATE'),
            'week_end' => array('type' => 'DATE'),
            'active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'notes' => array('type' => 'TEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('location_id');
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('week_start');
        $this->forge->addKey('week_end');
        $this->forge->addKey('active');
        $this->forge->addForeignKey('location_id', 'pontorh_locations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_location_assignments', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_location_assignments', true);
    }
}
