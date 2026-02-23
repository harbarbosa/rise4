<div class="page-content clearfix">
    <div class="card p20">
        <h3>Wizard FV - <?php echo app_lang('fv_select_kit'); ?></h3>
        <p><?php echo app_lang('fv_project'); ?>: <?php echo esc($project->title); ?></p>

        <div class="form-group">
            <label><?php echo app_lang('fv_kits'); ?></label>
            <input type="text" class="form-control" placeholder="Kit disponÃ­vel" />
        </div>

        <div class="mt15">
            <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/3'), app_lang('previous'), array('class' => 'btn btn-default')); ?>
            <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/5'), app_lang('next'), array('class' => 'btn btn-primary')); ?>
        </div>
    </div>
</div>
