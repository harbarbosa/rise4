<div class="mb20">
    <h5 class="mb10"><?php echo app_lang('fotovoltaico_wizard_step_kit'); ?></h5>
    <p class="text-off mb0">Escolha um kit existente ou registre uma referencia manual para a montagem.</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label for="kit_id" class="form-label"><?php echo app_lang('fotovoltaico_kit_name'); ?></label>
        <?php echo form_dropdown('kit_id', $kit_options, get_array_value($wizard_data, 'kit_id'), "class='form-select select2' id='kit_id'"); ?>
    </div>
    <div class="col-md-6">
        <label for="kit_label" class="form-label"><?php echo app_lang('fotovoltaico_kit_name'); ?></label>
        <?php echo form_input(array('id' => 'kit_label', 'name' => 'kit_label', 'value' => get_array_value($wizard_data, 'kit_label'), 'class' => 'form-control', 'placeholder' => app_lang('fotovoltaico_kit_name'))); ?>
    </div>
    <div class="col-12">
        <label for="kit_notes" class="form-label"><?php echo app_lang('fotovoltaico_notes'); ?></label>
        <?php echo form_textarea(array('id' => 'kit_notes', 'name' => 'kit_notes', 'value' => get_array_value($wizard_data, 'kit_notes'), 'class' => 'form-control', 'rows' => 4)); ?>
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
