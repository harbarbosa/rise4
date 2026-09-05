<?php

namespace PontoRH\Controllers;

use PontoRH\Libraries\PontoRh_period_service;

class PontoRH_treatment_guarded extends PontoRH_treatment
{
    public function save_manual()
    {
        $member_id = (int) $this->request->getPost('team_member_id');
        $date = $this->service->normalizeDate($this->request->getPost('work_date'));
        if ($member_id && $date && (new PontoRh_period_service())->isClosed($member_id, $date)) {
            return $this->locked();
        }
        return parent::save_manual();
    }

    public function record_action()
    {
        $record_id = (int) $this->request->getPost('record_id');
        $record = $record_id ? $this->records_model->get_one_with_details($record_id, array('scope' => 'all', 'current_user_id' => (int) $this->login_user->id)) : null;
        if ($record && (new PontoRh_period_service())->isClosed((int) $record->team_member_id, (string) ($record->date ?: $record->work_date))) {
            return $this->locked();
        }
        return parent::record_action();
    }

    public function action()
    {
        $case_id = (int) $this->request->getPost('case_id');
        $case = $case_id ? $this->treatment_cases_model->get_one_with_details($case_id) : null;
        if ($case && (new PontoRh_period_service())->isClosed((int) $case->team_member_id, (string) $case->work_date)) {
            return $this->locked();
        }
        return parent::action();
    }

    protected function locked()
    {
        echo json_encode(array('success' => false, 'message' => 'Período fechado. Reabra o mês antes de tratar ou alterar marcações.'));
    }
}
