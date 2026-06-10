<?php
$location = $location ?? null;
$assignments = $assignments ?? array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('pontorh_location_details'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage)) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/locais/assignment_modal'), "<i data-feather='link-2' class='icon-16'></i> " . app_lang('pontorh_assign_location'), array('class' => 'btn btn-default', 'title' => app_lang('pontorh_assign_location'), 'data-post-id' => $location->id, 'data-modal-lg' => '1')); ?>
                    <?php echo modal_anchor(get_uri('pontorh/locais/modal_form'), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit'), array('class' => 'btn btn-primary', 'title' => app_lang('edit'), 'data-post-id' => $location->id, 'data-modal-lg' => '1')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
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
                <div class="col-md-12">
                    <div class="text-muted"><?php echo app_lang('address'); ?></div>
                    <div class="mb15"><?php echo esc($location->address ?: '-'); ?></div>
                </div>
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
        </div>
    </div>

    <div class="card mt15">
        <div class="card-header">
            <h4 class="card-title mb0"><?php echo app_lang('pontorh_location_assignments'); ?></h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo app_lang('pontorh_employee'); ?></th>
                            <th><?php echo app_lang('pontorh_period_start'); ?></th>
                            <th><?php echo app_lang('pontorh_period_end'); ?></th>
                            <th><?php echo app_lang('status'); ?></th>
                            <?php if (!empty($can_manage)) { ?>
                                <th class="text-center"><?php echo app_lang('action'); ?></th>
                            <?php } ?>
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
                                    <?php if (!empty($can_manage)) { ?>
                                        <td class="text-center">
                                            <?php echo js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array(
                                                'class' => 'action-icon text-danger',
                                                'title' => app_lang('delete'),
                                                'data-id' => $assignment->id,
                                                'data-action-url' => get_uri('pontorh/locais/assignment_delete'),
                                                'data-action' => 'delete-confirmation',
                                            )); ?>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="<?php echo !empty($can_manage) ? 5 : 4; ?>" class="text-center text-muted"><?php echo app_lang('no_records_found'); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
