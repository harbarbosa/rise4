<div class="page-content clearfix">
    <div class="card p20">
        <h3>Wizard FV - <?php echo app_lang('fv_select_tariff'); ?></h3>
        <p><?php echo app_lang('fv_project'); ?>: <?php echo esc($project->title); ?></p>

        <div class="form-group">
            <label><?php echo app_lang('fv_distribution'); ?></label>
            <select class="form-control" id="fv-utility">
                <option value=""><?php echo app_lang('choose'); ?></option>
                <?php if (!empty($utilities)) { ?>
                    <?php foreach ($utilities as $utility) { ?>
                        <option value="<?php echo $utility->id; ?>"><?php echo esc($utility->name); ?></option>
                    <?php } ?>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label><?php echo app_lang('fv_select_tariff'); ?></label>
            <input type="text" class="form-control" placeholder="Tarifa" />
        </div>

        <div class="mt15">
            <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/1'), app_lang('previous'), array('class' => 'btn btn-default')); ?>
            <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/3'), app_lang('next'), array('class' => 'btn btn-primary')); ?>
        </div>
    </div>
</div>
