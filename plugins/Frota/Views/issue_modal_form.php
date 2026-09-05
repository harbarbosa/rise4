<?php echo form_open(get_uri('frota/ocorrencias/salvar'), ['id' => 'frota-issue-form', 'class' => 'general-form', 'role' => 'form']); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />

        <div class="form-group"><div class="row"><label for="vehicle_id" class="col-md-3">Veículo</label><div class="col-md-9">
            <?php echo form_dropdown('vehicle_id', $vehicleOptions, [$model_info->vehicle_id ?? ''], "class='select2 validate-hidden' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "' id='vehicle_id'"); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="title" class="col-md-3"><?php echo app_lang('title'); ?></label><div class="col-md-9">
            <?php echo form_input(['id' => 'title', 'name' => 'title', 'value' => $model_info->title ?? '', 'class' => 'form-control', 'placeholder' => app_lang('title'), 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required')]); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label><div class="col-md-9">
            <?php echo form_textarea(['id' => 'description', 'name' => 'description', 'value' => $model_info->description ?? '', 'class' => 'form-control', 'placeholder' => app_lang('description'), 'rows' => 5, 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required')]); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="severity" class="col-md-3">Gravidade</label><div class="col-md-9">
            <?php echo form_dropdown('severity', ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'], [$model_info->severity ?? 'medium'], "class='select2' id='severity'"); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="odometer" class="col-md-3">KM</label><div class="col-md-9">
            <?php echo form_input(['id' => 'odometer', 'name' => 'odometer', 'value' => $model_info->odometer ?? '', 'class' => 'form-control frota-km-mask', 'placeholder' => '111.111.111', 'inputmode' => 'numeric']); ?>
        </div></div></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>
<script type="text/javascript">
$(document).ready(function(){
    if (window.FrotaUI) {
        window.FrotaUI.prepareMasks('#frota-issue-form');
    }
    $("#frota-issue-form").appForm({onSuccess:function(){location.reload();}});
    $("#frota-issue-form .select2").select2();
});
</script>
