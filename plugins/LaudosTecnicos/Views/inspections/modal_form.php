<?php
$model_info = $model_info ?? (object) array();
$laudos_rows = is_array($laudos_rows ?? null) ? $laudos_rows : array();
$laudos_dropdown = array('' => '-');
foreach ($laudos_rows as $laudo) {
    $laudos_dropdown[$laudo->id] = trim((string) ($laudo->number ?? $laudo->title ?? ('#' . $laudo->id)));
}
?>
<?php echo form_open(get_uri('laudostecnicos/inspecoes/save'), array('id' => 'laudostecnicos-inspection-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Codigo</label><input type="text" name="code" class="form-control" value="<?php echo esc($model_info->code ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Laudo</label><?php echo form_dropdown('laudo_id', $laudos_dropdown, $model_info->laudo_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Cliente</label><?php echo form_dropdown('client_id', $clients_dropdown, $model_info->client_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Unidade</label><input type="text" name="unit_name" class="form-control" value="<?php echo esc($model_info->unit_name ?? ''); ?>"></div>
        <div class="col-md-8"><label class="form-label">Local</label><input type="text" name="location_name" class="form-control" value="<?php echo esc($model_info->location_name ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Tipo</label><input type="text" name="inspection_type" class="form-control" value="<?php echo esc($model_info->inspection_type ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Data</label><input type="date" name="inspection_date" class="form-control" value="<?php echo esc($model_info->inspection_date ?? ''); ?>"></div>
        <div class="col-md-2"><label class="form-label">Horario inicio</label><input type="time" name="start_time" class="form-control" value="<?php echo esc(substr((string) ($model_info->start_time ?? '08:00:00'), 0, 5)); ?>"></div>
        <div class="col-md-2"><label class="form-label">Horario fim</label><input type="time" name="end_time" class="form-control" value="<?php echo esc(substr((string) ($model_info->end_time ?? ''), 0, 5)); ?>"></div>
        <div class="col-md-2"><label class="form-label">Duracao</label><input type="number" name="duration_minutes" class="form-control" value="<?php echo esc($model_info->duration_minutes ?? 60); ?>"></div>
        <div class="col-md-4"><label class="form-label">Responsavel</label><?php echo form_dropdown('responsible_id', $responsibles_dropdown, $model_info->responsible_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Veiculo</label><input type="text" name="vehicle" class="form-control" value="<?php echo esc($model_info->vehicle ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Status</label><?php echo form_dropdown('status', $statuses, $model_info->status ?? 'planned', "class='form-select'"); ?></div>
        <div class="col-md-12"><label class="form-label">Equipe (IDs separados por virgula)</label><input type="text" name="team_members" class="form-control" value="<?php echo esc(trim((string) ($model_info->team_json ?? '[]'), '[]')); ?>"></div>
        <div class="col-md-12"><label class="form-label">Equipamentos (IDs separados por virgula)</label><input type="text" name="equipment_ids" class="form-control" value="<?php echo esc(trim((string) ($model_info->equipments_json ?? '[]'), '[]')); ?>"></div>
        <div class="col-md-12"><label class="form-label">Endereco</label><textarea name="address" class="form-control"><?php echo esc($model_info->address ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label">Latitude</label><input type="text" name="latitude" class="form-control" value="<?php echo esc($model_info->latitude ?? ''); ?>"></div>
        <div class="col-md-6"><label class="form-label">Longitude</label><input type="text" name="longitude" class="form-control" value="<?php echo esc($model_info->longitude ?? ''); ?>"></div>
        <div class="col-md-12"><label class="form-label">Observacoes</label><textarea name="observations" class="form-control"><?php echo esc($model_info->observations ?? ''); ?></textarea></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>
<script>
    $("#laudostecnicos-inspection-form").appForm();
</script>
