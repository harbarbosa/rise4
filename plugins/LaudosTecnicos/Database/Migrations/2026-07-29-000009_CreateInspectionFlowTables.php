<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInspectionFlowTables extends Migration
{
    public function up()
    {
        $this->createInspectionsTable();
        $this->createInspectionCheckinsTable();
        $this->createInspectionPhotosTable();
    }

    public function down()
    {
        foreach (array('laudo_inspection_photos', 'laudo_inspection_checkins', 'laudo_inspections') as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createInspectionsTable()
    {
        $table = $this->db->prefixTable('laudo_inspections');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'code' => array('type' => 'VARCHAR', 'constraint' => 120),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'client_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'unit_name' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'location_name' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'inspection_type' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'inspection_date' => array('type' => 'DATE', 'null' => true),
            'start_time' => array('type' => 'TIME', 'null' => true),
            'end_time' => array('type' => 'TIME', 'null' => true),
            'duration_minutes' => array('type' => 'INT', 'constraint' => 11, 'default' => 60),
            'responsible_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'team_json' => array('type' => 'LONGTEXT', 'null' => true),
            'vehicle' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'equipments_json' => array('type' => 'LONGTEXT', 'null' => true),
            'observations' => array('type' => 'TEXT', 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'planned'),
            'progress_percent' => array('type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0),
            'checkin_at' => array('type' => 'DATETIME', 'null' => true),
            'checkout_at' => array('type' => 'DATETIME', 'null' => true),
            'started_at' => array('type' => 'DATETIME', 'null' => true),
            'paused_at' => array('type' => 'DATETIME', 'null' => true),
            'completed_at' => array('type' => 'DATETIME', 'null' => true),
            'is_improductive' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'improductive_reason' => array('type' => 'TEXT', 'null' => true),
            'improductive_evidence_json' => array('type' => 'LONGTEXT', 'null' => true),
            'client_contact_name' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'client_signature_file' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'suggested_new_date' => array('type' => 'DATE', 'null' => true),
            'costs_json' => array('type' => 'LONGTEXT', 'null' => true),
            'comments' => array('type' => 'TEXT', 'null' => true),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'web'),
            'address' => array('type' => 'TEXT', 'null' => true),
            'latitude' => array('type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true),
            'longitude' => array('type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('client_id');
        $this->forge->addKey('responsible_id');
        $this->forge->addKey('inspection_date');
        $this->forge->addKey('status');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_inspections', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    private function createInspectionCheckinsTable()
    {
        $table = $this->db->prefixTable('laudo_inspection_checkins');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'inspection_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'check_type' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'checkin'),
            'checked_at' => array('type' => 'DATETIME', 'null' => true),
            'latitude' => array('type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true),
            'longitude' => array('type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true),
            'accuracy' => array('type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'device' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'distance_meters' => array('type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true),
            'observation' => array('type' => 'TEXT', 'null' => true),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'web'),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('inspection_id');
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('check_type');
        $this->forge->addKey('user_id');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_inspection_checkins', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }

    private function createInspectionPhotosTable()
    {
        $table = $this->db->prefixTable('laudo_inspection_photos');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'inspection_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'file_path' => array('type' => 'VARCHAR', 'constraint' => 255),
            'thumbnail_path' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'original_file_name' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'caption' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'photo_number' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'taken_at' => array('type' => 'DATETIME', 'null' => true),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'latitude' => array('type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true),
            'longitude' => array('type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true),
            'location_text' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'sector' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'equipment_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'checklist_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'measurement_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'nonconformity_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'observation' => array('type' => 'TEXT', 'null' => true),
            'hash_value' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true),
            'is_cover' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'is_before' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'is_after' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'metadata_json' => array('type' => 'LONGTEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('inspection_id');
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('equipment_id');
        $this->forge->addKey('checklist_id');
        $this->forge->addKey('measurement_id');
        $this->forge->addKey('hash_value');
        $this->forge->addKey('sort');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_inspection_photos', true, array('ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'));
    }
}
