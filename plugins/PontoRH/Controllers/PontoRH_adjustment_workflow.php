<?php

namespace PontoRH\Controllers;

use PontoRH\Libraries\PontoRh_period_service;

class PontoRH_adjustment_workflow extends PontoRH_Base_Controller
{
    public function review()
    {
        if (!\PontoRH\Plugin::canApproveAdjustment($this->login_user) && !\PontoRH\Plugin::canAdmin($this->login_user)) {
            app_redirect('forbidden');
        }
        $this->validate_submitted_data(array('id' => 'required', 'decision' => 'required'));
        $id = (int) $this->request->getPost('id');
        $decision = clean_data($this->request->getPost('decision'));
        if (!in_array($decision, array('approved', 'rejected'), true)) {
            return $this->respond(false, app_lang('error_occurred'));
        }

        $adjustment = $this->adjustments_model->get_one_with_details($id, array('scope' => 'all', 'current_user_id' => (int) $this->login_user->id));
        if (!$adjustment || (string) $adjustment->status !== 'pending') {
            return $this->respond(false, 'A solicitação não está mais pendente.');
        }

        $period = new PontoRh_period_service();
        if ($period->isClosed((int) $adjustment->team_member_id, (string) $adjustment->request_date)) {
            return $this->respond(false, 'Este período está fechado. Reabra o mês antes de tratar o ajuste.');
        }

        $before = clone $adjustment;
        $now = get_current_utc_time();
        $record_id = (int) ($adjustment->record_id ?? 0);

        if ($decision === 'approved' && !$record_id) {
            $punch_time = (string) ($adjustment->requested_time ?? '');
            $punch_type = (string) ($adjustment->adjustment_type ?? '');
            if (!$punch_time || !in_array($punch_type, array('in', 'lunch_out', 'lunch_return', 'out'), true)) {
                return $this->respond(false, 'O ajuste não possui horário ou tipo de marcação válido.');
            }

            $record_data = array(
                'team_member_id' => (int) $adjustment->team_member_id,
                'user_id' => (int) $this->login_user->id,
                'work_schedule_id' => null,
                'device_id' => null,
                'location_id' => null,
                'date' => (string) $adjustment->request_date,
                'punch_time' => $punch_time,
                'punch_type' => $punch_type,
                'latitude' => 0,
                'longitude' => 0,
                'ip_address' => $this->request->getIPAddress(),
                'source' => 'adjustment',
                'status' => 'adjusted',
                'hash' => hash('sha256', implode('|', array('adjustment', $id, $adjustment->team_member_id, $adjustment->request_date, $punch_time, $punch_type))),
                'work_date' => (string) $adjustment->request_date,
                'check_in' => in_array($punch_type, array('in', 'lunch_return'), true) ? $punch_time : null,
                'check_out' => in_array($punch_type, array('lunch_out', 'out'), true) ? $punch_time : null,
                'break_minutes' => 0,
                'minutes_worked' => 0,
                'notes' => 'Ajuste aprovado #' . $id . ': ' . (string) ($adjustment->reason ?? ''),
                'created_by' => (int) $this->login_user->id,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            );
            $record_id = (int) $this->records_model->ci_save($record_data);
            if (!$record_id) {
                return $this->respond(false, 'Não foi possível criar a marcação do ajuste.');
            }
            $this->logAudit('pontorh_records', $record_id, 'create_from_adjustment', 'Marcação criada a partir de ajuste aprovado', array('adjustment_id' => $id, 'record' => $record_data), (int) $adjustment->team_member_id);
        }

        $update = array(
            'status' => $decision,
            'record_id' => $record_id ?: null,
            'reviewed_by' => (int) $this->login_user->id,
            'reviewed_at' => $now,
            'updated_at' => $now,
        );
        if (!$this->adjustments_model->ci_save($update, $id)) {
            return $this->respond(false, app_lang('error_occurred'));
        }

        $after = $this->adjustments_model->get_one_with_details($id, array('scope' => 'all', 'current_user_id' => (int) $this->login_user->id));
        $this->logAudit('pontorh_adjustment_requests', $id, $decision, $decision === 'approved' ? 'Ajuste aprovado e aplicado ao ponto' : 'Ajuste rejeitado', array('before' => $before, 'after' => $after), (int) $adjustment->team_member_id);
        try {
            log_notification('pontorh_adjustment_reviewed', array('plugin_adjustment_id' => $id, 'plugin_requester_id' => (int) $adjustment->team_member_id, 'plugin_decision' => $decision), (int) $this->login_user->id);
        } catch (\Throwable $e) {
            log_message('error', '[PontoRH] Adjustment notification failed: ' . $e->getMessage());
        }

        $period->calculate((int) $adjustment->team_member_id, (int) date('Y', strtotime($adjustment->request_date)), (int) date('n', strtotime($adjustment->request_date)), (int) $this->login_user->id);
        return $this->respond(true, $decision === 'approved' ? 'Ajuste aprovado e marcação aplicada.' : 'Ajuste rejeitado.');
    }

    protected function respond(bool $success, string $message)
    {
        echo json_encode(array('success' => $success, 'message' => $message));
    }
}
