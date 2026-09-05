<?php

namespace PontoRH\Controllers;

use PontoRH\Libraries\PontoRh_period_service;

class PontoRH_adjustments_guarded extends PontoRH_adjustments
{
    public function save()
    {
        $member_id = (int) $this->request->getPost('team_member_id');
        $date = $this->service->normalizeDate($this->request->getPost('request_date') ?: $this->request->getPost('adjustment_date'));
        $id = (int) $this->request->getPost('id');
        if ($id) {
            $existing = $this->adjustments_model->get_one_with_details($id, array('scope' => 'all', 'current_user_id' => (int) $this->login_user->id));
            if ($existing) {
                $member_id = (int) $existing->team_member_id;
                $date = (string) $existing->request_date;
            }
        }
        if ($member_id && $date && (new PontoRh_period_service())->isClosed($member_id, $date)) {
            echo json_encode(array('success' => false, 'message' => 'Período fechado. Reabra o mês antes de solicitar ou alterar ajustes.'));
            return;
        }
        return parent::save();
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        $existing = $id ? $this->adjustments_model->get_one_with_details($id, array('scope' => 'all', 'current_user_id' => (int) $this->login_user->id)) : null;
        if ($existing && (new PontoRh_period_service())->isClosed((int) $existing->team_member_id, (string) $existing->request_date)) {
            echo json_encode(array('success' => false, 'message' => 'Período fechado. Reabra o mês antes de excluir ajustes.'));
            return;
        }
        return parent::delete();
    }
}
