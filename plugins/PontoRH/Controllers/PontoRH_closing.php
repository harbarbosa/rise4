<?php

namespace PontoRH\Controllers;

use PontoRH\Libraries\PontoRh_period_service;

class PontoRH_closing extends PontoRH_Base_Controller
{
    protected PontoRh_period_service $period_service;

    public function __construct()
    {
        parent::__construct();
        $this->period_service = new PontoRh_period_service();
    }

    public function index()
    {
        $this->ensureTreatmentAccess();
        $month = max(1, min(12, (int) ($this->request->getGet('month') ?: get_my_local_time('n'))));
        $year = (int) ($this->request->getGet('year') ?: get_my_local_time('Y'));
        $scope = $this->currentDataScope();
        $member_ids = $this->resolveMemberIds($scope);
        $rows = array();
        foreach ($member_ids as $member_id) {
            $summary = $this->period_service->calculate($member_id, $year, $month, (int) $this->login_user->id);
            $stored = model('PontoRH\\Models\\PontoRh_monthly_summaries_model')->get_by_member_month($member_id, $year, $month);
            $summary['status'] = $stored->status ?? $summary['status'];
            $rows[] = $summary;
        }

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        $issues = $this->unresolvedIssues($scope, $start, min($end, get_my_local_time('Y-m-d')));

        return $this->template->rander('PontoRH\\Views\\closing\\index', array(
            'rows' => $rows,
            'month' => $month,
            'year' => $year,
            'month_dropdown' => pontorh_month_options(),
            'year_dropdown' => $this->yearOptions(),
            'pending_count' => count($issues),
            'can_reopen' => \PontoRH\Plugin::canManageSettings($this->login_user) || \PontoRH\Plugin::canAdmin($this->login_user),
        ));
    }

    public function close_period()
    {
        $this->ensureTreatmentWriteAccess();
        $month = max(1, min(12, (int) $this->request->getPost('month')));
        $year = (int) $this->request->getPost('year');
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        if ($end >= get_my_local_time('Y-m-d')) {
            return $this->json(false, 'O período só pode ser fechado após o término do mês.');
        }

        $scope = $this->currentDataScope();
        $member_ids = $this->resolveMemberIds($scope);
        $issues = $this->unresolvedIssues($scope, $start, $end);
        if ($issues) {
            return $this->json(false, 'Existem ' . count($issues) . ' pendência(s) abertas no período. Trate todas antes de fechar.');
        }

        foreach ($member_ids as $member_id) {
            if (!$this->period_service->setStatus($member_id, $year, $month, 'closed', (int) $this->login_user->id)) {
                return $this->json(false, 'Não foi possível concluir o fechamento para todos os funcionários.');
            }
        }
        $this->logAudit('pontorh_closing', 0, 'close_period', 'Período de ponto fechado', array('year' => $year, 'month' => $month, 'members' => $member_ids));
        return $this->json(true, 'Período fechado com sucesso. As marcações deste mês estão bloqueadas.');
    }

    public function reopen_period()
    {
        if (!\PontoRH\Plugin::canManageSettings($this->login_user) && !\PontoRH\Plugin::canAdmin($this->login_user)) {
            app_redirect('forbidden');
        }
        $month = max(1, min(12, (int) $this->request->getPost('month')));
        $year = (int) $this->request->getPost('year');
        $member_ids = $this->resolveMemberIds($this->currentDataScope());
        foreach ($member_ids as $member_id) {
            $this->period_service->setStatus($member_id, $year, $month, 'calculated', (int) $this->login_user->id);
        }
        $this->logAudit('pontorh_closing', 0, 'reopen_period', 'Período de ponto reaberto', array('year' => $year, 'month' => $month, 'members' => $member_ids));
        return $this->json(true, 'Período reaberto. Alterações voltaram a ser permitidas.');
    }

    protected function unresolvedIssues(string $scope, string $start, string $end): array
    {
        $cases = $this->treatment_cases_model->sync_cases(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($scope),
            'date_from' => $start,
            'date_to' => $end,
        ));
        return array_values(array_filter($cases, static function ($case) {
            return !in_array((string) ($case['status'] ?? ''), array('complete', 'closed', 'treated_manual'), true);
        }));
    }

    protected function resolveMemberIds(string $scope): array
    {
        $allowed = $this->accessibleTeamMemberIds($scope);
        if ($scope !== 'all') {
            return array_values(array_unique(array_map('intval', $allowed)));
        }
        $rows = model('App\\Models\\Users_model')->get_team_members_id_and_name()->getResult();
        return array_values(array_filter(array_map(static function ($row) { return (int) $row->id; }, $rows)));
    }

    protected function yearOptions(): array
    {
        $current = (int) get_my_local_time('Y');
        $out = array();
        for ($year = $current - 3; $year <= $current + 1; $year++) {
            $out[$year] = $year;
        }
        return $out;
    }

    protected function json(bool $success, string $message)
    {
        echo json_encode(array('success' => $success, 'message' => $message));
    }
}
