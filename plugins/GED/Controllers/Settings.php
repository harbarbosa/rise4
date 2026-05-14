<?php

namespace GED\Controllers;

class Settings extends GedBaseController
{
    private $Ged_settings_model;

    public function __construct()
    {
        parent::__construct();
        $this->Ged_settings_model = model('GED\\Models\\Ged_settings_model');
    }

    public function index()
    {
        if (!$this->_has_manage_settings_permission()) {
            app_redirect('forbidden');
        }

        return $this->template->rander('GED\\Views\\settings\\index', array(
            'settings' => $this->_get_settings_payload(),
            'status_options' => $this->_get_status_options(),
            'portal_status_options' => $this->_get_portal_status_options(),
            'can_manage' => $this->_has_manage_settings_permission(),
        ));
    }

    public function save()
    {
        if (!$this->_has_manage_settings_permission()) {
            return $this->_json_permission_denied();
        }

        $alert_days = $this->_normalize_alert_days((string) $this->request->getPost('alert_days'));
        if ($alert_days === '') {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Informe ao menos um dia de alerta válido.',
            ));
        }

        $upload_max_size_mb = (int) $this->request->getPost('upload_max_size_mb');
        if ($upload_max_size_mb <= 0) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Informe um tamanho máximo de upload válido.',
            ));
        }

        $allowed_file_extensions = $this->_normalize_extensions((string) $this->request->getPost('allowed_file_extensions'));
        if ($allowed_file_extensions === '') {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Informe ao menos uma extensão permitida.',
            ));
        }

        $default_document_status = trim((string) $this->request->getPost('default_document_status'));
        if (!in_array($default_document_status, $this->_get_status_options(), true)) {
            $default_document_status = 'pending';
        }

        $default_submission_status = trim((string) $this->request->getPost('default_submission_status'));
        if (!in_array($default_submission_status, $this->_get_portal_status_options(), true)) {
            $default_submission_status = 'pending';
        }

        $settings = array(
            'alert_days' => $alert_days,
            'enable_native_notifications' => $this->request->getPost('enable_native_notifications') ? '1' : '0',
            'notify_admins' => $this->request->getPost('notify_admins') ? '1' : '0',
            'notify_document_creator' => $this->request->getPost('notify_document_creator') ? '1' : '0',
            'upload_max_size_mb' => (string) $upload_max_size_mb,
            'allowed_file_extensions' => $allowed_file_extensions,
            'default_document_status' => $default_document_status,
            'default_submission_status' => $default_submission_status,
        );

        foreach ($settings as $name => $value) {
            $this->Ged_settings_model->set_setting($name, $value);
        }

        return $this->_json_success(array(), app_lang('record_saved'));
    }

    private function _get_settings_payload()
    {
        return array(
            'alert_days' => ged_setting('alert_days', '30,15,7,0'),
            'enable_native_notifications' => ged_setting_bool('enable_native_notifications', true),
            'notify_admins' => ged_setting_bool('notify_admins', true),
            'notify_document_creator' => ged_setting_bool('notify_document_creator', true),
            'upload_max_size_mb' => ged_setting_int('upload_max_size_mb', 20),
            'allowed_file_extensions' => ged_setting('allowed_file_extensions', 'pdf,jpg,jpeg,png,doc,docx'),
            'default_document_status' => ged_setting('default_document_status', 'pending'),
            'default_submission_status' => ged_setting('default_submission_status', 'pending'),
        );
    }

    private function _get_status_options()
    {
        return array(
            'pending' => app_lang('ged_status_pending'),
            'valid' => app_lang('ged_status_valid'),
            'expiring' => app_lang('ged_status_expiring'),
            'expired' => app_lang('ged_status_expired'),
            'archived' => app_lang('ged_status_archived'),
        );
    }

    private function _get_portal_status_options()
    {
        return array(
            'pending' => app_lang('ged_status_pending'),
            'submitted' => app_lang('ged_status_submitted'),
            'approved' => app_lang('ged_status_approved'),
            'rejected' => app_lang('ged_status_rejected'),
            'expired' => app_lang('ged_status_expired'),
        );
    }

    private function _normalize_alert_days($raw)
    {
        $days = array();
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if ($part === '' || !is_numeric($part)) {
                continue;
            }
            $int_part = (int) $part;
            if ($int_part < 0) {
                continue;
            }
            $days[] = $int_part;
        }

        $days = array_values(array_unique($days));
        sort($days);
        return count($days) ? implode(',', $days) : '';
    }

    private function _normalize_extensions($raw)
    {
        $extensions = array();
        foreach (explode(',', strtolower($raw)) as $part) {
            $part = trim($part);
            if ($part === '' || preg_match('/[^a-z0-9]/', $part)) {
                continue;
            }
            $extensions[] = $part;
        }

        $extensions = array_values(array_unique($extensions));
        return count($extensions) ? implode(',', $extensions) : '';
    }
}
