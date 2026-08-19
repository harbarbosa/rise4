<?php

namespace LaudosTecnicos\Controllers;

class StatusHistory extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureStatusesAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\status_history\\index', array());
    }

    public function list_data()
    {
        $this->ensureStatusesAccess();

        $rows = $this->status_history_model->get_details(array(
            'laudo_id' => (int) $this->request->getPost('laudo_id'),
        ))->getResult();

        $result = array();
        foreach ($rows as $row) {
            $result[] = array(
                (int) $row->laudo_id,
                esc($row->laudo_title ?: '-'),
                esc($row->from_status_name ?: $row->from_status_code ?: '-'),
                esc($row->to_status_name ?: $row->to_status_code),
                esc($row->user_name ?: '-'),
                esc($row->source ?: '-'),
                esc($row->ip_address ?: '-'),
                esc($row->created_at ?: '-'),
                esc($row->comment ?: '-'),
            );
        }

        return $this->response->setJSON(array('data' => $result));
    }
}
