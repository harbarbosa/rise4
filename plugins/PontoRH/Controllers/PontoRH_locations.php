<?php

namespace PontoRH\Controllers;

class PontoRH_locations extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureLocationsAccess();

        $view_data['can_manage'] = \PontoRH\Plugin::canManageLocations($this->login_user);
        $view_data['status_dropdown'] = array(
            '' => '-',
            '1' => app_lang('active'),
            '0' => app_lang('inactive'),
        );

        return $this->template->rander('PontoRH\\Views\\locations\\index', $view_data);
    }

    public function list_data()
    {
        $this->ensureLocationsAccess();

        $rows = array();
        foreach ($this->locations_model->get_details(array(
            'search' => clean_data($this->request->getPost('search')),
            'active' => clean_data($this->request->getPost('active')),
        ))->getResult() as $location) {
            $rows[] = $this->_make_row($location);
        }

        echo json_encode(array('data' => $rows));
    }

    public function modal_form($id = 0)
    {
        $this->ensureLocationsAccess();

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id) {
            $view_data['model_info'] = $this->locations_model->get_one_with_details($id);
            if (!$view_data['model_info']) {
                app_redirect('forbidden');
            }
        } else {
            $view_data['model_info'] = (object) array(
                'id' => 0,
                'name' => '',
                'address' => '',
                'latitude' => 0,
                'longitude' => 0,
                'radius_meters' => 200,
                'active' => 1,
            );
        }

        $view_data['google_maps_api_key'] = trim((string) $this->settings_model->get_setting('google_maps_api_key', ''));

        return $this->renderPluginView('locations/modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureLocationsAccess();

        $this->validate_submitted_data(array(
            'name' => 'required',
        ));

        $id = (int) $this->request->getPost('id');
        $before = $id ? $this->locations_model->get_one_with_details($id) : null;
        if ($id && !$before) {
            app_redirect('forbidden');
        }

        $latitude = trim((string) $this->request->getPost('latitude'));
        $longitude = trim((string) $this->request->getPost('longitude'));
        $data = array(
            'team_member_id' => null,
            'user_id' => (int) $this->login_user->id,
            'name' => clean_data($this->request->getPost('name')),
            'address' => clean_data($this->request->getPost('address')),
            'latitude' => $latitude !== '' ? (float) str_replace(',', '.', $latitude) : 0,
            'longitude' => $longitude !== '' ? (float) str_replace(',', '.', $longitude) : 0,
            'radius_meters' => (int) $this->request->getPost('radius_meters'),
            'ip_address' => $this->request->getIPAddress(),
            'source' => 'manual',
            'status' => $this->request->getPost('active') ? 'active' : 'inactive',
            'hash' => hash('sha256', implode('|', array(
                clean_data($this->request->getPost('name')),
                $latitude,
                $longitude,
                microtime(true),
            ))),
            'active' => $this->request->getPost('active') ? 1 : 0,
            'deleted' => 0,
        );

        if (!$id) {
            $data['created_by'] = (int) $this->login_user->id;
            $data['created_at'] = get_current_utc_time();
        }
        $data['updated_at'] = get_current_utc_time();

        try {
            $save_id = $this->locations_model->ci_save($data, $id);
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Location save failed: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        if (!$save_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $location = $this->locations_model->get_one_with_details($save_id);
        $this->logAudit(
            'pontorh_locations',
            $save_id,
            $id ? 'update' : 'create',
            $id ? app_lang('pontorh_location_updated') : app_lang('pontorh_location_created'),
            array('before' => $before, 'after' => $location),
            (int) $this->login_user->id
        );

        echo json_encode(array(
            'success' => true,
            'data' => $this->_make_row($location),
            'id' => $save_id,
            'message' => app_lang('record_saved'),
        ));
    }

    public function details($id = 0)
    {
        $this->ensureLocationsAccess();

        $location = $this->locations_model->get_one_with_details((int) $id);
        if (!$location) {
            app_redirect('forbidden');
        }

        $assignments = $this->location_assignments_model->get_details(array(
            'location_id' => (int) $id,
        ))->getResult();

        return $this->template->rander('PontoRH\\Views\\locations\\details', array(
            'location' => $location,
            'assignments' => $assignments,
            'can_manage' => \PontoRH\Plugin::canManageLocations($this->login_user),
            'team_members_dropdown' => $this->teamMembersDropdown(true, 'all'),
        ));
    }

    public function view_modal($id = 0)
    {
        $this->ensureLocationsAccess();

        $location = $this->locations_model->get_one_with_details((int) $id);
        if (!$location) {
            app_redirect('forbidden');
        }

        $assignments = $this->location_assignments_model->get_details(array(
            'location_id' => (int) $id,
        ))->getResult();

        return $this->renderPluginView('locations/modal_view', array(
            'location' => $location,
            'assignments' => $assignments,
            'can_manage' => \PontoRH\Plugin::canManageLocations($this->login_user),
        ));
    }

    public function assignment_modal($id = 0)
    {
        $this->ensureLocationsAccess();

        $location_id = $id ? (int) $id : (int) ($this->request->getPost('location_id') ?: $this->request->getPost('id'));
        $view_data = array(
            'location_id' => $location_id,
            'location' => $location_id ? $this->locations_model->get_one_with_details($location_id) : null,
            'model_info' => (object) array(
                'id' => 0,
                'location_id' => $location_id,
                'week_start' => date('Y-m-01'),
                'week_end' => date('Y-m-t'),
                'active' => 1,
                'notes' => '',
            ),
            'team_members_dropdown' => $this->teamMembersDropdown(true, 'all'),
        );

        return $this->renderPluginView('locations/assignment_modal_form', $view_data);
    }

    public function assignment_save()
    {
        $this->ensureLocationsAccess();

        $location_id = (int) ($this->request->getPost('location_id') ?: $this->request->getPost('id'));
        $location = $this->locations_model->get_one_with_details($location_id);
        if (!$location) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $team_member_ids = $this->request->getPost('team_member_ids');
        if (!is_array($team_member_ids)) {
            $team_member_ids = array($team_member_ids);
        }
        $team_member_ids = array_values(array_unique(array_filter(array_map('intval', $team_member_ids))));
        if (!$team_member_ids) {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $week_start = $this->service->normalizeDate($this->request->getPost('week_start'));
        $week_end = $this->service->normalizeDate($this->request->getPost('week_end'));
        if (!$week_start || !$week_end || strtotime($week_end) < strtotime($week_start)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $notes = clean_data($this->request->getPost('notes'));
        $success = $this->location_assignments_model->sync_assignments(
            $location_id,
            $team_member_ids,
            $week_start,
            $week_end,
            (int) $this->login_user->id,
            $this->request->getPost('active') ? 1 : 0,
            $notes
        );

        if (!$success) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $this->logAudit(
            'pontorh_location_assignments',
            $location_id,
            'create',
            app_lang('pontorh_location_assignment_created'),
            array(
                'location_id' => $location_id,
                'team_member_ids' => $team_member_ids,
                'week_start' => $week_start,
                'week_end' => $week_end,
            ),
            (int) $this->login_user->id
        );

        echo json_encode(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function assignment_delete()
    {
        $this->ensureLocationsAccess();

        $id = (int) $this->request->getPost('id');
        $assignment = $this->location_assignments_model->get_one_with_details($id);
        if (!$assignment) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $success = $this->location_assignments_model->ci_save(array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        ), $id);

        if ($success) {
            $this->logAudit(
                'pontorh_location_assignments',
                $id,
                'delete',
                app_lang('delete'),
                array('before' => $assignment, 'after' => null),
                (int) $this->login_user->id
            );
        }

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function delete()
    {
        $this->ensureLocationsAccess();

        $id = (int) $this->request->getPost('id');
        $location = $this->locations_model->get_one_with_details($id);
        if (!$location) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $success = $this->locations_model->ci_save(array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        ), $id);

        if ($success) {
            $this->location_assignments_model->delete_assignments_by_location($id);
            $this->logAudit(
                'pontorh_locations',
                $id,
                'delete',
                app_lang('pontorh_location_deleted'),
                array('before' => $location, 'after' => null),
                (int) $this->login_user->id
            );
        }

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('pontorh_location_deleted') : app_lang('error_occurred')));
    }

    private function _make_row($location)
    {
        $status = !empty($location->active)
            ? '<span class="badge bg-success">' . app_lang('active') . '</span>'
            : '<span class="badge bg-secondary">' . app_lang('inactive') . '</span>';

        $actions = '';
        if (\PontoRH\Plugin::canManageLocations($this->login_user)) {
            $actions .= modal_anchor(get_uri('pontorh/locais/view_modal'), "<i data-feather='eye' class='icon-14'></i>", array(
                'class' => 'action-icon',
                'title' => app_lang('view_details'),
                'data-post-id' => $location->id,
                'data-modal-lg' => '1',
            ));
            $actions .= modal_anchor(get_uri('pontorh/locais/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array(
                'class' => 'action-icon',
                'title' => app_lang('edit'),
                'data-post-id' => $location->id,
                'data-modal-lg' => '1',
            ));
            $actions .= modal_anchor(get_uri('pontorh/locais/assignment_modal'), "<i data-feather='link-2' class='icon-14'></i>", array(
                'class' => 'action-icon',
                'title' => app_lang('pontorh_location_assignments'),
                'data-post-id' => $location->id,
                'data-modal-lg' => '1',
            ));
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array(
                'class' => 'action-icon text-danger',
                'title' => app_lang('delete'),
                'data-id' => $location->id,
                'data-action-url' => get_uri('pontorh/locais/delete'),
                'data-action' => 'delete-confirmation',
            ));
        }

        return array(
            esc($location->name),
            esc($location->address ?: '-'),
            esc((string) ($location->latitude ?? '0')),
            esc((string) ($location->longitude ?? '0')),
            esc((string) ($location->radius_meters ?? 0)),
            $status,
            $actions,
        );
    }

    protected function ensureLocationsAccess()
    {
        if (!\PontoRH\Plugin::canManageLocations($this->login_user)) {
            $this->auditDeniedAccess('locations');
            app_redirect('forbidden');
        }
    }
}
