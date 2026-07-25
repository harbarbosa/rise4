<?php

namespace LicitaIA\Controllers;

class Checklist extends Licitaia_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();
        $this->checklist_model->seed_default_items();

        return $this->template->rander('LicitaIA\\Views\\checklist\\index', array(
            'can_manage' => \LicitaIA\Plugin::canManageChecklist($this->login_user),
            'categories_dropdown' => $this->checklist_model->get_categories_dropdown(true),
        ));
    }

    public function list_data()
    {
        $this->ensureAccess();

        $options = array(
            'category' => trim((string) $this->request->getPost('category')),
            'status' => trim((string) $this->request->getPost('status')),
            'search' => trim((string) $this->request->getPost('search')),
        );

        $rows = $this->checklist_model->get_details($options)->getResult();
        $result = array();

        foreach ($rows as $row) {
            $options_html = '';
            if (\LicitaIA\Plugin::canManageChecklist($this->login_user)) {
                $options_html .= modal_anchor(get_uri('licitaia/checklist/modal_form/' . (int) $row->id), "<i data-feather='edit' class='icon-16'></i>", array('class' => 'action-icon', 'title' => app_lang('edit')));
            }

            if (\LicitaIA\Plugin::canDeleteRecords($this->login_user)) {
                $options_html .= js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => (int) $row->id, 'data-action-url' => get_uri('licitaia/checklist/delete'), 'data-action' => 'delete-confirmation'));
            }

            $result[] = array(
                esc($row->item_name ?: '-'),
                esc($row->category ?: '-'),
                esc($row->description ?: '-'),
                $row->is_required ? app_lang('yes') : app_lang('no'),
                $row->active ? app_lang('yes') : app_lang('no'),
                (int) $row->sort,
                $options_html,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        if (!\LicitaIA\Plugin::canManageChecklist($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $id ?: (int) $this->request->getPost('id');
        $model_info = $id ? $this->checklist_model->get_one($id) : (object) array(
            'id' => 0,
            'item_name' => '',
            'category' => '',
            'description' => '',
            'is_required' => 1,
            'active' => 1,
            'sort' => 0,
        );

        return $this->template->view('LicitaIA\\Views\\checklist\\modal_form', array(
            'model_info' => $model_info,
            'categories_dropdown' => $this->checklist_model->get_categories_dropdown(true),
        ));
    }

    public function save()
    {
        if (!\LicitaIA\Plugin::canManageChecklist($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $item_name = trim((string) $this->request->getPost('item_name'));
        if ($item_name === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('licitaia_required_checklist_item')));
        }

        $data = array(
            'item_name' => $item_name,
            'category' => trim((string) $this->request->getPost('category')),
            'description' => trim((string) $this->request->getPost('description')),
            'is_required' => $this->request->getPost('is_required') ? 1 : 0,
            'active' => $this->request->getPost('active') ? 1 : 0,
            'sort' => (int) $this->request->getPost('sort'),
            'updated_at' => get_my_local_time(),
        );

        if (!$id) {
            $data['created_by'] = (int) $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->checklist_model->ci_save($data, $id);
        return $this->response->setJSON(array('success' => $save_id !== false, 'message' => $save_id !== false ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function delete()
    {
        if (!\LicitaIA\Plugin::canDeleteRecords($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $this->request->getPost('id');
        $ok = $id > 0 ? $this->checklist_model->delete($id) : false;
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    public function create_for_opportunity($opportunity_id = 0)
    {
        if (!\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $ok = $this->opportunity_checklist_model->create_default_checklist((int) $opportunity_id);
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function update_opportunity_item($id = 0)
    {
        if (!\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $id = (int) $id ?: (int) $this->request->getPost('id');
        $ok = $this->opportunity_checklist_model->update_item($id, array(
            'status' => $this->request->getPost('status'),
            'notes' => $this->request->getPost('notes'),
            'document_id' => $this->request->getPost('document_id'),
        ));

        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')));
    }
}
