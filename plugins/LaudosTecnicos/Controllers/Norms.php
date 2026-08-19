<?php

namespace LaudosTecnicos\Controllers;

class Norms extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureNormsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\norms\\index', array(
            'can_manage_norms' => \LaudosTecnicos\Plugin::canManageNorms($this->login_user),
        ));
    }

    public function list_data()
    {
        $this->ensureNormsAccess();

        $rows = $this->norms_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => trim((string) $this->request->getPost('status')),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/normas/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= ' ' . js_anchor("<i data-feather='power' class='icon-16'></i>", array('title' => 'Alternar status', 'class' => 'btn btn-sm btn-outline-dark', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/normas/toggle_status/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Alternar status da norma?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/normas/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->code),
                esc($row->title),
                esc($row->institution ?: '-'),
                esc($row->category ?: '-'),
                esc($row->edition ?: '-'),
                esc($row->year ?: '-'),
                $this->statusBadge((string) ($row->status ?: 'inactive')),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureNormsAccess();

        $view_data['model_info'] = $id ? $this->norms_model->get_one((int) $id) : (object) array(
            'id' => '',
            'code' => '',
            'title' => '',
            'institution' => '',
            'category' => '',
            'edition' => '',
            'year' => '',
            'description' => '',
            'link' => '',
            'authorized_file' => '',
            'status' => 'active',
            'observation' => '',
        );

        return $this->template->view('LaudosTecnicos\\Views\\norms\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureNormsAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'code' => trim((string) $this->request->getPost('code')),
            'title' => trim((string) $this->request->getPost('title')),
            'institution' => trim((string) $this->request->getPost('institution')),
            'category' => trim((string) $this->request->getPost('category')),
            'edition' => trim((string) $this->request->getPost('edition')),
            'year' => (int) $this->request->getPost('year'),
            'description' => trim((string) $this->request->getPost('description')),
            'link' => trim((string) $this->request->getPost('link')),
            'authorized_file' => trim((string) $this->request->getPost('authorized_file')),
            'status' => trim((string) $this->request->getPost('status')) ?: 'active',
            'observation' => trim((string) $this->request->getPost('observation')),
        );

        if ($data['code'] === '' || $data['title'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $ok = $this->norms_model->save_from_post($data, $id ?: null);
        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureNormsAccess();
        return $this->response->setJSON(array('success' => (bool) $this->norms_model->toggle_status((int) $id)));
    }

    public function delete()
    {
        $this->ensureNormsAccess();
        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        if ($this->norms_model->is_in_use($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('laudostecnicos_norm_in_use')));
        }

        $ok = $this->norms_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function link()
    {
        $this->ensureNormsAccess();

        $entity_type = trim((string) $this->request->getPost('entity_type'));
        $entity_id = (int) $this->request->getPost('entity_id');
        $norm_ids = $this->request->getPost('norm_ids');
        $norm_ids = is_array($norm_ids) ? $norm_ids : array();

        $saved = $this->norm_links_model->sync_for_entity($entity_type, $entity_id, $norm_ids);
        return $this->response->setJSON(array('success' => $saved !== false, 'saved' => (int) $saved));
    }

    private function statusBadge(string $status): string
    {
        $map = array('active' => 'bg-success', 'inactive' => 'bg-secondary');
        $class = get_array_value($map, $status) ?: 'bg-secondary';
        return '<span class="badge ' . $class . '">' . esc($status ?: 'inactive') . '</span>';
    }
}
