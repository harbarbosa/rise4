<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPhotoToPontoRhRecords extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_records');
        if (!$this->db->tableExists($table)) {
            return;
        }

        if ($this->db->fieldExists('photo', $table)) {
            return;
        }

        $this->forge->addColumn('pontorh_records', array(
            'photo' => array(
                'type' => 'LONGTEXT',
                'null' => true,
                'default' => null,
                'after' => 'notes',
            ),
        ));
    }

    public function down()
    {
        $table = $this->db->prefixTable('pontorh_records');
        if (!$this->db->tableExists($table)) {
            return;
        }

        if ($this->db->fieldExists('photo', $table)) {
            $this->forge->dropColumn('pontorh_records', 'photo');
        }
    }
}
