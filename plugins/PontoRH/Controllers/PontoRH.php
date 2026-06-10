<?php

namespace PontoRH\Controllers;

class PontoRH extends PontoRH_Base_Controller
{
    public function index()
    {
        $this->ensureDashboardAccess();
        $scope = $this->currentDataScope();
        $member_ids = $this->accessibleTeamMemberIds($scope);
        $filters = $this->getDashboardFilters();
        $overview = $this->records_model->get_dashboard_overview(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $member_ids,
            'month' => $filters['month'],
            'year' => $filters['year'],
        ));

        $view_data['dashboard'] = $overview;
        $view_data['summary'] = get_array_value($overview, 'summary', array());
        $view_data['charts'] = get_array_value($overview, 'charts', array());
        $view_data['recent_records'] = $this->records_model->get_recent_records(5, array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $member_ids,
        ));
        $view_data['pending_adjustments'] = $this->adjustments_model->get_pending_count(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $member_ids,
        ));
        $view_data['records_can_manage'] = \PontoRH\Plugin::canManageRecords($this->login_user);
        $view_data['shifts_can_manage'] = \PontoRH\Plugin::canManageShifts($this->login_user);
        $view_data['adjustments_can_manage'] = \PontoRH\Plugin::canManageAdjustments($this->login_user);
        $view_data['current_scope'] = $scope;
        $view_data['dashboard_filters'] = $filters;
        $view_data['month_dropdown'] = pontorh_month_options();
        $view_data['year_dropdown'] = $this->getDashboardYearOptions();
        $view_data['dashboard_period'] = $this->getMirrorPeriodLabel($filters['month'], $filters['year']);

        return $this->template->rander('PontoRH\\Views\\dashboard\\index', $view_data);
    }

    public function mirror()
    {
        $this->ensureRecordsAccess();

        $filters = $this->getMirrorFilters();
        $scope = $this->currentDataScope();
        $selected_member_id = $filters['team_member_id'] ?: ($scope === 'own' ? (int) $this->login_user->id : 0);
        $selected_member = $selected_member_id ? model('App\\Models\\Users_model')->get_one($selected_member_id) : null;
        $report = $this->records_model->get_mirror_report(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($scope),
            'team_member_id' => $filters['team_member_id'],
            'month' => $filters['month'],
            'year' => $filters['year'],
        ));

        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, $scope);
        $view_data['month_dropdown'] = pontorh_month_options();
        $view_data['year_dropdown'] = $this->getMirrorYearOptions();
        $view_data['filters'] = $filters;
        $view_data['report'] = $report;
        $view_data['summary'] = get_array_value($report, 'summary', array());
        $view_data['rows'] = get_array_value($report, 'rows', array());
        $view_data['selected_member'] = $selected_member;
        $view_data['schedule'] = get_array_value($report, 'schedule');
        $view_data['report_title'] = app_lang('pontorh_mirror');
        $view_data['report_subtitle'] = $this->getMirrorPeriodLabel($filters['month'], $filters['year']);
        $view_data['export_pdf_url'] = get_uri('pontorh/espelho/export_pdf') . '?' . http_build_query($filters);
        $view_data['export_excel_url'] = get_uri('pontorh/espelho/export_excel') . '?' . http_build_query($filters);

        return $this->template->rander('PontoRH\\Views\\mirror\\index', $view_data);
    }

    public function export_pdf()
    {
        $this->ensureRecordsAccess();

        $filters = $this->getMirrorFilters();
        $selected_member_id = $filters['team_member_id'] ?: ($this->currentDataScope() === 'own' ? (int) $this->login_user->id : 0);
        $selected_member = $selected_member_id ? model('App\\Models\\Users_model')->get_one($selected_member_id) : null;
        $report = $this->records_model->get_mirror_report(array(
            'scope' => $this->currentDataScope(),
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($this->currentDataScope()),
            'team_member_id' => $filters['team_member_id'],
            'month' => $filters['month'],
            'year' => $filters['year'],
        ));

        $view_data['filters'] = $filters;
        $view_data['report'] = $report;
        $view_data['summary'] = get_array_value($report, 'summary', array());
        $view_data['rows'] = get_array_value($report, 'rows', array());
        $view_data['selected_member'] = $selected_member;
        $view_data['schedule'] = get_array_value($report, 'schedule');
        $view_data['report_title'] = app_lang('pontorh_mirror');
        $view_data['report_subtitle'] = $this->getMirrorPeriodLabel($filters['month'], $filters['year']);
        $html = $this->renderPluginView('mirror/pdf', $view_data);

        $pdf = new \App\Libraries\Pdf('general');
        $pdf->SetTitle($view_data['report_title']);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);

        return $pdf->PreparePDF($html, 'pontorh_mirror_' . $filters['year'] . '_' . str_pad((string) $filters['month'], 2, '0', STR_PAD_LEFT), 'view');
    }

    public function export_excel()
    {
        $this->ensureRecordsAccess();

        $filters = $this->getMirrorFilters();
        $selected_member_id = $filters['team_member_id'] ?: ($this->currentDataScope() === 'own' ? (int) $this->login_user->id : 0);
        $selected_member = $selected_member_id ? model('App\\Models\\Users_model')->get_one($selected_member_id) : null;
        $report = $this->records_model->get_mirror_report(array(
            'scope' => $this->currentDataScope(),
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($this->currentDataScope()),
            'team_member_id' => $filters['team_member_id'],
            'month' => $filters['month'],
            'year' => $filters['year'],
        ));

        require_once(APPPATH . 'ThirdParty/PHPOffice-PhpSpreadsheet/vendor/autoload.php');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Espelho');

        $row = 1;
        $writeRow = function (array $values) use (&$sheet, &$row) {
            $column = 1;
            foreach ($values as $value) {
                $sheet->setCellValueByColumnAndRow($column, $row, $value);
                $column++;
            }
            $row++;
        };

        $writeRow(array(
            app_lang('pontorh_mirror'),
            $this->getMirrorPeriodLabel($filters['month'], $filters['year'])
        ));
        $writeRow(array(
            app_lang('pontorh_employee'),
            $selected_member->first_name ?? '',
            $selected_member->last_name ?? ''
        ));
        $writeRow(array());
        $writeRow(array(
            app_lang('pontorh_work_date'),
            app_lang('pontorh_check_in'),
            app_lang('pontorh_check_out'),
            app_lang('pontorh_break_minutes'),
            app_lang('pontorh_minutes_worked'),
            app_lang('pontorh_extra_hours'),
            app_lang('pontorh_bank_hours'),
            app_lang('pontorh_absences'),
            app_lang('pontorh_lateness')
        ));

        foreach (get_array_value($report, 'rows', array()) as $day) {
            $writeRow(array(
                $day['date'],
                $day['entries'],
                $day['exits'],
                pontorh_minutes_to_hours_label($day['intervals_minutes']),
                pontorh_minutes_to_hours_label($day['worked_minutes']),
                pontorh_minutes_to_hours_label($day['extra_minutes']),
                pontorh_minutes_to_hours_label($day['bank_minutes']),
                (int) $day['absences'],
                pontorh_minutes_to_hours_label($day['lateness_minutes']),
            ));
        }

        $filename = 'pontorh_mirror_' . $filters['year'] . '_' . str_pad((string) $filters['month'], 2, '0', STR_PAD_LEFT) . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }

    public function reports()
    {
        $this->ensureReportsAccess();
        $scope = $this->currentDataScope();
        $filters = $this->getReportFilters();
        $report = $this->records_model->get_report_overview(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($scope),
            'team_member_id' => $filters['team_member_id'],
            'month' => $filters['month'],
            'year' => $filters['year'],
        ));

        $selected_member_id = $filters['team_member_id'] ?: ($scope === 'own' ? (int) $this->login_user->id : 0);
        $selected_member = $selected_member_id ? model('App\\Models\\Users_model')->get_one($selected_member_id) : null;

        $view_data['team_members_dropdown'] = $this->teamMembersDropdown(true, $scope);
        $view_data['month_dropdown'] = pontorh_month_options();
        $view_data['year_dropdown'] = $this->getMirrorYearOptions();
        $view_data['filters'] = $filters;
        $view_data['selected_member'] = $selected_member;
        $view_data['summary'] = get_array_value($report, 'summary', array());
        $view_data['charts'] = get_array_value($report, 'charts', array());
        $view_data['report_period'] = $this->getMirrorPeriodLabel($filters['month'], $filters['year']);

        return $this->template->rander('PontoRH\\Views\\reports\\index', $view_data);
    }

    public function audit_logs()
    {
        $this->ensureSettingsAccess();
        return $this->template->rander('PontoRH\\Views\\audit_logs\\index', array());
    }

    private function getMirrorFilters()
    {
        $now = new \DateTimeImmutable('now');

        return array(
            'team_member_id' => (int) $this->request->getGet('team_member_id'),
            'month' => max(1, min(12, (int) ($this->request->getGet('month') ?: $now->format('n')))),
            'year' => max(1970, (int) ($this->request->getGet('year') ?: $now->format('Y'))),
        );
    }

    private function getMirrorYearOptions()
    {
        $current_year = (int) get_my_local_time('Y');
        $years = array();
        for ($year = $current_year - 2; $year <= $current_year + 1; $year++) {
            $years[$year] = $year;
        }

        return $years;
    }

    private function getMirrorPeriodLabel($month, $year)
    {
        $month = max(1, min(12, (int) $month));
        $year = (int) $year;
        $months = pontorh_month_options();
        return get_array_value($months, $month) . ' / ' . $year;
    }

    private function getReportFilters()
    {
        $now = new \DateTimeImmutable('now');

        return array(
            'team_member_id' => (int) $this->request->getGet('team_member_id'),
            'month' => max(1, min(12, (int) ($this->request->getGet('month') ?: $now->format('n')))),
            'year' => max(1970, (int) ($this->request->getGet('year') ?: $now->format('Y'))),
        );
    }

    private function getDashboardFilters()
    {
        $now = new \DateTimeImmutable('now');

        return array(
            'month' => max(1, min(12, (int) ($this->request->getGet('month') ?: $now->format('n')))),
            'year' => max(1970, (int) ($this->request->getGet('year') ?: $now->format('Y'))),
        );
    }

    private function getDashboardYearOptions()
    {
        $current_year = (int) get_my_local_time('Y');
        $years = array();
        for ($year = $current_year - 1; $year <= $current_year + 1; $year++) {
            $years[$year] = $year;
        }

        return $years;
    }
}
