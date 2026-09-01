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

// Organizar os itens por etapa e consolidar itens repetidos no total geral.
$items_by_section = array();
$grouped_items = array();
foreach ($memory_items as $item) {
    $item_type = (string)($item->item_type ?? 'material');
    $description = trim((string)($item->description_override ?? '')) ?: trim((string)($item->item_title ?? '')) ?: '-';
    $item_id = (int)($item->item_id ?? 0);
    $group_key = $item_type . ':' . ($item_id > 0 ? $item_id : md5($description));
    $section_id = (int)($item->section_id ?? 0);
    $items_by_section[$section_id][] = $item;

    $qty = (float)($item->qty ?? 0);
    $cost_unit = (float)($item->cost_unit ?? 0);
    $sale_unit = (float)($item->sale_unit ?? 0);
    $markup = (float)($item->markup_percent ?? 0);
    $sale_unit_calculated = $sale_unit > 0 ? $sale_unit : ($cost_unit * (1 + $markup / 100));
    $cost_total = $qty * $cost_unit;
    $sale_total = $qty * $sale_unit_calculated;

    if (!isset($grouped_items[$group_key])) {
        $grouped_items[$group_key] = array(
            'description' => $description,
            'item_unit' => (string)($item->item_unit ?? ''),
            'qty' => 0,
            'cost_total' => 0,
            'sale_total' => 0,
        );
    }
    $grouped_items[$group_key]['qty'] += $qty;
    $grouped_items[$group_key]['cost_total'] += $cost_total;
    $grouped_items[$group_key]['sale_total'] += $sale_total;
}

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

<!-- Lista total consolidada -->
<?php if (count($grouped_items) > 0) { ?>
<div class="bg-white p15 mb15 rounded">
    <h5 class="mb10"><?php echo app_lang('proposals_consolidated_items'); ?> (<?php echo count($grouped_items); ?>)</h5>
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
            <?php foreach ($grouped_items as $grouped_item) {
                $average_cost = $grouped_item['qty'] > 0 ? $grouped_item['cost_total'] / $grouped_item['qty'] : 0;
                $average_sale = $grouped_item['qty'] > 0 ? $grouped_item['sale_total'] / $grouped_item['qty'] : 0;
            ?>
            <tr>
                <td><?php echo esc($grouped_item['description']); ?></td>
                <td class="text-end"><?php echo $grouped_item['qty']; ?></td>
                <td class="text-end"><?php echo $grouped_item['item_unit'] ?: 'UN'; ?></td>
                <td class="text-end"><?php echo to_currency($average_cost); ?></td>
                <td class="text-end"><?php echo to_currency($average_sale); ?></td>
                <td class="text-end"><?php echo to_currency($grouped_item['sale_total']); ?></td>
            </tr>
            <?php } ?>
            <tr class="bg-light">
                <td colspan="5"><strong><?php echo app_lang('total'); ?></strong></td>
                <td class="text-end"><strong><?php echo to_currency($total_sale); ?></strong></td>
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
    <?php foreach ($sections as $section) {
        $section_id = (int)$section->id;
        $section_items = $items_by_section[$section_id] ?? array();
    ?>
        <div class="border rounded p15 mb15">
            <h5 class="mb5"><?php echo esc($section->title); ?></h5>
            <?php if (!empty($section->description)) { ?><p class="text-muted mb10"><?php echo nl2br(esc($section->description)); ?></p><?php } ?>
            <?php if ($section_items) { ?>
                <table class="table table-bordered table-sm mb0">
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
                        <?php foreach ($section_items as $item) {
                            $qty = (float)($item->qty ?? 0);
                            $cost_unit = (float)($item->cost_unit ?? 0);
                            $sale_unit = (float)($item->sale_unit ?? 0);
                            $markup = (float)($item->markup_percent ?? 0);
                            $calculated_sale_unit = $sale_unit > 0 ? $sale_unit : ($cost_unit * (1 + $markup / 100));
                            $description = trim((string)($item->description_override ?? '')) ?: trim((string)($item->item_title ?? '')) ?: '-';
                        ?>
                            <tr>
                                <td><?php echo esc($description); ?></td>
                                <td class="text-end"><?php echo $qty; ?></td>
                                <td class="text-end"><?php echo !empty($item->item_unit) ? esc($item->item_unit) : 'UN'; ?></td>
                                <td class="text-end"><?php echo to_currency($cost_unit); ?></td>
                                <td class="text-end"><?php echo to_currency($calculated_sale_unit); ?></td>
                                <td class="text-end"><?php echo to_currency($qty * $calculated_sale_unit); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="text-muted"><?php echo app_lang('proposals_no_items_in_section'); ?></div>
            <?php } ?>
        </div>
    <?php }
    $unassigned_items = $items_by_section[0] ?? array();
    if ($unassigned_items) { ?>
        <div class="border rounded p15">
            <h5 class="mb10"><?php echo app_lang('proposals_unassigned_items'); ?></h5>
            <div class="text-muted"><?php echo count($unassigned_items); ?> <?php echo app_lang('proposals_unassigned_items_count'); ?></div>
        </div>
    <?php } ?>
</div>
<?php } ?>
