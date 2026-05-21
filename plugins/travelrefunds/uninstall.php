<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

function travelrefunds_uninstall()
{
    $db = db_connect('default');
    $prefix = get_db_prefix();

    $tables = array(
        $prefix . 'travelrefunds_approval_logs',
        $prefix . 'travelrefunds_reimbursements',
        $prefix . 'travelrefunds_trips',
        $prefix . 'travelrefunds_categories',
        $prefix . 'travelrefunds_settings',
    );

    foreach ($tables as $table) {
        if ($db->tableExists($table)) {
            $db->query('DROP TABLE `' . $table . '`');
        }
    }
}
