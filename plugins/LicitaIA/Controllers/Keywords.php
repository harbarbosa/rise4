<?php

namespace LicitaIA\Controllers;

class Keywords extends Licitaia_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();

        $type_dropdown = array(
            '' => '-',
            'include' => app_lang('licitaia_keyword_type_include'),
            'exclude' => app_lang('licitaia_keyword_type_exclude'),
        );

        $category_dropdown = array('' => '-');
        foreach ($this->keywords_model->get_categories_dropdown() as $value => $label) {
            $category_dropdown[$value] = $label;
        }

        return $this->template->rander('LicitaIA\\Views\\keywords\\index', array(
            'can_manage' => \LicitaIA\Plugin::canManageKeywords($this->login_user),
            'type_dropdown' => $type_dropdown,
            'category_dropdown' => $category_dropdown,
        ));
    }

    public function list_data()
    {
        $this->ensureAccess();

        $options = array(
            'keyword_type' => $this->request->getPost('keyword_type'),
            'category' => $this->request->getPost('category'),
            'search' => $this->request->getPost('search'),
        );

        $rows = $this->keywords_model->get_details($options)->getResult();
        $result = array();
        foreach ($rows as $row) {
            $result[] = $this->makeRow($row);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        if (!\LicitaIA\Plugin::canManageKeywords($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        $model_info = $id ? $this->keywords_model->get_one($id) : (object) array(
            'id' => 0,
            'keyword' => '',
            'category' => '',
            'keyword_type' => 'include',
            'weight' => 0,
            'active' => 1,
        );

        return $this->template->view('LicitaIA\\Views\\keywords\\modal_form', array(
            'model_info' => $model_info,
            'type_dropdown' => array(
                'include' => app_lang('licitaia_keyword_type_include'),
                'exclude' => app_lang('licitaia_keyword_type_exclude'),
            ),
        ));
    }

    public function save()
    {
        if (!\LicitaIA\Plugin::canManageKeywords($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $keyword = trim((string) $this->request->getPost('keyword'));
        $keyword_type = trim((string) $this->request->getPost('keyword_type'));
        $category = trim((string) $this->request->getPost('category'));

        if ($keyword === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('licitaia_required_keyword')));
        }

        if (!in_array($keyword_type, array('include', 'exclude'), true)) {
            $keyword_type = 'include';
        }

        $data = array(
            'keyword' => clean_data($keyword),
            'category' => clean_data($category),
            'keyword_type' => $keyword_type,
            'weight' => max(0, (int) $this->request->getPost('weight')),
            'active' => $this->request->getPost('active') ? 1 : 0,
            'deleted' => 0,
            'updated_at' => get_my_local_time(),
        );

        if (!$id) {
            $data['created_by'] = (int) $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->keywords_model->ci_save($data, $id);
        if ($save_id) {
            return $this->response->setJSON(array(
                'success' => true,
                'data' => $this->makeRow($this->keywords_model->get_one($save_id)),
                'id' => $save_id,
                'message' => app_lang('record_saved'),
            ));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function toggle_status()
    {
        if (!\LicitaIA\Plugin::canManageKeywords($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $active = (int) $this->request->getPost('active');
        $success = $this->keywords_model->set_active($id, $active);

        return $this->response->setJSON(array(
            'success' => (bool) $success,
            'message' => $success ? app_lang('record_saved') : app_lang('error_occurred'),
        ));
    }

    public function delete()
    {
        if (!\LicitaIA\Plugin::canDeleteRecords($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $success = $id > 0 ? $this->keywords_model->delete($id) : false;

        return $this->response->setJSON(array(
            'success' => (bool) $success,
            'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred'),
        ));
    }

    private function makeRow($data)
    {
        $type_label = ($data->keyword_type === 'exclude')
            ? app_lang('licitaia_keyword_type_exclude')
            : app_lang('licitaia_keyword_type_include');

        $type_badge = $data->keyword_type === 'exclude'
            ? 'danger'
            : 'success';

        $active_badge = !empty($data->active) ? 'success' : 'secondary';
        $active_label = !empty($data->active) ? app_lang('active') : app_lang('inactive');

        $options = '';
        if (\LicitaIA\Plugin::canManageKeywords($this->login_user)) {
            $options .= modal_anchor(get_uri('licitaia/keywords/modal_form/' . (int) $data->id), "<i data-feather='edit' class='icon-16'></i>", array('class' => 'action-icon', 'title' => app_lang('edit')));
            $options .= js_anchor("<i data-feather='" . (!empty($data->active) ? 'eye-off' : 'eye') . "' class='icon-16'></i>", array(
                'title' => !empty($data->active) ? app_lang('deactivate') : app_lang('activate'),
                'class' => 'action-icon text-' . (!empty($data->active) ? 'warning' : 'success') . ' js-toggle-keyword-status',
                'data-id' => (int) $data->id,
                'data-active' => !empty($data->active) ? 0 : 1,
                'data-action-url' => get_uri('licitaia/keywords/toggle_status'),
            ));
        }

        if (\LicitaIA\Plugin::canDeleteRecords($this->login_user)) {
            $options .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => (int) $data->id, 'data-action-url' => get_uri('licitaia/keywords/delete'), 'data-action' => 'delete-confirmation'));
        }

        return array(
            esc($data->keyword ?: '-'),
            esc($data->category ?: '-'),
            '<span class="badge bg-' . esc($type_badge) . '">' . esc($type_label) . '</span>',
            (int) $data->weight,
            '<span class="badge bg-' . esc($active_badge) . '">' . esc($active_label) . '</span>',
            $options,
        );
    }
}
