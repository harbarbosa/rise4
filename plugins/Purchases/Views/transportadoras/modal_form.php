<?php
$model_info = $model_info ?? (object) array('id' => 0, 'name' => '', 'email' => '', 'phone' => '', 'tax_id' => '', 'address' => '');
?>

<?php echo form_open(get_uri('purchases_transportadoras/save'), array('id' => 'transportadora-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id); ?>" />

    <div class="form-group">
        <label for="name" class="form-label"><?php echo app_lang('purchases_transportadora_name'); ?></label>
        <input id="name" name="name" value="<?php echo esc($model_info->name); ?>" class="form-control" placeholder="<?php echo app_lang('purchases_transportadora_name'); ?>" />
    </div>

    <div class="form-group">
        <label for="email" class="form-label"><?php echo app_lang('purchases_transportadora_email'); ?></label>
        <input id="email" name="email" value="<?php echo esc($model_info->email); ?>" class="form-control" />
    </div>

    <div class="form-group">
        <label for="phone" class="form-label"><?php echo app_lang('purchases_transportadora_phone'); ?></label>
        <input id="phone" name="phone" value="<?php echo esc($model_info->phone); ?>" class="form-control" />
    </div>

    <div class="form-group">
        <label for="tax_id" class="form-label"><?php echo app_lang('purchases_transportadora_tax_id'); ?></label>
        <input id="tax_id" name="tax_id" value="<?php echo esc($model_info->tax_id); ?>" class="form-control" />
    </div>

    <div class="form-group">
        <label for="address" class="form-label"><?php echo app_lang('purchases_transportadora_address'); ?></label>
        <textarea id="address" name="address" class="form-control" rows="3"><?php echo esc($model_info->address); ?></textarea>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><i data-feather="x" class="icon-16"></i> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary btn-sm"><i data-feather="check-circle" class="icon-16"></i> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#transportadora-form").appForm({
            onSuccess: function (result) {
                $("#purchases-transportadoras-table").appTable({newData: result.data, dataId: result.id});
            }
        });
    });
</script>
