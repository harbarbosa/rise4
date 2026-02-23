<?php
$client = $client ?? null;
$kit = $kit ?? null;
$kit_items = $kit_items ?? [];
$monthly = $monthly ?? [];
$financial = $financial ?? null;
$annual_generation = $annual_generation ?? 0;
$total_value = 0;
foreach ($kit_items as $item) {
    $qty = (float)$item['qty'];
    $price = $item['item_type'] === 'custom' ? (float)$item['price'] : (float)$item['product_price'];
    $total_value += $price * $qty;
}
?>
<div style="font-family: Arial, sans-serif; color:#1f2933;">
    <div style="text-align:center; padding:20px 0;">
        <div style="font-size:26px; font-weight:bold;">AlfaHP Energia Solar</div>
        <div style="font-size:14px; color:#52606d;">Proposta Fotovoltaica</div>
    </div>

    <hr/>

    <h3 style="margin-bottom:5px;">Resumo do Cliente</h3>
    <table width="100%" style="font-size:12px;">
        <tr>
            <td width="50%"><strong>Cliente:</strong> <?php echo esc($client->company_name ?? '-'); ?></td>
            <td width="50%"><strong>Projeto:</strong> <?php echo esc($project->title); ?></td>
        </tr>
        <tr>
            <td><strong>Cidade/UF:</strong> <?php echo esc(($project->city ?? '-') . '/' . ($project->state ?? '-')); ?></td>
            <td><strong>Status:</strong> <?php echo esc($project->status); ?></td>
        </tr>
        <tr>
            <td><strong>Data:</strong> <?php echo date('d/m/Y'); ?></td>
            <td><strong>Responsável:</strong> AlfaHP</td>
        </tr>
    </table>

    <hr/>

    <h3>Kit (BOM)</h3>
    <?php if (!$kit) { ?>
        <p style="font-size:12px; color:#7b8794;">Kit não vinculado à proposta. Selecione um kit ao gerar o PDF.</p>
    <?php } else { ?>
        <p style="font-size:12px;"><strong>Kit:</strong> <?php echo esc($kit->name); ?></p>
        <table width="100%" border="1" cellspacing="0" cellpadding="4" style="font-size:11px; border-collapse:collapse;">
            <thead>
                <tr style="background:#f2f4f7;">
                    <th align="left">Item</th>
                    <th align="left">Tipo</th>
                    <th align="right">Qtd</th>
                    <th align="right">Preço Unit</th>
                    <th align="right">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($kit_items as $item) { ?>
                <?php
                $name = $item['item_type'] === 'custom'
                    ? ($item['name'] ?: 'Item custom')
                    : trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''));
                $price = $item['item_type'] === 'custom' ? (float)$item['price'] : (float)$item['product_price'];
                $qty = (float)$item['qty'];
                ?>
                <tr>
                    <td><?php echo esc($name); ?></td>
                    <td><?php echo esc($item['item_type']); ?></td>
                    <td align="right"><?php echo number_format($qty, 2, ',', '.'); ?></td>
                    <td align="right"><?php echo number_format($price, 2, ',', '.'); ?></td>
                    <td align="right"><?php echo number_format($price * $qty, 2, ',', '.'); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <p style="text-align:right; font-size:12px;"><strong>Total do Kit:</strong> <?php echo number_format($total_value, 2, ',', '.'); ?></p>
    <?php } ?>

    <hr/>

    <h3>Geração e Economia</h3>
    <table width="100%" border="1" cellspacing="0" cellpadding="4" style="font-size:11px; border-collapse:collapse;">
        <thead>
            <tr style="background:#f2f4f7;">
                <th>Mês</th>
                <th>Geração (kWh)</th>
                <th>Economia (R$)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthly as $row) { ?>
                <tr>
                    <td align="center"><?php echo esc($row['month']); ?></td>
                    <td align="right"><?php echo number_format((float)$row['energy_generated_kwh'], 2, ',', '.'); ?></td>
                    <td align="right"><?php echo number_format((float)$row['savings_value'], 2, ',', '.'); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <p style="font-size:12px;"><strong>Geração anual estimada:</strong> <?php echo number_format((float)$annual_generation, 2, ',', '.'); ?> kWh</p>

    <hr/>

    <h3>Retorno Financeiro</h3>
    <table width="100%" style="font-size:12px;">
        <tr>
            <td><strong>Economia anual (ano 1):</strong> <?php echo $financial ? number_format((float)$financial->annual_savings_year1, 2, ',', '.') : '-'; ?></td>
            <td><strong>Payback:</strong> <?php echo $financial ? ($financial->payback_years . 'a ' . $financial->payback_months . 'm') : '-'; ?></td>
        </tr>
        <tr>
            <td><strong>TIR:</strong> <?php echo $financial ? number_format((float)$financial->irr_percent, 2, ',', '.') . '%' : '-'; ?></td>
            <td><strong>VPL:</strong> <?php echo $financial ? number_format((float)$financial->npv_value, 2, ',', '.') : '-'; ?></td>
        </tr>
    </table>

    <hr/>

    <h3>Condições Comerciais</h3>
    <ul style="font-size:12px;">
        <li>Prazo de validade da proposta: 15 dias.</li>
        <li>Condições de pagamento: à combinar.</li>
        <li>Prazo estimado de instalação: 30 a 45 dias após assinatura.</li>
    </ul>

    <p style="font-size:11px; color:#7b8794;">Este documento é uma proposta comercial e pode sofrer alterações conforme atualização de tarifas e condições técnicas.</p>
</div>
