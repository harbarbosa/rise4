<?php
$info = $request_info;
$request_code = $info->request_code ? $info->request_code : ('#' . $info->id);
$project_name = $info->project_title ? $info->project_title : ($info->cost_center ? $info->cost_center : '-');
$os_title = isset($info->os_title) && $info->os_title ? $info->os_title : ($info->os_id ? ('OS #' . $info->os_id) : '-');
$is_internal = !empty($info->is_internal);
$priority_key = 'purchases_priority_' . $info->priority;
$priority_label = app_lang($priority_key) ? app_lang($priority_key) : $info->priority;
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_request'); ?> <?php echo esc($request_code); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($has_quotation) && !empty($quotation_id)) { ?>
                    <?php echo anchor(get_uri('purchases_quotations/view/' . $quotation_id), "<i data-feather='layers' class='icon-16'></i> " . app_lang('purchases_view_quotation'), array('class' => 'btn btn-default')); ?>
                <?php } else if (!empty($can_create_quotation)) { ?>
                    <?php echo anchor(get_uri('purchases_quotations/create_from_request/' . $info->id), "<i data-feather='layers' class='icon-16'></i> " . app_lang('purchases_create_quotation'), array('class' => 'btn btn-default')); ?>
                <?php } ?>
                <?php if (!empty($can_generate_po_from_request) && !empty($quotation_id)) { ?>
                    <button type="button" class="btn btn-info js-generate-po" data-quotation="<?php echo esc($quotation_id); ?>">
                        <i data-feather='shopping-cart' class='icon-16'></i> <?php echo app_lang('purchases_generate_po'); ?>
                    </button>
                <?php } ?>
                <?php if ($can_edit) { ?>
                    <?php echo anchor(get_uri('purchases_requests/request_form/' . $info->id), "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit'), array('class' => 'btn btn-default')); ?>
                <?php } ?>
                <?php if (!empty($can_reopen)) { ?>
                    <button type="button" class="btn btn-warning js-reopen-request" data-id="<?php echo esc($info->id); ?>">
                        <i data-feather='rotate-ccw' class='icon-16'></i> <?php echo app_lang('purchases_reopen_request'); ?>
                    </button>
                <?php } ?>
                <?php echo anchor(get_uri('purchases_requests'), app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <td class="w150"><?php echo app_lang('purchases_request_code'); ?></td>
                            <td><?php echo esc($request_code); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('project'); ?></td>
                            <td><?php echo esc($is_internal ? '-' : $project_name); ?></td>
                        </tr>
                        <?php if ($info->os_id) { ?>
                            <tr>
                                <td><?php echo app_lang('purchases_os'); ?></td>
                                <td><?php echo esc($os_title); ?></td>
                            </tr>
                        <?php } ?>
                        <?php if ($is_internal) { ?>
                            <tr>
                                <td><?php echo app_lang('purchases_internal'); ?></td>
                                <td><?php echo app_lang('yes'); ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td><?php echo app_lang('purchases_priority'); ?></td>
                            <td><?php echo esc($priority_label); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('purchases_requested_by'); ?></td>
                            <td><?php echo esc($info->requested_by_name ? $info->requested_by_name : '-'); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('purchases_request_date'); ?></td>
                            <td><?php echo $info->created_at ? format_to_date($info->created_at, false) : '-'; ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('purchases_status'); ?></td>
                            <td><?php echo $status_label; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <td class="w150"><?php echo app_lang('purchases_note'); ?></td>
                            <td><?php echo nl2br(esc($info->note)); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo app_lang('purchases_status_history'); ?></td>
                            <td>
                                <div>
                                    <div><?php echo app_lang('purchases_status_draft'); ?>: <?php echo $info->created_at ? format_to_datetime($info->created_at) : '-'; ?></div>
                                    <?php if ($info->submitted_at) { ?><div><?php echo app_lang('purchases_status_sent_to_quotation'); ?>: <?php echo format_to_datetime($info->submitted_at); ?></div><?php } ?>
                                    <?php if ($info->approved_at) { ?><div><?php echo app_lang('purchases_status_approved'); ?>: <?php echo format_to_datetime($info->approved_at); ?></div><?php } ?>
                                    <?php if ($info->rejected_at) { ?><div><?php echo app_lang('purchases_status_rejected'); ?>: <?php echo format_to_datetime($info->rejected_at); ?></div><?php } ?>
                                    <?php if ($info->converted_at) { ?><div><?php echo app_lang('purchases_status_converted'); ?>: <?php echo format_to_datetime($info->converted_at); ?></div><?php } ?>
                                </div>
                            </td>
                        </tr>
                        <?php if ($info->rejected_reason) { ?>
                            <tr>
                                <td><?php echo app_lang('purchases_rejected_reason'); ?></td>
                                <td><?php echo nl2br(esc($info->rejected_reason)); ?></td>
                            </tr>
                        <?php } ?>
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
                                <th><?php echo app_lang('purchases_desired_date'); ?></th>
                                <th><?php echo app_lang('purchases_note'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($request_items)) { ?>
                                <?php foreach ($request_items as $item) { ?>
                                    <tr>
                                        <td><?php echo esc($item->item_title ? $item->item_title : '-'); ?></td>
                                        <td><?php echo esc($item->description); ?></td>
                                        <td class="text-right"><?php echo esc(to_decimal_format($item->quantity)); ?></td>
                                        <td><?php echo esc($item->unit ? $item->unit : $item->item_unit); ?></td>
                                        <td><?php echo $item->desired_date ? format_to_date($item->desired_date, false) : '-'; ?></td>
                                        <td><?php echo esc($item->note); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center text-off">-</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($quotation_suppliers) && !empty($quotation_items)) { ?>
                <div class="mt20">
                    <h4 class="mb10"><?php echo app_lang('purchases_quotation'); ?></h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo app_lang('purchases_material'); ?></th>
                                    <th><?php echo app_lang('purchases_item_description'); ?></th>
                                    <th class="text-right"><?php echo app_lang('purchases_qty'); ?></th>
                                    <?php foreach ($quotation_suppliers as $supplier) { ?>
                                        <th class="text-center"><?php echo esc($supplier->supplier_name); ?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotation_items as $item) { ?>
                                    <tr>
                                        <td><?php echo esc($item->item_title ? $item->item_title : '-'); ?></td>
                                        <td><?php echo esc($item->request_description); ?></td>
                                        <td class="text-right"><?php echo esc(to_decimal_format($item->qty)); ?></td>
                                        <?php foreach ($quotation_suppliers as $supplier) { ?>
                                            <?php
                                            $price = get_array_value(get_array_value($quotation_prices_map, $item->request_item_id, array()), $supplier->supplier_id);
                                            ?>
                                            <td>
                                                <div class="small"><?php echo app_lang('purchases_unit_price'); ?>: <?php echo $price ? to_currency($price->unit_price) : '-'; ?></div>
                                                <div class="small"><?php echo app_lang('purchases_freight_value'); ?>: <?php echo $price ? to_currency($price->freight_value) : '-'; ?></div>
                                                <?php if ($price && $price->is_winner) { ?>
                                                    <span class="badge bg-success"><?php echo app_lang('purchases_winner'); ?></span>
                                                <?php } ?>
                                            </td>
                                        <?php } ?>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt10">
                        <h5 class="mb10"><?php echo app_lang('purchases_totals_by_supplier'); ?></h5>
                        <div class="row">
                            <?php foreach ($quotation_suppliers as $supplier) { ?>
                                <div class="col-md-4 mb10">
                                    <div class="p10 bg-light">
                                        <strong><?php echo esc($supplier->supplier_name); ?></strong>
                                        <div><?php echo to_currency(get_array_value($quotation_totals, $supplier->supplier_id, 0)); ?></div>
                                        <div class="text-muted small"><?php echo app_lang('purchases_winner_total'); ?>: <?php echo to_currency(get_array_value($quotation_winner_totals, $supplier->supplier_id, 0)); ?></div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if (!empty($approvals) || $info->status === 'awaiting_approval') { ?>
                <?php
                $approval_map = array();
                if (!empty($approvals)) {
                    foreach ($approvals as $approval_row) {
                        $approval_map[$approval_row->approval_type] = $approval_row;
                    }
                }
                $requester_already_approved = !empty($approval_map['requester']) && !empty($approval_map['requester']->approved);
                $financial_already_approved = !empty($approval_map['financial']) && !empty($approval_map['financial']->approved);
                ?>
                <div class="mt20">
                    <h4 class="mb10"><?php echo app_lang('purchases_approvals'); ?></h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo app_lang('purchases_approval_type'); ?></th>
                                    <th><?php echo app_lang('purchases_approval_status'); ?></th>
                                    <th><?php echo app_lang('purchases_approved_by'); ?></th>
                                    <th><?php echo app_lang('purchases_approved_at'); ?></th>
                                    <th><?php echo app_lang('purchases_approval_comment'); ?></th>
                                    <th><?php echo app_lang('purchases_approval_limit_used'); ?></th>
                                    <th><?php echo app_lang('purchases_approval_total'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($approvals)) { ?>
                                    <?php foreach ($approvals as $approval) { ?>
                                        <?php
                                        $status_label = $approval->approved ? app_lang('purchases_approval_status_approved') : app_lang('purchases_approval_status_pending');
                                        if ($info->status === 'rejected' && !$approval->approved) {
                                            $status_label = app_lang('purchases_approval_status_rejected');
                                        }
                                        $type_label = app_lang('purchases_approval_' . $approval->approval_type);
                                        ?>
                                        <tr>
                                            <td><?php echo esc($type_label); ?></td>
                                            <td><?php echo esc($status_label); ?></td>
                                            <td><?php echo esc($approval->approved_by_name ? $approval->approved_by_name : '-'); ?></td>
                                            <td><?php echo $approval->approved_at ? format_to_datetime($approval->approved_at) : '-'; ?></td>
                                            <td><?php echo esc($approval->comment ? $approval->comment : '-'); ?></td>
                                            <td><?php echo $approval->approval_limit_used ? to_currency($approval->approval_limit_used) : '-'; ?></td>
                                            <td><?php echo $approval->total_value_at_approval ? to_currency($approval->total_value_at_approval) : '-'; ?></td>
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

                    <?php if ($info->status === 'awaiting_approval') { ?>
                        <div class="mt10">
                            <label for="approval-comment" class="form-label"><?php echo app_lang('purchases_approval_comment'); ?></label>
                            <textarea id="approval-comment" class="form-control" rows="2"></textarea>
                        </div>

                        <?php
                        $financial_limit_exceeded = $has_financial_permission && !$can_approve_financial && !$is_admin;
                        ?>
                        <?php if ($financial_limit_exceeded) { ?>
                            <div class="text-danger small mt5">
                                <?php echo app_lang('purchases_financial_limit_exceeded'); ?>
                            </div>
                        <?php } ?>

                        <div class="mt10 d-flex flex-wrap gap-2">
                            <?php if ($can_approve_requester) { ?>
                                <?php if (!$requester_already_approved) { ?>
                                <button type="button"
                                    class="btn btn-success btn-sm js-approval-action"
                                    data-url="<?php echo get_uri('purchases_requests/approve_requester'); ?>"
                                    data-id="<?php echo esc($info->id); ?>">
                                    <i data-feather='check-circle' class='icon-16'></i> <?php echo app_lang('purchases_approve_as_requester'); ?>
                                </button>
                                <?php } ?>
                            <?php } ?>

                            <?php if ($can_approve_financial) { ?>
                                <?php if (!$financial_already_approved) { ?>
                                <button type="button"
                                    class="btn btn-primary btn-sm js-approval-action"
                                    data-url="<?php echo get_uri('purchases_requests/approve_financial'); ?>"
                                    data-id="<?php echo esc($info->id); ?>">
                                    <i data-feather='check-circle' class='icon-16'></i> <?php echo app_lang('purchases_approve_as_financial'); ?>
                                </button>
                                <?php } ?>
                            <?php } ?>

                            <?php if ($can_reject_approval) { ?>
                                <button type="button"
                                    class="btn btn-danger btn-sm js-approval-action"
                                    data-url="<?php echo get_uri('purchases_requests/reject_approval'); ?>"
                                    data-id="<?php echo esc($info->id); ?>"
                                    data-requires-comment="1">
                                    <i data-feather='x-circle' class='icon-16'></i> <?php echo app_lang('purchases_reject_approval'); ?>
                                </button>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php
            $can_show_reminders = function_exists('can_access_reminders_module') ? can_access_reminders_module() : false;
            $task_col_class = $can_show_reminders ? "col-md-6" : "col-md-12";
            ?>
            <div class="row mt20">
                <div class="<?php echo $task_col_class; ?>">
                    <?php
                    echo view("Purchases\\Views\\requests\\tasks\\index", array(
                        "request_id" => (int)($info->id ?? 0)
                    ));
                    ?>
                </div>
                <?php if ($can_show_reminders) { ?>
                    <div class="col-md-6">
                        <div class="card reminders-card" id="purchase-request-reminders">
                            <div class="card-header fw-bold">
                                <i data-feather="clock" class="icon-16"></i> &nbsp;<?php echo app_lang("reminders") . " (" . app_lang('private') . ")"; ?>
                            </div>
                            <div class="card-body">
                                <?php echo view("Purchases\\Views\\requests\\reminders_view_data", array(
                                    "plugin_request_id" => (int)($info->id ?? 0),
                                    "hide_form" => true
                                )); ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="mt20">
                <?php if ($can_submit) { ?>
                    <button type="button" id="rc-submit-btn" class="btn btn-primary btn-sm"><i data-feather='send' class='icon-16'></i> <?php echo app_lang('purchases_submit_for_quotation'); ?></button>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php echo form_open(get_uri('purchases_requests/submit'), array('id' => 'rc-submit-form', 'class' => 'general-form')); ?>
<input type="hidden" name="id" value="<?php echo esc($info->id); ?>" />
<?php echo form_close(); ?>

<?php if (!empty($can_reopen) && !empty($reopen_targets)) { ?>
    <div class="modal fade" id="reopen-request-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><?php echo app_lang('purchases_reopen_request'); ?></h4>
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal" aria-label="Close">
                        <span data-feather="x" class="icon-16"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="reopen_request_target"><?php echo app_lang('purchases_reopen_target_status'); ?></label>
                        <select id="reopen_request_target" class="form-control">
                            <option value=""><?php echo "- " . app_lang('status') . " -"; ?></option>
                            <?php foreach ($reopen_targets as $target) { ?>
                                <option value="<?php echo esc($target['id']); ?>"><?php echo esc($target['text']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <p class="text-muted mb0"><?php echo app_lang('purchases_reopen_request_confirmation'); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
                    <button type="button" class="btn btn-warning js-confirm-reopen-request"><?php echo app_lang('save'); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<script type="text/javascript">
    $(document).ready(function () {
        var reopenRequestId = 0;

        <?php if (!empty($show_success_message)) { ?>
        appAlert.success("<?php echo app_lang('purchases_status_quotation_finalized'); ?>", {duration: 3000});
        <?php } ?>

        $(document).on('click', '#rc-submit-btn', function () {
            var $btn = $(this);
            $btn.prop('disabled', true);
            appAjaxRequest({
                url: "<?php echo get_uri('purchases_requests/submit'); ?>",
                type: "POST",
                dataType: "json",
                data: {id: "<?php echo esc($info->id); ?>"},
                success: function (result) {
                    if (result && result.success) {
                        if (result.message) {
                            appAlert.success(result.message, {duration: 3000});
                        }
                        setTimeout(function () {
                            location.reload();
                        }, 600);
                    } else if (result && result.message) {
                        appAlert.error(result.message);
                    } else {
                        appAlert.error("<?php echo app_lang('error_occurred'); ?>");
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });

        $(document).on("click", ".js-approval-action", function () {
            var $btn = $(this);
            var url = $btn.attr("data-url");
            var id = $btn.attr("data-id");
            var requiresComment = $btn.attr("data-requires-comment") === "1";
            var comment = $("#approval-comment").val();

            if (requiresComment && !comment) {
                appAlert.error("<?php echo app_lang('purchases_reject_comment_required'); ?>");
                return;
            }

            $btn.prop("disabled", true);
            appAjaxRequest({
                url: url,
                type: "POST",
                dataType: "json",
                data: {id: id, comment: comment},
                success: function (result) {
                    if (result && result.success) {
                        if (result.message) {
                            appAlert.success(result.message, {duration: 3000});
                        }
                        setTimeout(function () {
                            location.reload();
                        }, 600);
                    } else if (result && result.message) {
                        appAlert.error(result.message);
                    } else {
                        appAlert.error("<?php echo app_lang('error_occurred'); ?>");
                    }
                },
                complete: function () {
                    $btn.prop("disabled", false);
                }
            });
        });

        $(document).on("click", ".js-generate-po", function () {
            var $btn = $(this);
            var quotationId = $btn.data("quotation");
            if (!quotationId) {
                return;
            }
            $btn.prop("disabled", true);
            appAjaxRequest({
                url: "<?php echo get_uri('purchases_quotations/generate_po'); ?>/" + quotationId,
                type: "POST",
                dataType: "json",
                success: function (result) {
                    if (result && result.success) {
                        if (result.message) {
                            appAlert.success(result.message, {duration: 3000});
                        }
                        if (result.order_ids && result.order_ids.length) {
                            var targetUrl = "<?php echo get_uri('purchases_orders/view'); ?>/" + result.order_ids[0];
                            setTimeout(function () {
                                window.location = targetUrl;
                            }, 600);
                            return;
                        }
                    }
                    if (result && result.message) {
                        appAlert.error(result.message);
                    } else {
                        appAlert.error("<?php echo app_lang('error_occurred'); ?>");
                    }
                },
                complete: function () {
                    $btn.prop("disabled", false);
                }
            });
        });

        $(document).on("click", ".js-reopen-request", function () {
            reopenRequestId = $(this).data("id");
            if (!reopenRequestId) {
                return;
            }

            $("#reopen_request_target").val("");
            $("#reopen-request-modal").modal("show");
        });

        $(document).on("click", ".js-confirm-reopen-request", function () {
            var $btn = $(this);
            var targetStatus = $("#reopen_request_target").val();

            if (!reopenRequestId) {
                return;
            }

            if (!targetStatus) {
                appAlert.error("<?php echo app_lang('purchases_reopen_select_status'); ?>");
                return;
            }

            $btn.prop("disabled", true);
            appAjaxRequest({
                url: "<?php echo get_uri('purchases_requests/reopen'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    id: reopenRequestId,
                    target_status: targetStatus
                },
                success: function (result) {
                    if (result && result.success) {
                        $("#reopen-request-modal").modal("hide");
                        if (result.message) {
                            appAlert.success(result.message, {duration: 3000});
                        }
                        setTimeout(function () {
                            location.reload();
                        }, 600);
                    } else if (result && result.message) {
                        appAlert.error(result.message);
                    } else {
                        appAlert.error("<?php echo app_lang('error_occurred'); ?>");
                    }
                },
                complete: function () {
                    $btn.prop("disabled", false);
                }
            });
        });

        <?php if (!empty($can_reopen) && !empty($reopen_targets)) { ?>
        $("#reopen_request_target").select2({
            width: "100%"
        });
        <?php } ?>
    });
</script>



