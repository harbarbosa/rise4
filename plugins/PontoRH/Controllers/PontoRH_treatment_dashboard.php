<?php

namespace PontoRH\Controllers;

class PontoRH_treatment_dashboard extends PontoRH_treatment_ui
{
    public function index()
    {
        $this->ensureTreatmentAccess();

        $now = new \DateTimeImmutable('now');
        $today = $now->format('Y-m-d');
        $date_from = trim((string) ($this->request->getGet('date_from') ?: $now->format('Y-m-01')));
        $date_to = trim((string) ($this->request->getGet('date_to') ?: $today));
        $team_member_id = (int) $this->request->getGet('team_member_id');
        $date_from = $this->service->normalizeDate($date_from) ?: $now->format('Y-m-01');
        $date_to = $this->service->normalizeDate($date_to) ?: $today;
        if ($date_to > $today) {
            $date_to = $today;
        }

        $rows = $this->treatment_cases_model->get_details(array(
            'team_member_id' => $team_member_id,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ))->getResult();

        $summary = array(
            'pending_total' => 0,
            'incomplete_days' => 0,
            'inconsistent_days' => 0,
            'adjustments_pending' => 0,
            'outside_area' => 0,
            'no_photo' => 0,
            'awaiting_justification' => 0,
        );

        foreach ($rows as $row) {
            $status = (string) ($row->status ?? '');
            $pending = (string) ($row->pending_type ?? '');
            if (!in_array($status, array('complete', 'closed', 'treated_manual'), true)) {
                $summary['pending_total']++;
            }
            if ($status === 'incomplete') {
                $summary['incomplete_days']++;
            }
            if ($status === 'inconsistent') {
                $summary['inconsistent_days']++;
            }
            if ($status === 'adjustment_requested') {
                $summary['adjustments_pending']++;
            }
            if ($status === 'outside_area') {
                $summary['outside_area']++;
            }
            if ($status === 'no_photo') {
                $summary['no_photo']++;
            }
            if ($status === 'awaiting_justification' || $pending === 'awaiting_justification') {
                $summary['awaiting_justification']++;
            }
        }

        $scope = $this->currentDataScope();
        $view_data = array(
            'filters' => array(
                'team_member_id' => $team_member_id,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'status' => trim((string) $this->request->getGet('status')),
                'pending_type' => trim((string) $this->request->getGet('pending_type')),
                'month' => (int) $now->format('n'),
                'year' => (int) $now->format('Y'),
            ),
            'summary' => $summary,
            'team_members_dropdown' => $this->teamMembersDropdown(true, $scope),
            'status_dropdown' => pontorh_treatment_status_options(),
            'pending_type_dropdown' => pontorh_treatment_pending_type_options(),
            'month_dropdown' => pontorh_month_options(),
            'year_dropdown' => $this->yearOptions(),
            'dashboard_period' => format_to_date($date_from, false) . ' - ' . format_to_date($date_to, false),
        );

        return $this->template->rander('PontoRH\\Views\\treatment\\index', $view_data);
    }

    private function yearOptions(): array
    {
        $current = (int) date('Y');
        return array(
            $current - 1 => $current - 1,
            $current => $current,
            $current + 1 => $current + 1,
        );
    }
}
