<?php
$proposal_info = $proposal_info ?? (object) array();
$sections = $sections ?? array();
$items = $items ?? array();
$memory_items = $memory_items ?? array();
$proposal_items = $proposal_items ?? array();
$can_manage = $can_manage ?? false;

if (!function_exists('proposals_render_section_options')) {
    function proposals_render_section_options($sections, $parent_id = null, $level = 0)
    {
        $html = '';
        foreach ($sections as $section) {
            $pid = $section->parent_id ?? null;
            if ((string)$pid !== (string)$parent_id) {
                continue;
            }
            $label = str_repeat('-- ', $level) . ($section->title ?? app_lang('proposals_section'));
            $html .= "<option value=\"" . (int)$section->id . "\">" . esc($label) . "</option>";
            $html .= proposals_render_section_options($sections, $section->id, $level + 1);
        }
        return $html;
    }
}

$sections_dropdown_html = "<option value=''>" . app_lang('proposals_select_section') . "</option>";
$sections_dropdown_html .= proposals_render_section_options($sections, null, 0);
$default_desired_date = date('Y-m-d');
$requestable_items = array();
foreach ($items as $proposal_item) {
    if (($proposal_item->item_type ?? 'material') !== 'material') {
        continue;
    }
    $requestable_items[] = array(
        'id' => (int)($proposal_item->id ?? 0),
        'title' => trim((string)(($proposal_item->item_title ?? app_lang('item')) . (($proposal_item->description_override ?? '') ? ' - ' . $proposal_item->description_override : ''))),
        'qty' => (float)($proposal_item->qty ?? 0),
        'unit' => trim((string)($proposal_item->item_unit ?? 'UN')),
        'item_type' => $proposal_item->item_type ?? 'material'
    );
}
?>

<style type="text/css">
    .proposal-items-table {
        table-layout: fixed;
        width: 100%;
    }
    .proposal-items-table th,
    .proposal-items-table td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .proposal-items-table th:last-child,
    .proposal-items-table td:last-child {
        overflow: visible;
        text-overflow: clip;
        white-space: nowrap;
    }
    .proposal-section {
        border: 1px solid #eef1f5;
        box-shadow: none;
    }
    .proposal-section > .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #eef1f5;
    }
    .proposal-section > .card-header .section-title-text,
    .proposal-section > .card-header strong {
        color: #1f4e79;
        font-weight: 700;
        font-size: 15px;
    }
    .proposal-subsections {
        margin-top: 10px;
        padding-left: 14px;
        border-left: 2px solid #eef1f5;
    }
    .proposal-items-table {
        border-color: #eef1f5;
    }
    .proposal-items-table th,
    .proposal-items-table td {
        border-color: #eef1f5;
    }
    .proposal-items-table thead th {
        background: #f9fbfd;
        font-weight: 600;
    }
    .proposal-items-table tbody tr:nth-child(even) {
        background: #fcfdff;
    }
    .proposal-items-table .item-display-text {
        color: #2f6f4e;
    }
    .proposal-items-table .select2-container {
        max-width: 100%;
    }
    .proposal-items-table .select2-container .select2-selection--single {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .proposal-items-table .select2-container .select2-selection__rendered {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .proposal-items-table .edit-section-title,
    .proposal-items-table .item-edit,
    .proposal-section .edit-section-title {
        padding: 2px 4px;
    }
    .proposal-items-table .edit-section-title .icon-16,
    .proposal-items-table .item-edit .icon-16,
    .proposal-section .edit-section-title .icon-16 {
        width: 8px;
        height: 8px;
    }
    .proposal-items-table .item-display-text {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }
    .proposal-document-preview {
        border: 1px solid #eef1f5;
        background: #fff;
        padding: 15px;
        border-radius: 4px;
        min-height: 200px;
    }
    #proposal-document-description,
    #proposal-document-payment,
    #proposal-document-observations {
        min-height: 240px;
        height: auto;
        resize: vertical;
    }
    .proposal-doc-table th,
    .proposal-doc-table td {
        font-size: 12px;
    }
    .proposal-summary-grid .label {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 2px;
    }
    .proposal-summary-grid .value {
        font-weight: 600;
        color: #2c3e50;
    }
    .proposal-dashboard-hero {
        background: linear-gradient(135deg, #f4f8fc 0%, #eef6f1 100%);
        border: 1px solid #dfe9e4;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 18px;
    }
    .proposal-dashboard-kicker {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #5f7486;
        margin-bottom: 8px;
    }
    .proposal-dashboard-title {
        font-size: 26px;
        line-height: 1.2;
        font-weight: 700;
        color: #203347;
        margin-bottom: 10px;
    }
    .proposal-dashboard-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .proposal-dashboard-pill {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .85);
        border: 1px solid #dde7f0;
        color: #41576b;
        font-size: 12px;
        font-weight: 600;
    }
    .proposal-stat-card {
        height: 100%;
        border: 1px solid #e7edf3;
        border-radius: 12px;
        background: #fff;
        padding: 16px;
        box-shadow: 0 8px 18px rgba(27, 39, 51, .04);
    }
    .proposal-stat-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #7a8998;
        margin-bottom: 8px;
    }
    .proposal-stat-value {
        font-size: 24px;
        line-height: 1.1;
        font-weight: 700;
        color: #1f2f3f;
    }
    .proposal-stat-note {
        margin-top: 8px;
        font-size: 12px;
        color: #70808f;
    }
    .proposal-info-card {
        border: 1px solid #e7edf3;
        border-radius: 12px;
        background: #fff;
        padding: 16px;
        height: 100%;
    }
    .proposal-info-card-title {
        font-size: 13px;
        font-weight: 700;
        color: #203347;
        margin-bottom: 12px;
    }
    .proposal-info-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
    }
    .proposal-info-item {
        min-width: 0;
    }
    .proposal-info-item .label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #81909e;
        margin-bottom: 4px;
    }
    .proposal-info-item .value {
        font-size: 14px;
        font-weight: 600;
        color: #25384a;
        word-break: break-word;
    }
    .proposal-breakdown {
        background: #f8fafc;
        border: 1px solid #eef1f5;
        border-radius: 6px;
        padding: 12px;
    }
    .proposal-breakdown-bar {
        height: 12px;
        border-radius: 10px;
        overflow: hidden;
        background: #e9edf3;
        display: flex;
    }
    .proposal-breakdown-bar span {
        display: block;
        height: 100%;
        cursor: help;
    }
    .proposal-breakdown-legend {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 10px;
        font-size: 12px;
    }
    .proposal-breakdown-legend .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    .proposal-approve-request-section {
        border: 1px solid #eef1f5;
        border-radius: 6px;
        padding: 12px;
        margin-top: 12px;
        background: #fbfdff;
    }
    .proposal-approve-request-section .table td,
    .proposal-approve-request-section .table th {
        vertical-align: middle;
    }
    @media (max-width: 767px) {
        .proposal-dashboard-title {
            font-size: 22px;
        }
        .proposal-info-list {
            grid-template-columns: 1fr;
        }
        .proposal-stat-value {
            font-size: 20px;
        }
    }
