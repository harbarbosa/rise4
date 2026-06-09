<?php
$summary = $summary ?? array();
$rows = $rows ?? array();
?>
<style>
    .pontorh-pdf-title { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
    .pontorh-pdf-subtitle { color: #6c757d; margin-bottom: 16px; }
    .pontorh-pdf-meta { font-size: 10px; margin-bottom: 12px; }
    .pontorh-pdf-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .pontorh-pdf-table th, .pontorh-pdf-table td { border: 1px solid #d9dee5; padding: 6px; vertical-align: top; }
    .pontorh-pdf-table th { background: #f8f9fa; }
    .pontorh-pdf-cards { width: 100%; margin-bottom: 12px; }
    .pontorh-pdf-card { display: inline-block; width: 23%; border: 1px solid #d9dee5; padding: 8px; margin-right: 1%; margin-bottom: 8px; vertical-align: top; }
    .pontorh-pdf-card .label { font-size: 9px; color: #6c757d; display: block; margin-bottom: 4px; }
    .pontorh-pdf-card .value { font-size: 12px; font-weight: 700; }
</style>

<div class="pontorh-pdf-title"><?php echo esc($report_title ?? app_lang('pontorh_mirror')); ?></div>
<div class="pontorh-pdf-subtitle"><?php echo esc($report_subtitle ?? ''); ?></div>
<div class="pontorh-pdf-meta">
    <?php echo app_lang('pontorh_employee'); ?>: <?php echo esc($selected_member ? ($selected_member->first_name . ' ' . $selected_member->last_name) : app_lang('all')); ?>
    <?php if (!empty($schedule->name)) { ?> | <?php echo app_lang('pontorh_shift'); ?>: <?php echo esc($schedule->name); ?><?php } ?>
</div>

<?php echo view('PontoRH\\Views\\mirror\\report_content', array(
    'summary' => $summary,
    'rows' => $rows,
    'selected_member' => $selected_member,
    'schedule' => $schedule,
)); ?>
