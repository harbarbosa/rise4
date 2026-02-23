<div class="modal-body clearfix">
    <?php echo form_open_multipart(get_uri('fotovoltaico/products/import_preview'), array('id' => 'fv-import-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <div class="form-group">
            <label><?php echo app_lang('fv_import_file'); ?></label>
            <input type="file" name="file" class="form-control" accept=".csv" required />
        </div>

        <div class="form-group">
            <label><?php echo app_lang('fv_default_type'); ?></label>
            <?php echo form_dropdown('default_type', $types, 'module', "class='select2'"); ?>
        </div>
    <?php echo form_close(); ?>

    <div id="fv-import-preview" class="mt15" style="display:none;">
        <h5><?php echo app_lang('fv_preview'); ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered" id="fv-preview-table">
                <thead>
                    <tr>
                        <th><?php echo app_lang('type'); ?></th>
                        <th><?php echo app_lang('brand'); ?></th>
                        <th><?php echo app_lang('model'); ?></th>
                        <th><?php echo app_lang('power_w'); ?></th>
                        <th><?php echo app_lang('cost'); ?></th>
                        <th><?php echo app_lang('price'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="button" class="btn btn-primary" id="fv-import-preview-btn"><?php echo app_lang('fv_preview'); ?></button>
    <button type="button" class="btn btn-success" id="fv-import-run-btn" style="display:none;"><?php echo app_lang('fv_import_run'); ?></button>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();
        var importToken = "";

        $("#fv-import-preview-btn").on("click", function () {
            var form = $("#fv-import-form")[0];
            var data = new FormData(form);

            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/products/import_preview'); ?>",
                type: "POST",
                data: data,
                processData: false,
                contentType: false,
                dataType: "json"
            }).done(function (res) {
                if (!res || !res.success) {
                    appAlert.error(res.message || "<?php echo app_lang('error_occurred'); ?>");
                    return;
                }
                importToken = res.data.token;
                var $tbody = $("#fv-preview-table tbody");
                $tbody.empty();
                (res.data.preview || []).forEach(function (row) {
                    $tbody.append("<tr><td>" + (row.type || "") + "</td><td>" + (row.brand || "") + "</td><td>" + (row.model || "") + "</td><td>" + (row.power_w || "") + "</td><td>" + (row.cost || "") + "</td><td>" + (row.price || "") + "</td></tr>");
                });
                $("#fv-import-preview").show();
                $("#fv-import-run-btn").show();
            });
        });

        $("#fv-import-run-btn").on("click", function () {
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/products/import_process'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    token: importToken,
                    default_type: $("#fv-import-form select[name='default_type']").val()
                }
            }).done(function (res) {
                if (!res || !res.success) {
                    appAlert.error(res.message || "<?php echo app_lang('error_occurred'); ?>");
                    return;
                }
                appAlert.success("<?php echo app_lang('record_saved'); ?>");
                $("#fv-products-table").appTable({newData: null});
            });
        });
    });
</script>
