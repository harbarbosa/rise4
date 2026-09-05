<?php

namespace PontoRH\Controllers;

class PontoRH_treatment_ui extends PontoRH_treatment
{
    public function list_data()
    {
        $this->ensureTreatmentAccess();

        $filters = $this->getUiFilters();
        $scope = $this->currentDataScope();
        $rows = $this->treatment_cases_model->sync_cases(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($scope),
            'team_member_id' => $filters['team_member_id'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ));

        $team_member_filter = (int) $this->request->getPost('team_member_id');
        $status_filter = trim((string) $this->request->getPost('status'));
        $pending_type_filter = trim((string) $this->request->getPost('pending_type'));
        $search = strtolower(trim((string) $this->request->getPost('search')));
        $data = array();

        foreach ($rows as $row) {
            if ($team_member_filter && (int) $row['team_member_id'] !== $team_member_filter) {
                continue;
            }
            if ($status_filter !== '' && (string) $row['status'] !== $status_filter) {
                continue;
            }
            if ($pending_type_filter !== '' && (string) $row['pending_type'] !== $pending_type_filter) {
                continue;
            }
            if ($search !== '') {
                $haystack = strtolower(implode(' ', array(
                    $row['team_member_name'] ?? '',
                    $row['project_name'] ?? '',
                    $row['work_date'] ?? '',
                    $row['status'] ?? '',
                    $row['pending_type'] ?? '',
                )));
                if (strpos($haystack, $search) === false) {
                    continue;
                }
            }
            $data[] = $this->makeUiRow($row);
        }

        echo json_encode(array('data' => $data));
    }

    public function details($id = 0)
    {
        $this->ensureTreatmentAccess();

        $id = (int) ($id ?: $this->request->getPost('id'));
        $case = $this->treatment_cases_model->get_one_with_details($id);
        if (!$case || empty($case->id)) {
            app_redirect('forbidden');
        }

        $case_records = $this->records_model->get_details(array(
            'team_member_id' => (int) $case->team_member_id,
            'date_from' => $case->work_date,
            'date_to' => $case->work_date,
        ))->getResult();

        $view_data = array(
            'case' => $case,
            'records' => $case_records ?: array(),
            'history' => $this->treatment_history_model->get_details(array('treatment_case_id' => (int) $case->id))->getResult(),
            'diagnostics' => $this->decodeUiJson($case->diagnostics_json ?? null),
            'classification' => $this->decodeUiJson($case->classification_json ?? null),
            'final' => $this->decodeUiJson($case->final_json ?? null),
            'can_write' => \PontoRH\Plugin::canApproveAdjustment($this->login_user)
                || \PontoRH\Plugin::canViewReports($this->login_user)
                || \PontoRH\Plugin::canManageSettings($this->login_user)
                || \PontoRH\Plugin::canAdmin($this->login_user),
        );

        return $this->template->rander('PontoRH\\Views\\treatment\\details', $view_data);
    }

    private function makeUiRow(array $row): array
    {
        $status = (string) ($row['status'] ?? 'pending');
        $pending_type = (string) ($row['pending_type'] ?? 'incomplete');
        $case_id = (int) ($row['id'] ?? 0);
        $details_url = get_uri('pontorh/tratamento/detalhes/' . $case_id);
        $modal_url = get_uri('pontorh/tratamento/modal_form');

        $actions = '<a href="' . esc($details_url) . '" class="action-icon" title="' . esc(app_lang('view_details')) . '"><i data-feather="eye" class="icon-14"></i></a>';
        $actions .= modal_anchor($modal_url, "<i data-feather='plus-circle' class='icon-14'></i>", array(
            'class' => 'action-icon',
            'title' => app_lang('pontorh_add_manual_mark'),
            'data-modal-lg' => '1',
            'data-post-id' => $case_id,
        ));

        return array(
            esc($row['team_member_name'] ?? $row['user_name'] ?? '-'),
            esc(format_to_date($row['work_date'] ?? '', false)),
            esc($row['project_name'] ?? '-'),
            (int) ($row['record_count'] ?? 0),
            '<span class="badge bg-secondary">' . esc(pontorh_treatment_status_label($status)) . '</span>',
            esc(pontorh_treatment_pending_type_label($pending_type)),
            !empty($row['last_updated_at']) && is_date_exists($row['last_updated_at']) ? format_to_datetime($row['last_updated_at'], false) : '-',
            $actions,
        );
    }

    private function getUiFilters(): array
    {
        $now = new \DateTimeImmutable('now');
        $today = $now->format('Y-m-d');
        $date_from = trim((string) ($this->request->getPost('date_from') ?: $this->request->getGet('date_from')));
        $date_to = trim((string) ($this->request->getPost('date_to') ?: $this->request->getGet('date_to')));
        $team_member_id = (int) ($this->request->getPost('team_member_id') ?: $this->request->getGet('team_member_id'));
        $date_from = $this->service->normalizeDate($date_from) ?: $now->format('Y-m-01');
        $date_to = $this->service->normalizeDate($date_to) ?: $today;
        if ($date_to > $today) {
            $date_to = $today;
        }
        return array('team_member_id' => $team_member_id, 'date_from' => $date_from, 'date_to' => $date_to);
    }

    private function decodeUiJson($json): array
    {
        if (!$json) {
            return array();
        }
        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : array();
    }
}
