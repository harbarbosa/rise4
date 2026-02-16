<?php
$kpis = $kpis ?? (object) array(
    "pending_requests" => 0,
    "approved_last_30" => 0,
    "open_orders" => 0,
    "open_orders_total" => 0,
    "receipts_last_30" => 0
);

$pending_requests = $pending_requests ?? array();
$open_orders = $open_orders ?? array();
$can_manage = $can_manage ?? false;
$can_approve = $can_approve ?? false;
$can_financial_approve = $can_financial_approve ?? false;
$login_user_id = $login_user_id ?? 0;
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_dashboard'); ?></h1>
        </div>
        <nav aria-label="breadcrumb" class="p15 pt0">
            <ol class="breadcrumb mb0">
                <li class="breadcrumb-item">
                    <a href="<?php echo get_uri('dashboard'); ?>"><i data-feather='home' class='icon-14'></i></a>
                </li>
                <li class="breadcrumb-item"><?php echo app_lang('purchases_menu'); ?></li>
                <li class="breadcrumb-item"><?php echo app_lang('purchases_dashboard'); ?></li>
            </ol>
        </nav>
        <div class="card-body">
            <div class="row mb20">
                <div class="col-md-3 col-sm-6 mb15">
                    <div class="card p15">
                        <div class="text-off"><?php echo app_lang('purchases_kpi_pending_requests'); ?></div>
                        <div class="fs-24 fw-bold"><?php echo esc($kpis->pending_requests); ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb15">
                    <div class="card p15">
                        <div class="text-off"><?php echo app_lang('purchases_kpi_approved_last_30'); ?></div>
                        <div class="fs-24 fw-bold"><?php echo esc($kpis->approved_last_30); ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb15">
                    <div class="card p15">
                        <div class="text-off"><?php echo app_lang('purchases_kpi_open_orders'); ?></div>
                        <div class="fs-24 fw-bold"><?php echo esc($kpis->open_orders); ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb15">
                    <div class="card p15">
                        <div class="text-off"><?php echo app_lang('purchases_kpi_recent_receipts'); ?></div>
                        <div class="fs-24 fw-bold"><?php echo esc($kpis->receipts_last_30); ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb15">
                    <div class="card p15">
                        <div class="text-off"><?php echo app_lang('purchases_kpi_open_orders_total'); ?></div>
                        <div class="fs-24 fw-bold"><?php echo to_currency($kpis->open_orders_total); ?></div>
                    </div>
                </div>
            </div>

            <div class="mb20">
                <h4 class="mb10"><?php echo app_lang('purchases_shortcuts'); ?></h4>
                <div class="btn-group">
                    <?php if ($can_manage) { ?>
                        <?php echo anchor(get_uri('purchases_requests/request_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('purchases_add_request'), array('class' => 'btn btn-default')); ?>
                    <?php } ?>
                    <?php echo anchor(get_uri('items'), "<i data-feather='package' class='icon-16'></i> " . app_lang('items'), array('class' => 'btn btn-default')); ?>
                    <?php echo anchor(get_uri('purchases_suppliers'), "<i data-feather='users' class='icon-16'></i> " . app_lang('purchases_suppliers'), array('class' => 'btn btn-default')); ?>
                    <?php echo anchor(get_uri('purchases_orders'), "<i data-feather='shopping-cart' class='icon-16'></i> " . app_lang('purchases_purchase_orders'), array('class' => 'btn btn-default')); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb20">
                    <div class="card">
                        <div class="card-header">
                            <h4><?php echo app_lang('purchases_pending_requests'); ?></h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo app_lang('purchases_request_code'); ?></th>
                                        <th><?php echo app_lang('purchases_project_or_cost_center'); ?></th>
                                        <th><?php echo app_lang('purchases_requested_by'); ?></th>
                                        <th><?php echo app_lang('date'); ?></th>
                                        <th><?php echo app_lang('purchases_status'); ?></th>
                                        <th><?php echo app_lang('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pending_requests)) { ?>
                                        <?php foreach ($pending_requests as $request) { ?>
                                            <?php
                                            $request_code = $request->request_code ? $request->request_code : ('#' . $request->id);
                                            $context = $request->project_title ? $request->project_title : ($request->cost_center ? $request->cost_center : '-');
                                            if (!empty($request->is_internal)) {
                                                $context = app_lang('purchases_internal');
                                            } else if (!empty($request->os_id)) {
                                                $context = isset($request->os_title) && $request->os_title ? $request->os_title : ('OS #' . $request->os_id);
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo esc($request_code); ?></td>
                                                <td><?php echo esc($context); ?></td>
                                                <td><?php echo esc($request->requested_by_name ? $request->requested_by_name : '-'); ?></td>
                                                <td><?php echo $request->created_at ? format_to_date($request->created_at, false) : '-'; ?></td>
                                                <td><span class="badge bg-warning"><?php echo app_lang('purchases_status_awaiting_approval'); ?></span></td>
                                                <td>
                                                    <?php echo anchor(get_uri('purchases_requests/view/' . $request->id), "<i data-feather='external-link' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-secondary', 'title' => app_lang('view_details'))); ?>
                                                    <?php
                                                    $approve_role = '';
                                                    if ((int)$request->requested_by === (int)$login_user_id) {
                                                        $approve_role = 'requester';
                                                    } elseif ($can_financial_approve) {
                                                        $approve_role = 'financial';
                                                    }
                                                    ?>
                                                    <?php if ($approve_role) { ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success js-rc-approve" data-id="<?php echo (int)$request->id; ?>" data-role="<?php echo esc($approve_role); ?>">
                                                            <i data-feather='check' class='icon-16'></i>
                                                        </button>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-off"><?php echo app_lang('purchases_no_records'); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb20">
                    <div class="card">
                        <div class="card-header">
                            <h4><?php echo app_lang('purchases_open_orders'); ?></h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo app_lang('purchases_po_code'); ?></th>
                                        <th><?php echo app_lang('purchases_supplier'); ?></th>
                                        <th><?php echo app_lang('project'); ?></th>
                                        <th><?php echo app_lang('date'); ?></th>
                                        <th><?php echo app_lang('purchases_status'); ?></th>
                                        <th><?php echo app_lang('total'); ?></th>
                                        <th><?php echo app_lang('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($open_orders)) { ?>
                                        <?php foreach ($open_orders as $order) { ?>
                                            <?php
                                            $po_code = $order->po_code ? $order->po_code : ('#' . $order->id);
                                            $project_label = $order->project_title ? $order->project_title : ($order->cost_center ? $order->cost_center : '-');
                                            $status_label = $order->status ? app_lang('purchases_po_status_' . $order->status) : $order->status;
                                            ?>
                                            <tr>
                                                <td><?php echo esc($po_code); ?></td>
                                                <td><?php echo esc($order->supplier_name ? $order->supplier_name : '-'); ?></td>
                                                <td><?php echo esc($project_label); ?></td>
                                                <td><?php echo $order->order_date ? format_to_date($order->order_date, false) : '-'; ?></td>
                                                <td><?php echo esc($status_label); ?></td>
                                                <td><?php echo to_currency($order->total); ?></td>
                                                <td>
                                                    <?php echo anchor(get_uri('purchases_orders/view/' . $order->id), "<i data-feather='external-link' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-secondary', 'title' => app_lang('view_details'))); ?>
                                                    <?php if ($can_manage) { ?>
                                                        <?php echo modal_anchor(get_uri('purchases_goods_receipts/modal_form'), "<i data-feather='inbox' class='icon-16'></i>", array('class' => 'btn btn-sm btn-outline-primary', 'title' => app_lang('purchases_register_receipt'), 'data-post-order_id' => $order->id, 'data-modal-lg' => '1')); ?>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-off"><?php echo app_lang('purchases_no_records'); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
            $(".js-rc-approve").on("click", function () {
            var $btn = $(this);
            var id = $btn.attr("data-id");
            var role = $btn.attr("data-role") || "requester";
            var url = role === "financial" ? "<?php echo get_uri('purchases_requests/approve_financial'); ?>" : "<?php echo get_uri('purchases_requests/approve_requester'); ?>";
            if (!id) {
                return;
            }

            $btn.appConfirmation({
                title: "<?php echo app_lang('are_you_sure'); ?>",
                btnConfirmLabel: "<?php echo app_lang('yes'); ?>",
                btnCancelLabel: "<?php echo app_lang('no'); ?>",
                onConfirm: function () {
                    appAjaxRequest({
                        url: url,
                        type: "POST",
                        dataType: "json",
                        data: {id: id},
                        success: function (result) {
                            if (result && result.success) {
                                appAlert.success("<?php echo app_lang('purchases_request_approved'); ?>", {duration: 3000});
                                setTimeout(function () {
                                    location.reload();
                                }, 600);
                            } else if (result && result.message) {
                                appAlert.error(result.message);
                            } else {
                                appAlert.error("<?php echo app_lang('error_occurred'); ?>");
                            }
                        }
                    });
                }
            });
        });
    });
</script>
