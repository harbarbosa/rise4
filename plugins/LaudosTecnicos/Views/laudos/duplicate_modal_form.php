<?php
$model_info = $model_info ?? (object) array();
$copy_options = is_array($copy_options ?? null) ? $copy_options : array();
?>

<div class="modal-body clearfix">
    <?php echo form_open(get_uri('laudostecnicos/laudos/duplicate'), array('id' => 'laudostecnicos-duplicate-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <input type="hidden" name="source_id" value="<?php echo esc($model_info->id ?? ''); ?>">
        <div class="alert alert-info">
            Duplicar o laudo <strong>#<?php echo esc($model_info->number ?? $model_info->id ?? ''); ?></strong> - <?php echo esc($model_info->title ?? ''); ?>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <?php echo form_checkbox('copy_general', '1', get_array_value($copy_options, 'copy_general') ? true : false, "id='copy_general' class='form-check-input'"); ?>
                <label for="copy_general" class="form-check-label">Copiar dados gerais</label>
            </div>
            <div class="col-md-6">
                <?php echo form_checkbox('copy_content', '1', get_array_value($copy_options, 'copy_content') ? true : false, "id='copy_content' class='form-check-input'"); ?>
                <label for="copy_content" class="form-check-label">Copiar conteúdo técnico</label>
            </div>
            <div class="col-md-6">
                <?php echo form_checkbox('copy_template', '1', get_array_value($copy_options, 'copy_template') ? true : false, "id='copy_template' class='form-check-input'"); ?>
                <label for="copy_template" class="form-check-label">Copiar template</label>
            </div>
            <div class="col-md-6">
                <?php echo form_checkbox('copy_team', '1', get_array_value($copy_options, 'copy_team') ? true : false, "id='copy_team' class='form-check-input'"); ?>
                <label for="copy_team" class="form-check-label">Copiar equipe</label>
            </div>
            <div class="col-md-6">
                <?php echo form_checkbox('copy_checklists', '1', get_array_value($copy_options, 'copy_checklists') ? true : false, "id='copy_checklists' class='form-check-input'"); ?>
                <label for="copy_checklists" class="form-check-label">Copiar checklists</label>
            </div>
            <div class="col-md-6">
                <?php echo form_checkbox('copy_norms', '1', get_array_value($copy_options, 'copy_norms') ? true : false, "id='copy_norms' class='form-check-input'"); ?>
                <label for="copy_norms" class="form-check-label">Copiar normas</label>
            </div>
            <div class="col-md-6">
                <?php echo form_checkbox('copy_equipments', '1', get_array_value($copy_options, 'copy_equipments') ? true : false, "id='copy_equipments' class='form-check-input'"); ?>
                <label for="copy_equipments" class="form-check-label">Copiar equipamentos</label>
            </div>
            <div class="col-md-6">
                <?php echo form_checkbox('copy_photos', '1', get_array_value($copy_options, 'copy_photos') ? true : false, "id='copy_photos' class='form-check-input'"); ?>
                <label for="copy_photos" class="form-check-label">Copiar fotografias</label>
            </div>
        </div>
    <?php echo form_close(); ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="button" id="duplicate-laudo-btn" class="btn btn-primary">Duplicar</button>
</div>

<script type="text/javascript">
    $(function () {
        $("#duplicate-laudo-btn").on("click", function () {
            $("#laudostecnicos-duplicate-form").trigger("submit");
        });

        $("#laudostecnicos-duplicate-form").appForm({
            onSuccess: function (result) {
                if (result && result.redirect_to) {
                    window.location = result.redirect_to;
                }
            }
        });
    });
</script>
