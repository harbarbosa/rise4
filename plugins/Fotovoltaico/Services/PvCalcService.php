<?php

namespace Fotovoltaico\Services;

use Fotovoltaico\Services\Lei14300Service;

class PvCalcService
{
    public function preview($input = array())
    {
        $inputs = $this->_normalize_inputs($input);
        $monthly_irradiance = $this->_normalize_monthly_irradiance($inputs);
        $system_power_kwp = $inputs['system_power_kwp'];
        $pr = $inputs['pr'];
        $losses_factor = max(0, 1 - $inputs['losses']);
        $tariff = $inputs['tariff'];
        $consumption_avg = $inputs['consumption_avg'];
        $degradation = $inputs['degradation'];
        $law = $inputs['law'];
        $lei14300_service = new Lei14300Service();
        $current_year = (int) date('Y');

        $monthly_generation = array();
        $annual_generation = 0;
        foreach ($monthly_irradiance as $month => $irradiance) {
            $generation = $system_power_kwp * $irradiance * $pr * $losses_factor;
            $monthly_generation[$month] = round($generation, 4);
            $annual_generation += $generation;
        }

        $annual_generation = round($annual_generation, 4);
        $compensation = min($annual_generation, $consumption_avg > 0 ? $consumption_avg : $annual_generation);
        $offset_percent = $consumption_avg > 0 ? round(min(100, ($annual_generation / $consumption_avg) * 100), 2) : 0;

        $law_preview = $lei14300_service->preview(array(
            'annual_generation_kwh' => $annual_generation,
            'tariff' => $tariff,
            'consumption_avg' => $consumption_avg,
            'year' => $current_year,
            'years' => max(1, (int) get_array_value($law, 'projection_years')) ?: 25,
            'scenarios' => array(
                'base' => $law,
            ),
        ));
        $law_result = get_array_value(get_array_value($law_preview, 'scenarios'), 'base') ?: array();
        $monthly_economy = round(((float) get_array_value($law_result, 'economy_value') / 12), 4);
        $annual_economy = round((float) get_array_value($law_result, 'economy_value'), 4);

        $projection_years = max(1, (int) get_array_value($law, 'projection_years'));
        if (!$projection_years) {
            $projection_years = 25;
        }

        $projection = array();
        $year_generation = $annual_generation;
        for ($year = 1; $year <= $projection_years; $year++) {
            if ($year > 1) {
                $year_generation = $year_generation * (1 - $degradation);
            }

            $year_compensation = min($year_generation, $consumption_avg > 0 ? $consumption_avg : $year_generation);
            $year_law_result = $lei14300_service->calculate_for_projection($year_generation, $tariff, $consumption_avg, $current_year + ($year - 1), $law);
            $projection[] = array(
                'year' => $year,
                'generation_kwh' => round($year_generation, 4),
                'compensation_kwh' => round($year_compensation, 4),
                'offset_percent' => $consumption_avg > 0 ? round(min(100, ($year_generation / $consumption_avg) * 100), 2) : 0,
                'economy_value' => round((float) get_array_value($year_law_result, 'economy_value'), 4),
                'law_discount_value' => round((float) get_array_value($year_law_result, 'law_discount_value'), 4),
                'grid_charge_value' => round((float) get_array_value($year_law_result, 'grid_charge_value'), 4),
                'degradation_factor' => round($year > 1 ? pow(1 - $degradation, $year - 1) : 1, 6),
            );
        }

        $result = array(
            'success' => true,
            'inputs' => array(
                'system_power_kwp' => $system_power_kwp,
                'insolation' => $inputs['insolation_raw'],
                'pr' => $pr,
                'consumption_avg' => $consumption_avg,
                'tariff' => $tariff,
                'losses' => $inputs['losses'],
                'degradation' => $degradation,
                'law_14300' => $law,
            ),
            'outputs' => array(
                'monthly_generation' => $monthly_generation,
                'annual_generation' => $annual_generation,
                'compensation_energy' => round($compensation, 4),
                'economy_monthly' => $monthly_economy,
                'economy_annual' => $annual_economy,
                'offset_percent' => $offset_percent,
                'losses_percent' => round($inputs['losses'] * 100, 2),
                'losses_factor' => round($losses_factor, 4),
                'pr' => round($pr, 4),
                'degradation_percent' => round($degradation * 100, 4),
                'law_14300' => $law_result,
                'annual_projection' => $projection,
            ),
        );

        $result['snapshot'] = array(
            'technical' => $result['outputs'],
            'calculation_type' => 'pv_estimate',
            'generated_at' => get_my_local_time(),
        );

        return $result;
    }

