<div class="mb20">
    <h5 class="mb10"><?php echo app_lang('fotovoltaico_wizard_step_law'); ?></h5>
    <p class="text-off mb0">Defina os parametros da Lei 14.300 para a analise economica.</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label for="law_14300_mode" class="form-label">Modo</label>
        <?php echo form_input(array('id' => 'law_14300_mode', 'name' => 'law_14300_mode', 'value' => get_array_value($wizard_data, 'law_14300_mode'), 'class' => 'form-control')); ?>
    </div>
    <div class="col-md-4">
        <label for="law_14300_category" class="form-label">Categoria</label>
        <?php echo form_input(array('id' => 'law_14300_category', 'name' => 'law_14300_category', 'value' => get_array_value($wizard_data, 'law_14300_category'), 'class' => 'form-control')); ?>
    </div>
    <div class="col-md-4">
        <label for="law_14300_percentage" class="form-label">Percentual</label>
        <?php echo form_input(array('id' => 'law_14300_percentage', 'name' => 'law_14300_percentage', 'value' => get_array_value($wizard_data, 'law_14300_percentage'), 'class' => 'form-control text-end')); ?>
    </div>
    <div class="col-12">
        <label for="law_14300_notes" class="form-label"><?php echo app_lang('notes'); ?></label>
        <?php echo form_textarea(array('id' => 'law_14300_notes', 'name' => 'law_14300_notes', 'value' => get_array_value($wizard_data, 'law_14300_notes'), 'class' => 'form-control', 'rows' => 4)); ?>
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
