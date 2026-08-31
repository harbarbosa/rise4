<?php
$proposal_id = $project_info->proposal_id ?? 0;
if (!$proposal_id) {
    echo "<div class='text-center text-muted p20'>" . app_lang('no_proposal_linked') . "</div>";
    return;
}

// Carregar dados da proposta
$proposals_model = model('Proposals\Models\Proposals_model');
$sections_model = model('Proposals\Models\Proposal_sections_model');
$items_model = model('Proposals\Models\Proposal_items_model');

$proposal = $proposals_model->get_one($proposal_id);
if (!$proposal) {
    echo "<div class='text-center text-muted p20'>" . app_lang('record_not_found') . "</div>";
    return;
}

// Buscar itens da memória de cálculo
$memory_items = $items_model->get_details([
    'proposal_id' => $proposal_id,
    'in_memory' => 1,
    'deleted' => 0
])->getResult();

// Buscar seções (etapas)
$sections = $sections_model->get_details([
    'proposal_id' => $proposal_id
])->getResult();

// Calcular totais
$total_material = 0;
$total_service = 0;
$total_sale = 0;

foreach ($memory_items as $item) {
    $qty = (float)$item->qty;
    $sale_unit = (float)($item->sale_unit ?? 0);
    $cost_unit = (float)($item->cost_unit ?? 0);
    $markup = (float)($item->markup_percent ?? 0);
    
    $sale_total = $qty * ($sale_unit > 0 ? $sale_unit : ($cost_unit * (1 + $markup / 100)));
    $cost_total = $qty * $cost_unit;
    
    if ($item->item_type === 'service') {
        $total_service += $cost_total;
    } else {
        $total_material += $cost_total;
    }
    $total_sale += $sale_total;
}

$total_cost = $total_material + $total_service;
$profit = $total_sale - $total_cost;
$profit_pct = $total_sale > 0 ? ($profit / $total_sale) * 100 : 0;
?>

<div class="bg-white p15 mb15 rounded">
    <div class="d-flex justify-content-between align-items-center mb15">
        <h4 class="mb0"><?php echo app_lang('proposal') . " #" . str_pad($proposal_id, 3, "0", STR_PAD_LEFT); ?></h4>
        <?php echo anchor(get_uri("proposals/view/" . $proposal_id), "<i data-feather='external-link' class='icon-16'></i> " . app_lang('view_proposal'), ["class" => "btn btn-default btn-sm", "target" => "_blank"]); ?>
    </div>
    
    <?php if ($proposal->title) { ?>
    <p><strong><?php echo $proposal->title; ?></strong></p>
    <?php } ?>
</div>

<!-- Resumo Financeiro -->
<div class="bg-white p15 mb15 rounded">
    <h5 class="mb10"><?php echo app_lang('proposals_dash_summary'); ?></h5>
    <div class="row">
        <div class="col-md-3">
            <div class="text-muted"><?php echo app_lang('proposals_cost_label'); ?></div>
            <div class="font-highlight"><?php echo to_currency($total_cost); ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted"><?php echo app_lang('proposals_sale_label'); ?></div>
            <div class="font-highlight"><?php echo to_currency($total_sale); ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted"><?php echo app_lang('proposals_dash_net_profit'); ?></div>
            <div class="font-highlight<?php echo $profit >= 0 ? ' text-success' : ' text-danger'; ?>"><?php echo to_currency($profit); ?> (<?php echo number_format($profit_pct, 1, ",", "."); ?>%)</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted"><?php echo app_lang('items'); ?></div>
            <div class="font-highlight"><?php echo count($memory_items); ?></div>
        </div>
    </div>
</div>

<!-- Recursos de Materiais -->
<?php
$material_items = array_filter($memory_items, function($i) { return $i->item_type !== 'service'; });
$service_items = array_filter($memory_items, function($i) { return $i->item_type === 'service'; });
?>

