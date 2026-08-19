<?php

namespace Engenharia\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEngenhariaDomainTables extends Migration
{
    public function up()
    {
        $this->createTypesTableUpgrade();
        $this->createNumberSequencesTable();
        $this->createLaudosTable();
        $this->createAreasTable();
        $this->createChecklistsTable();
        $this->createChecklistGroupsTable();
        $this->createChecklistItemsTable();
        $this->createChecklistResponsesTable();
        $this->createMeasurementsTable();
        $this->createPhotosTable();
        $this->createNonconformitiesTable();
        $this->createNormsTable();
        $this->createLaudoNormsTable();
        $this->createInstrumentsTable();
        $this->createProfessionalsTable();
        $this->createStatusHistoryTable();
        $this->createReportTemplatesTable();
    }

    public function down()
    {
        $tables = array(
            'eng_status_history',
            'eng_report_templates',
            'eng_professionals',
            'eng_instruments',
            'eng_laudo_norms',
            'eng_norms',
            'eng_nonconformities',
            'eng_photos',
            'eng_measurements',
            'eng_checklist_responses',
            'eng_checklist_items',
            'eng_checklist_groups',
            'eng_checklists',
            'eng_laudo_areas',
            'eng_laudos',
            'eng_number_sequences',
        );

        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createTypesTableUpgrade()
    {
        $table = $this->db->prefixTable('eng_types');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = $this->db->getFieldNames($table);
        $add = array(
            'description' => array('type' => 'TEXT', 'null' => true),
            'prefix' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => true),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        );

        foreach ($add as $field => $definition) {
            if (!in_array($field, $fields, true)) {
                $this->forge->addColumn('eng_types', array($field => $definition));
            }
        }
    }

    private function createNumberSequencesTable()
    {
        $this->createTable('eng_number_sequences', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'type_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'sequence_year' => array('type' => 'INT', 'constraint' => 4),
            'next_number' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array(array('type_id', 'sequence_year')), true);
    }

    private function createLaudosTable()
    {
        $this->createTable('eng_laudos', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'number' => array('type' => 'VARCHAR', 'constraint' => 100),
            'type_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'title' => array('type' => 'VARCHAR', 'constraint' => 190),
            'client_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'contact_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'project_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'inspection_address' => array('type' => 'TEXT', 'null' => true),
            'installation_description' => array('type' => 'TEXT', 'null' => true),
            'objective' => array('type' => 'TEXT', 'null' => true),
            'scope' => array('type' => 'TEXT', 'null' => true),
            'technical_responsible_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'inspection_technician_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'checklist_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'checklist_version' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'checklist_snapshot_json' => array('type' => 'LONGTEXT', 'null' => true),
            'report_template_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'report_template_version' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'report_template_snapshot_json' => array('type' => 'LONGTEXT', 'null' => true),
            'scheduled_date' => array('type' => 'DATE', 'null' => true),
            'inspection_date' => array('type' => 'DATE', 'null' => true),
            'validity_date' => array('type' => 'DATE', 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 40, 'default' => 'draft'),
            'internal_notes' => array('type' => 'TEXT', 'null' => true),
            'conclusion' => array('type' => 'LONGTEXT', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'finalized_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'finalized_at' => array('type' => 'DATETIME', 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('number', 'type_id', 'client_id', 'project_id', 'status', 'deleted'));
    }

    private function createAreasTable()
    {
        $this->createTable('eng_laudo_areas', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'address' => array('type' => 'TEXT', 'null' => true),
            'description' => array('type' => 'TEXT', 'null' => true),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('laudo_id', 'deleted'));
    }

    private function createChecklistsTable()
    {
        $this->createTable('eng_checklists', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'root_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'type_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'description' => array('type' => 'TEXT', 'null' => true),
            'version' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'),
            'is_default' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'published_at' => array('type' => 'DATETIME', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('code', 'root_id', 'type_id', 'status', 'deleted'));
    }

    private function createChecklistGroupsTable()
    {
        $this->createTable('eng_checklist_groups', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'checklist_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'title' => array('type' => 'VARCHAR', 'constraint' => 190),
            'description' => array('type' => 'TEXT', 'null' => true),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('checklist_id', 'deleted'));
    }

    private function createChecklistItemsTable()
    {
        $this->createTable('eng_checklist_items', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'checklist_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'group_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'question' => array('type' => 'TEXT'),
            'response_type' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'text'),
            'required' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'norm_reference' => array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('checklist_id', 'group_id', 'deleted'));
    }

    private function createChecklistResponsesTable()
    {
        $this->createTable('eng_checklist_responses', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'item_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'area_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'response_value' => array('type' => 'TEXT', 'null' => true),
            'numeric_value' => array('type' => 'DECIMAL', 'constraint' => '18,6', 'null' => true),
            'observation' => array('type' => 'TEXT', 'null' => true),
            'is_conforming' => array('type' => 'TINYINT', 'constraint' => 1, 'null' => true),
            'answered_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'answered_at' => array('type' => 'DATETIME', 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('laudo_id', 'item_id', 'area_id', 'deleted'));
    }

    private function createMeasurementsTable()
    {
        $this->createTable('eng_measurements', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'area_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'instrument_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'value' => array('type' => 'DECIMAL', 'constraint' => '18,6', 'null' => true),
            'unit' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true),
            'measured_at' => array('type' => 'DATETIME', 'null' => true),
            'observation' => array('type' => 'TEXT', 'null' => true),
            'measured_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('laudo_id', 'area_id', 'instrument_id', 'deleted'));
    }

    private function createPhotosTable()
    {
        $this->createTable('eng_photos', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'area_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'response_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'file_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'file_name' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
            'storage_path' => array('type' => 'TEXT', 'null' => true),
            'caption' => array('type' => 'TEXT', 'null' => true),
            'hash' => array('type' => 'VARCHAR', 'constraint' => 128, 'null' => true),
            'sort' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
            'uploaded_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('laudo_id', 'area_id', 'response_id', 'deleted'));
    }

    private function createNonconformitiesTable()
    {
        $this->createTable('eng_nonconformities', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'area_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'response_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'title' => array('type' => 'VARCHAR', 'constraint' => 190),
            'description' => array('type' => 'TEXT', 'null' => true),
            'severity' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'medium'),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'open'),
            'recommendation' => array('type' => 'TEXT', 'null' => true),
            'due_date' => array('type' => 'DATE', 'null' => true),
            'resolved_at' => array('type' => 'DATETIME', 'null' => true),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('laudo_id', 'area_id', 'severity', 'status', 'deleted'));
    }

    private function createNormsTable()
    {
        $this->createTable('eng_norms', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'code' => array('type' => 'VARCHAR', 'constraint' => 100),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'edition' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true),
            'reference_text' => array('type' => 'TEXT', 'null' => true),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('code', 'is_active', 'deleted'));
    }

    private function createLaudoNormsTable()
    {
        $this->createTable('eng_laudo_norms', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'norm_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'reference_note' => array('type' => 'TEXT', 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'created_at' => $this->datetimeField(),
        ), array('laudo_id', 'norm_id', 'deleted'));
    }

