<?php
$case = $case ?? (object) array();
$records = $records ?? array();
$history = $history ?? array();
$diagnostics = $diagnostics ?? array();
$classification = $classification ?? array();
$final = $final ?? array();
$can_write = !empty($can_write);

$status_label = pontorh_treatment_status_label($case->status ?? '');
$pending_type_label = pontorh_treatment_pending_type_label($case->pending_type ?? '');
$project_name = trim((string) ($case->project_name ?? ''));
$record_count = (int) ($case->record_count ?? count($records));
$minutes_worked = (int) ($case->minutes_worked ?? 0);
$bank_minutes = (int) ($case->bank_minutes ?? 0);

$history_action_labels = array(
    'reprocess' => app_lang('pontorh_reprocess'),
    'request_justification' => app_lang('pontorh_treatment_status_awaiting_justification'),
    'ignore_extra' => app_lang('pontorh_treatment_pending_ignored'),
    'correct_classification' => app_lang('pontorh_treatment_pending_corrected'),
    'approve_day' => app_lang('pontorh_treatment_status_treated_manual'),
    'close_day' => app_lang('close'),
    'forward_rh' => app_lang('pontorh_forward_rh'),
    'manual_mark_added' => app_lang('pontorh_add_manual_mark'),
    'edit' => app_lang('pontorh_record_updated'),
    'delete' => app_lang('pontorh_record_deleted'),
);

