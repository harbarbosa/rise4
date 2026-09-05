<?php echo form_open(get_uri('frota/veiculos/salvar'), ['id' => 'frota-vehicle-form', 'class' => 'general-form', 'role' => 'form']); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />

        <?php
        $fields = [
            ['plate', 'Placa', $model_info->plate ?? '', true],
            ['prefix', 'Prefixo', $model_info->prefix ?? '', false],
            ['make', 'Marca', $model_info->make ?? '', false],
            ['model', 'Modelo', $model_info->model ?? '', true],
            ['year', 'Ano', $model_info->year ?? '', false],
            ['current_odometer', 'KM atual', $model_info->current_odometer ?? '', false],
            ['next_service_odometer', 'Próxima revisão (km)', $model_info->next_service_odometer ?? '', false],
        ];
        foreach ($fields as $field) {
            [$name, $label, $value, $required] = $field;
        ?>
            <div class="form-group">
                <div class="row">
                    <label for="<?php echo $name; ?>" class="col-md-3"><?php echo $label; ?></label>
                    <div class="col-md-9">
                        <?php echo form_input([
                            'id' => $name,
                            'name' => $name,
                            'value' => $value,
                            'class' => 'form-control',
                            'placeholder' => $label,
                            'data-rule-required' => $required ? true : null,
                            'data-msg-required' => $required ? app_lang('field_required') : null,
                        ]); ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="fuel_type" class="col-md-3">Combustível</label>
                <div class="col-md-9">
                    <?php echo form_dropdown('fuel_type', ['Flex' => 'Flex', 'Gasolina' => 'Gasolina', 'Etanol' => 'Etanol', 'Diesel' => 'Diesel', 'Elétrico' => 'Elétrico'], [$model_info->fuel_type ?? 'Flex'], "class='select2' id='fuel_type'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="status" class="col-md-3"><?php echo app_lang('status'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('status', ['active' => 'Ativo', 'maintenance' => 'Em manutenção', 'inactive' => 'Inativo'], [$model_info->status ?? 'active'], "class='select2' id='status'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="next_service_date" class="col-md-3">Próxima revisão</label>
                <div class="col-md-9">
                    <?php echo form_input(['id' => 'next_service_date', 'name' => 'next_service_date', 'value' => $model_info->next_service_date ?? '', 'class' => 'form-control', 'placeholder' => 'Próxima revisão', 'autocomplete' => 'off']); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="notes" class="col-md-3">Observações</label>
                <div class="col-md-9">
                    <?php echo form_textarea(['id' => 'notes', 'name' => 'notes', 'value' => $model_info->notes ?? '', 'class' => 'form-control', 'placeholder' => 'Observações', 'rows' => 4]); ?>
                </div>
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
    $(document).ready(function () {
        $("#frota-vehicle-form").appForm({
            onSuccess: function () { location.reload(); }
        });
        $("#frota-vehicle-form .select2").select2();
        setDatePicker("#next_service_date");
    });
</script>
