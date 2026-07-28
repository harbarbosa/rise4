<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudo_templates_model;
use LaudosTecnicos\Models\Laudos_model;
use LaudosTecnicos\Models\Laudos_settings_model;

class Laudos_templates extends Security_Controller
{
    protected $templates_model;
    protected $laudos_model;
    protected $settings_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        
        $this->templates_model = model('LaudosTecnicos\Models\Laudo_templates_model');
        $this->laudos_model = model('LaudosTecnicos\Models\Laudos_model');
        $this->settings_model = model('LaudosTecnicos\Models\Laudos_settings_model');
    }

    public function index()
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $view_data = array(
            'types_dropdown' => $this->_get_types_dropdown(),
            'status_list' => array(
                'draft' => 'Rascunho',
                'published' => 'Publicado',
                'archived' => 'Arquivado'
            )
        );

        return $this->template->rander('LaudosTecnicos\Views\templates\index', $view_data);
    }

    public function list_data()
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            return $this->_json_permission_denied();
        }

        $options = array(
            'search' => $this->request->getPost('search'),
            'laudo_type_id' => $this->request->getPost('laudo_type_id'),
            'status' => $this->request->getPost('status')
        );

        $list_data = $this->templates_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _make_row($data)
    {
        $status_class = $data->status === 'published' ? 'success' : ($data->status === 'archived' ? 'dark' : 'secondary');
        
        return array(
            $data->id,
            $data->name,
            $data->code . ' v' . $data->version,
            $data->type_name ?? '-',
            '<span class="badge bg-' . ($data->is_default ? 'warning' : 'secondary') . '">' . ($data->is_default ? 'Padrão' : '-') . '</span>',
            '<span class="badge bg-' . $status_class . '">' . ucfirst($data->status) . '</span>',
            $data->created_at,
            $this->_get_actions($data)
        );
    }

    private function _get_actions($data)
    {
        $actions = '';
        
        // Editar
        $actions .= modal_anchor(get_uri('laudos_templates/edit/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit btn btn-default btn-sm', 'title' => 'Editar')) . ' ';
        
        // Clonar
        $actions .= '<a href="' . get_uri('laudos_templates/clone/' . $data->id) . '" class="btn btn-default btn-sm" title="Clonar"><i data-feather="copy" class="icon-16"></i></a> ';
        
        // Visualizar/Preview
        $actions .= '<a href="' . get_uri('laudos_templates/preview/' . $data->id) . '" class="btn btn-default btn-sm" title="Visualizar"><i data-feather="eye" class="icon-16"></i></a> ';
        
        // Publicar
        if ($data->status === 'draft') {
            $actions .= '<a href="' . get_uri('laudos_templates/publish/' . $data->id) . '" class="btn btn-success btn-sm" title="Publicar"><i data-feather="send" class="icon-16"></i></a> ';
        }
        
        // Nova versão
        if ($data->status === 'published') {
            $actions .= '<a href="' . get_uri('laudos_templates/new_version/' . $data->id) . '" class="btn btn-info btn-sm" title="Nova Versão"><i data-feather="plus-circle" class="icon-16"></i></a> ';
        }
        
        // Excluir (apenas rascunho)
        if ($data->status === 'draft') {
            $actions .= js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('class' => 'delete btn btn-danger btn-sm', 'title' => 'Excluir', 'data-id' => $data->id));
        }
        
        return $actions;
    }

    public function edit($id = 0)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['template'] = $this->templates_model->get_one($id);
            $view_data['sections'] = $this->templates_model->get_sections($id);
            
            // Carregar campos de cada seção
            if ($view_data['sections']) {
                foreach ($view_data['sections'] as &$section) {
                    $section->fields = $this->templates_model->get_section_fields($section->id);
                }
            }
            
            $view_data['rules'] = $this->templates_model->get_rules($id);
        }

        $view_data['types_dropdown'] = $this->_get_types_dropdown();
        $view_data['field_types'] = $this->_get_field_types();
        $view_data['section_types'] = $this->_get_section_types();

        return $this->template->rander('LaudosTecnicos\Views\templates\edit', $view_data);
    }

    public function save()
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'name' => $this->request->getPost('name'),
            'code' => strtoupper($this->request->getPost('code')),
            'description' => $this->request->getPost('description'),
            'laudo_type_id' => $this->request->getPost('laudo_type_id') ?: null,
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
            'status' => $this->request->getPost('status') ?: 'draft',
            'updated_by' => $this->login_user->id
        );

        // Se novo, adicionar created_by
        if (!$id) {
            $data['created_by'] = $this->login_user->id;
        }

        $save_id = $this->templates_model->save($data, $id);

        if ($save_id) {
            // Salvar seções
            $this->_save_sections($save_id);
            
            // Salvar regras
            $this->_save_rules($save_id);
            
            return $this->response->setJSON(array('success' => true, 'data' => $save_id, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    private function _save_sections($template_id)
    {
        $sections = $this->request->getPost('sections');
        if (!$sections || !is_array($sections)) return;

        $existing_sections = $this->templates_model->get_sections($template_id);
        $existing_ids = array_map(function($s) { return $s->id; }, $existing_sections);
        $new_ids = array();

        foreach ($sections as $section_data) {
            $section_id = isset($section_data['id']) ? (int)$section_data['id'] : 0;
            
            $data = array(
                'template_id' => $template_id,
                'name' => $section_data['name'],
                'code' => $section_data['code'],
                'description' => $section_data['description'] ?? '',
                'section_type' => $section_data['section_type'],
                'sort_order' => $section_data['sort_order'] ?? 0,
                'page_break' => isset($section_data['page_break']) ? 1 : 0,
                'show_numbering' => isset($section_data['show_numbering']) ? 1 : 0,
                'visible_web' => isset($section_data['visible_web']) ? 1 : 0,
                'visible_mobile' => isset($section_data['visible_mobile']) ? 1 : 0,
                'visible_pdf' => isset($section_data['visible_pdf']) ? 1 : 0,
                'is_required' => isset($section_data['is_required']) ? 1 : 0
            );

            $new_section_id = $this->templates_model->save_section($data, $section_id);
            $new_ids[] = $new_section_id;

            // Salvar campos da seção
            if (isset($section_data['fields']) && is_array($section_data['fields'])) {
                $this->_save_fields($new_section_id, $section_data['fields']);
            }
        }

        // Remover seções que não existem mais
        foreach ($existing_sections as $existing) {
            if (!in_array($existing->id, $new_ids)) {
                $this->templates_model->delete_section($existing->id);
            }
        }
    }

    private function _save_fields($section_id, $fields)
    {
        $existing_fields = $this->templates_model->get_section_fields($section_id);
        $existing_ids = array_map(function($f) { return $f->id; }, $existing_fields);
        $new_ids = array();

        foreach ($fields as $field_data) {
            $field_id = isset($field_data['id']) ? (int)$field_data['id'] : 0;
            
            $data = array(
                'section_id' => $section_id,
                'field_name' => $field_data['field_name'],
                'field_key' => $field_data['field_key'],
                'field_type' => $field_data['field_type'],
                'label' => $field_data['label'],
                'description' => $field_data['description'] ?? '',
                'placeholder' => $field_data['placeholder'] ?? '',
                'default_value' => $field_data['default_value'] ?? '',
                'is_required' => isset($field_data['is_required']) ? 1 : 0,
                'sort_order' => $field_data['sort_order'] ?? 0,
                'width' => $field_data['width'] ?? '100%',
                'validation_rules' => $field_data['validation_rules'] ?? '',
                'mask' => $field_data['mask'] ?? '',
                'help_text' => $field_data['help_text'] ?? '',
                'visible_web' => isset($field_data['visible_web']) ? 1 : 0,
                'visible_mobile' => isset($field_data['visible_mobile']) ? 1 : 0,
                'visible_pdf' => isset($field_data['visible_pdf']) ? 1 : 0,
                'read_only' => isset($field_data['read_only']) ? 1 : 0,
                'options' => isset($field_data['options']) ? json_encode($field_data['options']) : null
            );

            $new_field_id = $this->templates_model->save_field($data, $field_id);
            $new_ids[] = $new_field_id;
        }

        // Remover campos que não existem mais
        foreach ($existing_fields as $existing) {
            if (!in_array($existing->id, $new_ids)) {
                $this->templates_model->delete_field($existing->id);
            }
        }
    }

    private function _save_rules($template_id)
    {
        $rules = $this->request->getPost('rules');
        if (!$rules || !is_array($rules)) return;

        $existing_rules = $this->templates_model->get_rules($template_id);
        $existing_ids = array_map(function($r) { return $r->id; }, $existing_rules);
        $new_ids = array();

        foreach ($rules as $rule_data) {
            $rule_id = isset($rule_data['id']) ? (int)$rule_data['id'] : 0;
            
            $data = array(
                'template_id' => $template_id,
                'rule_type' => $rule_data['rule_type'],
                'condition_field' => $rule_data['condition_field'] ?? '',
                'condition_operator' => $rule_data['condition_operator'] ?? '',
                'condition_value' => $rule_data['condition_value'] ?? '',
                'action' => $rule_data['action'],
                'action_target' => $rule_data['action_target'] ?? '',
                'action_value' => $rule_data['action_value'] ?? '',
                'sort_order' => $rule_data['sort_order'] ?? 0,
                'active' => isset($rule_data['active']) ? 1 : 0
            );

            $new_rule_id = $this->templates_model->save_rule($data, $rule_id);
            $new_ids[] = $new_rule_id;
        }

        // Remover regras que não existem mais
        foreach ($existing_rules as $existing) {
            if (!in_array($existing->id, $new_ids)) {
                $this->templates_model->delete_rule($existing->id);
            }
        }
    }

    public function clone_template($id)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $new_id = $this->templates_model->clone_template($id);

        if ($new_id) {
            app_redirect('laudos_templates/edit/' . $new_id);
        } else {
            app_redirect('laudos_templates');
        }
    }

    public function new_version($id)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $new_id = $this->templates_model->create_new_version($id);

        if ($new_id) {
            app_redirect('laudos_templates/edit/' . $new_id);
        } else {
            app_redirect('laudos_templates');
        }
    }

    public function publish($id)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $this->templates_model->publish($id);

        app_redirect('laudos_templates');
    }

    public function preview($id)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $template = $this->templates_model->get_one($id);
        
        if (!$template) {
            app_redirect('laudos_templates');
        }

        $sections = $this->templates_model->get_sections($id);
        
        // Carregar campos
        foreach ($sections as &$section) {
            $section->fields = $this->templates_model->get_section_fields($section->id);
        }

        $view_data = array(
            'template' => $template,
            'sections' => $sections,
            'field_types' => $this->_get_field_types()
        );

        return $this->template->rander('LaudosTecnicos\Views\templates\preview', $view_data);
    }

    public function save_data()
    {
        if (!$this->_has_create_permission() && !$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $laudo_id = $this->request->getPost('laudo_id');
        $field_key = $this->request->getPost('field_key');
        $field_value = $this->request->getPost('field_value');
        
        $laudo = $this->laudos_model->get_one($laudo_id);
        if (!$laudo) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Laudo não encontrado'));
        }

        $data_model = model('LaudosTecnicos\Models\Laudo_data_model');
        $data_model->save_field_value(
            $laudo_id,
            $laudo->template_id,
            $laudo->template_version,
            $this->request->getPost('section_id'),
            $this->request->getPost('field_id'),
            $field_key,
            $field_value
        );

        return $this->response->setJSON(array('success' => true));
    }

    public function get_for_type($type_id)
    {
        $templates = $this->templates_model->get_for_type($type_id);
        return $this->response->setJSON($templates);
    }

    public function apply_template($laudo_id, $template_id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $data_model = model('LaudosTecnicos\Models\Laudo_data_model');
        $result = $data_model->apply_template($laudo_id, $template_id);

        if ($result) {
            return $this->response->setJSON(array('success' => true, 'message' => 'Template aplicado com sucesso'));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function delete($id)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            return $this->_json_permission_denied();
        }

        $id = (int)$id;
        
        // Apenas rascunhos podem ser excluídos
        $template = $this->templates_model->get_one($id);
        if ($template && $template->status !== 'draft') {
            return $this->response->setJSON(array('success' => false, 'message' => 'Apenas rascunhos podem ser excluídos'));
        }

        if ($this->templates_model->delete($id)) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== HELPERS ====================
    private function _get_types_dropdown()
    {
        $model = model('LaudosTecnicos\Models\Laudo_types_model');
        return $model->get_dropdown();
    }

    private function _get_field_types()
    {
        return array(
            'text' => 'Texto Simples',
            'textarea' => 'Texto Longo',
            'rich_text' => 'Texto Rico (HTML)',
            'number' => 'Número',
            'decimal' => 'Decimal',
            'currency' => 'Moeda',
            'percentage' => 'Percentual',
            'date' => 'Data',
            'time' => 'Hora',
            'datetime' => 'Data e Hora',
            'yes_no' => 'Sim ou Não',
            'select' => 'Seleção Única',
            'multi_select' => 'Seleção Múltipla',
            'checkbox' => 'Checkbox',
            'dynamic_list' => 'Lista Dinâmica',
            'image' => 'Imagem',
            'file' => 'Arquivo',
            'signature' => 'Assinatura',
            'gps' => 'Localização GPS',
            'measurement' => 'Medição',
            'dynamic_table' => 'Tabela Dinâmica',
            'calculated' => 'Campo Calculado',
            'read_only' => 'Somente Leitura',
            'ai_text' => 'Texto gerado por IA'
        );
    }

    private function _get_section_types()
    {
        return array(
            'cover' => 'Capa',
            'summary' => 'Sumário',
            'identification' => 'Identificação',
            'client_data' => 'Dados do Cliente',
            'unit_data' => 'Dados da Unidade',
            'objective' => 'Objetivo',
            'scope' => 'Escopo',
            'methodology' => 'Metodologia',
            'standards' => 'Normas',
            'installation' => 'Descrição da Instalação',
            'checklist' => 'Checklist',
            'measurements' => 'Medições',
            'photos' => 'Fotografias',
            'non_conformities' => 'Não Conformidades',
            'recommendations' => 'Recomendações',
            'action_plan' => 'Plano de Ação',
            'conclusion' => 'Conclusão',
            'signatures' => 'Assinaturas',
            'attachments' => 'Anexos',
            'custom' => 'Personalizado'
        );
    }

    private function _has_permission($permission)
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), $permission) == '1';
    }

    private function _has_create_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_create') == '1';
    }

    private function _has_edit_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_edit') == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setStatusCode(403)->setJSON(array('success' => false, 'message' => app_lang('access_denied')));
    }
}