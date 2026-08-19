<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTechnicalLibraryTables extends Migration
{
    public function up()
    {
        $this->createChecklistsTable();
        $this->createChecklistResponsesTable();
        $this->createMeasurementTypesTable();
        $this->createMeasurementsTable();
        $this->createEquipmentsTable();
        $this->createNormsTable();
        $this->createNormLinksTable();
    }

    public function down()
    {
        $tables = array(
            'laudo_norm_links',
            'laudo_norms',
            'laudo_equipments',
            'laudo_measurements',
            'laudo_measurement_types',
            'laudo_checklist_responses',
            'laudo_checklists',
        );

        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createChecklistsTable()
    {
        $table = $this->db->prefixTable('laudo_checklists');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'code' => array('type' => 'VARCHAR', 'constraint' => 120),
            'category_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'type_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'description' => array('type' => 'TEXT', 'null' => true),
            'version' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'is_default' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'responsible_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'structure_json' => array('type' => 'LONGTEXT', 'null' => true),
            'published_at' => array('type' => 'DATETIME', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->addKey('type_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('status');
        $this->forge->addKey('is_active');
        $this->forge->addKey('is_default');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_checklists', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createChecklistResponsesTable()
    {
        $table = $this->db->prefixTable('laudo_checklist_responses');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'inspection_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'checklist_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'group_key' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'item_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'response' => array('type' => 'TEXT', 'null' => true),
            'observation' => array('type' => 'TEXT', 'null' => true),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'web'),
            'photos_json' => array('type' => 'LONGTEXT', 'null' => true),
            'measurements_json' => array('type' => 'LONGTEXT', 'null' => true),
            'nonconformity_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'answered_at' => array('type' => 'DATETIME', 'null' => true),
            'ip_address' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('checklist_id');
        $this->forge->addKey('item_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_checklist_responses', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createMeasurementTypesTable()
    {
        $table = $this->db->prefixTable('laudo_measurement_types');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'quantity' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'unit' => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => true),
            'min_value' => array('type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true),
            'max_value' => array('type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true),
            'reference_value' => array('type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true),
            'tolerance_value' => array('type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true),
            'decimal_places' => array('type' => 'INT', 'constraint' => 11, 'default' => 2),
            'auto_classification' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'description' => array('type' => 'TEXT', 'null' => true),
            'status' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->addKey('status');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_measurement_types', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createMeasurementsTable()
    {
        $table = $this->db->prefixTable('laudo_measurements');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'measurement_type_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'checklist_item_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'value' => array('type' => 'DECIMAL', 'constraint' => '15,4', 'null' => true),
            'unit' => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => true),
            'result' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true),
            'measured_at' => array('type' => 'DATETIME', 'null' => true),
            'location' => array('type' => 'TEXT', 'null' => true),
            'equipment_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'responsible_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'photo' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'observation' => array('type' => 'TEXT', 'null' => true),
            'gps_lat' => array('type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true),
            'gps_lng' => array('type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true),
            'gps_text' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('measurement_type_id');
        $this->forge->addKey('laudo_id');
        $this->forge->addKey('checklist_item_id');
        $this->forge->addKey('equipment_id');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_measurements', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createEquipmentsTable()
    {
        $table = $this->db->prefixTable('laudo_equipments');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'equipment_type' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'manufacturer' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'model' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'serial_number' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'patrimony_number' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'acquisition_date' => array('type' => 'DATE', 'null' => true),
            'last_calibration' => array('type' => 'DATE', 'null' => true),
            'next_calibration' => array('type' => 'DATE', 'null' => true),
            'certificate' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'laboratory' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'available'),
            'observations' => array('type' => 'TEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->addKey('serial_number');
        $this->forge->addKey('status');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_equipments', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createNormsTable()
    {
        $table = $this->db->prefixTable('laudo_norms');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'code' => array('type' => 'VARCHAR', 'constraint' => 120),
            'title' => array('type' => 'VARCHAR', 'constraint' => 190),
            'institution' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'category' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'edition' => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => true),
            'year' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
            'description' => array('type' => 'TEXT', 'null' => true),
            'link' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'authorized_file' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'status' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'observation' => array('type' => 'TEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->addKey('status');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_norms', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }

    private function createNormLinksTable()
    {
        $table = $this->db->prefixTable('laudo_norm_links');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'norm_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'entity_type' => array('type' => 'VARCHAR', 'constraint' => 100),
            'entity_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'notes' => array('type' => 'TEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('norm_id');
        $this->forge->addKey('entity_type');
        $this->forge->addKey('entity_id');
        $this->forge->addKey('deleted');
        $this->forge->createTable('laudo_norm_links', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }
}
