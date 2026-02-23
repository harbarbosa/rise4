<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ajusta tarifas para componentes TE/TUSD/bandeira.
 */
class UpdateFvTariffsComponents extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('fv_tariffs');
        if (!$this->db->tableExists($table)) {
            return;
        }

        if (!$this->db->fieldExists('te_value', $table)) {
            $this->forge->addColumn('fv_tariffs', [
                'te_value' => ['type' => 'DECIMAL', 'constraint' => '16,6', 'default' => 0]
            ]);
            if ($this->db->fieldExists('te', $table)) {
                $this->db->query("UPDATE {$table} SET te_value = te");
            }
        }

        if (!$this->db->fieldExists('tusd_value', $table)) {
            $this->forge->addColumn('fv_tariffs', [
                'tusd_value' => ['type' => 'DECIMAL', 'constraint' => '16,6', 'default' => 0]
            ]);
            if ($this->db->fieldExists('tusd', $table)) {
                $this->db->query("UPDATE {$table} SET tusd_value = tusd");
            }
        }

        if (!$this->db->fieldExists('flags_value', $table)) {
            $this->forge->addColumn('fv_tariffs', [
                'flags_value' => ['type' => 'DECIMAL', 'constraint' => '16,6', 'default' => 0]
            ]);
        }
    }

    public function down()
    {
        // sem rollback
    }
}
