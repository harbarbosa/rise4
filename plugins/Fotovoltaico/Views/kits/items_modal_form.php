<div class="modal-body clearfix">
    <input type="hidden" id="fv-kit-id" value="<?php echo (int)$kit_id; ?>" />

    <div class="mb15">
        <h4><?php echo app_lang('fv_kit_items'); ?></h4>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table id="fv-kit-items-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>

    <?php echo form_open(get_uri('fotovoltaico/kit_items_save'), array('id' => 'fv-kit-item-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <input type="hidden" name="id" value="" />
        <input type="hidden" name="kit_id" value="<?php echo (int)$kit_id; ?>" />

        <div class="form-group">
            <label for="product_id"><?php echo app_lang('fv_product'); ?></label>
            <?php echo form_dropdown('product_id', $products, '', "class='select2 validate-hidden' id='product_id' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "'"); ?>
        </div>

        <div class="form-group">
            <label for="quantity"><?php echo app_lang('quantity'); ?></label>
            <?php echo form_input(array('id' => 'quantity', 'name' => 'quantity', 'value' => 1, 'class' => 'form-control')); ?>
        </div>

        <div class="form-group">
            <label for="is_optional"><?php echo app_lang('optional'); ?></label>
            <?php echo form_checkbox('is_optional', '1', false, "id='is_optional'"); ?>
        </div>
    <?php echo form_close(); ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="button" class="btn btn-primary" id="fv-kit-item-save"><?php echo app_lang('save'); ?></button>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();
        $("#fv-kit-items-table").appTable({
            source: '<?php echo_uri("fotovoltaico/kit_items_list_data"); ?>',
            postData: {kit_id: <?php echo (int)$kit_id; ?>},
            columns: [
                {title: '<?php echo app_lang("type"); ?>'},
                {title: '<?php echo app_lang("title"); ?>'},
                {title: '<?php echo app_lang("quantity"); ?>'},
                {title: '<?php echo app_lang("optional"); ?>'}
            ]
        });

        $('#fv-kit-item-form').appForm({
            onSuccess: function () {
                $("#fv-kit-items-table").appTable({newData: null});
                $('#fv-kit-item-form')[0].reset();
                $('#product_id').val('').trigger('change');
            }
        });

        $('#fv-kit-item-save').on('click', function () {
            $('#fv-kit-item-form').trigger('submit');
        });
    });
</script>
