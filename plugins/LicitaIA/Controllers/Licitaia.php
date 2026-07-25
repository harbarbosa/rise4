<?php

namespace LicitaIA\Controllers;

class Licitaia extends Licitaia_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();

        $summary = (array) $this->opportunities_model->get_dashboard_summary();
        $status_counts = $this->opportunities_model->count_by_status();
        $recent_opportunities = $this->opportunities_model->get_recent_opportunities(8);
        $due_soon = $this->opportunities_model->get_due_soon(7);
        $pipeline_total = (int) get_array_value($status_counts, 'proposal_in_progress', 0)
            + (int) get_array_value($status_counts, 'sent', 0);

        $chart = array(
            'labels' => array(
                app_lang('licitaia_status_new'),
                app_lang('licitaia_status_analyzing'),
                app_lang('licitaia_status_participate'),
                app_lang('licitaia_status_not_participate'),
                app_lang('licitaia_status_won'),
                app_lang('licitaia_status_lost'),
            ),
            'data' => array(
                (int) get_array_value($status_counts, 'new', 0),
                (int) get_array_value($status_counts, 'analyzing', 0),
                (int) get_array_value($status_counts, 'participate', 0),
                (int) get_array_value($status_counts, 'not_participate', 0),
                (int) get_array_value($status_counts, 'won', 0),
                (int) get_array_value($status_counts, 'lost', 0),
            ),
            'colors' => array('#0d6efd', '#fd7e14', '#198754', '#adb5bd', '#20c997', '#dc3545'),
        );

        $view_data = array(
            'summary' => $summary,
            'status_counts' => $status_counts,
            'recent_opportunities' => $recent_opportunities,
            'due_soon' => $due_soon,
            'pipeline_total' => $pipeline_total,
            'chart' => $chart,
            'can_manage' => \LicitaIA\Plugin::canManageOpportunities($this->login_user),
            'can_manage_settings' => \LicitaIA\Plugin::canManageSettings($this->login_user),
        );

        return $this->template->rander('LicitaIA\\Views\\dashboard\\index', $view_data);
    }
}
