<?php
$request = $request_info;
?>

<?php echo form_open(get_uri('purchases_quotations/save_from_request'), array('id' => 'quotation-create-form', 'class' => 'general-form', 'role' => 'form')); ?>
<input type="hidden" name="request_id" value="<?php echo esc($request->id); ?>" />

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_create_quotation'); ?></h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('purchases_requests/view/' . $request->id), app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb15">
                    <label class="form-label"><?php echo app_lang('purchases_request_code'); ?></label>
                    <div class="form-control-plaintext"><?php echo esc($request->request_code ? $request->request_code : ('#' . $request->id)); ?></div>
                </div>
                <div class="col-md-6 mb15">
                    <label for="supplier_ids" class="form-label"><?php echo app_lang('purchases_suppliers'); ?></label>
                    <?php echo form_dropdown("supplier_ids[]", $suppliers_dropdown, "", "class='select2' id='supplier_ids' multiple"); ?>
                    <small class="text-muted"><?php echo app_lang('purchases_suppliers_limit'); ?></small>
                    <div class="small text-muted mt5" id="quotation-create-selected-count"></div>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($request_items as $item) { ?>
                                <tr>
                                    <td><?php echo esc($item->item_title ? $item->item_title : '-'); ?></td>
                                    <td><?php echo esc($item->description); ?></td>
                                    <td class="text-right"><?php echo esc($item->quantity); ?></td>
                                    <td><?php echo esc($item->unit); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt15">
                <button type="submit" class="btn btn-primary"><i data-feather='check-circle' class='icon-16'></i> <?php echo app_lang('purchases_create_quotation'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var updateSelectedSuppliersCount = function () {
            var values = $("#supplier_ids").val() || [];
            $("#quotation-create-selected-count").text(values.length ? (values.length + " fornecedor(es) selecionado(s)") : "");
        };

        $("#supplier_ids").select2();
        $("#supplier_ids").on("change", updateSelectedSuppliersCount);
        updateSelectedSuppliersCount();

        $("#quotation-create-form").appForm({
            onSuccess: function (result) {
                if (result && result.redirect) {
                    window.location = result.redirect;
                } else {
                    window.location.reload();
                }
            },
            onError: function (result) {
                appAlert.error((result && result.message) ? result.message : "<?php echo app_lang('error_occurred'); ?>");
                return false;
            }
        });
    });
</script>
