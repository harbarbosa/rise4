<?php

namespace GED\Controllers;

use App\Controllers\Security_Controller;
use GED\Libraries\GedNotificationService;

class GedNotifications extends Security_Controller
{
    private $Ged_settings_model;

    public function __construct()
    {
        parent::__construct(false);
        $this->Ged_settings_model = model('GED\\Models\\Ged_settings_model');
    }

    public function run($token = '')
    {
        try {
            $request = \Config\Services::request();
            $posted_token = trim((string) $request->getPost('token'));
            $query_token = trim((string) $request->getGet('token'));
            $token = trim((string) ($token ?: ($posted_token ?: $query_token)));

            if (!$this->_can_run_notifications($token)) {
                return $this->response->setJSON(array(
                    'success' => false,
                    'message' => app_lang('permission_denied'),
                ));
            }

            $service = new GedNotificationService();
            $result = $service->run(array('source' => 'web', 'token' => $token));

            return $this->response->setJSON(array_merge(array(
                'success' => true,
                'message' => 'GED notifications processed.',
            ), $result));
        } catch (\Throwable $e) {
            log_message('error', '[GED] notification run error: ' . $e->getMessage());
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Falha ao executar notificações do GED.',
            ));
        }
    }

    private function _can_run_notifications($token = '')
    {
        if (php_sapi_name() === 'cli') {
            return true;
        }

        if ($this->login_user && ($this->login_user->is_admin || $this->_has_manage_notifications_permission())) {
            return true;
        }

        $saved_token = trim((string) $this->Ged_settings_model->get_value('notifications_token', ''));
        if (!$saved_token) {
            $saved_token = trim((string) $this->Ged_settings_model->get_value('ged_notifications_token', ''));
        }

        return $token !== '' && $saved_token !== '' && hash_equals($saved_token, $token);
    }

    private function _has_manage_notifications_permission()
    {
        if (!$this->login_user) {
            return false;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'ged_manage_notifications') == '1';
    }
}
