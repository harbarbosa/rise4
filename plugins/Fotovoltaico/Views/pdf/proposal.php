<?php
$proposal_block = $proposal_block ?: array();
$kit = $kit ?: array();
$technical = $technical ?: array();
$financial = $financial ?: array();
$commercial = $commercial ?: array();
$insolation = $insolation ?: array();
$wizard = $wizard ?: array();
$results = $results ?: array();
$crm = $crm ?: array();

$kit_items = get_array_value($kit, 'items') ?: array();
$monthly_generation = get_array_value($technical, 'monthly_generation') ?: array();
$annual_projection = get_array_value($technical, 'annual_projection') ?: array();
$financial_projection = get_array_value($financial, 'annual_projection') ?: array();
$law_result = get_array_value($technical, 'law_14300') ?: array();
$kit_title = get_array_value($kit, 'title') ?: '-';
$system_power = (float) get_array_value($kit, 'power_kwp');
$annual_generation = (float) get_array_value($technical, 'annual_generation');
$monthly_economy = (float) get_array_value($technical, 'economy_monthly');
$annual_economy = (float) get_array_value($technical, 'economy_annual');
$payback_simple = get_array_value($financial, 'payback_simple_years');
$payback_discounted = get_array_value($financial, 'payback_discounted_years');
$tir = get_array_value($financial, 'tir');
$vpl = (float) get_array_value($financial, 'vpl');
$valid_until = get_array_value($proposal_block, 'valid_until') ?: '-';
$consumer_unit = get_array_value($proposal_block, 'consumer_unit') ?: '-';
$total_value = (float) get_array_value($proposal_block, 'total');
$status = get_array_value($proposal_block, 'status') ?: 'draft';
$proposal_code = get_array_value($proposal_block, 'proposal_code') ?: '-';
$title = get_array_value($proposal_block, 'title') ?: $proposal_code;
?>
<style>
    .fv-pdf {
        font-family: dejavusans, sans-serif;
        color: #1f2937;
        font-size: 10px;
        line-height: 1.45;
    }
    .fv-cover {
        background-color: #0f172a;
        color: #ffffff;
        padding: 22px 24px;
        border-radius: 12px;
        margin-bottom: 16px;
    }
    .fv-cover h1, .fv-cover h2, .fv-section h3 {
        margin: 0;
    }
    .fv-kicker {
        text-transform: uppercase;
        letter-spacing: 1.8px;
        font-size: 8px;
        color: #93c5fd;
        margin-bottom: 6px;
    }
    .fv-cover-grid {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
    }
    .fv-cover-grid td {
        vertical-align: top;
        width: 50%;
        color: #e5e7eb;
        padding: 4px 0;
    }
    .fv-section {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 14px;
    }
    .fv-section-title {
        font-size: 13px;
        font-weight: bold;
        color: #0f172a;
        margin-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 8px;
    }
    .fv-table {
        width: 100%;
        border-collapse: collapse;
    }
    .fv-table th, .fv-table td {
        border: 1px solid #dbe2ea;
        padding: 6px 8px;
        vertical-align: top;
    }
    .fv-table th {
        background: #f8fafc;
        font-weight: bold;
        text-align: left;
    }
    .fv-metrics {
        width: 100%;
        border-collapse: collapse;
    }
    .fv-metrics td {
        padding: 6px 8px;
        border: 1px solid #edf2f7;
    }
    .fv-metric-label {
        width: 55%;
        background: #f8fafc;
        font-weight: bold;
    }
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
    .text-muted {
        color: #64748b;
    }
    .fv-badge {
        display: inline-block;
        padding: 3px 8px;
        background: #e2e8f0;
        border-radius: 999px;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .fv-columns {
        width: 100%;
        border-collapse: collapse;
    }
    .fv-columns td {
        width: 50%;
        vertical-align: top;
        padding: 0 6px 0 0;
    }
</style>

<div class="fv-pdf">
    <div class="fv-cover">
        <div class="fv-kicker">Proposta Fotovoltaica</div>
        <h1 style="font-size:22px; font-weight:bold;"><?php echo esc($title); ?></h1>
        <h2 style="font-size:12px; font-weight:normal; margin-top:6px;"><?php echo esc($proposal_code); ?> | V<?php echo (int) get_array_value($version, 'number'); ?></h2>
        <table class="fv-cover-grid">
            <tr>
                <td>
                    <strong>Cliente / CRM</strong><br>
                    <?php
                    $crm_client = get_array_value($crm, 'client') ?: array();
                    $crm_lead = get_array_value($crm, 'lead') ?: array();
                    $crm_contact = get_array_value($crm, 'contact') ?: array();
                    $crm_name = get_array_value($crm_client, 'company_name') ?: get_array_value($crm_lead, 'company_name') ?: trim((string) get_array_value($crm_contact, 'first_name') . ' ' . (string) get_array_value($crm_contact, 'last_name'));
                    ?>
                    <?php echo esc($crm_name ?: '-'); ?><br>
                    <span class="text-muted"><?php echo esc(get_array_value($crm, 'label') ?: '-'); ?></span>
                </td>
                <td>
                    <strong>Validade</strong><br>
                    <?php echo esc($valid_until); ?><br>
                    <span class="text-muted">Status: <?php echo esc($status); ?></span>
                </td>
            </tr>
        </table>
    </div>

    <table class="fv-columns">
        <tr>
            <td>
                <div class="fv-section">
                    <div class="fv-section-title">Dados do Cliente</div>
                    <table class="fv-metrics">
                        <tr>
                            <td class="fv-metric-label">Cliente / Lead</td>
                            <td><?php echo esc(get_array_value($crm, 'label') ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Unidade consumidora</td>
                            <td><?php echo esc($consumer_unit); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Distribuidora</td>
                            <?php $tariff_block = get_array_value($commercial, 'tariff') ?: array(); ?>
                            <td><?php echo esc(get_array_value($tariff_block, 'distributor_title') ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Consumo médio</td>
                            <td><?php echo number_format((float) get_array_value($proposal_block, 'consumption_avg'), 2, ',', '.'); ?> kWh</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="fv-section">
                    <div class="fv-section-title">Visão Geral do Sistema</div>
                    <table class="fv-metrics">
                        <tr>
                            <td class="fv-metric-label">Potência instalada</td>
                            <td><?php echo number_format($system_power, 2, ',', '.'); ?> kWp</td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Kit</td>
                            <td><?php echo esc($kit_title); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Geração anual estimada</td>
                            <td><?php echo number_format($annual_generation, 2, ',', '.'); ?> kWh</td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Economia anual projetada</td>
                            <td><?php echo to_currency($annual_economy, get_setting('currency_symbol')); ?></td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="fv-section">
        <div class="fv-section-title">Composição Resumida</div>
        <table class="fv-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qtd.</th>
                    <th class="text-right">Unit.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($kit_items)) { ?>
                    <?php foreach ($kit_items as $item) { ?>
                        <tr>
                            <td>
                                <strong><?php echo esc(get_array_value($item, 'product_title') ?: '-'); ?></strong><br>
                                <span class="text-muted"><?php echo esc(get_array_value($item, 'product_type') ?: '-'); ?></span>
                            </td>
                            <td class="text-right"><?php echo number_format((float) get_array_value($item, 'quantity'), 2, ',', '.'); ?></td>
                            <td class="text-right"><?php echo to_currency((float) get_array_value($item, 'unit_price'), get_setting('currency_symbol')); ?></td>
                            <td class="text-right"><?php echo to_currency((float) get_array_value($item, 'total_price'), get_setting('currency_symbol')); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="4" class="text-center text-muted">Nenhum item cadastrado no kit.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <table class="fv-columns">
        <tr>
            <td>
                <div class="fv-section">
                    <div class="fv-section-title">Análise Técnica</div>
                    <table class="fv-metrics">
                        <tr>
                            <td class="fv-metric-label">Geração mensal média</td>
                            <td><?php echo number_format($annual_generation / 12, 2, ',', '.'); ?> kWh</td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Compensação estimada</td>
                            <td><?php echo number_format((float) get_array_value($technical, 'compensation_energy'), 2, ',', '.'); ?> kWh</td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Percentual de offset</td>
                            <td><?php echo number_format((float) get_array_value($technical, 'offset_percent'), 2, ',', '.'); ?>%</td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">PR</td>
                            <td><?php echo number_format((float) get_array_value($technical, 'pr'), 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Perdas</td>
                            <td><?php echo number_format((float) get_array_value($technical, 'losses_percent'), 2, ',', '.'); ?>%</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="fv-section">
                    <div class="fv-section-title">Análise Financeira</div>
                    <table class="fv-metrics">
                        <tr>
                            <td class="fv-metric-label">Payback simples</td>
                            <td><?php echo is_numeric($payback_simple) ? number_format((float) $payback_simple, 2, ',', '.') . ' anos' : '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Payback descontado</td>
                            <td><?php echo is_numeric($payback_discounted) ? number_format((float) $payback_discounted, 2, ',', '.') . ' anos' : '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">TIR</td>
                            <td><?php echo is_numeric($tir) ? number_format((float) $tir * 100, 2, ',', '.') . '%' : '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">VPL</td>
                            <td><?php echo to_currency($vpl, get_setting('currency_symbol')); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Economia mensal</td>
                            <td><?php echo to_currency($monthly_economy, get_setting('currency_symbol')); ?></td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="fv-columns">
        <tr>
            <td>
                <div class="fv-section">
                    <div class="fv-section-title">Lei 14.300</div>
                    <table class="fv-metrics">
                        <tr>
                            <td class="fv-metric-label">Fio B</td>
                            <td><?php echo number_format((float) get_array_value($law_result, 'fio_b_percent') * 100, 2, ',', '.'); ?>%</td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Carga de rede</td>
                            <td><?php echo to_currency((float) get_array_value($law_result, 'grid_charge_value'), get_setting('currency_symbol')); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Economia consolidada</td>
                            <td><?php echo to_currency((float) get_array_value($law_result, 'economy_value'), get_setting('currency_symbol')); ?></td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="fv-section">
                    <div class="fv-section-title">Premissas</div>
                    <table class="fv-metrics">
                        <tr>
                            <td class="fv-metric-label">Insolação anual</td>
                            <td><?php echo number_format((float) get_array_value($insolation, 'annual_insolation'), 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Tarifa</td>
                            <td><?php echo to_currency((float) get_array_value($commercial, 'tariff_value'), get_setting('currency_symbol')); ?></td>
                        </tr>
                        <tr>
                            <td class="fv-metric-label">Vigência tarifa</td>
                            <td><?php echo esc(get_array_value($tariff_block, 'valid_from') ?: '-'); ?> a <?php echo esc(get_array_value($tariff_block, 'valid_to') ?: '-'); ?></td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="fv-section">
        <div class="fv-section-title">Economia Projetada</div>
        <table class="fv-table">
            <thead>
                <tr>
                    <th>Ano</th>
                    <th class="text-right">Geração</th>
                    <th class="text-right">Economia</th>
                    <th class="text-right">Fluxo líquido</th>
                    <th class="text-right">Acumulado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $projection_source = count($financial_projection) ? $financial_projection : $annual_projection;
                $max_rows = min(10, count($projection_source));
                for ($i = 0; $i < $max_rows; $i++) {
                    $row = $projection_source[$i];
                    ?>
                    <tr>
                        <td><?php echo (int) get_array_value($row, 'year'); ?></td>
                        <td class="text-right"><?php echo number_format((float) (get_array_value($row, 'gross_savings') ?: get_array_value($row, 'generation_kwh') ?: 0), 2, ',', '.'); ?></td>
                        <td class="text-right"><?php echo to_currency((float) (get_array_value($row, 'net_cash_flow') ?: get_array_value($row, 'economy_value') ?: 0), get_setting('currency_symbol')); ?></td>
                        <td class="text-right"><?php echo to_currency((float) get_array_value($row, 'net_cash_flow'), get_setting('currency_symbol')); ?></td>
                        <td class="text-right"><?php echo to_currency((float) (get_array_value($row, 'cumulative_cash_flow') ?: get_array_value($row, 'cumulative') ?: 0), get_setting('currency_symbol')); ?></td>
                    </tr>
                <?php } ?>
                <?php if (!$max_rows) { ?>
                    <tr><td colspan="5" class="text-center text-muted">Sem projeção disponível.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="fv-section">
        <div class="fv-section-title">Observações Comerciais</div>
        <div>
            <?php echo nl2br(esc(get_array_value($commercial, 'notes') ?: get_array_value($wizard, 'finance_notes') ?: '-')); ?>
        </div>
    </div>

    <div class="text-center text-muted" style="margin-top:18px; font-size:8px;">
        Documento gerado em <?php echo esc($generated_at); ?> com base no snapshot congelado da versão.
    </div>
</div>
