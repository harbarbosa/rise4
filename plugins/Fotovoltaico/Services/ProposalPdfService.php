<?php

namespace Fotovoltaico\Services;

use App\Libraries\Pdf;
use Fotovoltaico\Models\Proposals_model;

class ProposalPdfService
{
    private $ProposalSnapshotService;
    private $Proposals_model;

    public function __construct()
    {
        $this->ProposalSnapshotService = new ProposalSnapshotService();
        $this->Proposals_model = model(Proposals_model::class);
    }

    public function generate($proposal_id, $proposal_version_id = 0)
    {
        $proposal_id = (int) $proposal_id;
        $proposal_version_id = (int) $proposal_version_id;

        $proposal = $this->Proposals_model->get_one_with_details($proposal_id);
        if (!$proposal || !$proposal->id) {
            return array('success' => false, 'message' => 'Proposal not found');
        }

        $snapshot_row = $this->ProposalSnapshotService->get_snapshot_for_pdf($proposal_id, $proposal_version_id);
        if (!$snapshot_row || !$snapshot_row->snapshot_json) {
            return array('success' => false, 'message' => 'Snapshot not found');
        }

        $snapshot = json_decode($snapshot_row->snapshot_json, true);
        if (!is_array($snapshot)) {
            return array('success' => false, 'message' => 'Invalid snapshot');
        }

        $version = get_array_value($snapshot, 'version') ?: array();
        $proposal_block = get_array_value($snapshot, 'proposal') ?: array();
        $kit = get_array_value($snapshot, 'kit') ?: array();
        $technical = get_array_value($snapshot, 'technical') ?: array();
        $financial = get_array_value($snapshot, 'financial') ?: array();
        $commercial = get_array_value($snapshot, 'commercial') ?: array();
        $insolation = get_array_value($snapshot, 'insolation') ?: array();
        $wizard = get_array_value($snapshot, 'wizard') ?: array();
        $results = get_array_value($snapshot, 'results') ?: array();
        $crm = get_array_value($snapshot, 'crm') ?: array();

        $view_data = array(
            'proposal' => $proposal,
            'snapshot' => $snapshot,
            'version' => $version,
            'proposal_block' => $proposal_block,
            'kit' => $kit,
            'technical' => $technical,
            'financial' => $financial,
            'commercial' => $commercial,
            'insolation' => $insolation,
            'wizard' => $wizard,
            'results' => $results,
            'crm' => $crm,
            'pdf_title' => $this->_build_title($proposal_block, $version),
            'generated_at' => get_array_value($version, 'generated_at') ?: get_my_local_time(),
        );

        $html = view('Fotovoltaico\\Views\\pdf\\proposal', $view_data);
        $pdf = new Pdf('proposal');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(get_setting('company_name') ?: get_setting('site_name'));
        $pdf->SetTitle($view_data['pdf_title']);
        $pdf->SetSubject($view_data['pdf_title']);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $file_name = $this->_build_file_name($proposal_block, $version);
        $pdf_content = $pdf->Output($file_name, 'S');

        $save_path = $this->_save_copy($proposal_id, $file_name, $pdf_content);

        return array(
            'success' => true,
            'proposal' => $proposal,
            'snapshot' => $snapshot,
            'snapshot_row' => $snapshot_row,
            'version' => $version,
            'file_name' => $file_name,
            'save_path' => $save_path,
            'save_url' => $save_path ? $this->_to_public_url($save_path) : '',
            'pdf_content' => $pdf_content,
            'html' => $html,
        );
    }

    private function _build_title($proposal_block, $version)
    {
        $code = get_array_value($proposal_block, 'proposal_code') ?: 'FVP';
        $title = get_array_value($proposal_block, 'title') ?: $code;
        $version_number = (int) get_array_value($version, 'number');
        return trim($title . ' - V' . $version_number);
    }

    private function _build_file_name($proposal_block, $version)
    {
        $code = get_array_value($proposal_block, 'proposal_code') ?: 'FVP';
        $version_number = (int) get_array_value($version, 'number');
        return get_hyphenated_string('fotovoltaico-' . $code . '-v' . $version_number) . '.pdf';
    }

    private function _save_copy($proposal_id, $file_name, $pdf_content)
    {
        $base_dir = FCPATH . 'files/fotovoltaico/proposals/' . (int) $proposal_id . '/';
        if (!is_dir($base_dir)) {
            @mkdir($base_dir, 0775, true);
        }

        if (!is_dir($base_dir) || !is_writable($base_dir)) {
            return '';
        }

        $full_path = $base_dir . $file_name;
        $written = file_put_contents($full_path, $pdf_content);

        return $written === false ? '' : $full_path;
    }

    private function _to_public_url($path)
    {
        $path = str_replace(FCPATH, '', $path);
        $path = str_replace('\\', '/', $path);
        return base_url($path);
    }
}
