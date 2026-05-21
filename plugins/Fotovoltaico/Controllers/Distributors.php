<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\AneelImporterService;
use Fotovoltaico\Services\AuditService;

class Distributors extends Security_Controller
{
    private $Distributors_model;
    private $AuditService;
    private $AneelImporterService;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canViewDistributors($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();

        $this->Distributors_model = model('Fotovoltaico\\Models\\Distributors_model');
        $this->AuditService = new AuditService();
        $this->AneelImporterService = new AneelImporterService();
    }

    public function index()
    {
        $view_data = array(
            'can_manage_distributors' => Plugin::canManageTariffs($this->login_user)
        );

        return $this->template->rander('Fotovoltaico\\Views\\distributors\\index', $view_data);
    }

    public function list_data()
    {
        $search = trim((string) $this->request->getPost('search'));
        $status = $this->request->getPost('status');
        $source = trim((string) $this->request->getPost('source'));
        $show_only = $this->request->getPost('show_in_registration');

        $options = array(
            'search' => $search
        );
        if ($status !== '' && $status !== null) {
            $options['active_only'] = (int) $status;
        }
        if ($source !== '') {
            $options['source'] = $source;
        }
        if ($show_only !== '' && $show_only !== null) {
            $options['show_in_registration'] = (int) $show_only;
        }

        $query = $this->Distributors_model->get_details($options);
        $list_data = $query ? $query->getResult() : array();
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
            'model_info' => $this->Distributors_model->get_one((int) $this->request->getPost('id'))
        );

        return $this->template->view('Fotovoltaico\\Views\\distributors\\modal_form', $view_data);
    }

    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'title' => 'required'
        ));

        if (!Plugin::canManageTariffs($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $data = array(
            'title' => trim((string) $this->request->getPost('title')),
            'legal_name' => trim((string) $this->request->getPost('legal_name')) ?: null,
            'document' => preg_replace('/\D+/', '', (string) $this->request->getPost('document')) ?: null,
            'aneel_code' => trim((string) $this->request->getPost('aneel_code')) ?: null,
            'acronym' => trim((string) $this->request->getPost('acronym')) ?: null,
            'state_code' => strtoupper(trim((string) $this->request->getPost('state_code'))) ?: null,
            'agent_type' => trim((string) $this->request->getPost('agent_type')) ?: 'desconhecido',
            'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            'sync_notes' => trim((string) $this->request->getPost('sync_notes')) ?: null,
            'active' => $this->request->getPost('active') ? 1 : 0,
            'show_in_registration' => $this->request->getPost('show_in_registration') ? 1 : 0,
            'source' => $id ? ($this->request->getPost('source') ?: 'manual') : 'manual',
            'updated_at' => get_my_local_time(),
        );
        $data = clean_data($data);
        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->Distributors_model->ci_save($data, $id);
        if ($save_id) {
            $this->_audit('distributor', $save_id, $id ? 'distributor_updated' : 'distributor_created', array(), array(
                'title' => $data['title'],
                'state_code' => $data['state_code'],
                'active' => $data['active'],
                'show_in_registration' => $data['show_in_registration'],
            ));
            echo json_encode(array(
                'success' => true,
                'id' => $save_id,
                'data' => $this->_make_row($this->Distributors_model->get_details(array('id' => $save_id))->getRow()),
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
        if ($this->Distributors_model->delete($id)) {
            $this->_audit('distributor', $id, 'distributor_deleted');
            echo json_encode(array('success' => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    public function sync_from_api()
    {
        if (!Plugin::canManageTariffs($this->login_user)) {
            app_redirect('forbidden');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        $result = $this->AneelImporterService->importOfficial(array(
            'batch_size' => 250,
            'created_by' => $this->login_user->id,
            'distributors_only' => true,
        ));

        if (get_array_value($result, 'success')) {
            $this->_audit('distributor', 0, 'distributors_synced_from_api', array(), array(
                'processed' => (int) get_array_value(get_array_value($result, 'data'), 'rows_read'),
                'created' => (int) get_array_value(get_array_value($result, 'data'), 'created_distributors'),
                'updated' => (int) get_array_value(get_array_value($result, 'data'), 'updated_distributors'),
            ));
        }

        echo json_encode(array(
            'success' => (bool) get_array_value($result, 'success'),
            'message' => get_array_value($result, 'message') ?: (get_array_value($result, 'success') ? app_lang('record_saved') : app_lang('error_occurred')),
            'data' => get_array_value($result, 'data') ?: array(),
            'errors' => get_array_value($result, 'errors') ?: array(),
        ));
    }

    public function reprocess_eligibility()
    {
        if (!Plugin::canManageTariffs($this->login_user)) {
            app_redirect('forbidden');
        }

        $this->AneelImporterService->syncDisplayFlags();
        $this->_audit('distributor', 0, 'distributors_reprocessed_eligibility');

        echo json_encode(array(
            'success' => true,
            'message' => app_lang('fotovoltaico_eligibility_reprocessed'),
        ));
    }

    private function _make_row($data)
    {
        $source = trim((string) ($data->source ?? 'manual'));
        $source_label = $source === 'aneel' || $source === 'external'
            ? app_lang('fotovoltaico_source_aneel')
            : app_lang('fotovoltaico_source_manual');

        return array(
            esc($data->title),
            esc($data->acronym ?: '-'),
            esc($data->state_code ?: '-'),
            esc($data->document ?: '-'),
            esc($data->agent_type ?: '-'),
            "<span class='badge " . ($source === 'aneel' || $source === 'external' ? "bg-info" : "bg-secondary") . "'>" . esc($source_label) . "</span>",
            ($data->current_tariff_count ?? 0) > 0
                ? "<span class='badge bg-success'>" . esc(app_lang('fotovoltaico_has_current_tariff')) . "</span>"
                : "<span class='badge bg-warning text-dark'>" . esc(app_lang('fotovoltaico_no_current_tariff')) . "</span>",
            $data->show_in_registration ? "<span class='badge bg-success'>" . esc(app_lang('yes')) . "</span>" : "<span class='badge bg-secondary'>" . esc(app_lang('no')) . "</span>",
            $data->active ? "<span class='badge bg-success'>" . esc(app_lang('active')) . "</span>" : "<span class='badge bg-secondary'>" . esc(app_lang('inactive')) . "</span>",
            Plugin::canManageTariffs($this->login_user)
                ? modal_anchor(get_uri('fotovoltaico/distributors/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array('class' => 'edit', 'title' => app_lang('fotovoltaico_edit_distributor'), 'data-post-id' => $data->id))
                    . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete'), 'class' => 'delete', 'data-id' => $data->id, 'data-action-url' => get_uri('fotovoltaico/distributors/delete'), 'data-action' => 'delete'))
                : '-'
        );
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