    private function createInstrumentsTable()
    {
        $this->createTable('eng_instruments', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'manufacturer' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'model' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'serial_number' => array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true),
            'calibration_due_date' => array('type' => 'DATE', 'null' => true),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('serial_number', 'is_active', 'deleted'));
    }

    private function createProfessionalsTable()
    {
        $this->createTable('eng_professionals', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'user_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'registration_type' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true),
            'registration_number' => array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true),
            'registration_region' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true),
            'is_technical_responsible' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'is_inspection_technician' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'is_active' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('user_id', 'registration_number', 'is_active', 'deleted'));
    }

    private function createStatusHistoryTable()
    {
        $this->createTable('eng_status_history', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'laudo_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'from_status' => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => true),
            'to_status' => array('type' => 'VARCHAR', 'constraint' => 40),
            'comment' => array('type' => 'TEXT', 'null' => true),
            'changed_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true),
            'changed_at' => array('type' => 'DATETIME'),
            'source' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'web'),
        ), array('laudo_id', 'to_status', 'changed_by', 'changed_at'));
    }

    private function createReportTemplatesTable()
    {
        $this->createTable('eng_report_templates', array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'root_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'type_id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'name' => array('type' => 'VARCHAR', 'constraint' => 190),
            'code' => array('type' => 'VARCHAR', 'constraint' => 80),
            'version' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
            'content_html' => array('type' => 'LONGTEXT', 'null' => true),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'),
            'is_default' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'updated_by' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'created_at' => $this->datetimeField(),
            'updated_at' => $this->datetimeField(),
        ), array('code', 'root_id', 'type_id', 'status', 'deleted'));
    }

    private function createTable(string $name, array $fields, array $indexes = array(), bool $uniqueComposite = false)
    {
        $table = $this->db->prefixTable($name);
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField($fields);
        $this->forge->addKey('id', true);
        foreach ($indexes as $index) {
            $this->forge->addKey($index, false, $uniqueComposite && is_array($index));
        }

        $this->forge->createTable($name, true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ));
    }

    private function datetimeField()
    {
        return array('type' => 'DATETIME', 'null' => true, 'default' => null);
    }
}
