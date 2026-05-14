<?php

namespace GED\Controllers;

use App\Controllers\Security_Controller;

abstract class GedBaseController extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
    }

    protected function _permissions()
    {
        return $this->login_user->permissions ?? array();
    }

    protected function _is_admin()
    {
        return $this->login_user && !empty($this->login_user->is_admin);
    }

    protected function _can_access_module()
    {
        if (!$this->login_user) {
            return false;
        }

        if ($this->_is_admin()) {
            return true;
        }

        return $this->_has_any_permission(array(
            'ged_access',
            'ged_view_documents',
            'ged_create_documents',
            'ged_edit_documents',
            'ged_delete_documents',
            'ged_download_documents',
            'ged_manage_document_types',
            'ged_manage_submissions',
            'ged_view_reports',
            'ged_manage_settings',
            'ged_manage_notifications',
        ));
    }

    protected function _has_view_permission()
    {
        return $this->_is_admin() || $this->_has_any_permission(array(
            'ged_access',
            'ged_view_documents',
            'ged_edit_documents',
            'ged_manage_document_types',
            'ged_manage_submissions',
            'ged_view_reports',
            'ged_manage_settings',
            'ged_manage_notifications',
        ));
    }

    protected function _has_create_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_create_documents') == '1';
    }

    protected function _has_edit_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_edit_documents') == '1';
    }

    protected function _has_delete_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_delete_documents') == '1';
    }

    protected function _has_download_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_download_documents') == '1';
    }

    protected function _has_manage_document_types_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_manage_document_types') == '1';
    }

    protected function _has_manage_submissions_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_manage_submissions') == '1';
    }

    protected function _has_view_reports_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_view_reports') == '1';
    }

    protected function _has_manage_settings_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_manage_settings') == '1';
    }

    protected function _has_manage_notifications_permission()
    {
        return $this->_is_admin() || get_array_value($this->_permissions(), 'ged_manage_notifications') == '1';
    }

    protected function _has_any_permission($keys)
    {
        $permissions = $this->_permissions();
        foreach ((array) $keys as $key) {
            if (get_array_value($permissions, $key) == '1') {
                return true;
            }
        }

        return false;
    }

    protected function _json_permission_denied()
    {
        return $this->response->setJSON(array(
            'success' => false,
            'message' => app_lang('permission_denied')
        ));
    }

    protected function _json_success($data = array(), $message = '')
    {
        return $this->response->setJSON(array_merge(array(
            'success' => true,
            'message' => $message
        ), $data));
    }
}
