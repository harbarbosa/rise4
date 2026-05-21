<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\EnergyTariffApiService;

class Api_energy extends Security_Controller
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        Plugin::ensureSchema();

        $this->service = new EnergyTariffApiService();
    }

    public function status()
    {
        $this->_authorize_read();
        $this->_respond($this->service->getApiStatus(array(
            'created_by' => $this->login_user->id,
        )));
    }

    public function flag_current()
    {
        $this->_authorize_read();
        $this->_respond($this->service->getCurrentFlag(array(
            'created_by' => $this->login_user->id,
        )));
    }

    public function distributors()
    {
        $this->_authorize_read();
        $result = $this->service->getDistributorsCache(array(
            'created_by' => $this->login_user->id,
        ));
        $this->_respond_or_select2($result);
    }

    public function distributors_selectable()
    {
        $this->_authorize_read();
        $result = $this->service->getSelectableDistributors(array(
            'created_by' => $this->login_user->id,
        ));
        $this->_respond_or_select2($result);
    }

    public function distributors_slugs()
    {
        $this->_authorize_read();
        $this->_respond($this->service->getDistributorSlugs(array(
            'created_by' => $this->login_user->id,
        )));
    }

    public function distributors_search()
    {
        $this->_authorize_read();
        $term = trim((string) $this->request->getGet('term'));
        $result = $this->service->searchDistributorsByName($term, array(
            'created_by' => $this->login_user->id,
        ));
        $this->_respond_or_select2($result);
    }

    public function distributors_by_uf($uf = '')
    {
        $this->_authorize_read();
        $result = $this->service->getDistributorsByUf($uf, array(
            'created_by' => $this->login_user->id,
        ));
        $this->_respond_or_select2($result);
    }

    public function projection()
    {
        $this->_authorize_read();
        $consumo_kwh = (float) $this->request->getPost('consumo_kwh');
        $slug = trim((string) $this->request->getPost('distributor_slug'));
        if ($consumo_kwh <= 0 || $slug === '') {
            $this->_respond(array(
                'success' => false,
                'message' => 'Invalid parameters',
                'data' => array(),
                'errors' => array('consumo_kwh and distributor_slug are required'),
            ));
            return;
        }

        $this->_respond($this->service->getCostProjection($consumo_kwh, $slug, array(
            'created_by' => $this->login_user->id,
        )));
    }

    public function sync_distributors()
    {
        $this->_authorize_manage();
        $this->_respond($this->service->syncExternalDistributorsToLocal(array(
            'created_by' => $this->login_user->id,
        )));
    }

    public function reload_cache()
    {
        $this->_authorize_manage();
        $this->_respond($this->service->reloadExternalCache(array(
            'created_by' => $this->login_user->id,
        )));
    }

    private function _authorize_read()
    {
        if (
            !Plugin::canViewIntegrations($this->login_user) &&
            !Plugin::canManageIntegrations($this->login_user) &&
            !Plugin::canViewTariffs($this->login_user) &&
            !Plugin::canManageProposals($this->login_user) &&
            !Plugin::canCreateProposals($this->login_user)
        ) {
            app_redirect('forbidden');
        }
    }

    private function _authorize_manage()
    {
        if (!Plugin::canManageIntegrations($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _respond_or_select2($result)
    {
        $select2 = (int) $this->request->getGet('select2');
        if ($select2 === 1 && get_array_value($result, 'success')) {
            $data = get_array_value($result, 'data');
            $items = array_map(function ($item) {
                if (array_key_exists('text', $item)) {
                    return $item;
                }

                return array(
                    'id' => get_array_value($item, 'external_slug') ?: get_array_value($item, 'id'),
                    'text' => trim((string) get_array_value($item, 'name') . ' - ' . get_array_value($item, 'uf'), ' -'),
                );
            }, is_array($data) ? $data : array());

            echo json_encode($items);
            return;
        }

        $this->_respond($result);
    }

    private function _respond($result)
    {
        echo json_encode(array(
            'success' => (bool) get_array_value($result, 'success'),
            'message' => get_array_value($result, 'message') ?: 'OK',
            'data' => get_array_value($result, 'data') ?: array(),
            'errors' => get_array_value($result, 'errors') ?: array(),
            'meta' => get_array_value($result, 'meta') ?: array(),
        ));
    }
}
