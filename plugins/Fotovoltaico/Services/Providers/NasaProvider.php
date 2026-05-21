<?php

namespace Fotovoltaico\Services\Providers;

class NasaProvider
{
    public function get_annual_insolation($latitude, $longitude)
    {
        $query = http_build_query(array(
            'parameters' => 'ALLSKY_SFC_SW_DWN',
            'community' => 'RE',
            'longitude' => $longitude,
            'latitude' => $latitude,
            'format' => 'JSON',
        ));

        $endpoint = 'https://power.larc.nasa.gov/api/temporal/climatology/point?' . $query;
        $response = $this->_request_json($endpoint);
        if (!$response['success']) {
            return $response;
        }

        $data = $response['body'];
        $monthly = array();
        $annual = 0;
        $months = get_array_value(get_array_value(get_array_value($data, 'properties'), 'parameter'), 'ALLSKY_SFC_SW_DWN');
        if (is_array($months)) {
            foreach ($months as $month => $value) {
                if (!is_numeric($value)) {
                    continue;
                }

                $month_number = $this->_normalize_month_key($month);
                if (!$month_number) {
                    continue;
                }

                $days = $this->_days_in_month($month_number);
                $monthly[$month_number] = round(((float) $value) * $days, 4);
                $annual += $monthly[$month_number];
            }
        }

        return array(
            'success' => true,
            'provider' => 'nasa',
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
                'error_message' => $error ?: 'Unable to reach NASA POWER'
            );
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return array(
                'success' => false,
                'http_status' => $http_status,
                'endpoint' => $url,
                'error_message' => 'Invalid NASA POWER response'
            );
        }

        return array(
            'success' => $http_status >= 200 && $http_status < 300,
            'http_status' => $http_status,
            'endpoint' => $url,
            'body' => $decoded,
            'error_message' => $http_status >= 200 && $http_status < 300 ? '' : 'NASA POWER returned HTTP ' . $http_status
        );
    }

    private function _normalize_month_key($month)
    {
        if (is_numeric($month)) {
            $month = (int) $month;
            return ($month >= 1 && $month <= 12) ? $month : 0;
        }

        $map = array(
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        );

        $key = strtolower(substr(trim((string) $month), 0, 9));
        return get_array_value($map, $key) ?: 0;
    }

    private function _days_in_month($month_number)
    {
        $month_number = max(1, min(12, (int) $month_number));
        return cal_days_in_month(CAL_GREGORIAN, $month_number, (int) date('Y'));
    }
}
