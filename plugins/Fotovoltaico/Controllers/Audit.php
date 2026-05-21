<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\AuditService;

class Audit extends Security_Controller
{
    private $Audit_logs_model;
    private $Users_model;
    private $AuditService;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        Plugin::ensureSchema();

        if (!Plugin::canViewAudit($this->login_user) && !Plugin::canManageSettings($this->login_user)) {
            app_redirect('forbidden');
        }

        $this->Audit_logs_model = model('Fotovoltaico\\Models\\Audit_logs_model');
        $this->Users_model = model('App\\Models\\Users_model');
        $this->AuditService = new AuditService();
    }

    public function index()
    {
        $view_data = array(
            'can_view_audit' => Plugin::canViewAudit($this->login_user),
            'can_manage_settings' => Plugin::canManageSettings($this->login_user),
            'retention_days' => (int) (get_setting('fotovoltaico_audit_retention_days') ?: 365),
        );

        return $this->template->rander('Fotovoltaico\\Views\\audit\\index', $view_data);
    }

    public function list_data()
    {
        if (!Plugin::canViewAudit($this->login_user) && !Plugin::canManageSettings($this->login_user)) {
            app_redirect('forbidden');
        }

        $options = array(
            'entity_type' => trim((string) $this->request->getPost('entity_type')),
            'action' => trim((string) $this->request->getPost('action')),
            'entity_id' => get_only_numeric_value($this->request->getPost('entity_id')),
        );

        $list_data = $this->Audit_logs_model->get_details($options)->getResult();
        $rows = array();
        foreach ($list_data as $item) {
            $rows[] = array(
                esc($item->created_at ?: ''),
                esc($item->created_by_name ?: ($item->created_by ?: '-')),
                esc($item->action ?: '-'),
                esc($item->entity_type ?: '-'),
                (int) $item->entity_id,
                esc($this->_summarize_payload($item->changes_json ?: $item->new_json ?: '')),
                $this->_badge((int) ($item->success ?? 1)),
            );
        }

        echo json_encode(array('data' => $rows));
    }

    public function cleanup()
    {
        if (!Plugin::canManageSettings($this->login_user)) {
            app_redirect('forbidden');
        }

        $days = (int) get_setting('fotovoltaico_audit_retention_days');
        if ($days <= 0) {
            $days = 365;
        }

        $result = $this->AuditService->purge_old_logs($days);
        echo json_encode(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'result' => $result,
        ));
    }

    private function _summarize_payload($json)
    {
        $data = json_decode((string) $json, true);
        if (!is_array($data) || !$data) {
            return '-';
        }

        $parts = array();
        foreach (array_slice($data, 0, 3, true) as $key => $value) {
            if (is_array($value)) {
                $value = get_array_value($value, 'new');
            }
            $parts[] = $key . '=' . (is_scalar($value) ? $value : json_encode($value));
        }

        return implode('; ', $parts);
    }

    private function _badge($success)
    {
        return $success ? "<span class='badge bg-success'>" . esc(app_lang('yes')) . "</span>" : "<span class='badge bg-danger'>" . esc(app_lang('no')) . "</span>";
    }
}
