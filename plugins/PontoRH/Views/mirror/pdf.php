<?php
$summary = $summary ?? array();
$rows = $rows ?? array();
$format_mirror_date = function ($date) {
    return ($date && is_date_exists($date)) ? format_to_date($date, false) : ($date ?: '-');
};
?>
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #212529; }
    .pontorh-pdf-title { font-size: 20px; font-weight: 700; margin: 0 0 4px 0; }
    .pontorh-pdf-subtitle { color: #6c757d; margin: 0 0 12px 0; }
    .pontorh-pdf-meta, .pontorh-pdf-summary, .pontorh-pdf-table { width: 100%; border-collapse: collapse; }
    .pontorh-pdf-meta { margin-bottom: 12px; }
    .pontorh-pdf-meta td { border: 1px solid #d9dee5; padding: 6px 8px; vertical-align: top; }
    .pontorh-pdf-meta .label { display: block; color: #6c757d; font-size: 9px; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.02em; }
    .pontorh-pdf-summary { margin-bottom: 14px; }
    .pontorh-pdf-summary td { border: 1px solid #d9dee5; padding: 6px 8px; width: 20%; vertical-align: top; }
    .pontorh-pdf-summary .label { display: block; color: #6c757d; font-size: 9px; margin-bottom: 2px; text-transform: uppercase; }
    .pontorh-pdf-summary .value { display: block; font-size: 12px; font-weight: 700; }
    .pontorh-pdf-section-title { font-size: 11px; font-weight: 700; margin: 0 0 8px 0; }
    .pontorh-pdf-table { font-size: 9px; }
    .pontorh-pdf-table th, .pontorh-pdf-table td { border: 1px solid #d9dee5; padding: 5px 6px; vertical-align: top; }
    .pontorh-pdf-table th { background: #e9eef5; font-weight: 700; text-align: center; }
    .pontorh-pdf-table td { text-align: center; }
    .pontorh-pdf-table td.left { text-align: left; }
</style>

<div class="pontorh-pdf-title"><?php echo esc($report_title ?? app_lang('pontorh_mirror')); ?></div>
<div class="pontorh-pdf-subtitle"><?php echo esc($report_subtitle ?? ''); ?></div>

<table class="pontorh-pdf-meta">
    <tr>
        <td style="width: 40%;">
            <span class="label"><?php echo app_lang('pontorh_employee'); ?></span>
            <?php echo esc($selected_member ? trim(($selected_member->first_name ?? '') . ' ' . ($selected_member->last_name ?? '')) : app_lang('all')); ?>
        </td>
        <td style="width: 30%;">
            <span class="label"><?php echo app_lang('pontorh_shift'); ?></span>
            <?php echo esc($schedule->name ?? '-'); ?>
        </td>
        <td style="width: 30%;">
            <span class="label"><?php echo app_lang('pontorh_schedule_type'); ?></span>
            <?php echo esc(isset($schedule->schedule_type) ? pontorh_schedule_type_label($schedule->schedule_type) : '-'); ?>
        </td>
    </tr>
</table>

<table class="pontorh-pdf-summary">
    <tr>
        <td><span class="label"><?php echo app_lang('pontorh_minutes_worked'); ?></span><span class="value"><?php echo esc(pontorh_minutes_to_hours_label(get_array_value($summary, 'worked_minutes_total', 0))); ?></span></td>
        <td><span class="label"><?php echo app_lang('pontorh_extra_hours'); ?></span><span class="value"><?php echo esc(pontorh_minutes_to_hours_label(get_array_value($summary, 'extra_minutes_total', 0))); ?></span></td>
        <td><span class="label"><?php echo app_lang('pontorh_bank_hours'); ?></span><span class="value"><?php echo esc(pontorh_minutes_to_hours_label(get_array_value($summary, 'bank_minutes_end', 0))); ?></span></td>
        <td><span class="label"><?php echo app_lang('pontorh_absences'); ?></span><span class="value"><?php echo (int) get_array_value($summary, 'absences_total', 0); ?></span></td>
        <td><span class="label"><?php echo app_lang('pontorh_lateness'); ?></span><span class="value"><?php echo esc(pontorh_minutes_to_hours_label(get_array_value($summary, 'lateness_total', 0))); ?></span></td>
    </tr>
</table>

<div class="pontorh-pdf-section-title"><?php echo app_lang('details'); ?></div>
<table class="pontorh-pdf-table">
    <thead>
        <tr>
            <th><?php echo app_lang('pontorh_work_date'); ?></th>
            <th><?php echo 'Dia'; ?></th>
            <th><?php echo app_lang('pontorh_check_in'); ?></th>
            <th><?php echo app_lang('pontorh_check_out'); ?></th>
            <th><?php echo app_lang('pontorh_break_minutes'); ?></th>
            <th><?php echo app_lang('pontorh_minutes_worked'); ?></th>
            <th><?php echo app_lang('pontorh_extra_hours'); ?></th>
            <th><?php echo app_lang('pontorh_bank_hours'); ?></th>
            <th><?php echo app_lang('pontorh_absences'); ?></th>
            <th><?php echo app_lang('pontorh_lateness'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row) { ?>
            <tr>
                <td class="left"><?php echo esc($format_mirror_date($row['date'] ?? '')); ?></td>
                <td class="left"><?php echo esc($row['weekday_label'] ?? ucfirst((string) ($row['weekday'] ?? ''))); ?></td>
                <td><?php echo esc($row['entries'] ?: '-'); ?></td>
                <td><?php echo esc($row['exits'] ?: '-'); ?></td>
                <td><?php echo esc(pontorh_minutes_to_hours_label($row['intervals_minutes'] ?? 0)); ?></td>
                <td><?php echo esc(pontorh_minutes_to_hours_label($row['worked_minutes'] ?? 0)); ?></td>
                <td><?php echo esc(pontorh_minutes_to_hours_label($row['extra_minutes'] ?? 0)); ?></td>
                <td><?php echo esc(pontorh_minutes_to_hours_label($row['bank_minutes'] ?? 0)); ?></td>
                <td><?php echo (int) ($row['absences'] ?? 0); ?></td>
                <td><?php echo esc(pontorh_minutes_to_hours_label($row['lateness_minutes'] ?? 0)); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
