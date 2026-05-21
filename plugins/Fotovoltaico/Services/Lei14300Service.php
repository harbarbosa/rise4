<?php

namespace Fotovoltaico\Services;

use Fotovoltaico\Models\Settings_model;

class Lei14300Service
{
    private $settings_model;
    private $setting_key = 'lei14300_config_json';

    public function __construct()
    {
        $this->settings_model = model(Settings_model::class);
    }

    public function get_configuration()
    {
        $raw = $this->settings_model->get_setting($this->setting_key);
        $config = $this->_decode_json($raw);

        return $this->_normalize_config($config);
    }

    public function save_configuration($config = array())
    {
        $config = $this->_normalize_config($config);
        return $this->settings_model->save_setting(
            $this->setting_key,
            json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'app'
        );
    }

    public function preview($input = array())
    {
        $annual_generation_kwh = $this->_number(get_array_value($input, 'annual_generation_kwh'));
        $tariff = $this->_number(get_array_value($input, 'tariff'));
        $consumption_avg = $this->_number(get_array_value($input, 'consumption_avg'));
        $year = (int) get_array_value($input, 'year');
        $years = (int) get_array_value($input, 'years');
        $years = $years > 0 ? $years : 25;

        $configuration = $this->get_configuration();
        $scenarios = $this->_build_scenarios($configuration, $input);

        $results = array();
        foreach ($scenarios as $scenario_name => $scenario_config) {
            $results[$scenario_name] = $this->_calculate_scenario($annual_generation_kwh, $tariff, $consumption_avg, $year, $years, $scenario_config);
        }

        return array(
            'success' => true,
            'configuration' => $configuration,
            'scenarios' => $results,
            'consolidated' => $this->_consolidate_results($results),
        );
    }

    public function calculate_for_projection($annual_generation_kwh, $tariff, $consumption_avg, $year, $scenario = array())
    {
        $configuration = $this->get_configuration();
        $scenario = $this->_normalize_scenario(array_merge($configuration, $scenario));
        return $this->_calculate_scenario($annual_generation_kwh, $tariff, $consumption_avg, $year, 1, $scenario);
    }

    private function _build_scenarios($configuration, $input)
    {
        $scenarios = array();
        $provided = get_array_value($input, 'scenarios');
        if (is_array($provided) && $provided) {
            foreach ($provided as $name => $scenario) {
                $scenarios[$name] = $this->_normalize_scenario(array_merge($configuration, is_array($scenario) ? $scenario : array()));
            }
        }

        if (!$scenarios) {
            $scenarios = array(
                'base' => $this->_normalize_scenario($configuration),
            );
        }

        return $scenarios;
    }

