<?php

namespace LicitaIA\Controllers;

class Sources extends Licitaia_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();

        return $this->template->rander('LicitaIA\\Views\\sources\\index', array(
            'can_manage' => \LicitaIA\Plugin::canManageSources($this->login_user),
            'source_type_dropdown' => $this->sources_model->get_source_types_dropdown(),
            'frequency_dropdown' => $this->sources_model->get_frequency_dropdown(),
        ));
    }

    public function list_data()
    {
        $this->ensureAccess();

        $options = array(
            'search' => trim((string) $this->request->getPost('search')),
            'source_type' => trim((string) $this->request->getPost('source_type')),
            'active' => $this->request->getPost('active'),
        );

        $rows = $this->sources_model->get_details($options)->getResult();
        $result = array();
        foreach ($rows as $row) {
            $result[] = $this->makeRow($row);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        if (!\LicitaIA\Plugin::canManageSources($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $id;
        if (!$id) {
            $id = (int) $this->request->getPost('id');
        }

        $model_info = $id ? $this->sources_model->get_details(array('id' => $id))->getRow() : (object) array(
            'id' => 0,
            'name' => '',
            'source_type' => 'pncp',
            'url' => '',
            'city' => '',
            'state' => '',
            'search_frequency' => 'manual',
            'base_url' => '',
            'api_endpoint' => '',
            'active' => 1,
            'notes' => '',
            'last_search_at' => '',
            'last_search_by' => '',
        );

        if ($id && !$model_info) {
            show_404();
        }

        if ($model_info && empty($model_info->url) && !empty($model_info->base_url)) {
            $model_info->url = $model_info->base_url;
        }
        if ($model_info && ($model_info->source_type ?? '') === 'portal') {
            $model_info->source_type = 'portal_municipal';
        }

        return $this->template->view('LicitaIA\\Views\\sources\\modal_form', array(
            'model_info' => $model_info,
            'source_type_dropdown' => $this->sources_model->get_source_types_dropdown(),
            'frequency_dropdown' => $this->sources_model->get_frequency_dropdown(),
        ));
    }

    public function save()
    {
        if (!\LicitaIA\Plugin::canManageSources($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        $source_type = trim((string) $this->request->getPost('source_type'));
        $search_frequency = trim((string) $this->request->getPost('search_frequency'));
        $url = trim((string) $this->request->getPost('url'));

        if ($name === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('licitaia_required_source')));
        }

        $allowed_types = array_keys($this->sources_model->get_source_types_dropdown());
        if ($source_type === 'portal') {
            $source_type = 'portal_municipal';
        }
        if (!in_array($source_type, $allowed_types, true)) {
            $source_type = 'outro';
        }

        $allowed_frequencies = array_keys($this->sources_model->get_frequency_dropdown());
        if (!in_array($search_frequency, $allowed_frequencies, true)) {
            $search_frequency = 'manual';
        }

        $data = array(
            'name' => clean_data($name),
            'source_type' => $source_type,
            'url' => clean_data($url),
            'base_url' => clean_data($url),
            'city' => clean_data($this->request->getPost('city')),
            'state' => strtoupper(substr(trim((string) $this->request->getPost('state')), 0, 2)),
            'search_frequency' => $search_frequency,
            'active' => $this->request->getPost('active') ? 1 : 0,
            'notes' => clean_data($this->request->getPost('notes')),
            'updated_at' => get_my_local_time(),
        );

        if (!$id) {
            $data['created_by'] = (int) $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->sources_model->ci_save($data, $id);
        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array(
            'success' => true,
            'id' => $save_id,
            'message' => app_lang('record_saved'),
        ));
    }

    public function toggle_status()
    {
        if (!\LicitaIA\Plugin::canManageSources($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $active = (int) $this->request->getPost('active');
        $success = $this->sources_model->set_active($id, $active);

        return $this->response->setJSON(array(
            'success' => (bool) $success,
            'message' => $success ? app_lang('record_saved') : app_lang('error_occurred'),
        ));
    }

    public function test($id = 0)
    {
        return $this->runSourceAction($id, 'test');
    }

    public function search_now($id = 0)
    {
        return $this->runSourceAction($id, 'search_now');
    }

    public function delete()
    {
        if (!\LicitaIA\Plugin::canDeleteRecords($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $success = $id > 0 ? $this->sources_model->delete($id) : false;

        return $this->response->setJSON(array(
            'success' => (bool) $success,
            'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred'),
        ));
    }

    private function runSourceAction($id, $action)
    {
        if ($action === 'test' || $action === 'search_now') {
            if (!\LicitaIA\Plugin::canRunSearch($this->login_user)) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
            }
        } elseif (!\LicitaIA\Plugin::canManageSources($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $id;
        if (!$id) {
            $id = (int) $this->request->getPost('id');
        }

        $source = $this->sources_model->get_details(array('id' => $id))->getRow();
        if (!$source) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $message = $action === 'test'
            ? app_lang('licitaia_source_tested')
            : app_lang('licitaia_source_search_queued');

        $this->sources_model->update_last_search($id, $source->name);

        $this->search_log_model->log_search(array(
            'source_id' => $id,
            'query_text' => $source->name,
            'filters_json' => array(
                'source_type' => $source->source_type,
                'city' => $source->city,
                'state' => $source->state,
                'search_frequency' => $source->search_frequency,
                'action' => $action,
            ),
            'results_count' => 0,
            'status' => $action === 'test' ? 'structure_only' : 'queued',
            'response_json' => array(
                'message' => $message,
                'source_id' => $id,
                'action' => $action,
            ),
            'created_by' => $this->login_user->id,
            'started_at' => get_my_local_time(),
            'finished_at' => get_my_local_time(),
        ));

        return $this->response->setJSON(array(
            'success' => true,
            'message' => $message,
        ));
    }

    private function makeRow($data)
    {
        $options = '';
        if (\LicitaIA\Plugin::canManageSources($this->login_user)) {
            $options .= modal_anchor(get_uri('licitaia/sources/modal_form/' . (int) $data->id), "<i data-feather='edit' class='icon-16'></i>", array('class' => 'action-icon', 'title' => app_lang('edit')));
        }

        if (\LicitaIA\Plugin::canRunSearch($this->login_user)) {
            $options .= js_anchor("<i data-feather='eye' class='icon-16'></i>", array(
                'title' => app_lang('licitaia_source_test'),
                'class' => 'action-icon text-info js-source-action',
                'data-id' => (int) $data->id,
                'data-action-url' => get_uri('licitaia/sources/test/' . (int) $data->id),
            ));
            $options .= js_anchor("<i data-feather='refresh-cw' class='icon-16'></i>", array(
                'title' => app_lang('licitaia_source_search_now'),
                'class' => 'action-icon text-primary js-source-action',
                'data-id' => (int) $data->id,
                'data-action-url' => get_uri('licitaia/sources/search_now/' . (int) $data->id),
            ));
        }

        if (\LicitaIA\Plugin::canManageSources($this->login_user)) {
            $options .= js_anchor("<i data-feather='" . (!empty($data->active) ? 'eye-off' : 'eye') . "' class='icon-16'></i>", array(
                'title' => !empty($data->active) ? app_lang('deactivate') : app_lang('activate'),
                'class' => 'action-icon text-' . (!empty($data->active) ? 'warning' : 'success') . ' js-source-toggle-status',
                'data-id' => (int) $data->id,
                'data-active' => !empty($data->active) ? 0 : 1,
                'data-action-url' => get_uri('licitaia/sources/toggle_status'),
            ));
        }

        if (\LicitaIA\Plugin::canDeleteRecords($this->login_user)) {
            $options .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                'class' => 'action-icon text-danger',
                'title' => app_lang('delete'),
                'data-id' => (int) $data->id,
                'data-action-url' => get_uri('licitaia/sources/delete'),
                'data-action' => 'delete-confirmation',
            ));
        }

        $source_type = $this->formatSourceType($data->source_type ?? '');
        $frequency = $this->formatFrequency($data->search_frequency ?? '');
        $status_label = !empty($data->active) ? '<span class="badge bg-success">' . esc(app_lang('active')) . '</span>' : '<span class="badge bg-secondary">' . esc(app_lang('inactive')) . '</span>';
        $last_search = !empty($data->last_search_at) ? esc(format_to_datetime($data->last_search_at, false)) : '-';

        return array(
            esc($data->name ?: '-'),
            '<span class="badge bg-light text-dark border">' . esc($source_type) . '</span>',
            esc($data->url ?: ($data->base_url ?: '-')),
            esc($data->city ?: '-'),
            esc($data->state ?: '-'),
            esc($frequency),
            $last_search,
            $status_label,
            $options,
        );
    }

    private function formatSourceType($source_type)
    {
        if ($source_type === 'portal') {
            $source_type = 'portal_municipal';
        }

        if ($source_type === 'paradigma') {
            return 'Paradigma / SESCSP';
        }

        $dropdown = $this->sources_model->get_source_types_dropdown();
        return get_array_value($dropdown, $source_type, $source_type ?: '-');
    }

    private function formatFrequency($frequency)
    {
        $dropdown = $this->sources_model->get_frequency_dropdown();
        return get_array_value($dropdown, $frequency, $frequency ?: '-');
    }
}
