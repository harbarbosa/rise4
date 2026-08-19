<?php

namespace LaudosTecnicos\Controllers;

use LaudosTecnicos\Models\LaudoEquipments_model;
use LaudosTecnicos\Models\LaudoNonconformities_model;
use LaudosTecnicos\Models\LaudoPlatform_model;

class Reports extends LaudosTecnicos_Base_Controller
{
    private LaudoPlatform_model $platform_model;
    private LaudoNonconformities_model $nonconformities_model;
    private LaudoEquipments_model $equipments_model;

    public function __construct()
    {
        parent::__construct();
        $this->platform_model = model(LaudoPlatform_model::class);
        $this->nonconformities_model = model(LaudoNonconformities_model::class);
        $this->equipments_model = model(LaudoEquipments_model::class);
    }

    public function index()
    {
        $this->ensureAccess();
        if (!\LaudosTecnicos\Plugin::canViewReports($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        return $this->template->rander('LaudosTecnicos\\Views\\reports\\index', array(
            'summary' => $this->platform_model->get_report_summary(),
            'nc_stats' => $this->nonconformities_model->get_dashboard_stats(),
            'report_types' => $this->reportTypes(),
        ));
    }

    public function export_csv($report = '')
    {
        $this->ensureAccess();
        if (!\LaudosTecnicos\Plugin::canViewReports($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        return $this->exportReport((string) $report, 'csv');
    }

    public function export_xls($report = '')
    {
        $this->ensureAccess();
        if (!\LaudosTecnicos\Plugin::canViewReports($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        return $this->exportReport((string) $report, 'xls');
    }

    public function print_view($report = '')
    {
        $this->ensureAccess();
        if (!\LaudosTecnicos\Plugin::canViewReports($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        return $this->template->view('LaudosTecnicos\\Views\\reports\\report', array(
            'report' => (string) $report,
            'rows' => $this->reportRows((string) $report),
            'as_print' => true,
        ));
    }

    public function pdf($report = '')
    {
        $this->ensureAccess();
        if (!\LaudosTecnicos\Plugin::canViewReports($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        $rows = $this->reportRows((string) $report);
        $html = $this->template->view('LaudosTecnicos\\Views\\reports\\report', array(
            'report' => (string) $report,
            'rows' => $rows,
            'as_print' => false,
        ));

        $pdf = new \App\Libraries\Pdf('laudo');
        $pdf->SetCreator('LaudosTecnicos');
        $pdf->SetAuthor('LaudosTecnicos');
        $pdf->SetTitle('Relatorio ' . $report);
        $pdf->SetMargins(12, 14, 12);
        $pdf->AddPage('P', 'A4');
        $pdf->writeHTML($html, true, false, true, false, '');
        return $pdf->Output('relatorio-' . $report . '.pdf', 'I');
    }

    private function exportReport(string $report, string $format)
    {
        $rows = array_values($this->reportRows($report));
        if ($format === 'csv') {
            $output = fopen('php://temp', 'r+');
            if ($rows) {
                fputcsv($output, array_keys((array) $rows[0]));
                foreach ($rows as $row) {
                    fputcsv($output, (array) $row);
                }
            }
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="relatorio-' . $report . '.csv"')
                ->setBody($csv);
        }

        $html = $this->template->view('LaudosTecnicos\\Views\\reports\\report', array('report' => $report, 'rows' => $rows, 'as_print' => false));
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="relatorio-' . $report . '.xls"')
            ->setBody($html);
    }

    private function reportRows(string $report): array
    {
        switch ($report) {
            case 'laudos-status':
                return $this->laudos_model->get_details(array())->getResult();
            case 'nao-conformidades':
                return $this->nonconformities_model->get_details(array())->getResult();
            case 'equipamentos-vencidos':
                return array_filter($this->equipments_model->get_details(array())->getResult(), function ($row) {
                    return $this->equipments_model->calibration_status($row) === 'expired';
                });
            case 'api-usage':
                return array((object) $this->platform_model->get_report_summary());
            case 'ai-usage':
                $summary = $this->platform_model->get_report_summary();
                return array((object) array(
                    'total_requests' => (int) get_array_value($summary, 'ai_requests'),
                    'provider' => get_setting('ai_provider'),
                    'model' => get_setting('ai_model'),
                    'user_limit' => get_setting('ai_user_limit'),
                ));
            default:
                return array();
        }
    }

    private function reportTypes(): array
    {
        return array(
            'laudos-status' => 'Laudos por status',
            'nao-conformidades' => 'Nao conformidades',
            'equipamentos-vencidos' => 'Equipamentos vencidos',
            'api-usage' => 'Uso da API',
            'ai-usage' => 'Consumo de IA',
        );
    }
}