</style>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('proposals_view'); ?></h1>
            <div class="title-button-group">
                <button type="button" class="btn btn-default" id="proposal-document-print">
                    <i data-feather="printer" class="icon-16"></i> <?php echo app_lang('print'); ?>
                </button>
                <?php if ($can_manage && !empty($proposal_info->id)) { ?>
                    <button type="button" class="btn btn-success" id="proposal-approve-button" data-bs-toggle="modal" data-bs-target="#proposal-approve-modal">
                        Aprovar proposta
                    </button>
                    <button type="button" class="btn btn-default" id="proposal-duplicate-button">
                        Duplicar proposta
                    </button>
                <?php } ?>
                <?php if (!empty($can_manage)) { ?>
                    <select id="proposal-status-select" class="form-select">
                        <?php foreach (($status_options ?? array()) as $status_option) { ?>
                            <option value="<?php echo esc($status_option['id']); ?>" <?php echo ($proposal_info->status ?? 'draft') === $status_option['id'] ? 'selected' : ''; ?>>
                                <?php echo esc($status_option['text']); ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } ?>
                <?php if ($can_manage && !empty($proposal_info->id)) { ?>
                    <?php echo modal_anchor(get_uri('propostas/modal_form/' . $proposal_info->id), app_lang('edit'), array('class' => 'btn btn-default', 'title' => app_lang('edit'))); ?>
                <?php } ?>
                <?php echo anchor(get_uri('propostas'), app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs bg-white title" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#proposal-dashboard" role="tab">
                        <?php echo app_lang('proposals_overview'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#proposal-memory" role="tab">
                        <?php echo app_lang('proposals_memory'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#proposal-items" role="tab">
                        <?php echo app_lang('proposals_proposal_items'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#proposal-document" role="tab">
                        <?php echo app_lang('proposals_document'); ?>
                    </a>
                </li>
            </ul>

            <div class="tab-content p15">
                <div class="tab-pane fade show active" id="proposal-dashboard" role="tabpanel">
                    <?php
                    $dash = $dashboard_data ?? array();
                    $client_label = $proposal_info->client_company ?? ($proposal_info->client_name ?? '-');
                    $commission_type = $proposal_info->commission_type ?? 'percent';
                    $commission_value = $proposal_info->commission_value ?? 0;
                    $commission_label = $commission_type === 'fixed'
                        ? to_currency($commission_value)
                        : number_format((float)$commission_value, 2, ",", ".") . '%';
                    ?>
                    <div class="proposal-dashboard-hero">
                        <div class="proposal-dashboard-kicker">Visão Geral da Proposta</div>
                        <div class="proposal-dashboard-title"><?php echo esc($proposal_info->title ?? '-'); ?></div>
                        <div class="proposal-dashboard-meta">
                            <span class="proposal-dashboard-pill"><?php echo esc($client_label ?: '-'); ?></span>
                            <span class="proposal-dashboard-pill"><?php echo $dash['status'] ?? '-'; ?></span>
                            <span class="proposal-dashboard-pill"><?php echo app_lang('proposals_validity_days'); ?>: <?php echo esc($proposal_info->validity_days ?? '-'); ?></span>
                            <span class="proposal-dashboard-pill"><?php echo app_lang('proposals_dash_updated_at'); ?>: <?php echo $dash['updated_at'] ?? '-'; ?></span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_total_sale'); ?></div>
                                <div class="proposal-stat-value" id="proposal-dash-total-sale"><?php echo $dash['total_sale'] ?? '-'; ?></div>
                                <div class="proposal-stat-note">Valor final considerado na proposta.</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_cost_total'); ?></div>
                                <div class="proposal-stat-value"><?php echo to_currency((float)(($dash['total_cost_material_n'] ?? 0) + ($dash['total_cost_service_n'] ?? 0))); ?></div>
                                <div class="proposal-stat-note">Materiais e serviços somados.</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_net_profit'); ?></div>
                                <div class="proposal-stat-value" id="proposal-dash-net-profit"><?php echo $dash['net_profit'] ?? '-'; ?></div>
                                <div class="proposal-stat-note">Resultado líquido após impostos e comissão.</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_markup_avg'); ?></div>
                                <div class="proposal-stat-value" id="proposal-dash-markup"><?php echo $dash['markup_avg'] ?? '-'; ?></div>
                                <div class="proposal-stat-note">Markup médio aplicado sobre o custo.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb15">
                        <div class="col-md-7 mb15">
                            <div class="proposal-info-card proposal-summary-grid">
                                <div class="proposal-info-card-title">Resumo comercial</div>
                                <div class="proposal-info-list">
                                    <div class="proposal-info-item">
                                        <div class="label"><?php echo app_lang('client'); ?></div>
                                        <div class="value"><?php echo esc($client_label ?: '-'); ?></div>
                                    </div>
                                    <div class="proposal-info-item">
                                        <div class="label"><?php echo app_lang('status'); ?></div>
                                        <div class="value" id="proposal-dash-status"><?php echo $dash['status'] ?? '-'; ?></div>
                                    </div>
                                    <div class="proposal-info-item">
                                        <div class="label"><?php echo app_lang('proposals_commission'); ?></div>
                                        <div class="value"><?php echo esc($commission_label); ?></div>
                                    </div>
                                    <div class="proposal-info-item">
                                        <div class="label"><?php echo app_lang('proposals_tax_product_percent'); ?></div>
                                        <div class="value"><?php echo number_format((float)($proposal_info->tax_product_percent ?? 0), 2, ",", "."); ?>%</div>
                                    </div>
                                    <div class="proposal-info-item">
                                        <div class="label"><?php echo app_lang('proposals_tax_service_percent'); ?></div>
                                        <div class="value"><?php echo number_format((float)($proposal_info->tax_service_percent ?? 0), 2, ",", "."); ?>%</div>
                                    </div>
                                    <div class="proposal-info-item">
                                        <div class="label"><?php echo app_lang('proposals_tax_service_only'); ?></div>
                                        <div class="value"><?php echo !empty($proposal_info->tax_service_only) ? app_lang('yes') : app_lang('no'); ?></div>
                                    </div>
                                    <div class="proposal-info-item">
                                        <div class="label"><?php echo app_lang('proposals_dash_created_by'); ?></div>
                                        <div class="value" id="proposal-dash-created-by"><?php echo $dash['created_by'] ?? '-'; ?></div>
                                    </div>
                                    <div class="proposal-info-item">
                                        <div class="label"><?php echo app_lang('proposals_dash_updated_at'); ?></div>
                                        <div class="value" id="proposal-dash-updated"><?php echo $dash['updated_at'] ?? '-'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 mb15">
                            <div class="proposal-breakdown h-100"
                                data-total="<?php echo (float)($dash['total_sale_n'] ?? 0); ?>"
                                data-cost="<?php echo (float)(($dash['total_cost_material_n'] ?? 0) + ($dash['total_cost_service_n'] ?? 0)); ?>"
                                data-tax="<?php echo (float)($dash['taxes_total_n'] ?? 0); ?>"
                                data-commission="<?php echo (float)($dash['commission_total_n'] ?? 0); ?>"
                                data-profit="<?php echo (float)($dash['net_profit_n'] ?? 0); ?>">
                                <div class="proposal-info-card-title mb10"><?php echo app_lang('proposals_dash_breakdown'); ?></div>
                                <div class="proposal-breakdown-bar">
                                    <span class="breakdown-cost" style="background:#f39c12;"></span>
                                    <span class="breakdown-tax" style="background:#e74c3c;"></span>
                                    <span class="breakdown-commission" style="background:#8e44ad;"></span>
                                    <span class="breakdown-profit" style="background:#2ecc71;"></span>
                                </div>
                                <div class="proposal-breakdown-legend">
                                    <div><span class="dot" style="background:#f39c12;"></span><?php echo app_lang('proposals_dash_cost_total'); ?></div>
                                    <div><span class="dot" style="background:#e74c3c;"></span><?php echo app_lang('proposals_dash_taxes'); ?></div>
                                    <div><span class="dot" style="background:#8e44ad;"></span><?php echo app_lang('proposals_dash_commission'); ?></div>
                                    <div><span class="dot" style="background:#2ecc71;"></span><?php echo app_lang('proposals_dash_net_profit'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_cost_material'); ?></div>
                                <div class="proposal-stat-value" id="proposal-dash-cost-material"><?php echo $dash['total_cost_material'] ?? '-'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_cost_service'); ?></div>
                                <div class="proposal-stat-value" id="proposal-dash-cost-service"><?php echo $dash['total_cost_service'] ?? '-'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_taxes'); ?></div>
                                <div class="proposal-stat-value" id="proposal-dash-taxes"><?php echo $dash['taxes_total'] ?? '-'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_commission'); ?></div>
                                <div class="proposal-stat-value" id="proposal-dash-commission"><?php echo $dash['commission_total'] ?? '-'; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_gross_profit'); ?></div>
                                <div class="proposal-stat-value" id="proposal-dash-gross-profit"><?php echo $dash['gross_profit'] ?? '-'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label">Impostos + comissão</div>
                                <div class="proposal-stat-value"><?php echo to_currency((float)(($dash['taxes_total_n'] ?? 0) + ($dash['commission_total_n'] ?? 0))); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label">Resultado antes de taxas</div>
                                <div class="proposal-stat-value"><?php echo to_currency((float)($dash['gross_profit_n'] ?? 0)); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb15">
                            <div class="proposal-stat-card">
                                <div class="proposal-stat-label"><?php echo app_lang('proposals_dash_created_by'); ?></div>
                                <div class="proposal-stat-value" style="font-size:18px"><?php echo $dash['created_by'] ?? '-'; ?></div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $can_show_reminders = function_exists('can_access_reminders_module') ? can_access_reminders_module() : false;
                    $task_col_class = $can_show_reminders ? "col-md-6" : "col-md-12";
                    ?>
                    <div class="row mt20">
                        <div class="<?php echo $task_col_class; ?>">
                            <?php
                            echo view("Proposals\\Views\\proposals\\tasks\\index", array(
                                "proposal_id" => (int)($proposal_info->id ?? 0)
                            ));
                            ?>
                        </div>
                        <?php if ($can_show_reminders) { ?>
                            <div class="col-md-6">
                                <div class="card reminders-card" id="proposal-reminders">
                                    <div class="card-header fw-bold">
                                        <i data-feather="clock" class="icon-16"></i> &nbsp;<?php echo app_lang("reminders") . " (" . app_lang('private') . ")"; ?>
                                    </div>
                                    <div class="card-body">
                                        <?php echo view("Proposals\\Views\\proposals\\reminders_view_data", array(
                                            "plugin_proposal_id" => (int)($proposal_info->id ?? 0),
                                            "hide_form" => true
                                        )); ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="tab-pane fade" id="proposal-memory" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb15">
                        <div class="text-muted">
                            <?php echo app_lang('proposals_memory_hint'); ?>
                        </div>
                        <?php if ($can_manage) { ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i data-feather="plus" class="icon-16"></i> <?php echo app_lang('add'); ?>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="#" data-action="add-section"><?php echo app_lang('proposals_add_section'); ?></a>
                                    <a class="dropdown-item" href="#" data-action="add-item"><?php echo app_lang('proposals_add_item'); ?></a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <div id="proposal-memory-sections"></div>

                    <div class="mt20 text-end">
                        <strong><?php echo app_lang('proposals_total_cost'); ?>:</strong>
                        <span id="proposal-memory-total-cost">0,00</span>
                        | <strong><?php echo app_lang('proposals_total_general'); ?>:</strong>
                        <span id="proposal-memory-total">0,00</span>
                    </div>
                </div>
                <div class="tab-pane fade" id="proposal-items" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb15">
                        <div class="text-muted">
                            <?php echo app_lang('proposals_proposal_items_hint'); ?>
                        </div>
                        <?php if ($can_manage) { ?>
                            <button type="button" class="btn btn-primary" id="proposal-items-add-item">
                                <i data-feather="plus" class="icon-16"></i> <?php echo app_lang('proposals_add_item'); ?>
                            </button>
                        <?php } ?>
                    </div>

                    <?php if ($can_manage) { ?>
                        <button type="button" class="btn btn-outline-primary mb15" id="proposal-items-copy-from-memory">
                            <?php echo app_lang('proposals_copy_from_memory'); ?>
                        </button>
                    <?php } ?>

                    <div class="table-responsive">
                        <table class="table table-bordered proposal-items-table mb10" id="proposal-proposal-items-table">
                            <thead>
                                <tr>
                                    <th style="width:40%"><?php echo app_lang('item'); ?></th>
                                    <th style="width:12%" class="text-end"><?php echo app_lang('quantity'); ?></th>
                                    <th style="width:12%"><?php echo app_lang('unit'); ?></th>
                                    <th style="width:16%" class="text-end"><?php echo app_lang('proposals_sale_unit'); ?></th>
                                    <th style="width:12%" class="text-end"><?php echo app_lang('total'); ?></th>
                                    <th style="width:8%"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="text-center text-muted mb10 hide" id="proposal-proposal-items-empty">
                        <?php echo app_lang('proposals_no_proposal_items'); ?>
                    </div>

                    <div class="mt20 text-end">
                        <strong><?php echo app_lang('proposals_total_general'); ?>:</strong>
                        <span id="proposal-proposal-items-total">0,00</span>
                    </div>
                </div>
                <div class="tab-pane fade" id="proposal-document" role="tabpanel">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?php echo app_lang('proposals_document_display_mode'); ?></label>
                                <?php
                                $display_mode = $proposal_info->display_mode ?? 'detailed';
                                ?>
                                <div>
                                    <label class="me-2">
                                        <input type="radio" name="display_mode" value="detailed" <?php echo $display_mode === 'detailed' ? 'checked' : ''; ?>>
                                        <?php echo app_lang('proposals_document_mode_detailed'); ?>
                                    </label>
                                    <label class="me-2">
                                        <input type="radio" name="display_mode" value="partial" <?php echo $display_mode === 'partial' ? 'checked' : ''; ?>>
                                        <?php echo app_lang('proposals_document_mode_partial'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="proposal-document-description"><?php echo app_lang('description'); ?></label>
                                <textarea id="proposal-document-description" class="form-control" rows="10"><?php echo esc($proposal_info->description ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="proposal-document-payment"><?php echo app_lang('payment_terms'); ?></label>
                                <textarea id="proposal-document-payment" class="form-control" rows="10"><?php echo esc($proposal_info->payment_terms ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="proposal-document-observations"><?php echo app_lang('notes'); ?></label>
                                <textarea id="proposal-document-observations" class="form-control" rows="10"><?php echo esc($proposal_info->observations ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="proposal-document-validity"><?php echo app_lang('proposals_validity_days'); ?></label>
                                <input type="number" id="proposal-document-validity" class="form-control" value="<?php echo esc($proposal_info->validity_days ?? ''); ?>">
                            </div>
                            <?php if ($can_manage) { ?>
                                <button type="button" class="btn btn-primary" id="proposal-document-save">
                                    <?php echo app_lang('save'); ?>
                                </button>
                            <?php } ?>
                        </div>
                        <div class="col-md-8">
                            <div class="proposal-document-preview" id="proposal-document-preview">
                                <?php echo $document_html ?? ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($can_manage && !empty($proposal_info->id)) { ?>
    <div class="modal fade" id="proposal-approve-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aprovar proposta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo app_lang('close'); ?>"></button>
                </div>
                <div class="modal-body">
                    <form id="proposal-approve-form">
                        <input type="hidden" name="id" value="<?php echo (int)$proposal_info->id; ?>">
                        <div class="form-check mb10">
                            <input class="form-check-input" type="checkbox" id="proposal-create-project" name="create_project" value="1" checked>
                            <label class="form-check-label" for="proposal-create-project">
                                Criar novo projeto com os dados do cliente e o valor total da proposta
                            </label>
                        </div>
                        <div class="form-check mb10">
                            <input class="form-check-input" type="checkbox" id="proposal-create-purchase-request" name="create_purchase_request" value="1">
                            <label class="form-check-label" for="proposal-create-purchase-request">
                                Criar requisição de compra
                            </label>
                        </div>

                        <div class="proposal-approve-request-section hide" id="proposal-purchase-request-section">
                            <div class="mb10 text-muted">
                                Selecione os itens que devem entrar na requisição. Você também pode adicionar linhas novas.
                            </div>
                            <div class="row mb15">
                                <div class="col-md-4">
                                    <div class="form-check mt10">
                                        <input class="form-check-input" type="checkbox" id="proposal-select-all-request-items">
                                        <label class="form-check-label" for="proposal-select-all-request-items">
                                            Selecionar todos os itens
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="proposal-bulk-request-date" class="mb5">Aplicar data de entrega em todos</label>
                                    <input type="date" class="form-control" id="proposal-bulk-request-date" value="<?php echo esc($default_desired_date); ?>">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width:6%"></th>
                                            <th style="width:34%">Item</th>
                                            <th style="width:12%"><?php echo app_lang('quantity'); ?></th>
                                            <th style="width:12%"><?php echo app_lang('unit'); ?></th>
                                            <th style="width:16%">Data desejada</th>
                                            <th style="width:20%"><?php echo app_lang('notes'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requestable_items as $request_item) { ?>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="proposal-request-item-checkbox" name="request_item_selected[<?php echo $request_item['id']; ?>]" value="1">
                                                </td>
                                                <td>
                                                    <?php echo esc($request_item['title']); ?>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="request_item_quantity[<?php echo $request_item['id']; ?>]" value="<?php echo esc($request_item['qty'] ?: 1); ?>">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="request_item_unit[<?php echo $request_item['id']; ?>]" value="<?php echo esc($request_item['unit'] ?: 'UN'); ?>">
                                                </td>
                                                <td>
                                                    <input type="date" class="form-control proposal-request-date-input" name="request_item_desired_date[<?php echo $request_item['id']; ?>]" value="<?php echo esc($default_desired_date); ?>">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="request_item_note[<?php echo $request_item['id']; ?>]">
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt15 mb10">
                                <strong>Novos itens</strong>
                                <button type="button" class="btn btn-default btn-sm" id="proposal-add-request-row">
                                    <i data-feather="plus" class="icon-16"></i> <?php echo app_lang('add'); ?>
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="proposal-new-request-items-table">
                                    <thead>
                                        <tr>
                                            <th style="width:22%">Produto</th>
                                            <th style="width:24%">Descrição</th>
                                            <th style="width:10%"><?php echo app_lang('quantity'); ?></th>
                                            <th style="width:10%"><?php echo app_lang('unit'); ?></th>
                                            <th style="width:16%">Data desejada</th>
                                            <th style="width:14%"><?php echo app_lang('notes'); ?></th>
                                            <th style="width:4%"></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
                    <button type="button" class="btn btn-primary" id="proposal-approve-submit">Confirmar aprovação</button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<script type="text/javascript">
    (function () {
        var $box = $(".proposal-breakdown");
        if (!$box.length) {
            return;
        }
        var total = parseFloat($box.data("total")) || 0;
        var cost = parseFloat($box.data("cost")) || 0;
        var tax = parseFloat($box.data("tax")) || 0;
        var commission = parseFloat($box.data("commission")) || 0;
        var profit = parseFloat($box.data("profit")) || 0;
        if (total <= 0) {
            $box.find(".proposal-breakdown-bar span").css("width", "0%");
            return;
        }
        var pctCost = (cost / total) * 100;
        var pctTax = (tax / total) * 100;
        var pctCommission = (commission / total) * 100;
        var pctProfit = (profit / total) * 100;
        var formatNumber2 = function (value) {
            var num = parseFloat(value || 0);
            if (isNaN(num)) {
                num = 0;
            }
            return num.toFixed(2).replace(".", ",");
        };
        var formatMoney = function (value) {
            if (typeof toCurrency === "function") {
                return toCurrency(value || 0);
            }
            return (value || 0).toFixed(2);
        };

        $box.find(".breakdown-cost")
            .css("width", pctCost + "%")
            .attr("title", formatNumber2(pctCost) + "% | " + formatMoney(cost));
        $box.find(".breakdown-tax")
            .css("width", pctTax + "%")
            .attr("title", formatNumber2(pctTax) + "% | " + formatMoney(tax));
        $box.find(".breakdown-commission")
            .css("width", pctCommission + "%")
            .attr("title", formatNumber2(pctCommission) + "% | " + formatMoney(commission));
        $box.find(".breakdown-profit")
            .css("width", pctProfit + "%")
            .attr("title", formatNumber2(pctProfit) + "% | " + formatMoney(profit));
    })();

    window.proposalsMemoryConfig = {
        proposalId: <?php echo (int)($proposal_info->id ?? 0); ?>,
        sections: <?php echo json_encode($sections); ?>,
        items: <?php echo json_encode($memory_items); ?>,
        itemsOptionsHtml: <?php echo json_encode($items_options_html ?? ""); ?>,
        canManage: <?php echo $can_manage ? 'true' : 'false'; ?>,
        endpoints: {
            addSection: '<?php echo_uri("propostas/sections/add"); ?>',
            updateSection: '<?php echo_uri("propostas/sections/update"); ?>',
            deleteSection: '<?php echo_uri("propostas/sections/delete"); ?>',
            addItem: '<?php echo_uri("propostas/items/add"); ?>',
            updateItem: '<?php echo_uri("propostas/items/update"); ?>',
            deleteItem: '<?php echo_uri("propostas/items/delete"); ?>',
            reorder: '<?php echo_uri("propostas/reorder"); ?>',
            itemSearch: '<?php echo_uri("propostas/items/search"); ?>',
            dashboardData: '<?php echo_uri("propostas/dashboard_data"); ?>',
            createItemModal: '<?php echo_uri("items/modal_form"); ?>',
            createItemQuick: '<?php echo_uri("propostas/items/create_quick"); ?>'
        },
        labels: {
            section: <?php echo json_encode(app_lang('proposals_section')); ?>,
            subSection: <?php echo json_encode(app_lang('proposals_subsection')); ?>,
            item: <?php echo json_encode(app_lang('item')); ?>,
            description: <?php echo json_encode(app_lang('description')); ?>,
            quantity: <?php echo json_encode(app_lang('quantity')); ?>,
            costUnit: <?php echo json_encode(app_lang('proposals_cost_unit')); ?>,
            markupPercent: <?php echo json_encode(app_lang('proposals_markup_percent')); ?>,
            saleUnit: <?php echo json_encode(app_lang('proposals_sale_unit')); ?>,
            total: <?php echo json_encode(app_lang('total')); ?>,
            addItem: <?php echo json_encode(app_lang('proposals_add_item')); ?>,
            addSubSection: <?php echo json_encode(app_lang('proposals_add_subsection')); ?>,
            remove: <?php echo json_encode(app_lang('delete')); ?>,
            moveUp: <?php echo json_encode(app_lang('move_up')); ?>,
            moveDown: <?php echo json_encode(app_lang('move_down')); ?>,
            selectSectionFirst: <?php echo json_encode(app_lang('proposals_select_section_first')); ?>,
            selectItem: <?php echo json_encode(app_lang('proposals_select_item')); ?>,
            confirmDelete: <?php echo json_encode(app_lang('proposals_confirm_delete')); ?>,
            material: <?php echo json_encode(app_lang('proposals_item_type_material')); ?>,
            service: <?php echo json_encode(app_lang('proposals_item_type_service')); ?>,
            showInProposal: <?php echo json_encode(app_lang('proposals_show_in_proposal')); ?>,
            showValues: <?php echo json_encode(app_lang('proposals_show_values_in_proposal')); ?>,
            noItems: <?php echo json_encode(app_lang('proposals_no_items')); ?>,
            totalCost: <?php echo json_encode(app_lang('proposals_total_cost')); ?>,
            costLabel: <?php echo json_encode(app_lang('proposals_cost_label')); ?>,
            saleLabel: <?php echo json_encode(app_lang('proposals_sale_label')); ?>,
            save: <?php echo json_encode(app_lang('save')); ?>,
            cancel: <?php echo json_encode(app_lang('cancel')); ?>
        }
    };
</script>
<?php
$memory_js_version = @filemtime(PLUGINPATH . 'Proposals/assets/js/proposals_memory.js');
$proposal_items_js_version = @filemtime(PLUGINPATH . 'Proposals/assets/js/proposals_proposal_items.js');
$document_js_version = @filemtime(PLUGINPATH . 'Proposals/assets/js/proposals_document.js');
?>
<script src="<?php echo base_url('plugins/Proposals/assets/js/proposals_memory.js?v=' . $memory_js_version); ?>"></script>
<script type="text/javascript">
    window.proposalsProposalItemsConfig = {
        proposalId: <?php echo (int)($proposal_info->id ?? 0); ?>,
        items: <?php echo json_encode($proposal_items); ?>,
        itemsOptionsHtml: <?php echo json_encode($items_options_html ?? ""); ?>,
        defaultMarkupPercent: <?php echo json_encode((float)($default_markup_percent ?? 0)); ?>,
        canManage: <?php echo $can_manage ? 'true' : 'false'; ?>,
        endpoints: {
            addItem: '<?php echo_uri("propostas/items/add"); ?>',
            updateItem: '<?php echo_uri("propostas/items/update"); ?>',
            deleteItem: '<?php echo_uri("propostas/items/delete"); ?>',
            itemSearch: '<?php echo_uri("propostas/items/search"); ?>',
            dashboardData: '<?php echo_uri("propostas/dashboard_data"); ?>',
            copyItems: '<?php echo_uri("propostas/items/copy_from_memory"); ?>',
            createItemQuick: '<?php echo_uri("propostas/items/create_quick"); ?>'
        },
        labels: {
            item: <?php echo json_encode(app_lang('item')); ?>,
            quantity: <?php echo json_encode(app_lang('quantity')); ?>,
            unit: <?php echo json_encode(app_lang('unit')); ?>,
            saleUnit: <?php echo json_encode(app_lang('proposals_sale_unit')); ?>,
            total: <?php echo json_encode(app_lang('total')); ?>,
            remove: <?php echo json_encode(app_lang('delete')); ?>,
            confirmDelete: <?php echo json_encode(app_lang('proposals_confirm_delete')); ?>,
            noItems: <?php echo json_encode(app_lang('proposals_no_proposal_items')); ?>,
            selectItem: <?php echo json_encode(app_lang('proposals_select_item')); ?>,
            save: <?php echo json_encode(app_lang('save')); ?>,
            cancel: <?php echo json_encode(app_lang('cancel')); ?>
        }
    };
</script>
<script src="<?php echo base_url('plugins/Proposals/assets/js/proposals_proposal_items.js?v=' . $proposal_items_js_version); ?>"></script>
<script type="text/javascript">
    window.proposalsDocumentConfig = {
        proposalId: <?php echo (int)($proposal_info->id ?? 0); ?>,
        canManage: <?php echo $can_manage ? 'true' : 'false'; ?>,
        endpoints: {
            preview: '<?php echo_uri("propostas/document/preview"); ?>',
            save: '<?php echo_uri("propostas/document/save"); ?>',
            downloadPdf: '<?php echo_uri("propostas/download_pdf/" . (int)($proposal_info->id ?? 0)); ?>'
        },
        filename: <?php
            $file_code = "Proposta " . str_pad((int)($proposal_info->id ?? 0), 3, "0", STR_PAD_LEFT);
            $file_title = trim((string)($proposal_info->title ?? ""));
            $file_client = trim((string)($proposal_info->client_company ?? ($proposal_info->client_name ?? "")));
            echo json_encode(trim($file_code . " - " . $file_title . " - " . $file_client));
        ?>,
        labels: {
            saved: <?php echo json_encode(app_lang('record_saved')); ?>,
            error: <?php echo json_encode(app_lang('error_occurred')); ?>
        }
    };
</script>
<script src="<?php echo base_url('plugins/Proposals/assets/js/proposals_document.js?v=' . $document_js_version); ?>"></script>
<script>
    $(document).ready(function () {
        var proposalId = "<?php echo (int)($proposal_info->id ?? 0); ?>";
        var tabStorageKey = "proposal-view-active-tab-" + proposalId;
        var $proposalTabs = $('.nav-tabs a[data-bs-toggle="tab"][href^="#proposal-"]');
        var savedTab = localStorage.getItem(tabStorageKey);
        var newRequestItemOptionsHtml = <?php echo json_encode($items_options_html ?? ""); ?>;
        var $materialRequestItemOptions = $("<div>").html(newRequestItemOptionsHtml);
        $materialRequestItemOptions.find("option[data-type='service']").remove();
        newRequestItemOptionsHtml = $materialRequestItemOptions.html();

        if (savedTab) {
            var $savedTabLink = $proposalTabs.filter('[href="' + savedTab + '"]');

            if ($savedTabLink.length) {
                if (window.bootstrap && window.bootstrap.Tab) {
                    window.bootstrap.Tab.getOrCreateInstance($savedTabLink[0]).show();
                } else {
                    $savedTabLink.trigger("click");
                }
            }
        }

        $proposalTabs.on("shown.bs.tab", function (e) {
            var targetTab = $(e.target).attr("href");

            if (targetTab) {
                localStorage.setItem(tabStorageKey, targetTab);
            }
        });

        $("#proposal-status-select").on("change", function () {
            var status = $(this).val();
            if (!status) {
                return;
            }
            appAjaxRequest({
                url: "<?php echo_uri('propostas/update_status'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    id: "<?php echo (int)($proposal_info->id ?? 0); ?>",
                    status: status
                },
                success: function (result) {
                    if (result && result.success) {
                        if (result.status_html) {
                            $("#proposal-dash-status").html(result.status_html);
                        }
                        appAlert.success(result.status || "", {duration: 4000});
                    } else {
                        appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                    }
                }
            });
        });

        $("#proposal-create-purchase-request").on("change", function () {
            $("#proposal-purchase-request-section").toggleClass("hide", !$(this).is(":checked"));
        }).trigger("change");

        $("#proposal-add-request-row").on("click", function () {
            var bulkRequestDate = $("#proposal-bulk-request-date").val() || "<?php echo esc($default_desired_date); ?>";
            var rowHtml = '' +
                '<tr>' +
                    '<td><select name="new_item_id[]" class="form-control proposal-request-new-item-select">' + newRequestItemOptionsHtml + '</select></td>' +
                    '<td><input type="text" name="new_item_description[]" class="form-control"></td>' +
                    '<td><input type="text" name="new_item_quantity[]" class="form-control" value="1"></td>' +
                    '<td><input type="text" name="new_item_unit[]" class="form-control" value="UN"></td>' +
                    '<td><input type="date" name="new_item_desired_date[]" class="form-control proposal-request-date-input" value="' + bulkRequestDate + '"></td>' +
                    '<td><input type="text" name="new_item_note[]" class="form-control"></td>' +
                    '<td class="text-center"><a href="#" class="text-danger proposal-remove-request-row"><i data-feather="x" class="icon-16"></i></a></td>' +
                '</tr>';

            $("#proposal-new-request-items-table tbody").append(rowHtml);
            if (window.feather) {
                window.feather.replace();
            }
        });

        $(document).on("click", ".proposal-remove-request-row", function (e) {
            e.preventDefault();
            $(this).closest("tr").remove();
        });

        $(document).on("change", ".proposal-request-new-item-select", function () {
            var $option = $(this).find("option:selected");
            var $row = $(this).closest("tr");
            var $description = $row.find("input[name='new_item_description[]']");
            var $unit = $row.find("input[name='new_item_unit[]']");

            if (!$description.val()) {
                $description.val($.trim($option.text()) === "-" ? "" : $.trim($option.text()));
            }
            if ($option.data("unit")) {
                $unit.val($option.data("unit"));
            }
        });

        $("#proposal-select-all-request-items").on("change", function () {
            $(".proposal-request-item-checkbox").prop("checked", $(this).is(":checked"));
        });

        $("#proposal-bulk-request-date").on("change", function () {
            var value = $(this).val();
            if (!value) {
                return;
            }
            $(".proposal-request-date-input").val(value);
        });

        $("#proposal-approve-submit").on("click", function () {
            appAjaxRequest({
                url: "<?php echo_uri('propostas/approve'); ?>",
                type: "POST",
                dataType: "json",
                data: $("#proposal-approve-form").serialize(),
                success: function (result) {
                    if (result && result.success) {
                        window.location.href = result.redirect_to || "<?php echo_uri('propostas/view/' . (int)($proposal_info->id ?? 0)); ?>";
                    } else {
                        appAlert.error((result && result.message) || "<?php echo app_lang('error_occurred'); ?>");
                    }
                }
            });
        });

        $("#proposal-duplicate-button").on("click", function () {
            if (!confirm("Deseja duplicar esta proposta?")) {
                return;
            }

            appAjaxRequest({
                url: "<?php echo_uri('propostas/duplicate'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    id: proposalId
                },
                success: function (result) {
                    if (result && result.success) {
                        window.location.href = result.redirect_to;
                    } else {
                        appAlert.error((result && result.message) || "<?php echo app_lang('error_occurred'); ?>");
                    }
                }
            });
        });
    });
</script>
