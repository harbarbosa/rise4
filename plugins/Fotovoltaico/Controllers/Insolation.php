<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\InsolationService;

class Insolation extends Security_Controller
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canManageProposals($this->login_user) && !Plugin::canViewIntegrations($this->login_user)) {
            app_redirect('forbidden');
        }

        $this->service = new InsolationService();
    }

    public function get_data()
    {
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $manual_value = $this->request->getPost('manual_value');

        $result = $this->service->get_data($latitude, $longitude, array(
            'manual_value' => $manual_value,
            'created_by' => $this->login_user->id,
        ));

        echo json_encode($result);
    }
}
