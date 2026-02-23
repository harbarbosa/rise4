<?php

namespace Fotovoltaico\Services;

/**
 * Serviço de validação elétrica (strings/MPPT).
 */
class ElectricalValidatorService
{
    private $db;
    private $settings;

    public function __construct($settings = array())
    {
        $this->db = db_connect('default');
        $this->settings = $settings;

        $helper = __DIR__ . '/../Helpers/fv_specs_helper.php';
        if (file_exists($helper)) {
            require_once $helper;
        }
    }

    public function validateKit($kit_id, $save = false)
    {
        $items_model = model('Fotovoltaico\\Models\\Fv_kit_items_model');
        $items = $items_model->get_by_kit($kit_id);

        $modules = array_filter($items, function ($item) {
            return ($item['item_type'] ?? '') === 'product' && ($item['type'] ?? '') === 'module';
        });
        $inverters = array_filter($items, function ($item) {
            return ($item['item_type'] ?? '') === 'product' && ($item['type'] ?? '') === 'inverter';
        });

        $messages = [];
        $checks = [
            'vdc_ok' => false,
            'mppt_ok' => false,
            'current_ok' => false,
            'strings_ok' => false
        ];

        if (!$modules) {
            return $this->result('warning', [['level' => 'warning', 'text' => 'Adicione módulos ao kit.']], $checks, null);
        }
        if (!$inverters) {
            return $this->result('warning', [['level' => 'warning', 'text' => 'Adicione inversor ao kit.']], $checks, null);
        }

        $module_qty_total = 0;
        $module_product = null;
        foreach ($modules as $m) {
            $module_qty_total += (float)($m['qty'] ?? 1);
            if (!$module_product) {
                $module_product = $m;
            }
        }

        $inverter_product = reset($inverters);
        if (count($inverters) > 1) {
            $messages[] = ['level' => 'warning', 'text' => 'Kit possui múltiplos inversores; validação simplificada.'];
        }

        $module_specs = fv_normalize_module_specs($module_product['specs_json'] ?? null, $module_product['power_w'] ?? null, $this->getSetting('assume_vmpp_ratio', 0.83));
        $inverter_specs = fv_normalize_inverter_specs($inverter_product['specs_json'] ?? null, $inverter_product['power_w'] ?? null);

        $temp_min = $this->getSetting('temp_min_c', 5);
        $safety_vdc = $this->getSetting('safety_margin_vdc_percent', 2);
        $safety_current = $this->getSetting('safety_margin_current_percent', 0);
        $assume_voc_coeff = $this->getSetting('assume_voc_temp_coeff_if_missing', -0.28);

        $voc = $module_specs['voc_v'];
        $vmpp = $module_specs['vmpp_v'];
        $isc = $module_specs['isc_a'];
        $impp = $module_specs['impp_a'];
        $coef_voc = $module_specs['temp_coeff_voc_percent_per_c'];

        if ($voc === null || $vmpp === null) {
            $messages[] = ['level' => 'warning', 'text' => 'Specs do módulo incompletas (Voc/Vmpp).'];
        }

        $vdc_max = $inverter_specs['vdc_max_v'];
        $mppt_min = $inverter_specs['mppt_min_v'];
        $mppt_max = $inverter_specs['mppt_max_v'];
        $mppt_count = max(1, (int)$inverter_specs['mppt_count']);
        $strings_per_mppt = max(1, (int)$inverter_specs['strings_per_mppt']);
        $max_current_mppt = $inverter_specs['max_current_mppt_a'];

        if (!$vdc_max || !$mppt_min || !$mppt_max) {
            $messages[] = ['level' => 'warning', 'text' => 'Specs do inversor incompletas (Vdc/MPPT).'];
        }

        $coef_voc = $coef_voc !== null ? $coef_voc : $assume_voc_coeff;
        $coef_voc_abs = abs((float)$coef_voc);
        $voc_cold = $voc ? $voc * (1 + ($coef_voc_abs / 100) * (25 - $temp_min)) : null;
        $voc_cold_margin = $voc_cold ? $voc_cold * (1 + $safety_vdc / 100) : null;

        $vmpp_est = $vmpp;
        if (!$vmpp_est && $voc) {
            $vmpp_est = $voc * $this->getSetting('assume_vmpp_ratio', 0.83);
        }

        $suggestion = null;

        if ($voc_cold_margin && $vmpp_est && $vdc_max && $mppt_min && $mppt_max) {
            $s_max_vdc = (int)floor($vdc_max / $voc_cold_margin);
            $s_min_mppt = (int)ceil($mppt_min / $vmpp_est);
            $s_max_mppt = (int)floor($mppt_max / $vmpp_est);

            $s_min = max($s_min_mppt, 1);
            $s_max = min($s_max_vdc, $s_max_mppt);

            if ($s_max < $s_min) {
                $messages[] = ['level' => 'error', 'text' => 'Nenhuma configuração de série válida encontrada.'];
            } else {
                $best = null;
                $strings_max_total = $mppt_count * $strings_per_mppt;
                $current_string = $impp ?: $isc;

                for ($s = $s_min; $s <= $s_max; $s++) {
                    $strings_total = (int)floor($module_qty_total / $s);
                    if ($strings_total <= 0) {
                        continue;
                    }
                    if ($strings_total > $strings_max_total) {
                        continue;
                    }

                    $distribution = $this->distributeStrings($strings_total, $mppt_count);
                    $current_ok = true;
                    if ($max_current_mppt && $current_string) {
                        foreach ($distribution as $strings_mppt) {
                            $current_mppt = $strings_mppt * $current_string * (1 + $safety_current / 100);
                            if ($current_mppt > $max_current_mppt) {
                                $current_ok = false;
                                break;
                            }
                        }
                    }
                    if (!$current_ok) {
                        continue;
                    }

                    $used = $strings_total * $s;
                    $left = $module_qty_total - $used;
                    $score = [$used, -$left];

                    if (!$best || $score > $best['score']) {
                        $best = [
                            'score' => $score,
                            's' => $s,
                            'strings_total' => $strings_total,
                            'distribution' => $distribution,
                            'used' => $used,
                            'left' => $left
                        ];
                    }
                }

                if ($best) {
                    $suggestion = [
                        'modules_in_series' => $best['s'],
                        'strings_total' => $best['strings_total'],
                        'mppt_count' => $mppt_count,
                        'strings_per_mppt_distribution' => $best['distribution'],
                        'modules_used' => $best['used'],
                        'modules_leftover' => $best['left']
                    ];

                    $checks['vdc_ok'] = $best['s'] <= $s_max_vdc;
                    $checks['mppt_ok'] = $best['s'] >= $s_min_mppt && $best['s'] <= $s_max_mppt;
                    $checks['strings_ok'] = $best['strings_total'] <= ($mppt_count * $strings_per_mppt);
                    $checks['current_ok'] = true;
                } else {
                    $messages[] = ['level' => 'error', 'text' => 'Não foi possível sugerir strings dentro dos limites.'];
                }
            }
        }

        $status = 'ok';
        foreach ($messages as $m) {
            if ($m['level'] === 'error') {
                $status = 'error';
                break;
            }
            if ($m['level'] === 'warning' && $status !== 'error') {
                $status = 'warning';
            }
        }

        $result = [
            'status' => $status,
            'messages' => $messages,
            'suggestion' => $suggestion,
            'checks' => $checks
        ];

        if ($save) {
            $this->saveDesign($kit_id, $module_product['product_id'] ?? null, $inverter_product['product_id'] ?? null, $module_qty_total, $result);
        }

        return $result;
    }

    private function saveDesign($kit_id, $module_product_id, $inverter_product_id, $module_qty_total, $design)
    {
        $table = $this->db->prefixTable('fv_electrical_designs');
        if (!$this->db->tableExists($table)) {
            return false;
        }
        $this->db->table($table)->insert([
            'kit_id' => (int)$kit_id,
            'module_product_id' => $module_product_id ? (int)$module_product_id : null,
            'inverter_product_id' => $inverter_product_id ? (int)$inverter_product_id : null,
            'module_qty_total' => $module_qty_total,
            'design_json' => json_encode($design, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return true;
    }

    private function distributeStrings($strings_total, $mppt_count)
    {
        $distribution = array_fill(0, $mppt_count, 0);
        for ($i = 0; $i < $strings_total; $i++) {
            $distribution[$i % $mppt_count] += 1;
        }
        return $distribution;
    }

    private function getSetting($key, $default)
    {
        if (isset($this->settings[$key]) && $this->settings[$key] !== '') {
            return $this->settings[$key];
        }
        return $default;
    }

    private function result($status, $messages, $checks, $suggestion)
    {
        return [
            'status' => $status,
            'messages' => $messages,
            'suggestion' => $suggestion,
            'checks' => $checks
        ];
    }
}
