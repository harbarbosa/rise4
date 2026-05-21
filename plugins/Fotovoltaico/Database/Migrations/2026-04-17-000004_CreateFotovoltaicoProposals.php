<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFotovoltaicoProposals extends Migration
{
    public function up()
    {
        $proposals_table = $this->db->prefixTable('fv_proposals');
        if (!$this->db->tableExists($proposals_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'proposal_code' => array('type' => 'VARCHAR', 'constraint' => 80),
                'client_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'lead_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'project_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'title' => array('type' => 'VARCHAR', 'constraint' => 190),
                'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'),
                'currency' => array('type' => 'VARCHAR', 'constraint' => 10, 'default' => 'BRL'),
                'subtotal' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'discount_total' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'tax_total' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'total' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'issue_date' => array('type' => 'DATE', 'null' => true, 'default' => null),
                'valid_until' => array('type' => 'DATE', 'null' => true, 'default' => null),
                'notes' => array('type' => 'TEXT', 'null' => true),
                'metadata_json' => array('type' => 'LONGTEXT', 'null' => true),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('proposal_code', false, true);
            $this->forge->addKey('client_id');
            $this->forge->addKey('lead_id');
            $this->forge->addKey('project_id');
            $this->forge->addKey('status');
            $this->forge->createTable('fv_proposals', true, array('ENGINE' => 'InnoDB'));
        }

        $proposal_versions_table = $this->db->prefixTable('fv_proposal_versions');
        if (!$this->db->tableExists($proposal_versions_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'proposal_id' => array('type' => 'INT', 'constraint' => 11),
                'version_number' => array('type' => 'INT', 'constraint' => 11, 'default' => 1),
                'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'),
                'subtotal' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'discount_total' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'tax_total' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'total' => array('type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0),
                'result_json' => array('type' => 'LONGTEXT', 'null' => true),
                'payload_json' => array('type' => 'LONGTEXT', 'null' => true),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('proposal_id');
            $this->forge->addKey('version_number');
            $this->forge->addKey(array('proposal_id', 'version_number'), false, true, 'proposal_version_unique');
            $this->forge->addKey('status');
            $this->forge->createTable('fv_proposal_versions', true, array('ENGINE' => 'InnoDB'));
        }

        $proposal_snapshots_table = $this->db->prefixTable('fv_proposal_snapshots');
        if (!$this->db->tableExists($proposal_snapshots_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'proposal_id' => array('type' => 'INT', 'constraint' => 11),
                'proposal_version_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'snapshot_json' => array('type' => 'LONGTEXT'),
                'snapshot_hash' => array('type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
                'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
                'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            ));

            $this->forge->addKey('id', true);
            $this->forge->addKey('proposal_id');
            $this->forge->addKey('proposal_version_id');
            $this->forge->createTable('fv_proposal_snapshots', true, array('ENGINE' => 'InnoDB'));
        }
    }

    public function down()
    {
        $this->forge->dropTable('fv_proposal_snapshots', true);
        $this->forge->dropTable('fv_proposal_versions', true);
        $this->forge->dropTable('fv_proposals', true);
    }
}
