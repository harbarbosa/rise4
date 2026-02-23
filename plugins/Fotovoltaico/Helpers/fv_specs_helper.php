<?php

/**
 * Helpers para normalizar specs de módulo e inversor.
 */

if (!function_exists('fv_get_spec')) {
    function fv_get_spec($specs, $candidates, $default = null)
    {
        if (!is_array($specs)) {
            return $default;
        }
        foreach ($candidates as $key) {
            if (array_key_exists($key, $specs) && $specs[$key] !== '' && $specs[$key] !== null) {
                return $specs[$key];
            }
        }
        return $default;
    }
}

if (!function_exists('fv_normalize_module_specs')) {
    function fv_normalize_module_specs($specs_json, $power_w = null, $assume_vmpp_ratio = 0.83)
    {
        $specs = $specs_json;
        if (is_string($specs_json)) {
            $decoded = json_decode($specs_json, true);
            if (is_array($decoded)) {
                $specs = $decoded;
            }
        }

        $voc = fv_get_spec($specs, ['voc_v', 'voc', 'open_circuit_voltage']);
        $vmpp = fv_get_spec($specs, ['vmpp_v', 'vmpp']);
        $isc = fv_get_spec($specs, ['isc_a', 'isc', 'short_circuit_current']);
        $impp = fv_get_spec($specs, ['impp_a', 'impp']);
        $pmax = fv_get_spec($specs, ['pmax_w', 'pmax', 'max_power', 'rated_power'], $power_w);
        $coef_voc = fv_get_spec($specs, ['temp_coeff_voc', 'temp_coeff_voc_percent_per_c', 'temp_coeff_voc_percent']);
        $coef_pmax = fv_get_spec($specs, ['temp_coeff_pmax', 'temp_coeff_pmax_percent_per_c', 'temp_coeff_pmax_percent']);
        $noct = fv_get_spec($specs, ['noct_c', 'noct']);

        if (!$vmpp && $voc) {
            $vmpp = (float)$voc * (float)$assume_vmpp_ratio;
        }

        return [
            'pmax_w' => $pmax !== null ? (float)$pmax : null,
            'voc_v' => $voc !== null ? (float)$voc : null,
            'vmpp_v' => $vmpp !== null ? (float)$vmpp : null,
            'isc_a' => $isc !== null ? (float)$isc : null,
            'impp_a' => $impp !== null ? (float)$impp : null,
            'temp_coeff_voc_percent_per_c' => $coef_voc !== null ? (float)$coef_voc : null,
            'temp_coeff_pmax_percent_per_c' => $coef_pmax !== null ? (float)$coef_pmax : null,
            'noct_c' => $noct !== null ? (float)$noct : null
        ];
    }
}

if (!function_exists('fv_normalize_inverter_specs')) {
    function fv_normalize_inverter_specs($specs_json, $power_w = null)
    {
        $specs = $specs_json;
        if (is_string($specs_json)) {
            $decoded = json_decode($specs_json, true);
            if (is_array($decoded)) {
                $specs = $decoded;
            }
        }

        $vdc_max = fv_get_spec($specs, ['vdc_max_v', 'vdc_max']);
        $mppt_min = fv_get_spec($specs, ['mppt_min_v', 'mppt_min']);
        $mppt_max = fv_get_spec($specs, ['mppt_max_v', 'mppt_max']);
        $mppt_count = fv_get_spec($specs, ['mppt_count'], 1);
        $strings_per_mppt = fv_get_spec($specs, ['strings_per_mppt', 'max_strings_per_mppt'], 1);
        $max_current = fv_get_spec($specs, ['max_current_mppt_a', 'max_input_current_a'], null);
        $ac_power = fv_get_spec($specs, ['ac_power_w', 'rated_ac_power_w', 'rated_ac_power', 'power_w'], $power_w);
        $dc_power_max = fv_get_spec($specs, ['dc_power_max_w', 'dc_power_max']);

        return [
            'vdc_max_v' => $vdc_max !== null ? (float)$vdc_max : null,
            'mppt_min_v' => $mppt_min !== null ? (float)$mppt_min : null,
            'mppt_max_v' => $mppt_max !== null ? (float)$mppt_max : null,
            'mppt_count' => (int)$mppt_count,
            'strings_per_mppt' => (int)$strings_per_mppt,
            'max_current_mppt_a' => $max_current !== null ? (float)$max_current : null,
            'ac_power_w' => $ac_power !== null ? (float)$ac_power : null,
            'dc_power_max_w' => $dc_power_max !== null ? (float)$dc_power_max : null
        ];
    }
}
