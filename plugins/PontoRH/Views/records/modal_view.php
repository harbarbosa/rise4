<?php
$record = $record ?? (object) array();
?>

<div class="modal-body clearfix">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="text-muted"><?php echo app_lang('pontorh_employee'); ?></div>
            <div class="font-18 fw-bold"><?php echo esc($record->team_member_name ?: '-'); ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted"><?php echo app_lang('pontorh_work_date'); ?></div>
            <div class="font-18 fw-bold"><?php echo esc($record->date ?: '-'); ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted"><?php echo app_lang('time'); ?></div>
            <div class="font-18 fw-bold"><?php echo $record->punch_time ? esc(pontorh_extract_time($record->punch_time)) : '-'; ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted"><?php echo app_lang('pontorh_type'); ?></div>
            <div class="font-18 fw-bold"><?php echo esc(pontorh_punch_type_label($record->punch_type ?? '')); ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted"><?php echo app_lang('pontorh_location'); ?></div>
            <div class="font-18 fw-bold"><?php echo esc($record->location_name ?: '-'); ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted"><?php echo app_lang('pontorh_status'); ?></div>
            <div class="font-18 fw-bold"><span class="badge bg-secondary"><?php echo esc(app_lang('pontorh_status_' . $record->status)); ?></span></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted"><?php echo app_lang('pontorh_source'); ?></div>
            <div class="font-18 fw-bold"><?php echo esc($record->source ?: '-'); ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted">Latitude</div>
            <div class="font-18 fw-bold"><?php echo esc($record->latitude ?? '0'); ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted">Longitude</div>
            <div class="font-18 fw-bold"><?php echo esc($record->longitude ?? '0'); ?></div>
        </div>
        <div class="col-md-12">
            <div class="text-muted"><?php echo app_lang('created_by'); ?></div>
            <div class="font-18 fw-bold"><?php echo esc($record->creator_name ?: ($record->created_by ?? '-')); ?></div>
        </div>
        <div class="col-md-12">
            <div class="text-muted"><?php echo app_lang('notes'); ?></div>
            <div><?php echo nl2br(esc($record->notes ?: '-')); ?></div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
</div>
