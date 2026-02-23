<?php

namespace Fotovoltaico\Services;

/**
 * Serviço de cálculo de geração e indicadores financeiros.
 */
class FvCalculationService
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect('default');
    }

    public function calculateMonthlyGeneration($inputs)
    {
        $results = [];
        $power_kwp = (float)($inputs['system_power_kwp'] ?? 0);
        $losses = (float)($inputs['losses_percent'] ?? 0);
        $tariff = (float)($inputs['tariff_value'] ?? 0);
        $offset = (float)($inputs['offset_percent'] ?? 100);
        $offset_limit = $inputs['offset_limit_percent'] ?? null;
        $irr = $inputs['irradiation_monthly'] ?? [];
        if ($offset_limit !== null && $offset_limit !== '') {
            $offset = min($offset, (float)$offset_limit);
        }

        $tariff_total = $this->resolveTariffTotal($inputs);
        if ($tariff_total !== null) {
            $tariff = $tariff_total;
        }

        for ($m = 1; $m <= 12; $m++) {
            $irr_m = (float)($irr[$m - 1] ?? 0);
            $energy_gen = $irr_m * $power_kwp * (1 - $losses / 100);
            $energy_off = $energy_gen * ($offset / 100);
            $savings = $energy_off * $tariff;
            $results[] = [
                'month' => $m,
                'irradiation_kwh_kwp' => $irr_m,
                'energy_generated_kwh' => $energy_gen,
                'energy_offset_kwh' => $energy_off,
                'savings_value' => $savings
            ];
        }

        return $results;
    }

    public function calculateAnnualProjection($inputs, $energy_year1)
    {
        $results = [];
        $tariff = (float)($inputs['tariff_value'] ?? 0);
        $tariff_growth = (float)($inputs['tariff_growth_percent_year'] ?? 0);
        $degradation = (float)($inputs['degradation_percent_year'] ?? 0);
        $cumulative = 0;
        $regulatory = $inputs['regulatory_snapshot'] ?? [];
        $tariff_mode = $inputs['tariff_mode'] ?? 'total';
        $te_value = (float)($inputs['tariff_te'] ?? 0);
        $tusd_value = (float)($inputs['tariff_tusd'] ?? 0);
        $flags_value = (float)($inputs['tariff_flags'] ?? 0);

        for ($y = 1; $y <= 25; $y++) {
            if ($tariff_mode === 'components') {
                $te_year = $te_value * pow(1 + $tariff_growth / 100, $y - 1);
                $tusd_year = $tusd_value * pow(1 + $tariff_growth / 100, $y - 1);
                $flags_year = $flags_value * pow(1 + $tariff_growth / 100, $y - 1);
                $tariff_year = $te_year + $tusd_year + $flags_year;
            } else {
                $tariff_year = $tariff * pow(1 + $tariff_growth / 100, $y - 1);
            }
            $degradation_factor = pow(1 - $degradation / 100, $y - 1);
            $energy_year = $energy_year1 * $degradation_factor;
            $annual_savings = $energy_year * $tariff_year;

            $annual_savings = $this->applyRegulatoryRules($annual_savings, $tariff_year, $energy_year, $y, $regulatory, [
                'tariff_mode' => $tariff_mode,
                'tariff_te' => $te_value,
                'tariff_tusd' => $tusd_value,
                'tariff_flags' => $flags_value,
                'tariff_growth' => $tariff_growth
            ]);
            $cumulative += $annual_savings;

            $results[] = [
                'year' => $y,
                'energy_generated_kwh' => $energy_year,
                'tariff_value' => $tariff_year,
                'annual_savings' => $annual_savings,
                'cumulative_savings' => $cumulative,
                'degradation_factor' => $degradation_factor
            ];
        }

        return $results;
    }

    public function calculatePayback($investment, $annuals)
    {
        $cumulative = 0;
        foreach ($annuals as $year => $value) {
            $cumulative += $value;
            if ($cumulative >= $investment) {
                $prev = $cumulative - $value;
                $remain = $investment - $prev;
                $months = $value > 0 ? (int)round(($remain / $value) * 12) : 0;
                return ['years' => $year + 1, 'months' => $months];
            }
        }
        return ['years' => 0, 'months' => 0];
    }

    public function calculateNPV($cashflows, $rate)
    {
        $npv = 0;
        foreach ($cashflows as $t => $cf) {
            $npv += $cf / pow(1 + $rate, $t);
        }
        return $npv;
    }

    public function calculateIRR($cashflows)
    {
        $low = -0.9;
        $high = 1.0;
        for ($i = 0; $i < 100; $i++) {
            $mid = ($low + $high) / 2;
            $npv = $this->calculateNPV($cashflows, $mid);
            if (abs($npv) < 0.0001) {
                return $mid;
            }
            if ($npv > 0) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }
        return $mid;
    }

    public function runFullCalculation($project_version_id, $inputs)
    {
        $table12 = $this->db->prefixTable('fv_energy_results_12m');
        $table25 = $this->db->prefixTable('fv_energy_results_25y');
        $tableFin = $this->db->prefixTable('fv_financial_results');

        $this->db->table($table12)->where('project_version_id', $project_version_id)->delete();
        $this->db->table($table25)->where('project_version_id', $project_version_id)->delete();
        $this->db->table($tableFin)->where('project_version_id', $project_version_id)->delete();

        $monthly = $this->calculateMonthlyGeneration($inputs);
        $energy_year1 = array_sum(array_column($monthly, 'energy_generated_kwh'));
        $annuals = $this->calculateAnnualProjection($inputs, $energy_year1);

        foreach ($monthly as $row) {
            $row['project_version_id'] = $project_version_id;
            $row['created_at'] = date('Y-m-d H:i:s');
            $this->db->table($table12)->insert($row);
        }

        foreach ($annuals as $row) {
            $row['project_version_id'] = $project_version_id;
            $row['created_at'] = date('Y-m-d H:i:s');
            $this->db->table($table25)->insert($row);
        }

        $investment = (float)($inputs['investment_value'] ?? 0);
        $opex = (float)($inputs['opex_year'] ?? 0);
        $discount_rate = (float)($inputs['discount_rate_percent'] ?? 0) / 100;
        $annual_savings_year1 = $annuals[0]['annual_savings'] ?? 0;

        $annual_values = array_map(function ($row) {
            return $row['annual_savings'];
        }, $annuals);

        $payback = $this->calculatePayback($investment, $annual_values);

        $cashflows = [];
        $cashflows[] = -$investment;
        foreach ($annuals as $row) {
            $cashflows[] = (float)$row['annual_savings'] - $opex;
        }

        $npv = $this->calculateNPV($cashflows, $discount_rate);
        $irr = $this->calculateIRR($cashflows);

        $economia_media_mensal_lei_14300 = $annual_savings_year1 / 12;
        $payback_lei_14300 = $payback['years'] + ($payback['months'] / 12);
        $total_economizado_25_anos = 0;
        if (!empty($annuals)) {
            $last = end($annuals);
            $total_economizado_25_anos = $last['cumulative_savings'] ?? 0;
        }

        $this->db->table($tableFin)->insert([
            'project_version_id' => $project_version_id,
            'investment_value' => $investment,
            'annual_savings_year1' => $annual_savings_year1,
            'payback_years' => $payback['years'],
            'payback_months' => $payback['months'],
            'irr_percent' => $irr * 100,
            'npv_value' => $npv,
            'economia_media_mensal_lei_14300' => $economia_media_mensal_lei_14300,
            'payback_ano_lei_14300' => $payback_lei_14300,
            'total_economizado_25_anos_lei_14300' => $total_economizado_25_anos,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'generation_annual' => $energy_year1,
            'savings_year1' => $annual_savings_year1,
            'payback' => $payback,
            'irr' => $irr * 100,
            'npv' => $npv
        ];
    }

    private function applyRegulatoryRules($annual_savings, $tariff_year, $energy_year, $year, $snapshot, $tariff_components = [])
    {
        if (!is_array($snapshot)) {
            return $annual_savings;
        }

        $compensation_mode = $snapshot['compensation_mode'] ?? 'full';
        if ($compensation_mode === 'full') {
            return $annual_savings;
        }

        $fio_b_percent = (float)($snapshot['fio_b_percent_tariff'] ?? 0);
        $fio_b_start = (int)($snapshot['fio_b_start_year'] ?? 1);
        $fio_b_ramp = $snapshot['fio_b_ramp'] ?? null;

        if ($year < $fio_b_start) {
            $fio_b_percent = 0;
        } elseif (is_array($fio_b_ramp) && isset($fio_b_ramp[$year])) {
            $fio_b_percent = (float)$fio_b_ramp[$year];
        }

        $fio_b_apply_on = $snapshot['fio_b_apply_on'] ?? 'total';
        if ($tariff_components && ($tariff_components['tariff_mode'] ?? '') === 'components') {
            $growth = (float)($tariff_components['tariff_growth'] ?? 0);
            $te_year = (float)$tariff_components['tariff_te'] * pow(1 + $growth / 100, $year - 1);
            $tusd_year = (float)$tariff_components['tariff_tusd'] * pow(1 + $growth / 100, $year - 1);
            $flags_year = (float)$tariff_components['tariff_flags'] * pow(1 + $growth / 100, $year - 1);

            if ($fio_b_apply_on === 'tusd') {
                $tusd_year = $tusd_year * (1 - $fio_b_percent / 100);
            } else {
                $tariff_year = $tariff_year * (1 - $fio_b_percent / 100);
                $te_year = $tariff_year;
                $tusd_year = 0;
                $flags_year = 0;
            }

            $effective_tariff = max(0, $te_year + $tusd_year + $flags_year);
        } else {
            $fio_b_component = $tariff_year * ($fio_b_percent / 100);
            $effective_tariff = max(0, $tariff_year - $fio_b_component);
        }
        $annual_savings = $energy_year * $effective_tariff;

        $availability_fee_kwh = (float)($snapshot['availability_fee_kwh'] ?? 0);
        if ($availability_fee_kwh > 0) {
            $energy_year = max(0, $energy_year - ($availability_fee_kwh * 12));
            $annual_savings = $energy_year * $effective_tariff;
        }

        $taxes_percent = (float)($snapshot['taxes_percent'] ?? 0);
        if ($taxes_percent > 0) {
            $annual_savings = $annual_savings * (1 - $taxes_percent / 100);
        }

        return $annual_savings;
    }

    private function resolveTariffTotal($inputs)
    {
        $mode = $inputs['tariff_mode'] ?? null;
        if ($mode === 'components') {
            $te = (float)($inputs['tariff_te'] ?? 0);
            $tusd = (float)($inputs['tariff_tusd'] ?? 0);
            $flags = (float)($inputs['tariff_flags'] ?? 0);
            return $te + $tusd + $flags;
        }
        return null;
    }
}
