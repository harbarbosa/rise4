<?php

namespace LaudosTecnicos\Controllers;

class Checklists extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureChecklistsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\checklists\\index', array(
            'can_manage_checklists' => \LaudosTecnicos\Plugin::canManageChecklists($this->login_user),
            'types_dropdown' => $this->types_model->get_active_dropdown(true),
            'categories_dropdown' => $this->categories_model->get_active_dropdown(true),
            'status_options' => $this->statusOptions(),
        ));
    }

    public function list_data()
    {
        $this->ensureChecklistsAccess();

        $rows = $this->checklists_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => trim((string) $this->request->getPost('status')),
            'type_id' => (int) $this->request->getPost('type_id'),
            'category_id' => (int) $this->request->getPost('category_id'),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/checklists/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= ' ' . js_anchor("<i data-feather='copy' class='icon-16'></i>", array('title' => 'Duplicar', 'class' => 'btn btn-sm btn-outline-primary', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/checklists/duplicate'), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Duplicar este checklist?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='check-circle' class='icon-16'></i>", array('title' => 'Publicar', 'class' => 'btn btn-sm btn-outline-success', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/checklists/publish/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Publicar este checklist?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='archive' class='icon-16'></i>", array('title' => 'Arquivar', 'class' => 'btn btn-sm btn-outline-warning', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/checklists/archive/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Arquivar este checklist?', 'data-reload-on-success' => true));
            $actions .= ' ' . anchor(get_uri('laudostecnicos/checklists/export/' . $row->id), "<i data-feather='download' class='icon-16'></i>", array('title' => 'Exportar', 'class' => 'btn btn-sm btn-outline-info', 'target' => '_blank'));
            $actions .= ' ' . js_anchor("<i data-feather='power' class='icon-16'></i>", array('title' => empty($row->is_active) ? 'Ativar' : 'Inativar', 'class' => 'btn btn-sm btn-outline-dark', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/checklists/toggle_status/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => empty($row->is_active) ? 'Ativar este checklist?' : 'Inativar este checklist?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/checklists/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->name),
                esc($row->code),
                esc($row->category_name ?: '-'),
                esc($row->type_name ?: '-'),
                (int) $row->version,
                $this->statusBadge((string) $row->status),
                !empty($row->is_default) ? '<span class="badge bg-primary">Sim</span>' : '<span class="badge bg-light text-dark">Nao</span>',
                !empty($row->is_active) ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Nao</span>',
                esc($row->published_at ?: '-'),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureChecklistsAccess();

        $model_info = $id ? $this->checklists_model->get_one_with_structure((int) $id) : null;
        if (!$model_info) {
            $model_info = (object) array(
                'id' => '',
                'name' => '',
                'code' => '',
                'category_id' => '',
                'type_id' => '',
                'description' => '',
                'version' => 1,
                'status' => 'draft',
                'is_active' => 1,
                'is_default' => 0,
                'responsible_id' => (int) ($this->login_user->id ?? 0),
                'published_at' => '',
                'structure' => $this->defaultStructure(),
            );
        } elseif (empty($model_info->structure)) {
            $model_info->structure = $this->defaultStructure();
        }

        return $this->template->view('LaudosTecnicos\\Views\\checklists\\modal_form', array(
            'model_info' => $model_info,
            'types_dropdown' => $this->types_model->get_active_dropdown(true),
            'categories_dropdown' => $this->categories_model->get_active_dropdown(true),
            'status_options' => $this->statusOptions(),
            'response_types' => $this->responseTypes(),
            'criticalities' => $this->criticalities(),
        ));
    }

    public function save()
    {
        $this->ensureChecklistsAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'name' => trim((string) $this->request->getPost('name')),
            'code' => trim((string) $this->request->getPost('code')),
            'category_id' => (int) $this->request->getPost('category_id'),
            'type_id' => (int) $this->request->getPost('type_id'),
            'description' => trim((string) $this->request->getPost('description')),
            'version' => (int) $this->request->getPost('version') ?: 1,
            'status' => trim((string) $this->request->getPost('status')) ?: 'draft',
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
            'responsible_id' => (int) $this->request->getPost('responsible_id'),
            'created_by' => (int) ($this->login_user->id ?? 0),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        if ($data['name'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $structure = $this->buildStructureFromPost($this->request->getPost());
        $saved_id = $this->checklists_model->save_versioned($data, $structure, $id ?: null, false);
        if ($saved_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $new_id = is_numeric($saved_id) ? (int) $saved_id : $id;
        $this->logAudit('checklist', $new_id, $id ? 'update' : 'create', 'Checklist salvo', array(), $data);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved'), 'id' => $new_id));
    }

    public function duplicate()
    {
        $this->ensureChecklistsAccess();

        $id = (int) $this->request->getPost('id');
        $new_id = $this->checklists_model->duplicate($id, array('created_by' => (int) ($this->login_user->id ?? 0)));
        if (!$new_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->logAudit('checklist', (int) $new_id, 'duplicate', 'Checklist duplicado', array(), array('source_id' => $id));
        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved'), 'id' => (int) $new_id));
    }

    public function publish($id = 0)
    {
        $this->ensureChecklistsAccess();
        $ok = $this->checklists_model->publish((int) $id);
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function archive($id = 0)
    {
        $this->ensureChecklistsAccess();
        $ok = $this->checklists_model->archive((int) $id);
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureChecklistsAccess();
        return $this->response->setJSON(array('success' => (bool) $this->checklists_model->toggle_active((int) $id)));
    }

    public function delete()
    {
        $this->ensureChecklistsAccess();

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        if ($this->checklists_model->is_in_use($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('laudostecnicos_checklist_in_use')));
        }

        $ok = $this->checklists_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    public function export($id = 0)
    {
        $this->ensureChecklistsAccess();
        return $this->response->setBody($this->checklists_model->export_json((int) $id))->setContentType('application/json');
    }

    public function import()
    {
        $this->ensureChecklistsAccess();

        $json = trim((string) $this->request->getPost('json'));
        if ($json === '' && $this->request->getFile('file')) {
            $file = $this->request->getFile('file');
            if ($file && $file->isValid()) {
                $json = (string) file_get_contents($file->getTempName());
            }
        }

        $saved_id = $this->checklists_model->import_json($json, array('created_by' => (int) ($this->login_user->id ?? 0)));
        if (!$saved_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved'), 'id' => (int) $saved_id));
    }

    public function progress($id = 0)
    {
        $this->ensureChecklistsAccess();
        return $this->response->setJSON($this->checklist_responses_model->get_progress(array('checklist_id' => (int) $id)));
    }

    public function save_responses()
    {
        $this->ensureChecklistsAccess();

        $responses = $this->request->getPost('responses');
        $responses = is_array($responses) ? $responses : array();
        $base = array(
            'laudo_id' => (int) $this->request->getPost('laudo_id'),
            'inspection_id' => (int) $this->request->getPost('inspection_id'),
            'checklist_id' => (int) $this->request->getPost('checklist_id'),
            'source' => trim((string) $this->request->getPost('source')) ?: 'web',
            'user_id' => (int) ($this->login_user->id ?? 0),
            'created_by' => (int) ($this->login_user->id ?? 0),
            'updated_by' => (int) ($this->login_user->id ?? 0),
            'ip_address' => $this->request->getIPAddress(),
        );

        $saved = $this->checklist_responses_model->save_bulk($responses, $base);
        if ($saved !== false) {
            foreach ($responses as $response) {
                if (!is_array($response)) {
                    continue;
                }

                $raw_response = strtolower(trim((string) get_array_value($response, 'response')));
                if (!in_array($raw_response, array('nao_conforme', 'não conforme', 'nao conforme', 'non_conforming', 'critico', 'crítico', 'critical'), true)) {
                    continue;
                }

                laudostecnicos_create_nonconformity_from_event(array(
                    'code' => trim((string) get_array_value($response, 'code')),
                    'title' => trim((string) (get_array_value($response, 'question') ?: get_array_value($response, 'item_title') ?: 'Nao conformidade do checklist')),
                    'description' => trim((string) get_array_value($response, 'observation')),
                    'client_id' => (int) get_array_value($base, 'client_id'),
                    'laudo_id' => (int) get_array_value($base, 'laudo_id'),
                    'inspection_id' => (int) get_array_value($base, 'inspection_id'),
                    'checklist_id' => (int) get_array_value($base, 'checklist_id'),
                    'checklist_item_id' => (int) get_array_value($response, 'item_id'),
                    'norm_id' => (int) get_array_value($response, 'norm_id'),
                    'classification' => 'critica',
                    'probability' => 3,
                    'impact' => 3,
                    'recommendation' => trim((string) get_array_value($response, 'recommendation')) ?: 'Avaliar correcao do item nao conforme.',
                    'suggested_deadline' => date('Y-m-d', strtotime('+7 days')),
                    'responsible_id' => (int) get_array_value($base, 'user_id'),
                    'status' => 'open',
                    'identified_at' => get_current_utc_time(),
                    'evidence_json' => get_array_value($response, 'photos') ?: array(),
                    'photos_json' => get_array_value($response, 'photos') ?: array(),
                    'created_by' => (int) get_array_value($base, 'created_by'),
                    'updated_by' => (int) get_array_value($base, 'updated_by'),
                ));
            }
        }
        return $this->response->setJSON(array('success' => $saved !== false, 'saved' => (int) $saved));
    }

    private function buildStructureFromPost(array $post): array
    {
        $groups = array();
        foreach ((array) get_array_value($post, 'groups') as $group) {
            if (!is_array($group)) {
                continue;
            }

            $groups[] = array(
                'key' => trim((string) get_array_value($group, 'key')),
                'title' => trim((string) get_array_value($group, 'title')),
                'description' => trim((string) get_array_value($group, 'description')),
                'sort' => (int) get_array_value($group, 'sort'),
                'active' => get_array_value($group, 'active') ? 1 : 0,
                'hidden' => get_array_value($group, 'hidden') ? 1 : 0,
            );
        }

        $items = array();
        foreach ((array) get_array_value($post, 'items') as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = array(
                'group_key' => trim((string) get_array_value($item, 'group_key')),
                'code' => trim((string) get_array_value($item, 'code')),
                'question' => trim((string) get_array_value($item, 'question')),
                'guidance' => trim((string) get_array_value($item, 'guidance')),
                'response_type' => trim((string) get_array_value($item, 'response_type')),
                'expected_response' => trim((string) get_array_value($item, 'expected_response')),
                'criticality' => trim((string) get_array_value($item, 'criticality')),
                'weight' => (float) get_array_value($item, 'weight'),
                'required' => get_array_value($item, 'required') ? 1 : 0,
                'evidence_required' => get_array_value($item, 'evidence_required') ? 1 : 0,
                'photo_required' => get_array_value($item, 'photo_required') ? 1 : 0,
                'measurement_required' => get_array_value($item, 'measurement_required') ? 1 : 0,
                'observation_required' => get_array_value($item, 'observation_required') ? 1 : 0,
                'related_norm' => trim((string) get_array_value($item, 'related_norm')),
                'generates_nc' => get_array_value($item, 'generates_nc') ? 1 : 0,
                'sort' => (int) get_array_value($item, 'sort'),
                'active' => get_array_value($item, 'active') ? 1 : 0,
            );
        }

        usort($groups, function ($a, $b) {
            return ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0));
        });
        usort($items, function ($a, $b) {
            return ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0));
        });

        return array('groups' => $groups, 'items' => $items);
    }

    private function statusBadge(string $status): string
    {
        $map = array(
            'draft' => 'bg-secondary',
            'published' => 'bg-success',
            'archived' => 'bg-warning text-dark',
            'inactive' => 'bg-dark',
        );

        $class = get_array_value($map, $status) ?: 'bg-secondary';
        return '<span class="badge ' . $class . '">' . esc(ucfirst($status ?: 'draft')) . '</span>';
    }

    private function statusOptions(): array
    {
        return array(
            'draft' => 'Rascunho',
            'published' => 'Publicado',
            'archived' => 'Arquivado',
            'inactive' => 'Inativo',
        );
    }

    private function responseTypes(): array
    {
        return array(
            'conforme' => 'Conforme',
            'nao_conforme' => 'Nao conforme',
            'nao_se_aplica' => 'Nao se aplica',
            'nao_verificado' => 'Nao verificado',
            'sim_nao' => 'Sim ou nao',
            'texto' => 'Texto',
            'numero' => 'Numero',
            'data' => 'Data',
            'selecao_unica' => 'Selecao unica',
            'selecao_multipla' => 'Selecao multipla',
        );
    }

    private function criticalities(): array
    {
        return array(
            'baixa' => 'Baixa',
            'media' => 'Media',
            'alta' => 'Alta',
            'critica' => 'Critica',
        );
    }

    private function defaultStructure(): array
    {
        return array(
            'groups' => array(
                array('key' => 'geral', 'title' => 'Grupo geral', 'description' => '', 'sort' => 1, 'active' => 1, 'hidden' => 0),
            ),
            'items' => array(
                array('group_key' => 'geral', 'code' => 'ITEM-001', 'question' => 'Verificacao inicial', 'guidance' => 'Descreva o item do checklist.', 'response_type' => 'conforme', 'expected_response' => 'conforme', 'criticality' => 'media', 'weight' => 1, 'required' => 1, 'evidence_required' => 0, 'photo_required' => 0, 'measurement_required' => 0, 'observation_required' => 0, 'related_norm' => '', 'generates_nc' => 0, 'sort' => 1, 'active' => 1),
            ),
        );
    }
}
