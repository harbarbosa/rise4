<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\AneelImporterService;
use Fotovoltaico\Services\AuditService;

class Tariffs extends Security_Controller
{
    private $Tariffs_model;
    private $Distributors_model;
    private $AuditService;
    private $AneelImporterService;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canViewTariffs($this->login_user) && !Plugin::canManageTariffs($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();

        $this->Tariffs_model = model('Fotovoltaico\\Models\\Tariffs_model');
        $this->Distributors_model = model('Fotovoltaico\\Models\\Distributors_model');
        $this->AuditService = new AuditService();
        $this->AneelImporterService = new AneelImporterService();
    }

    public function index()
    {
        $view_data = array(
            'distributors_dropdown' => $this->_get_distributors_dropdown(true),
            'vigency_dropdown' => $this->_get_vigency_dropdown(true),
            'source_dropdown' => $this->_get_source_dropdown(true),
            'can_manage_tariffs' => Plugin::canManageTariffs($this->login_user)
        );

        return $this->template->rander('Fotovoltaico\\Views\\tariffs\\index', $view_data);
    }

    public function list_data()
    {
        $distributor_id = get_only_numeric_value($this->request->getPost('distributor_id'));
        $vigency_status = trim((string) $this->request->getPost('vigency_status'));
        $source = trim((string) $this->request->getPost('source'));

        $options = array(
            'reference_date' => date('Y-m-d'),
            'vigency_status' => $vigency_status
        );
        if ($distributor_id) {
            $options['distributor_id'] = (int) $distributor_id;
        }
        if ($source !== '') {
            $options['source'] = $source;
        }

        $list_data = $this->Tariffs_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        echo json_encode(array('data' => $result));
    }

