<?php echo form_open(get_uri('frota/abastecimentos/salvar'), ['id' => 'frota-fueling-form', 'class' => 'general-form', 'role' => 'form']); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="vehicle_id" class="col-md-3">Veículo</label>
                <div class="col-md-9">
                    <?php echo form_dropdown('vehicle_id', $vehicleOptions, [$model_info->vehicle_id ?? ''], "class='select2 validate-hidden' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "' id='vehicle_id'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="fueling_at" class="col-md-3">Data/hora</label>
                <div class="col-md-9">
                    <?php
                    $fueling_at = $model_info->fueling_at ?? date('Y-m-d H:i:s');
                    $fueling_at_value = $fueling_at ? date('Y-m-d\TH:i', strtotime($fueling_at)) : '';
                    echo form_input(['type' => 'datetime-local', 'id' => 'fueling_at', 'name' => 'fueling_at', 'value' => $fueling_at_value, 'class' => 'form-control']);
                    ?>
                </div>
            </div>
        </div>

        <?php
        $fields = [
            ['odometer', 'KM', $model_info->odometer ?? '', true],
            ['liters', 'Litros', $model_info->liters ?? '', true],
            ['unit_price', 'Preço por litro', $model_info->unit_price ?? '', false],
            ['total_amount', 'Valor total', $model_info->total_amount ?? '', true],
            ['station', 'Posto', $model_info->station ?? '', false],
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
        $("#frota-fueling-form").appForm({
            onSuccess: function () { location.reload(); }
        });
        $("#frota-fueling-form .select2").select2();
    });
</script>
