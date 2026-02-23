<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Armazena snapshot de tarifa por versÃ£o de proposta.
 */
class CreateFvProjectTariffSnapshots extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_project_tariff_snapshots');
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
            'utility_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],
            'tariff_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],
            'snapshot_json' => [
                'type' => 'LONGTEXT',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('project_version_id');
        $this->forge->addKey('utility_id');
        $this->forge->createTable('fv_project_tariff_snapshots', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('fv_project_tariff_snapshots', true);
    }
}
