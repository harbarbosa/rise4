<?php echo form_open(get_uri('frota/manutencoes/salvar'), ['id' => 'frota-maintenance-form', 'class' => 'general-form', 'role' => 'form']); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />

        <div class="form-group"><div class="row"><label for="vehicle_id" class="col-md-3">Veículo</label><div class="col-md-9">
            <?php echo form_dropdown('vehicle_id', $vehicleOptions, [$model_info->vehicle_id ?? ''], "class='select2 validate-hidden' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "' id='vehicle_id'"); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="type" class="col-md-3">Tipo</label><div class="col-md-9">
            <?php echo form_dropdown('type', ['preventive' => 'Preventiva', 'corrective' => 'Corretiva'], [$model_info->type ?? 'preventive'], "class='select2' id='type'"); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="service_date" class="col-md-3">Data</label><div class="col-md-9">
            <?php echo form_input(['id' => 'service_date', 'name' => 'service_date', 'value' => $model_info->service_date ?? date('Y-m-d'), 'class' => 'form-control', 'placeholder' => 'Data', 'autocomplete' => 'off', 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required')]); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label><div class="col-md-9">
            <?php echo form_textarea(['id' => 'description', 'name' => 'description', 'value' => $model_info->description ?? '', 'class' => 'form-control', 'placeholder' => app_lang('description'), 'rows' => 4, 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required')]); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="supplier" class="col-md-3">Fornecedor</label><div class="col-md-9">
            <?php echo form_input(['id' => 'supplier', 'name' => 'supplier', 'value' => $model_info->supplier ?? '', 'class' => 'form-control', 'placeholder' => 'Fornecedor']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="odometer" class="col-md-3">KM</label><div class="col-md-9">
            <?php echo form_input(['id' => 'odometer', 'name' => 'odometer', 'value' => $model_info->odometer ?? '', 'class' => 'form-control frota-km-mask', 'placeholder' => '111.111.111', 'inputmode' => 'numeric']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="cost" class="col-md-3">Custo</label><div class="col-md-9">
            <?php echo form_input(['id' => 'cost', 'name' => 'cost', 'value' => $model_info->cost ?? '', 'class' => 'form-control', 'placeholder' => 'Custo']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="next_service_odometer" class="col-md-3">Próxima revisão (km)</label><div class="col-md-9">
            <?php echo form_input(['id' => 'next_service_odometer', 'name' => 'next_service_odometer', 'value' => $model_info->next_service_odometer ?? '', 'class' => 'form-control frota-km-mask', 'placeholder' => '111.111.111', 'inputmode' => 'numeric']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="next_service_date" class="col-md-3">Próxima revisão</label><div class="col-md-9">
            <?php echo form_input(['id' => 'next_service_date', 'name' => 'next_service_date', 'value' => $model_info->next_service_date ?? '', 'class' => 'form-control', 'placeholder' => 'Próxima revisão', 'autocomplete' => 'off']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="status" class="col-md-3"><?php echo app_lang('status'); ?></label><div class="col-md-9">
            <?php echo form_dropdown('status', ['scheduled' => 'Agendada', 'in_progress' => 'Em andamento', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'], [$model_info->status ?? 'scheduled'], "class='select2' id='status'"); ?>
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
        window.FrotaUI.prepareMasks('#frota-maintenance-form');
    }
    $("#frota-maintenance-form").appForm({onSuccess:function(){location.reload();}});
    $("#frota-maintenance-form .select2").select2();
    setDatePicker("#service_date, #next_service_date");
});
</script>
