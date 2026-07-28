<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudo_checklists_model;
use LaudosTecnicos\Models\Laudo_measurements_model;
use LaudosTecnicos\Models\Laudo_equipment_model;
use LaudosTecnicos\Models\Laudo_standards_model;

class Laudo_technical extends Security_Controller
{
    protected $checklists_model;
    protected $measurements_model;
    protected $equipment_model;
    protected $standards_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        
        $this->checklists_model = model('LaudosTecnicos\Models\Laudo_checklists_model');
        $this->measurements_model = model('LaudosTecnicos\Models\Laudo_measurements_model');
        $this->equipment_model = model('LaudosTecnicos\Models\Laudo_equipment_model');
        $this->standards_model = model('LaudosTecnicos\Models\Laudo_standards_model');
    }

    // ==================== CHECKLISTS ====================
    public function checklists()
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $types_model = model('LaudosTecnicos\Models\Laudo_types_model');
        
        $view_data = array(
            'types_dropdown' => $types_model->get_dropdown(),
            'status_list' => array('draft' => 'Rascunho', 'published' => 'Publicado')
        );

        return $this->template->rander('LaudosTecnicos\Views\technical\checklists\index', $view_data);
    }

    public function checklists_list_data()
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            return $this->_json_permission_denied();
        }

        $options = array(
            'search' => $this->request->getPost('search'),
            'laudo_type_id' => $this->request->getPost('laudo_type_id'),
            'status' => $this->request->getPost('status')
        );

        $list_data = $this->checklists_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $item_count = count($this->checklists_model->get_items($data->id));
            $result[] = array(
                $data->id,
                $data->name,
                $data->code . ' v' . $data->version,
                $data->type_name ?? '-',
                $item_count . ' itens',
                '<span class="badge bg-' . ($data->status === 'published' ? 'success' : 'secondary') . '">' . ucfirst($data->status) . '</span>',
                $data->created_at,
                $this->_get_checklist_actions($data)
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _get_checklist_actions($data)
    {
        $actions = modal_anchor(get_uri('laudo_technical/checklist_edit/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit btn btn-default btn-sm', 'title' => 'Editar')) . ' ';
        $actions .= '<a href="' . get_uri('laudo_technical/checklist_duplicate/' . $data->id) . '" class="btn btn-default btn-sm" title="Clonar"><i data-feather="copy" class="icon-16"></i></a> ';
        
        if ($data->status === 'draft') {
            $actions .= '<a href="' . get_uri('laudo_technical/checklist_publish/' . $data->id) . '" class="btn btn-success btn-sm" title="Publicar"><i data-feather="send" class="icon-16"></i></a>';
        }
        
        return $actions;
    }

    public function checklist_edit($id = 0)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['checklist'] = $this->checklists_model->get_one($id);
            $view_data['items'] = $this->checklists_model->get_items($id);
        }

        $types_model = model('LaudosTecnicos\Models\Laudo_types_model');
        $view_data['types_dropdown'] = $types_model->get_dropdown();
        $view_data['response_types'] = $this->_get_response_types();
        $view_data['severities'] = array('low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica');

        return $this->template->rander('LaudosTecnicos\Views\technical\checklists\edit', $view_data);
    }

    public function checklist_save()
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'name' => $this->request->getPost('name'),
            'code' => strtoupper($this->request->getPost('code')),
            'category' => $this->request->getPost('category'),
            'laudo_type_id' => $this->request->getPost('laudo_type_id') ?: null,
            'description' => $this->request->getPost('description'),
            'status' => $this->request->getPost('status') ?: 'draft',
            'updated_by' => $this->login_user->id
        );

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
        }

        $save_id = $this->checklists_model->save($data, $id);

        if ($save_id) {
            $this->_save_checklist_items($save_id);
            return $this->response->setJSON(array('success' => true, 'data' => $save_id, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    private function _save_checklist_items($checklist_id)
    {
        $items = $this->request->getPost('items');
        if (!$items || !is_array($items)) return;

        $existing = $this->checklists_model->get_items($checklist_id);
        $existing_ids = array_map(function($i) { return $i->id; }, $existing);
        $new_ids = array();

        foreach ($items as $item_data) {
            $item_id = isset($item_data['id']) ? (int)$item_data['id'] : 0;
            
            $data = array(
                'checklist_id' => $checklist_id,
                'group_name' => $item_data['group_name'] ?? '',
                'code' => $item_data['code'] ?? '',
                'question' => $item_data['question'],
                'guidance' => $item_data['guidance'] ?? '',
                'response_type' => $item_data['response_type'] ?? 'conforme_nao_conforme',
                'expected_answer' => $item_data['expected_answer'] ?? '',
                'severity' => $item_data['severity'] ?? 'medium',
                'weight' => $item_data['weight'] ?? 1,
                'is_required' => isset($item_data['is_required']) ? 1 : 0,
                'evidence_required' => isset($item_data['evidence_required']) ? 1 : 0,
                'photo_required' => isset($item_data['photo_required']) ? 1 : 0,
                'measurement_required' => isset($item_data['measurement_required']) ? 1 : 0,
                'observation_required' => isset($item_data['observation_required']) ? 1 : 0,
                'standard_code' => $item_data['standard_code'] ?? '',
                'generates_nc' => isset($item_data['generates_nc']) ? 1 : 0,
                'sort_order' => $item_data['sort_order'] ?? 0
            );

            $new_id = $this->checklists_model->save_item($data, $item_id);
            $new_ids[] = $new_id;
        }

        // Remover itens excluídos
        foreach ($existing as $existing_item) {
            if (!in_array($existing_item->id, $new_ids)) {
                $this->checklists_model->delete_item($existing_item->id);
            }
        }
    }

    public function checklist_duplicate($id)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $new_id = $this->checklists_model->clone_checklist($id);

        if ($new_id) {
            app_redirect('laudo_technical/checklist_edit/' . $new_id);
        }
        
        app_redirect('laudo_technical/checklists');
    }

    public function checklist_publish($id)
    {
        if (!$this->_has_permission('laudos_manage_templates')) {
            app_redirect('forbidden');
        }

        $this->checklists_model->publish($id);
        app_redirect('laudo_technical/checklists');
    }

    // ==================== MEDIÇÕES ====================
    public function measurement_types()
    {
        if (!$this->_has_settings_permission()) {
            app_redirect('forbidden');
        }

        return $this->template->rander('LaudosTecnicos\Views\technical\measurements\types');
    }

    public function measurement_types_list_data()
    {
        if (!$this->_has_settings_permission()) {
            return $this->_json_permission_denied();
        }

        $list_data = $this->measurements_model->get_details(array(
            'search' => $this->request->getPost('search'),
            'active' => $this->request->getPost('active')
        ))->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = array(
                $data->id,
                $data->name,
                $data->magnitude,
                $data->unit,
                $data->reference_value ?? '-',
                $data->tolerance ?? '-',
                '<span class="badge bg-' . ($data->active ? 'success' : 'secondary') . '">' . ($data->active ? 'Ativo' : 'Inativo') . '</span>',
                modal_anchor(get_uri('laudo_technical/measurement_type_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit')))
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function measurement_type_form($id = 0)
    {
        if (!$this->_has_settings_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->measurements_model->get_one($id);
        }

        return $this->template->view('LaudosTecnicos\Views\technical\measurements\type_form', $view_data);
    }

    public function measurement_type_save()
    {
        if (!$this->_has_settings_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'name' => $this->request->getPost('name'),
            'magnitude' => $this->request->getPost('magnitude'),
            'unit' => $this->request->getPost('unit'),
            'min_value' => $this->request->getPost('min_value') ?: null,
            'max_value' => $this->request->getPost('max_value') ?: null,
            'reference_value' => $this->request->getPost('reference_value') ?: null,
            'tolerance' => $this->request->getPost('tolerance') ?: null,
            'decimal_places' => $this->request->getPost('decimal_places') ?: 2,
            'auto_classify' => $this->request->getPost('auto_classify') ? 1 : 0,
            'active' => $this->request->getPost('active') ? 1 : 0
        );

        $save_id = $this->measurements_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== EQUIPAMENTOS ====================
    public function equipment()
    {
        if (!$this->_has_settings_permission()) {
            app_redirect('forbidden');
        }

        $view_data = array(
            'status_list' => array('active' => 'Ativo', 'maintenance' => 'Em manutenção', 'inactive' => 'Inativo')
        );

        return $this->template->rander('LaudosTecnicos\Views\technical\equipment\index', $view_data);
    }

    public function equipment_list_data()
    {
        if (!$this->_has_settings_permission()) {
            return $this->_json_permission_denied();
        }

        $list_data = $this->equipment_model->get_details(array(
            'search' => $this->request->getPost('search'),
            'type' => $this->request->getPost('type'),
            'status' => $this->request->getPost('status')
        ))->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $calibration_status = '';
            if ($data->next_calibration) {
                $days = (strtotime($data->next_calibration) - time()) / 86400;
                if ($days < 0) {
                    $calibration_status = '<span class="badge bg-danger">Vencida</span>';
                } elseif ($days < 30) {
                    $calibration_status = '<span class="badge bg-warning">Próxima</span>';
                } else {
                    $calibration_status = '<span class="badge bg-success">OK</span>';
                }
            }
            
            $result[] = array(
                $data->id,
                $data->name,
                $data->type,
                $data->serial_number ?? '-',
                $data->patrimony ?? '-',
                $data->next_calibration ?? '-',
                $calibration_status,
                '<span class="badge bg-' . ($data->status === 'active' ? 'success' : ($data->status === 'maintenance' ? 'warning' : 'secondary')) . '">' . ucfirst($data->status) . '</span>',
                modal_anchor(get_uri('laudo_technical/equipment_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit')))
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function equipment_form($id = 0)
    {
        if (!$this->_has_settings_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->equipment_model->get_one($id);
        }

        return $this->template->view('LaudosTecnicos\Views\technical\equipment\form', $view_data);
    }

    public function equipment_save()
    {
        if (!$this->_has_settings_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'name' => $this->request->getPost('name'),
            'type' => $this->request->getPost('type'),
            'manufacturer' => $this->request->getPost('manufacturer'),
            'model' => $this->request->getPost('model'),
            'serial_number' => $this->request->getPost('serial_number'),
            'patrimony' => $this->request->getPost('patrimony'),
            'acquisition_date' => $this->request->getPost('acquisition_date') ?: null,
            'last_calibration' => $this->request->getPost('last_calibration') ?: null,
            'next_calibration' => $this->request->getPost('next_calibration') ?: null,
            'lab_name' => $this->request->getPost('lab_name'),
            'status' => $this->request->getPost('status') ?: 'active',
            'observations' => $this->request->getPost('observations')
        );

        $save_id = $this->equipment_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function equipment_check($id)
    {
        $valid = $this->equipment_model->is_valid($id);
        return $this->response->setJSON(array('valid' => $valid));
    }

    // ==================== NORMAS ====================
    public function standards()
    {
        if (!$this->_has_settings_permission()) {
            app_redirect('forbidden');
        }

        $view_data = array(
            'institutions' => $this->standards_model->get_institutions(),
            'categories' => $this->standards_model->get_categories()
        );

        return $this->template->rander('LaudosTecnicos\Views\technical\standards\index', $view_data);
    }

    public function standards_list_data()
    {
        if (!$this->_has_settings_permission()) {
            return $this->_json_permission_denied();
        }

        $list_data = $this->standards_model->get_details(array(
            'search' => $this->request->getPost('search'),
            'institution' => $this->request->getPost('institution'),
            'category' => $this->request->getPost('category')
        ))->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = array(
                $data->id,
                $data->code,
                $data->title,
                $data->institution,
                $data->category ?? '-',
                $data->year ?? '-',
                '<span class="badge bg-' . ($data->status === 'active' ? 'success' : 'secondary') . '">' . ucfirst($data->status) . '</span>',
                modal_anchor(get_uri('laudo_technical/standard_form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit', 'title' => app_lang('edit')))
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function standard_form($id = 0)
    {
        if (!$this->_has_settings_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->standards_model->get_one($id);
        }

        return $this->template->view('LaudosTecnicos\Views\technical\standards\form', $view_data);
    }

    public function standard_save()
    {
        if (!$this->_has_settings_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'code' => strtoupper($this->request->getPost('code')),
            'title' => $this->request->getPost('title'),
            'institution' => $this->request->getPost('institution'),
            'category' => $this->request->getPost('category'),
            'edition' => $this->request->getPost('edition'),
            'year' => $this->request->getPost('year'),
            'description' => $this->request->getPost('description'),
            'link' => $this->request->getPost('link'),
            'status' => $this->request->getPost('status') ?: 'active',
            'observations' => $this->request->getPost('observations')
        );

        $save_id = $this->standards_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== HELPERS ====================
    private function _get_response_types()
    {
        return array(
            'conforme_nao_conforme' => 'Conforme / Não conforme',
            'nao_se_aplica' => 'Não se aplica',
            'sim_nao' => 'Sim ou não',
            'text' => 'Texto livre',
            'number' => 'Número',
            'date' => 'Data',
            'single_select' => 'Seleção única',
            'multi_select' => 'Seleção múltipla'
        );
    }

    private function _has_permission($permission)
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), $permission) == '1';
    }

    private function _has_settings_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_settings') == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setStatusCode(403)->setJSON(array('success' => false, 'message' => app_lang('access_denied')));
    }
}