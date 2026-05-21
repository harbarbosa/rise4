<?php

namespace Fotovoltaico\Services;

class FinanceCalcService
{
    public function preview($input = array())
    {
        $inputs = $this->_normalize_inputs($input);
        $horizon = $inputs['horizon'];
        $cash_flows = array();
        $cumulative = array();
        $discounted_cumulative = array();
        $projected_savings = array();

        $cash_flows[0] = -$inputs['investment_initial'];
        $cumulative[0] = $cash_flows[0];
        $discounted_cumulative[0] = $cash_flows[0];
        $projected_savings[0] = 0;

        $simple_payback = null;
        $discounted_payback = null;
        $vpl = $cash_flows[0];
        $discount_rate = $inputs['discount_rate'];
        $tariff_escalation = $inputs['tariff_escalation'];
        $annual_savings = $inputs['economy_annual'];
        $maintenance_cost = $inputs['maintenance_cost_annual'];
        $replacement_schedule = $inputs['replacement_schedule'];

        for ($year = 1; $year <= $horizon; $year++) {
            $growth_factor = pow(1 + $tariff_escalation, $year - 1);
            $year_savings = $annual_savings * $growth_factor;
            $year_maintenance = $maintenance_cost * pow(1 + $inputs['maintenance_escalation'], $year - 1);
            $year_replacements = $this->_replacement_cost_for_year($replacement_schedule, $year);

            $net_flow = $year_savings - $year_maintenance - $year_replacements;
            $cash_flows[$year] = round($net_flow, 4);
            $projected_savings[$year] = round($year_savings, 4);

            $cumulative[$year] = round(($cumulative[$year - 1] ?? 0) + $net_flow, 4);
            $discount_factor = pow(1 + $discount_rate, $year);
            $discounted_flow = $discount_factor > 0 ? $net_flow / $discount_factor : $net_flow;
            $discounted_cumulative[$year] = round(($discounted_cumulative[$year - 1] ?? 0) + $discounted_flow, 4);
            $vpl += $discounted_flow;

            if ($simple_payback === null && $cumulative[$year] >= 0) {
                $prev = $cumulative[$year - 1] ?? -$inputs['investment_initial'];
                $simple_payback = $this->_interpolate_payback($year, $prev, $cumulative[$year]);
            }

            if ($discounted_payback === null && $discounted_cumulative[$year] >= 0) {
                $prev = $discounted_cumulative[$year - 1] ?? -$inputs['investment_initial'];
                $discounted_payback = $this->_interpolate_payback($year, $prev, $discounted_cumulative[$year]);
            }
        }

        $irr = $this->_irr($cash_flows);

        $annual_projection = array();
        for ($year = 1; $year <= $horizon; $year++) {
            $annual_projection[] = array(
                'year' => $year,
                'gross_savings' => $projected_savings[$year] ?? 0,
                'maintenance_cost' => round($maintenance_cost * pow(1 + $inputs['maintenance_escalation'], $year - 1), 4),
                'replacement_cost' => round($this->_replacement_cost_for_year($replacement_schedule, $year), 4),
                'net_cash_flow' => $cash_flows[$year] ?? 0,
                'cumulative_cash_flow' => $cumulative[$year] ?? 0,
                'discounted_cash_flow' => round(($cash_flows[$year] ?? 0) / pow(1 + $discount_rate, $year), 4),
                'discounted_cumulative_cash_flow' => $discounted_cumulative[$year] ?? 0,
            );
        }

        $result = array(
            'success' => true,
            'inputs' => $inputs,
            'outputs' => array(
                'payback_simple_years' => $simple_payback,
                'payback_discounted_years' => $discounted_payback,
                'tir' => $irr,
                'vpl' => round($vpl, 4),
                'cash_flows' => $cash_flows,
                'annual_projection' => $annual_projection,
                'retorno_acumulado' => $cumulative,
                'retorno_acumulado_descontado' => $discounted_cumulative,
                'economia_projetada' => $projected_savings,
            ),
        );

        $result['snapshot'] = array(
            'financial' => $result['outputs'],
            'generated_at' => get_my_local_time(),
        );

        return $result;
    }

