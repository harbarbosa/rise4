<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabela de PDFs de proposta FV.
 */
class CreateFvProposals extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_proposals');
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
            'project_version_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false
            ],
            'pdf_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ],
            'total_value' => [
                'type' => 'DECIMAL',
                'constraint' => '16,2',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('project_version_id');
        $this->forge->createTable('fv_proposals', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8',
            'COLLATE' => 'utf8_general_ci'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_proposals', true);
    }
}
