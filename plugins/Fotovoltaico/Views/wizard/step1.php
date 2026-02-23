<div class="page-content clearfix">
    <div class="card p20">
        <h3>Wizard FV - <?php echo app_lang('fv_consumption_history'); ?></h3>
        <p><?php echo app_lang('fv_project'); ?>: <?php echo esc($project->title); ?></p>

        <div class="form-group">
            <label><?php echo app_lang('client'); ?></label>
            <input type="text" class="form-control" value="<?php echo esc($client->company_name ?? '-'); ?>" readonly />
        </div>

        <div class="form-group">
            <label><?php echo app_lang('fv_consumption_history'); ?></label>
            <textarea class="form-control" rows="4" placeholder="Ex.: 12 meses, kWh/mÃªs"></textarea>
        </div>

        <div class="mt15">
            <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/2'), app_lang('next'), array('class' => 'btn btn-primary')); ?>
        </div>
    </div>
</div>
