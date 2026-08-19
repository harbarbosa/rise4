<?php

namespace LaudosTecnicos\Controllers;

use LaudosTecnicos\Models\LaudoAuditLogs_model;
use LaudosTecnicos\Models\LaudoPlatform_model;

class LaudosTecnicos extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();

        $platform_model = model(LaudoPlatform_model::class);
        $audit_model = model(LaudoAuditLogs_model::class);
        $view_data['settings'] = $this->settings_model->get_all_settings_with_defaults();
        $view_data['stats'] = (array) $this->laudos_model->get_dashboard_stats();
        $view_data['cards'] = laudostecnicos_dashboard_status_cards($view_data['stats']);
        $view_data['recent_laudos'] = $this->laudos_model->get_recent_laudos(5);
        $view_data['nc_stats'] = $this->nonconformities_model->get_dashboard_stats();
        $view_data['plan_stats'] = $this->action_plans_model->get_dashboard_stats();
        $view_data['platform_summary'] = $platform_model->get_report_summary();
        $view_data['recent_activity'] = array_slice($audit_model->get_details(array())->getResult(), 0, 10);
        $view_data['can_manage_settings'] = \LaudosTecnicos\Plugin::canManageSettings($this->login_user);
        $view_data['can_view_laudos'] = \LaudosTecnicos\Plugin::canViewLaudos($this->login_user);

        return $this->template->rander('LaudosTecnicos\\Views\\dashboard\\index', $view_data);
    }
}
