<?php

namespace Fotovoltaico\Services;

use Fotovoltaico\Models\Distributors_model;
use Fotovoltaico\Models\External_cache_model;
use Fotovoltaico\Models\Integration_logs_model;
use Fotovoltaico\Models\Settings_model;
use Fotovoltaico\Models\Tariffs_model;
use Fotovoltaico\Services\Providers\AneelCkanProvider;

class EnergyTariffApiService
{
    private $Settings_model;
    private $Integration_logs_model;
    private $Distributors_model;
    private $Tariffs_model;
    private $External_cache_model;
    private $provider;

    public function __construct()
    {
        $this->Settings_model = model(Settings_model::class);
        $this->Integration_logs_model = model(Integration_logs_model::class);
        $this->Distributors_model = model(Distributors_model::class);
        $this->Tariffs_model = model(Tariffs_model::class);
        $this->External_cache_model = model(External_cache_model::class);
        $this->provider = new AneelCkanProvider();
    }

    public function get_configuration()
    {
        $config = $this->Settings_model->get_setting('energy_tariff_api_config_json');
        $config = $this->_decode_json($config);
        return $this->_normalize_configuration($config);
    }

    public function save_configuration($config = array())
    {
        $config = $this->_normalize_configuration($config);
        return $this->Settings_model->save_setting(
            'energy_tariff_api_config_json',
            json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'app'
        );
    }

    public function getApiStatus($options = array())
    {
        return $this->_call_endpoint('status', 'getApiStatus', array(), $options);
    }

    public function getCurrentFlag($options = array())
    {
        $result = $this->_call_endpoint('flag/current', 'getCurrentFlag', array(), $options);
        if (get_array_value($result, 'success')) {
            $result['data'] = $this->normalizeFlagPayload(get_array_value($result, 'data'));
        }
        return $result;
    }

    public function getDistributorsCache($options = array())
    {
        $result = $this->_call_endpoint('distributors', 'getDistributorsCache', array(), $options);
        if (get_array_value($result, 'success')) {
            $result['data'] = $this->_normalize_distributors_collection(get_array_value($result, 'data'));
        }
        return $result;
    }

    public function getDistributorSlugs($options = array())
    {
        return $this->_call_endpoint('distributors/slugs', 'getDistributorSlugs', array(), $options);
    }

    public function getSelectableDistributors($options = array())
    {
        $result = $this->_call_endpoint('distributors/selectable', 'getSelectableDistributors', array(), $options);
        if (get_array_value($result, 'success')) {
            $items = $this->_normalize_distributors_collection(get_array_value($result, 'data'));
            $result['data'] = array_map(function ($item) {
                return array(
                    'id' => get_array_value($item, 'external_slug') ?: get_array_value($item, 'id'),
                    'text' => trim((string) get_array_value($item, 'name') . ' - ' . get_array_value($item, 'uf'), ' -'),
                );
            }, $items);
        }
        return $result;
    }

    public function searchDistributorsByName($name, $options = array())
    {
        $name = trim((string) $name);
        $result = $this->_call_endpoint('distributors/search', 'searchDistributorsByName', array($name), $options);
        if (get_array_value($result, 'success')) {
            $result['data'] = $this->_normalize_distributors_collection(get_array_value($result, 'data'));
        }
        return $result;
    }

    public function getDistributorsByUf($uf, $options = array())
    {
        $uf = strtoupper(trim((string) $uf));
        $result = $this->_call_endpoint('distributors/by-uf', 'getDistributorsByUf', array($uf), $options);
        if (get_array_value($result, 'success')) {
            $result['data'] = $this->_normalize_distributors_collection(get_array_value($result, 'data'));
        }
        return $result;
    }

    public function getCostProjection($consumo_kwh, $slug, $options = array())
    {
        $consumo_kwh = (float) $consumo_kwh;
        $slug = trim((string) $slug);
        $result = $this->_call_endpoint('projection', 'getCostProjection', array($consumo_kwh, $slug), $options);
        if (get_array_value($result, 'success')) {
            $result['data'] = $this->normalizeProjectionPayload(get_array_value($result, 'data'));
        }
        return $result;
    }

