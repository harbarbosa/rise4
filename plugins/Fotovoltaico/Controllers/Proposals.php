<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\AuditService;
use Fotovoltaico\Services\ProposalPdfService;
use Fotovoltaico\Services\ProposalSnapshotService;

class Proposals extends Security_Controller
{
    public $Proposals_model;
    public $Proposal_versions_model;
    public $Proposal_snapshots_model;
    public $Clients_model;
    public $Users_model;
    public $Distributors_model;
    private $ProposalSnapshotService;
    private $ProposalPdfService;
    private $AuditService;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canViewProposals($this->login_user) && !Plugin::canCreateProposals($this->login_user) && !Plugin::canManageProposals($this->login_user) && !Plugin::canApproveProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();

        $this->Proposals_model = model('Fotovoltaico\\Models\\Proposals_model');
        $this->Proposal_versions_model = model('Fotovoltaico\\Models\\Proposal_versions_model');
        $this->Proposal_snapshots_model = model('Fotovoltaico\\Models\\Proposal_snapshots_model');
        $this->Clients_model = model('App\\Models\\Clients_model');
        $this->Users_model = model('App\\Models\\Users_model');
        $this->Distributors_model = model('Fotovoltaico\\Models\\Distributors_model');
        $this->ProposalSnapshotService = new ProposalSnapshotService();
        $this->ProposalPdfService = new ProposalPdfService();
        $this->AuditService = new AuditService();
    }

    public function index()
    {
        $view_data = array();
        $view_data['status_dropdown'] = $this->_get_status_dropdown(true);
        $view_data['can_create_proposals'] = Plugin::canCreateProposals($this->login_user);
        $view_data['can_manage_proposals'] = Plugin::canManageProposals($this->login_user);
        $view_data['can_approve_proposals'] = Plugin::canApproveProposals($this->login_user);

        return $this->template->rander('Fotovoltaico\\Views\\proposals\\index', $view_data);
    }

    public function list_data()
    {
        if (!Plugin::canViewProposals($this->login_user) && !Plugin::canCreateProposals($this->login_user) && !Plugin::canManageProposals($this->login_user) && !Plugin::canApproveProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $options = array(
            'status' => trim((string) $this->request->getPost('status')),
            'search' => $this->_get_search_term(),
        );

        $rows = array();
        $query = $this->Proposals_model->get_details($options);
        $list_data = $query ? $query->getResult() : array();
        foreach ($list_data as $data) {
            $rows[] = $this->_make_row($data);
        }

        echo json_encode(array('data' => $rows));
    }

    public function view($id = 0)
    {
        $id = (int) $id;
        if (!$id && !Plugin::canCreateProposals($this->login_user) && !Plugin::canManageProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $proposal = $id ? $this->Proposals_model->get_one_with_details($id) : null;
        if ($id && !$proposal) {
            show_404();
        }

        $versions = array();
        $current_version = 0;
        $current_version_id = 0;
        if ($proposal && $proposal->id) {
            $version_query = $this->Proposal_versions_model->get_versions($proposal->id);
            $versions = $version_query ? $version_query->getResult() : array();
            $current_version = (int) ($proposal->current_version ?: count($versions));
            $current_version_id = (int) (($versions[0]->id ?? 0));
        }

        $selected_from = (int) $this->request->getGet('compare_from');
        $selected_to = (int) $this->request->getGet('compare_to');
        if (!$selected_to && $current_version) {
            $selected_to = $current_version;
        }
        if (!$selected_from && $selected_to > 1) {
            $selected_from = $selected_to - 1;
        }

        $comparison = $this->_build_comparison_data($proposal ? $proposal->id : 0, $selected_from, $selected_to);

        $view_data = array(
            'proposal' => $proposal ?: $this->_get_empty_proposal(),
            'proposal_id' => $proposal ? (int) $proposal->id : 0,
            'is_new' => !$proposal,
            'can_create_proposals' => Plugin::canCreateProposals($this->login_user),
            'can_manage_proposals' => Plugin::canManageProposals($this->login_user),
            'can_approve_proposals' => Plugin::canApproveProposals($this->login_user),
            'can_generate_pdf' => Plugin::canGeneratePdf($this->login_user),
            'status_options' => $this->_get_status_dropdown(false),
            'client_options' => $this->_get_clients_dropdown(),
            'lead_options' => $this->_get_leads_dropdown(),
            'contact_options' => $this->_get_contacts_dropdown(),
            'distributor_options' => $this->_get_distributors_dropdown(),
            'versions' => $versions,
            'current_version' => $current_version,
            'current_version_id' => $current_version_id,
            'comparison' => $comparison,
            'selected_compare_from' => $selected_from,
            'selected_compare_to' => $selected_to,
            'summary' => $this->_build_summary($proposal),
        );

        return $this->template->rander('Fotovoltaico\\Views\\proposals\\view', $view_data);
    }

    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'title' => 'required'
        ));

        if (!Plugin::canCreateProposals($this->login_user) && !Plugin::canManageProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        if ($id && !Plugin::canManageProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $binding = $this->_resolve_crm_binding();
        if (!$binding) {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $status = $this->_normalize_status((string) $this->request->getPost('status'));
        if ($status === 'approved' && !Plugin::canApproveProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $proposal_data = array(
            'client_id' => $binding['client_id'],
            'lead_id' => $binding['lead_id'],
            'contact_id' => $binding['contact_id'],
            'distributor_id' => get_only_numeric_value($this->request->getPost('distributor_id')) ?: null,
            'consumer_unit' => trim((string) $this->request->getPost('consumer_unit')) ?: null,
            'consumption_avg' => (float) unformat_currency($this->request->getPost('consumption_avg')),
            'title' => trim((string) $this->request->getPost('title')),
            'status' => $status,
            'currency' => trim((string) $this->request->getPost('currency')) ?: get_setting('default_currency'),
            'subtotal' => (float) unformat_currency($this->request->getPost('subtotal')),
            'discount_total' => (float) unformat_currency($this->request->getPost('discount_total')),
            'tax_total' => (float) unformat_currency($this->request->getPost('tax_total')),
            'total' => (float) unformat_currency($this->request->getPost('total')),
            'issue_date' => trim((string) $this->request->getPost('issue_date')) ?: null,
            'valid_until' => trim((string) $this->request->getPost('valid_until')) ?: null,
            'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            'metadata_json' => $this->_normalize_json_for_save((string) $this->request->getPost('metadata_json')),
            'updated_at' => get_my_local_time(),
        );

        $proposal_data = clean_data($proposal_data);

        $existing = $id ? $this->Proposals_model->get_one_with_details($id) : null;
        if (!$id) {
            $proposal_data['proposal_code'] = $this->_generate_proposal_code();
            $proposal_data['created_by'] = $this->login_user->id;
            $proposal_data['created_at'] = get_my_local_time();
            $proposal_data['current_version'] = 1;
        } else {
            $proposal_data['current_version'] = ((int) ($existing->current_version ?? 0)) + 1;
        }

        $db = db_connect();
        $db->transStart();

        $save_id = $this->Proposals_model->ci_save($proposal_data, $id);
        if (!$save_id) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $saved_proposal = $this->Proposals_model->get_one_with_details($save_id);
        $version_number = (int) ($saved_proposal->current_version ?? 1);
        $snapshot_result = $this->_store_version_snapshot($saved_proposal, $version_number, $status, array('source' => 'save'));
        if (!$snapshot_result['version_id']) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        if (!$snapshot_result['snapshot_id']) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $db->transComplete();

        $this->_audit('proposal', $save_id, $id ? 'proposal_updated' : 'proposal_created', array(
            'status' => $status,
            'version' => $version_number,
            'total' => (float) $proposal_data['total'],
        ));

        if (!$id && (!$saved_proposal->proposal_code || $saved_proposal->proposal_code === '')) {
            $proposal_code_update = array('proposal_code' => $this->_generate_proposal_code($save_id));
            $this->Proposals_model->ci_save($proposal_code_update, $save_id);
        }

        $message = app_lang('record_saved');

        $response = array(
            'success' => true,
            'id' => $save_id,
            'version_id' => $snapshot_result['version_id'],
            'version_number' => $version_number,
            'redirect_url' => get_uri('fotovoltaico/proposals/view/' . $save_id),
            'message' => $message
        );

        if (!$this->request->isAJAX()) {
            app_redirect('fotovoltaico/proposals/view/' . $save_id);
            return;
        }

        echo json_encode($response);
    }

    public function change_status()
    {
        $this->validate_submitted_data(array(
            'id' => 'required|numeric',
            'status' => 'required'
        ));

        $proposal_id = (int) $this->request->getPost('id');
        $proposal = $this->Proposals_model->get_one_with_details($proposal_id);
        if (!$proposal) {
            echo json_encode(array('success' => false, 'message' => app_lang('record_not_found')));
            return;
        }

        if (!Plugin::canManageProposals($this->login_user) && !Plugin::canApproveProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $status = $this->_normalize_status((string) $this->request->getPost('status'));
        if ($status === 'approved' && !Plugin::canApproveProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $proposal_data = array(
            'status' => $status,
            'updated_at' => get_my_local_time(),
            'current_version' => ((int) ($proposal->current_version ?? 0)) + 1,
        );

        $db = db_connect();
        $db->transStart();

        $save_id = $this->Proposals_model->ci_save($proposal_data, $proposal_id);
        if (!$save_id) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $saved_proposal = $this->Proposals_model->get_one_with_details($save_id);
        $version_number = (int) $saved_proposal->current_version;
        $snapshot_result = $this->_store_version_snapshot($saved_proposal, $version_number, $status, array('status_changed' => true));

        if (!$snapshot_result['version_id']) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        if (!$snapshot_result['snapshot_id']) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }
        $db->transComplete();

        $this->_audit('proposal', $save_id, 'proposal_status_changed', array(
            'status' => $proposal->status,
            'version' => (int) ($proposal->current_version ?? 0),
        ), array(
            'status' => $status,
            'version' => $version_number,
        ));

        $response = array(
            'success' => true,
            'id' => $save_id,
            'version_number' => $version_number,
            'status' => $status,
            'message' => app_lang('record_saved')
        );

        if (!$this->request->isAJAX()) {
            app_redirect('fotovoltaico/proposals/view/' . $save_id);
            return;
        }

        echo json_encode($response);
    }

    public function duplicate_version()
    {
        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        $proposal_id = (int) $this->request->getPost('id');
        $proposal = $this->Proposals_model->get_one_with_details($proposal_id);
        if (!$proposal) {
            echo json_encode(array('success' => false, 'message' => app_lang('record_not_found')));
            return;
        }

        if (!Plugin::canCreateProposals($this->login_user) && !Plugin::canManageProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $status = $proposal->status ?: 'draft';
        $proposal_data = array(
            'current_version' => ((int) ($proposal->current_version ?? 0)) + 1,
            'status' => $status,
            'updated_at' => get_my_local_time(),
        );

        $db = db_connect();
        $db->transStart();

        $save_id = $this->Proposals_model->ci_save($proposal_data, $proposal_id);
        if (!$save_id) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $saved_proposal = $this->Proposals_model->get_one_with_details($save_id);
        $version_number = (int) $saved_proposal->current_version;
        $snapshot_result = $this->_store_version_snapshot($saved_proposal, $version_number, $status, array('duplicated' => true));

        if (!$snapshot_result['version_id']) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        if (!$snapshot_result['snapshot_id']) {
            $db->transRollback();
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }
        $db->transComplete();

        $this->_audit('proposal', $save_id, 'proposal_version_duplicated', array(
            'version' => (int) ($proposal->current_version ?? 0),
            'status' => $status,
        ), array(
            'version' => $version_number,
            'status' => $status,
        ));

        $response = array(
            'success' => true,
            'id' => $save_id,
            'version_number' => $version_number,
            'redirect_url' => get_uri('fotovoltaico/proposals/view/' . $save_id),
            'message' => app_lang('record_saved')
        );

        if (!$this->request->isAJAX()) {
            app_redirect('fotovoltaico/proposals/view/' . $save_id);
            return;
        }

        echo json_encode($response);
    }

    public function generate_pdf($proposal_id = 0, $version_id = 0)
    {
        $proposal_id = (int) $proposal_id;
        $version_id = (int) $version_id;
        if (!$proposal_id) {
            show_404();
        }

        if (!Plugin::canGeneratePdf($this->login_user) || !Plugin::canViewProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $proposal = $this->Proposals_model->get_one_with_details($proposal_id);
        if (!$proposal) {
            show_404();
        }

        $result = $this->ProposalPdfService->generate($proposal_id, $version_id ?: (int) ($proposal->current_version ?: 0));
        if (!get_array_value($result, 'success')) {
            show_error(get_array_value($result, 'message') ?: app_lang('error_occurred'));
            return;
        }

        $snapshot_row = get_array_value($result, 'snapshot_row');
        $version_number = (int) get_array_value(get_array_value($result, 'version'), 'number');
        $save_path = get_array_value($result, 'save_path');
        $pdf_content = get_array_value($result, 'pdf_content');
        $file_name = get_array_value($result, 'file_name');
        $mode = trim((string) $this->request->getPost('mode')) ?: 'download';
        $mode = in_array($mode, array('download', 'view'), true) ? $mode : 'download';

        $this->_remember_generated_pdf($proposal, $version_number, $save_path, $snapshot_row);
        $this->_audit('proposal', $proposal_id, 'proposal_pdf_generated', array(
            'version' => $version_number,
            'snapshot_id' => (int) ($snapshot_row->id ?? 0),
        ), array(
            'file_path' => $save_path ? str_replace(FCPATH, '', $save_path) : '',
            'mode' => $mode,
        ));

        if ($mode === 'view') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $file_name . '"');
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
        }

        header('Content-Length: ' . strlen($pdf_content));
        echo $pdf_content;
        exit;
    }

    private function _make_row($data)
    {
        if (!$data) {
            return array();
        }

        $proposal_title = anchor(get_uri('fotovoltaico/proposals/view/' . $data->id), esc($data->title ?: $data->proposal_code ?: ('#' . $data->id)), array(
            'title' => app_lang('fotovoltaico_proposal_details')
        ));

        $crm_label = $this->_crm_reference_label($data);
        $distributor = esc($data->distributor_title ?: '-');
        $consumer_unit = esc($data->consumer_unit ?: '-');
        $current_version = (int) ($data->current_version ?: 1);
        $status_label = $this->_status_label($data->status ?: 'draft');

        $actions = anchor(get_uri('fotovoltaico/proposals/view/' . $data->id), "<i data-feather='eye' class='icon-16'></i>", array(
            'class' => 'view',
            'title' => app_lang('fotovoltaico_proposal_details'),
        ));

        if ($this->_can_edit_proposal($data)) {
            $wizard_step = trim((string) ($data->wizard_step ?? ''));
            if ($wizard_step === '') {
                $wizard_step = 'client';
            }

            $actions .= anchor(get_uri('fotovoltaico/proposal_wizard/step/' . $data->id . '/' . $wizard_step), "<i data-feather='edit' class='icon-16'></i>", array(
                'class' => 'edit ms-2',
                'title' => app_lang('edit'),
            ));
        }

        $row = array(
            $proposal_title,
            esc($crm_label),
            $consumer_unit,
            $distributor,
            $current_version,
            $status_label,
            to_currency((float) $data->total, get_setting('currency_symbol')),
            format_to_date($data->updated_at, false),
            $actions
        );

        return $row;
    }

    private function _can_edit_proposal($proposal)
    {
        if (!$proposal || !(int) ($proposal->id ?? 0)) {
            return false;
        }

        if (Plugin::canManageProposals($this->login_user)) {
            return true;
        }

        if (!Plugin::canCreateProposals($this->login_user)) {
            return false;
        }

        return (int) ($proposal->created_by ?? 0) === (int) $this->login_user->id
            && in_array((string) ($proposal->status ?? 'draft'), array('draft', 'in_progress'), true);
    }

    private function _get_empty_proposal()
    {
        return (object) array(
            'id' => 0,
            'proposal_code' => '',
            'client_id' => 0,
            'lead_id' => 0,
            'contact_id' => 0,
            'distributor_id' => 0,
            'consumer_unit' => '',
            'consumption_avg' => 0,
            'title' => '',
            'status' => 'draft',
            'currency' => get_setting('default_currency'),
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'notes' => '',
            'metadata_json' => '',
            'current_version' => 1,
            'created_by_name' => '',
            'created_at' => '',
            'updated_at' => '',
        );
    }

    private function _build_summary($proposal)
    {
        if (!$proposal) {
            return array();
        }

        return array(
            'proposal_code' => $proposal->proposal_code ?: '',
            'title' => $proposal->title ?: '',
            'crm_reference' => $this->_crm_reference_label($proposal),
            'distributor' => $proposal->distributor_title ?: '',
            'consumer_unit' => $proposal->consumer_unit ?: '',
            'consumption_avg' => (float) ($proposal->consumption_avg ?? 0),
            'current_version' => (int) ($proposal->current_version ?? 0),
            'status' => $proposal->status ?: 'draft',
            'total' => (float) ($proposal->total ?? 0),
        );
    }

    private function _build_version_payload($proposal)
    {
        return array(
            'proposal' => array(
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
                'currency' => $proposal->currency ?: get_setting('default_currency'),
                'subtotal' => (float) ($proposal->subtotal ?? 0),
                'discount_total' => (float) ($proposal->discount_total ?? 0),
                'tax_total' => (float) ($proposal->tax_total ?? 0),
                'total' => (float) ($proposal->total ?? 0),
                'notes' => $proposal->notes ?: '',
                'current_version' => (int) ($proposal->current_version ?? 0),
                'issue_date' => $proposal->issue_date ?: '',
                'valid_until' => $proposal->valid_until ?: '',
                'metadata_json' => $proposal->metadata_json ?: '',
            ),
            'crm' => array(
                'type' => $this->_crm_type($proposal),
                'label' => $this->_crm_reference_label($proposal),
            ),
            'summary' => $this->_build_summary($proposal),
        );
    }

    private function _store_version_snapshot($proposal, $version_number, $status, $context = array())
    {
        $snapshot_result = $this->ProposalSnapshotService->build_snapshot($proposal, (int) $version_number, $context);
        if (!get_array_value($snapshot_result, 'success')) {
            return false;
        }

        $snapshot_json = get_array_value($snapshot_result, 'snapshot_json');
        $snapshot_payload = $this->_decode_json_to_array($snapshot_json);

        $version_data = array(
            'proposal_id' => (int) $proposal->id,
            'version_number' => (int) $version_number,
            'status' => $status ?: ($proposal->status ?: 'draft'),
            'subtotal' => (float) ($proposal->subtotal ?? 0),
            'discount_total' => (float) ($proposal->discount_total ?? 0),
            'tax_total' => (float) ($proposal->tax_total ?? 0),
            'total' => (float) ($proposal->total ?? 0),
            'result_json' => json_encode(array(
                'snapshot_hash' => get_array_value($snapshot_result, 'snapshot_hash'),
                'generated_at' => get_array_value(get_array_value($snapshot_payload, 'version'), 'generated_at'),
                'source_step' => get_array_value(get_array_value($snapshot_payload, 'version'), 'source_step'),
                'technical' => get_array_value($snapshot_payload, 'technical'),
                'financial' => get_array_value($snapshot_payload, 'financial'),
                'commercial' => get_array_value($snapshot_payload, 'commercial'),
                'results' => get_array_value($snapshot_payload, 'results'),
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

        $snapshot_id = $this->ProposalSnapshotService->store_snapshot_json($proposal, $version_id, $snapshot_json, $this->login_user->id);
        if (!$snapshot_id) {
            return false;
        }

        return array(
            'version_id' => $version_id,
            'snapshot_id' => $snapshot_id,
        );
    }

    private function _build_comparison_data($proposal_id, $from_version, $to_version)
    {
        $proposal_id = (int) $proposal_id;
        $from_version = (int) $from_version;
        $to_version = (int) $to_version;

        if (!$proposal_id || !$from_version || !$to_version) {
            return array(
                'from' => null,
                'to' => null,
                'rows' => array(),
            );
        }

        $from = $this->Proposal_versions_model->get_version($proposal_id, $from_version);
        $to = $this->Proposal_versions_model->get_version($proposal_id, $to_version);

        if (!$from || !$to) {
            return array(
                'from' => $from,
                'to' => $to,
                'rows' => array(),
            );
        }

        $from_payload = $this->_decode_json_to_array($from->payload_json ?? '');
        $to_payload = $this->_decode_json_to_array($to->payload_json ?? '');

        $from_values = get_array_value($from_payload, 'proposal') ?: array();
        $to_values = get_array_value($to_payload, 'proposal') ?: array();

        $fields = array(
            'proposal_code' => app_lang('fotovoltaico_proposal_code'),
            'title' => app_lang('fotovoltaico_proposal_title'),
            'crm' => app_lang('fotovoltaico_proposal_crm'),
            'consumer_unit' => app_lang('fotovoltaico_proposal_consumer_unit'),
            'distributor_id' => app_lang('fotovoltaico_proposal_distributor'),
            'consumption_avg' => app_lang('fotovoltaico_proposal_consumption_avg'),
            'status' => app_lang('status'),
            'subtotal' => app_lang('subtotal'),
            'discount_total' => app_lang('discount'),
            'tax_total' => app_lang('tax'),
            'total' => app_lang('total'),
            'notes' => app_lang('notes'),
        );

        $rows = array();
        foreach ($fields as $field => $label) {
            $left = $field === 'crm' ? $this->_crm_reference_label_from_payload($from_payload) : $this->_format_compare_value($field, get_array_value($from_values, $field));
            $right = $field === 'crm' ? $this->_crm_reference_label_from_payload($to_payload) : $this->_format_compare_value($field, get_array_value($to_values, $field));

            $rows[] = array(
                'label' => $label,
                'left' => $left,
                'right' => $right,
                'changed' => $left !== $right,
            );
        }

        return array(
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
        );
    }

    private function _crm_type($proposal)
    {
        if (!$proposal) {
            return 'client';
        }

        if ((int) ($proposal->contact_id ?? 0)) {
            return 'contact';
        }

        if ((int) ($proposal->lead_id ?? 0)) {
            return 'lead';
        }

        return 'client';
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

    private function _crm_reference_label_from_payload($payload)
    {
        $proposal = get_array_value($payload, 'proposal');
        if (!is_array($proposal)) {
            return '-';
        }

        if ((int) get_array_value($proposal, 'contact_id')) {
            $crm = get_array_value($payload, 'crm');
            return get_array_value($crm, 'label') ?: '-';
        }

        $crm = get_array_value($payload, 'crm');
        return get_array_value($crm, 'label') ?: '-';
    }

    private function _format_compare_value($field, $value)
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (in_array($field, array('subtotal', 'discount_total', 'tax_total', 'total', 'consumption_avg'), true)) {
            return is_numeric($value) ? number_format((float) $value, 2, ',', '.') : $value;
        }

        return (string) $value;
    }

    private function _resolve_crm_binding()
    {
        $client_id = get_only_numeric_value($this->request->getPost('client_id'));
        $lead_id = get_only_numeric_value($this->request->getPost('lead_id'));
        $contact_id = get_only_numeric_value($this->request->getPost('contact_id'));

        if ($contact_id) {
            $contact = $this->Users_model->get_one($contact_id);
            if (!$contact || !$contact->id || !in_array($contact->user_type, array('client', 'lead'), true)) {
                return false;
            }

            $client_id = (int) $contact->client_id;
            return array(
                'client_id' => $client_id,
                'lead_id' => 0,
                'contact_id' => $contact_id,
            );
        }

        if ($lead_id) {
            return array(
                'client_id' => 0,
                'lead_id' => $lead_id,
                'contact_id' => 0,
            );
        }

        if ($client_id) {
            return array(
                'client_id' => $client_id,
                'lead_id' => 0,
                'contact_id' => 0,
            );
        }

        return false;
    }

    private function _get_clients_dropdown()
    {
        $result = array('' => '-');
        $clients = $this->Clients_model->get_details()->getResult();
        foreach ($clients as $client) {
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
        $leads = $this->Clients_model->get_details(array('leads_only' => 1))->getResult();
        foreach ($leads as $lead) {
            $result[$lead->id] = $lead->company_name;
        }
        return $result;
    }

    private function _get_contacts_dropdown()
    {
        $result = array('' => '-');
        $client_contacts = $this->Users_model->get_details(array('user_type' => 'client', 'status' => 'active'))->getResult();
        $lead_contacts = $this->Users_model->get_details(array('user_type' => 'lead', 'status' => 'active'))->getResult();
        $contacts = array_merge($client_contacts, $lead_contacts);
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
        $distributors = $this->Distributors_model->get_details(array('active_only' => 1))->getResult();
        foreach ($distributors as $distributor) {
            $result[$distributor->id] = $distributor->title;
        }
        return $result;
    }

    private function _get_status_dropdown($for_filter = false)
    {
        $options = array(
            '' => '-',
            'draft' => app_lang('fotovoltaico_proposal_status_draft'),
            'in_progress' => app_lang('fotovoltaico_proposal_status_in_progress'),
            'sent' => app_lang('fotovoltaico_proposal_status_sent'),
            'reviewed' => app_lang('fotovoltaico_proposal_status_reviewed'),
            'approved' => app_lang('fotovoltaico_proposal_status_approved'),
            'lost' => app_lang('fotovoltaico_proposal_status_lost'),
            'canceled' => app_lang('fotovoltaico_proposal_status_canceled'),
        );

        if ($for_filter) {
            $list = array();
            foreach ($options as $value => $label) {
                $list[] = array('id' => $value, 'text' => $label);
            }
            return json_encode($list);
        }

        return $options;
    }

    private function _status_label($status)
    {
        $class = 'bg-secondary';
        if ($status === 'in_progress') {
            $class = 'bg-info';
        } else if ($status === 'sent') {
            $class = 'bg-primary';
        } else if ($status === 'reviewed') {
            $class = 'bg-warning text-dark';
        } else if ($status === 'approved') {
            $class = 'bg-success';
        } else if ($status === 'lost' || $status === 'canceled') {
            $class = 'bg-danger';
        }

        $label = get_array_value($this->_get_status_dropdown(false), $status) ?: $status;
        return "<span class='badge $class'>" . esc($label) . "</span>";
    }

    private function _normalize_status($status)
    {
        $status = trim((string) $status);
        $allowed = array('draft', 'in_progress', 'sent', 'reviewed', 'approved', 'lost', 'canceled');
        if (!in_array($status, $allowed, true)) {
            return 'draft';
        }

        return $status;
    }

    private function _generate_proposal_code($id = 0)
    {
        $suffix = $id ? '-' . (int) $id : '';
        return 'FVP-' . date('YmdHis') . $suffix . '-' . strtoupper(substr(make_random_string(), 0, 4));
    }

    private function _get_search_term()
    {
        $search = $this->request->getPost('search');
        if (is_array($search)) {
            return trim((string) get_array_value($search, 'value'));
        }

        return trim((string) $search);
    }

    private function _normalize_json_for_save($json_text)
    {
        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return '';
        }

        $decoded = json_decode($json_text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    private function _remember_generated_pdf($proposal, $version_number, $save_path, $snapshot_row)
    {
        if (!$save_path) {
            return false;
        }

        $metadata = $this->_decode_json_to_array($proposal->metadata_json ?? '');
        $metadata['generated_pdf'] = array(
            'version_number' => (int) $version_number,
            'file_path' => str_replace(FCPATH, '', $save_path),
            'generated_at' => get_my_local_time(),
            'snapshot_id' => (int) ($snapshot_row->id ?? 0),
        );

        $proposal_update = array(
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => get_my_local_time(),
        );

        return $this->Proposals_model->ci_save($proposal_update, (int) $proposal->id);
    }

    private function _audit($entity_type, $entity_id, $action, $old_data = array(), $new_data = array())
    {
        if (!$this->AuditService) {
            return false;
        }

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
