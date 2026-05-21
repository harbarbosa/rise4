<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\Lei14300Service;

class Lei14300 extends Security_Controller
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canManageProposals($this->login_user) && !Plugin::canManageSettings($this->login_user)) {
            app_redirect('forbidden');
        }

        $this->service = new Lei14300Service();
    }

    public function preview()
    {
        $input = array(
            'annual_generation_kwh' => $this->request->getPost('annual_generation_kwh'),
            'tariff' => $this->request->getPost('tariff'),
            'consumption_avg' => $this->request->getPost('consumption_avg'),
            'year' => $this->request->getPost('year'),
            'years' => $this->request->getPost('years'),
            'scenarios' => $this->_decode_json($this->request->getPost('scenarios_json')),
        );

        $result = $this->service->preview($input);
        echo json_encode($result);
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
