<?php
$model_info = $model_info ?? (object) array();
$plan_statuses = is_array($plan_statuses ?? null) ? $plan_statuses : array();
$nc_dropdown = is_array($nc_dropdown ?? null) ? $nc_dropdown : array();
$responsibles_dropdown = is_array($responsibles_dropdown ?? null) ? $responsibles_dropdown : array();
$priorities_dropdown = is_array($priorities_dropdown ?? null) ? $priorities_dropdown : array();

$nc_options = array('' => '-');
foreach ($nc_dropdown as $nc) {
    $nc_options[$nc->id] = trim((string) ($nc->code ? ($nc->code . ' - ') : '') . ($nc->title ?? ('#' . $nc->id)));
}
?>
<?php echo form_open(get_uri('laudostecnicos/nao-conformidades/save_plan'), array('id' => 'laudostecnicos-plan-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Nao conformidade</label><?php echo form_dropdown('nonconformity_id', $nc_options, $model_info->nonconformity_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Codigo</label><input type="text" name="code" class="form-control" value="<?php echo esc($model_info->code ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Status</label><?php echo form_dropdown('status', $plan_statuses, $model_info->status ?? 'draft', "class='form-select'"); ?></div>
        <div class="col-md-12"><label class="form-label">Acao</label><textarea name="action" class="form-control" rows="2"><?php echo esc($model_info->action ?? ''); ?></textarea></div>
        <div class="col-md-12"><label class="form-label">Motivo</label><textarea name="motive" class="form-control" rows="2"><?php echo esc($model_info->motive ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">Local</label><input type="text" name="location_text" class="form-control" value="<?php echo esc($model_info->location_text ?? ''); ?>"></div>
        <div class="col-md-6"><label class="form-label">Empresa responsavel</label><input type="text" name="company_name" class="form-control" value="<?php echo esc($model_info->company_name ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Responsavel</label><?php echo form_dropdown('responsible_id', $responsibles_dropdown, $model_info->responsible_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Prioridade</label><?php echo form_dropdown('priority', $priorities_dropdown, $model_info->priority ?? 'medium', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Prazo</label><input type="date" name="deadline" class="form-control" value="<?php echo esc($model_info->deadline ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Custo estimado</label><input type="text" name="estimated_cost" class="form-control" value="<?php echo esc($model_info->estimated_cost ?? ''); ?>"></div>
        <div class="col-md-8"><label class="form-label">Metodo</label><textarea name="method" class="form-control" rows="2"><?php echo esc($model_info->method ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">What</label><textarea name="what_field" class="form-control" rows="2"><?php echo esc($model_info->what_field ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">Why</label><textarea name="why_field" class="form-control" rows="2"><?php echo esc($model_info->why_field ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">Where</label><textarea name="where_field" class="form-control" rows="2"><?php echo esc($model_info->where_field ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">When</label><textarea name="when_field" class="form-control" rows="2"><?php echo esc($model_info->when_field ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">Who</label><textarea name="who_field" class="form-control" rows="2"><?php echo esc($model_info->who_field ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">How</label><textarea name="how_field" class="form-control" rows="2"><?php echo esc($model_info->how_field ?? ''); ?></textarea></div>
        <div class="col-md-12"><label class="form-label">How much</label><textarea name="how_much_field" class="form-control" rows="2"><?php echo esc($model_info->how_much_field ?? ''); ?></textarea></div>
        <div class="col-md-12"><label class="form-label">Evidencias (JSON)</label><textarea name="evidence_json" class="form-control" rows="2"><?php echo esc($model_info->evidence_json ?? '[]'); ?></textarea></div>
        <div class="col-md-4"><label class="form-label">Data conclusao</label><input type="datetime-local" name="completion_date" class="form-control" value="<?php echo esc(str_replace(' ', 'T', substr((string) ($model_info->completion_date ?? ''), 0, 16))); ?>"></div>
        <div class="col-md-4"><label class="form-label">Validador</label><?php echo form_dropdown('validator_id', $responsibles_dropdown, $model_info->validator_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-2 form-check mt-4">
            <?php echo form_checkbox('auto_create_task', '1', !empty($model_info->auto_create_task), "id='auto_create_task' class='form-check-input'"); ?>
            <label class="form-check-label" for="auto_create_task">Criar tarefa</label>
        </div>
        <div class="col-md-2 form-check mt-4">
            <?php echo form_checkbox('task_sync_enabled', '1', !empty($model_info->task_sync_enabled), "id='task_sync_enabled' class='form-check-input'"); ?>
            <label class="form-check-label" for="task_sync_enabled">Sincronizar</label>
        </div>
        <div class="col-md-6"><label class="form-label">Titulo da tarefa</label><input type="text" name="task_title" class="form-control" value="<?php echo esc($model_info->task_title ?? ''); ?>"></div>
        <div class="col-md-6"><label class="form-label">Descricao da tarefa</label><input type="text" name="task_description" class="form-control" value="<?php echo esc($model_info->task_description ?? ''); ?>"></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>
<script>
    $("#laudostecnicos-plan-form").appForm();
</script>
