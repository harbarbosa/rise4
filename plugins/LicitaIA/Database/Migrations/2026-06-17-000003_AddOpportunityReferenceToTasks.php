<?php

namespace LicitaIA\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOpportunityReferenceToTasks extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('tasks');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $this->ensureColumns($table, array(
            'opportunity_id' => "ALTER TABLE `{$table}` ADD `opportunity_id` INT(11) NULL DEFAULT NULL AFTER `ticket_id`",
        ));
    }

    public function down()
    {
        $table = $this->db->prefixTable('tasks');
        if (!$this->db->tableExists($table)) {
            return;
        }

        try {
            $fields = $this->db->getFieldNames($table);
            if (in_array('opportunity_id', (array) $fields, true)) {
                $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `opportunity_id`");
            }
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA] Unable to rollback tasks opportunity reference column: ' . $e->getMessage());
        }
    }

    private function ensureColumns($table, array $columns)
    {
        $existing_fields = array();
        try {
            $existing_fields = $this->db->getFieldNames($table);
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA] Unable to inspect columns for ' . $table . ': ' . $e->getMessage());
        }

        $existing_fields = array_change_key_case(array_flip((array) $existing_fields), CASE_LOWER);

        foreach ($columns as $column => $sql) {
            try {
                if (!isset($existing_fields[strtolower((string) $column)])) {
                    $this->db->query($sql);
                }
            } catch (\Throwable $e) {
                if (stripos($e->getMessage(), 'Duplicate column name') === false) {
                    log_message('error', '[LicitaIA] Column fallback error for ' . $table . '.' . $column . ': ' . $e->getMessage());
                }
            }
        }
    }
}
