<?php
$summary = $summary ?? array();
$rows = $rows ?? array();
$selected_member = $selected_member ?? null;
$schedule = $schedule ?? null;
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted"><?php echo app_lang('pontorh_minutes_worked'); ?></div>
                <div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label(get_array_value($summary, 'worked_minutes_total', 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted"><?php echo app_lang('pontorh_extra_hours'); ?></div>
                <div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label(get_array_value($summary, 'extra_minutes_total', 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted"><?php echo app_lang('pontorh_bank_hours'); ?></div>
                <div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label(get_array_value($summary, 'bank_minutes_end', 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted"><?php echo app_lang('pontorh_absences'); ?></div>
                <div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'absences_total', 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted"><?php echo app_lang('pontorh_lateness'); ?></div>
                <div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label(get_array_value($summary, 'lateness_total', 0)); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted"><?php echo app_lang('pontorh_employee'); ?></div>
                <div class="font-18 fw-bold"><?php echo esc($selected_member ? ($selected_member->first_name . ' ' . $selected_member->last_name) : app_lang('all')); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted"><?php echo app_lang('pontorh_shift'); ?></div>
                <div class="font-18 fw-bold"><?php echo esc($schedule->name ?? '-'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted"><?php echo app_lang('pontorh_schedule_type'); ?></div>
                <div class="font-18 fw-bold"><?php echo esc(isset($schedule->schedule_type) ? pontorh_schedule_type_label($schedule->schedule_type) : '-'); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo app_lang('pontorh_work_date'); ?></th>
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
                    <td>
                        <?php echo esc($row['date']); ?><br>
                        <small class="text-muted"><?php echo esc(ucfirst($row['weekday'])); ?></small>
                    </td>
                    <td><?php echo esc($row['entries'] ?: '-'); ?></td>
                    <td><?php echo esc($row['exits'] ?: '-'); ?></td>
                    <td><?php echo esc(pontorh_minutes_to_hours_label($row['intervals_minutes'])); ?></td>
                    <td><?php echo esc(pontorh_minutes_to_hours_label($row['worked_minutes'])); ?></td>
                    <td><?php echo esc(pontorh_minutes_to_hours_label($row['extra_minutes'])); ?></td>
                    <td><?php echo esc(pontorh_minutes_to_hours_label($row['bank_minutes'])); ?></td>
                    <td><?php echo (int) $row['absences']; ?></td>
                    <td><?php echo esc(pontorh_minutes_to_hours_label($row['lateness_minutes'])); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
