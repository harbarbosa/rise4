<?php

namespace Purchases\Controllers;

use App\Controllers\Security_Controller;

class Purchases extends Security_Controller
{
    private $Purchases_dashboard_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Purchases_dashboard_model = model('Purchases\\Models\\Purchases_dashboard_model');
    }

    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $company_id = $this->_get_company_id();
        $kpis = $this->Purchases_dashboard_model->get_kpis(array(
            "company_id" => $company_id
        ));

        $view_data = array(
            "kpis" => $kpis,
            "pending_requests" => $this->Purchases_dashboard_model->get_pending_requests(array("company_id" => $company_id), 10),
            "open_orders" => $this->Purchases_dashboard_model->get_open_purchase_orders(array("company_id" => $company_id), 10),
            "can_manage" => $this->_has_manage_permission(),
            "can_approve" => $this->_has_approval_permission(),
            "can_financial_approve" => $this->_has_financial_approval_permission(),
            "login_user_id" => $this->login_user->id
        );

        return $this->template->rander('Purchases\\Views\\dashboard', $view_data);
    }

    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_view') == '1'
            || get_array_value($permissions, 'purchases_manage') == '1'
            || get_array_value($permissions, 'purchases_approve') == '1'
            || get_array_value($permissions, 'purchases_financial_approve') == '1';
    }

    private function _has_manage_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_manage') == '1';
    }

    private function _has_approval_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_approve') == '1';
    }

    private function _has_financial_approval_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'purchases_financial_approve') == '1';
    }

    private function _get_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return $this->login_user->company_id;
        }

        return get_default_company_id();
    }
}
