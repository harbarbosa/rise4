<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\AuditService;
use Fotovoltaico\Services\SupplierIntegrationService;

class Integrations extends Security_Controller
{
    private $service;
    private $AuditService;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canViewIntegrations($this->login_user) && !Plugin::canManageIntegrations($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();

        $this->service = new SupplierIntegrationService();
        $this->AuditService = new AuditService();
    }

    public function index()
    {
        $view_data = array(
            'can_manage_integrations' => Plugin::canManageIntegrations($this->login_user),
            'can_view_integrations' => Plugin::canViewIntegrations($this->login_user),
            'providers' => $this->service->get_provider_definitions(),
            'configuration' => $this->service->get_configuration(),
        );

        return $this->template->rander('Fotovoltaico\\Views\\integrations\\index', $view_data);
    }

    public function save_settings()
    {
        if (!Plugin::canManageIntegrations($this->login_user)) {
            app_redirect('forbidden');
        }

        $config = array(
            'provider_key' => trim((string) $this->request->getPost('provider_key')),
            'base_url' => trim((string) $this->request->getPost('base_url')),
            'auth_type' => trim((string) $this->request->getPost('auth_type')),
            'token' => trim((string) $this->request->getPost('token')),
            'username' => trim((string) $this->request->getPost('username')),
            'password' => trim((string) $this->request->getPost('password')),
            'healthcheck_endpoint' => trim((string) $this->request->getPost('healthcheck_endpoint')),
            'products_endpoint' => trim((string) $this->request->getPost('products_endpoint')),
            'kits_endpoint' => trim((string) $this->request->getPost('kits_endpoint')),
            'freight_endpoint' => trim((string) $this->request->getPost('freight_endpoint')),
            'quote_endpoint' => trim((string) $this->request->getPost('quote_endpoint')),
            'timeout_seconds' => (int) $this->request->getPost('timeout_seconds'),
            'cache_ttl_seconds' => (int) $this->request->getPost('cache_ttl_seconds'),
            'notes' => trim((string) $this->request->getPost('notes')),
        );

        $saved = $this->service->save_configuration($config);
        $this->_audit('integration', 0, 'integration_settings_saved', array(), array(
            'provider_key' => $config['provider_key'],
            'base_url' => $config['base_url'],
            'auth_type' => $config['auth_type'],
        ));
        echo json_encode(array(
            'success' => (bool) $saved,
            'message' => $saved ? app_lang('record_saved') : app_lang('error_occurred'),
        ));
    }

    public function test_connection()
    {
        if (!Plugin::canManageIntegrations($this->login_user)) {
            app_redirect('forbidden');
        }

        $provider_key = trim((string) $this->request->getPost('provider_key'));
        $result = $this->service->test_connection(array(
            'provider_key' => $provider_key,
            'created_by' => $this->login_user->id,
        ));
        $this->_audit('integration', 0, 'integration_connection_tested', array(), array(
            'provider_key' => $provider_key,
            'success' => get_array_value($result, 'success'),
            'http_status' => get_array_value($result, 'http_status'),
        ));

        echo json_encode($result);
    }

    public function get_quote()
    {
        if (!Plugin::canManageIntegrations($this->login_user) && !Plugin::canViewIntegrations($this->login_user)) {
            app_redirect('forbidden');
        }

        $input = array(
            'provider_key' => trim((string) $this->request->getPost('provider_key')),
            'client_zip' => trim((string) $this->request->getPost('client_zip')),
            'city' => trim((string) $this->request->getPost('city')),
            'state' => trim((string) $this->request->getPost('state')),
            'items' => $this->_decode_json($this->request->getPost('items_json')),
            'kit_id' => (int) $this->request->getPost('kit_id'),
            'proposal_id' => (int) $this->request->getPost('proposal_id'),
            'created_by' => $this->login_user->id,
        );

        $result = $this->service->get_quote($input);
        $this->_audit('integration', 0, 'integration_quote_requested', array(), array(
            'provider_key' => $input['provider_key'],
            'kit_id' => $input['kit_id'],
            'proposal_id' => $input['proposal_id'],
            'success' => get_array_value($result, 'success'),
            'http_status' => get_array_value($result, 'http_status'),
        ));
        echo json_encode($result);
    }

    public function logs_list()
    {
        if (!Plugin::canViewIntegrations($this->login_user) && !Plugin::canManageIntegrations($this->login_user)) {
            app_redirect('forbidden');
        }

        $filters = array(
            'provider' => trim((string) $this->request->getPost('provider')),
            'http_status' => trim((string) $this->request->getPost('http_status')),
        );

        $logs = $this->service->list_logs($filters);
        $rows = array();
        foreach ($logs as $log) {
            $rows[] = array(
                esc($log->created_at ?: ''),
                esc($log->provider ?: ''),
                esc($log->endpoint ?: ''),
                esc($log->method ?: ''),
                (int) $log->http_status,
                $log->cache_hit ? app_lang('yes') : app_lang('no'),
                $log->success ? app_lang('yes') : app_lang('no'),
                esc($log->error_message ?: '-'),
            );
        }

        echo json_encode(array('data' => $rows));
    }

    private function _decode_json($json_text)
    {
        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return array();
        }

        $decoded = json_decode($json_text, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : array();
    }

    private function _audit($entity_type, $entity_id, $action, $old_data = array(), $new_data = array())
    {
        try {
            return $this->AuditService->record($entity_type, $entity_id, $action, $old_data, $new_data, array(
                'created_by' => $this->login_user->id,
            ));
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Audit error: ' . $e->getMessage());
            return false;
        }
    }
}
