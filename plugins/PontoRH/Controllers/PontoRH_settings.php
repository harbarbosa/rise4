<?php

namespace PontoRH\Controllers;

class PontoRH_settings extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureSettingsAccess();

        $view_data['settings'] = $this->settings_model->get_all_settings_with_defaults();
        $view_data['can_manage'] = \PontoRH\Plugin::canManageSettings($this->login_user);

        return $this->template->rander('PontoRH\\Views\\settings\\index', $view_data);
    }

    public function save()
    {
        $this->ensureSettingsAccess();

        $keys = array(
            'workday_start',
            'workday_end',
            'default_break_minutes',
            'allow_manual_adjustments',
            'mirror_default_range_days',
            'reports_default_range_days',
            'require_gps',
            'require_selfie',
            'allow_offline_marking',
            'allowed_radius_meters',
            'default_tolerance_minutes',
            'bank_hours_enabled',
        );

        foreach ($keys as $key) {
            $value = $this->request->getPost($key);
            if (in_array($key, array('allow_manual_adjustments', 'require_gps', 'require_selfie', 'allow_offline_marking', 'bank_hours_enabled'), true)) {
                $value = $value ? 1 : 0;
            }
            $this->settings_model->save_setting($key, $value);
        }

        $this->logAudit(
            'pontorh_settings',
            0,
            'update',
            'Updated module settings',
            array('keys' => $keys),
            (int) $this->login_user->id
        );

        echo json_encode(array('success' => true, 'message' => app_lang('record_saved')));
    }
}
