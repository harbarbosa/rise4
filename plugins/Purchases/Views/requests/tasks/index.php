<div class="card">
    <div class="card-header fw-bold">
        <i data-feather="check-circle" class="icon-16"></i> &nbsp;<?php echo app_lang("tasks"); ?>
    </div>

    <div class="card-body">
        <?php
        echo modal_anchor(
            get_uri("tasks/modal_form"),
            "<i data-feather='plus' class='icon-16'></i> " . app_lang('add_task'),
            array(
                "class" => "",
                "data-post-context" => "general",
                "data-post-plugin_purchase_request_id" => $request_id,
                "title" => app_lang('add_task')
            )
        );
        ?>
    </div>

    <div class="table-responsive">
        <table id="purchase-request-task-table" class="display no-thead b-t b-b-only no-hover hide-dtr-control hide-status-checkbox" width="100%"></table>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var requestId = "<?php echo $request_id; ?>";

        $("#purchase-request-task-table").appTable({
            source: '<?php echo_uri("purchases_requests/tasks/list_data/" . $request_id); ?>',
            order: [[0, "desc"]],
            hideTools: true,
            displayLength: 100,
            stateSave: false,
            responsive: true,
            mobileMirror: true,
            reloadHooks: [{
                type: "app_form",
                id: "task-form",
                filter: {plugin_purchase_request_id: requestId}
            }],
            columns: [
                {title: "<?php echo app_lang('id'); ?>", order_by: "id"},
                {title: "<?php echo app_lang('title'); ?>", "class": "all", order_by: "title"},
                {title: "<?php echo app_lang('assigned_to'); ?>", "class": "min-w150", order_by: "assigned_to"},
                {title: "<?php echo app_lang('status'); ?>", order_by: "status"},
                {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center option w100"}
            ]
        });

        if (typeof registerAppFormHook === "function") {
            registerAppFormHook("task-form", function () {
                $("#purchase-request-task-table").appTable({reload: true});
            }, "purchase-request-task", requestId);
        }

        $("#ajaxModal").on("shown.bs.modal", function () {
            var $form = $("#task-form");
            if (!$form.length) {
                return;
            }

            if (!$form.find("input[name='plugin_purchase_request_id']").length) {
                $("<input>", {
                    type: "hidden",
                    name: "plugin_purchase_request_id",
                    value: requestId
                }).appendTo($form);
            }

            var $context = $form.find("#task-context");
            if ($context.length) {
                $context.val("general").trigger("change");
                $context.closest(".form-group").addClass("hide");
            }

            $form.find("input[name='proposal_id']").val("").prop("disabled", true);
            $form.find(".task-context-options").val("").prop("disabled", true);
        });
    });
</script>