    private function _calculate_scenario($annual_generation_kwh, $tariff, $consumption_avg, $year, $years, $scenario)
    {
        $year = (int) $year;
        $years = max(1, (int) $years);
        $ramp_years = max(1, (int) get_array_value($scenario, 'ramp_years'));
        $start_year = (int) get_array_value($scenario, 'start_year');
        if (!$start_year) {
            $start_year = (int) date('Y');
        }

        $current_year = $year ?: $start_year;
        $elapsed = max(0, $current_year - $start_year);
        $ramp_ratio = min(1, $elapsed / $ramp_years);
        $fio_b_start = $this->_percentage_to_factor(get_array_value($scenario, 'fio_b_start_percent'));
        $fio_b_end = $this->_percentage_to_factor(get_array_value($scenario, 'fio_b_end_percent'));
        $grid_fee = $this->_percentage_to_factor(get_array_value($scenario, 'grid_fee_percent'));
        $energy_distributed = $this->_percentage_to_factor(get_array_value($scenario, 'energy_distributed_percent'));
        $compensation_factor = $this->_percentage_to_factor(get_array_value($scenario, 'compensation_factor'), 1);
        $full_offset_until = (int) get_array_value($scenario, 'full_offset_until');

        $fio_b_percent = $fio_b_start + (($fio_b_end - $fio_b_start) * $ramp_ratio);
        $effective_compensation_factor = max(0, min(1, $compensation_factor));
        if ($full_offset_until > 0 && $current_year > $full_offset_until) {
            $effective_compensation_factor = max(0, $effective_compensation_factor - $grid_fee);
        }

        $compensation_kwh = min($annual_generation_kwh, $consumption_avg > 0 ? $consumption_avg : $annual_generation_kwh);
        $eligible_energy_kwh = $compensation_kwh * $effective_compensation_factor;
        $law_discount_value = $eligible_energy_kwh * $tariff;
        $fio_b_charge_value = $compensation_kwh * $tariff * $fio_b_percent;
        $grid_charge_value = $compensation_kwh * $tariff * $grid_fee;
        $distributed_value = $compensation_kwh * $tariff * $energy_distributed;
        $economy_value = max(0, $law_discount_value - $fio_b_charge_value - $grid_charge_value + $distributed_value);

        return array(
            'scenario' => get_array_value($scenario, 'name') ?: 'base',
            'year' => $current_year,
            'ramp_ratio' => round($ramp_ratio, 4),
            'compensation_kwh' => round($compensation_kwh, 4),
            'eligible_energy_kwh' => round($eligible_energy_kwh, 4),
            'compensation_factor' => round($effective_compensation_factor, 4),
            'fio_b_start_percent' => round($fio_b_start, 4),
            'fio_b_end_percent' => round($fio_b_end, 4),
            'fio_b_percent' => round($fio_b_percent, 4),
            'grid_fee_percent' => round($grid_fee, 4),
            'energy_distributed_percent' => round($energy_distributed, 4),
            'law_discount_value' => round($law_discount_value, 4),
            'fio_b_charge_value' => round($fio_b_charge_value, 4),
            'grid_charge_value' => round($grid_charge_value, 4),
            'distributed_value' => round($distributed_value, 4),
            'economy_value' => round($economy_value, 4),
            'consumption_avg' => round($consumption_avg, 4),
            'tariff' => round($tariff, 4),
            'annual_generation_kwh' => round($annual_generation_kwh, 4),
        );
    }

    private function _consolidate_results($results)
    {
        $consolidated = array(
            'economy_value' => 0,
            'law_discount_value' => 0,
            'fio_b_charge_value' => 0,
            'grid_charge_value' => 0,
            'distributed_value' => 0,
        );

        foreach ($results as $result) {
            $consolidated['economy_value'] += (float) get_array_value($result, 'economy_value');
            $consolidated['law_discount_value'] += (float) get_array_value($result, 'law_discount_value');
            $consolidated['fio_b_charge_value'] += (float) get_array_value($result, 'fio_b_charge_value');
            $consolidated['grid_charge_value'] += (float) get_array_value($result, 'grid_charge_value');
            $consolidated['distributed_value'] += (float) get_array_value($result, 'distributed_value');
        }

        return array_map(function ($value) {
            return round((float) $value, 4);
        }, $consolidated);
    }

    private function _normalize_config($config)
    {
        if (!is_array($config)) {
            $config = array();
        }

        $defaults = array(
            'name' => 'default',
            'start_year' => (int) date('Y'),
            'ramp_years' => 25,
            'fio_b_start_percent' => 0.0,
            'fio_b_end_percent' => 0.0,
            'grid_fee_percent' => 0.0,
            'energy_distributed_percent' => 0.0,
            'compensation_factor' => 1.0,
            'full_offset_until' => 0,
        );

        $config = array_merge($defaults, $config);
        $config['start_year'] = (int) $config['start_year'];
        $config['ramp_years'] = max(1, (int) $config['ramp_years']);
        $config['fio_b_start_percent'] = $this->_percentage_to_factor($config['fio_b_start_percent']);
        $config['fio_b_end_percent'] = $this->_percentage_to_factor($config['fio_b_end_percent']);
        $config['grid_fee_percent'] = $this->_percentage_to_factor($config['grid_fee_percent']);
        $config['energy_distributed_percent'] = $this->_percentage_to_factor($config['energy_distributed_percent']);
        $config['compensation_factor'] = $this->_percentage_to_factor($config['compensation_factor'], 1);
        $config['full_offset_until'] = (int) $config['full_offset_until'];
        $config['scenarios'] = isset($config['scenarios']) && is_array($config['scenarios']) ? $config['scenarios'] : array();

        return $config;
    }

    private function _normalize_scenario($scenario)
    {
        $scenario = $this->_normalize_config($scenario);
        return $scenario;
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
