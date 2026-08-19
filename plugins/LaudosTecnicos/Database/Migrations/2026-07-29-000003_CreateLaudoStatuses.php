<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaudoStatuses extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('laudo_statuses');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'color' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true),
            'icon' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'status_initial' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'status_final' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'status_cancellation' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'allow_edit' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'allow_delete' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'allow_issue' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'require_comment' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->addKey('sort');
        $this->forge->addKey('is_active');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_statuses', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    public function down()
    {
        $this->forge->dropTable('laudo_statuses', true);
    }
}
