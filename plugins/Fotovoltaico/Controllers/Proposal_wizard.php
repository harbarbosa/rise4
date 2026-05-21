<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\AuditService;
use Fotovoltaico\Services\FinanceCalcService;

class Proposal_wizard extends Security_Controller
{
    public $Proposals_model;
    public $Proposal_versions_model;
    public $Proposal_snapshots_model;
    public $Clients_model;
    public $Users_model;
    public $Distributors_model;
    public $Tariffs_model;
    public $Kits_model;
    private $FinanceCalcService;
    private $ProposalSnapshotService;
    private $AuditService;

    private $steps = array('client', 'consumption', 'tariff', 'kit', 'insolation', 'law', 'finance', 'review');

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canCreateProposals($this->login_user) && !Plugin::canManageProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();

        $this->Proposals_model = model('Fotovoltaico\\Models\\Proposals_model');
        $this->Proposal_versions_model = model('Fotovoltaico\\Models\\Proposal_versions_model');
        $this->Proposal_snapshots_model = model('Fotovoltaico\\Models\\Proposal_snapshots_model');
        $this->Clients_model = model('App\\Models\\Clients_model');
        $this->Users_model = model('App\\Models\\Users_model');
        $this->Distributors_model = model('Fotovoltaico\\Models\\Distributors_model');
        $this->Tariffs_model = model('Fotovoltaico\\Models\\Tariffs_model');
        $this->Kits_model = model('Fotovoltaico\\Models\\Kits_model');
        $this->FinanceCalcService = new FinanceCalcService();
        $this->ProposalSnapshotService = new \Fotovoltaico\Services\ProposalSnapshotService();
        $this->AuditService = new AuditService();
    }

    public function start()
    {
        $draft = $this->Proposals_model->get_last_wizard_draft_by_user($this->login_user->id);
        if ($draft && $draft->id) {
            app_redirect('fotovoltaico/proposal_wizard/step/' . $draft->id . '/' . ($draft->wizard_step ?: 'client'));
            return;
        }

        $proposal_id = $this->_create_wizard_proposal();
        if ($proposal_id) {
            app_redirect('fotovoltaico/proposal_wizard/step/' . $proposal_id . '/client');
            return;
        }

        app_redirect('fotovoltaico/proposals');
    }

    public function step($proposal_id = 0, $step = 'client')
    {
        $proposal_id = (int) $proposal_id;
        $step = $this->_normalize_step($step);
        if (!$proposal_id) {
            show_404();
        }

        $proposal = $this->Proposals_model->get_one_with_details($proposal_id);
        if (!$proposal) {
            show_404();
        }

        if (!$this->_can_edit_proposal($proposal)) {
            app_redirect('forbidden');
        }

        $wizard_data = $this->_decode_json_to_array($proposal->wizard_data_json ?? '');
        $progress = $this->_step_progress($step);
        $current_step_index = array_search($step, $this->steps, true);

        $view_data = array(
            'proposal' => $proposal,
            'proposal_id' => $proposal_id,
            'step' => $step,
            'step_index' => $current_step_index,
            'steps' => $this->steps,
            'step_labels' => $this->_get_step_labels(),
            'progress' => $progress,
            'wizard_data' => $wizard_data,
            'summary' => $this->_build_summary($proposal, $wizard_data),
            'previous_step' => $this->_previous_step($step),
            'next_step' => $this->_next_step($step),
            'step_title' => $this->_step_title($step),
            'client_options' => $this->_get_clients_dropdown(),
            'lead_options' => $this->_get_leads_dropdown(),
            'contact_options' => $this->_get_contacts_dropdown(),
            'distributor_options' => $this->_get_distributors_dropdown(),
            'tariff_options' => $this->_get_tariffs_dropdown(get_array_value($wizard_data, 'distributor_id')),
            'kit_options' => $this->_get_kits_dropdown(),
            'current_tariff' => $this->_get_current_tariff_summary($wizard_data),
            'step_view_data' => array(
                'proposal' => $proposal,
                'proposal_id' => $proposal_id,
                'step' => $step,
                'step_index' => $current_step_index,
                'steps' => $this->steps,
                'step_labels' => $this->_get_step_labels(),
                'progress' => $progress,
                'wizard_data' => $wizard_data,
                'summary' => $this->_build_summary($proposal, $wizard_data),
                'previous_step' => $this->_previous_step($step),
                'next_step' => $this->_next_step($step),
                'step_title' => $this->_step_title($step),
                'client_options' => $this->_get_clients_dropdown(),
                'lead_options' => $this->_get_leads_dropdown(),
                'contact_options' => $this->_get_contacts_dropdown(),
                'distributor_options' => $this->_get_distributors_dropdown(),
                'tariff_options' => $this->_get_tariffs_dropdown(get_array_value($wizard_data, 'distributor_id')),
                'kit_options' => $this->_get_kits_dropdown(),
                'current_tariff' => $this->_get_current_tariff_summary($wizard_data),
            ),
        );

        return $this->template->rander('Fotovoltaico\\Views\\proposal_wizard\\wizard', $view_data);
    }

    public function save_step()
    {
        $proposal_id = (int) $this->request->getPost('proposal_id');
        $step = $this->_normalize_step((string) $this->request->getPost('step'));
        if (!$proposal_id || !$step) {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $proposal = $this->Proposals_model->get_one_with_details($proposal_id);
        if (!$proposal) {
            echo json_encode(array('success' => false, 'message' => app_lang('record_not_found')));
            return;
        }

        if (!$this->_can_edit_proposal($proposal)) {
            app_redirect('forbidden');
        }

        $wizard_data = $this->_decode_json_to_array($proposal->wizard_data_json ?? '');
        $step_data = $this->_extract_step_data($step, $proposal, $wizard_data);
        if ($step === 'client' && !$this->_has_crm_binding_from_data($step_data)) {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $wizard_data = array_merge($wizard_data, $step_data);
        $wizard_data['wizard_step'] = $this->_next_step($step);

        $status = trim((string) $proposal->status);
        if ($status === '') {
            $status = 'draft';
        }

        $proposal_data = array(
            'client_id' => get_only_numeric_value(get_array_value($step_data, 'client_id')) ?: (int) ($proposal->client_id ?: 0),
            'lead_id' => get_only_numeric_value(get_array_value($step_data, 'lead_id')) ?: (int) ($proposal->lead_id ?: 0),
            'contact_id' => get_only_numeric_value(get_array_value($step_data, 'contact_id')) ?: (int) ($proposal->contact_id ?: 0),
            'distributor_id' => get_only_numeric_value(get_array_value($step_data, 'distributor_id')) ?: (int) ($proposal->distributor_id ?: 0),
            'consumer_unit' => trim((string) get_array_value($step_data, 'consumer_unit')) ?: $proposal->consumer_unit,
            'consumption_avg' => $this->_numeric_or_existing(get_array_value($step_data, 'consumption_avg'), $proposal->consumption_avg),
            'subtotal' => $this->_numeric_or_existing(get_array_value($step_data, 'subtotal'), $proposal->subtotal),
            'discount_total' => $this->_numeric_or_existing(get_array_value($step_data, 'discount_total'), $proposal->discount_total),
            'tax_total' => $this->_numeric_or_existing(get_array_value($step_data, 'tax_total'), $proposal->tax_total),
            'total' => $this->_numeric_or_existing(get_array_value($step_data, 'total'), $proposal->total),
            'notes' => trim((string) get_array_value($step_data, 'notes')) ?: $proposal->notes,
            'metadata_json' => $this->_encode_step_metadata($wizard_data),
            'wizard_step' => $wizard_data['wizard_step'],
            'wizard_data_json' => json_encode($wizard_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => $status,
            'current_version' => ((int) ($proposal->current_version ?? 0)) + 1,
            'updated_at' => get_my_local_time(),
        );
        $proposal_context = clone $proposal;
        foreach (array('client_id', 'lead_id', 'contact_id', 'distributor_id', 'consumer_unit', 'consumption_avg', 'subtotal', 'discount_total', 'tax_total', 'total', 'notes') as $field) {
            if (array_key_exists($field, $proposal_data)) {
                $proposal_context->{$field} = $proposal_data[$field];
            }
        }
        $proposal_data['title'] = $this->_build_title($proposal_context, $wizard_data);

        if (!$proposal->title) {
            $proposal_data['created_by'] = $this->login_user->id;
            $proposal_data['created_at'] = get_my_local_time();
            $proposal_data['proposal_code'] = $proposal->proposal_code ?: $this->_generate_proposal_code($proposal_id);
        }

        $db = db_connect();
        $db->transStart();

        $proposal_data = clean_data($proposal_data);
        $save_id = $this->Proposals_model->ci_save($proposal_data, $proposal_id);
        if (!$save_id) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $saved_proposal = $this->Proposals_model->get_one_with_details($save_id);
        $version_id = $this->_create_version_snapshot($saved_proposal, $step, $step_data);
        if (!$version_id) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $db->transComplete();

        $next_step = $this->_next_step($step);
        $response = array(
            'success' => true,
            'id' => $save_id,
            'version_id' => $version_id,
            'next_step' => $next_step,
            'redirect_url' => $next_step ? get_uri('fotovoltaico/proposal_wizard/step/' . $save_id . '/' . $next_step) : get_uri('fotovoltaico/proposal_wizard/step/' . $save_id . '/review'),
            'message' => app_lang('record_saved')
        );

        if (!$this->request->isAJAX()) {
            app_redirect($response['redirect_url']);
            return;
        }

        echo json_encode($response);
    }

    public function finish()
    {
        $proposal_id = (int) $this->request->getPost('proposal_id');
        if (!$proposal_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $proposal = $this->Proposals_model->get_one_with_details($proposal_id);
        if (!$proposal) {
            echo json_encode(array('success' => false, 'message' => app_lang('record_not_found')));
            return;
        }

        if (!$this->_can_edit_proposal($proposal)) {
            app_redirect('forbidden');
        }

        if (!$this->_has_crm_binding_from_proposal($proposal)) {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $wizard_data = $this->_decode_json_to_array($proposal->wizard_data_json ?? '');
        $wizard_data['wizard_step'] = 'review';

        $proposal_data = array(
            'status' => 'in_progress',
            'title' => $this->_build_title($proposal, $wizard_data),
            'wizard_step' => 'review',
            'wizard_data_json' => json_encode($wizard_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'metadata_json' => $this->_encode_step_metadata($wizard_data),
            'current_version' => ((int) ($proposal->current_version ?? 0)) + 1,
            'updated_at' => get_my_local_time(),
        );

        $db = db_connect();
        $db->transStart();

        $proposal_data = clean_data($proposal_data);
        $save_id = $this->Proposals_model->ci_save($proposal_data, $proposal_id);
        if (!$save_id) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $saved_proposal = $this->Proposals_model->get_one_with_details($save_id);
        $version_id = $this->_create_version_snapshot($saved_proposal, 'review', array('finalized' => true));
        if (!$version_id) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $db->transComplete();
        $this->_audit('proposal', $save_id, 'proposal_wizard_finished', array(
            'status' => $proposal_data['status'],
        ), array(
            'version_id' => $version_id,
            'step' => 'review',
        ));

        $response = array(
            'success' => true,
            'id' => $save_id,
            'redirect_url' => get_uri('fotovoltaico/proposals/view/' . $save_id),
            'message' => app_lang('record_saved')
        );

        if (!$this->request->isAJAX()) {
            app_redirect($response['redirect_url']);
            return;
        }

        echo json_encode($response);
    }

    private function _create_wizard_proposal()
    {
        Plugin::ensureSchema();

        $db = db_connect();
        $proposal_table = $db->prefixTable('fv_proposals');
        if (!$db->tableExists($proposal_table)) {
            Plugin::runMigrations();
        }
        if (!$db->tableExists($proposal_table)) {
            log_message('error', '[Fotovoltaico] Proposal wizard cannot start because fv_proposals is missing.');
            return 0;
        }

        $proposal_code = $this->_generate_proposal_code();
        $data = array(
            'proposal_code' => $proposal_code,
            'title' => $proposal_code,
            'status' => 'draft',
            'current_version' => 1,
            'wizard_step' => 'client',
            'wizard_data_json' => json_encode(array('wizard_step' => 'client'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_by' => $this->login_user->id,
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
        );

        $proposal_id = $this->Proposals_model->ci_save($data);
        if (!$proposal_id) {
            log_message('error', '[Fotovoltaico] Proposal wizard could not create draft proposal.');
            return 0;
        }

        $this->_audit('proposal', $proposal_id, 'proposal_wizard_started', array(), array(
            'status' => 'draft',
            'wizard_step' => 'client',
        ));

        return $proposal_id;
    }

    private function _extract_step_data($step, $proposal = null, $wizard_data = array())
    {
        $data = array();

        if ($step === 'client') {
            $data = array(
                'client_id' => get_only_numeric_value($this->request->getPost('client_id')) ?: null,
                'lead_id' => get_only_numeric_value($this->request->getPost('lead_id')) ?: null,
                'contact_id' => get_only_numeric_value($this->request->getPost('contact_id')) ?: null,
                'consumer_unit' => trim((string) $this->request->getPost('consumer_unit')) ?: null,
                'title' => trim((string) $this->request->getPost('title')) ?: null,
                'crm_note' => trim((string) $this->request->getPost('crm_note')) ?: null,
            );
        } else if ($step === 'consumption') {
            $data = array(
                'consumption_avg' => (float) unformat_currency($this->request->getPost('consumption_avg')),
                'monthly_bill_value' => (float) unformat_currency($this->request->getPost('monthly_bill_value')),
                'consumption_profile' => trim((string) $this->request->getPost('consumption_profile')) ?: null,
                'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            );
        } else if ($step === 'tariff') {
            $tariff_id = get_only_numeric_value($this->request->getPost('tariff_id'));
            $tariff = $tariff_id ? $this->Tariffs_model->get_details(array('id' => (int) $tariff_id))->getRow() : null;
            $data = array(
                'distributor_id' => get_only_numeric_value($this->request->getPost('distributor_id')) ?: null,
                'tariff_id' => $tariff_id ?: null,
                'tariff_label' => $tariff ? ($tariff->distributor_title . ' - ' . $tariff->modality . ' / ' . $tariff->subgroup) : null,
                'tariff_te' => $tariff ? (float) $tariff->te : 0,
                'tariff_tusd' => $tariff ? (float) $tariff->tusd : 0,
                'tariff_flag' => $tariff ? $tariff->flag_name : null,
                'tariff_flag_value' => $tariff ? (float) $tariff->flag_value : 0,
                'selected_tariff_snapshot' => $tariff ? array(
                    'id' => (int) $tariff->id,
                    'distributor_id' => (int) $tariff->distributor_id,
                    'modality' => $tariff->modality,
                    'subgroup' => $tariff->subgroup,
                    'te' => (float) $tariff->te,
                    'tusd' => (float) $tariff->tusd,
                    'flag_name' => $tariff->flag_name,
                    'flag_value' => (float) $tariff->flag_value,
                    'valid_from' => $tariff->valid_from,
                    'valid_to' => $tariff->valid_to,
                ) : null,
            );
        } else if ($step === 'kit') {
            $kit_id = get_only_numeric_value($this->request->getPost('kit_id'));
            $kit = $kit_id ? $this->Kits_model->get_one($kit_id) : null;
            $data = array(
                'kit_id' => $kit_id ?: null,
                'kit_label' => $kit ? $kit->title : trim((string) $this->request->getPost('kit_label')),
                'kit_notes' => trim((string) $this->request->getPost('kit_notes')) ?: null,
            );
        } else if ($step === 'insolation') {
            $data = array(
                'insolation_city' => trim((string) $this->request->getPost('insolation_city')) ?: null,
                'insolation_state' => trim((string) $this->request->getPost('insolation_state')) ?: null,
                'latitude' => trim((string) $this->request->getPost('latitude')) ?: null,
                'longitude' => trim((string) $this->request->getPost('longitude')) ?: null,
                'annual_insolation' => trim((string) $this->request->getPost('annual_insolation')) ?: null,
                'insolation_source' => trim((string) $this->request->getPost('insolation_source')) ?: null,
            );
        } else if ($step === 'law') {
            $data = array(
                'law_14300_mode' => trim((string) $this->request->getPost('law_14300_mode')) ?: null,
                'law_14300_category' => trim((string) $this->request->getPost('law_14300_category')) ?: null,
                'law_14300_percentage' => (float) unformat_currency($this->request->getPost('law_14300_percentage')),
                'law_14300_notes' => trim((string) $this->request->getPost('law_14300_notes')) ?: null,
            );
        } else if ($step === 'finance') {
            $data = array(
                'entry_value' => (float) unformat_currency($this->request->getPost('entry_value')),
                'entry_percent' => (float) unformat_currency($this->request->getPost('entry_percent')),
                'installments' => (int) get_only_numeric_value($this->request->getPost('installments')),
                'financing_rate' => (float) unformat_currency($this->request->getPost('financing_rate')),
                'monthly_value' => (float) unformat_currency($this->request->getPost('monthly_value')),
                'investment_initial' => (float) unformat_currency($this->request->getPost('investment_initial')),
                'economy_annual' => (float) unformat_currency($this->request->getPost('economy_annual')),
                'tariff_escalation' => (float) unformat_currency($this->request->getPost('tariff_escalation')),
                'discount_rate' => (float) unformat_currency($this->request->getPost('discount_rate')),
                'maintenance_cost_annual' => (float) unformat_currency($this->request->getPost('maintenance_cost_annual')),
                'maintenance_escalation' => (float) unformat_currency($this->request->getPost('maintenance_escalation')),
                'horizon' => (int) get_only_numeric_value($this->request->getPost('horizon')),
                'replacement_schedule_json' => trim((string) $this->request->getPost('replacement_schedule_json')) ?: null,
                'finance_notes' => trim((string) $this->request->getPost('finance_notes')) ?: null,
            );
        } else if ($step === 'review') {
            $data = array(
                'review_notes' => trim((string) $this->request->getPost('review_notes')) ?: null,
            );
        }

        if ($step === 'finance') {
            $data['finance_calc'] = $this->_build_finance_calc_data($proposal, $data, $wizard_data);
        }

        return clean_data($data);
    }

    private function _create_version_snapshot($proposal, $step, $extra = array())
    {
        $snapshot_result = $this->ProposalSnapshotService->build_snapshot($proposal, (int) ($proposal->current_version ?: 1), array(
            'step' => $step,
            'extra' => $extra,
        ));
        if (!get_array_value($snapshot_result, 'success')) {
            return false;
        }

        $snapshot_json = get_array_value($snapshot_result, 'snapshot_json');

        $version_data = array(
            'proposal_id' => (int) $proposal->id,
            'version_number' => (int) ($proposal->current_version ?: 1),
            'status' => $proposal->status ?: 'draft',
            'subtotal' => (float) ($proposal->subtotal ?? 0),
            'discount_total' => (float) ($proposal->discount_total ?? 0),
            'tax_total' => (float) ($proposal->tax_total ?? 0),
            'total' => (float) ($proposal->total ?? 0),
            'result_json' => json_encode(array(
                'summary' => $this->_build_summary($proposal, $this->_decode_json_to_array($proposal->wizard_data_json ?? '')),
                'step' => $step,
                'snapshot_hash' => get_array_value($snapshot_result, 'snapshot_hash'),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'payload_json' => $snapshot_json,
            'created_by' => $this->login_user->id,
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
        );
        $version_id = $this->Proposal_versions_model->ci_save($version_data);

        if (!$version_id) {
            return false;
        }

        return $this->ProposalSnapshotService->store_snapshot_json($proposal, $version_id, $snapshot_json, $this->login_user->id);
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

    private function _build_proposal_payload($proposal)
    {
        return array(
            'id' => (int) $proposal->id,
            'proposal_code' => $proposal->proposal_code ?: '',
            'client_id' => (int) ($proposal->client_id ?: 0),
            'lead_id' => (int) ($proposal->lead_id ?: 0),
            'contact_id' => (int) ($proposal->contact_id ?: 0),
            'distributor_id' => (int) ($proposal->distributor_id ?: 0),
            'consumer_unit' => $proposal->consumer_unit ?: '',
            'consumption_avg' => (float) ($proposal->consumption_avg ?? 0),
            'title' => $proposal->title ?: '',
            'status' => $proposal->status ?: 'draft',
            'subtotal' => (float) ($proposal->subtotal ?? 0),
            'discount_total' => (float) ($proposal->discount_total ?? 0),
            'tax_total' => (float) ($proposal->tax_total ?? 0),
            'total' => (float) ($proposal->total ?? 0),
            'notes' => $proposal->notes ?: '',
            'current_version' => (int) ($proposal->current_version ?? 0),
        );
    }

    private function _build_summary($proposal, $wizard_data)
    {
        return array(
            'title' => $proposal->title ?: '',
            'proposal_code' => $proposal->proposal_code ?: '',
            'crm_reference' => $this->_crm_reference_label($proposal),
            'consumer_unit' => $proposal->consumer_unit ?: '',
            'consumption_avg' => (float) ($proposal->consumption_avg ?? 0),
            'distributor' => $proposal->distributor_title ?: '',
            'tariff' => get_array_value($wizard_data, 'tariff_label') ?: '',
            'kit' => get_array_value($wizard_data, 'kit_label') ?: '',
            'annual_insolation' => get_array_value($wizard_data, 'annual_insolation') ?: '',
            'law_14300_mode' => get_array_value($wizard_data, 'law_14300_mode') ?: '',
            'entry_value' => get_array_value($wizard_data, 'entry_value') ?: '',
            'installments' => get_array_value($wizard_data, 'installments') ?: '',
            'current_version' => (int) ($proposal->current_version ?? 0),
            'status' => $proposal->status ?: 'draft',
            'total' => (float) ($proposal->total ?? 0),
        );
    }

    private function _build_finance_calc_data($proposal, $step_data, $wizard_data)
    {
        $kit_id = get_only_numeric_value(get_array_value($wizard_data, 'kit_id'));
        $kit_power_kwp = 0;
        if ($kit_id) {
            $kit = $this->Kits_model->get_one($kit_id);
            $kit_power_kwp = $kit ? (float) ($kit->power_kwp ?? 0) : 0;
        }

        $annual_insolation = $this->_numeric_or_existing(get_array_value($wizard_data, 'annual_insolation'), 0);
        $pr = 0.75;
        $losses = 0.14;
        $monthly_generation = $kit_power_kwp > 0 ? $kit_power_kwp * ($annual_insolation / 12) * $pr * (1 - $losses) : 0;
        $annual_generation = $kit_power_kwp > 0 ? $kit_power_kwp * $annual_insolation * $pr * (1 - $losses) : 0;

        $tariff_id = get_only_numeric_value(get_array_value($wizard_data, 'tariff_id'));
        $tariff = $tariff_id ? $this->Tariffs_model->get_details(array('id' => (int) $tariff_id))->getRow() : null;
        $tariff_value = $tariff ? ((float) $tariff->te + (float) $tariff->tusd + (float) $tariff->flag_value) : 0;

        $economy_annual = $annual_generation * $tariff_value;
        if ($economy_annual <= 0) {
            $economy_annual = (float) get_array_value($wizard_data, 'monthly_bill_value') * 12;
        }

        $result = $this->FinanceCalcService->preview(array(
            'investment_initial' => (float) ($proposal->total ?? 0),
            'economy_annual' => $economy_annual,
            'tariff_escalation' => get_array_value($wizard_data, 'tariff_escalation') ?: 0,
            'discount_rate' => get_array_value($wizard_data, 'discount_rate') ?: 0,
            'maintenance_cost_annual' => get_array_value($wizard_data, 'maintenance_cost_annual') ?: 0,
            'maintenance_escalation' => get_array_value($wizard_data, 'maintenance_escalation') ?: 0,
            'horizon' => get_array_value($wizard_data, 'horizon') ?: 25,
            'replacement_schedule_json' => get_array_value($wizard_data, 'replacement_schedule_json') ?: '',
        ));

        $result['inputs']['kit_power_kwp'] = $kit_power_kwp;
        $result['inputs']['annual_insolation'] = $annual_insolation;
        $result['inputs']['monthly_generation_estimate'] = $monthly_generation;
        $result['inputs']['annual_generation_estimate'] = $annual_generation;
        $result['inputs']['tariff_estimate'] = $tariff_value;

        return $result;
    }

    private function _get_clients_dropdown()
    {
        $result = array('' => '-');
        foreach ($this->Clients_model->get_details()->getResult() as $client) {
            if ((int) $client->is_lead === 1) {
                continue;
            }
            $result[$client->id] = $client->company_name;
        }
        return $result;
    }

    private function _get_leads_dropdown()
    {
        $result = array('' => '-');
        foreach ($this->Clients_model->get_details(array('leads_only' => 1))->getResult() as $lead) {
            $result[$lead->id] = $lead->company_name;
        }
        return $result;
    }

    private function _get_contacts_dropdown()
    {
        $result = array('' => '-');
        $contacts = array_merge(
            $this->Users_model->get_details(array('user_type' => 'client', 'status' => 'active'))->getResult(),
            $this->Users_model->get_details(array('user_type' => 'lead', 'status' => 'active'))->getResult()
        );
        foreach ($contacts as $contact) {
            $company_name = $contact->company_name ?: '';
            $name = trim((string) $contact->first_name . ' ' . (string) $contact->last_name);
            $result[$contact->id] = trim($name . ($company_name ? ' - ' . $company_name : ''), ' -');
        }
        return $result;
    }

    private function _get_distributors_dropdown()
    {
        $result = array('' => '-');
        foreach ($this->Distributors_model->get_details(array('active_only' => 1))->getResult() as $distributor) {
            $result[$distributor->id] = $distributor->title;
        }
        return $result;
    }

    private function _get_tariffs_dropdown($distributor_id = 0)
    {
        $result = array('' => '-');
        $options = array('vigency_status' => 'current');
        if ($distributor_id) {
            $options['distributor_id'] = (int) $distributor_id;
        }

        foreach ($this->Tariffs_model->get_details($options)->getResult() as $tariff) {
            $result[$tariff->id] = $tariff->distributor_title . ' - ' . $tariff->modality . ' / ' . $tariff->subgroup;
        }
        return $result;
    }

    private function _get_kits_dropdown()
    {
        $result = array('' => '-');
        foreach ($this->Kits_model->get_details(array('active_only' => 1))->getResult() as $kit) {
            $result[$kit->id] = $kit->title;
        }
        return $result;
    }

    private function _get_current_tariff_summary($wizard_data)
    {
        $tariff_id = get_array_value($wizard_data, 'tariff_id');
        if (!$tariff_id) {
            return null;
        }

        $tariff = $this->Tariffs_model->get_details(array('id' => (int) $tariff_id))->getRow();
        if (!$tariff || !$tariff->id) {
            return null;
        }

        return $tariff;
    }

    private function _normalize_step($step)
    {
        $step = trim((string) $step);
        if (!in_array($step, $this->steps, true)) {
            return 'client';
        }

        return $step;
    }

    private function _step_title($step)
    {
        $map = array(
            'client' => app_lang('fotovoltaico_wizard_step_client'),
            'consumption' => app_lang('fotovoltaico_wizard_step_consumption'),
            'tariff' => app_lang('fotovoltaico_wizard_step_tariff'),
            'kit' => app_lang('fotovoltaico_wizard_step_kit'),
            'insolation' => app_lang('fotovoltaico_wizard_step_insolation'),
            'law' => app_lang('fotovoltaico_wizard_step_law'),
            'finance' => app_lang('fotovoltaico_wizard_step_finance'),
            'review' => app_lang('fotovoltaico_wizard_step_review'),
        );

        return get_array_value($map, $step) ?: $step;
    }

    private function _previous_step($step)
    {
        $index = array_search($step, $this->steps, true);
        if ($index === false || $index <= 0) {
            return '';
        }

        return $this->steps[$index - 1];
    }

    private function _next_step($step)
    {
        $index = array_search($step, $this->steps, true);
        if ($index === false || $index >= count($this->steps) - 1) {
            return '';
        }

        return $this->steps[$index + 1];
    }

    private function _step_progress($step)
    {
        $index = array_search($step, $this->steps, true);
        if ($index === false) {
            return 0;
        }

        return (int) round((($index + 1) / count($this->steps)) * 100);
    }

    private function _get_step_labels()
    {
        return array(
            'client' => app_lang('fotovoltaico_wizard_step_client'),
            'consumption' => app_lang('fotovoltaico_wizard_step_consumption'),
            'tariff' => app_lang('fotovoltaico_wizard_step_tariff'),
            'kit' => app_lang('fotovoltaico_wizard_step_kit'),
            'insolation' => app_lang('fotovoltaico_wizard_step_insolation'),
            'law' => app_lang('fotovoltaico_wizard_step_law'),
            'finance' => app_lang('fotovoltaico_wizard_step_finance'),
            'review' => app_lang('fotovoltaico_wizard_step_review'),
        );
    }

    private function _can_edit_proposal($proposal)
    {
        if (!$proposal || !$proposal->id) {
            return false;
        }

        if (Plugin::canManageProposals($this->login_user)) {
            return true;
        }

        return (int) $proposal->created_by === (int) $this->login_user->id && in_array($proposal->status, array('draft', 'in_progress'), true);
    }

    private function _has_crm_binding_from_data($data)
    {
        return (int) get_array_value($data, 'client_id') || (int) get_array_value($data, 'lead_id') || (int) get_array_value($data, 'contact_id');
    }

    private function _has_crm_binding_from_proposal($proposal)
    {
        if (!$proposal) {
            return false;
        }

        return (int) ($proposal->client_id ?? 0) || (int) ($proposal->lead_id ?? 0) || (int) ($proposal->contact_id ?? 0);
    }

    private function _build_title($proposal, $wizard_data)
    {
        $title = trim((string) get_array_value($wizard_data, 'title'));
        if ($title !== '') {
            return $title;
        }

        $crm_reference = $this->_crm_reference_label($proposal);
        if ($crm_reference !== '-') {
            return app_lang('fotovoltaico_proposals') . ' - ' . $crm_reference;
        }

        return $proposal->title ?: $proposal->proposal_code;
    }

    private function _crm_reference_label($proposal)
    {
        if (!$proposal) {
            return '-';
        }

        if ((int) ($proposal->contact_id ?? 0)) {
            $contact_name = trim((string) ($proposal->contact_first_name ?? '') . ' ' . (string) ($proposal->contact_last_name ?? ''));
            $client_name = $proposal->contact_client_company_name ?: $proposal->client_company_name;
            return trim($contact_name . ' - ' . $client_name, ' -');
        }

        if ((int) ($proposal->lead_id ?? 0)) {
            return $proposal->lead_company_name ?: '-';
        }

        return $proposal->client_company_name ?: '-';
    }

    private function _numeric_or_existing($value, $fallback)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        return (float) unformat_currency($value);
    }

    private function _encode_step_metadata($wizard_data)
    {
        return json_encode($wizard_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function _decode_json_to_array($json_text)
    {
        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return array();
        }

        $decoded = json_decode($json_text, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : array();
    }

    private function _generate_proposal_code($id = 0)
    {
        $suffix = $id ? '-' . (int) $id : '';
        return 'FVP-' . date('YmdHis') . $suffix . '-' . strtoupper(substr(make_random_string(), 0, 4));
    }
}
