<div class="mb20">
    <h5 class="mb10"><?php echo app_lang('fotovoltaico_wizard_step_client'); ?></h5>
    <p class="text-off mb0">Selecione o registro principal no CRM e a unidade consumidora da proposta.</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label for="title" class="form-label"><?php echo app_lang('fotovoltaico_proposal_title'); ?></label>
        <?php echo form_input(array('id' => 'title', 'name' => 'title', 'value' => get_array_value($wizard_data, 'title'), 'class' => 'form-control', 'placeholder' => app_lang('fotovoltaico_proposal_title'))); ?>
    </div>
    <div class="col-md-6">
        <label for="consumer_unit" class="form-label"><?php echo app_lang('fotovoltaico_proposal_consumer_unit'); ?></label>
        <?php echo form_input(array('id' => 'consumer_unit', 'name' => 'consumer_unit', 'value' => get_array_value($wizard_data, 'consumer_unit'), 'class' => 'form-control', 'placeholder' => app_lang('fotovoltaico_proposal_consumer_unit'))); ?>
    </div>
    <div class="col-md-4">
        <label for="client_id" class="form-label"><?php echo app_lang('fotovoltaico_proposal_client'); ?></label>
        <?php echo form_dropdown('client_id', $client_options, get_array_value($wizard_data, 'client_id'), "class='form-select select2' id='client_id'"); ?>
    </div>
    <div class="col-md-4">
        <label for="lead_id" class="form-label"><?php echo app_lang('fotovoltaico_proposal_lead'); ?></label>
        <?php echo form_dropdown('lead_id', $lead_options, get_array_value($wizard_data, 'lead_id'), "class='form-select select2' id='lead_id'"); ?>
    </div>
    <div class="col-md-4">
        <label for="contact_id" class="form-label"><?php echo app_lang('fotovoltaico_proposal_contact'); ?></label>
        <?php echo form_dropdown('contact_id', $contact_options, get_array_value($wizard_data, 'contact_id'), "class='form-select select2' id='contact_id'"); ?>
    </div>
    <div class="col-12">
        <label for="crm_note" class="form-label">Observacao do CRM</label>
        <?php echo form_textarea(array('id' => 'crm_note', 'name' => 'crm_note', 'value' => get_array_value($wizard_data, 'crm_note'), 'class' => 'form-control', 'rows' => 3)); ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt20">
    <div>
        <?php if ($previous_step) { ?>
            <a href="<?php echo get_uri('fotovoltaico/proposal_wizard/step/' . $proposal_id . '/' . $previous_step); ?>" class="btn btn-default">
                <i data-feather="arrow-left" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_previous'); ?>
            </a>
        <?php } ?>
    </div>
    <button type="submit" class="btn btn-primary">
        <i data-feather="arrow-right" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_save_continue'); ?>
    </button>
</div>

<script type="text/javascript">
    $(function () {
        var fields = ['client_id', 'lead_id', 'contact_id'];

        var syncCrmSelectors = function (activeField) {
            var activeValue = $("#" + activeField).val();
            if (activeValue) {
                fields.forEach(function (field) {
                    if (field !== activeField) {
                        $("#" + field).val('').trigger('change.select2').prop('disabled', true);
                    }
                });
                return;
            }

            fields.forEach(function (field) {
                $("#" + field).prop('disabled', false);
            });
        };

        fields.forEach(function (field) {
            $("#" + field).on('change', function () {
                syncCrmSelectors(field);
            });
        });

        fields.forEach(function (field) {
            if ($("#" + field).val()) {
                syncCrmSelectors(field);
            }
        });
    });
</script>
