<?php echo form_open(get_uri('laudostecnicos/medicoes/save'), array('id' => 'laudostecnicos-measurement-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Tipo</label><?php echo form_dropdown('measurement_type_id', $types_dropdown, $model_info->measurement_type_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-4"><label class="form-label">Laudo</label><input type="number" name="laudo_id" class="form-control" value="<?php echo esc($model_info->laudo_id ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Item checklist</label><input type="number" name="checklist_item_id" class="form-control" value="<?php echo esc($model_info->checklist_item_id ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Valor</label><input type="text" name="value" class="form-control" value="<?php echo esc($model_info->value ?? ''); ?>" required></div>
        <div class="col-md-2"><label class="form-label">Unidade</label><input type="text" name="unit" class="form-control" value="<?php echo esc($model_info->unit ?? ''); ?>"></div>
        <div class="col-md-3"><label class="form-label">Equipamento</label><?php echo form_dropdown('equipment_id', $equipments_dropdown, $model_info->equipment_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-3"><label class="form-label">Responsavel</label><input type="number" name="responsible_id" class="form-control" value="<?php echo esc($model_info->responsible_id ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Data/Hora</label><input type="text" name="measured_at" class="form-control" value="<?php echo esc($model_info->measured_at ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Local</label><input type="text" name="location" class="form-control" value="<?php echo esc($model_info->location ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Foto</label><input type="text" name="photo" class="form-control" value="<?php echo esc($model_info->photo ?? ''); ?>"></div>
        <div class="col-md-12"><label class="form-label">Observacao</label><textarea name="observation" class="form-control"><?php echo esc($model_info->observation ?? ''); ?></textarea></div>
        <div class="col-md-3"><label class="form-label">GPS Lat</label><input type="text" name="gps_lat" class="form-control" value="<?php echo esc($model_info->gps_lat ?? ''); ?>"></div>
        <div class="col-md-3"><label class="form-label">GPS Lng</label><input type="text" name="gps_lng" class="form-control" value="<?php echo esc($model_info->gps_lng ?? ''); ?>"></div>
        <div class="col-md-6"><label class="form-label">GPS Texto</label><input type="text" name="gps_text" class="form-control" value="<?php echo esc($model_info->gps_text ?? ''); ?>"></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>
<script>$("#laudostecnicos-measurement-form").appForm();</script>
