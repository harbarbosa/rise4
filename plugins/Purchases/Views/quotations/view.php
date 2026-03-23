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
?>

<div id="page-content" class="page-wrapper clearfix">
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
                <div class="mb15">
                    <?php echo form_open(get_uri('purchases_quotations/update_suppliers/' . $quotation->id), array('id' => 'quotation-suppliers-form', 'class' => 'general-form')); ?>
                    <label class="form-label"><?php echo app_lang('purchases_suppliers'); ?></label>
                    <select name="supplier_ids[]" class="form-control select2" multiple data-placeholder="<?php echo app_lang('purchases_suppliers'); ?>">
                        <?php foreach ($suppliers_all as $supplier_id => $supplier_name) { ?>
                            <option value="<?php echo esc($supplier_id); ?>" <?php echo in_array((int)$supplier_id, $selected_supplier_ids) ? 'selected' : ''; ?>>
                                <?php echo esc($supplier_name); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="text-muted small mt5"><?php echo app_lang('purchases_select_suppliers_limit'); ?></div>
                    <div class="mt10">
                        <button type="submit" class="btn btn-default btn-sm"><i data-feather='save' class='icon-16'></i> <?php echo app_lang('save'); ?></button>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            <?php } ?>

            <?php echo form_open(get_uri('purchases_quotations/save_prices/' . $quotation->id), array('id' => 'quotation-prices-form', 'class' => 'general-form')); ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo app_lang('purchases_material'); ?></th>
                            <th><?php echo app_lang('purchases_item_description'); ?></th>
                            <th class="text-right"><?php echo app_lang('purchases_qty'); ?></th>
                            <?php foreach ($suppliers as $supplier) { ?>
                                <th class="text-center"><?php echo esc($supplier->supplier_name); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item) { ?>
                            <tr>
                                <td><?php echo esc($item->item_title ? $item->item_title : '-'); ?></td>
                                <td><?php echo esc($item->request_description); ?></td>
                                <td class="text-right">
                                    <input type="text" name="qty[<?php echo $item->request_item_id; ?>]" class="form-control text-right" value="<?php echo esc(to_decimal_format($item->qty)); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                </td>
                                <?php foreach ($suppliers as $supplier) { ?>
                                    <?php
                                    $price = get_array_value(get_array_value($price_map, $item->request_item_id, array()), $supplier->supplier_id);
                                    $unit_price = $price ? to_decimal_format($price->unit_price) : '';
                                    $delivery_date = $price ? $price->delivery_date : '';
                                    $freight = $price ? to_decimal_format($price->freight_value) : '';
                                    $payment_terms = $price ? $price->payment_terms : '';
                                    $notes = $price ? $price->notes : '';
                                    $desired_date = $item->request_desired_date ?? '';
                                    $late_delivery = false;
                                    if ($desired_date && $delivery_date) {
                                        $late_delivery = (strtotime($delivery_date) > strtotime($desired_date));
                                    }
                                    ?>
                                    <td>
                                        <div class="mb5 text-center">
                                            <label class="small text-muted"><?php echo app_lang('purchases_winner'); ?></label>
                                            <div>
                                                <input type="radio" name="winner_supplier[<?php echo $item->request_item_id; ?>]" value="<?php echo $supplier->supplier_id; ?>" <?php echo (($winner_map[$item->request_item_id] ?? 0) == $supplier->supplier_id) ? 'checked' : ''; ?> <?php echo $can_edit ? '' : 'disabled'; ?> />
                                            </div>
                                        </div>
                                        <div class="mb5">
                                            <label class="small text-muted"><?php echo app_lang('purchases_unit_price'); ?></label>
                                            <input type="text" name="unit_price[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control js-currency-field" value="<?php echo esc($unit_price); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                        </div>
                                        <div class="mb5">
                                            <label class="small text-muted"><?php echo app_lang('purchases_delivery_date'); ?></label>
                                            <input type="date" name="delivery_date[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control" value="<?php echo esc($delivery_date); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                            <?php if ($late_delivery) { ?>
                                                <div class="text-danger small"><?php echo app_lang('purchases_delivery_date_late'); ?></div>
                                            <?php } ?>
                                        </div>
                                        <div class="mb5">
                                            <label class="small text-muted"><?php echo app_lang('purchases_freight_value'); ?></label>
                                            <input type="text" name="freight_value[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control js-currency-field" value="<?php echo esc($freight); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                        </div>
                                        <div class="mb5">
                                            <label class="small text-muted"><?php echo app_lang('purchases_payment_terms'); ?></label>
                                            <input type="text" name="payment_terms[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control" value="<?php echo esc($payment_terms); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                        </div>
                                        <div>
                                            <label class="small text-muted"><?php echo app_lang('purchases_notes'); ?></label>
                                            <input type="text" name="notes[<?php echo $supplier->supplier_id; ?>][<?php echo $item->request_item_id; ?>]" class="form-control" value="<?php echo esc($notes); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                                        </div>
                                    </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php if ($can_edit) { ?>
                <div class="mt10">
                    <button type="submit" class="btn btn-primary btn-sm"><i data-feather='save' class='icon-16'></i> <?php echo app_lang('purchases_save_prices_winners'); ?></button>
                </div>
            <?php } ?>
            <?php echo form_close(); ?>

            <div class="mt15">
                <h4 class="mb10"><?php echo app_lang('purchases_totals_by_supplier'); ?></h4>
                <div class="row">
                    <?php foreach ($suppliers as $supplier) { ?>
                        <div class="col-md-4 mb10">
                            <div class="p10 bg-light">
                                <strong><?php echo esc($supplier->supplier_name); ?></strong>
                                <div><?php echo to_currency(get_array_value($totals, $supplier->supplier_id, 0)); ?></div>
                                <div class="text-muted small"><?php echo app_lang('purchases_winner_total'); ?>: <?php echo to_currency(get_array_value($winner_totals, $supplier->supplier_id, 0)); ?></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="mt20">
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
