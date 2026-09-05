<?php echo form_open(get_uri('frota/manutencoes/salvar'), ['id' => 'frota-maintenance-form', 'class' => 'general-form', 'role' => 'form']); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id ?? ''; ?>" />
        <div id="selected-issue-inputs"></div>

        <div class="form-group"><div class="row"><label for="vehicle_id" class="col-md-3">Veículo</label><div class="col-md-9">
            <?php echo form_dropdown('vehicle_id', $vehicleOptions, [$model_info->vehicle_id ?? ''], "class='select2 validate-hidden' data-rule-required='true' data-msg-required='" . app_lang('field_required') . "' id='vehicle_id'"); ?>
        </div></div></div>

        <div id="open-issues-group" class="form-group" style="display:none;">
            <div class="row">
                <label class="col-md-3">Ocorrências abertas</label>
                <div class="col-md-9">
                    <div id="open-issues-list"></div>
                    <div class="text-off small mt-1">Selecione uma ou mais ocorrências. Ao concluir a manutenção, as ocorrências selecionadas serão encerradas automaticamente.</div>
                </div>
            </div>
        </div>

        <div class="form-group"><div class="row"><label for="type" class="col-md-3">Tipo</label><div class="col-md-9">
            <?php echo form_dropdown('type', ['preventive' => 'Preventiva', 'corrective' => 'Corretiva'], [$model_info->type ?? 'preventive'], "class='select2' id='type'"); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="service_date" class="col-md-3">Data</label><div class="col-md-9">
            <?php echo form_input(['id' => 'service_date', 'name' => 'service_date', 'value' => $model_info->service_date ?? date('Y-m-d'), 'class' => 'form-control', 'placeholder' => 'Data', 'autocomplete' => 'off', 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required')]); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label><div class="col-md-9">
            <?php echo form_textarea(['id' => 'description', 'name' => 'description', 'value' => $model_info->description ?? '', 'class' => 'form-control', 'placeholder' => app_lang('description'), 'rows' => 7, 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required')]); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="supplier" class="col-md-3">Fornecedor</label><div class="col-md-9">
            <?php echo form_input(['id' => 'supplier', 'name' => 'supplier', 'value' => $model_info->supplier ?? '', 'class' => 'form-control', 'placeholder' => 'Fornecedor']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="odometer" class="col-md-3">KM</label><div class="col-md-9">
            <?php echo form_input(['id' => 'odometer', 'name' => 'odometer', 'value' => $model_info->odometer ?? '', 'class' => 'form-control frota-km-mask', 'placeholder' => 'Informe a quilometragem', 'inputmode' => 'numeric']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="cost" class="col-md-3">Custo</label><div class="col-md-9">
            <?php echo form_input(['id' => 'cost', 'name' => 'cost', 'value' => $model_info->cost ?? '', 'class' => 'form-control frota-money-mask', 'data-decimals' => 2, 'placeholder' => 'Informe o custo']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="next_service_odometer" class="col-md-3">Próxima revisão (km)</label><div class="col-md-9">
            <?php echo form_input(['id' => 'next_service_odometer', 'name' => 'next_service_odometer', 'value' => $model_info->next_service_odometer ?? '', 'class' => 'form-control frota-km-mask', 'placeholder' => 'Informe a KM da próxima revisão', 'inputmode' => 'numeric']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="next_service_date" class="col-md-3">Próxima revisão</label><div class="col-md-9">
            <?php echo form_input(['id' => 'next_service_date', 'name' => 'next_service_date', 'value' => (($model_info->next_service_date ?? '') === '0000-00-00' ? '' : ($model_info->next_service_date ?? '')), 'class' => 'form-control', 'placeholder' => 'Informe a data da próxima revisão', 'autocomplete' => 'off']); ?>
        </div></div></div>

        <div class="form-group"><div class="row"><label for="status" class="col-md-3"><?php echo app_lang('status'); ?></label><div class="col-md-9">
            <?php echo form_dropdown('status', ['scheduled' => 'Agendada', 'in_progress' => 'Em andamento', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'], [$model_info->status ?? 'scheduled'], "class='select2' id='status'"); ?>
        </div></div></div>
    </div>
</div>
<div class="modal-footer">
    <?php if (!empty($model_info->id)) { ?>
        <button type="button" id="frota-delete-maintenance" class="btn btn-danger me-auto"><span data-feather="trash-2" class="icon-16"></span> Excluir manutenção</button>
    <?php } ?>
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>
<script type="text/javascript">
$(document).ready(function(){
    var maintenanceId = <?php echo (int)($model_info->id ?? 0); ?>;
    var $vehicle = $('#vehicle_id');
    var $list = $('#open-issues-list');
    var $group = $('#open-issues-group');
    var $description = $('#description');
    var $selectedInputs = $('#selected-issue-inputs');

    if (window.FrotaUI) {
        window.FrotaUI.prepareMasks('#frota-maintenance-form');
    }
    $("#frota-maintenance-form").appForm({onSuccess:function(){location.reload();}});
    $("#frota-maintenance-form .select2").select2();
    setDatePicker("#service_date, #next_service_date");

    function addSelectedIssue(issue, appendDescription) {
        var id = parseInt(issue.id, 10);
        if (!id || $selectedInputs.find('input[data-issue-id="' + id + '"]').length) return;
        $('<input>', {type:'hidden', name:'issue_ids[]', value:id, 'data-issue-id':id}).appendTo($selectedInputs);

        if (appendDescription) {
            var text = $.trim(issue.description || '');
            if (text) {
                var current = $.trim($description.val() || '');
                $description.val(current ? current + '\n\n' + text : text);
            }
        }
    }

    function renderIssues(rows) {
        $list.empty();
        $selectedInputs.empty();
        if (!rows || !rows.length) {
            $group.show();
            $list.html('<div class="text-off">Nenhuma ocorrência aberta para este veículo.</div>');
            return;
        }

        rows.forEach(function(issue){
            var selected = !!issue.selected;
            if (selected) addSelectedIssue(issue, false);

            var $card = $('<div class="border rounded p-2 mb-2"></div>');
            var $header = $('<div class="d-flex justify-content-between align-items-start gap-2"></div>');
            var $info = $('<div class="flex-grow-1"></div>');
            $('<div class="fw-bold"></div>').text(issue.title || ('Ocorrência #' + issue.id)).appendTo($info);
            $('<div class="text-off small mt-1"></div>').text(issue.description || '').appendTo($info);
            var $button = $('<button type="button" class="btn btn-sm"></button>');

            function setSelectedState(value) {
                if (value) {
                    $button.removeClass('btn-default').addClass('btn-success').text('Selecionada').prop('disabled', true);
                } else {
                    $button.removeClass('btn-success').addClass('btn-default').text('Selecionar').prop('disabled', false);
                }
            }
            setSelectedState(selected);

            $button.on('click', function(){
                addSelectedIssue(issue, true);
                setSelectedState(true);
            });

            $header.append($info).append($button);
            $card.append($header).appendTo($list);
        });
        $group.show();
    }

    function loadIssues() {
        var vehicleId = parseInt($vehicle.val(), 10) || 0;
        $selectedInputs.empty();
        if (!vehicleId) {
            $group.hide();
            $list.empty();
            return;
        }

        $group.show();
        $list.html('<div class="text-off">Carregando ocorrências...</div>');
        $.ajax({
            url: <?php echo json_encode(get_uri('frota/manutencoes/ocorrencias/veiculo')); ?> + '/' + vehicleId,
            type: 'GET',
            dataType: 'json',
            data: {maintenance_id: maintenanceId}
        }).done(function(response){
            renderIssues((response && response.data) ? response.data : []);
        }).fail(function(){
            $list.html('<div class="text-danger">Não foi possível carregar as ocorrências do veículo.</div>');
        });
    }

    $vehicle.on('change', loadIssues);
    loadIssues();

    $('#frota-delete-maintenance').on('click', function(){
        if (!maintenanceId || !window.confirm('Excluir esta manutenção?')) return;
        var $button = $(this).prop('disabled', true);
        $.ajax({
            url: <?php echo json_encode(get_uri('frota/manutencoes/' . (int)($model_info->id ?? 0) . '/excluir')); ?>,
            type: 'POST',
            dataType: 'json'
        }).done(function(response){
            if (response && response.success) location.reload();
            else { alert((response && response.message) || 'Não foi possível excluir a manutenção.'); $button.prop('disabled', false); }
        }).fail(function(xhr){
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'Não foi possível excluir a manutenção.');
            $button.prop('disabled', false);
        });
    });
});
</script>
