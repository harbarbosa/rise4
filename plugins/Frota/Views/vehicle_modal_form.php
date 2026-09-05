<?php echo form_open(get_uri('frota/veiculos/salvar'), ['id' => 'frota-vehicle-form', 'class' => 'general-form', 'role' => 'form']); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="plate" class="col-md-3">Placa</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'plate',
                        'name' => 'plate',
                        'value' => $model_info->plate ?? '',
                        'class' => 'form-control frota-plate-mask',
                        'placeholder' => 'ABC-1D23',
                        'maxlength' => 8,
                        'data-rule-required' => true,
                        'data-rule-pattern' => '^[A-Z]{3}-[0-9][A-Z][0-9]{2}$',
                        'data-msg-required' => app_lang('field_required'),
                        'data-msg-pattern' => 'Use o formato ABC-1D23.'
                    ]); ?>
                    <div class="text-off small mt-1">Formato Mercosul: ABC-1D23</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="prefix" class="col-md-3">Prefixo</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'prefix',
                        'name' => 'prefix',
                        'value' => $model_info->prefix ?? '',
                        'class' => 'form-control',
                        'placeholder' => 'Prefixo'
                    ]); ?>
                </div>
            </div>
        </div>

        <input type="hidden" name="make" id="make" value="<?php echo esc($model_info->make ?? ''); ?>" />
        <div class="form-group">
            <div class="row">
                <label for="make_code" class="col-md-3">Marca</label>
                <div class="col-md-9">
                    <select id="make_code" class="form-control"></select>
                    <div class="text-off small mt-1">Marcas consultadas na Tabela FIPE via BrasilAPI. Também é possível digitar manualmente.</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="model" class="col-md-3">Modelo</label>
                <div class="col-md-9">
                    <select id="model" name="model" class="form-control validate-hidden" data-rule-required="true" data-msg-required="<?php echo app_lang('field_required'); ?>">
                        <?php if (!empty($model_info->model)) { ?>
                            <option value="<?php echo esc($model_info->model); ?>" selected><?php echo esc($model_info->model); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="year" class="col-md-3">Ano</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'year',
                        'name' => 'year',
                        'value' => $model_info->year ?? '',
                        'class' => 'form-control frota-year-mask',
                        'placeholder' => '2026',
                        'maxlength' => 4,
                        'inputmode' => 'numeric'
                    ]); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="current_odometer" class="col-md-3">KM atual</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'current_odometer',
                        'name' => 'current_odometer',
                        'value' => $model_info->current_odometer ?? '',
                        'class' => 'form-control frota-km-mask',
                        'placeholder' => '111.111.111',
                        'inputmode' => 'numeric'
                    ]); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="next_service_odometer" class="col-md-3">Próxima revisão (km)</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'next_service_odometer',
                        'name' => 'next_service_odometer',
                        'value' => $model_info->next_service_odometer ?? '',
                        'class' => 'form-control frota-km-mask',
                        'placeholder' => '111.111.111',
                        'inputmode' => 'numeric'
                    ]); ?>
                </div>
            </div>
        </div>

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
        if (window.FrotaUI) {
            window.FrotaUI.prepareMasks('#frota-vehicle-form');
            window.FrotaUI.initVehicleFipe({
                brandSelector: '#make_code',
                makeHiddenSelector: '#make',
                modelSelector: '#model',
                currentMake: <?php echo json_encode($model_info->make ?? ''); ?>,
                currentModel: <?php echo json_encode($model_info->model ?? ''); ?>,
                type: 'carros',
                brandsUrl: <?php echo json_encode(get_uri('frota/fipe/marcas')); ?>,
                modelsUrl: <?php echo json_encode(get_uri('frota/fipe/modelos')); ?>
            });
        }

        $("#frota-vehicle-form").appForm({
            onSuccess: function () { location.reload(); }
        });
        $("#frota-vehicle-form .select2").select2();
        setDatePicker("#next_service_date");
    });
</script>
