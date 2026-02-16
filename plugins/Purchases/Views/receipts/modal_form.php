<?php echo form_open(get_uri('purchases_goods_receipts/save'), array('id' => 'purchases-receipt-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div id="purchases-receipt-dropzone" class="post-dropzone">
    <div class="modal-body clearfix">
        <div class="container-fluid">
            <input type="hidden" name="order_id" value="<?php echo $order_info->id; ?>" />

            <div class="form-group">
                <div class="row">
                    <label for="receipt_date" class="col-md-3"><?php echo app_lang('purchases_receipt_date'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "receipt_date",
                            "name" => "receipt_date",
                            "value" => get_my_local_time("Y-m-d"),
                            "class" => "form-control",
                            "autocomplete" => "off",
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="received_by" class="col-md-3"><?php echo app_lang('purchases_received_by'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_dropdown("received_by", $received_by_dropdown, "", "class='select2' id='received_by'");
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="nf_number" class="col-md-3"><?php echo app_lang('purchases_nf_number'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "nf_number",
                            "name" => "nf_number",
                            "value" => "",
                            "class" => "form-control",
                            "placeholder" => app_lang('purchases_nf_number')
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="receipt_note" class="col-md-3"><?php echo app_lang('purchases_note'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "receipt_note",
                            "name" => "note",
                            "value" => "",
                            "class" => "form-control",
                            "placeholder" => app_lang('purchases_note'),
                            "rows" => 3
                        ));
                        ?>
                    </div>
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
                                <th class="text-right"><?php echo app_lang('purchases_pending_qty'); ?></th>
                                <th class="text-right"><?php echo app_lang('purchases_qty_received_now'); ?></th>
                                <th><?php echo app_lang('purchases_item_note'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $has_pending = false;
                            foreach ($items as $item) {
                                if ($item->pending_qty <= 0) {
                                    continue;
                                }
                                $has_pending = true;
                                ?>
                                <tr>
                                    <td><?php echo esc($item->description ? $item->description : '-'); ?></td>
                                    <td><?php echo esc($item->description); ?></td>
                                    <td class="text-right"><?php echo to_decimal_format($item->quantity); ?></td>
                                    <td class="text-right"><?php echo to_decimal_format($item->pending_qty); ?></td>
                                    <td class="text-right w150">
                                        <input type="hidden" name="order_item_id[]" value="<?php echo $item->id; ?>" />
                                        <?php
                                        echo form_input(array(
                                            "name" => "qty_received_now[]",
                                            "value" => "",
                                            "class" => "form-control text-right",
                                            "placeholder" => "0.00"
                                        ));
                                        ?>
                                    </td>
                                    <td class="w250">
                                        <?php
                                        echo form_input(array(
                                            "name" => "item_note[]",
                                            "value" => "",
                                            "class" => "form-control"
                                        ));
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php if (!$has_pending) { ?>
                                <tr>
                                    <td colspan="6" class="text-center"><?php echo app_lang('purchases_no_pending_items'); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php echo view("includes/dropzone_preview"); ?>
        </div>
    </div>

    <div class="modal-footer">
        <?php echo view("includes/upload_button"); ?>
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
        <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    </div>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#purchases-receipt-form").appForm({
            onSuccess: function () {
                location.reload();
            }
        });

        setDatePicker("#receipt_date");
        $("#received_by").select2();
    });
</script>
