<?php

namespace LicitaIA\Controllers;

class Settings extends Licitaia_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();

        $settings = $this->settings_model->get_all_settings();
        $settings_map = array();
        foreach ($settings as $row) {
            $settings_map[$row->setting_name] = $row->setting_value;
        }

        $alert_settings = $this->settings_model->get_alert_settings();
        $settings_map = array_merge($alert_settings, $settings_map);

        $users_model = model('App\\Models\\Users_model');
        $staff_users = $users_model->get_all_where(array(
            'deleted' => 0,
            'status' => 'active',
            'user_type' => 'staff',
        ))->getResult();
        $recipient_dropdown = array('' => '-');
        foreach ($staff_users as $user) {
            $recipient_dropdown[$user->id] = trim($user->first_name . ' ' . $user->last_name);
        }

        $selected_recipients = array_filter(array_map('trim', explode(',', (string) ($settings_map['alerts_recipient_user_ids'] ?? ''))));

        return $this->template->rander('LicitaIA\\Views\\settings\\index', array(
            'settings' => $settings_map,
            'can_manage_settings' => \LicitaIA\Plugin::canManageSettings($this->login_user),
            'can_manage_ai_settings' => \LicitaIA\Plugin::canManageAiSettings($this->login_user),
            'provider_dropdown' => array(
                'openai' => 'OpenAI',
                'azure_openai' => 'Azure OpenAI',
                'openrouter' => app_lang('licitaia_ai_provider_openrouter'),
                'local' => 'Local',
            ),
            'recipient_dropdown' => $recipient_dropdown,
            'selected_recipients' => $selected_recipients,
        ));
    }

    public function save()
    {
        if (!\LicitaIA\Plugin::canManageSettings($this->login_user) && !\LicitaIA\Plugin::canManageAiSettings($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $recipient_ids = $this->request->getPost('alerts_recipient_user_ids');
        if (!is_array($recipient_ids)) {
            $recipient_ids = array();
        }

        if (\LicitaIA\Plugin::canManageAiSettings($this->login_user)) {
            $ai_payload = array(
                'ai_provider' => trim((string) $this->request->getPost('ai_provider')),
                'ai_model' => trim((string) $this->request->getPost('ai_model')),
                'ai_api_base_url' => trim((string) $this->request->getPost('ai_api_base_url')),
                'ai_api_key' => trim((string) $this->request->getPost('ai_api_key')),
                'ai_enabled' => $this->request->getPost('ai_enabled') ? '1' : '0',
            );

            if ($ai_payload['ai_provider'] === 'openrouter' && $ai_payload['ai_api_base_url'] === '') {
                $ai_payload['ai_api_base_url'] = 'https://openrouter.ai/api/v1';
            }

            foreach ($ai_payload as $name => $value) {
                $this->settings_model->save_setting($name, $value, $name === 'ai_api_key');
            }
        }

        if (\LicitaIA\Plugin::canManageSettings($this->login_user)) {
            $payload = array(
                'reports_enabled' => $this->request->getPost('reports_enabled') ? '1' : '0',
                'checklist_enabled' => $this->request->getPost('checklist_enabled') ? '1' : '0',
                'opportunities_default_status' => trim((string) ($this->request->getPost('opportunities_default_status') ?: 'new')),
                'alerts_enabled' => $this->request->getPost('alerts_enabled') ? '1' : '0',
                'alerts_days_before_opening' => trim((string) ($this->request->getPost('alerts_days_before_opening') ?: '7,3,1')),
                'alerts_days_before_submission' => trim((string) ($this->request->getPost('alerts_days_before_submission') ?: '7,3,1')),
                'alerts_recipient_user_ids' => implode(',', array_map('intval', $recipient_ids)),
                'alerts_email_enabled' => $this->request->getPost('alerts_email_enabled') ? '1' : '0',
                'alerts_whatsapp_enabled' => $this->request->getPost('alerts_whatsapp_enabled') ? '1' : '0',
            );

            foreach ($payload as $name => $value) {
                $this->settings_model->save_setting($name, $value, false);
            }
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }
}
