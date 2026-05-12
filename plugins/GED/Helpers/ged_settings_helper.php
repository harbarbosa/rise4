<?php

if (!function_exists('ged_settings_model')) {
    function ged_settings_model()
    {
        return model('GED\\Models\\Ged_settings_model');
    }
}

if (!function_exists('ged_setting')) {
    function ged_setting($key, $default = '')
    {
        $value = ged_settings_model()->get_value($key, $default);
        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('ged_setting_bool')) {
    function ged_setting_bool($key, $default = false)
    {
        $value = ged_setting($key, $default ? '1' : '0');
        return (string) $value === '1';
    }
}

if (!function_exists('ged_setting_int')) {
    function ged_setting_int($key, $default = 0)
    {
        return (int) ged_setting($key, (string) $default);
    }
}

if (!function_exists('ged_setting_alert_days')) {
    function ged_setting_alert_days()
    {
        $raw = (string) ged_setting('alert_days', '30,15,7,0');
        $parts = array_filter(array_map('trim', explode(',', $raw)), static function ($value) {
            return $value !== '';
        });

        $days = array();
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $days[] = (int) $part;
            }
        }

        $days = array_values(array_unique($days));
        sort($days);

        return $days;
    }
}

if (!function_exists('ged_setting_extensions')) {
    function ged_setting_extensions()
    {
        $raw = (string) ged_setting('allowed_file_extensions', 'pdf,jpg,jpeg,png,doc,docx');
        $extensions = array_filter(array_map('trim', explode(',', strtolower($raw))), static function ($value) {
            return $value !== '';
        });

        return array_values(array_unique($extensions));
    }
}

if (!function_exists('ged_setting_status_options')) {
    function ged_setting_status_options()
    {
        return array('pending', 'valid', 'expiring', 'expired', 'archived');
    }
}

if (!function_exists('ged_setting_portal_status_options')) {
    function ged_setting_portal_status_options()
    {
        return array('pending', 'submitted', 'approved', 'rejected', 'expired');
    }
}

if (!function_exists('ged_setting_defaults')) {
    function ged_setting_defaults()
    {
        return array(
            'alert_days' => '30,15,7,0',
            'enable_native_notifications' => '1',
            'notify_admins' => '1',
            'notify_document_creator' => '1',
            'upload_max_size_mb' => '20',
            'allowed_file_extensions' => 'pdf,jpg,jpeg,png,doc,docx',
            'default_document_status' => 'pending',
            'default_submission_status' => 'pending',
        );
    }
}
