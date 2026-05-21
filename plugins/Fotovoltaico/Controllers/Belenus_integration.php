<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\BelenusApiService;
use Fotovoltaico\Services\BelenusImportService;

class Belenus_integration extends Security_Controller
{
    private $Api_service;
    private $Import_service;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canViewBelenus($this->login_user) && !Plugin::canManageBelenus($this->login_user)) {
            app_redirect('forbidden');
        }

        Plugin::ensureSchema();

        $this->Api_service = new BelenusApiService();
        $this->Import_service = new BelenusImportService();
    }

    public function settings()
    {
        return $this->template->rander('Fotovoltaico\\Views\\belenus\\settings', array(
            'configuration' => $this->Api_service->getConfiguration(),
            'can_manage_belenus' => Plugin::canManageBelenus($this->login_user),
            'can_manage_products' => Plugin::canManageProducts($this->login_user),
            'can_manage_kits' => Plugin::canManageKits($this->login_user),
        ));
    }

    public function products()
    {
        return $this->template->rander('Fotovoltaico\\Views\\belenus\\products', array(
            'configuration' => $this->Api_service->getConfiguration(),
            'can_manage_belenus' => Plugin::canManageBelenus($this->login_user),
            'can_manage_products' => Plugin::canManageProducts($this->login_user),
        ));
    }

    public function kits()
    {
        return $this->template->rander('Fotovoltaico\\Views\\belenus\\kits', array(
            'configuration' => $this->Api_service->getConfiguration(),
            'can_manage_belenus' => Plugin::canManageBelenus($this->login_user),
            'can_manage_kits' => Plugin::canManageKits($this->login_user),
        ));
    }

    public function import_logs()
    {
        return $this->template->rander('Fotovoltaico\\Views\\belenus\\import_logs', array(
            'configuration' => $this->Api_service->getConfiguration(),
            'can_view_belenus' => Plugin::canViewBelenus($this->login_user),
            'can_manage_belenus' => Plugin::canManageBelenus($this->login_user),
        ));
    }
}
