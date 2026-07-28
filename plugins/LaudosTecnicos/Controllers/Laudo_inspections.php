<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudo_inspections_model;
use LaudosTecnicos\Models\Laudo_photos_model;
use LaudosTecnicos\Models\Laudo_equipment_model;
use LaudosTecnicos\Models\Laudo_checklists_model;

class Laudo_inspections extends Security_Controller
{
    protected $inspections_model;
    protected $photos_model;
    protected $equipment_model;
    protected $checklists_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        
        $this->inspections_model = model('LaudosTecnicos\Models\Laudo_inspections_model');
        $this->photos_model = model('LaudosTecnicos\Models\Laudo_photos_model');
        $this->equipment_model = model('LaudosTecnicos\Models\Laudo_equipment_model');
        $this->checklists_model = model('LaudosTecnicos\Models\Laudo_checklists_model');
    }

    // ==================== LISTAGEM ====================
    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $status_list = array(
            'planned' => 'Planejada',
            'scheduled' => 'Agendada',
            'confirmed' => 'Confirmada',
            'on_route' => 'Em deslocamento',
            'iniciated' => 'Iniciada',
            'paused' => 'Pausada',
            'completed' => 'Concluída',
            'unproductive' => 'Improdutiva',
            'reagendada' => 'Reagendada',
            'canceled' => 'Cancelada'
        );

        $view_data = array(
            'status_list' => $status_list
        );

        return $this->template->rander('LaudosTecnicos\Views\inspections\index', $view_data);
    }

    public function list_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $options = array(
            'status' => $this->request->getPost('status'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date' => $this->request->getPost('end_date'),
            'responsible_id' => $this->request->getPost('responsible_id')
        );

        $list_data = $this->inspections_model->get_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    private function _make_row($data)
    {
        $status_colors = array(
            'planned' => 'secondary',
            'scheduled' => 'info',
            'confirmed' => 'info',
            'on_route' => 'warning',
            'iniciated' => 'primary',
            'paused' => 'warning',
            'completed' => 'success',
            'unproductive' => 'danger',
            'reagendada' => 'warning',
            'canceled' => 'dark'
        );
        
        return array(
            $data->id,
            $data->code,
            $data->laudo_title ?? $data->laudo_number,
            $data->company_name ?? '-',
            $data->scheduled_date,
            $data->scheduled_time ?? '-',
            $data->responsible_name ?? '-',
            '<span class="badge bg-' . ($status_colors[$data->status] ?? 'secondary') . '">' . $data->status . '</span>',
            $this->_get_actions($data)
        );
    }

    private function _get_actions($data)
    {
        $actions = '<a href="' . get_uri('laudo_inspections/view/' . $data->id) . '" class="btn btn-default btn-sm" title="Visualizar"><i data-feather="eye" class="icon-16"></i></a> ';
        $actions .= modal_anchor(get_uri('laudo_inspections/form/' . $data->id), '<i data-feather="edit-2" class="icon-16"></i>', array('class' => 'edit btn btn-default btn-sm', 'title' => 'Editar'));
        
        return $actions;
    }

    // ==================== FORMULÁRIO ====================
    public function form($id = 0)
    {
        if (!$this->_has_edit_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $view_data = array();
        
        if ($id) {
            $view_data['model_info'] = $this->inspections_model->get_one($id);
        }

        // Dropdowns
        $laudos_model = model('LaudosTecnicos\Models\Laudos_model');
        $laudos = $laudos_model->get_details()->getResult();
        $laudos_dropdown = array();
        foreach ($laudos as $l) {
            $laudos_dropdown[$l->id] = $l->laudo_number . ' - ' . substr($l->title, 0, 40);
        }
        
        $users_model = model('App\Models\Users_model');
        $team_dropdown = $users_model->get_dropdown();
        
        $equipment_dropdown = $this->equipment_model->get_dropdown();
        
        $view_data['laudos_dropdown'] = $laudos_dropdown;
        $view_data['team_dropdown'] = $team_dropdown;
        $view_data['equipment_dropdown'] = $equipment_dropdown;
        $view_data['status_list'] = $this->_get_status_list();

        return $this->template->view('LaudosTecnicos\Views\inspections\form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'laudo_id' => $this->request->getPost('laudo_id') ?: null,
            'client_id' => $this->request->getPost('client_id') ?: null,
            'location' => $this->request->getPost('location'),
            'address' => $this->request->getPost('address'),
            'inspection_type' => $this->request->getPost('inspection_type'),
            'scheduled_date' => $this->request->getPost('scheduled_date'),
            'scheduled_time' => $this->request->getPost('scheduled_time') ?: null,
            'duration_minutes' => $this->request->getPost('duration_minutes') ?: 120,
            'responsible_id' => $this->request->getPost('responsible_id') ?: null,
            'team_ids' => $this->request->getPost('team_ids') ? json_encode($this->request->getPost('team_ids')) : null,
            'vehicle' => $this->request->getPost('vehicle'),
            'equipment_ids' => $this->request->getPost('equipment_ids') ? json_encode($this->request->getPost('equipment_ids')) : null,
            'observations' => $this->request->getPost('observations'),
            'status' => $this->request->getPost('status') ?: 'planned',
            'created_by' => $this->login_user->id
        );

        $save_id = $this->inspections_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'data' => $save_id, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    // ==================== VISUALIZAÇÃO / EXECUÇÃO ====================
    public function view($id)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $inspection = $this->inspections_model->get_one($id);
        
        if (!$inspection) {
            app_redirect('laudo_inspections');
        }

        // Fotos
        $photos = $this->photos_model->get_for_inspection($id);
        
        // Checklists e respostas
        $checklist_answers_model = model('LaudosTecnicos\Models\Laudo_checklist_answers_model');
        $answers = $checklist_answers_model->get_for_laudo($inspection->laudo_id);
        
        // Estatísticas
        $stats = $checklist_answers_model->get_stats($inspection->laudo_id);

        $view_data = array(
            'inspection' => $inspection,
            'photos' => $photos,
            'answers' => $answers,
            'stats' => $stats,
            'status_list' => $this->_get_status_list()
        );

        return $this->template->rander('LaudosTecnicos\Views\inspections\view', $view_data);
    }

    // ==================== CHECK-IN ====================
    public function checkin($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $lat = $this->request->getPost('lat');
        $lng = $this->request->getPost('lng');
        $accuracy = $this->request->getPost('accuracy');
        $observation = $this->request->getPost('observation');

        $data = array(
            'checkin_at' => get_my_local_time(),
            'checkin_lat' => $lat,
            'checkin_lng' => $lng,
            'checkin_accuracy' => $accuracy,
            'status' => 'iniciated'
        );

        $this->inspections_model->save($data, $id);

        return $this->response->setJSON(array('success' => true, 'message' => 'Check-in realizado'));
    }

    public function checkout($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $data = array(
            'checkout_at' => get_my_local_time(),
            'status' => 'completed'
        );

        $this->inspections_model->save($data, $id);

        return $this->response->setJSON(array('success' => true, 'message' => 'Check-out realizado'));
    }

    // ==================== FOTOGRAFIAS ====================
    public function upload_photo($laudo_id, $inspection_id = null)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $file = $this->request->getFile('photo');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(array('success' => false, 'message' => 'Arquivo inválido'));
        }

        // Salvar arquivo
        $upload_path = 'uploads/laudos/' . $laudo_id . '/photos/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $new_name = $file->getRandomName();
        $file->move($upload_path, $new_name);
        
        $original_path = $upload_path . $new_name;
        
        // Gerar hash
        $hash = $this->photos_model->generate_hash($original_path);

        $data = array(
            'laudo_id' => $laudo_id,
            'inspection_id' => $inspection_id,
            'original_file' => $original_path,
            'thumbnail_file' => $original_path, // Em produção, gerar thumbnail
            'taken_at' => get_my_local_time(),
            'user_id' => $this->login_user->id,
            'gps_lat' => $this->request->getPost('lat'),
            'gps_lng' => $this->request->getPost('lng'),
            'location_name' => $this->request->getPost('location'),
            'caption' => $this->request->getPost('caption'),
            'observation' => $this->request->getPost('observation'),
            'file_hash' => $hash
        );

        $photo_id = $this->photos_model->save($data, 0);

        return $this->response->setJSON(array(
            'success' => true,
            'photo_id' => $photo_id,
            'url' => base_url($original_path)
        ));
    }

    public function delete_photo($id)
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        if ($this->photos_model->delete($id)) {
            return $this->response->setJSON(array('success' => true));
        }

        return $this->response->setJSON(array('success' => false));
    }

    // ==================== AGENDA ====================
    public function calendar()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $view_data = array(
            'team_dropdown' => model('App\Models\Users_model')->get_dropdown()
        );

        return $this->template->rander('LaudosTecnicos\Views\inspections\calendar', $view_data);
    }

    public function calendar_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');
        $responsible_id = $this->request->getGet('responsible_id');

        $inspections = $this->inspections_model->get_for_calendar($start, $end, $responsible_id);

        $events = array();
        foreach ($inspections as $ins) {
            $status_colors = array(
                'planned' => '#6c757d',
                'scheduled' => '#0dcaf0',
                'confirmed' => '#0d6efd',
                'on_route' => '#ffc107',
                'iniciated' => '#0d6efd',
                'paused' => '#fd7e14',
                'completed' => '#198754',
                'unproductive' => '#dc3545',
                'reagendada' => '#fd7e14',
                'canceled' => '#6c757d'
            );

            $events[] = array(
                'id' => $ins->id,
                'title' => ($ins->laudo_number ?? $ins->code) . ' - ' . ($ins->client_name ?? 'Sem cliente'),
                'start' => $ins->scheduled_date . 'T' . ($ins->scheduled_time ?? '09:00:00'),
                'end' => $ins->scheduled_date . 'T' . ($ins->scheduled_time ? date('H:i:s', strtotime($ins->scheduled_time . ' +' . ($ins->duration_minutes ?? 120) . ' minutes')) : '11:00:00'),
                'backgroundColor' => $status_colors[$ins->status] ?? '#6c757d',
                'borderColor' => $status_colors[$ins->status] ?? '#6c757d',
                'url' => get_uri('laudo_inspections/view/' . $ins->id)
            );
        }

        return $this->response->setJSON($events);
    }

    // ==================== CONFLITOS ====================
    public function check_conflicts()
    {
        $date = $this->request->getPost('date');
        $time = $this->request->getPost('time');
        $responsible_id = $this->request->getPost('responsible_id');
        $exclude_id = $this->request->getPost('exclude_id');

        $conflicts = $this->inspections_model->check_conflicts($date, $time, $responsible_id, $exclude_id);

        return $this->response->setJSON(array(
            'has_conflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts
        ));
    }

    public function check_equipment_conflicts()
    {
        $date = $this->request->getPost('date');
        $equipment_ids = $this->request->getPost('equipment_ids');
        $exclude_id = $this->request->getPost('exclude_id');

        $conflicts = $this->inspections_model->check_equipment_conflicts($date, $equipment_ids, $exclude_id);

        return $this->response->setJSON(array(
            'has_conflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts
        ));
    }

    // ==================== HELPERS ====================
    private function _get_status_list()
    {
        return array(
            'planned' => 'Planejada',
            'scheduled' => 'Agendada',
            'confirmed' => 'Confirmada',
            'on_route' => 'Em deslocamento',
            'iniciated' => 'Iniciada',
            'paused' => 'Pausada',
            'completed' => 'Concluída',
            'unproductive' => 'Improdutiva',
            'reagendada' => 'Reagendada',
            'canceled' => 'Cancelada'
        );
    }

    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_view') == '1';
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