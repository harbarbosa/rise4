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

        <div class="form-group">
            <div class="row">
                <label for="odometer" class="col-md-3">KM</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'odometer',
                        'name' => 'odometer',
                        'value' => $model_info->odometer ?? '',
                        'class' => 'form-control frota-km-mask',
                        'placeholder' => 'Informe a quilometragem atual',
                        'inputmode' => 'numeric',
                        'data-rule-required' => true,
                        'data-msg-required' => app_lang('field_required')
                    ]); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="liters" class="col-md-3">Litros</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'liters',
                        'name' => 'liters',
                        'value' => $model_info->liters ?? '',
                        'class' => 'form-control frota-decimal-mask',
                        'data-decimals' => 3,
                        'placeholder' => 'Informe a quantidade abastecida',
                        'inputmode' => 'decimal',
                        'data-rule-required' => true,
                        'data-msg-required' => app_lang('field_required')
                    ]); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="unit_price" class="col-md-3">Preço por litro</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'unit_price',
                        'name' => 'unit_price',
                        'value' => $model_info->unit_price ?? '',
                        'class' => 'form-control frota-money-mask',
                        'data-decimals' => 3,
                        'placeholder' => 'Informe o preço por litro',
                        'inputmode' => 'decimal',
                        'data-rule-required' => true,
                        'data-msg-required' => app_lang('field_required')
                    ]); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="total_amount" class="col-md-3">Valor total</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'total_amount',
                        'name' => 'total_amount',
                        'value' => $model_info->total_amount ?? '',
                        'class' => 'form-control frota-money-mask',
                        'data-decimals' => 2,
                        'placeholder' => 'Calculado automaticamente',
                        'readonly' => true,
                        'data-rule-required' => true,
                        'data-msg-required' => app_lang('field_required')
                    ]); ?>
                    <div class="text-off small mt-1">Calculado automaticamente: litros × preço por litro.</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="station" class="col-md-3">Posto</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'station',
                        'name' => 'station',
                        'value' => $model_info->station ?? '',
                        'class' => 'form-control',
                        'placeholder' => 'Posto'
                    ]); ?>
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
        if (window.FrotaUI) {
            window.FrotaUI.prepareMasks('#frota-fueling-form');
            window.FrotaUI.initFuelingCalculation('#frota-fueling-form');
        }
        $("#frota-fueling-form").appForm({
            onSuccess: function () { location.reload(); }
        });
        $("#frota-fueling-form .select2").select2();
    });
</script>
