<?php
$record = $record ?? (object) array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('pontorh_record_details'); ?></h1>
                <div class="text-muted"><?php echo esc($record->team_member_name ?: '-'); ?></div>
            </div>
            <div class="title-button-group">
                <?php if ($can_manage) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/registros/modal_form'), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit'), array('class' => 'btn btn-primary', 'title' => app_lang('edit'), 'data-post-id' => $record->id)); ?>
                <?php } ?>
                <?php echo modal_anchor(get_uri('pontorh/registros/view_modal'), "<i data-feather='eye' class='icon-16'></i> " . app_lang('view_details'), array('class' => 'btn btn-default', 'title' => app_lang('view_details'), 'data-post-id' => $record->id)); ?>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_employee'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc($record->team_member_name ?: '-'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_work_date'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc($record->date ?: '-'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('time'); ?></div>
                            <div class="font-18 fw-bold"><?php echo $record->punch_time ? esc(pontorh_extract_time($record->punch_time)) : '-'; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_type'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc(pontorh_punch_type_label($record->punch_type ?? '')); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_status'); ?></div>
                            <div class="font-18 fw-bold"><span class="badge bg-secondary"><?php echo esc(app_lang('pontorh_status_' . $record->status)); ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th><?php echo app_lang('pontorh_location'); ?></th>
                                        <td><?php echo esc($record->location_name ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo app_lang('pontorh_source'); ?></th>
                                        <td><?php echo esc($record->source ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Latitude</th>
                                        <td><?php echo esc($record->latitude ?? '0'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Longitude</th>
                                        <td><?php echo esc($record->longitude ?? '0'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th><?php echo app_lang('ip_address'); ?></th>
                                        <td><?php echo esc($record->ip_address ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo app_lang('created_at'); ?></th>
                                        <td><?php echo !empty($record->created_at) ? format_to_datetime($record->created_at) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo app_lang('created_by'); ?></th>
                                        <td><?php echo esc($record->creator_name ?: ($record->created_by ?? '-')); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo app_lang('notes'); ?></th>
                                        <td><?php echo nl2br(esc($record->notes ?: '-')); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
