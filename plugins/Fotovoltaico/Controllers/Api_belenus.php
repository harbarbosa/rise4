<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;
use Fotovoltaico\Services\BelenusApiService;
use Fotovoltaico\Services\BelenusImportService;

class Api_belenus extends Security_Controller
{
    private $Api_service;
    private $Import_service;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        Plugin::ensureSchema();

        $this->Api_service = new BelenusApiService();
        $this->Import_service = new BelenusImportService();
    }

    public function save_settings()
    {
        $this->authorizeManage();
        $config = $this->readConfigFromPost();
        $current = $this->Api_service->getConfiguration();
        if (trim((string) get_array_value($config, 'api_password')) === '' && trim((string) get_array_value($current, 'api_password')) !== '') {
            $config['api_password'] = get_array_value($current, 'api_password');
        }
        $saved = $this->Api_service->saveConfiguration($config);
        echo json_encode(array(
            'success' => (bool) $saved,
            'message' => $saved ? app_lang('record_saved') : app_lang('error_occurred'),
        ));
    }

    public function test_connection()
    {
        $this->authorizeManage();
        echo json_encode($this->Api_service->testConnection());
    }

    public function products_search()
    {
        $this->authorizeReadProducts();
        echo json_encode($this->Api_service->getProducts($this->readFilters()));
    }

    public function products_import()
    {
        $this->authorizeManageProducts();
        $ids = $this->readIds();
        echo json_encode($this->Import_service->importProducts($ids));
    }

    public function products_sync()
    {
        $this->authorizeManageProducts();
        echo json_encode($this->Import_service->syncProducts($this->readFilters()));
    }

    public function products_update_price()
    {
        $this->authorizeManageProducts();
        $localId = (int) $this->request->getPost('local_id');
        echo json_encode($this->Import_service->updateProductPrice($localId));
    }

    public function products_update_prices_batch()
    {
        $this->authorizeManageProducts();
        $ids = $this->readIds('local_ids');
        echo json_encode($this->Import_service->updateProductPricesBatch($ids));
    }

    public function kits_search()
    {
        $this->authorizeReadKits();
        echo json_encode($this->Api_service->getKits($this->readFilters()));
    }

    public function kits_import()
    {
        $this->authorizeManageKits();
        $ids = $this->readIds();
        echo json_encode($this->Import_service->importKits($ids));
    }

    public function kits_sync()
    {
        $this->authorizeManageKits();
        echo json_encode($this->Import_service->syncKits($this->readFilters()));
    }

    public function kits_update_price()
    {
        $this->authorizeManageKits();
        $localId = (int) $this->request->getPost('local_id');
        echo json_encode($this->Import_service->updateKitPrice($localId));
    }

    public function logs_list_data()
    {
        $this->authorizeRead();
        $logs = model('Fotovoltaico\\Models\\Belenus_import_logs_model')->get_details(array(
            'provider' => 'belenus',
            'entity_type' => trim((string) $this->request->getPost('entity_type')),
        ))->getResult();

        $rows = array();
        foreach ($logs as $log) {
            $rows[] = array(
                esc($log->created_at ?: ''),
                esc($log->entity_type ?: ''),
                esc($log->action ?: ''),
                esc($log->external_id ?: '-'),
                esc($log->local_id ?: '-'),
                esc($log->status ?: '-'),
                esc($log->message ?: '-'),
            );
        }

        echo json_encode(array('data' => $rows));
    }

    public function cache_clear()
    {
        $this->authorizeManage();
        echo json_encode($this->Api_service->clearCache());
    }

    private function authorizeRead()
    {
        if (!Plugin::canViewBelenus($this->login_user) && !Plugin::canManageBelenus($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function authorizeManage()
    {
        if (!Plugin::canManageBelenus($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function authorizeReadProducts()
    {
        if (!Plugin::canViewBelenus($this->login_user) && !Plugin::canManageBelenus($this->login_user) && !Plugin::canViewProducts($this->login_user) && !Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function authorizeManageProducts()
    {
        if (!Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function authorizeReadKits()
    {
        if (!Plugin::canViewBelenus($this->login_user) && !Plugin::canManageBelenus($this->login_user) && !Plugin::canViewKits($this->login_user) && !Plugin::canManageKits($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function authorizeManageKits()
    {
        if (!Plugin::canManageKits($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function readConfigFromPost()
    {
        return array(
            'base_url' => trim((string) $this->request->getPost('base_url')),
            'api_email' => trim((string) $this->request->getPost('api_email')),
            'api_password' => trim((string) $this->request->getPost('api_password')),
            'token_ttl_seconds' => (int) $this->request->getPost('token_ttl_seconds'),
            'products_cache_ttl_seconds' => (int) $this->request->getPost('products_cache_ttl_seconds'),
            'price_cache_ttl_seconds' => (int) $this->request->getPost('price_cache_ttl_seconds'),
            'kits_cache_ttl_seconds' => (int) $this->request->getPost('kits_cache_ttl_seconds'),
            'timeout_seconds' => (int) $this->request->getPost('timeout_seconds'),
            'active' => $this->request->getPost('active') ? 1 : 0,
        );
    }

    private function readFilters()
    {
        return array(
            'nome' => trim((string) $this->request->getPost('nome')),
            'codigo' => trim((string) $this->request->getPost('codigo')),
            'categoria' => trim((string) $this->request->getPost('categoria')),
            'fabricante' => trim((string) $this->request->getPost('fabricante')),
            'ativo' => trim((string) $this->request->getPost('ativo')),
            'q' => trim((string) $this->request->getPost('q')),
            'page' => (int) $this->request->getPost('page'),
            'pageSize' => (int) $this->request->getPost('pageSize'),
            'limit' => (int) $this->request->getPost('limit'),
        );
    }

    private function readIds($field = 'ids')
    {
        $ids = $this->request->getPost($field);
        if (is_string($ids)) {
            $ids = preg_split('/[,\s]+/', $ids);
        }

        return array_values(array_filter(array_map('intval', (array) $ids)));
    }
}
