<?php
$model_info = $model_info ?? (object) array();
$classification_options = is_array($classification_options ?? null) ? $classification_options : array();
$categories_dropdown = is_array($categories_dropdown ?? null) ? $categories_dropdown : array();
?>
<?php echo form_open(get_uri('laudostecnicos/nao-conformidades/save_matrix'), array('id' => 'laudostecnicos-matrix-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="<?php echo esc($model_info->name ?? ''); ?>" required></div>
        <div class="col-md-6"><label class="form-label">Categoria</label><?php echo form_dropdown('category_id', $categories_dropdown, $model_info->category_id ?? '', "class='form-select'"); ?></div>
        <div class="col-md-2"><label class="form-label">Probabilidade</label><input type="number" name="probability" class="form-control" value="<?php echo esc($model_info->probability ?? 1); ?>"></div>
        <div class="col-md-2"><label class="form-label">Impacto</label><input type="number" name="impact" class="form-control" value="<?php echo esc($model_info->impact ?? 1); ?>"></div>
        <div class="col-md-2"><label class="form-label">Resultado</label><input type="number" name="result" class="form-control" value="<?php echo esc($model_info->result ?? 1); ?>"></div>
        <div class="col-md-3"><label class="form-label">Classificacao</label><?php echo form_dropdown('classification', $classification_options, $model_info->classification ?? 'observacao', "class='form-select'"); ?></div>
        <div class="col-md-3"><label class="form-label">Cor</label><input type="text" name="color" class="form-control" value="<?php echo esc($model_info->color ?? '#6c757d'); ?>"></div>
        <div class="col-md-4"><label class="form-label">Prazo sugerido (dias)</label><input type="number" name="suggested_deadline_days" class="form-control" value="<?php echo esc($model_info->suggested_deadline_days ?? 30); ?>"></div>
        <div class="col-md-4"><label class="form-label">Ordem</label><input type="number" name="sort" class="form-control" value="<?php echo esc($model_info->sort ?? 0); ?>"></div>
        <div class="col-md-4 form-check mt-4">
            <?php echo form_checkbox('is_default', '1', !empty($model_info->is_default), "id='matrix_is_default' class='form-check-input'"); ?>
            <label class="form-check-label" for="matrix_is_default">Padrao</label>
        </div>
        <div class="col-md-4 form-check mt-2">
            <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "id='matrix_is_active' class='form-check-input'"); ?>
            <label class="form-check-label" for="matrix_is_active">Ativo</label>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>
<script>
    $("#laudostecnicos-matrix-form").appForm();
</script>
