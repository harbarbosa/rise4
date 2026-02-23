<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Snapshot de irradiação por versão de proposta.
 */
class CreateFvProjectIrradiationSnapshots extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_project_irradiation_snapshots');
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
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true
            ],
            'lat' => [
                'type' => 'DECIMAL',
                'constraint' => '10,6',
                'null' => true
            ],
            'lon' => [
                'type' => 'DECIMAL',
                'constraint' => '10,6',
                'null' => true
            ],
            'monthly_json' => [
                'type' => 'LONGTEXT',
                'null' => true
            ],
            'annual_value' => [
                'type' => 'DECIMAL',
                'constraint' => '12,4',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('project_version_id');
        $this->forge->createTable('fv_project_irradiation_snapshots', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_project_irradiation_snapshots', true);
    }
}
