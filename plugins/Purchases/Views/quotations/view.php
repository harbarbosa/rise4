<?php
$quotation = $quotation_info;
$request = $request_info;
$status = $quotation->status ? $quotation->status : 'draft';
$request_code = $request->request_code ? $request->request_code : ('#' . $request->id);
$quotation_status_class = get_array_value(array(
    'draft' => 'secondary',
    'finalized' => 'success',
    'canceled' => 'danger'
), $status, 'secondary');

$supplier_name_map = array();
foreach ($suppliers as $supplier) {
    $supplier_name_map[$supplier->supplier_id] = $supplier->supplier_name;
}
?>

<style>
    .purchases-quotation-layout .quotation-suppliers-panel,
    .purchases-quotation-layout .quotation-summary-card,
    .purchases-quotation-layout .quotation-item-card,
    .purchases-quotation-layout .quotation-item-meta,
    .purchases-quotation-layout .quotation-footer-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
    }

    .purchases-quotation-layout .quotation-suppliers-panel,
    .purchases-quotation-layout .quotation-footer-panel {
        padding: 16px;
        background: #f8fafc;
    }

    .purchases-quotation-layout .quotation-summary-card {
        padding: 14px;
        height: 100%;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .purchases-quotation-layout .quotation-items-stack {
        display: grid;
        gap: 14px;
    }

    .purchases-quotation-layout .quotation-item-card {
        overflow: hidden;
    }

    .purchases-quotation-layout .quotation-item-toggle {
        width: 100%;
        border: 0;
        background: #fff;
        text-align: left;
        padding: 16px 18px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
    }

    .purchases-quotation-layout .quotation-item-toggle:hover {
        background: #f8fafc;
    }

    .purchases-quotation-layout .quotation-item-title {
        font-size: 15px;
        font-weight: 600;
        color: #111827;
    }

    .purchases-quotation-layout .quotation-item-subtitle {
        margin-top: 4px;
        color: #6b7280;
        font-size: 13px;
    }

    .purchases-quotation-layout .quotation-item-meta {
        padding: 10px 12px;
        min-width: 320px;
        background: #f8fafc;
        display: grid;
        gap: 8px;
    }

    .purchases-quotation-layout .quotation-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 14px;
    }

    .purchases-quotation-layout .quotation-meta-label {
        display: block;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6b7280;
        margin-bottom: 2px;
    }

    .purchases-quotation-layout .quotation-meta-value {
        color: #111827;
        font-weight: 600;
    }

    .purchases-quotation-layout .quotation-item-body {
        padding: 0 18px 18px;
        border-top: 1px solid #eef2f7;
        background: #fbfdff;
    }

    .purchases-quotation-layout .quotation-item-table {
        margin-top: 14px;
        background: #fff;
    }

    .purchases-quotation-layout .quotation-item-table th,
    .purchases-quotation-layout .quotation-item-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .purchases-quotation-layout .quotation-item-table td.notes-cell,
    .purchases-quotation-layout .quotation-item-table th.notes-cell {
        white-space: normal;
        min-width: 220px;
    }

    .purchases-quotation-layout .supplier-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 12px;
        font-weight: 600;
    }

    .purchases-quotation-layout .is-late {
        color: #b91c1c;
        font-size: 12px;
        margin-top: 4px;
    }

    .purchases-quotation-layout .winner-row {
        background: rgba(13, 110, 253, 0.06);
    }

    .purchases-quotation-layout .rotate-icon {
        transition: transform .2s ease;
    }

    .purchases-quotation-layout .quotation-item-toggle[aria-expanded="true"] .rotate-icon {
        transform: rotate(180deg);
    }

    @media (max-width: 991px) {
        .purchases-quotation-layout .quotation-item-toggle {
            flex-direction: column;
        }

        .purchases-quotation-layout .quotation-item-meta {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<div id="page-content" class="page-wrapper clearfix purchases-quotation-layout">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_quotation'); ?> #<?php echo esc($quotation->id); ?></h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('purchases_requests/view/' . $request->id), app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb10">
                    <div class="text-muted"><?php echo app_lang('purchases_request_code'); ?>: <strong><?php echo esc($request_code); ?></strong></div>
                </div>
                <div class="col-md-6 mb10 text-end">
                    <span class="badge bg-<?php echo esc($quotation_status_class); ?>"><?php echo app_lang('purchases_quotation_status_' . $status); ?></span>
                </div>
            </div>

            <?php if ($can_edit) { ?>
                <div class="quotation-suppliers-panel mb15">
                    <?php echo form_open(get_uri('purchases_quotations/update_suppliers/' . $quotation->id), array('id' => 'quotation-suppliers-form', 'class' => 'general-form')); ?>
                    <label class="form-label"><?php echo app_lang('purchases_suppliers'); ?></label>
                    <select name="supplier_ids[]" id="quotation-supplier-ids" class="form-control select2" multiple data-placeholder="<?php echo app_lang('purchases_suppliers'); ?>">
                        <?php foreach ($suppliers_all as $supplier_id => $supplier_name) { ?>
                            <option value="<?php echo esc($supplier_id); ?>" <?php echo in_array((int) $supplier_id, $selected_supplier_ids) ? 'selected' : ''; ?>>
                                <?php echo esc($supplier_name); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="text-muted small mt5"><?php echo app_lang('purchases_select_suppliers_limit'); ?></div>
                    <div class="text-muted small mt5" id="quotation-selected-suppliers-count"></div>
                    <div class="mt10">
                        <button type="submit" class="btn btn-default btn-sm"><i data-feather='save' class='icon-16'></i> <?php echo app_lang('save'); ?></button>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            <?php } ?>

            <div class="mt15">
                <h4 class="mb10"><?php echo app_lang('purchases_totals_by_supplier'); ?></h4>
                <div class="row">
                    <?php foreach ($suppliers as $supplier) { ?>
                        <div class="col-md-4 col-xl-3 mb10">
                            <div class="quotation-summary-card">
                                <strong><?php echo esc($supplier->supplier_name); ?></strong>
                                <div class="mt5"><?php echo to_currency(get_array_value($totals, $supplier->supplier_id, 0)); ?></div>
                                <div class="text-muted small mt5"><?php echo app_lang('purchases_winner_total'); ?>: <?php echo to_currency(get_array_value($winner_totals, $supplier->supplier_id, 0)); ?></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <?php echo form_open(get_uri('purchases_quotations/save_prices/' . $quotation->id), array('id' => 'quotation-prices-form', 'class' => 'general-form')); ?>
            <div class="quotation-items-stack mt15" id="quotation-items-accordion">
                <?php foreach ($items as $index => $item) { ?>
                    <?php
                    $winner_supplier_id = (int) ($winner_map[$item->request_item_id] ?? 0);
                    $winner_supplier_name = $winner_supplier_id ? get_array_value($supplier_name_map, $winner_supplier_id, '-') : '-';
                    $desired_date = $item->request_desired_date ?? '';
                    ?>
                    <div class="quotation-item-card">
                        <button class="quotation-item-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#quotation-item-<?php echo $item->request_item_id; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                            <div>
                                <div class="quotation-item-title"><?php echo esc($item->item_title ? $item->item_title : '-'); ?></div>
                                <div class="quotation-item-subtitle"><?php echo esc($item->request_description); ?></div>
                            </div>
                            <div class="quotation-item-meta">
                                <div class="quotation-meta-grid">
                                    <div>
                                        <span class="quotation-meta-label"><?php echo app_lang('purchases_qty'); ?></span>
                                        <span class="quotation-meta-value"><?php echo esc(to_decimal_format($item->qty)); ?></span>
                                    </div>
                                    <div>
                                        <span class="quotation-meta-label"><?php echo app_lang('purchases_supplier'); ?></span>
                                        <span class="quotation-meta-value"><?php echo esc($winner_supplier_name); ?></span>
                                    </div>
                                    <div>
                                        <span class="quotation-meta-label"><?php echo app_lang('purchases_unit'); ?></span>
                                        <span class="quotation-meta-value"><?php echo esc($item->request_unit ? $item->request_unit : '-'); ?></span>
                                    </div>
                                    <div>
                                        <span class="quotation-meta-label"><?php echo app_lang('purchases_delivery_date'); ?></span>
                                        <span class="quotation-meta-value"><?php echo $desired_date ? format_to_date($desired_date, false) : '-'; ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <i data-feather="chevron-down" class="icon-18 rotate-icon"></i>
                                </div>
                            </div>
                        </button>

                        <div id="quotation-item-<?php echo $item->request_item_id; ?>" class="collapse <?php echo $index === 0 ? 'show' : ''; ?>" data-bs-parent="#quotation-items-accordion">
                            <div class="quotation-item-body">
                                <div class="mb10">
                                    <label class="form-label"><?php echo app_lang('purchases_qty'); ?></label>
                                    <input type="text" name="qty[<?php echo $item->request_item_id; ?>]" class="form-control text-right w150" value="<?php echo esc(to_decimal_format($item->qty)); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered quotation-item-table">
                                        <thead>
                                            <tr>
                                                <th><?php echo app_lang('purchases_supplier'); ?></th>
                                                <th class="text-center"><?php echo app_lang('purchases_winner'); ?></th>
                                                <th><?php echo app_lang('purchases_unit_price'); ?></th>
                                                <th><?php echo app_lang('purchases_freight_value'); ?></th>
                                                <th><?php echo app_lang('purchases_total'); ?></th>
                                                <th><?php echo app_lang('purchases_delivery_date'); ?></th>
                                                <th><?php echo app_lang('purchases_payment_terms'); ?></th>
                                                <th class="notes-cell"><?php echo app_lang('purchases_notes'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($suppliers as $supplier) { ?>
                                                <?php
                                                $price = get_array_value(get_array_value($price_map, $item->request_item_id, array()), $supplier->supplier_id);
                                                $unit_price = $price ? to_decimal_format($price->unit_price) : '';
                                                $delivery_date = $price ? $price->delivery_date : '';
                                                $freight = $price ? to_decimal_format($price->freight_value) : '';
                                                $payment_terms = $price ? $price->payment_terms : '';
                                                $notes = $price ? $price->notes : '';
                                                $line_total = $price ? (((float) $item->qty * (float) $price->unit_price) + (float) $price->freight_value) : 0;
                                                $late_delivery = false;
                                                if ($desired_date && $delivery_date) {
                                                    $late_delivery = (strtotime($delivery_date) > strtotime($desired_date));
                                                }
                                                $is_winner = (($winner_map[$item->request_item_id] ?? 0) == $supplier->supplier_id);
                                                ?>
                                                <tr class="<?php echo $is_winner ? 'winner-row' : ''; ?>">
                                                    <td>
                                                        <span class="supplier-pill"><?php echo esc($supplier->supplier_name); ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="radio" name="winner_supplier[<?php echo $item->request_item_id; ?>]" value="<?php echo $supplier->supplier_id; ?>" <?php echo $is_winner ? 'checked' : ''; ?> <?php echo $can_edit ? '' : 'disabled'; ?> />
                                                    </td>
                                                    <td>
                                                        <input type="text" name="unit_price[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control js-currency-field" value="<?php echo esc($unit_price); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                                    </td>
                                                    <td>
                                                        <input type="text" name="freight_value[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control js-currency-field" value="<?php echo esc($freight); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                                    </td>
                                                    <td class="fw-bold"><?php echo $line_total > 0 ? to_currency($line_total) : '-'; ?></td>
                                                    <td>
                                                        <input type="date" name="delivery_date[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control" value="<?php echo esc($delivery_date); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                                        <?php if ($late_delivery) { ?>
                                                            <div class="is-late"><?php echo app_lang('purchases_delivery_date_late'); ?></div>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="payment_terms[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control" value="<?php echo esc($payment_terms); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                                    </td>
                                                    <td class="notes-cell">
                                                        <input type="text" name="notes[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control" value="<?php echo esc($notes); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php if ($can_edit) { ?>
                <div class="mt15">
                    <button type="submit" class="btn btn-primary btn-sm"><i data-feather='save' class='icon-16'></i> <?php echo app_lang('purchases_save_prices_winners'); ?></button>
                </div>
            <?php } ?>
            <?php echo form_close(); ?>

            <div class="quotation-footer-panel mt20">
                <?php if ($can_finalize) { ?>
                    <?php echo form_open(get_uri('purchases_quotations/finalize/' . $quotation->id), array('id' => 'quotation-finalize-form', 'class' => 'general-form')); ?>
                    <button type="submit" class="btn btn-success btn-sm"><i data-feather='check-circle' class='icon-16'></i> <?php echo app_lang('purchases_finalize_quotation'); ?></button>
                    <?php echo form_close(); ?>
                <?php } ?>

                <?php if (!$can_generate_po && $quotation->status === 'finalized' && !$has_order) { ?>
                    <div class="text-muted small mt10">
                        <?php echo app_lang('purchases_waiting_approval_before_po'); ?>
                    </div>
                <?php } ?>

                <?php if ($can_generate_po) { ?>
                    <?php echo form_open(get_uri('purchases_quotations/generate_po/' . $quotation->id), array('id' => 'quotation-generate-po-form', 'class' => 'general-form')); ?>
                    <button type="submit" class="btn btn-info btn-sm mt10"><i data-feather='shopping-cart' class='icon-16'></i> <?php echo app_lang('purchases_generate_po'); ?></button>
                    <?php echo form_close(); ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();

        var updateSelectedSuppliersCount = function () {
            var values = $("#quotation-supplier-ids").val() || [];
            $("#quotation-selected-suppliers-count").text(values.length ? (values.length + " fornecedor(es) selecionado(s)") : "");
        };

        updateSelectedSuppliersCount();
        $("#quotation-supplier-ids").on("change", updateSelectedSuppliersCount);

        var formatCurrencyField = function ($field) {
            if (typeof toCurrency !== "function" || typeof unformatCurrency !== "function") {
                return;
            }

            var value = $field.val();
            if (value === "") {
                return;
            }

            var numeric = unformatCurrency(value);
            if (isNaN(numeric)) {
                return;
            }

            $field.val(toCurrency(numeric));
        };

        $(".js-currency-field").each(function () {
            formatCurrencyField($(this));
        });

        $(document).on("blur", ".js-currency-field", function () {
            formatCurrencyField($(this));
        });

        $(document).on("input", ".js-currency-field", function () {
            var $field = $(this);
            if ($field.data("formatting")) {
                return;
            }
            $field.data("formatting", true);
            if (typeof toCurrency === "function") {
                var raw = $field.val();
                var digits = raw.replace(/\D/g, "");
                if (!digits.length) {
                    $field.val("");
                } else {
                    var numeric = parseInt(digits, 10) / 100;
                    $field.val(toCurrency(numeric));
                }
            }
            $field.data("formatting", false);

            var el = this;
            if (el.setSelectionRange) {
                var len = $field.val().length;
                el.setSelectionRange(len, len);
            }
        });

        $("#quotation-prices-form").appForm({
            onSuccess: function () {
                window.location.reload();
            }
        });

        $("#quotation-suppliers-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    if (result.message) {
                        appAlert.success(result.message, {duration: 3000});
                    }
                    setTimeout(function () {
                        window.location.reload();
                    }, 600);
                    return;
                }
                if (result && result.message) {
                    appAlert.error(result.message);
                } else {
                    appAlert.error("<?php echo app_lang('error_occurred'); ?>");
                }
            },
            onError: function (result) {
                appAlert.error((result && result.message) ? result.message : "<?php echo app_lang('error_occurred'); ?>");
                return false;
            }
        });

        $("#quotation-finalize-form").appForm({
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 3000});
                }
                var requestViewUrl = "<?php echo get_uri('purchases_requests/view/' . $request->id); ?>";
                setTimeout(function () {
                    window.location = requestViewUrl + "?purchases_success=quotation_finalized";
                }, 600);
            }
        });

        $("#quotation-generate-po-form").appForm({
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 3000});
                }
                if (result && result.order_ids && result.order_ids.length) {
                    var orderId = result.order_ids[0];
                    var targetUrl = "<?php echo get_uri('purchases_orders/view'); ?>/" + orderId;
                    setTimeout(function () {
                        window.location = targetUrl;
                    }, 600);
                    return;
                }
                setTimeout(function () {
                    window.location.reload();
                }, 600);
            }
        });
    });
</script>
