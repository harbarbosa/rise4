<?php

namespace Fotovoltaico\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAneelImportSupport extends Migration
{
    public function up()
    {
        $distributors_table = $this->db->prefixTable('fv_distributors');
        if ($this->db->tableExists($distributors_table)) {
            $fields = $this->db->getFieldNames($distributors_table);
            if (is_array($fields)) {
                if (!in_array('aneel_code', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `aneel_code` VARCHAR(50) NULL AFTER `document`");
                }
                if (!in_array('agent_type', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `agent_type` VARCHAR(30) NOT NULL DEFAULT 'desconhecido' AFTER `source`");
                }
                if (!in_array('show_in_registration', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `show_in_registration` TINYINT(1) NOT NULL DEFAULT 1 AFTER `active`");
                }
                if (!in_array('origin_hash', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `origin_hash` VARCHAR(64) NULL AFTER `raw_payload`");
                }
                if (!in_array('sync_notes', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$distributors_table}` ADD `sync_notes` TEXT NULL AFTER `notes`");
                }
            }
        }

        $tariffs_table = $this->db->prefixTable('fv_tariffs');
        if ($this->db->tableExists($tariffs_table)) {
            $fields = $this->db->getFieldNames($tariffs_table);
            if (is_array($fields)) {
                if (!in_array('tariff_class', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `tariff_class` VARCHAR(120) NULL AFTER `subgroup`");
                }
                if (!in_array('tariff_subclass', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `tariff_subclass` VARCHAR(120) NULL AFTER `tariff_class`");
                }
                if (!in_array('group_name', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `group_name` VARCHAR(40) NULL AFTER `tariff_subclass`");
                }
                if (!in_array('time_slot', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `time_slot` VARCHAR(80) NULL AFTER `group_name`");
                }
                if (!in_array('unit', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `unit` VARCHAR(40) NULL AFTER `time_slot`");
                }
                if (!in_array('resolution', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `resolution` VARCHAR(255) NULL AFTER `unit`");
                }
                if (!in_array('tariff_detail', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `tariff_detail` VARCHAR(120) NULL AFTER `resolution`");
                }
                if (!in_array('tariff_base', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `tariff_base` VARCHAR(120) NULL AFTER `tariff_detail`");
                }
                if (!in_array('source', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `source` VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER `flag_value`");
                }
                if (!in_array('origin_hash', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `origin_hash` VARCHAR(64) NULL AFTER `source`");
                }
                if (!in_array('sync_notes', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `sync_notes` TEXT NULL AFTER `notes`");
                }
                if (!in_array('is_current', $fields, true)) {
                    $this->db->query("ALTER TABLE `{$tariffs_table}` ADD `is_current` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`");
                }
            }
        }

        $logs_table = $this->db->prefixTable('fv_import_logs');
        if (!$this->db->tableExists($logs_table)) {
            $this->forge->addField(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'import_type' => array('type' => 'VARCHAR', 'constraint' => 80),
                'source_type' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'url'),
                'source_path' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
                'status' => array('type' => 'VARCHAR', 'constraint' => 20, 'default' => 'completed'),
                'rows_read' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'created_count' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'updated_count' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'ignored_count' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'error_count' => array('type' => 'INT', 'constraint' => 11, 'default' => 0),
                'errors_json' => array('type' => 'LONGTEXT', 'null' => true),
                'summary_json' => array('type' => 'LONGTEXT', 'null' => true),
                'started_at' => array('type' => 'DATETIME', 'null' => true),
                'finished_at' => array('type' => 'DATETIME', 'null' => true),
                'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'created_at' => array('type' => 'DATETIME', 'null' => true),
                'updated_at' => array('type' => 'DATETIME', 'null' => true),
                'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            ));
            $this->forge->addKey('id', true);
            $this->forge->addKey('import_type');
            $this->forge->addKey('status');
            $this->forge->addKey('started_at');
            $this->forge->createTable('fv_import_logs', true, array('ENGINE' => 'InnoDB'));
        }
    }

    public function down()
    {
        $logs_table = $this->db->prefixTable('fv_import_logs');
        if ($this->db->tableExists($logs_table)) {
            $this->forge->dropTable('fv_import_logs', true);
        }
    }
}
