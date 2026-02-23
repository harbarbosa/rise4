<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para resultados energéticos e financeiros.
 */
class CreateFvEnergyResults extends Migration
{
    public function up()
    {
        $table12 = $this->db->prefixTable('fv_energy_results_12m');
        if (!$this->db->tableExists($table12)) {
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
                'month' => [
                    'type' => 'TINYINT',
                    'constraint' => 2,
                    'null' => false
                ],
                'irradiation_kwh_kwp' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,4',
                    'default' => 0
                ],
                'energy_generated_kwh' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,4',
                    'default' => 0
                ],
                'energy_offset_kwh' => [
                    'type' => 'DECIMAL',
                    'constraint' => '14,4',
                    'default' => 0
                ],
                'savings_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '16,4',
                    'default' => 0
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('project_version_id');
            $this->forge->addKey('month');
            $this->forge->createTable('fv_energy_results_12m', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4'
            ]);
        }

        $table25 = $this->db->prefixTable('fv_energy_results_25y');
        if (!$this->db->tableExists($table25)) {
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
                'year' => [
                    'type' => 'TINYINT',
                    'constraint' => 2,
                    'null' => false
                ],
                'energy_generated_kwh' => [
                    'type' => 'DECIMAL',
                    'constraint' => '16,4',
                    'default' => 0
                ],
                'tariff_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '16,6',
                    'default' => 0
                ],
                'annual_savings' => [
                    'type' => 'DECIMAL',
                    'constraint' => '16,4',
                    'default' => 0
                ],
                'cumulative_savings' => [
                    'type' => 'DECIMAL',
                    'constraint' => '18,4',
                    'default' => 0
                ],
                'degradation_factor' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,6',
                    'default' => 1
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('project_version_id');
            $this->forge->addKey('year');
            $this->forge->createTable('fv_energy_results_25y', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4'
            ]);
        }

        $tableFin = $this->db->prefixTable('fv_financial_results');
        if (!$this->db->tableExists($tableFin)) {
            $this->forge->addField([
                'project_version_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false
                ],
                'investment_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '16,2',
                    'default' => 0
                ],
                'annual_savings_year1' => [
                    'type' => 'DECIMAL',
                    'constraint' => '16,2',
                    'default' => 0
                ],
                'payback_years' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0
                ],
                'payback_months' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0
                ],
                'irr_percent' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,4',
                    'default' => 0
                ],
                'npv_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '16,2',
                    'default' => 0
                ],
                'economia_media_mensal_lei_14300' => [
                    'type' => 'DECIMAL',
                    'constraint' => '16,2',
                    'default' => 0
                ],
                'payback_ano_lei_14300' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,4',
                    'default' => 0
                ],
                'total_economizado_25_anos_lei_14300' => [
                    'type' => 'DECIMAL',
                    'constraint' => '18,2',
                    'default' => 0
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ]);
            $this->forge->addKey('project_version_id', true);
            $this->forge->createTable('fv_financial_results', true, [
                'ENGINE' => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4'
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('fv_energy_results_12m', true);
        $this->forge->dropTable('fv_energy_results_25y', true);
        $this->forge->dropTable('fv_financial_results', true);
    }
}
