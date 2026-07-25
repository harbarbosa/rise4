<?php

namespace LicitaIA\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicitaiaAlertInfrastructure extends Migration
{
    public function up()
    {
        $charset = $this->db->charset ?: 'utf8mb4';
        $collation = $this->db->DBCollat ?: 'utf8mb4_unicode_ci';

        $this->ensureNotificationsColumns();
        $this->ensureAlertLogsTable($charset, $collation);
    }

    public function down()
    {
        $this->forge->dropTable('licitaia_alert_logs', true);
    }

    private function ensureNotificationsColumns()
    {
        $table = $this->db->prefixTable('notifications');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $columns = array(
            'plugin_alert_type' => "ALTER TABLE `{$table}` ADD `plugin_alert_type` VARCHAR(80) NULL DEFAULT NULL AFTER `reminder_log_id`",
            'plugin_opportunity_id' => "ALTER TABLE `{$table}` ADD `plugin_opportunity_id` INT(11) NULL DEFAULT NULL AFTER `plugin_alert_type`",
            'plugin_alert_key' => "ALTER TABLE `{$table}` ADD `plugin_alert_key` VARCHAR(120) NULL DEFAULT NULL AFTER `plugin_opportunity_id`",
            'plugin_link_url' => "ALTER TABLE `{$table}` ADD `plugin_link_url` VARCHAR(255) NULL DEFAULT NULL AFTER `plugin_alert_key`",
            'plugin_message' => "ALTER TABLE `{$table}` ADD `plugin_message` LONGTEXT NULL AFTER `plugin_link_url`",
            'plugin_payload_json' => "ALTER TABLE `{$table}` ADD `plugin_payload_json` LONGTEXT NULL AFTER `plugin_message`",
            'plugin_channel_web' => "ALTER TABLE `{$table}` ADD `plugin_channel_web` TINYINT(1) NOT NULL DEFAULT 1 AFTER `plugin_payload_json`",
            'plugin_channel_email' => "ALTER TABLE `{$table}` ADD `plugin_channel_email` TINYINT(1) NOT NULL DEFAULT 0 AFTER `plugin_channel_web`",
            'plugin_channel_whatsapp' => "ALTER TABLE `{$table}` ADD `plugin_channel_whatsapp` TINYINT(1) NOT NULL DEFAULT 0 AFTER `plugin_channel_email`",
        );

        $this->ensureColumns($table, $columns);
    }

    private function ensureAlertLogsTable($charset, $collation)
    {
        $table = $this->db->prefixTable('licitaia_alert_logs');
        if ($this->db->tableExists($table)) {
            return;
        }

        $this->forge->addField(array(
            'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
            'alert_type' => array('type' => 'VARCHAR', 'constraint' => 80),
            'alert_key' => array('type' => 'VARCHAR', 'constraint' => 120),
            'opportunity_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'recipient_user_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'notification_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'alert_date' => array('type' => 'DATE'),
            'channel_web' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1),
            'channel_email' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'channel_whatsapp' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
            'status' => array('type' => 'VARCHAR', 'constraint' => 30, 'default' => 'completed'),
            'subject' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null),
            'message' => array('type' => 'LONGTEXT', 'null' => true),
            'payload_json' => array('type' => 'LONGTEXT', 'null' => true),
            'sent_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'created_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null),
            'created_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'updated_at' => array('type' => 'DATETIME', 'null' => true, 'default' => null),
            'deleted' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
        ));

        $this->forge->addKey('id', true);
        $this->forge->addKey('alert_type');
        $this->forge->addKey('alert_key');
        $this->forge->addKey('opportunity_id');
        $this->forge->addKey('recipient_user_id');
        $this->forge->addKey('alert_date');
        $this->forge->addKey(array('alert_key', 'opportunity_id', 'recipient_user_id', 'alert_date'), false, true, 'licitaia_alert_dedupe');
        $this->forge->createTable('licitaia_alert_logs', true, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => $charset,
            'COLLATE' => $collation,
        ));
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