<?php if (count($material_items) > 0) { ?>
<div class="bg-white p15 mb15 rounded">
    <h5 class="mb10"><?php echo app_lang('proposals_materials'); ?> (<?php echo count($material_items); ?>)</h5>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th><?php echo app_lang('item'); ?></th>
                <th class="text-end"><?php echo app_lang('quantity'); ?></th>
                <th class="text-end"><?php echo app_lang('unit'); ?></th>
                <th class="text-end"><?php echo app_lang('proposals_cost_unit'); ?></th>
                <th class="text-end"><?php echo app_lang('proposals_sale_unit'); ?></th>
                <th class="text-end"><?php echo app_lang('total'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $material_total = 0;
            foreach ($material_items as $item) { 
                $qty = (float)$item->qty;
                $sale_unit = (float)($item->sale_unit ?? 0);
                $cost_unit = (float)($item->cost_unit ?? 0);
                $markup = (float)($item->markup_percent ?? 0);
                $sale_total = $qty * ($sale_unit > 0 ? $sale_unit : ($cost_unit * (1 + $markup / 100)));
                $material_total += $sale_total;
                $description = $item->description_override ?: ($item->item_title ?? '-');
            ?>
            <tr>
                <td><?php echo $description; ?></td>
                <td class="text-end"><?php echo $qty; ?></td>
                <td class="text-end"><?php echo $item->unit ?? 'UN'; ?></td>
                <td class="text-end"><?php echo to_currency($cost_unit); ?></td>
                <td class="text-end"><?php echo to_currency($sale_unit); ?></td>
                <td class="text-end"><?php echo to_currency($sale_total); ?></td>
            </tr>
            <?php } ?>
            <tr class="bg-light">
                <td colspan="5"><strong><?php echo app_lang('total'); ?></strong></td>
                <td class="text-end"><strong><?php echo to_currency($material_total); ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>
<?php } ?>

<!-- Recursos de Pessoas (Serviços) -->
<?php if (count($service_items) > 0) { ?>
<div class="bg-white p15 mb15 rounded">
    <h5 class="mb10"><?php echo app_lang('proposals_services'); ?> (<?php echo count($service_items); ?>)</h5>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th><?php echo app_lang('item'); ?></th>
                <th class="text-end"><?php echo app_lang('quantity'); ?></th>
                <th class="text-end"><?php echo app_lang('unit'); ?></th>
                <th class="text-end"><?php echo app_lang('proposals_cost_unit'); ?></th>
                <th class="text-end"><?php echo app_lang('proposals_sale_unit'); ?></th>
                <th class="text-end"><?php echo app_lang('total'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $service_total = 0;
            foreach ($service_items as $item) { 
                $qty = (float)$item->qty;
                $sale_unit = (float)($item->sale_unit ?? 0);
                $cost_unit = (float)($item->cost_unit ?? 0);
                $markup = (float)($item->markup_percent ?? 0);
                $sale_total = $qty * ($sale_unit > 0 ? $sale_unit : ($cost_unit * (1 + $markup / 100)));
                $service_total += $sale_total;
                $description = $item->description_override ?: ($item->item_title ?? '-');
            ?>
            <tr>
                <td><?php echo $description; ?></td>
                <td class="text-end"><?php echo $qty; ?></td>
                <td class="text-end"><?php echo $item->unit ?? 'UN'; ?></td>
                <td class="text-end"><?php echo to_currency($cost_unit); ?></td>
                <td class="text-end"><?php echo to_currency($sale_unit); ?></td>
                <td class="text-end"><?php echo to_currency($sale_total); ?></td>
            </tr>
            <?php } ?>
            <tr class="bg-light">
                <td colspan="5"><strong><?php echo app_lang('total'); ?></strong></td>
                <td class="text-end"><strong><?php echo to_currency($service_total); ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>
<?php } ?>

<!-- Etapas da Proposta -->
<?php if (count($sections) > 0) { ?>
<div class="bg-white p15 rounded">
    <h5 class="mb10"><?php echo app_lang('proposals_sections'); ?> (<?php echo count($sections); ?>)</h5>
    <p class="text-muted"><?php echo app_lang('import_sections_hint') ?: 'Edite o projeto e marque para importar as etapas como milestones.'; ?></p>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th><?php echo app_lang('proposals_section'); ?></th>
                <th><?php echo app_lang('description'); ?></th>
                <th class="text-center"><?php echo app_lang('items'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sections_model->db = db_connect();
            foreach ($sections as $section) {
                $section_items = $items_model->get_details([
                    'proposal_id' => $proposal_id,
                    'section_id' => $section->id,
                    'deleted' => 0
                ])->getResult();
            ?>
            <tr>
                <td><?php echo $section->title; ?></td>
                <td><?php echo $section->description ? nl2br($section->description) : '-'; ?></td>
                <td class="text-center"><?php echo count($section_items); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>
