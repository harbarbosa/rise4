<?php echo form_open(get_uri('laudostecnicos/tipos-medicao/save'), array('id' => 'laudostecnicos-measurement-type-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="<?php echo esc($model_info->name ?? ''); ?>" required></div>
        <div class="col-md-6"><label class="form-label">Grandeza</label><input type="text" name="quantity" class="form-control" value="<?php echo esc($model_info->quantity ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Unidade</label><input type="text" name="unit" class="form-control" value="<?php echo esc($model_info->unit ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Minimo</label><input type="text" name="min_value" class="form-control" value="<?php echo esc($model_info->min_value ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Maximo</label><input type="text" name="max_value" class="form-control" value="<?php echo esc($model_info->max_value ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Referencia</label><input type="text" name="reference_value" class="form-control" value="<?php echo esc($model_info->reference_value ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Tolerancia</label><input type="text" name="tolerance_value" class="form-control" value="<?php echo esc($model_info->tolerance_value ?? ''); ?>"></div>
        <div class="col-md-2"><label class="form-label">Casas</label><input type="number" name="decimal_places" class="form-control" value="<?php echo esc($model_info->decimal_places ?? 2); ?>"></div>
        <div class="col-md-2"><label class="form-label d-block">Auto</label><?php echo form_checkbox('auto_classification', '1', !empty($model_info->auto_classification), "class='form-check-input'"); ?></div>
        <div class="col-md-6"><label class="form-label">Descricao</label><input type="text" name="description" class="form-control" value="<?php echo esc($model_info->description ?? ''); ?>"></div>
        <div class="col-md-4"><label class="form-label">Status</label><input type="text" name="status" class="form-control" value="<?php echo esc($model_info->status ?? 'active'); ?>"></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>
<script>$("#laudostecnicos-measurement-type-form").appForm();</script>
