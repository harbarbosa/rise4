<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para perfis regulatórios e snapshots.
 */
class CreateFvRegulatoryProfiles extends Migration
{
    public function up()
    {
        $profiles = $this->db->prefixTable('fv_regulatory_profiles');
        if (!$this->db->tableExists($profiles)) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'rules_json' => [
                    'type' => 'LONGTEXT',
                    'null' => true
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('is_active');
            $this->forge->createTable('fv_regulatory_profiles', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4'
            ]);
        }

        $snapshots = $this->db->prefixTable('fv_project_regulatory_snapshots');
        if (!$this->db->tableExists($snapshots)) {
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
                'profile_id' => [
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
            $this->forge->createTable('fv_project_regulatory_snapshots', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4'
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('fv_regulatory_profiles', true);
        $this->forge->dropTable('fv_project_regulatory_snapshots', true);
    }
}
