<?php echo form_open(get_uri('laudostecnicos/equipamentos/save'), array('id' => 'laudostecnicos-equipment-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="<?php echo esc($model_info->name ?? ''); ?>" required></div>
        <div class="col-md-6"><label class="form-label">Tipo</label><input type="text" name="equipment_type" class="form-control" value="<?php echo esc($model_info->equipment_type ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Fabricante</label><input type="text" name="manufacturer" class="form-control" value="<?php echo esc($model_info->manufacturer ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Modelo</label><input type="text" name="model" class="form-control" value="<?php echo esc($model_info->model ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Serie</label><input type="text" name="serial_number" class="form-control" value="<?php echo esc($model_info->serial_number ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Patrimonio</label><input type="text" name="patrimony_number" class="form-control" value="<?php echo esc($model_info->patrimony_number ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Aquisicao</label><input type="text" name="acquisition_date" class="form-control" value="<?php echo esc($model_info->acquisition_date ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Ultima calibracao</label><input type="text" name="last_calibration" class="form-control" value="<?php echo esc($model_info->last_calibration ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Proxima calibracao</label><input type="text" name="next_calibration" class="form-control" value="<?php echo esc($model_info->next_calibration ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Certificado</label><input type="text" name="certificate" class="form-control" value="<?php echo esc($model_info->certificate ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Laboratorio</label><input type="text" name="laboratory" class="form-control" value="<?php echo esc($model_info->laboratory ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Status</label><input type="text" name="status" class="form-control" value="<?php echo esc($model_info->status ?? 'active'); ?>"></div>
        <div class="col-md-12"><label class="form-label">Observacoes</label><textarea name="observations" class="form-control"><?php echo esc($model_info->observations ?? ''); ?></textarea></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>
<script>$("#laudostecnicos-equipment-form").appForm();</script>
