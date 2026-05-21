<div class="mb20">
    <h5 class="mb10"><?php echo app_lang('fotovoltaico_wizard_step_consumption'); ?></h5>
    <p class="text-off mb0">Informe consumo medio e dados de leitura para orientar o dimensionamento.</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label for="consumption_avg" class="form-label"><?php echo app_lang('fotovoltaico_proposal_consumption_avg'); ?></label>
        <?php echo form_input(array('id' => 'consumption_avg', 'name' => 'consumption_avg', 'value' => get_array_value($wizard_data, 'consumption_avg'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="monthly_bill_value" class="form-label">Valor medio da conta</label>
        <?php echo form_input(array('id' => 'monthly_bill_value', 'name' => 'monthly_bill_value', 'value' => get_array_value($wizard_data, 'monthly_bill_value'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-md-4">
        <label for="consumption_profile" class="form-label">Perfil de consumo</label>
        <?php echo form_input(array('id' => 'consumption_profile', 'name' => 'consumption_profile', 'value' => get_array_value($wizard_data, 'consumption_profile'), 'class' => 'form-control', 'placeholder' => 'Residencial, comercial, rural...')); ?>
    </div>
    <div class="col-12">
        <label for="notes" class="form-label"><?php echo app_lang('notes'); ?></label>
        <?php echo form_textarea(array('id' => 'notes', 'name' => 'notes', 'value' => get_array_value($wizard_data, 'notes'), 'class' => 'form-control', 'rows' => 4)); ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt20">
    <div>
        <a href="<?php echo get_uri('fotovoltaico/proposal_wizard/step/' . $proposal_id . '/' . $previous_step); ?>" class="btn btn-default">
            <i data-feather="arrow-left" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_previous'); ?>
        </a>
    </div>
    <button type="submit" class="btn btn-primary">
        <i data-feather="arrow-right" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_save_continue'); ?>
    </button>
</div>
