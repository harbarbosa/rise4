<?php

namespace LaudosTecnicos\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLaudosFieldsAndSequences extends Migration
{
    public function up()
    {
        $this->addLaudosFields();
        $this->createNumberSequencesTable();
    }

    public function down()
    {
        $table = $this->db->prefixTable('laudo_number_sequences');
        if ($this->db->tableExists($table)) {
            $this->forge->dropTable('laudo_number_sequences', true);
        }

        $laudos_table = $this->db->prefixTable('laudos');
        $fields = array(
            'task_id',
            'contract_id',
            'proposal_id',
            'service_order_id',
            'unit_name',
            'address',
            'inspection_location',
            'commercial_responsible_id',
            'inspection_team',
            'technical_responsible_id',
            'tags',
            'cost_center',
            'proposal_number',
            'contract_number',
            'external_reference',
            'confidentiality',
            'client_observations',
            'number_sequence',
            'number_sequence_key',
        );

        foreach ($fields as $field) {
            if ($this->db->fieldExists($field, $laudos_table)) {
                $this->forge->dropColumn('laudos', $field);
            }
        }
    }

    private function addLaudosFields()
    {
        $table = $this->db->prefixTable('laudos');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $fields = array();

        if (!$this->db->fieldExists('task_id', $table)) {
            $fields['task_id'] = array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true);
        }

        if (!$this->db->fieldExists('contract_id', $table)) {
            $fields['contract_id'] = array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true);
        }

        if (!$this->db->fieldExists('proposal_id', $table)) {
            $fields['proposal_id'] = array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true);
        }

        if (!$this->db->fieldExists('service_order_id', $table)) {
            $fields['service_order_id'] = array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true);
        }

        if (!$this->db->fieldExists('unit_name', $table)) {
            $fields['unit_name'] = array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true);
        }

        if (!$this->db->fieldExists('address', $table)) {
            $fields['address'] = array('type' => 'TEXT', 'null' => true);
        }

        if (!$this->db->fieldExists('inspection_location', $table)) {
            $fields['inspection_location'] = array('type' => 'TEXT', 'null' => true);
        }

        if (!$this->db->fieldExists('commercial_responsible_id', $table)) {
            $fields['commercial_responsible_id'] = array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true);
        }

        if (!$this->db->fieldExists('inspection_team', $table)) {
            $fields['inspection_team'] = array('type' => 'LONGTEXT', 'null' => true);
        }

        if (!$this->db->fieldExists('technical_responsible_id', $table)) {
            $fields['technical_responsible_id'] = array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true);
        }

        if (!$this->db->fieldExists('tags', $table)) {
            $fields['tags'] = array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true);
        }

        if (!$this->db->fieldExists('cost_center', $table)) {
            $fields['cost_center'] = array('type' => 'VARCHAR', 'constraint' => 190, 'null' => true);
        }

        if (!$this->db->fieldExists('proposal_number', $table)) {
            $fields['proposal_number'] = array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true);
        }

        if (!$this->db->fieldExists('contract_number', $table)) {
            $fields['contract_number'] = array('type' => 'VARCHAR', 'constraint' => 80, 'null' => true);
        }

        if (!$this->db->fieldExists('external_reference', $table)) {
            $fields['external_reference'] = array('type' => 'VARCHAR', 'constraint' => 120, 'null' => true);
        }

        if (!$this->db->fieldExists('confidentiality', $table)) {
            $fields['confidentiality'] = array('type' => 'VARCHAR', 'constraint' => 50, 'null' => true);
        }

        if (!$this->db->fieldExists('client_observations', $table)) {
            $fields['client_observations'] = array('type' => 'TEXT', 'null' => true);
        }

        if (!$this->db->fieldExists('number_sequence', $table)) {
            $fields['number_sequence'] = array('type' => 'INT', 'constraint' => 11, 'null' => true);
        }

        if (!$this->db->fieldExists('number_sequence_key', $table)) {
            $fields['number_sequence_key'] = array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true);
        }

        if ($fields) {
            $this->forge->addColumn('laudos', $fields);
        }
    }

    private function createNumberSequencesTable()
    {
        $table = $this->db->prefixTable('laudo_number_sequences');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'sequence_key' => array('type' => 'VARCHAR', 'constraint' => 255),
            'next_sequence' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
        ));
        $this->forge->addField('`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->forge->addField('`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->forge->addKey('id', true);
        $this->forge->addKey('sequence_key', false, true);
        $this->forge->createTable('laudo_number_sequences', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ));
    }
}
