<?php
$model_info = $model_info ?? (object) array(
    'id' => 0,
    'name' => '',
    'description' => '',
    'has_expiration' => 0,
    'is_active' => 1,
);
?>

<?php echo form_open(get_uri('ged/document_types/save'), array('id' => 'ged-document-type-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id); ?>" />

    <div class="form-group">
        <label for="name" class="form-label"><?php echo app_lang('ged_field_name'); ?> *</label>
        <input type="text" id="name" name="name" value="<?php echo esc($model_info->name); ?>" class="form-control" required />
    </div>

    <div class="form-group">
        <label for="description" class="form-label"><?php echo app_lang('ged_field_description'); ?></label>
        <textarea id="description" name="description" class="form-control" rows="4"><?php echo esc($model_info->description); ?></textarea>
    </div>

    <div class="form-group">
        <div class="form-check">
            <?php echo form_checkbox('has_expiration', '1', !empty($model_info->has_expiration), "id='has_expiration' class='form-check-input'"); ?>
            <label for="has_expiration" class="form-check-label"><?php echo app_lang('ged_field_has_expiration'); ?></label>
        </div>
    </div>

    <div class="form-group">
        <div class="form-check">
            <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "id='is_active' class='form-check-input'"); ?>
            <label for="is_active" class="form-check-label"><?php echo app_lang('ged_field_active'); ?></label>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary btn-sm"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#ged-document-type-form .select2").select2();

        $("#ged-document-type-form").appForm({
            onSuccess: function (result) {
                $("#ged-document-types-table").appTable({newData: result.data, dataId: result.id});
            }
        });
    });
</script>
