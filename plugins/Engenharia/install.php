<?php

namespace Engenharia\install;

function engenharia_install()
{
    \Engenharia\Plugin::runMigrations();

    try {
        $db = db_connect('default');
        $settings_table = $db->prefixTable('eng_settings');
        $types_table = $db->prefixTable('eng_types');

        if (!$db->tableExists($settings_table) || !$db->tableExists($types_table)) {
            log_message('error', '[Engenharia] Install skipped because required tables are unavailable.');
            return false;
        }

        $now = function_exists('get_current_utc_time') ? get_current_utc_time() : gmdate('Y-m-d H:i:s');

        foreach (engenharia_default_settings() as $key => $value) {
            $exists = $db->table($settings_table)->where('setting_name', $key)->get()->getRow();
            if ($exists) {
                continue;
            }

            $db->table($settings_table)->insert(array(
                'setting_name' => $key,
                'setting_value' => (string) $value,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }

        foreach (engenharia_default_types() as $type) {
            $exists = $db->table($types_table)->where('code', $type['code'])->get()->getRow();
            if ($exists) {
                if (empty($exists->prefix) && !empty($type['prefix'])) {
                    $db->table($types_table)->where('id', $exists->id)->update(array('prefix' => $type['prefix'], 'updated_at' => $now));
                }
                continue;
            }

            $db->table($types_table)->insert(array(
                'name' => $type['name'],
                'code' => $type['code'],
                'prefix' => $type['prefix'],
                'is_enabled' => $type['is_enabled'],
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }

        return true;
    } catch (\Throwable $e) {
        log_message('error', '[Engenharia] Install hook error: ' . $e->getMessage());
        return false;
    }
}