    public function reloadExternalCache($options = array())
    {
        $this->External_cache_model->invalidate_by_prefix($this->provider->getKey());
        return $this->_call_endpoint('cache/reload', 'reloadExternalCache', array(), array_merge($options, array(
            'disable_cache' => true,
        )));
    }

    public function syncExternalDistributorsToLocal($options = array())
    {
        if (method_exists($this->provider, 'getUniqueDistributors')) {
            $config = $this->get_configuration();
            $result = $this->provider->getUniqueDistributors($config);
        } else {
            $result = $this->getDistributorsCache(array_merge($options, array(
                'disable_cache' => true,
                'fallback_to_local' => false,
            )));
        }
        if (!get_array_value($result, 'success')) {
            return $result;
        }

        $data = get_array_value($result, 'data');
        if (!is_array($data) || !count($data)) {
            $data = get_array_value($result, 'payload');
        }

        return $this->_sync_normalized_distributors($this->_normalize_distributors_collection($data), (int) get_array_value($options, 'created_by'));
    }

    public function findBestLocalDistributorMatch($externalItem)
    {
        $slug = trim((string) get_array_value($externalItem, 'external_slug'));
        if ($slug !== '') {
            $by_slug = $this->Distributors_model->get_by_external_slug($slug);
            if ($by_slug && $by_slug->id) {
                return $by_slug;
            }
        }

        $name = trim((string) get_array_value($externalItem, 'name'));
        $uf = strtoupper(trim((string) get_array_value($externalItem, 'uf')));
        if ($name !== '') {
            return $this->Distributors_model->find_by_title_and_uf($name, $uf);
        }

        return null;
    }

    public function normalizeDistributorPayload($payload)
    {
        $name = trim((string) (
            get_array_value($payload, 'nome')
            ?: get_array_value($payload, 'name')
            ?: get_array_value($payload, 'distribuidora')
            ?: get_array_value($payload, 'title')
            ?: get_array_value($payload, 'SigAgente')
        ));
        $slug = trim((string) (
            get_array_value($payload, 'slug')
            ?: get_array_value($payload, 'external_slug')
            ?: $this->_slugify($name)
        ));
        $uf = strtoupper(trim((string) (
            get_array_value($payload, 'uf')
            ?: get_array_value($payload, 'estado')
            ?: get_array_value($payload, 'state_code')
            ?: get_array_value($payload, 'SigUF')
        )));

        $local_match = $this->findBestLocalDistributorMatch(array(
            'name' => $name,
            'uf' => $uf,
            'external_slug' => $slug,
        ));

        return array(
            'id' => $local_match && $local_match->id ? (int) $local_match->id : null,
            'external_slug' => $slug,
            'name' => $name,
            'uf' => $uf,
            'source' => $local_match && $local_match->id ? 'local' : 'external',
            'is_synced' => $local_match && $local_match->id ? 1 : 0,
            'raw_payload' => $payload,
        );
    }

    public function normalizeProjectionPayload($payload)
    {
        $estimated_cost = get_array_value($payload, 'estimated_cost');
        if ($estimated_cost === null || $estimated_cost === '') {
            $estimated_cost = get_array_value($payload, 'valor_estimado');
        }
        if ($estimated_cost === null || $estimated_cost === '') {
            $estimated_cost = get_array_value($payload, 'custo_total');
        }

        return array(
            'consumo_kwh' => (float) (
                get_array_value($payload, 'consumo_kwh')
                ?: get_array_value($payload, 'consumption_kwh')
                ?: 0
            ),
            'distributor_slug' => trim((string) (
                get_array_value($payload, 'distribuidora_slug')
                ?: get_array_value($payload, 'distributor_slug')
                ?: ''
            )),
            'estimated_cost' => (float) $estimated_cost,
            'flag_applied' => trim((string) (
                get_array_value($payload, 'flag_applied')
                ?: get_array_value($payload, 'bandeira')
                ?: ''
            )),
            'taxes_applied' => get_array_value($payload, 'taxes_applied') ?: get_array_value($payload, 'impostos') ?: null,
            'source' => trim((string) (get_array_value($payload, 'source') ?: 'external')),
            'raw_payload' => $payload,
        );
    }

