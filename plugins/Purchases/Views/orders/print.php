<?php
$order = $order_info;
$po_code = $order->po_code ? $order->po_code : ('#' . $order->id);
$project = $order->project_title ? $order->project_title : ($order->cost_center ? $order->cost_center : '-');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php echo esc($po_code); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; }
        .box { border: 1px solid #ccc; padding: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f5f5f5; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title"><?php echo app_lang('purchases_purchase_order'); ?> <?php echo esc($po_code); ?></div>
        <div><?php echo app_lang('date'); ?>: <?php echo $order->order_date ? format_to_date($order->order_date, false) : '-'; ?></div>
    </div>

    <div class="box">
        <strong><?php echo app_lang('purchases_supplier'); ?>:</strong> <?php echo esc($order->supplier_name ? $order->supplier_name : '-'); ?><br />
        <strong><?php echo app_lang('project'); ?>:</strong> <?php echo esc($project); ?><br />
        <strong><?php echo app_lang('purchases_expected_delivery_date'); ?>:</strong> <?php echo $order->expected_delivery_date ? format_to_date($order->expected_delivery_date, false) : '-'; ?><br />
        <strong><?php echo app_lang('purchases_delivery_address'); ?>:</strong> <?php echo esc($order->delivery_address ? $order->delivery_address : '-'); ?><br />
        <strong><?php echo app_lang('purchases_payment_terms'); ?>:</strong> <?php echo esc($order->payment_terms ? $order->payment_terms : '-'); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th><?php echo app_lang('purchases_item_description'); ?></th>
                <th class="right"><?php echo app_lang('purchases_qty'); ?></th>
                <th><?php echo app_lang('purchases_unit'); ?></th>
                <th class="right"><?php echo app_lang('purchases_unit_price'); ?></th>
                <th class="right"><?php echo app_lang('total'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_items as $item) { ?>
                <tr>
                    <td><?php echo esc($item->description); ?></td>
                    <td class="right"><?php echo esc($item->quantity); ?></td>
                    <td><?php echo esc($item->unit); ?></td>
                    <td class="right"><?php echo to_currency($item->rate); ?></td>
                    <td class="right"><?php echo to_currency($item->total); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="box" style="text-align:right;">
        <strong><?php echo app_lang('total'); ?>:</strong> <?php echo to_currency($order->total); ?>
    </div>
</body>
</html>
