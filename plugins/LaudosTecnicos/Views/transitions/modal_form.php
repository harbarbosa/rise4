<?php echo form_open(get_uri('laudostecnicos/transitions/save'), array('id' => 'laudostecnicos-transition-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Status de origem</label>
                <?php echo form_dropdown('from_status_code', $statuses_dropdown, $model_info->from_status_code ?? '', "class='form-control select2'"); ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Status de destino</label>
                <?php echo form_dropdown('to_status_code', $statuses_dropdown, $model_info->to_status_code ?? '', "class='form-control select2'"); ?>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Perfis autorizados (JSON)</label>
                <textarea name="allowed_roles_json" class="form-control" rows="3"><?php echo esc($model_info->allowed_roles_json ?? '[]'); ?></textarea>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Permissoes necessarias (JSON)</label>
                <textarea name="required_permissions_json" class="form-control" rows="3"><?php echo esc($model_info->required_permissions_json ?? '[]'); ?></textarea>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Validacoes obrigatorias (JSON)</label>
                <textarea name="required_validations_json" class="form-control" rows="3"><?php echo esc($model_info->required_validations_json ?? '[]'); ?></textarea>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Titulo da tarefa automatica</label>
                <input type="text" name="task_title" class="form-control" value="<?php echo esc($model_info->task_title ?? ''); ?>">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Descricao da tarefa automatica</label>
                <textarea name="task_description" class="form-control" rows="3"><?php echo esc($model_info->task_description ?? ''); ?></textarea>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Ordem</label>
                <input type="number" name="sort" class="form-control" value="<?php echo esc($model_info->sort ?? 0); ?>">
            </div>
            <div class="col-md-4 form-check mt-4">
                <?php echo form_checkbox('require_comment', '1', !empty($model_info->require_comment), "id='require_comment_transition' class='form-check-input'"); ?>
                <label class="form-check-label" for="require_comment_transition">Comentario obrigatorio</label>
            </div>
            <div class="col-md-4 form-check mt-4">
                <?php echo form_checkbox('send_notification', '1', !empty($model_info->send_notification), "id='send_notification' class='form-check-input'"); ?>
                <label class="form-check-label" for="send_notification">Notificar</label>
            </div>
            <div class="col-md-4 form-check mt-2">
                <?php echo form_checkbox('auto_create_task', '1', !empty($model_info->auto_create_task), "id='auto_create_task' class='form-check-input'"); ?>
                <label class="form-check-label" for="auto_create_task">Criar tarefa automaticamente</label>
            </div>
            <div class="col-md-4 form-check mt-2">
                <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "id='laudostecnicos_transition_is_active' class='form-check-input'"); ?>
                <label class="form-check-label" for="laudostecnicos_transition_is_active"><?php echo app_lang('status'); ?></label>
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
        $("#laudostecnicos-transition-form .select2").select2();
        $("#laudostecnicos-transition-form").appForm();
    });
</script>
