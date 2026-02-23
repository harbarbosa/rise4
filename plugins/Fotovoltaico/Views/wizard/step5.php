<div class="page-content clearfix">
    <div class="card p20">
        <h3>Wizard FV - <?php echo app_lang('fv_summary'); ?></h3>
        <p><?php echo app_lang('fv_project'); ?>: <?php echo esc($project->title); ?></p>

        <div class="alert alert-info">
            <?php echo app_lang('fv_summary'); ?>: campos preenchidos nas etapas anteriores.
        </div>

        <div class="mt15">
            <?php echo modal_anchor(get_uri('fotovoltaico/wizard_modal/' . $project->id . '/4'), app_lang('previous'), array('class' => 'btn btn-default')); ?>
            <?php echo anchor(get_uri('fotovoltaico/projects_view/' . $project->id), app_lang('close'), array('class' => 'btn btn-primary')); ?>
        </div>
    </div>
</div>
