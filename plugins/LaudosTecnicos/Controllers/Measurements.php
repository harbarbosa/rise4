<?php

namespace LaudosTecnicos\Controllers;

class Measurements extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureMeasurementsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\measurements\\index', array(
            'can_manage_measurements' => \LaudosTecnicos\Plugin::canManageMeasurements($this->login_user),
            'types_dropdown' => $this->measurement_types_model->get_active_dropdown(true),
        ));
    }

    public function list_data()
    {
        $this->ensureMeasurementsAccess();

        $rows = $this->measurements_model->get_details(array(
            'laudo_id' => (int) $this->request->getPost('laudo_id'),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/medicoes/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $result[] = array(
                esc($row->type_name ?: '-'),
                esc($row->value),
                esc($row->type_unit ?: $row->unit ?: '-'),
                '<span class="badge bg-info">' . esc($row->result ?: '-') . '</span>',
                esc($row->location ?: '-'),
                esc($row->equipment_name ?: '-'),
                esc($row->measured_at ?: '-'),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureMeasurementsAccess();

        $view_data['model_info'] = $id ? $this->measurements_model->get_one((int) $id) : (object) array(
            'id' => '',
            'measurement_type_id' => '',
            'laudo_id' => '',
            'checklist_item_id' => '',
            'value' => '',
            'unit' => '',
            'result' => '',
            'measured_at' => get_current_utc_time(),
            'location' => '',
            'equipment_id' => '',
            'responsible_id' => '',
            'photo' => '',
            'observation' => '',
            'gps_lat' => '',
            'gps_lng' => '',
            'gps_text' => '',
        );
        $view_data['types_dropdown'] = $this->measurement_types_model->get_active_dropdown(true);
        $view_data['equipments_dropdown'] = $this->equipments_model->get_active_dropdown(true);

        return $this->template->view('LaudosTecnicos\\Views\\measurements\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureMeasurementsAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'measurement_type_id' => (int) $this->request->getPost('measurement_type_id'),
            'laudo_id' => (int) $this->request->getPost('laudo_id'),
            'checklist_item_id' => (int) $this->request->getPost('checklist_item_id'),
            'value' => trim((string) $this->request->getPost('value')),
            'unit' => trim((string) $this->request->getPost('unit')),
            'measured_at' => trim((string) $this->request->getPost('measured_at')),
            'location' => trim((string) $this->request->getPost('location')),
            'equipment_id' => (int) $this->request->getPost('equipment_id'),
            'responsible_id' => (int) $this->request->getPost('responsible_id'),
            'photo' => trim((string) $this->request->getPost('photo')),
            'observation' => trim((string) $this->request->getPost('observation')),
            'gps_lat' => trim((string) $this->request->getPost('gps_lat')),
            'gps_lng' => trim((string) $this->request->getPost('gps_lng')),
            'gps_text' => trim((string) $this->request->getPost('gps_text')),
        );

        if (!$data['measurement_type_id'] || $data['value'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $ok = $this->measurements_model->save_from_post($data, $id ?: null);
        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $measurement_type = $this->measurement_types_model->get_one((int) $data['measurement_type_id']);
        $classification = $this->measurements_model->classify_value($data, $measurement_type);
        if (!in_array($classification, array('conforme', 'informado'), true)) {
            laudostecnicos_create_nonconformity_from_event(array(
                'title' => 'Medição fora da faixa',
                'description' => trim((string) $data['observation']) ?: 'Resultado fora da faixa esperada.',
                'client_id' => 0,
                'laudo_id' => (int) $data['laudo_id'],
                'inspection_id' => 0,
                'classification' => $classification === 'critico' ? 'critica' : 'alta',
                'probability' => 3,
                'impact' => $classification === 'critico' ? 4 : 3,
                'risk_level' => $classification,
                'risk_color' => '#dc3545',
                'recommendation' => 'Reavaliar a medicao e corrigir a condicao de campo.',
                'suggested_deadline' => date('Y-m-d', strtotime('+7 days')),
                'responsible_id' => (int) $data['responsible_id'],
                'status' => 'open',
                'identified_at' => get_current_utc_time(),
                'evidence_json' => array('measurement' => $data),
                'photos_json' => array(),
                'created_by' => (int) ($this->login_user->id ?? 0),
                'updated_by' => (int) ($this->login_user->id ?? 0),
            ));
        }

        if (!empty($data['equipment_id']) && !$this->equipments_model->is_valid_for_use((int) $data['equipment_id'])) {
            laudostecnicos_create_nonconformity_from_event(array(
                'title' => 'Equipamento irregular em uso',
                'description' => 'Equipamento utilizado na medicao esta com situacao/calibracao irregular.',
                'client_id' => 0,
                'laudo_id' => (int) $data['laudo_id'],
                'inspection_id' => 0,
                'equipment_id' => (int) $data['equipment_id'],
                'classification' => 'alta',
                'probability' => 4,
                'impact' => 3,
                'risk_level' => 'alta',
                'risk_color' => '#fd7e14',
                'recommendation' => 'Bloquear uso do equipamento e providenciar calibracao/manutencao.',
                'suggested_deadline' => date('Y-m-d', strtotime('+3 days')),
                'status' => 'open',
                'identified_at' => get_current_utc_time(),
                'evidence_json' => array('equipment_id' => $data['equipment_id']),
                'photos_json' => array(),
                'created_by' => (int) ($this->login_user->id ?? 0),
                'updated_by' => (int) ($this->login_user->id ?? 0),
            ));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }
}
