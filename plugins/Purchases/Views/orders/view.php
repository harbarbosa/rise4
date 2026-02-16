<?php
$order = $order_info;
$po_code = $order->po_code ? $order->po_code : ('#' . $order->id);
$project = $order->project_title ? $order->project_title : ($order->cost_center ? $order->cost_center : '-');

$received_map = array();
if (!empty($receipt_items)) {
    foreach ($receipt_items as $items) {
        foreach ($items as $receipt_item) {
            if (!isset($received_map[$receipt_item->order_item_id])) {
                $received_map[$receipt_item->order_item_id] = 0;
            }
            $received_map[$receipt_item->order_item_id] += (float)$receipt_item->quantity_received;
        }
    }
}

$has_pending = false;
foreach ($order_items as $item) {
    $received_qty = (float)get_array_value($received_map, $item->id, 0);
    $pending_qty = (float)$item->quantity - $received_qty;
    if ($pending_qty > 0) {
        $has_pending = true;
        break;
    }
}
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_purchase_order'); ?> <?php echo esc($po_code); ?></h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('purchases_orders/print_view/' . $order->id), "<i data-feather='printer' class='icon-16'></i> " . app_lang('purchases_print'), array('class' => 'btn btn-default', 'target' => '_blank')); ?>
                <?php if ($has_pending && $order->status !== 'canceled') { ?>
                    <?php echo modal_anchor(get_uri('purchases_goods_receipts/modal_form'), "<i data-feather='inbox' class='icon-16'></i> " . app_lang('purchases_register_receipt'), array('class' => 'btn btn-default', 'title' => app_lang('purchases_register_receipt'), 'data-post-order_id' => $order->id, 'data-modal-lg' => '1')); ?>
                <?php } ?>
                <?php echo anchor(get_uri('purchases_orders'), app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <td class="w150"><?php echo app_lang('purchases_po_code'); ?></td>
                            <td><?php echo esc($po_code); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('purchases_supplier'); ?></td>
                            <td><?php echo esc($order->supplier_name ? $order->supplier_name : '-'); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('project'); ?></td>
                            <td><?php echo esc($project); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('purchases_expected_delivery_date'); ?></td>
                            <td><?php echo $order->expected_delivery_date ? format_to_date($order->expected_delivery_date, false) : '-'; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <td class="w150"><?php echo app_lang('status'); ?></td>
                            <td><?php echo $status_label; ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('purchases_delivery_address'); ?></td>
                            <td><?php echo esc($order->delivery_address ? $order->delivery_address : '-'); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('purchases_payment_terms'); ?></td>
                            <td><?php echo esc($order->payment_terms ? $order->payment_terms : '-'); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('total'); ?></td>
                            <td><?php echo to_currency($order->total); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt15">
                <h4 class="mb10"><?php echo app_lang('purchases_items'); ?></h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo app_lang('purchases_material'); ?></th>
                                <th><?php echo app_lang('purchases_item_description'); ?></th>
                                <th class="text-right"><?php echo app_lang('purchases_qty'); ?></th>
                                <th><?php echo app_lang('purchases_unit'); ?></th>
                                <th class="text-right"><?php echo app_lang('purchases_unit_price'); ?></th>
                                <th class="text-right"><?php echo app_lang('total'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item) { ?>
                                <tr>
                                    <td><?php echo esc($item->description ? $item->description : '-'); ?></td>
                                    <td><?php echo esc($item->description); ?></td>
                                    <td class="text-right"><?php echo esc($item->quantity); ?></td>
                                    <td><?php echo esc($item->unit); ?></td>
                                    <td class="text-right"><?php echo to_currency($item->rate); ?></td>
                                    <td class="text-right"><?php echo to_currency($item->total); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($can_update_status) { ?>
                <div class="mt15 d-flex flex-wrap gap-2">
                    <?php echo form_open(get_uri('purchases_orders/update_status/' . $order->id), array('class' => 'general-form order-status-form')); ?>
                        <input type="hidden" name="status" value="sent" />
                        <button type="submit" class="btn btn-primary btn-sm"><i data-feather='send' class='icon-16'></i> <?php echo app_lang('purchases_mark_sent'); ?></button>
                    <?php echo form_close(); ?>

                    <?php echo form_open(get_uri('purchases_orders/update_status/' . $order->id), array('class' => 'general-form order-status-form')); ?>
                        <input type="hidden" name="status" value="partial_received" />
                        <button type="submit" class="btn btn-warning btn-sm"><i data-feather='truck' class='icon-16'></i> <?php echo app_lang('purchases_mark_partial_received'); ?></button>
                    <?php echo form_close(); ?>

                    <?php echo form_open(get_uri('purchases_orders/update_status/' . $order->id), array('class' => 'general-form order-status-form')); ?>
                        <input type="hidden" name="status" value="received" />
                        <button type="submit" class="btn btn-success btn-sm"><i data-feather='check-circle' class='icon-16'></i> <?php echo app_lang('purchases_mark_received'); ?></button>
                    <?php echo form_close(); ?>

                    <?php echo form_open(get_uri('purchases_orders/update_status/' . $order->id), array('class' => 'general-form order-status-form')); ?>
                        <input type="hidden" name="status" value="canceled" />
                        <button type="submit" class="btn btn-danger btn-sm"><i data-feather='x-circle' class='icon-16'></i> <?php echo app_lang('purchases_mark_canceled'); ?></button>
                    <?php echo form_close(); ?>
                </div>
            <?php } ?>

            <div class="mt20">
                <h4 class="mb10"><?php echo app_lang('purchases_receipt_history'); ?></h4>
                <?php if (!$receipts) { ?>
                    <div class="text-muted"><?php echo app_lang('purchases_no_receipts'); ?></div>
                <?php } else { ?>
                    <?php foreach ($receipts as $receipt) { ?>
                        <div class="card mb15">
                            <div class="card-body">
                                <div class="row mb10">
                                    <div class="col-md-4"><strong><?php echo app_lang('purchases_receipt_date'); ?>:</strong> <?php echo $receipt->receipt_date ? format_to_date($receipt->receipt_date, false) : '-'; ?></div>
                                    <div class="col-md-4"><strong><?php echo app_lang('purchases_received_by'); ?>:</strong> <?php echo esc($receipt->received_by_name); ?></div>
                                    <div class="col-md-4"><strong><?php echo app_lang('purchases_nf_number'); ?>:</strong> <?php echo esc($receipt->nf_number ? $receipt->nf_number : '-'); ?></div>
                                </div>
                                <?php if ($receipt->note) { ?>
                                    <div class="mb10"><strong><?php echo app_lang('purchases_note'); ?>:</strong> <?php echo esc($receipt->note); ?></div>
                                <?php } ?>

                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?php echo app_lang('purchases_material'); ?></th>
                                                <th><?php echo app_lang('purchases_item_description'); ?></th>
                                                <th class="text-right"><?php echo app_lang('purchases_qty_received_now'); ?></th>
                                                <th><?php echo app_lang('purchases_unit'); ?></th>
                                                <th><?php echo app_lang('purchases_item_note'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (get_array_value($receipt_items, $receipt->id, array()) as $receipt_item) { ?>
                                                <tr>
                                                    <td><?php echo esc($receipt_item->description ? $receipt_item->description : '-'); ?></td>
                                                    <td><?php echo esc($receipt_item->description); ?></td>
                                                    <td class="text-right"><?php echo to_decimal_format($receipt_item->quantity_received); ?></td>
                                                    <td><?php echo esc($receipt_item->unit ? $receipt_item->unit : '-'); ?></td>
                                                    <td><?php echo esc($receipt_item->note ? $receipt_item->note : '-'); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php $files = get_array_value($receipt_files, $receipt->id, array()); ?>
                                <?php if ($files) { ?>
                                    <div class="mt10">
                                        <strong><?php echo app_lang('files'); ?>:</strong>
                                        <div class="mt5">
                                            <?php foreach ($files as $file) { ?>
                                                <?php
                                                $file_name = $file->original_file_name ? $file->original_file_name : $file->file_name;
                                                ?>
                                                <div>
                                                    <?php echo js_anchor(remove_file_prefix($file_name), array("data-toggle" => "app-modal", "data-sidebar" => "0", "data-url" => get_uri("purchases_goods_receipts/file_preview/" . $file->id))); ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".order-status-form").appForm({
            onSuccess: function () {
                window.location.reload();
            }
        });
    });
</script>
