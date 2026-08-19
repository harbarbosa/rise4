<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaudoStatusTransitions extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('laudo_status_transitions');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'from_status_code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'to_status_code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'allowed_roles_json' => array('type' => 'LONGTEXT', 'null' => true),
            'required_permissions_json' => array('type' => 'LONGTEXT', 'null' => true),
            'require_comment' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'required_validations_json' => array('type' => 'LONGTEXT', 'null' => true),
            'send_notification' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'auto_create_task' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'task_title' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'task_description' => array('type' => 'LONGTEXT', 'null' => true),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('from_status_code');
        $this->forge->addKey('to_status_code');
        $this->forge->addKey('sort');
        $this->forge->addKey('is_active');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_status_transitions', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    public function down()
    {
        $this->forge->dropTable('laudo_status_transitions', true);
    }
}
