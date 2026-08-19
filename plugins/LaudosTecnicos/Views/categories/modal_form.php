<?php echo form_open(get_uri('laudostecnicos/categorias/save'), array('id' => 'laudostecnicos-category-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label"><?php echo app_lang('name'); ?></label>
                <input type="text" name="name" class="form-control" value="<?php echo esc($model_info->name ?? ''); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label"><?php echo app_lang('code'); ?></label>
                <input type="text" name="code" class="form-control" value="<?php echo esc($model_info->code ?? ''); ?>" required>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label"><?php echo app_lang('description'); ?></label>
                <textarea name="description" class="form-control" rows="3"><?php echo esc($model_info->description ?? ''); ?></textarea>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo app_lang('color'); ?></label>
                <input type="text" name="color" class="form-control" value="<?php echo esc($model_info->color ?? '#0d6efd'); ?>" placeholder="#0d6efd">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo app_lang('icon'); ?></label>
                <input type="text" name="icon" class="form-control" value="<?php echo esc($model_info->icon ?? 'layers'); ?>" placeholder="layers">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo app_lang('sort'); ?></label>
                <input type="number" name="sort" class="form-control" value="<?php echo esc($model_info->sort ?? 0); ?>">
            </div>
            <div class="col-md-12 form-check mt-2">
                <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "id='laudostecnicos_category_is_active' class='form-check-input'"); ?>
                <label class="form-check-label" for="laudostecnicos_category_is_active"><?php echo app_lang('status'); ?></label>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">
        <span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?>
    </button>
    <button type="submit" class="btn btn-primary">
        <span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?>
    </button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-category-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    appAlert.success(result.message || "<?php echo app_lang('record_saved'); ?>");
                }
            }
        });
    });
</script>