$diagnostics_lines = array();
if (is_array($diagnostics)) {
    foreach ($diagnostics as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $diagnostics_lines[] = $line;
        }
    }
}
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('pontorh_treatment_dashboard_title'); ?></h1>
                <div class="text-muted">
                    <?php echo esc(($case->team_member_name ?? '-') . ' - ' . format_to_date($case->work_date ?? '', false)); ?>
                </div>
            </div>
            <div class="title-button-group">
                <?php if ($can_write) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/tratamento/modal_form/' . (int) ($case->id ?? 0)), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('pontorh_add_manual_mark'), array('class' => 'btn btn-primary', 'title' => app_lang('pontorh_add_manual_mark'), 'data-modal-lg' => '1')); ?>
                <?php } ?>
                <a href="<?php echo get_uri('pontorh/tratamento'); ?>" class="btn btn-default"><?php echo app_lang('back'); ?></a>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small"><?php echo app_lang('pontorh_employee'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc($case->team_member_name ?? '-'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small"><?php echo app_lang('pontorh_work_date'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc(format_to_date($case->work_date ?? '', false)); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small"><?php echo app_lang('pontorh_status'); ?></div>
                            <div class="font-18 fw-bold"><span class="badge bg-secondary"><?php echo esc($status_label); ?></span></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small"><?php echo app_lang('pontorh_type'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc($pending_type_label); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small"><?php echo app_lang('pontorh_project'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc($project_name !== '' ? $project_name : '-'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small"><?php echo app_lang('pontorh_quantity_of_records'); ?></div>
                            <div class="font-18 fw-bold"><?php echo (int) $record_count; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small"><?php echo app_lang('pontorh_minutes_worked'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc(pontorh_minutes_to_hours_label($minutes_worked)); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small"><?php echo app_lang('pontorh_bank_hours'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc(pontorh_minutes_to_hours_label($bank_minutes)); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_timeline'); ?></h4>
                            <span class="text-muted small"><?php echo esc(count($records)); ?> <?php echo app_lang('pontorh_quantity_of_records'); ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($records)) { ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th><?php echo app_lang('time'); ?></th>
                                                <th><?php echo app_lang('pontorh_type'); ?></th>
                                                <th><?php echo app_lang('pontorh_source'); ?></th>
                                                <th><?php echo app_lang('pontorh_status'); ?></th>
                                                <th><?php echo app_lang('pontorh_location'); ?></th>
                                                <?php if ($can_write) { ?>
                                                    <th class="text-center"><?php echo app_lang('action'); ?></th>
                                                <?php } ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($records as $record) { ?>
                                                <tr>
                                                    <td><?php echo esc($record->punch_time ? pontorh_extract_time($record->punch_time) : '-'); ?></td>
                                                    <td><?php echo esc(pontorh_punch_type_label($record->punch_type ?? '')); ?></td>
                                                    <td><?php echo esc($record->source ? app_lang('pontorh_audit_source_' . strtolower((string) $record->source)) : '-'); ?></td>
                                                    <td><?php echo esc($record->status ? app_lang('pontorh_status_' . strtolower((string) $record->status)) : '-'); ?></td>
                                                    <td><?php echo esc($record->location_name ?? '-'); ?></td>
                                                    <?php if ($can_write) { ?>
                                                        <td class="text-center text-nowrap">
                                                            <?php echo modal_anchor(get_uri('pontorh/tratamento/modal_form/' . (int) ($case->id ?? 0)), "<i data-feather='plus-circle' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('pontorh_add_manual_mark'), 'data-modal-lg' => '1')); ?>
                                                            <?php echo modal_anchor(get_uri('pontorh/tratamento/record_modal/' . (int) ($case->id ?? 0) . '/' . (int) ($record->id ?? 0) . '/edit'), "<i data-feather='edit-2' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('pontorh_record_action_edit'), 'data-modal-lg' => '1')); ?>
                                                            <?php echo modal_anchor(get_uri('pontorh/tratamento/record_modal/' . (int) ($case->id ?? 0) . '/' . (int) ($record->id ?? 0) . '/delete'), "<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('pontorh_record_action_delete'), 'data-modal-lg' => '1')); ?>
                                                        </td>
                                                    <?php } ?>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } else { ?>
                                <div class="text-muted"><?php echo app_lang('pontorh_records_empty'); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_summary'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="text-muted small"><?php echo app_lang('pontorh_diagnostics'); ?></div>
                                <div class="bg-light border rounded p-3">
                                    <?php if (!empty($diagnostics_lines)) { ?>
                                        <?php echo nl2br(esc(implode("\n", $diagnostics_lines))); ?>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_classification'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($classification)) { ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th><?php echo app_lang('time'); ?></th>
                                                <th><?php echo app_lang('pontorh_type'); ?></th>
                                                <th><?php echo app_lang('pontorh_status'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($classification as $item) { ?>
                                                <tr>
                                                    <td><?php echo esc($item['id'] ?? '-'); ?></td>
                                                    <td><?php echo esc($item['time'] ?? '-'); ?></td>
                                                    <td><?php echo esc($item['effective_type'] ? pontorh_punch_type_label($item['effective_type']) : '-'); ?></td>
                                                    <td><?php echo esc($item['status'] ? app_lang('pontorh_status_' . strtolower((string) $item['status'])) : '-'); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } else { ?>
                                <div class="text-muted">-</div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_treatment_history'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th><?php echo app_lang('created_at'); ?></th>
                                            <th><?php echo app_lang('creator'); ?></th>
                                            <th><?php echo app_lang('pontorh_action'); ?></th>
                                            <th><?php echo app_lang('pontorh_reason'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($history)) { ?>
                                            <?php foreach ($history as $item) { ?>
                                                <tr>
                                                    <td><?php echo !empty($item->created_at) ? format_to_datetime($item->created_at) : '-'; ?></td>
                                                    <td><?php echo esc($item->creator_name ?: '-'); ?></td>
                                                    <td><?php echo esc($history_action_labels[$item->action ?? ''] ?? ($item->action ?? '-')); ?></td>
                                                    <td><?php echo esc($item->justification ?: '-'); ?></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="4" class="text-muted"><?php echo app_lang('no_record_found'); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_action'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php echo form_open(get_uri('pontorh/tratamento/action'), array('id' => 'pontorh-treatment-action-form', 'class' => 'general-form')); ?>
                            <input type="hidden" name="case_id" value="<?php echo (int) ($case->id ?? 0); ?>" />
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label"><?php echo app_lang('pontorh_action'); ?></label>
                                    <select name="action_type" class="form-control select2 w100p" required>
                                        <option value="">-</option>
                                        <option value="approve_day"><?php echo app_lang('pontorh_treatment_status_treated_manual'); ?></option>
                                        <option value="reprocess"><?php echo app_lang('pontorh_reprocess'); ?></option>
                                        <option value="request_justification"><?php echo app_lang('pontorh_treatment_status_awaiting_justification'); ?></option>
                                        <option value="ignore_extra"><?php echo app_lang('pontorh_treatment_pending_ignored'); ?></option>
                                        <option value="correct_classification"><?php echo app_lang('pontorh_treatment_pending_corrected'); ?></option>
                                        <option value="forward_rh"><?php echo app_lang('pontorh_forward_rh'); ?></option>
                                        <option value="close_day"><?php echo app_lang('close'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label"><?php echo app_lang('pontorh_reason'); ?></label>
                                    <textarea name="justification" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
                                </div>
                            </div>
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_final_result'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($final)) { ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th><?php echo app_lang('time'); ?></th>
                                                <th><?php echo app_lang('pontorh_type'); ?></th>
                                                <th><?php echo app_lang('pontorh_source'); ?></th>
                                                <th><?php echo app_lang('pontorh_status'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($final as $item) { ?>
                                                <tr>
                                                    <td><?php echo esc($item->id ?? ($item['id'] ?? '-')); ?></td>
                                                    <td><?php echo esc(isset($item->punch_time) ? pontorh_extract_time($item->punch_time) : (isset($item['punch_time']) ? pontorh_extract_time($item['punch_time']) : '-')); ?></td>
                                                    <td><?php echo esc(isset($item->punch_type) ? pontorh_punch_type_label($item->punch_type) : (isset($item['punch_type']) ? pontorh_punch_type_label($item['punch_type']) : '-')); ?></td>
                                                    <td><?php echo esc(isset($item->source) ? app_lang('pontorh_audit_source_' . strtolower((string) $item->source)) : (isset($item['source']) ? app_lang('pontorh_audit_source_' . strtolower((string) $item['source'])) : '-')); ?></td>
                                                    <td><?php echo esc(isset($item->status) ? app_lang('pontorh_status_' . strtolower((string) $item->status)) : (isset($item['status']) ? app_lang('pontorh_status_' . strtolower((string) $item['status'])) : '-')); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } else { ?>
                                <div class="text-muted">-</div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#pontorh-treatment-action-form .select2").select2();
        $("#pontorh-treatment-action-form").appForm({
            onSuccess: function () {
                location.reload();
            }
        });
    });
</script>
