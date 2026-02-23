<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para criar a tabela de projetos fotovoltaicos.
 */
class CreateFvProjects extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_projects');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'client_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'sent', 'won', 'lost'],
                'default' => 'draft'
            ],
            'city' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true
            ],
            'state' => [
                'type' => 'VARCHAR',
                'constraint' => 2,
                'null' => true
            ],
            'lat' => [
                'type' => 'DECIMAL',
                'constraint' => '10,7',
                'null' => true
            ],
            'lon' => [
                'type' => 'DECIMAL',
                'constraint' => '10,7',
                'null' => true
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ]
        ]);

        $this->forge->addField('`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->addKey('id', true);
        $this->forge->addKey('client_id');
        $this->forge->addKey('status');

        $this->forge->createTable('fv_projects', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_projects', true);
    }
}
