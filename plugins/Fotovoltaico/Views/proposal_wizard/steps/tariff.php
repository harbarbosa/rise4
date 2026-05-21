<div class="mb20">
    <h5 class="mb10"><?php echo app_lang('fotovoltaico_wizard_step_tariff'); ?></h5>
    <p class="text-off mb0">Selecione a distribuidora e a tarifa vigente para a proposta.</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label for="distributor_id" class="form-label"><?php echo app_lang('fotovoltaico_distributor_name'); ?></label>
        <?php echo form_dropdown('distributor_id', $distributor_options, get_array_value($wizard_data, 'distributor_id'), "class='form-select select2' id='distributor_id'"); ?>
    </div>
    <div class="col-md-6">
        <label for="tariff_id" class="form-label"><?php echo app_lang('fotovoltaico_tariffs'); ?></label>
        <?php echo form_dropdown('tariff_id', $tariff_options, get_array_value($wizard_data, 'tariff_id'), "class='form-select select2' id='tariff_id'"); ?>
    </div>
    <div class="col-md-3">
        <label class="form-label"><?php echo app_lang('fotovoltaico_tariff_te'); ?></label>
        <?php echo form_input(array('value' => $current_tariff ? to_decimal_format($current_tariff->te) : '', 'class' => 'form-control text-end', 'disabled' => true)); ?>
    </div>
    <div class="col-md-3">
        <label class="form-label"><?php echo app_lang('fotovoltaico_tariff_tusd'); ?></label>
        <?php echo form_input(array('value' => $current_tariff ? to_decimal_format($current_tariff->tusd) : '', 'class' => 'form-control text-end', 'disabled' => true)); ?>
    </div>
    <div class="col-md-3">
        <label class="form-label"><?php echo app_lang('fotovoltaico_tariff_flag'); ?></label>
        <?php echo form_input(array('value' => $current_tariff ? ($current_tariff->flag_name ?: '') : '', 'class' => 'form-control', 'disabled' => true)); ?>
    </div>
    <div class="col-md-3">
        <label class="form-label"><?php echo app_lang('fotovoltaico_tariff_flag_value'); ?></label>
        <?php echo form_input(array('value' => $current_tariff ? to_decimal_format($current_tariff->flag_value) : '', 'class' => 'form-control text-end', 'disabled' => true)); ?>
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
