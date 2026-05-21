<?php

namespace Fotovoltaico\Services\Providers;

class PvgisProvider
{
    public function get_annual_insolation($latitude, $longitude)
    {
        $query = http_build_query(array(
            'lat' => $latitude,
            'lon' => $longitude,
            'peakpower' => 1,
            'loss' => 14,
            'outputformat' => 'json',
            'angle' => 30,
            'aspect' => 0
        ));

        $endpoint = 'https://re.jrc.ec.europa.eu/api/v5_2/PVcalc?' . $query;
        $response = $this->_request_json($endpoint);
        if (!$response['success']) {
            return $response;
        }

        $data = $response['body'];
        $monthly = array();
        $annual = 0;
        $monthly_rows = get_array_value(get_array_value(get_array_value($data, 'outputs'), 'monthly'), 'fixed');
        if (is_array($monthly_rows)) {
            foreach ($monthly_rows as $row) {
                $month = (int) get_array_value($row, 'month');
                $value = $this->_first_numeric_value($row, array('H(i)_m', 'E_m', 'E_d'));
                if (!$month || $value === null) {
                    continue;
                }

                $monthly[$month] = (float) $value;
                $annual += (float) $value;
            }
        }

        return array(
            'success' => true,
            'provider' => 'pvgis',
            'endpoint' => $endpoint,
            'http_status' => 200,
            'payload' => $data,
            'annual_insolation' => round($annual, 4),
            'monthly_insolation' => $monthly,
        );
    }

    private function _request_json($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array('Accept: application/json'),
        ));

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $http_status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error) {
            return array(
                'success' => false,
                'http_status' => $http_status ?: 0,
                'endpoint' => $url,
                'error_message' => $error ?: 'Unable to reach PVGIS'
            );
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return array(
                'success' => false,
                'http_status' => $http_status,
                'endpoint' => $url,
                'error_message' => 'Invalid PVGIS response'
            );
        }

        return array(
            'success' => $http_status >= 200 && $http_status < 300,
            'http_status' => $http_status,
            'endpoint' => $url,
            'body' => $decoded,
            'error_message' => $http_status >= 200 && $http_status < 300 ? '' : 'PVGIS returned HTTP ' . $http_status
        );
    }

    private function _first_numeric_value($row, $keys)
    {
        foreach ($keys as $key) {
            $value = get_array_value($row, $key);
            if ($value !== null && $value !== '' && is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}
