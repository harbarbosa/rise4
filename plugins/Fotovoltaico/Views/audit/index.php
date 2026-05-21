<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_audit'); ?></h4>
        <div class="title-button-group float-end">
            <?php if ($can_manage_settings) { ?>
                <button type="button" class="btn btn-default" id="audit-cleanup-btn">
                    <i data-feather="trash-2" class="icon-16"></i> Cleanup
                </button>
            <?php } ?>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-body">
            <div class="row g-3 mb15">
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('entity'); ?></label>
                    <input type="text" id="audit-entity-type" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('action'); ?></label>
                    <input type="text" id="audit-action" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('id'); ?></label>
                    <input type="text" id="audit-entity-id" class="form-control" />
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w100p" id="audit-refresh-btn"><?php echo app_lang('refresh'); ?></button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="audit-table">
                    <thead>
                        <tr>
                            <th><?php echo app_lang('date'); ?></th>
                            <th><?php echo app_lang('user'); ?></th>
                            <th><?php echo app_lang('action'); ?></th>
                            <th><?php echo app_lang('entity'); ?></th>
                            <th><?php echo app_lang('id'); ?></th>
                            <th><?php echo app_lang('details'); ?></th>
                            <th><?php echo app_lang('status'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        function loadAudit() {
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/audit/list_data'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    entity_type: $("#audit-entity-type").val(),
                    action: $("#audit-action").val(),
                    entity_id: $("#audit-entity-id").val()
                },
                success: function (response) {
                    var html = [];
                    if (response && response.data) {
                        $.each(response.data, function (_, row) {
                            html.push("<tr>" +
                                "<td>" + row[0] + "</td>" +
                                "<td>" + row[1] + "</td>" +
                                "<td>" + row[2] + "</td>" +
                                "<td>" + row[3] + "</td>" +
                                "<td>" + row[4] + "</td>" +
                                "<td>" + row[5] + "</td>" +
                                "<td>" + row[6] + "</td>" +
                            "</tr>");
                        });
                    }
                    $("#audit-table tbody").html(html.join(""));
                }
            });
        }

        $("#audit-refresh-btn").on("click", loadAudit);
        $("#audit-cleanup-btn").on("click", function () {
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/audit/cleanup'); ?>",
                type: "POST",
                dataType: "json",
                success: function (response) {
                    if (response && response.success) {
                        appAlert.success(response.message);
                        loadAudit();
                    }
                }
            });
        });

        loadAudit();
    });
</script>
