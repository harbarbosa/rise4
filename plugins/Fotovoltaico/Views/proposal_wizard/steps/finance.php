<div class="mb20">
    <h5 class="mb10"><?php echo app_lang('fotovoltaico_wizard_step_finance'); ?></h5>
    <p class="text-off mb0">Ajuste entrada, parcelas e condicoes comerciais.</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label for="entry_value" class="form-label">Valor de entrada</label>
        <?php echo form_input(array('id' => 'entry_value', 'name' => 'entry_value', 'value' => get_array_value($wizard_data, 'entry_value'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="entry_percent" class="form-label">Entrada %</label>
        <?php echo form_input(array('id' => 'entry_percent', 'name' => 'entry_percent', 'value' => get_array_value($wizard_data, 'entry_percent'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="installments" class="form-label">Parcelas</label>
        <?php echo form_input(array('id' => 'installments', 'name' => 'installments', 'value' => get_array_value($wizard_data, 'installments'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="financing_rate" class="form-label">Taxa de financiamento</label>
        <?php echo form_input(array('id' => 'financing_rate', 'name' => 'financing_rate', 'value' => get_array_value($wizard_data, 'financing_rate'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="monthly_value" class="form-label">Parcela mensal</label>
        <?php echo form_input(array('id' => 'monthly_value', 'name' => 'monthly_value', 'value' => get_array_value($wizard_data, 'monthly_value'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="investment_initial" class="form-label">Investimento inicial</label>
        <?php echo form_input(array('id' => 'investment_initial', 'name' => 'investment_initial', 'value' => get_array_value($wizard_data, 'investment_initial') ?: get_array_value($summary, 'total'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="economy_annual" class="form-label">Economia anual</label>
        <?php echo form_input(array('id' => 'economy_annual', 'name' => 'economy_annual', 'value' => get_array_value($wizard_data, 'economy_annual'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="tariff_escalation" class="form-label">Reajuste tarifario %</label>
        <?php echo form_input(array('id' => 'tariff_escalation', 'name' => 'tariff_escalation', 'value' => get_array_value($wizard_data, 'tariff_escalation'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="discount_rate" class="form-label">Taxa de desconto %</label>
        <?php echo form_input(array('id' => 'discount_rate', 'name' => 'discount_rate', 'value' => get_array_value($wizard_data, 'discount_rate'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="maintenance_cost_annual" class="form-label">Manutencao anual</label>
        <?php echo form_input(array('id' => 'maintenance_cost_annual', 'name' => 'maintenance_cost_annual', 'value' => get_array_value($wizard_data, 'maintenance_cost_annual'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="maintenance_escalation" class="form-label">Reajuste da manutencao %</label>
        <?php echo form_input(array('id' => 'maintenance_escalation', 'name' => 'maintenance_escalation', 'value' => get_array_value($wizard_data, 'maintenance_escalation'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="horizon" class="form-label">Horizonte do projeto</label>
        <?php echo form_input(array('id' => 'horizon', 'name' => 'horizon', 'value' => get_array_value($wizard_data, 'horizon') ?: 25, 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-12">
        <label for="replacement_schedule_json" class="form-label">Substituicoes de equipamentos JSON</label>
        <?php echo form_textarea(array('id' => 'replacement_schedule_json', 'name' => 'replacement_schedule_json', 'value' => get_array_value($wizard_data, 'replacement_schedule_json'), 'class' => 'form-control', 'rows' => 4, 'placeholder' => '[{"year":10,"cost":1500,"label":"Inversor"}]')); ?>
    </div>
    <div class="col-12">
        <label for="finance_notes" class="form-label"><?php echo app_lang('notes'); ?></label>
        <?php echo form_textarea(array('id' => 'finance_notes', 'name' => 'finance_notes', 'value' => get_array_value($wizard_data, 'finance_notes'), 'class' => 'form-control', 'rows' => 4)); ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt20">
    <div>
        <a href="<?php echo get_uri('fotovoltaico/proposal_wizard/step/' . $proposal_id . '/' . $previous_step); ?>" class="btn btn-default">
            <i data-feather="arrow-left" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_previous'); ?>
        </a>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" id="finance-preview">
            <i data-feather="activity" class="icon-16"></i> Preview
        </button>
        <button type="submit" class="btn btn-primary">
            <i data-feather="arrow-right" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_save_continue'); ?>
        </button>
    </div>
</div>

<div class="alert alert-light border mt20 d-none" id="finance-preview-result"></div>

<script type="text/javascript">
    $(function () {
        $("#finance-preview").on("click", function () {
            var $result = $("#finance-preview-result");
            $result.removeClass("d-none").html("Calculando...");

            $.post("<?php echo get_uri('fotovoltaico/finance/preview'); ?>", {
                investment_initial: $("#investment_initial").val(),
                economy_annual: $("#economy_annual").val(),
                tariff_escalation: $("#tariff_escalation").val(),
                discount_rate: $("#discount_rate").val(),
                maintenance_cost_annual: $("#maintenance_cost_annual").val(),
                maintenance_escalation: $("#maintenance_escalation").val(),
                horizon: $("#horizon").val(),
                replacement_schedule_json: $("#replacement_schedule_json").val()
            }).done(function (response) {
                if (typeof response === "string") {
                    response = JSON.parse(response);
                }

                if (response && response.success) {
                    var outputs = response.outputs || {};
                    $result.html(
                        "<strong>Payback simples:</strong> " + (outputs.payback_simple_years ?? '-') + "<br>" +
                        "<strong>Payback descontado:</strong> " + (outputs.payback_discounted_years ?? '-') + "<br>" +
                        "<strong>TIR:</strong> " + ((outputs.tir !== null && outputs.tir !== undefined) ? (outputs.tir * 100).toFixed(2) + "%" : "-") + "<br>" +
                        "<strong>VPL:</strong> " + (outputs.vpl ?? '-')
                    );
                } else {
                    $result.html("Nao foi possivel calcular os indicadores.");
                }
            }).fail(function () {
                $result.html("Nao foi possivel calcular os indicadores.");
            });
        });
    });
</script>
