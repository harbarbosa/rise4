<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dados do assistente de proposta.
 */
class CreateFvAssistantData extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_project_assistant_data');
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
            'cep' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true
            ],
            'consumption_kwh_month' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('project_version_id');
        $this->forge->createTable('fv_project_assistant_data', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8',
            'COLLATE' => 'utf8_general_ci'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_project_assistant_data', true);
    }
}
