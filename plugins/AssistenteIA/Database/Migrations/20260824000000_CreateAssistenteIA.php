<?php

namespace AssistenteIA\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssistenteIA extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable($this->db->prefixTable('ai_conversations'), true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'conversation_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => 20],
            'content' => ['type' => 'LONGTEXT'],
            'tool_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'metadata' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['conversation_id', 'user_id']);
        $this->forge->createTable($this->db->prefixTable('ai_messages'), true);
    }

    public function down()
    {
        $this->forge->dropTable($this->db->prefixTable('ai_messages'), true);
        $this->forge->dropTable($this->db->prefixTable('ai_conversations'), true);
    }
}
