<?php

namespace travelrefunds\Controllers;

use App\Controllers\App_Controller;

class Cities extends App_Controller
{
    protected const CACHE_TTL = 604800;

    public function index()
    {
        $filters = $this->request->getGet();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = (int) ($filters['limit'] ?? 25);
        if ($limit < 1) {
            $limit = 25;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $cities = $this->getBrazilCities();
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $normalizedSearch = $this->normalizeSearchText($search);
            $cities = array_values(array_filter($cities, function (array $city) use ($normalizedSearch) {
                return strpos($this->normalizeSearchText($city['text']), $normalizedSearch) !== false
                    || strpos($this->normalizeSearchText($city['city']), $normalizedSearch) !== false
                    || strpos($this->normalizeSearchText($city['state']), $normalizedSearch) !== false;
            }));
        }

        $total = count($cities);
        $offset = ($page - 1) * $limit;
        $rows = array_slice($cities, $offset, $limit);

        return $this->response->setJSON(array(
            'status' => true,
            'results' => $rows,
            'pagination' => array(
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'more' => ($offset + $limit) < $total,
            ),
        ));
    }

    protected function getBrazilCities(): array
    {
        $cacheFile = WRITEPATH . 'cache/travelrefunds_brazil_cities.json';
        $cachedCities = $this->readCitiesCache($cacheFile);
        if ($cachedCities) {
            return $cachedCities;
        }

        $staleCachedCities = $this->readCitiesCache($cacheFile, false);
        $cities = $this->fetchBrazilCitiesFromIbge();
        if ($cities) {
            $this->writeCitiesCache($cacheFile, $cities);
            return $cities;
        }

        $localFallback = $this->buildLocalCityFallback();
        if ($localFallback) {
            return $localFallback;
        }

        return $staleCachedCities ?: array();
    }

    protected function fetchBrazilCitiesFromIbge(): array
    {
        $response = $this->requestJson('https://servicodados.ibge.gov.br/api/v1/localidades/municipios?orderBy=nome');
        if (!is_array($response) || !$response) {
            return array();
        }

        $cities = array();
        $seen = array();

        foreach ($response as $item) {
            if (!is_array($item)) {
                continue;
            }

            $city = trim((string) ($item['nome'] ?? ''));
            if ($city === '') {
                continue;
            }

            $state = trim((string) ($item['microrregiao']['mesorregiao']['UF']['sigla'] ?? ''));
            $label = $city . ($state ? ' - ' . $state : '');
            $key = strtolower($this->normalizeSearchText($label));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $cities[] = array(
                'id' => $label,
                'text' => $label,
                'city' => $city,
                'state' => $state,
                'ibge_id' => (string) ($item['id'] ?? ''),
            );
        }

        return $cities;
    }

    protected function requestJson(string $url): array
    {
        $body = $this->requestUrl($url);
        if ($body === '') {
            return array();
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : array();
    }

    protected function requestUrl(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => array('Accept: application/json'),
            ));

            $body = curl_exec($ch);
            curl_close($ch);

            return is_string($body) ? $body : '';
        }

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => 15,
            ),
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
            ),
        ));

        $body = @file_get_contents($url, false, $context);
        return is_string($body) ? $body : '';
    }

    protected function readCitiesCache(string $cacheFile, bool $respectTtl = true): array
    {
        if (!is_file($cacheFile)) {
            return array();
        }

        if ($respectTtl && (time() - filemtime($cacheFile)) > self::CACHE_TTL) {
            return array();
        }

        $decoded = json_decode((string) file_get_contents($cacheFile), true);
        return is_array($decoded) ? $decoded : array();
    }

    protected function writeCitiesCache(string $cacheFile, array $cities): void
    {
        $directory = dirname($cacheFile);
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        @file_put_contents($cacheFile, json_encode($cities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    protected function normalizeSearchText(string $value): string
    {
        $value = strtolower(trim($value));
        $normalized = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($normalized) && $normalized !== '') {
            $value = $normalized;
        }

        return preg_replace('/\s+/', ' ', $value) ?: '';
    }

    protected function buildLocalCityFallback(): array
    {
        $db = db_connect('default');
        $tables = array(
            'clients' => array('city', 'state', 'deleted'),
            'leads' => array('city', 'state', 'deleted'),
            'users' => array('city', 'state', 'deleted'),
        );
        $cities = array();
        $seen = array();

        foreach ($tables as $tableName => $fields) {
            $prefixTable = $db->prefixTable($tableName);
            if (!$db->tableExists($prefixTable) || !$db->fieldExists('city', $prefixTable)) {
                continue;
            }

            $builder = $db->table($prefixTable);
            $builder->select('city, state');

            if ($db->fieldExists('deleted', $prefixTable)) {
                $builder->where('deleted', 0);
            }

            $rows = $builder->get()->getResultArray();
            foreach ($rows as $row) {
                $city = trim((string) ($row['city'] ?? ''));
                if ($city === '') {
                    continue;
                }

                $state = trim((string) ($row['state'] ?? ''));
                $label = $city . ($state ? ' - ' . $state : '');
                $key = strtolower($this->normalizeSearchText($label));
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $cities[] = array(
                    'id' => $label,
                    'text' => $label,
                    'city' => $city,
                    'state' => $state,
                    'ibge_id' => '',
                );
            }
        }

        usort($cities, function (array $left, array $right) {
            return strcmp($left['text'], $right['text']);
        });

        return $cities;
    }
}
