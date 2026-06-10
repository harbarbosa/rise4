<?php
$filters = $filters ?? array('team_member_id' => '', 'month' => get_my_local_time('n'), 'year' => get_my_local_time('Y'));
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('pontorh_mirror'); ?></h1>
                <div class="text-muted"><?php echo esc($report_subtitle ?? ''); ?></div>
            </div>
            <div class="title-button-group">
                <a href="<?php echo esc($export_pdf_url ?? '#'); ?>" target="_blank" class="btn btn-default">
                    <i data-feather="printer" class="icon-16"></i> <?php echo app_lang('export_pdf'); ?>
                </a>
                <a href="<?php echo esc($export_excel_url ?? '#'); ?>" class="btn btn-default">
                    <i data-feather="download" class="icon-16"></i> <?php echo app_lang('export_excel'); ?>
                </a>
            </div>
        </div>

        <div class="card-body border-bottom">
            <?php echo form_open(get_uri('pontorh/espelho'), array('method' => 'get', 'class' => 'general-form')); ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label"><?php echo app_lang('pontorh_employee'); ?></label>
                    <?php echo form_dropdown('team_member_id', $team_members_dropdown, $filters['team_member_id'] ?? '', 'class="form-control select2"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('month'); ?></label>
                    <?php echo form_dropdown('month', $month_dropdown, $filters['month'] ?? get_my_local_time('n'), 'class="form-control select2"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('year'); ?></label>
                    <?php echo form_dropdown('year', $year_dropdown, $filters['year'] ?? get_my_local_time('Y'), 'class="form-control select2"'); ?>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm"><?php echo app_lang('filter'); ?></button>
                    <a href="<?php echo get_uri('pontorh/espelho'); ?>" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></a>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>

        <div class="card-body">
            <?php echo view('PontoRH\\Views\\mirror\\report_content', array(
                'summary' => $summary,
                'rows' => $rows,
                'selected_member' => $selected_member,
                'schedule' => $schedule,
            )); ?>
        </div>
    </div>
</div>
