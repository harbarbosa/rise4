<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateFotovoltaicoProposalsForVersioning extends Migration
{
    public function up()
    {
        $proposals_table = $this->db->prefixTable('fv_proposals');
        if ($this->db->tableExists($proposals_table)) {
            $columns = array(
                'contact_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'default' => null,
                    'after' => 'lead_id'
                ),
                'distributor_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'default' => null,
                    'after' => 'project_id'
                ),
                'consumer_unit' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 190,
                    'null' => true,
                    'default' => null,
                    'after' => 'title'
                ),
                'consumption_avg' => array(
                    'type' => 'DECIMAL',
                    'constraint' => '16,3',
                    'default' => 0,
                    'after' => 'consumer_unit'
                ),
                'current_version' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                    'after' => 'consumption_avg'
                ),
                'wizard_step' => array(
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => true,
                    'default' => null,
                    'after' => 'current_version'
                ),
                'wizard_data_json' => array(
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'after' => 'wizard_step'
                ),
            );

            foreach ($columns as $column_name => $definition) {
                if (!$this->db->fieldExists($column_name, $proposals_table)) {
                    $this->forge->addColumn('fv_proposals', array($column_name => $definition));
                }
            }

            $this->db->query("UPDATE $proposals_table SET current_version=1 WHERE current_version IS NULL OR current_version=0");
        }
    }

    public function down()
    {
        $proposals_table = $this->db->prefixTable('fv_proposals');
        if ($this->db->tableExists($proposals_table)) {
            foreach (array('wizard_data_json', 'wizard_step', 'current_version', 'consumption_avg', 'consumer_unit', 'distributor_id', 'contact_id') as $column_name) {
                if ($this->db->fieldExists($column_name, $proposals_table)) {
                    $this->forge->dropColumn('fv_proposals', $column_name);
                }
            }
        }
    }
}
