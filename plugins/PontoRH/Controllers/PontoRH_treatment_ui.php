<?php

namespace PontoRH\Controllers;

class PontoRH_treatment_ui extends PontoRH_treatment
{
    public function list_data()
    {
        $this->ensureTreatmentAccess();

        $filters = $this->getUiFilters();
        $scope = $this->currentDataScope();
        $previous = $this->loadExistingCaseMap($filters);

        $rows = $this->treatment_cases_model->sync_cases(array(
            'scope' => $scope,
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($scope),
            'team_member_id' => $filters['team_member_id'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ));

        foreach ($rows as $index => $row) {
            $key = (int) ($row['team_member_id'] ?? 0) . '|' . (string) ($row['work_date'] ?? '');
            $old = $previous[$key] ?? null;
            $caseId = (int) ($row['id'] ?? 0);
            if ($caseId) {
                $case = $this->treatment_cases_model->get_one_with_details($caseId);
                $case = $this->refreshCase($case, true, $old);
                if ($case) {
                    $row['status'] = (string) $case->status;
                    $row['pending_type'] = (string) $case->pending_type;
                    $row['record_count'] = (int) $case->record_count;
                    $row['last_updated_at'] = $case->last_updated_at;
                }
            }
            $rows[$index] = $row;
        }

        // Corrige casos antigos que ficaram pendentes mesmo depois de a inconsistência desaparecer.
        foreach ($previous as $old) {
            $key = (int) $old->team_member_id . '|' . (string) $old->work_date;
            $present = false;
            foreach ($rows as $row) {
                if (((int) ($row['team_member_id'] ?? 0) . '|' . (string) ($row['work_date'] ?? '')) === $key) {
                    $present = true;
                    break;
                }
            }
            if (!$present && !in_array((string) $old->status, array('closed', 'treated_manual'), true)) {
                $this->refreshCase($old, false, $old);
            }
        }

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

        $case = $this->refreshCase($case, true, $case);
        $case_records = $this->getDayRecords((int) $case->team_member_id, (string) $case->work_date);

        $view_data = array(
            'case' => $case,
            'records' => $case_records,
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

    public function save_manual()
    {
        $this->ensureTreatmentWriteAccess();
        $this->validate_submitted_data(array(
            'team_member_id' => 'required',
            'work_date' => 'required',
            'punch_time' => 'required',
            'punch_type' => 'required',
            'justification' => 'required',
        ));

        $team_member_id = (int) $this->request->getPost('team_member_id');
        $work_date = $this->service->normalizeDate($this->request->getPost('work_date')) ?: get_my_local_time('Y-m-d');
        $time = trim((string) $this->request->getPost('punch_time'));
        $local = $work_date . ' ' . ($time ?: '00:00') . ':00';
        $punch_time = function_exists('convert_date_local_to_utc') ? convert_date_local_to_utc($local) : $local;
        $punch_type = clean_data($this->request->getPost('punch_type'));
        $justification = clean_data($this->request->getPost('justification'));
        $notes = clean_data($this->request->getPost('notes'));

        if (!in_array($punch_type, array('in', 'lunch_out', 'lunch_return', 'out'), true)) {
            echo json_encode(array('success' => false, 'message' => 'Tipo de marcação inválido.'));
            return;
        }

        $record = array(
            'team_member_id' => $team_member_id,
            'user_id' => (int) $this->login_user->id,
            'work_schedule_id' => null,
            'device_id' => null,
            'location_id' => null,
            'date' => $work_date,
            'punch_time' => $punch_time,
            'punch_type' => $punch_type,
            'latitude' => 0,
            'longitude' => 0,
            'ip_address' => $this->request->getIPAddress(),
            'source' => 'manual',
            'status' => 'adjusted',
            'hash' => hash('sha256', implode('|', array($team_member_id, $work_date, $punch_time, microtime(true)))),
            'work_date' => $work_date,
            'check_in' => in_array($punch_type, array('in', 'lunch_return'), true) ? $punch_time : null,
            'check_out' => in_array($punch_type, array('out', 'lunch_out'), true) ? $punch_time : null,
            'break_minutes' => 0,
            'minutes_worked' => 0,
            'notes' => $notes ?: $justification,
            'created_by' => (int) $this->login_user->id,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        $record_id = $this->records_model->ci_save($record);
        if (!$record_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $case = $this->treatment_cases_model->get_or_create_case($team_member_id, $work_date, array(
            'scope' => $this->currentDataScope(),
            'current_user_id' => (int) $this->login_user->id,
            'team_member_ids' => $this->accessibleTeamMemberIds($this->currentDataScope()),
        ));

        if ($case && !empty($case->id)) {
            $case = $this->refreshCase($case, false, $case);
            $this->logTreatmentHistory($case, 'manual_mark_added', $justification, null, $record);
            $this->logAudit('pontorh_treatment', (int) $case->id, 'manual_mark_added', 'Marca manual adicionada no tratamento', array('record' => $record), $team_member_id);
        }

        echo json_encode(array('success' => true, 'message' => app_lang('record_saved'), 'id' => $record_id));
    }

    public function action()
    {
        $this->ensureTreatmentWriteAccess();
        $case_id = (int) $this->request->getPost('case_id');
        $action = trim((string) $this->request->getPost('action_type'));
        $justification = trim((string) clean_data($this->request->getPost('justification')));
        $case = $this->treatment_cases_model->get_one_with_details($case_id);

        if (!$case || empty($case->id)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $before = clone $case;
        $raw = $this->refreshCase($case, false, $case);
        $state = $this->analyzeCurrentCase($raw);

        if ($action === 'reprocess') {
            $this->logTreatmentHistory($raw, 'reprocess', $justification, $before, $raw);
            echo json_encode(array('success' => true, 'message' => 'Dia reprocessado com sucesso.'));
            return;
        }

        if ($action === 'approve_day') {
            if ($justification === '') {
                echo json_encode(array('success' => false, 'message' => 'Informe uma justificativa para aprovar o dia.'));
                return;
            }
            if (!empty($state['structural_issue'])) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Ainda existem marcações faltantes, extras ou fora de sequência. Corrija as marcações e reprocese o dia antes de aprovar.'
                ));
                return;
            }

            $save = array(
                'status' => 'treated_manual',
                'pending_type' => 'approved',
                'diagnostics_json' => pontorh_safe_json($state['diagnostics']),
                'last_updated_by' => (int) $this->login_user->id,
                'last_updated_at' => get_current_utc_time(),
                'updated_at' => get_current_utc_time(),
            );
            $this->treatment_cases_model->save_action($case_id, $save);
            $hashSave = array('hash' => $state['signature']);
            $this->treatment_cases_model->ci_save($hashSave, $case_id);
            $after = $this->treatment_cases_model->get_one_with_details($case_id);
            $this->logTreatmentHistory($after, 'approve_day', $justification, $before, $after);
            $this->logAudit('pontorh_treatment', $case_id, 'approve_day', 'Dia aprovado após conferência', array('justification' => $justification), (int) $case->team_member_id);
            echo json_encode(array('success' => true, 'message' => 'Dia aprovado. As ocorrências conferidas foram tratadas e registradas no histórico.'));
            return;
        }

        if ($action === 'request_justification') {
            if ($justification === '') {
                echo json_encode(array('success' => false, 'message' => 'Informe o motivo da solicitação de justificativa.'));
                return;
            }
            $save = array(
                'status' => 'awaiting_justification',
                'pending_type' => 'awaiting_justification',
                'last_updated_by' => (int) $this->login_user->id,
                'last_updated_at' => get_current_utc_time(),
            );
            $this->treatment_cases_model->save_action($case_id, $save);
            $after = $this->treatment_cases_model->get_one_with_details($case_id);
            $this->logTreatmentHistory($after, 'request_justification', $justification, $before, $after);
            echo json_encode(array('success' => true, 'message' => 'Justificativa solicitada.'));
            return;
        }

        echo json_encode(array('success' => false, 'message' => 'Ação inválida para o fluxo de tratamento.'));
    }

    private function refreshCase($case, bool $preserveResolved = true, $previous = null)
    {
        if (!$case || empty($case->id)) {
            return $case;
        }

        $state = $this->analyzeCurrentCase($case);
        $previous = $previous ?: $case;
        $oldStatus = (string) ($previous->status ?? '');
        $oldHash = (string) ($previous->hash ?? '');
        $preserve = false;

        if ($preserveResolved && $oldStatus === 'closed') {
            $preserve = true;
        } elseif ($preserveResolved && $oldStatus === 'treated_manual') {
            // Migração suave: aprovações antigas recebem a assinatura atual na primeira leitura.
            $preserve = ($oldHash === '' || $oldHash === $state['signature'] || strlen($oldHash) === 64);
        }

        $status = $preserve ? $oldStatus : $state['status'];
        $pending = $preserve ? (string) ($previous->pending_type ?: 'approved') : $state['pending_type'];

        $save = array(
            'record_count' => $state['record_count'],
            'status' => $status,
            'pending_type' => $pending,
            'classification_json' => pontorh_safe_json($state['classification']),
            'final_json' => pontorh_safe_json($state['final']),
            'diagnostics_json' => pontorh_safe_json($state['diagnostics']),
            'hash' => $state['signature'],
            'last_updated_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
        );
        $this->treatment_cases_model->ci_save($save, (int) $case->id);
        return $this->treatment_cases_model->get_one_with_details((int) $case->id);
    }

    private function analyzeCurrentCase($case): array
    {
        $records = $this->getDayRecords((int) $case->team_member_id, (string) $case->work_date);
        usort($records, static function ($a, $b) {
            return strcmp((string) ($a->punch_time ?? ''), (string) ($b->punch_time ?? ''));
        });

        $expected = array('in', 'lunch_out', 'lunch_return', 'out');
        $types = array();
        $diagnostics = array();
        $classification = array();
        // O hash da tabela é único. Inclua a identidade do caso para que dias sem
        // marcações não produzam todos o SHA-256 da string vazia.
        $signatureParts = array(
            'case:' . (int) ($case->id ?? 0),
            'team_member:' . (int) ($case->team_member_id ?? 0),
            'work_date:' . (string) ($case->work_date ?? ''),
        );
        $outside = false;

        foreach ($records as $record) {
            $type = (string) ($record->punch_type ?? '');
            $types[] = $type;
            if ((string) ($record->status ?? '') === 'outside_area') {
                $outside = true;
            }
            $signatureParts[] = implode(':', array(
                (int) ($record->id ?? 0),
                (string) ($record->punch_time ?? ''),
                $type,
                (string) ($record->status ?? ''),
            ));
            $classification[] = array(
                'id' => (int) ($record->id ?? 0),
                'time' => !empty($record->punch_time) ? pontorh_extract_time($record->punch_time) : '',
                'automatic_type' => $type,
                'effective_type' => $type,
                'source' => (string) ($record->source ?? ''),
                'status' => (string) ($record->status ?? ''),
            );
        }

        $count = count($records);
        $structural = false;
        if ($count < 4) {
            $structural = true;
            $diagnostics[] = 'Foram encontradas menos marcações do que o esperado para a jornada.';
        } elseif ($count > 4) {
            $structural = true;
            $diagnostics[] = 'Existem marcações adicionais que precisam ser conferidas.';
        }

        $missing = array_values(array_diff($expected, $types));
        if ($missing) {
            $structural = true;
            $diagnostics[] = 'Marcações faltantes: ' . implode(', ', $missing) . '.';
        }

        if ($count > 0) {
            foreach ($types as $index => $type) {
                if ($index > 3 || !isset($expected[$index]) || $type !== $expected[$index]) {
                    $structural = true;
                    $diagnostics[] = 'A ordem das marcações está diferente do padrão esperado.';
                    break;
                }
            }
        }

        if ($outside) {
            $diagnostics[] = 'Existe pelo menos uma marcação realizada fora do local permitido.';
        }

        if ($structural) {
            $status = 'incomplete';
            $pending = 'missing_punch';
        } elseif ($outside) {
            $status = 'outside_area';
            $pending = 'outside_area';
        } else {
            $status = 'complete';
            $pending = '';
        }

        return array(
            'status' => $status,
            'pending_type' => $pending,
            'structural_issue' => $structural,
            'outside_area' => $outside,
            'record_count' => $count,
            'diagnostics' => array_values(array_unique($diagnostics)),
            'classification' => $classification,
            'final' => $records,
            'signature' => hash('sha256', implode('|', $signatureParts)),
        );
    }

    private function getDayRecords(int $teamMemberId, string $workDate): array
    {
        $result = $this->records_model->get_details(array(
            'team_member_id' => $teamMemberId,
            'date_from' => $workDate,
            'date_to' => $workDate,
        ));
        return $result ? ($result->getResult() ?: array()) : array();
    }

    private function loadExistingCaseMap(array $filters): array
    {
        $rows = $this->treatment_cases_model->get_details(array(
            'team_member_id' => $filters['team_member_id'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ))->getResult();
        $map = array();
        foreach ($rows as $row) {
            $map[(int) $row->team_member_id . '|' . (string) $row->work_date] = $row;
        }
        return $map;
    }

    private function logTreatmentHistory($case, string $action, string $justification, $before, $after): void
    {
        if (!$case || empty($case->id)) {
            return;
        }
        $this->treatment_history_model->log_action(array(
            'treatment_case_id' => (int) $case->id,
            'team_member_id' => (int) $case->team_member_id,
            'user_id' => (int) $this->login_user->id,
            'action' => $action,
            'old_value_json' => $before ? pontorh_safe_json($before) : null,
            'new_value_json' => $after ? pontorh_safe_json($after) : null,
            'justification' => $justification,
            'ip_address' => $this->request->getIPAddress(),
            'source' => 'manual',
            'status' => 'logged',
            'created_by' => (int) $this->login_user->id,
            'created_at' => get_current_utc_time(),
        ));
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
