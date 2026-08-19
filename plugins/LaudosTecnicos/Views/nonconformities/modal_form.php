<?php
$model_info = $model_info ?? (object) array();
$nc_statuses = is_array($nc_statuses ?? null) ? $nc_statuses : array();
$classification_options = is_array($classification_options ?? null) ? $classification_options : array();
$clients_dropdown = is_array($clients_dropdown ?? null) ? $clients_dropdown : array();
$laudos_rows = is_array($laudos_rows ?? null) ? $laudos_rows : array();
$inspections_rows = is_array($inspections_rows ?? null) ? $inspections_rows : array();
$equipments_dropdown = is_array($equipments_dropdown ?? null) ? $equipments_dropdown : array();
$checklists_dropdown = is_array($checklists_dropdown ?? null) ? $checklists_dropdown : array();
$norms_dropdown = is_array($norms_dropdown ?? null) ? $norms_dropdown : array();
$responsibles_dropdown = is_array($responsibles_dropdown ?? null) ? $responsibles_dropdown : array();

$laudos_dropdown = array('' => '-');
foreach ($laudos_rows as $laudo) {
    $laudos_dropdown[$laudo->id] = trim((string) ($laudo->number ?? $laudo->title ?? ('#' . $laudo->id)));
}

$inspections_dropdown = array('' => '-');
foreach ($inspections_rows as $inspection) {
    $inspections_dropdown[$inspection->id] = trim((string) ($inspection->code ?? ('#' . $inspection->id)));
}
?>
<?php echo form_open(get_uri('laudostecnicos/nao-conformidades/save'), array('id' => 'laudostecnicos-nc-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Codigo</label><input type="text" name="code" class="form-control" value="<?php echo esc($model_info->code ?? ''); ?>"></div>
        <div class="col-md-8"><label class="form-label">Titulo</label><input type="text" name="title" class="form-control" value="<?php echo esc($model_info->title ?? ''); ?>" required></div>
        <div class="col-md-12"><label class="form-label">Descricao</label><textarea name="description" class="form-control" rows="3"><?php echo esc($model_info->description ?? ''); ?></textarea></div>
        <div class="col-md-4"><label class="form-label">Cliente</label><?php echo form_dropdown('client_id', $clients_dropdown, $model_info->client_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Laudo</label><?php echo form_dropdown('laudo_id', $laudos_dropdown, $model_info->laudo_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Inspecao</label><?php echo form_dropdown('inspection_id', $inspections_dropdown, $model_info->inspection_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Local</label><input type="text" name="location_text" class="form-control" value="<?php echo esc($model_info->location_text ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Setor</label><input type="text" name="sector" class="form-control" value="<?php echo esc($model_info->sector ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Equipamento</label><?php echo form_dropdown('equipment_id', $equipments_dropdown, $model_info->equipment_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Checklist</label><?php echo form_dropdown('checklist_id', $checklists_dropdown, $model_info->checklist_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Norma</label><?php echo form_dropdown('norm_id', $norms_dropdown, $model_info->norm_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Classificacao</label><?php echo form_dropdown('classification', $classification_options, $model_info->classification ?? 'observacao', "class='form-select'"); ?></div>
        <div class="col-md-2"><label class="form-label">Probabilidade</label><input type="number" name="probability" class="form-control" value="<?php echo esc($model_info->probability ?? 1); ?>"></div>
        <div class="col-md-2"><label class="form-label">Impacto</label><input type="number" name="impact" class="form-control" value="<?php echo esc($model_info->impact ?? 1); ?>"></div>
        <div class="col-md-4"><label class="form-label">Nível de risco</label><input type="text" name="risk_level" class="form-control" value="<?php echo esc($model_info->risk_level ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Cor</label><input type="text" name="risk_color" class="form-control" value="<?php echo esc($model_info->risk_color ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Recomendacao</label><input type="text" name="recommendation" class="form-control" value="<?php echo esc($model_info->recommendation ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Prazo sugerido</label><input type="date" name="suggested_deadline" class="form-control" value="<?php echo esc($model_info->suggested_deadline ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Responsavel</label><?php echo form_dropdown('responsible_id', $responsibles_dropdown, $model_info->responsible_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Validador</label><?php echo form_dropdown('validator_id', $responsibles_dropdown, $model_info->validator_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Status</label><?php echo form_dropdown('status', $nc_statuses, $model_info->status ?? 'open', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Identificada em</label><input type="datetime-local" name="identified_at" class="form-control" value="<?php echo esc(str_replace(' ', 'T', substr((string) ($model_info->identified_at ?? ''), 0, 16))); ?>"></div>
        <div class="col-md-4"><label class="form-label">Corrigida em</label><input type="datetime-local" name="corrected_at" class="form-control" value="<?php echo esc(str_replace(' ', 'T', substr((string) ($model_info->corrected_at ?? ''), 0, 16))); ?>"></div>
        <div class="col-md-12"><label class="form-label">Evidencias (JSON)</label><textarea name="evidence_json" class="form-control" rows="2"><?php echo esc($model_info->evidence_json ?? '[]'); ?></textarea></div>
        <div class="col-md-12"><label class="form-label">Fotos (JSON)</label><textarea name="photos_json" class="form-control" rows="2"><?php echo esc($model_info->photos_json ?? '[]'); ?></textarea></div>
        <div class="col-md-12"><label class="form-label">Comentarios de correcao</label><textarea name="correction_comments" class="form-control" rows="2"><?php echo esc($model_info->correction_comments ?? ''); ?></textarea></div>
        <div class="col-md-12"><label class="form-label">Evidencia da correcao (JSON)</label><textarea name="correction_evidence_json" class="form-control" rows="2"><?php echo esc($model_info->correction_evidence_json ?? '[]'); ?></textarea></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>
<script>
    $("#laudostecnicos-nc-form").appForm();
</script>
