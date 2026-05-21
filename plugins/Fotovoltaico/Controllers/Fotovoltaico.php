<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Models\Settings_model;
use Fotovoltaico\Services\Lei14300Service;

class Fotovoltaico extends Security_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        if (!Plugin::canAccessModule($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();
    }

    public function index()
    {
        $this->_ensure_dashboard_access();
        return $this->dashboard();
    }

    public function dashboard()
    {
        $this->_ensure_dashboard_access();
        return $this->_render_module('dashboard');
    }

    public function products()
    {
        $this->_ensure_products_access();
        return $this->_render_module('products');
    }

    public function kits()
    {
        $this->_ensure_kits_access();
        return $this->_render_module('kits');
    }

    public function proposals()
    {
        $this->_ensure_proposals_access();
        return $this->_render_module('proposals');
    }

    public function distributors()
    {
        $this->_ensure_distributors_access();
        return $this->_render_module('distributors');
    }

    public function tariffs()
    {
        $this->_ensure_tariffs_access();
        return $this->_render_module('tariffs');
    }

    public function integrations()
    {
        $this->_ensure_integrations_access();
        return $this->_render_module('integrations');
    }

    public function audit()
    {
        $this->_ensure_audit_access();
        return $this->_render_module('audit');
    }

    public function settings()
    {
        $this->_ensure_settings_access();
        return $this->_render_module('settings');
    }

    public function save_lei14300()
    {
        if (!Plugin::canManageSettings($this->login_user) && !Plugin::canManageProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $service = new Lei14300Service();
        $config = array(
            'start_year' => (int) $this->request->getPost('start_year'),
            'ramp_years' => (int) $this->request->getPost('ramp_years'),
            'fio_b_start_percent' => $this->request->getPost('fio_b_start_percent'),
            'fio_b_end_percent' => $this->request->getPost('fio_b_end_percent'),
            'grid_fee_percent' => $this->request->getPost('grid_fee_percent'),
            'energy_distributed_percent' => $this->request->getPost('energy_distributed_percent'),
            'compensation_factor' => $this->request->getPost('compensation_factor'),
            'full_offset_until' => (int) $this->request->getPost('full_offset_until'),
            'scenarios' => $this->_decode_json($this->request->getPost('scenarios_json')),
        );

        $saved = $service->save_configuration($config);
        echo json_encode(array(
            'success' => (bool) $saved,
            'message' => $saved ? app_lang('record_saved') : app_lang('error_occurred'),
        ));
    }

    private function _render_module($module)
    {
        $modules = array();

        if (Plugin::canViewDashboard($this->login_user)) {
            $modules['dashboard'] = 'fotovoltaico';
        }
        if (Plugin::canViewProducts($this->login_user) || Plugin::canManageProducts($this->login_user)) {
            $modules['products'] = 'fotovoltaico/products';
        }
        if (Plugin::canViewKits($this->login_user) || Plugin::canManageKits($this->login_user)) {
            $modules['kits'] = 'fotovoltaico/kits';
        }
        if (Plugin::canViewProposals($this->login_user) || Plugin::canCreateProposals($this->login_user) || Plugin::canManageProposals($this->login_user) || Plugin::canApproveProposals($this->login_user)) {
            $modules['proposals'] = 'fotovoltaico/proposals';
        }
        if (Plugin::canViewDistributors($this->login_user)) {
            $modules['distributors'] = 'fotovoltaico/distributors';
        }
        if (Plugin::canViewTariffs($this->login_user) || Plugin::canManageTariffs($this->login_user)) {
            $modules['tariffs'] = 'fotovoltaico/tariffs';
        }
        if (Plugin::canViewIntegrations($this->login_user) || Plugin::canManageIntegrations($this->login_user)) {
            $modules['integrations'] = 'fotovoltaico/integrations';
        }
        if (Plugin::canViewAudit($this->login_user)) {
            $modules['audit'] = 'fotovoltaico/audit';
        }
        if (Plugin::canManageSettings($this->login_user)) {
            $modules['settings'] = 'fotovoltaico/settings';
        }

        $view_data = array(
            'page_title' => app_lang('fotovoltaico_' . $module),
            'page_description' => app_lang('fotovoltaico_' . $module . '_description'),
            'active_module' => $module,
            'available_modules' => $modules
        );

        return $this->template->rander('Fotovoltaico\\Views\\index', $view_data);
    }

    private function _ensure_dashboard_access()
    {
        if (!Plugin::canViewDashboard($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _ensure_products_access()
    {
        if (!Plugin::canViewProducts($this->login_user) && !Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _ensure_kits_access()
    {
        if (!Plugin::canViewKits($this->login_user) && !Plugin::canManageKits($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _ensure_proposals_access()
    {
        if (!Plugin::canViewProposals($this->login_user) && !Plugin::canCreateProposals($this->login_user) && !Plugin::canManageProposals($this->login_user) && !Plugin::canApproveProposals($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _ensure_distributors_access()
    {
        if (!Plugin::canViewDistributors($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _ensure_tariffs_access()
    {
        if (!Plugin::canViewTariffs($this->login_user) && !Plugin::canManageTariffs($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _ensure_integrations_access()
    {
        if (!Plugin::canViewIntegrations($this->login_user) && !Plugin::canManageIntegrations($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _ensure_audit_access()
    {
        if (!Plugin::canViewAudit($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _ensure_settings_access()
    {
        if (!Plugin::canManageSettings($this->login_user)) {
            app_redirect('forbidden');
        }
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
}
