<?php

namespace PontoRH\install;

function pontorh_install()
{
    \PontoRH\Plugin::runMigrations();

    try {
        $db = db_connect('default');
        $table = $db->prefixTable('pontorh_settings');

        if (!$db->tableExists($table)) {
            return true;
        }

        $defaults = array(
            'workday_start' => '08:00',
            'workday_end' => '18:00',
            'default_break_minutes' => '60',
            'allow_manual_adjustments' => '1',
            'mirror_default_range_days' => '31',
            'reports_default_range_days' => '31',
            'require_gps' => '1',
            'require_selfie' => '0',
            'allow_offline_marking' => '0',
            'allowed_radius_meters' => '200',
            'default_tolerance_minutes' => '10',
            'bank_hours_enabled' => '1',
        );

        foreach ($defaults as $key => $value) {
            $existing = $db->table($table)->where('setting_name', $key)->get()->getRow();
            if (!$existing) {
                $db->table($table)->insert(array(
                    'setting_name' => $key,
                    'setting_value' => $value,
                    'created_at' => get_current_utc_time(),
                    'updated_at' => get_current_utc_time(),
                ));
            }
        }
    } catch (\Throwable $e) {
        log_message('error', '[PontoRH] Install hook error: ' . $e->getMessage());
    }

    return true;
}
