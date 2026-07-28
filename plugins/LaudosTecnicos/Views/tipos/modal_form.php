<?php
$model_info = isset($model_info) ? $model_info : null;
?>

<form method="post" action="<?php echo_uri('laudos_tecnicos/save_tipo'); ?>" class="modal-form">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo app_lang('laudos_type_add'); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="name"><?php echo app_lang('laudos_type_name'); ?> *</label>
                    <input type="text" name="name" id="name" class="form-control" required value="<?php echo $model_info ? $model_info->name : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="description"><?php echo app_lang('laudos_description'); ?></label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?php echo $model_info ? $model_info->description : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="prefix"><?php echo app_lang('laudos_type_prefix'); ?> *</label>
                    <input type="text" name="prefix" id="prefix" class="form-control" required value="<?php echo $model_info ? $model_info->prefix : ''; ?>" maxlength="10">
                </div>
                <div class="form-group">
                    <label for="validity_days"><?php echo app_lang('laudos_type_validity_days'); ?></label>
                    <input type="number" name="validity_days" id="validity_days" class="form-control" value="<?php echo $model_info ? $model_info->validity_days : '365'; ?>">
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="require_inspection" id="require_inspection" class="form-check-input" value="1" <?php echo $model_info && $model_info->require_inspection ? 'checked' : ''; ?>>
                        <label for="require_inspection" class="form-check-label"><?php echo app_lang('laudos_type_require_inspection'); ?></label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="require_approval" id="require_approval" class="form-check-input" value="1" <?php echo !$model_info || $model_info->require_approval ? 'checked' : ''; ?>>
                        <label for="require_approval" class="form-check-label"><?php echo app_lang('laudos_type_require_approval'); ?></label>
                    </div>
                </div>
                <input type="hidden" name="id" value="<?php echo $model_info ? $model_info->id : ''; ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('cancel'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
            </div>
        </div>
    </div>
</form>