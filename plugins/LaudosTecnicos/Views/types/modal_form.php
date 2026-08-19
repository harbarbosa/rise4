<?php echo form_open(get_uri('laudostecnicos/tipos/save'), array('id' => 'laudostecnicos-type-form', 'class' => 'general-form', 'role' => 'form')); ?>
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
            <div class="col-md-6 mb-3">
                <label class="form-label">Categoria</label>
                <?php echo form_dropdown('category_id', $categories_dropdown, $model_info->category_id ?? '', "class='form-control select2'"); ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Prefixo</label>
                <input type="text" name="prefix" class="form-control" value="<?php echo esc($model_info->prefix ?? ''); ?>">
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Descricao</label>
                <textarea name="description" class="form-control" rows="3"><?php echo esc($model_info->description ?? ''); ?></textarea>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Template padrao</label>
                <input type="number" name="default_template_id" class="form-control" value="<?php echo esc($model_info->default_template_id ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Validade padrao (dias)</label>
                <input type="number" name="validity_days" class="form-control" value="<?php echo esc($model_info->validity_days ?? 365); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Ordem</label>
                <input type="number" name="sort" class="form-control" value="<?php echo esc($model_info->sort ?? 0); ?>">
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('require_technical_responsible', '1', !empty($model_info->require_technical_responsible), "id='require_technical_responsible' class='form-check-input'"); ?>
                <label class="form-check-label" for="require_technical_responsible">Exige responsavel tecnico</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('require_review', '1', !empty($model_info->require_review), "id='require_review' class='form-check-input'"); ?>
                <label class="form-check-label" for="require_review">Exige revisao</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('require_approval', '1', !empty($model_info->require_approval), "id='require_approval' class='form-check-input'"); ?>
                <label class="form-check-label" for="require_approval">Exige aprovacao</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('require_signature', '1', !empty($model_info->require_signature), "id='require_signature' class='form-check-input'"); ?>
                <label class="form-check-label" for="require_signature">Exige assinatura</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('require_inspection', '1', !empty($model_info->require_inspection), "id='require_inspection' class='form-check-input'"); ?>
                <label class="form-check-label" for="require_inspection">Exige inspecao</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('require_calibrated_equipment', '1', !empty($model_info->require_calibrated_equipment), "id='require_calibrated_equipment' class='form-check-input'"); ?>
                <label class="form-check-label" for="require_calibrated_equipment">Exige equipamento calibrado</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('allow_mobile', '1', !empty($model_info->allow_mobile), "id='allow_mobile' class='form-check-input'"); ?>
                <label class="form-check-label" for="allow_mobile">Permite mobile</label>
            </div>
            <div class="col-md-6 form-check mt-2">
                <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "id='laudostecnicos_type_is_active' class='form-check-input'"); ?>
                <label class="form-check-label" for="laudostecnicos_type_is_active"><?php echo app_lang('status'); ?></label>
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
        $("#laudostecnicos-type-form .select2").select2();
        $("#laudostecnicos-type-form").appForm();
    });
</script>