    private function _normalize_inputs($input)
    {
        $system_power_kwp = $this->_number(get_array_value($input, 'system_power_kwp'));
        $pr = $this->_number(get_array_value($input, 'pr'), 0.75);
        $consumption_avg = $this->_number(get_array_value($input, 'consumption_avg'));
        $tariff = $this->_number(get_array_value($input, 'tariff'));
        $losses = $this->_percentage_to_factor(get_array_value($input, 'losses'), 0.14);
        $degradation = $this->_percentage_to_factor(get_array_value($input, 'degradation'), 0.005);
        $law = get_array_value($input, 'law_14300');
        if (!is_array($law)) {
            $law = $this->_decode_json(get_array_value($input, 'law_14300_json'));
        }
        if (!is_array($law)) {
            $law = array();
        }

        $insolation_raw = get_array_value($input, 'insolation');
        $monthly_insolation = $this->_decode_monthly_irradiance($insolation_raw);
        if (!$monthly_insolation) {
            $annual_insolation = $this->_number($insolation_raw);
            if ($annual_insolation > 0) {
                $monthly_insolation = array_fill(1, 12, round($annual_insolation / 12, 4));
            } else {
                $monthly_insolation = array_fill(1, 12, 0);
            }
        }

        return array(
            'system_power_kwp' => $system_power_kwp,
            'insolation_raw' => $insolation_raw,
            'monthly_insolation' => $monthly_insolation,
            'pr' => $pr,
            'consumption_avg' => $consumption_avg,
            'tariff' => $tariff,
            'losses' => $losses,
            'degradation' => $degradation,
            'law' => $law,
        );
    }

    private function _normalize_monthly_irradiance($inputs)
    {
        $monthly = get_array_value($inputs, 'monthly_insolation');
        $result = array();
        for ($month = 1; $month <= 12; $month++) {
            $result[$month] = $this->_number(get_array_value($monthly, $month));
        }

        return $result;
    }

    private function _decode_monthly_irradiance($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (!is_array($value)) {
            return array();
        }

        $result = array();
        foreach ($value as $key => $item) {
            $month = $this->_normalize_month_key($key);
            if (!$month) {
                continue;
            }

            if (is_array($item)) {
                $item = get_array_value($item, 'value');
            }

            $result[$month] = $this->_number($item);
        }

        return $result;
    }

    private function _normalize_month_key($month)
    {
        if (is_numeric($month)) {
            $month = (int) $month;
            return ($month >= 1 && $month <= 12) ? $month : 0;
        }

        $month = strtolower(trim((string) $month));
        $map = array(
            'jan' => 1,
            'january' => 1,
            'fev' => 2,
            'feb' => 2,
            'february' => 2,
            'mar' => 3,
            'march' => 3,
            'abr' => 4,
            'apr' => 4,
            'april' => 4,
            'mai' => 5,
            'may' => 5,
            'jun' => 6,
            'june' => 6,
            'jul' => 7,
            'july' => 7,
            'ago' => 8,
            'aug' => 8,
            'august' => 8,
            'set' => 9,
            'sep' => 9,
            'september' => 9,
            'out' => 10,
            'oct' => 10,
            'october' => 10,
            'nov' => 11,
            'november' => 11,
            'dez' => 12,
            'dec' => 12,
            'december' => 12,
        );

        return get_array_value($map, $month) ?: 0;
    }

    private function _number($value, $fallback = 0)
    {
        if ($value === null || $value === '') {
            return (float) $fallback;
        }

        if (is_array($value)) {
            $value = get_array_value($value, 'value');
        }

        $value = str_replace(array('%', ','), array('', '.'), (string) $value);
        if (!is_numeric($value)) {
            return (float) $fallback;
        }

        return (float) $value;
    }

    private function _percentage_to_factor($value, $fallback = 0)
    {
        $value = $this->_number($value, $fallback);
        if ($value > 1) {
            return $value / 100;
        }

        return $value;
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
}
