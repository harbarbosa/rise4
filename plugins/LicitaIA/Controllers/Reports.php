<?php

namespace LicitaIA\Controllers;

use App\Libraries\Pdf;

class Reports extends Licitaia_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();

        return $this->template->rander('LicitaIA\\Views\\reports\\index', array(
            'can_manage' => \LicitaIA\Plugin::canManageOpportunities($this->login_user),
            'can_generate_report' => \LicitaIA\Plugin::canGenerateReport($this->login_user),
        ));
    }

    public function list_data()
    {
        $this->ensureAccess();

        $rows = $this->reports_model->get_details(array())->getResult();
        $result = array();
        foreach ($rows as $row) {
            $result[] = array(
                esc($row->opportunity_title ?: '-'),
                esc($row->report_type ?: '-'),
                esc($row->title ?: '-'),
                esc($row->generated_at ?: '-'),
                esc($row->created_by_name ?: '-'),
                '',
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function technical_opinion($opportunity_id = 0)
    {
        $this->ensureViewReportsAccess();

        $report_data = $this->buildTechnicalOpinionData((int) $opportunity_id);
        if (!$report_data['opportunity']) {
            show_404();
        }

        return $this->template->rander('LicitaIA\\Views\\reports\\technical_opinion', $report_data);
    }

    public function download($opportunity_id = 0)
    {
        $this->ensureViewReportsAccess();

        $report_data = $this->buildTechnicalOpinionData((int) $opportunity_id);
        if (!$report_data['opportunity']) {
            show_404();
        }

        $generated = $this->generateTechnicalOpinionPdf($report_data);
        if (!$generated['success']) {
            session()->setFlashdata('error_message', $generated['message']);
            return app_redirect('licitaia/reports/technical_opinion/' . (int) $opportunity_id);
        }

        return $this->response->download($generated['file_path'], null)->setFileName($generated['file_name']);
    }

    public function generate_report($opportunity_id = 0)
    {
        $this->ensureViewReportsAccess();

        $opportunity_id = (int) $opportunity_id ?: (int) $this->request->getPost('id');
        if (!$opportunity_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(array(
                'success' => true,
                'redirect_url' => get_uri('licitaia/reports/technical_opinion/' . $opportunity_id),
            ));
        }

        return app_redirect('licitaia/reports/technical_opinion/' . $opportunity_id);
    }

    private function ensureViewReportsAccess()
    {
        if (!\LicitaIA\Plugin::canGenerateReport($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function buildTechnicalOpinionData($opportunity_id)
    {
        $opportunity_id = (int) $opportunity_id;
        $opportunity = $this->opportunities_model->get_details(array('id' => $opportunity_id))->getRow();
        if (!$opportunity) {
            return array(
                'opportunity' => null,
                'documents' => array(),
                'checklist_items' => array(),
                'checklist_progress' => array('percent' => 0, 'total' => 0, 'done' => 0, 'pending' => 0),
                'ai_requirements' => array(),
                'ai_risks' => array(),
                'decision_options' => $this->decisionOptions(),
                'latest_report' => null,
            );
        }

        $documents = $this->documentsModel()->get_by_opportunity($opportunity_id)->getResult();
        $checklist_items = $this->opportunity_checklist_model->get_by_opportunity($opportunity_id)->getResult();
        $checklist_progress = $this->opportunity_checklist_model->get_progress($opportunity_id);
        $latest_report = $this->reports_model->get_latest_by_opportunity($opportunity_id, 'technical_opinion');

        return array(
            'opportunity' => $opportunity,
            'documents' => $documents,
            'checklist_items' => $checklist_items,
            'checklist_progress' => $checklist_progress,
            'ai_requirements' => $this->decodeJsonField($opportunity->ai_requirements ?? ''),
            'ai_risks' => $this->normalizeList($opportunity->ai_risks ?? ''),
            'ai_summary' => trim((string) ($opportunity->ai_summary ?? '')),
            'ai_recommendation' => trim((string) ($opportunity->ai_recommendation ?? '')),
            'technical_score' => (float) ($opportunity->technical_score ?? 0),
            'risk_level' => trim((string) ($opportunity->risk_level ?? '')),
            'recommendation' => trim((string) ($opportunity->recommendation ?? '')),
            'decision_options' => $this->decisionOptions(),
            'latest_report' => $latest_report,
            'generated_at' => get_my_local_time(),
        );
    }

    private function generateTechnicalOpinionPdf(array $report_data)
    {
        $opportunity = get_array_value($report_data, 'opportunity');
        if (!$opportunity) {
            return array('success' => false, 'message' => app_lang('error_occurred'));
        }

        $html = $this->renderPluginView('reports/technical_opinion_pdf', $report_data);
        if (trim((string) $html) === '') {
            return array('success' => false, 'message' => app_lang('error_occurred'));
        }

        $relative_dir = 'uploads/licitaia/reports/opportunity_' . (int) $opportunity->id . '/';
        $report_dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relative_dir);
        if (!is_dir($report_dir) && !@mkdir($report_dir, 0755, true)) {
            return array('success' => false, 'message' => app_lang('error_occurred'));
        }

        $file_name = 'parecer-tecnico-oportunidade-' . (int) $opportunity->id . '-' . date('Ymd-His') . '-' . substr(uniqid('', true), -8) . '.pdf';
        $absolute_path = $report_dir . $file_name;

        try {
            $pdf = new Pdf('report');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 10);
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($absolute_path, 'F');
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA] Technical opinion PDF error: ' . $e->getMessage());
            return array('success' => false, 'message' => app_lang('error_occurred'));
        }

        if (!is_file($absolute_path)) {
            return array('success' => false, 'message' => app_lang('error_occurred'));
        }

        $report_id = $this->reports_model->save_report_file((int) $opportunity->id, array(
            'report_type' => 'technical_opinion',
            'title' => 'Parecer tecnico - ' . (string) $opportunity->title,
            'file_path' => $relative_dir . $file_name,
            'file_name' => $file_name,
            'generated_at' => get_my_local_time(),
            'created_by' => $this->login_user->id,
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
        ));

        if (!$report_id) {
            log_message('error', '[LicitaIA] Technical opinion record could not be saved for opportunity ' . (int) $opportunity->id);
        }

        return array(
            'success' => true,
            'message' => $report_id ? app_lang('record_saved') : app_lang('error_occurred'),
            'file_path' => $absolute_path,
            'file_name' => $file_name,
            'report_id' => $report_id,
        );
    }

    private function documentsModel()
    {
        return model(\LicitaIA\Models\Document_model::class);
    }

    private function decodeJsonField($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : array();
    }

    private function normalizeList($value)
    {
        if (is_array($value)) {
            $items = array();
            foreach ($value as $entry) {
                if (is_array($entry)) {
                    $entry = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $entry = trim((string) $entry);
                if ($entry !== '') {
                    $items[] = $entry;
                }
            }

            return $items;
        }

        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return $this->normalizeList($decoded);
        }

        $value = trim((string) $value);
        return $value !== '' ? array($value) : array();
    }

    private function decisionOptions()
    {
        return array(
            'participate' => app_lang('licitaia_status_participate'),
            'not_participate' => app_lang('licitaia_status_not_participate'),
            'analyze_better' => 'Analisar melhor',
        );
    }
}
