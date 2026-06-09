<?php

namespace PontoRH\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPlatformAndAppVersionToPontoRhDevices extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('pontorh_devices');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = array();
        if (!$this->db->fieldExists('platform', $table)) {
            $fields['platform'] = array(
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'default' => null,
                'after' => 'source',
            );
        }

        if (!$this->db->fieldExists('app_version', $table)) {
            $fields['app_version'] = array(
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'default' => null,
                'after' => 'platform',
            );
        }

        if ($fields) {
            $this->forge->addColumn('pontorh_devices', $fields);
        }
    }

    public function down()
    {
        $table = $this->db->prefixTable('pontorh_devices');
        if (!$this->db->tableExists($table)) {
            return;
        }

        if ($this->db->fieldExists('app_version', $table)) {
            $this->forge->dropColumn('pontorh_devices', 'app_version');
        }
        if ($this->db->fieldExists('platform', $table)) {
            $this->forge->dropColumn('pontorh_devices', 'platform');
        }
    }
}
