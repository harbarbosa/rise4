<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaudoStatusHistory extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('laudo_status_history');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'from_status_code' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'to_status_code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'comment' => array('type' => 'LONGTEXT', 'null' => true),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'web'),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('from_status_code');
        $this->forge->addKey('to_status_code');
        $this->forge->addKey('user_id');
        $this->forge->createTable('laudo_status_history', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    public function down()
    {
        $this->forge->dropTable('laudo_status_history', true);
    }
}
