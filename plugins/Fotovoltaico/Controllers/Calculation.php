<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\PvCalcService;

class Calculation extends Security_Controller
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canCreateProposals($this->login_user) && !Plugin::canManageProposals($this->login_user)) {
            app_redirect('forbidden');
        }

        $this->service = new PvCalcService();
    }

    public function preview()
    {
        $input = array(
            'system_power_kwp' => $this->request->getPost('system_power_kwp'),
            'insolation' => $this->request->getPost('insolation'),
            'pr' => $this->request->getPost('pr'),
            'consumption_avg' => $this->request->getPost('consumption_avg'),
            'tariff' => $this->request->getPost('tariff'),
            'losses' => $this->request->getPost('losses'),
            'degradation' => $this->request->getPost('degradation'),
            'law_14300_json' => $this->request->getPost('law_14300_json'),
        );

        $result = $this->service->preview($input);
        echo json_encode($result);
    }
}
