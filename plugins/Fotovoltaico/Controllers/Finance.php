<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\FinanceCalcService;

class Finance extends Security_Controller
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canManageProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $this->service = new FinanceCalcService();
    }

    public function preview()
    {
        $input = array(
            'investment_initial' => $this->request->getPost('investment_initial'),
            'economy_annual' => $this->request->getPost('economy_annual'),
            'tariff_escalation' => $this->request->getPost('tariff_escalation'),
            'discount_rate' => $this->request->getPost('discount_rate'),
            'maintenance_cost_annual' => $this->request->getPost('maintenance_cost_annual'),
            'maintenance_escalation' => $this->request->getPost('maintenance_escalation'),
            'horizon' => $this->request->getPost('horizon'),
            'replacement_schedule' => $this->_decode_json($this->request->getPost('replacement_schedule_json')),
        );

        echo json_encode($this->service->preview($input));
    }

    private function _decode_json($json_text)
    {
        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return array();
        }

        $decoded = json_decode($json_text, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : array();
    }
}
