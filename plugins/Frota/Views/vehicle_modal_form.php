<?php
$nextServiceDate = trim((string)($model_info->next_service_date ?? ''));
if ($nextServiceDate === '0000-00-00' || preg_match('/^(0000|1900)-/', $nextServiceDate)) {
    $nextServiceDate = '';
}
?>
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
                        'data-rule-minlength' => 8,
                        'data-msg-required' => app_lang('field_required'),
                        'data-msg-minlength' => 'Use o formato ABC-1234 ou ABC-1D23.'
                    ]); ?>
                    <div class="text-off small mt-1">Formatos aceitos: ABC-1234 ou ABC-1D23</div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="prefix" class="col-md-3">Cód. Interno</label>
                <div class="col-md-9">
                    <?php echo form_input([
                        'id' => 'prefix',
                        'name' => 'prefix',
                        'value' => $model_info->prefix ?? '',
                        'class' => 'form-control',
                        'placeholder' => 'Código interno do veículo'
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
                        'placeholder' => 'Informe a quilometragem atual',
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
                        'placeholder' => 'Informe a KM da próxima revisão',
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
                    <?php echo form_input([
                        'id' => 'next_service_date',
                        'name' => 'next_service_date',
                        'value' => $nextServiceDate,
                        'class' => 'form-control',
                        'placeholder' => 'Informe a data da próxima revisão',
                        'autocomplete' => 'off'
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
    <?php if (!empty($model_info->id)) { ?>
        <button type="button" id="frota-delete-vehicle" class="btn btn-danger me-auto">
            <span data-feather="trash-2" class="icon-16"></span> Excluir veículo
        </button>
    <?php } ?>
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

        $("#frota-delete-vehicle").on("click", function () {
            var vehicleId = <?php echo (int)($model_info->id ?? 0); ?>;
            if (!vehicleId) return;

            var warning = "Excluir este veículo? Todos os abastecimentos, manutenções, ocorrências e fotos vinculadas a ele serão apagados definitivamente.";
            if (!window.confirm(warning)) return;

            var $button = $(this);
            $button.prop("disabled", true);

            $.ajax({
                url: <?php echo json_encode(get_uri('frota/veiculos/' . (int)($model_info->id ?? 0) . '/excluir')); ?>,
                type: "POST",
                dataType: "json"
            }).done(function (response) {
                if (response && response.success) {
                    location.reload();
                    return;
                }
                alert((response && response.message) || "Não foi possível excluir o veículo.");
                $button.prop("disabled", false);
            }).fail(function (xhr) {
                var message = "Não foi possível excluir o veículo.";
                if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
                alert(message);
                $button.prop("disabled", false);
            });
        });
    });
</script>
