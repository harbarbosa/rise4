<?php
$record = $record ?? (object) array();

$status_label = app_lang('pontorh_status_' . ($record->status ?? ''));
if ($status_label === 'pontorh_status_' . ($record->status ?? '')) {
    $status_label = $record->status ?? '-';
}

$type_label = pontorh_punch_type_label($record->punch_type ?? '');
$time_label = $record->punch_time ? pontorh_extract_time($record->punch_time) : '-';
$photo_src = function_exists('pontorh_record_photo_src') ? pontorh_record_photo_src($record->photo ?? '') : '';
?>

<div class="modal-body clearfix">
    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
        <div>
            <div class="text-muted text-uppercase small"><?php echo app_lang('pontorh_record_details'); ?></div>
            <h5 class="mb-1"><?php echo esc($record->team_member_name ?: '-'); ?></h5>
            <div class="text-muted"><?php echo esc(($record->date ?: '-') . ' • ' . $time_label); ?></div>
        </div>
        <div class="text-end">
            <div class="mb-2"><span class="badge bg-secondary"><?php echo esc($status_label); ?></span></div>
            <div class="text-muted small"><?php echo esc($type_label); ?></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('pontorh_employee'); ?></div>
                            <div class="fw-bold"><?php echo esc($record->team_member_name ?: '-'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small"><?php echo app_lang('pontorh_work_date'); ?></div>
                            <div class="fw-bold"><?php echo esc($record->date ?: '-'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small"><?php echo app_lang('time'); ?></div>
                            <div class="fw-bold"><?php echo esc($time_label); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('pontorh_type'); ?></div>
                            <div class="fw-bold"><?php echo esc($type_label); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('pontorh_location'); ?></div>
                            <div class="fw-bold"><?php echo esc($record->location_name ?: '-'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('pontorh_source'); ?></div>
                            <div class="fw-bold"><?php echo esc($record->source ?: '-'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Latitude</div>
                            <div class="fw-bold"><?php echo esc($record->latitude ?? '0'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Longitude</div>
                            <div class="fw-bold"><?php echo esc($record->longitude ?? '0'); ?></div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-muted small"><?php echo app_lang('created_by'); ?></div>
                            <div class="fw-bold"><?php echo esc($record->creator_name ?: ($record->created_by ?? '-')); ?></div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-muted small"><?php echo app_lang('notes'); ?></div>
                            <div class="bg-light border rounded p-3"><?php echo nl2br(esc($record->notes ?: '-')); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2"><?php echo app_lang('pontorh_selfie'); ?></div>
                    <?php if ($photo_src) { ?>
                        <img src="<?php echo esc($photo_src); ?>" alt="<?php echo app_lang('pontorh_selfie'); ?>" class="img-fluid rounded border w-100" style="max-height: 320px; object-fit: cover;">
                    <?php } else { ?>
                        <div class="text-muted">-</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
</div>
