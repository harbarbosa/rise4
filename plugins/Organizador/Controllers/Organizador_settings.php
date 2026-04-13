<?php

namespace Organizador\Controllers;

use App\Controllers\Security_Controller;
use Organizador\Plugin;

class Organizador_settings extends Security_Controller
{
    function __construct()
    {
        parent::__construct();
    }

    private function _ensure_access()
    {
        if (!Plugin::canManageSettings($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    public function index()
    {
        $this->_ensure_access();
        $view_data['settings'] = array(
            'organizador_enable_internal_notifications' => get_setting('organizador_enable_internal_notifications'),
            'organizador_enable_email_notifications' => get_setting('organizador_enable_email_notifications'),
            'organizador_enable_auto_reminders' => get_setting('organizador_enable_auto_reminders'),
            'organizador_enable_overdue_alerts' => get_setting('organizador_enable_overdue_alerts'),
            'organizador_reminder_hours_before_due' => get_setting('organizador_reminder_hours_before_due'),
            'organizador_sync_to_events_calendar' => get_setting('organizador_sync_to_events_calendar'),
            'organizador_public_api_enabled' => get_setting('organizador_public_api_enabled'),
            'organizador_public_api_token' => $this->_ensure_public_api_token(false),
        );

        return $this->template->rander('Organizador\\Views\\settings\\index', $view_data);
    }

    public function save()
    {
        $this->_ensure_access();

        $settings = array(
            'organizador_enable_internal_notifications',
            'organizador_enable_email_notifications',
            'organizador_enable_auto_reminders',
            'organizador_enable_overdue_alerts',
            'organizador_reminder_hours_before_due',
            'organizador_sync_to_events_calendar',
            'organizador_public_api_enabled',
        );

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (is_null($value)) {
                $value = '';
            }
            $this->_save_app_setting($setting, $value);
        }

        if ($this->request->getPost('regenerate_public_api_token')) {
            $this->_ensure_public_api_token(true);
        } else {
            $this->_ensure_public_api_token(false);
        }

        echo json_encode(array('success' => true, 'message' => app_lang('settings_updated')));
    }

    public function regenerate_public_api_token()
    {
        $this->_ensure_access();
        $token = $this->_ensure_public_api_token(true);
        echo json_encode(array(
            'success' => true,
            'message' => app_lang('organizador_public_api_token_regenerated'),
            'token' => $token,
        ));
    }

    private function _save_app_setting($setting_name, $setting_value)
    {
        $settings_table = db_connect('default')->prefixTable('settings');
        $db = db_connect('default');

        $existing = $db->table($settings_table)
            ->where('setting_name', $setting_name)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        $data = array(
            'setting_name' => $setting_name,
            'setting_value' => is_array($setting_value) ? serialize($setting_value) : $setting_value,
            'type' => 'app',
            'deleted' => 0,
        );

        if ($existing) {
            if (isset($existing->id)) {
                $db->table($settings_table)->where('id', $existing->id)->update($data);
            } else {
                $db->table($settings_table)->where('setting_name', $setting_name)->update($data);
            }
        } else {
            $db->table($settings_table)->insert($data);
        }
    }

    private function _ensure_public_api_token($regenerate = false)
    {
        $setting_name = 'organizador_public_api_token';
        $current = trim((string) get_setting($setting_name));
        if ($regenerate || !$current) {
            $current = Plugin::generatePublicApiToken();
            $this->_save_app_setting($setting_name, $current);
        }

        return $current;
    }
}
