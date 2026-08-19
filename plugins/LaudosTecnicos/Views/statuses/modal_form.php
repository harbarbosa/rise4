<?php echo form_open(get_uri('laudostecnicos/statuses/save'), array('id' => 'laudostecnicos-status-form', 'class' => 'general-form', 'role' => 'form')); ?>
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
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo app_lang('color'); ?></label>
                <input type="text" name="color" class="form-control" value="<?php echo esc($model_info->color ?? '#0d6efd'); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo app_lang('icon'); ?></label>
                <input type="text" name="icon" class="form-control" value="<?php echo esc($model_info->icon ?? 'circle'); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label"><?php echo app_lang('sort'); ?></label>
                <input type="number" name="sort" class="form-control" value="<?php echo esc($model_info->sort ?? 0); ?>">
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('status_initial', '1', !empty($model_info->status_initial), "id='status_initial' class='form-check-input'"); ?>
                <label class="form-check-label" for="status_initial">Status inicial</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('status_final', '1', !empty($model_info->status_final), "id='status_final' class='form-check-input'"); ?>
                <label class="form-check-label" for="status_final">Status final</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('status_cancellation', '1', !empty($model_info->status_cancellation), "id='status_cancellation' class='form-check-input'"); ?>
                <label class="form-check-label" for="status_cancellation">Status de cancelamento</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('allow_edit', '1', !empty($model_info->allow_edit), "id='allow_edit' class='form-check-input'"); ?>
                <label class="form-check-label" for="allow_edit">Permite edicao</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('allow_delete', '1', !empty($model_info->allow_delete), "id='allow_delete' class='form-check-input'"); ?>
                <label class="form-check-label" for="allow_delete">Permite exclusao</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('allow_issue', '1', !empty($model_info->allow_issue), "id='allow_issue' class='form-check-input'"); ?>
                <label class="form-check-label" for="allow_issue">Permite emissao</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('require_comment', '1', !empty($model_info->require_comment), "id='require_comment' class='form-check-input'"); ?>
                <label class="form-check-label" for="require_comment">Exige comentario</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "id='laudostecnicos_status_is_active' class='form-check-input'"); ?>
                <label class="form-check-label" for="laudostecnicos_status_is_active"><?php echo app_lang('status'); ?></label>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>
<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-status-form").appForm();
    });
</script>
