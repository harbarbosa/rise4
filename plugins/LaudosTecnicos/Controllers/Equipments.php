<?php

namespace LaudosTecnicos\Controllers;

class Equipments extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureEquipmentsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\equipments\\index', array(
            'can_manage_equipments' => \LaudosTecnicos\Plugin::canManageEquipments($this->login_user),
        ));
    }

    public function list_data()
    {
        $this->ensureEquipmentsAccess();

        $rows = $this->equipments_model->get_details(array(
            'search' => trim((string) $this->request->getPost('search')),
            'status' => trim((string) $this->request->getPost('status')),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $status = $this->equipments_model->calibration_status($row);
            $actions = modal_anchor(get_uri('laudostecnicos/equipamentos/modal_form/' . $row->id), "<i data-feather='edit' class='icon-16'></i>", array('title' => app_lang('edit'), 'class' => 'btn btn-sm btn-outline-secondary'));
            $actions .= ' ' . js_anchor("<i data-feather='power' class='icon-16'></i>", array('title' => 'Alternar status', 'class' => 'btn btn-sm btn-outline-dark', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/equipamentos/toggle_status/' . $row->id), 'data-action' => 'delete-confirmation', 'data-confirmation-title' => 'Alternar status do equipamento?', 'data-reload-on-success' => true));
            $actions .= ' ' . js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger', 'data-id' => $row->id, 'data-action-url' => get_uri('laudostecnicos/equipamentos/delete'), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true));

            $result[] = array(
                esc($row->name),
                esc($row->equipment_type ?: '-'),
                esc($row->manufacturer ?: '-'),
                esc($row->model ?: '-'),
                esc($row->serial_number ?: '-'),
                esc($row->patrimony_number ?: '-'),
                esc($row->last_calibration ?: '-'),
                esc($row->next_calibration ?: '-'),
                $this->statusBadge((string) ($row->status ?: '')),
                $this->calibrationBadge($status),
                $actions,
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function modal_form($id = 0)
    {
        $this->ensureEquipmentsAccess();

        $view_data['model_info'] = $id ? $this->equipments_model->get_one((int) $id) : (object) array(
            'id' => '',
            'name' => '',
            'equipment_type' => '',
            'manufacturer' => '',
            'model' => '',
            'serial_number' => '',
            'patrimony_number' => '',
            'acquisition_date' => '',
            'last_calibration' => '',
            'next_calibration' => '',
            'certificate' => '',
            'laboratory' => '',
            'status' => 'active',
            'observations' => '',
        );

        return $this->template->view('LaudosTecnicos\\Views\\equipments\\modal_form', $view_data);
    }

    public function save()
    {
        $this->ensureEquipmentsAccess();

        $id = (int) $this->request->getPost('id');
        $data = array(
            'name' => trim((string) $this->request->getPost('name')),
            'equipment_type' => trim((string) $this->request->getPost('equipment_type')),
            'manufacturer' => trim((string) $this->request->getPost('manufacturer')),
            'model' => trim((string) $this->request->getPost('model')),
            'serial_number' => trim((string) $this->request->getPost('serial_number')),
            'patrimony_number' => trim((string) $this->request->getPost('patrimony_number')),
            'acquisition_date' => trim((string) $this->request->getPost('acquisition_date')),
            'last_calibration' => trim((string) $this->request->getPost('last_calibration')),
            'next_calibration' => trim((string) $this->request->getPost('next_calibration')),
            'certificate' => trim((string) $this->request->getPost('certificate')),
            'laboratory' => trim((string) $this->request->getPost('laboratory')),
            'status' => trim((string) $this->request->getPost('status')) ?: 'active',
            'observations' => trim((string) $this->request->getPost('observations')),
        );

        if ($data['name'] === '') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $ok = $this->equipments_model->save_from_post($data, $id ?: null);
        if ($ok === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function toggle_status($id = 0)
    {
        $this->ensureEquipmentsAccess();
        $row = $this->equipments_model->get_one((int) $id);
        if (!$row || !$row->id) {
            return $this->response->setJSON(array('success' => false));
        }

        $status = (string) ($row->status ?? 'active');
        $new_status = $status === 'active' ? 'blocked' : 'active';
        $ok = $this->equipments_model->save_from_post(array('status' => $new_status), (int) $id);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    public function delete()
    {
        $this->ensureEquipmentsAccess();
        $id = (int) $this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        if ($this->equipments_model->is_in_use($id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('laudostecnicos_equipment_in_use')));
        }

        $ok = $this->equipments_model->delete($id);
        return $this->response->setJSON(array('success' => (bool) $ok));
    }

    private function statusBadge(string $status): string
    {
        $map = array('active' => 'bg-success', 'blocked' => 'bg-danger', 'maintenance' => 'bg-warning text-dark', 'inactive' => 'bg-secondary');
        $class = get_array_value($map, $status) ?: 'bg-secondary';
        return '<span class="badge ' . $class . '">' . esc($status ?: 'inactive') . '</span>';
    }

    private function calibrationBadge(string $status): string
    {
        $map = array('valid' => 'bg-success', 'due' => 'bg-warning text-dark', 'expired' => 'bg-danger', 'blocked' => 'bg-danger', 'maintenance' => 'bg-info', 'unknown' => 'bg-secondary');
        $class = get_array_value($map, $status) ?: 'bg-secondary';
        return '<span class="badge ' . $class . '">' . esc($status) . '</span>';
    }
}
