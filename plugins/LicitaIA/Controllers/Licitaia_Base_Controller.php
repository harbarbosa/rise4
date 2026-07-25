<?php

namespace LicitaIA\Controllers;

use App\Controllers\Security_Controller;
use LicitaIA\Models\Ai_log_model;
use LicitaIA\Models\Alert_log_model;
use LicitaIA\Models\Checklist_item_model;
use LicitaIA\Models\Keyword_model;
use LicitaIA\Models\Opportunity_checklist_model;
use LicitaIA\Models\Opportunity_model;
use LicitaIA\Models\Licitaia_ai_analyses_model;
use LicitaIA\Models\Licitaia_reports_model;
use LicitaIA\Models\Search_log_model;
use LicitaIA\Models\Licitaia_settings_model;
use LicitaIA\Models\Source_model;

abstract class Licitaia_Base_Controller extends Security_Controller
{
    protected Opportunity_model $opportunities_model;
    protected Keyword_model $keywords_model;
    protected Source_model $sources_model;
    protected Licitaia_settings_model $settings_model;
    protected Checklist_item_model $checklist_model;
    protected Opportunity_checklist_model $opportunity_checklist_model;
    protected Ai_log_model $ai_log_model;
    protected Alert_log_model $alert_log_model;
    protected Search_log_model $search_log_model;
    protected Licitaia_ai_analyses_model $ai_analyses_model;
    protected Licitaia_reports_model $reports_model;

    public function __construct()
    {
        parent::__construct(!is_cli());
        \LicitaIA\Plugin::runMigrations();

        $this->opportunities_model = model(Opportunity_model::class);
        $this->keywords_model = model(Keyword_model::class);
        $this->sources_model = model(Source_model::class);
        $this->settings_model = model(Licitaia_settings_model::class);
        $this->checklist_model = model(Checklist_item_model::class);
        $this->opportunity_checklist_model = model(Opportunity_checklist_model::class);
        $this->ai_log_model = model(Ai_log_model::class);
        $this->alert_log_model = model(Alert_log_model::class);
        $this->search_log_model = model(Search_log_model::class);
        $this->ai_analyses_model = model(Licitaia_ai_analyses_model::class);
        $this->reports_model = model(Licitaia_reports_model::class);
    }

    protected function ensureAccess()
    {
        if (!\LicitaIA\Plugin::canAccessModule($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    protected function ensureManageAccess()
    {
        if (
            !\LicitaIA\Plugin::canManageOpportunities($this->login_user)
            && !\LicitaIA\Plugin::canManageKeywords($this->login_user)
            && !\LicitaIA\Plugin::canManageSources($this->login_user)
            && !\LicitaIA\Plugin::canManageChecklist($this->login_user)
            && !\LicitaIA\Plugin::canManageSettings($this->login_user)
        ) {
            app_redirect('forbidden');
        }
    }

    protected function renderPluginView($relative_path, $data = array())
    {
        $relative_path = trim((string) $relative_path, "/\\");
        $view_path = rtrim(PLUGINPATH, '/\\') . DIRECTORY_SEPARATOR . 'LicitaIA' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relative_path) . '.php';

        if (!is_file($view_path)) {
            throw new \RuntimeException('Invalid plugin view file: ' . $view_path);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $view_path;
        return ob_get_clean();
    }

    protected function statusDropdown()
    {
        return array(
            '' => '-',
            'new' => app_lang('licitaia_status_new'),
            'analyzing' => app_lang('licitaia_status_analyzing'),
            'waiting_decision' => app_lang('licitaia_status_waiting_decision'),
            'participate' => app_lang('licitaia_status_participate'),
            'not_participate' => app_lang('licitaia_status_not_participate'),
            'proposal_in_progress' => app_lang('licitaia_status_proposal_in_progress'),
            'sent' => app_lang('licitaia_status_sent'),
            'won' => app_lang('licitaia_status_won'),
            'lost' => app_lang('licitaia_status_lost'),
            'canceled' => app_lang('licitaia_status_canceled'),
        );
    }

    protected function aiStatusDropdown()
    {
        return array(
            '' => '-',
            'pending' => app_lang('licitaia_ai_pending'),
            'processing' => app_lang('licitaia_ai_processing'),
            'completed' => app_lang('licitaia_ai_completed'),
            'failed' => app_lang('licitaia_ai_failed'),
        );
    }

    protected function yesNoDropdown()
    {
        return array(
            '' => '-',
            '1' => app_lang('yes'),
            '0' => app_lang('no'),
        );
    }
}
