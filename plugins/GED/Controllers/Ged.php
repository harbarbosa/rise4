<?php

namespace GED\Controllers;

class Ged extends GedBaseController
{
    public function index()
    {
        if (!$this->_can_access_dashboard()) {
            app_redirect('forbidden');
        }

        $documents_model = model('GED\\Models\\Ged_documents_model');
        helper('ged_expiration');

        $document_stats = $documents_model->get_dashboard_kpis();
        $view_data = array(
            'kpis' => $document_stats,
            'recent_documents_expiring' => $documents_model->get_expiring_documents(30, 5),
            'recent_documents_expired' => $documents_model->get_expired_documents(5),
            'can_manage_documents' => $this->_has_create_permission() || $this->_has_edit_permission(),
            'can_view_documents' => $this->_has_view_permission(),
            'can_manage_settings' => $this->_has_manage_settings_permission(),
            'can_manage_notifications' => $this->_has_manage_notifications_permission(),
        );

        return $this->template->rander('GED\\Views\\dashboard\\index', $view_data);
    }

    private function _can_access_dashboard()
    {
        if ($this->_is_admin()) {
            return true;
        }

        return get_array_value($this->_permissions(), 'ged_access') == '1';
    }
}
