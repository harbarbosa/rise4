<?php

namespace WhatsAppNotifications\Controllers;

use App\Controllers\Security_Controller;
use App\Models\Settings_model;
use WhatsAppNotifications\Libraries\WhatsAppNotificationsService;

class WhatsAppNotificationsSettings extends Security_Controller
{
    public $Settings_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_admin();
        $this->Settings_model = new Settings_model();
    }

    public function index()
    {
        $view_data = array(
            "enabled" => (bool) get_setting("whatsapp.enabled"),
            "api_url" => get_setting("whatsapp.apiUrl"),
            "token" => get_setting("whatsapp.token"),
            "instance_id" => get_setting("whatsapp.id") ?: "260687"
        );

        return $this->template->rander("WhatsAppNotifications\\Views\\settings", $view_data);
    }

    public function save()
    {
        $settings = array(
            "whatsapp.enabled" => $this->request->getPost("enabled") ? "1" : "",
            "whatsapp.apiUrl" => trim((string) $this->request->getPost("api_url")),
            "whatsapp.token" => trim((string) $this->request->getPost("token")),
            "whatsapp.id" => trim((string) $this->request->getPost("instance_id"))
        );

        foreach ($settings as $key => $value) {
            $this->Settings_model->save_setting($key, $value);
        }

        $this->session->setFlashdata("success_message", "Configuracoes do WhatsApp salvas.");
        return app_redirect("whatsapp_notifications_settings");
    }

    public function connect_session()
    {
        return $this->respond_gateway_action((new WhatsAppNotificationsService())->connect_session());
    }

    public function session_status()
    {
        return $this->respond_gateway_action((new WhatsAppNotificationsService())->get_session_status());
    }

    public function session_qr()
    {
        return $this->respond_gateway_action((new WhatsAppNotificationsService())->get_session_qr());
    }

    public function disconnect_session()
    {
        return $this->respond_gateway_action((new WhatsAppNotificationsService())->disconnect_session());
    }

    private function respond_gateway_action(array $result)
    {
        $status_code = (int) get_array_value($result, "status_code");
        if (!$status_code) {
            $status_code = get_array_value($result, "success") ? 200 : 500;
        }

        return $this->response->setStatusCode($status_code)->setJSON(get_array_value($result, "data", array()));
    }
}