    public function modal_form()
    {
        $this->validate_submitted_data(array('id' => 'numeric'));

        $view_data = array(
            'model_info' => $this->Tariffs_model->get_one((int) $this->request->getPost('id')),
            'distributors_dropdown' => $this->_get_distributors_dropdown(false),
            'modalities_dropdown' => $this->_get_modalities_dropdown(false),
            'subgroups_dropdown' => $this->_get_subgroups_dropdown(false),
            'flags_dropdown' => $this->_get_flags_dropdown(false),
        );

        return $this->template->view('Fotovoltaico\\Views\\tariffs\\modal_form', $view_data);
    }

    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'distributor_id' => 'required|numeric',
            'modality' => 'required',
            'subgroup' => 'required',
            'valid_from' => 'required'
        ));

        if (!Plugin::canManageTariffs($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $distributor_id = (int) $this->request->getPost('distributor_id');
        $modality = trim((string) $this->request->getPost('modality'));
        $subgroup = trim((string) $this->request->getPost('subgroup'));
        $valid_from = trim((string) $this->request->getPost('valid_from'));
        $valid_to = trim((string) $this->request->getPost('valid_to')) ?: null;
        $active = $this->request->getPost('active') ? 1 : 0;

        $data = array(
            'distributor_id' => $distributor_id,
            'modality' => $modality,
            'subgroup' => $subgroup,
            'tariff_class' => trim((string) $this->request->getPost('tariff_class')) ?: null,
            'tariff_subclass' => trim((string) $this->request->getPost('tariff_subclass')) ?: null,
            'group_name' => trim((string) $this->request->getPost('group_name')) ?: null,
            'time_slot' => trim((string) $this->request->getPost('time_slot')) ?: null,
            'unit' => trim((string) $this->request->getPost('unit')) ?: null,
            'resolution' => trim((string) $this->request->getPost('resolution')) ?: null,
            'tariff_detail' => trim((string) $this->request->getPost('tariff_detail')) ?: null,
            'tariff_base' => trim((string) $this->request->getPost('tariff_base')) ?: null,
            'te' => (float) unformat_currency($this->request->getPost('te')),
            'tusd' => (float) unformat_currency($this->request->getPost('tusd')),
            'flag_name' => trim((string) $this->request->getPost('flag_name')) ?: null,
            'flag_value' => (float) unformat_currency($this->request->getPost('flag_value')),
            'source' => $id ? ($this->request->getPost('source') ?: 'manual') : 'manual',
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
            'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            'sync_notes' => trim((string) $this->request->getPost('sync_notes')) ?: null,
            'active' => $active,
            'updated_at' => get_my_local_time(),
        );
        $data = clean_data($data);
        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->Tariffs_model->ci_save($data, $id);
        if ($save_id) {
            if ($active) {
                $this->Tariffs_model->close_previous_vigency($distributor_id, $modality, $subgroup, $valid_from, $save_id);
            }
            $this->Tariffs_model->sync_current_flags($distributor_id);

            $this->_audit('tariff', $save_id, $id ? 'tariff_updated' : 'tariff_created', array(), array(
                'distributor_id' => $distributor_id,
                'modality' => $modality,
                'subgroup' => $subgroup,
                'valid_from' => $valid_from,
                'valid_to' => $valid_to,
                'active' => $active,
            ));

            echo json_encode(array(
                'success' => true,
                'id' => $save_id,
                'data' => $this->_make_row($this->Tariffs_model->get_details(array('id' => $save_id))->getRow()),
                'message' => app_lang('record_saved')
            ));
            return;
        }

        echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function delete()
    {
        $this->validate_submitted_data(array('id' => 'required|numeric'));

        if (!Plugin::canManageTariffs($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        if ($this->Tariffs_model->delete($id)) {
            $this->_audit('tariff', $id, 'tariff_deleted', array(), array());
            echo json_encode(array('success' => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    public function import_aneel()
    {
        if (!Plugin::canManageTariffs($this->login_user)) {
            app_redirect('forbidden');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $result = $this->AneelImporterService->importOfficial(array(
            'batch_size' => 250,
            'created_by' => $this->login_user->id,
        ));

        if (get_array_value($result, 'success')) {
            $this->_audit('tariff', 0, 'tariffs_imported_from_aneel', array(), get_array_value($result, 'data') ?: array());
        }

        echo json_encode(array(
            'success' => (bool) get_array_value($result, 'success'),
            'message' => get_array_value($result, 'message') ?: (get_array_value($result, 'success') ? app_lang('record_saved') : app_lang('error_occurred')),
            'data' => get_array_value($result, 'data') ?: array(),
            'errors' => get_array_value($result, 'errors') ?: array(),
        ));
    }

    private function _make_row($data)
    {
        $source = trim((string) ($data->source ?? 'manual'));
        return array(
            esc($data->distributor_title ?: '-'),
            esc($data->tariff_class ?: '-'),
            esc($data->modality ?: '-'),
            esc($data->subgroup ?: '-'),
            esc($data->time_slot ?: '-'),
            to_currency((float) $data->te, 'R$'),
            to_currency((float) $data->tusd, 'R$'),
            esc($data->flag_name ?: '-'),
            to_currency((float) $data->flag_value, 'R$'),
            esc($data->valid_from ?: '-'),
            esc($data->valid_to ?: '-'),
            "<span class='badge " . ($data->current_status || $data->is_current ? "bg-success" : "bg-secondary") . "'>" . esc($data->current_status || $data->is_current ? app_lang('fotovoltaico_vigency_current') : app_lang('fotovoltaico_vigency_expired')) . "</span>",
            "<span class='badge " . ($source === 'aneel' ? "bg-info" : "bg-secondary") . "'>" . esc($source === 'aneel' ? app_lang('fotovoltaico_source_aneel') : app_lang('fotovoltaico_source_manual')) . "</span>",
            $data->active ? "<span class='badge bg-success'>" . esc(app_lang('active')) . "</span>" : "<span class='badge bg-secondary'>" . esc(app_lang('inactive')) . "</span>",
            Plugin::canManageTariffs($this->login_user)
                ? modal_anchor(get_uri('fotovoltaico/tariffs/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array('class' => 'edit', 'title' => app_lang('fotovoltaico_edit_tariff'), 'data-post-id' => $data->id))
                    . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'delete', 'data-id' => $data->id, 'data-action-url' => get_uri('fotovoltaico/tariffs/delete'), 'data-action' => 'delete'))
                : '-'
        );
    }

    private function _get_distributors_dropdown($for_filter = false)
    {
        $distributors = $for_filter
            ? $this->Distributors_model->get_available_distributors()
            : $this->Distributors_model->get_details(array('active_only' => 1))->getResult();
        if ($for_filter) {
            $list = array(array('id' => '', 'text' => '-'));
            foreach ($distributors as $distributor) {
                $list[] = array('id' => (int) $distributor->id, 'text' => $distributor->title);
            }
            return json_encode($list);
        }

        $dropdown = array('' => '-');
        foreach ($distributors as $distributor) {
            $dropdown[$distributor->id] = $distributor->title;
        }
        return $dropdown;
    }

    private function _get_modalities_dropdown($for_filter = false)
    {
        $items = array(
            '' => '-',
            'convencional' => app_lang('fotovoltaico_tariff_modality_convencional'),
            'branca' => app_lang('fotovoltaico_tariff_modality_branca'),
            'verde' => app_lang('fotovoltaico_tariff_modality_verde'),
            'azul' => app_lang('fotovoltaico_tariff_modality_azul'),
        );
        return $for_filter ? $this->_to_json_dropdown($items) : $items;
    }

    private function _get_source_dropdown($for_filter = false)
    {
        $items = array(
            '' => '-',
            'manual' => app_lang('fotovoltaico_source_manual'),
            'aneel' => app_lang('fotovoltaico_source_aneel'),
        );

        return $for_filter ? $this->_to_json_dropdown($items) : $items;
    }

    private function _get_subgroups_dropdown($for_filter = false)
    {
        $items = array(
            '' => '-',
            'b1' => 'B1',
            'b2' => 'B2',
            'b3' => 'B3',
            'a1' => 'A1',
            'a4' => 'A4',
            'total' => app_lang('fotovoltaico_tariff_subgroup_total'),
        );
        return $for_filter ? $this->_to_json_dropdown($items) : $items;
    }

    private function _get_flags_dropdown($for_filter = false)
    {
        $items = array(
            '' => '-',
            'verde' => app_lang('fotovoltaico_tariff_flag_green'),
            'amarela' => app_lang('fotovoltaico_tariff_flag_yellow'),
            'vermelha_p1' => app_lang('fotovoltaico_tariff_flag_red_p1'),
            'vermelha_p2' => app_lang('fotovoltaico_tariff_flag_red_p2'),
        );
        return $for_filter ? $this->_to_json_dropdown($items) : $items;
    }

    private function _get_vigency_dropdown($for_filter = false)
    {
        $items = array(
            '' => '-',
            'current' => app_lang('fotovoltaico_vigency_current'),
            'expired' => app_lang('fotovoltaico_vigency_expired'),
            'future' => app_lang('fotovoltaico_vigency_future'),
        );
        return $for_filter ? $this->_to_json_dropdown($items) : $items;
    }

    private function _to_json_dropdown($items)
    {
        $list = array();
        foreach ($items as $id => $text) {
            $list[] = array('id' => $id, 'text' => $text);
        }
        return json_encode($list);
    }

    private function _audit($entity_type, $entity_id, $action, $old_data = array(), $new_data = array())
    {
        try {
            return $this->AuditService->record($entity_type, $entity_id, $action, $old_data, $new_data, array(
                'created_by' => $this->login_user->id,
            ));
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Audit error: ' . $e->getMessage());
            return false;
        }
    }
}
