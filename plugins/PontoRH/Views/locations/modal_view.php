<?php
$location = $location ?? null;
$assignments = $assignments ?? array();
?>

<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="text-muted"><?php echo app_lang('name'); ?></div>
                <div class="mb15"><?php echo esc($location->name ?? '-'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-muted"><?php echo app_lang('status'); ?></div>
                <div class="mb15">
                    <?php echo !empty($location->active) ? '<span class="badge bg-success">' . app_lang('active') . '</span>' : '<span class="badge bg-secondary">' . app_lang('inactive') . '</span>'; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="text-muted"><?php echo app_lang('address'); ?></div>
                <div class="mb15"><?php echo esc($location->address ?: '-'); ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="text-muted">Latitude</div>
                <div class="mb15"><?php echo esc((string) ($location->latitude ?? '-')); ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted">Longitude</div>
                <div class="mb15"><?php echo esc((string) ($location->longitude ?? '-')); ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted"><?php echo app_lang('pontorh_allowed_radius_meters'); ?></div>
                <div class="mb15"><?php echo esc((string) ($location->radius_meters ?? '-')); ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4 class="mb10"><?php echo app_lang('pontorh_location_assignments'); ?></h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?php echo app_lang('pontorh_employee'); ?></th>
                                <th><?php echo app_lang('pontorh_period_start'); ?></th>
                                <th><?php echo app_lang('pontorh_period_end'); ?></th>
                                <th><?php echo app_lang('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($assignments)) { ?>
                                <?php foreach ($assignments as $assignment) { ?>
                                    <tr>
                                        <td><?php echo esc($assignment->team_member_name ?: '-'); ?></td>
                                        <td><?php echo esc(format_to_date($assignment->week_start, false)); ?></td>
                                        <td><?php echo esc(format_to_date($assignment->week_end, false)); ?></td>
                                        <td><?php echo !empty($assignment->active) ? app_lang('active') : app_lang('inactive'); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted"><?php echo app_lang('no_records_found'); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