    private function _normalize_inputs($input)
    {
        $investment_initial = $this->_number(get_array_value($input, 'investment_initial'));
        $economy_annual = $this->_number(get_array_value($input, 'economy_annual'));
        $tariff_escalation = $this->_percentage_to_factor(get_array_value($input, 'tariff_escalation'));
        $discount_rate = $this->_percentage_to_factor(get_array_value($input, 'discount_rate'));
        $maintenance_cost_annual = $this->_number(get_array_value($input, 'maintenance_cost_annual'));
        $maintenance_escalation = $this->_percentage_to_factor(get_array_value($input, 'maintenance_escalation'));
        $horizon = (int) get_array_value($input, 'horizon');
        if ($horizon <= 0) {
            $horizon = 25;
        }

        $replacement_schedule = get_array_value($input, 'replacement_schedule');
        if (!$replacement_schedule) {
            $replacement_schedule = get_array_value($input, 'replacement_schedule_json');
        }
        if (is_string($replacement_schedule)) {
            $replacement_schedule = $this->_decode_json($replacement_schedule);
        }
        if (!is_array($replacement_schedule)) {
            $replacement_schedule = array();
        }

        $replacement_schedule = $this->_normalize_replacement_schedule($replacement_schedule);

        return array(
            'investment_initial' => $investment_initial,
            'economy_annual' => $economy_annual,
            'tariff_escalation' => $tariff_escalation,
            'discount_rate' => $discount_rate,
            'maintenance_cost_annual' => $maintenance_cost_annual,
            'maintenance_escalation' => $maintenance_escalation,
            'horizon' => $horizon,
            'replacement_schedule' => $replacement_schedule,
        );
    }

    private function _normalize_replacement_schedule($schedule)
    {
        $result = array();
        foreach ($schedule as $row) {
            if (!is_array($row)) {
                continue;
            }

            $year = (int) get_array_value($row, 'year');
            $cost = $this->_number(get_array_value($row, 'cost'));
            if ($year > 0 && $cost > 0) {
                $result[] = array(
                    'year' => $year,
                    'cost' => $cost,
                    'label' => trim((string) get_array_value($row, 'label')),
                );
            }
        }

        return $result;
    }

    private function _replacement_cost_for_year($schedule, $year)
    {
        $cost = 0;
        foreach ($schedule as $row) {
            if ((int) get_array_value($row, 'year') === (int) $year) {
                $cost += $this->_number(get_array_value($row, 'cost'));
            }
        }

        return $cost;
    }

    private function _interpolate_payback($year, $previous_cumulative, $current_cumulative)
    {
        $net = $current_cumulative - $previous_cumulative;
        if ($net == 0) {
            return (float) $year;
        }

        $portion = abs($previous_cumulative) / abs($net);
        return round(($year - 1) + $portion, 4);
    }

    private function _irr($cash_flows)
    {
        $low = -0.9999;
        $high = 10;
        $npv_low = $this->_npv($cash_flows, $low);
        $npv_high = $this->_npv($cash_flows, $high);

        if ($npv_low * $npv_high > 0) {
            return null;
        }

        for ($i = 0; $i < 100; $i++) {
            $mid = ($low + $high) / 2;
            $npv_mid = $this->_npv($cash_flows, $mid);
            if (abs($npv_mid) < 0.0001) {
                return round($mid, 6);
            }

            if ($npv_low * $npv_mid <= 0) {
                $high = $mid;
                $npv_high = $npv_mid;
            } else {
                $low = $mid;
                $npv_low = $npv_mid;
            }
        }

        return round(($low + $high) / 2, 6);
    }

    private function _npv($cash_flows, $rate)
    {
        $npv = 0;
        foreach ($cash_flows as $period => $value) {
            $npv += $value / pow(1 + $rate, $period);
        }
        return $npv;
    }

    private function _number($value, $fallback = 0)
    {
        if ($value === null || $value === '') {
            return (float) $fallback;
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