    public function normalizeFlagPayload($payload)
    {
        return array(
            'flag_name' => trim((string) (
                get_array_value($payload, 'flag_name')
                ?: get_array_value($payload, 'bandeira')
                ?: get_array_value($payload, 'nome')
                ?: ''
            )),
            'flag_value' => (float) (
                get_array_value($payload, 'flag_value')
                ?: get_array_value($payload, 'valor')
                ?: 0
            ),
            'reference_date' => trim((string) (
                get_array_value($payload, 'reference_date')
                ?: get_array_value($payload, 'data_referencia')
                ?: date('Y-m-d')
            )),
            'source' => trim((string) (get_array_value($payload, 'source') ?: 'external')),
            'raw_payload' => $payload,
        );
    }

    private function _call_endpoint($cache_namespace, $provider_method, $arguments = array(), $options = array())
    {
        $config = $this->get_configuration();
        $created_by = (int) get_array_value($options, 'created_by');
        $fallback_enabled = array_key_exists('fallback_to_local', $options)
            ? (bool) get_array_value($options, 'fallback_to_local')
            : (bool) get_array_value($config, 'external_api_fallback_to_local');

        if (!get_array_value($config, 'external_api_enabled')) {
            return $fallback_enabled
                ? $this->_fallback_result($provider_method, $arguments)
                : $this->_result(false, 'External API disabled', array(), array('external_api_disabled'));
        }

        $cache_key = $this->_build_cache_key($cache_namespace, $arguments);
        $strategy = (string) get_array_value($config, 'external_api_sync_strategy');
        $use_cache = !$options || !get_array_value($options, 'disable_cache');
        $use_cache = $use_cache && in_array($strategy, array('cache_local', 'sync_local'), true);

        if ($use_cache) {
            $cached_row = $this->External_cache_model->get_valid_cache($cache_key);
            if ($cached_row && $cached_row->id) {
                $payload = $this->_decode_json($cached_row->payload_json);
                $this->_register_log(array(
                    'provider' => $this->provider->getKey(),
                    'endpoint' => 'cache:' . $cache_namespace,
                    'method' => 'CACHE',
                    'request_json' => $this->_safe_json(array('args' => $arguments)),
                    'response_json' => $this->_safe_json($payload),
                    'http_status' => 200,
                    'latency_ms' => 0,
                    'cache_hit' => 1,
                    'success' => 1,
                    'created_by' => $created_by,
                ));
                return $this->_result(true, 'OK', get_array_value($payload, 'data'), array(), array(
                    'cache_hit' => true,
                    'source' => get_array_value($payload, 'source') ?: 'external_cache',
                ));
            }
        }

        $provider_result = call_user_func_array(array($this->provider, $provider_method), array_merge($arguments, array($config)));
        $external_data = $this->_extract_provider_data($provider_result);

        $this->_register_log(array(
            'provider' => $this->provider->getKey(),
            'endpoint' => get_array_value($provider_result, 'url') ?: $cache_namespace,
            'method' => get_array_value($provider_result, 'method') ?: 'GET',
            'request_json' => $this->_safe_json(get_array_value($provider_result, 'request_payload')),
            'response_json' => $this->_safe_json($external_data),
            'http_status' => (int) get_array_value($provider_result, 'http_status'),
            'latency_ms' => (int) get_array_value($provider_result, 'latency_ms'),
            'cache_hit' => 0,
            'success' => get_array_value($provider_result, 'success') ? 1 : 0,
            'error_message' => get_array_value($provider_result, 'error_message') ?: get_array_value($provider_result, 'message'),
            'created_by' => $created_by,
        ));

        if (get_array_value($provider_result, 'success')) {
            $response = $this->_result(true, 'OK', $external_data, array(), array(
                'cache_hit' => false,
                'source' => 'external',
            ));

            if ($use_cache) {
                $ttl = (int) get_array_value($config, 'external_api_cache_ttl');
                if ($ttl <= 0) {
                    $ttl = 3600;
                }
                $cache_payload = array(
                    'data' => $external_data,
                    'source' => 'external_cache',
                );
                $this->External_cache_model->put_cache(array(
                    'provider' => $this->provider->getKey(),
                    'cache_key' => $cache_key,
                    'endpoint' => $cache_namespace,
                    'request_hash' => hash('sha256', json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                    'payload_json' => json_encode($cache_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
                    'created_by' => $created_by,
                ));
            }

            if ($strategy === 'sync_local' && in_array($provider_method, array('getDistributorsCache', 'searchDistributorsByName', 'getDistributorsByUf'), true)) {
                $this->_sync_normalized_distributors($this->_normalize_distributors_collection($external_data), $created_by);
            }

            return $response;
        }

        if ($fallback_enabled) {
            $fallback = $this->_fallback_result($provider_method, $arguments);
            $fallback['errors'][] = get_array_value($provider_result, 'message') ?: 'External provider unavailable';
            return $fallback;
        }

        $provider_message = trim((string) get_array_value($provider_result, 'message'));
        if ($provider_message === '') {
            $http_status = (int) get_array_value($provider_result, 'http_status');
            $provider_message = $http_status ? 'External provider unavailable (HTTP ' . $http_status . ')' : 'External provider unavailable';
        }

        return $this->_result(false, $provider_message, array(), array(
            $provider_message,
        ));
    }

    private function _fallback_result($provider_method, $arguments = array())
    {
        if ($provider_method === 'getDistributorsCache') {
            return $this->_result(true, 'Fallback to local distributors', $this->_local_distributors(array()), array(), array(
                'source' => 'local',
            ));
        }

        if ($provider_method === 'searchDistributorsByName') {
            return $this->_result(true, 'Fallback to local distributors', $this->_local_distributors(array(
                'search' => get_array_value($arguments, 0),
            )), array(), array(
                'source' => 'local',
            ));
        }

        if ($provider_method === 'getDistributorsByUf') {
            return $this->_result(true, 'Fallback to local distributors', $this->_local_distributors(array(
                'state_code' => get_array_value($arguments, 0),
            )), array(), array(
                'source' => 'local',
            ));
        }

        if ($provider_method === 'getCurrentFlag') {
            $tariff = $this->Tariffs_model->get_latest_current_flag();
            $data = $tariff ? array(
                'flag_name' => $tariff->flag_name ?: '',
                'flag_value' => (float) ($tariff->flag_value ?? 0),
                'reference_date' => date('Y-m-d'),
                'source' => 'local',
                'raw_payload' => array(
                    'tariff_id' => (int) $tariff->id,
                    'distributor_id' => (int) $tariff->distributor_id,
                ),
            ) : array();
            return $this->_result((bool) $tariff, $tariff ? 'Fallback to local tariff' : 'No local tariff found', $data, $tariff ? array() : array('No local tariff found'), array(
                'source' => 'local',
            ));
        }

        if ($provider_method === 'getCostProjection') {
            $projection = $this->_local_projection((float) get_array_value($arguments, 0), (string) get_array_value($arguments, 1));
            return $this->_result((bool) get_array_value($projection, 'estimated_cost'), 'Fallback to local projection', $projection, array(), array(
                'source' => 'local',
            ));
        }

        if ($provider_method === 'getApiStatus') {
            return $this->_result(true, 'External API unavailable, local fallback enabled', array(
                'status' => 'fallback',
                'source' => 'local',
            ));
        }

        if ($provider_method === 'getDistributorSlugs') {
            $items = $this->_local_distributors(array());
            $slugs = array_values(array_filter(array_map(function ($item) {
                return get_array_value($item, 'external_slug');
            }, $items)));
            return $this->_result(true, 'Fallback to local slugs', $slugs, array(), array('source' => 'local'));
        }

        if ($provider_method === 'getSelectableDistributors') {
            $items = array_map(function ($item) {
                return array(
                    'id' => get_array_value($item, 'external_slug') ?: get_array_value($item, 'id'),
                    'text' => trim((string) get_array_value($item, 'name') . ' - ' . get_array_value($item, 'uf'), ' -'),
                );
            }, $this->_local_distributors(array()));
            return $this->_result(true, 'Fallback to local selectable distributors', $items, array(), array('source' => 'local'));
        }

        return $this->_result(false, 'No fallback available', array(), array('No fallback available'));
    }

    private function _local_distributors($filters = array())
    {
        $rows = $this->Distributors_model->get_details(array_merge(array(
            'active_only' => 1,
        ), $filters))->getResult();

        return array_map(function ($row) {
            return array(
                'id' => (int) $row->id,
                'external_slug' => $row->external_slug ?: '',
                'name' => $row->title ?: '',
                'uf' => $row->state_code ?: '',
                'source' => 'local',
                'is_synced' => (int) ($row->is_synced ?? 0),
                'raw_payload' => $this->_decode_json($row->raw_payload ?? ''),
            );
        }, $rows);
    }

    private function _local_projection($consumo_kwh, $slug)
    {
        $distributor = $this->Distributors_model->get_by_external_slug($slug);
        if (!$distributor || !$distributor->id) {
            $normalized_slug = str_replace('-', ' ', strtolower($slug));
            $matches = $this->Distributors_model->get_details(array(
                'search' => $normalized_slug,
                'active_only' => 1,
            ))->getResult();
            $distributor = count($matches) ? $matches[0] : null;
        }

        if (!$distributor || !$distributor->id) {
            return array(
                'consumo_kwh' => (float) $consumo_kwh,
                'distributor_slug' => $slug,
                'estimated_cost' => 0,
                'flag_applied' => '',
                'taxes_applied' => null,
                'source' => 'local',
                'raw_payload' => array(),
            );
        }

        $tariff = $this->Tariffs_model->get_current_tariff((int) $distributor->id);
        $unit_price = $tariff ? ((float) $tariff->te + (float) $tariff->tusd + (float) $tariff->flag_value) : 0;

        return array(
            'consumo_kwh' => (float) $consumo_kwh,
            'distributor_slug' => $slug,
            'estimated_cost' => round($consumo_kwh * $unit_price, 2),
            'flag_applied' => $tariff ? ($tariff->flag_name ?: '') : '',
            'taxes_applied' => null,
            'source' => 'local',
            'raw_payload' => array(
                'distributor_id' => (int) $distributor->id,
                'tariff_id' => $tariff ? (int) $tariff->id : 0,
                'unit_price' => $unit_price,
            ),
        );
    }

    private function _normalize_distributors_collection($data)
    {
        $items = $this->_extract_list($data);
        $result = array();
        $seen = array();

        foreach ($items as $item) {
            $normalized = $this->normalizeDistributorPayload($item);
            $name = trim((string) get_array_value($normalized, 'name'));
            if ($name === '') {
                continue;
            }

            $key = trim((string) get_array_value($normalized, 'external_slug'));
            if ($key === '') {
                $key = strtolower($name) . '|' . strtolower((string) get_array_value($normalized, 'uf'));
            }

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $normalized;
        }

        return $result;
    }

    private function _sync_normalized_distributors($items = array(), $created_by = 0)
    {
        $synced = 0;
        $updated = 0;

        foreach ((array) $items as $item) {
            $match = $this->findBestLocalDistributorMatch($item);
            $payload = array(
                'title' => get_array_value($item, 'name'),
                'acronym' => $this->_build_acronym(get_array_value($item, 'name')),
                'state_code' => strtoupper((string) get_array_value($item, 'uf')),
                'external_slug' => get_array_value($item, 'external_slug'),
                'source' => 'external',
                'is_synced' => 1,
                'raw_payload' => json_encode(get_array_value($item, 'raw_payload'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'active' => 1,
                'updated_at' => get_my_local_time(),
            );

            if (!$match || !$match->id) {
                $payload['created_by'] = $created_by ?: null;
                $payload['created_at'] = get_my_local_time();
                $this->Distributors_model->ci_save($payload);
                $synced++;
            } else {
                $this->Distributors_model->ci_save($payload, $match->id);
                $updated++;
            }
        }

        return array(
            'success' => true,
            'message' => 'Synchronization completed',
            'data' => array(
                'processed' => count((array) $items),
                'created' => $synced,
                'updated' => $updated,
            ),
            'errors' => array(),
        );
    }

    private function _extract_provider_data($provider_result)
    {
        $payload = get_array_value($provider_result, 'payload');
        if (is_array($payload) && array_key_exists('data', $payload)) {
            return get_array_value($payload, 'data');
        }

        return $payload;
    }

    private function _extract_list($data)
    {
        if (!is_array($data)) {
            return array();
        }

        if ($this->_is_list_array($data)) {
            return $data;
        }

        foreach (array('data', 'items', 'results', 'distribuidoras', 'lista') as $key) {
            $items = get_array_value($data, $key);
            if (is_array($items)) {
                return $items;
            }
        }

        return count($data) ? array($data) : array();
    }

    private function _build_cache_key($namespace, $arguments)
    {
        return 'energy_api:' . $namespace . ':' . hash('sha256', json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function _is_list_array($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $expected = 0;
        foreach (array_keys($data) as $key) {
            if ($key !== $expected) {
                return false;
            }
            $expected++;
        }

        return true;
    }

    private function _safe_json($payload)
    {
        return json_encode($this->_mask_secrets($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function _mask_secrets($payload)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $secret_keys = array('token', 'secret', 'password', 'api_key', 'apikey', 'authorization', 'client_secret');
        $result = array();
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->_mask_secrets($value);
                continue;
            }

            if (in_array(strtolower((string) $key), $secret_keys, true)) {
                $result[$key] = '***';
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function _register_log($data = array())
    {
        return $this->Integration_logs_model->register_log(array(
            'provider' => get_array_value($data, 'provider'),
            'endpoint' => get_array_value($data, 'endpoint'),
            'method' => get_array_value($data, 'method'),
            'request_json' => get_array_value($data, 'request_json'),
            'response_json' => get_array_value($data, 'response_json'),
            'http_status' => get_array_value($data, 'http_status'),
            'latency_ms' => get_array_value($data, 'latency_ms'),
            'cache_hit' => get_array_value($data, 'cache_hit'),
            'success' => get_array_value($data, 'success'),
            'error_message' => get_array_value($data, 'error_message'),
            'created_by' => get_array_value($data, 'created_by'),
        ));
    }

    private function _normalize_configuration($config)
    {
        if (!is_array($config)) {
            $config = array();
        }

        $defaults = array(
            'external_api_enabled' => 1,
            'external_api_fallback_to_local' => 1,
            'external_api_base_url' => 'https://dadosabertos.aneel.gov.br',
            'external_api_timeout' => 30,
            'external_api_cache_ttl' => 86400,
            'external_api_sync_strategy' => 'cache_local',
        );

        $config = array_merge($defaults, $config);
        $config['external_api_enabled'] = (int) !!$config['external_api_enabled'];
        $config['external_api_fallback_to_local'] = (int) !!$config['external_api_fallback_to_local'];
        $config['external_api_base_url'] = rtrim(trim((string) $config['external_api_base_url']), '/');
        $config['external_api_timeout'] = max(3, (int) $config['external_api_timeout']);
        $config['external_api_cache_ttl'] = max(60, (int) $config['external_api_cache_ttl']);

        if (!in_array($config['external_api_sync_strategy'], array('live_only', 'cache_local', 'sync_local'), true)) {
            $config['external_api_sync_strategy'] = 'cache_local';
        }

        return $config;
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

    private function _build_acronym($name = '')
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name);
        $acronym = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $acronym .= strtoupper(substr($part, 0, 1));
            if (strlen($acronym) >= 6) {
                break;
            }
        }

        return $acronym;
    }

    private function _result($success, $message, $data = array(), $errors = array(), $meta = array())
    {
        return array(
            'success' => (bool) $success,
            'message' => $message,
            'data' => $data,
            'errors' => array_values(array_filter($errors)),
            'meta' => $meta,
        );
    }

    private function _slugify($text = '')
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($normalized === false) {
            $normalized = $text;
        }

        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized);
        return trim((string) $normalized, '-');
    }
}
