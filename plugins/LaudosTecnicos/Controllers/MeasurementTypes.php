<?php

namespace LaudosTecnicos\Controllers;

class MeasurementTypes extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureMeasurementsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\measurement_types\\index', array(
            'can_manage_measurements' => \LaudosTecnicos\Plugin::canManageMeasurements($this->login_user),
        ));
    }

    public function list_data()
    {
        $this->ensureMeasurementsAccess();

        $rows = $this->measurement_types_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => trim((string) $this->request->getPost('status')),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $actions = modal_anchor(get_uri('laudostecnicos/tipos-medicao/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= ' ' . js_anchor("<i data-feather='power' class='icon-16'></i>", array('title' => empty($row->status) || $row->status === 'inactive' ? 'Ativar' : 'Inativar', 'class' => 'btn btn-sm btn-outline-dark', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/tipos-medicao/toggle_status/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Alterar status?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/tipos-medicao/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->name),
                esc($row->quantity ?: '-'),
                esc($row->unit ?: '-'),
                esc($row->min_value !== null ? $row->min_value : '-'),
                esc($row->max_value !== null ? $row->max_value : '-'),
                esc($row->reference_value !== null ? $row->reference_value : '-'),
                esc($row->tolerance_value !== null ? $row->tolerance_value : '-'),
                (int) $row->decimal_places,
                !empty($row->auto_classification) ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Nao</span>',
                (string) $row->status === 'active' ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>',
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureMeasurementsAccess();

        $view_data['model_info'] = $id ? $this->measurement_types_model->get_one((int) $id) : (object) array(
            'id' => '',
            'name' => '',
            'quantity' => '',
            'unit' => '',
            'min_value' => '',
            'max_value' => '',
            'reference_value' => '',
            'tolerance_value' => '',
            'decimal_places' => 2,
            'auto_classification' => 1,
            'description' => '',
            'status' => 'active',
        );

        return $this->template->view('LaudosTecnicos\\Views\\measurement_types\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureMeasurementsAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'name' => trim((string) $this->request->getPost('name')),
            'quantity' => trim((string) $this->request->getPost('quantity')),
            'unit' => trim((string) $this->request->getPost('unit')),
            'min_value' => $this->request->getPost('min_value') !== '' ? $this->request->getPost('min_value') : null,
            'max_value' => $this->request->getPost('max_value') !== '' ? $this->request->getPost('max_value') : null,
            'reference_value' => $this->request->getPost('reference_value') !== '' ? $this->request->getPost('reference_value') : null,
            'tolerance_value' => $this->request->getPost('tolerance_value') !== '' ? $this->request->getPost('tolerance_value') : null,
            'decimal_places' => (int) $this->request->getPost('decimal_places'),
            'auto_classification' => $this->request->getPost('auto_classification') ? 1 : 0,
            'description' => trim((string) $this->request->getPost('description')),
            'status' => trim((string) $this->request->getPost('status')) ?: 'active',
        );

        if ($data['name'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $ok = $this->measurement_types_model->save_from_post($data, $id ?: null);
        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureMeasurementsAccess();
        return $this->response->setJSON(array('success' => (bool) $this->measurement_types_model->toggle_status((int) $id)));
    }

    public function delete()
    {
        $this->ensureMeasurementsAccess();
        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        if ($this->measurement_types_model->is_in_use($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('laudostecnicos_measurement_type_in_use')));
        }

        $ok = $this->measurement_types_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }
}
