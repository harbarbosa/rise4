<?php

namespace GED\Database\Migrations;

class AddGedNotificationColumns
{
    public function up()
    {
        $db = db_connect('default');

        $columns = array(
            'plugin_document_id' => "ADD COLUMN plugin_document_id INT NOT NULL DEFAULT 0 AFTER reminder_log_id",
            'plugin_document_creator_id' => "ADD COLUMN plugin_document_creator_id INT NOT NULL DEFAULT 0 AFTER plugin_document_id",
            'plugin_document_title' => "ADD COLUMN plugin_document_title VARCHAR(255) NOT NULL DEFAULT '' AFTER plugin_document_creator_id",
            'plugin_document_type_name' => "ADD COLUMN plugin_document_type_name VARCHAR(255) NOT NULL DEFAULT '' AFTER plugin_document_title",
            'plugin_document_status' => "ADD COLUMN plugin_document_status VARCHAR(50) NOT NULL DEFAULT '' AFTER plugin_document_type_name",
            'plugin_expiration_date' => "ADD COLUMN plugin_expiration_date VARCHAR(20) NOT NULL DEFAULT '' AFTER plugin_document_status",
            'plugin_days_before' => "ADD COLUMN plugin_days_before INT NOT NULL DEFAULT 0 AFTER plugin_expiration_date",
            'plugin_recipient_user_id' => "ADD COLUMN plugin_recipient_user_id INT NOT NULL DEFAULT 0 AFTER plugin_days_before",
            'plugin_link_url' => "ADD COLUMN plugin_link_url VARCHAR(255) NOT NULL DEFAULT '' AFTER plugin_recipient_user_id",
            'plugin_submission_id' => "ADD COLUMN plugin_submission_id INT NOT NULL DEFAULT 0 AFTER plugin_link_url",
            'plugin_submission_creator_id' => "ADD COLUMN plugin_submission_creator_id INT NOT NULL DEFAULT 0 AFTER plugin_submission_id",
            'plugin_submission_status' => "ADD COLUMN plugin_submission_status VARCHAR(50) NOT NULL DEFAULT '' AFTER plugin_submission_creator_id",
            'plugin_portal_reference' => "ADD COLUMN plugin_portal_reference VARCHAR(255) NOT NULL DEFAULT '' AFTER plugin_submission_status",
        );

        foreach ($columns as $field => $sql) {
            if (!$db->fieldExists($field, 'notifications')) {
                $db->query("ALTER TABLE " . $db->prefixTable('notifications') . " " . $sql);
            }
        }
    }

    public function down()
    {
        $db = db_connect('default');
        $fields = array(
            'plugin_document_id',
            'plugin_document_creator_id',
            'plugin_document_title',
            'plugin_document_type_name',
            'plugin_document_status',
            'plugin_expiration_date',
            'plugin_days_before',
            'plugin_recipient_user_id',
            'plugin_link_url',
            'plugin_submission_id',
            'plugin_submission_creator_id',
            'plugin_submission_status',
            'plugin_portal_reference',
        );

        foreach ($fields as $field) {
            if ($db->fieldExists($field, 'notifications')) {
                $db->query("ALTER TABLE " . $db->prefixTable('notifications') . " DROP COLUMN " . $field);
            }
        }
    }
}
