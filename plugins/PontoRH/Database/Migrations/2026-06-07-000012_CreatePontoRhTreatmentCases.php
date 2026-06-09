<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePontoRhTreatmentCases extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_treatment_cases');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'team_member_id' => array('type' => 'INT', 'constraint' => 11),
            'user_id' => array('type' => 'INT', 'constraint' => 11),
            'work_date' => array('type' => 'DATE'),
            'project_name' => array('type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'default' => null),
            'record_count' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'status' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'pending'),
            'pending_type' => array('type' => 'VARCHAR', 'constraint' => 60, 'default' => 'incomplete'),
            'classification_json' => array('type' => 'LONGTEXT', 'null' => true),
            'final_json' => array('type' => 'LONGTEXT', 'null' => true),
            'diagnostics_json' => array('type' => 'LONGTEXT', 'null' => true),
            'last_updated_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'last_updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'closed_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'created_by' => array('type' => 'INT', 'constraint' => 11),
            'created_at' => array('type' => 'DATETIME'),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'hash' => array('type' => 'CHAR', 'constraint' => 64),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('team_member_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('work_date');
        $this->forge->addKey('status');
        $this->forge->addKey('pending_type');
        $this->forge->addKey('hash', false, true);
        $this->forge->addForeignKey('team_member_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('last_updated_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pontorh_treatment_cases', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    public function down()
    {
        $this->forge->dropTable('pontorh_treatment_cases', true);
    }
}
