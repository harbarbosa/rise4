<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhTreatmentHistory extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_treatment_history');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'treatment_case_id' => array('type' => 'INT', 'constraint' => 11),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11),
            'user_id' => array('type' => 'INT', 'constraint' => 11),
            'action' => array('type' => 'VARCHAR', 'constraint' => 60),
            'old_value_json' => array('type' => 'LONGTEXT', 'null' => true),
            'new_value_json' => array('type' => 'LONGTEXT', 'null' => true),
            'justification' => array('type' => 'TEXT', 'null' => true),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'default' => ''),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'manual'),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'logged'),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'hash' => array('type' => 'CHAR', 'constraint' => 64),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('treatment_case_id');
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('status');
        $this->forge->addKey('hash', false, true);
        $this->forge->addForeignKey('treatment_case_id', 'pontorh_treatment_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_treatment_history', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_treatment_history', true);
    }
}
