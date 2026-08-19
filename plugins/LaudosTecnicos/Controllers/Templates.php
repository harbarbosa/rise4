<?php

namespace LaudosTecnicos\Controllers;

class Templates extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureTemplatesAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\templates\\index', array(
            'can_manage_templates' => \LaudosTecnicos\Plugin::canManageTemplates($this->login_user),
            'types_dropdown' => $this->types_model->get_active_dropdown(true),
            'categories_dropdown' => $this->categories_model->get_active_dropdown(true),
        ));
    }

    public function list_data()
    {
        $this->ensureTemplatesAccess();

        $rows = $this->templates_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => trim((string) $this->request->getPost('status')),
            'type_id' => (int) $this->request->getPost('type_id'),
            'category_id' => (int) $this->request->getPost('category_id'),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/templates/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= ' ' . js_anchor("<i data-feather='copy' class='icon-16'></i>", array('title' => 'Nova versão', 'class' => 'btn btn-sm btn-outline-primary', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/templates/new_version'), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Criar nova versão?', 'data-reload-on-success' => true));
            $actions .= ' ' . anchor(get_uri('laudostecnicos/templates/preview/' . $row->id), "<i data-feather='eye' class='icon-16'></i>", array('title' => 'Pré-visualizar', 'class' => 'btn btn-sm btn-outline-info', 'target' => '_blank'));
            $actions .= ' ' . js_anchor("<i data-feather='check-circle' class='icon-16'></i>", array('title' => 'Publicar', 'class' => 'btn btn-sm btn-outline-success', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/templates/publish/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Publicar esta versão?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='archive' class='icon-16'></i>", array('title' => 'Arquivar', 'class' => 'btn btn-sm btn-outline-warning', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/templates/archive/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Arquivar este template?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='power' class='icon-16'></i>", array('title' => empty($row->is_active) ? 'Ativar' : 'Inativar', 'class' => 'btn btn-sm btn-outline-dark', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/templates/toggle_status/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => empty($row->is_active) ? 'Ativar este template?' : 'Inativar este template?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/templates/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->name),
                esc($row->code ?: '-'),
                esc($row->template_key ?: '-'),
                esc($row->type_name ?: '-'),
                esc($row->category_name ?: '-'),
                (int) $row->version,
                $this->renderBadge((string) ($row->status ?? 'draft')),
                $row->is_default ? '<span class="badge bg-primary">Padrão</span>' : '<span class="badge bg-light text-dark">-</span>',
                $row->is_active ? '<span class="badge bg-success">' . app_lang('laudostecnicos_enabled') . '</span>' : '<span class="badge bg-secondary">' . app_lang('laudostecnicos_disabled') . '</span>',
                esc($row->published_at ?: '-'),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureTemplatesAccess();

        $id = (int) $id;
        $model_info = $id ? $this->templates_model->get_one_with_structure($id) : null;
        if (!$model_info) {
            $model_info = (object) array(
                'id' => '',
                'template_key' => '',
                'name' => '',
                'code' => '',
                'description' => '',
                'type_id' => '',
                'category_id' => '',
                'version' => 1,
                'status' => 'draft',
                'is_active' => 1,
                'is_default' => 0,
                'published_at' => '',
                'structure' => $this->defaultStructure(),
            );
        } else if (empty($model_info->structure)) {
            $model_info->structure = $this->defaultStructure();
        }

        return $this->template->view('LaudosTecnicos\\Views\\templates\\modal_form', array(
            'model_info' => $model_info,
            'types_dropdown' => $this->types_model->get_active_dropdown(true),
            'categories_dropdown' => $this->categories_model->get_active_dropdown(true),
            'status_options' => $this->statusOptions(),
            'preview_url' => $id ? get_uri('laudostecnicos/templates/preview/' . $id) : '#',
            'can_manage_templates' => \LaudosTecnicos\Plugin::canManageTemplates($this->login_user),
        ));
    }

    public function save()
    {
        $this->ensureTemplatesAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'template_key' => trim((string) $this->request->getPost('template_key')),
            'name' => trim((string) $this->request->getPost('name')),
            'code' => trim((string) $this->request->getPost('code')),
            'description' => trim((string) $this->request->getPost('description')),
            'type_id' => (int) $this->request->getPost('type_id'),
            'category_id' => (int) $this->request->getPost('category_id'),
            'version' => (int) $this->request->getPost('version') ?: 1,
            'status' => trim((string) $this->request->getPost('status')) ?: 'draft',
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
            'published_at' => trim((string) $this->request->getPost('published_at')) ?: null,
            'created_by' => (int) ($this->login_user->id ?? 0),
            'updated_by' => (int) ($this->login_user->id ?? 0),
        );

        if ($data['name'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $structure = $this->templates_model->build_structure_payload($this->request->getPost());
        $saved_id = $this->templates_model->save_versioned($data, $structure, $id ?: null);
        if ($saved_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $template_id = is_numeric($saved_id) ? (int) $saved_id : $id;
        $created_new_version = $id && is_numeric($saved_id) && (int) $saved_id !== $id;
        $this->logAudit('template', $template_id, $created_new_version ? 'new_version' : ($id ? 'update' : 'create'), 'Template salvo', array(), $data);

        return $this->response->setJSON(array(
            'success' => true,
            'message' => $created_new_version ? 'Nova versao criada.' : app_lang('record_saved'),
            'id' => $template_id,
            'redirect_to' => get_uri('laudostecnicos/templates/preview/' . $template_id),
        ));
    }

    public function new_version()
    {
        $this->ensureTemplatesAccess();

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $new_id = $this->templates_model->clone_template($id, array('created_by' => (int) ($this->login_user->id ?? 0)));
        if (!$new_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->logAudit('template', (int) $new_id, 'new_version', 'Nova versao criada a partir do template #' . $id, array(), array('source_id' => $id));

        return $this->response->setJSON(array(
            'success' => true,
            'message' => 'Nova versao criada.',
            'id' => (int) $new_id,
            'redirect_to' => get_uri('laudostecnicos/templates/modal_form/' . (int) $new_id),
        ));
    }

    public function preview($id = 0)
    {
        $this->ensureTemplatesAccess();

        $template = $this->templates_model->get_one_with_structure((int) $id);
        if (!$template || !$template->id) {
            show_404();
        }

        return $this->template->rander('LaudosTecnicos\\Views\\templates\\preview', array(
            'template' => $template,
            'structure' => $template->structure,
        ));
    }

    public function publish($id = 0)
    {
        $this->ensureTemplatesAccess();

        $ok = $this->templates_model->publish((int) $id);
        if ($ok) {
            $this->logAudit('template', (int) $id, 'publish', 'Template publicado', array(), array());
        }

        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function archive($id = 0)
    {
        $this->ensureTemplatesAccess();

        $ok = $this->templates_model->archive((int) $id);
        if ($ok) {
            $this->logAudit('template', (int) $id, 'archive', 'Template arquivado', array(), array());
        }

        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureTemplatesAccess();

        $ok = $this->templates_model->toggle_active((int) $id);
        if ($ok) {
            $this->logAudit('template', (int) $id, 'toggle_status', 'Status ativo alterado', array(), array());
        }

        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function delete()
    {
        $this->ensureTemplatesAccess();

        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        if ($this->templates_model->is_in_use($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Este template possui vinculos e nao pode ser excluido.'));
        }

        $ok = $this->templates_model->delete($id);
        if ($ok) {
            $this->logAudit('template', $id, 'delete', 'Template excluido logicamente', array(), array());
        }

        return $this->response->setJSON(array('success' => (bool) $ok, 'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred')));
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

    private function renderBadge(string $status): string
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

    private function defaultStructure(): array
    {
        return array(
            'sections' => array(
                array('key' => 'capa', 'title' => 'Capa', 'description' => '', 'sort' => 1, 'page_break' => 0, 'numbering' => 0, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'identificacao', 'title' => 'Identificação', 'description' => '', 'sort' => 2, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'dados_cliente', 'title' => 'Dados do cliente', 'description' => '', 'sort' => 3, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'objetivo', 'title' => 'Objetivo', 'description' => '', 'sort' => 4, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'escopo', 'title' => 'Escopo', 'description' => '', 'sort' => 5, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'metodologia', 'title' => 'Metodologia', 'description' => '', 'sort' => 6, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'conclusao', 'title' => 'Conclusão', 'description' => '', 'sort' => 7, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'assinaturas', 'title' => 'Assinaturas', 'description' => '', 'sort' => 8, 'page_break' => 0, 'numbering' => 0, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
            ),
            'fields' => array(
                array('key' => 'title', 'section_key' => 'identificacao', 'type' => 'text', 'name' => 'title', 'label' => 'Título', 'description' => '', 'placeholder' => 'Título do laudo', 'default_value' => '', 'required' => 1, 'sort' => 1, 'width' => '12', 'validation' => '', 'mask' => '', 'help' => '', 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'read_only' => 0, 'generated_ai' => 0),
                array('key' => 'objective', 'section_key' => 'objetivo', 'type' => 'text_long', 'name' => 'objective', 'label' => 'Objetivo', 'description' => '', 'placeholder' => '', 'default_value' => '', 'required' => 0, 'sort' => 2, 'width' => '12', 'validation' => '', 'mask' => '', 'help' => '', 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'read_only' => 0, 'generated_ai' => 0),
                array('key' => 'conclusion', 'section_key' => 'conclusao', 'type' => 'text_long', 'name' => 'conclusion', 'label' => 'Conclusão', 'description' => '', 'placeholder' => '', 'default_value' => '', 'required' => 0, 'sort' => 3, 'width' => '12', 'validation' => '', 'mask' => '', 'help' => '', 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'read_only' => 0, 'generated_ai' => 0),
            ),
            'rules' => array(
                array('name' => 'Foto quando não conforme', 'trigger_field' => 'resultado', 'operator' => 'equals', 'trigger_value' => 'nao_conforme', 'action_type' => 'require_field', 'action_target' => 'foto', 'message' => 'Exige fotografia quando houver não conformidade.', 'sort' => 1, 'active' => 1),
                array('name' => 'Observação quando resposta for Não', 'trigger_field' => 'status_item', 'operator' => 'equals', 'trigger_value' => 'nao', 'action_type' => 'require_field', 'action_target' => 'observacao', 'message' => 'Exige observação quando a resposta for Não.', 'sort' => 2, 'active' => 1),
            ),
        );
    }
}
