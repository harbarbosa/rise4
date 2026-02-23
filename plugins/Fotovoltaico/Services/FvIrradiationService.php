<?php

namespace Fotovoltaico\Services;

/**
 * ServiÃ§o para buscar irradiaÃ§Ã£o mensal com cache.
 */
class FvIrradiationService
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect('default');
    }

    public function getMonthlyIrradiation($lat, $lon, $provider_preference = 'pvgis')
    {
        $lat = $this->normalizeCoord($lat);
        $lon = $this->normalizeCoord($lon);

        $providers = $provider_preference === 'nasa' ? ['nasa', 'pvgis'] : ['pvgis', 'nasa'];
        foreach ($providers as $provider) {
            $cached = $this->getCached($provider, $lat, $lon);
            if ($cached) {
                return $cached;
            }

            $result = $this->fetchProvider($provider, $lat, $lon);
            if ($result) {
                $this->saveCache($provider, $lat, $lon, $result['monthly'], $result['annual']);
                $result['provider'] = $provider;
                return $result;
            }
        }

        return null;
    }

    private function getCached($provider, $lat, $lon)
    {
        $table = $this->db->prefixTable('fv_irradiation_cache');
        if (!$this->db->tableExists($table)) {
            return null;
        }

        $row = $this->db->table($table)
            ->where('provider', $provider)
            ->where('lat', $lat)
            ->where('lon', $lon)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();

        if (!$row) {
            return null;
        }

        if ($row->expires_at && strtotime($row->expires_at) < time()) {
            return null;
        }

        $monthly = json_decode($row->monthly_json, true);
        if (!is_array($monthly)) {
            return null;
        }

        return [
            'provider' => $provider,
            'monthly' => $monthly,
            'annual' => (float)($row->annual_value ?? 0)
        ];
    }

    private function saveCache($provider, $lat, $lon, $monthly, $annual)
    {
        $table = $this->db->prefixTable('fv_irradiation_cache');
        if (!$this->db->tableExists($table)) {
            return;
        }

        $this->db->table($table)->insert([
            'provider' => $provider,
            'lat' => $lat,
            'lon' => $lon,
            'monthly_json' => json_encode($monthly, JSON_UNESCAPED_UNICODE),
            'annual_value' => $annual,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+180 days'))
        ]);
    }

    private function fetchProvider($provider, $lat, $lon)
    {
        if ($provider === 'nasa') {
            return $this->fetchNasa($lat, $lon);
        }
        return $this->fetchPvgis($lat, $lon);
    }

    private function fetchPvgis($lat, $lon)
    {
        $url = 'https://re.jrc.ec.europa.eu/api/v5_2/PVGIS-SARAH2?lat=' . $lat . '&lon=' . $lon . '&outputformat=json&pvcalculation=0&angle=0&aspect=0';
        $json = $this->httpGetJson($url);
        if (!$json) {
            return null;
        }

        $monthly = [];
        if (isset($json['outputs']['monthly']) && is_array($json['outputs']['monthly'])) {
            foreach ($json['outputs']['monthly'] as $row) {
                $month = (int)($row['month'] ?? 0);
                if ($month >= 1 && $month <= 12) {
                    $value = $row['H(h)_m'] ?? $row['Hh_m'] ?? null;
                    if ($value === null && isset($row['H(i)_m'])) {
                        $value = $row['H(i)_m'];
                    }
                    $monthly[$month - 1] = (float)$value;
                }
            }
        }

        if (count($monthly) !== 12) {
            return null;
        }

        $annual = array_sum($monthly);
        return ['monthly' => array_values($monthly), 'annual' => $annual];
    }

    private function fetchNasa($lat, $lon)
    {
        $url = 'https://power.larc.nasa.gov/api/temporal/climatology/point?parameters=ALLSKY_SFC_SW_DWN&community=RE&longitude=' . $lon . '&latitude=' . $lat . '&format=JSON';
        $json = $this->httpGetJson($url);
        if (!$json) {
            return null;
        }

        $data = $json['properties']['parameter']['ALLSKY_SFC_SW_DWN'] ?? null;
        if (!$data || !is_array($data)) {
            return null;
        }

        $map = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        $monthly = [];
        foreach ($map as $i => $key) {
            $monthly[$i] = isset($data[$key]) ? (float)$data[$key] : 0;
        }

        $annual = array_sum($monthly);
        return ['monthly' => $monthly, 'annual' => $annual];
    }

    private function httpGetJson($url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 20
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);

        $raw = @file_get_contents($url, false, $context);
        if (!$raw) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    private function normalizeCoord($value)
    {
        $num = (float)str_replace(',', '.', (string)$value);
        return round($num, 6);
    }
}
