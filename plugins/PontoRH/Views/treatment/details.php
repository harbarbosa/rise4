<?php
$case = $case ?? (object) array();
$records = $records ?? array();
$history = $history ?? array();
$diagnostics = $diagnostics ?? array();
$classification = $classification ?? array();
$final = $final ?? array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('pontorh_treatment_dashboard_title'); ?></h1>
                <div class="text-muted"><?php echo esc(($case->team_member_name ?? '-') . ' - ' . ($case->work_date ?? '-')); ?></div>
            </div>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('pontorh/tratamento/modal_form/' . (int) ($case->id ?? 0)), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('pontorh_add_manual_mark'), array('class' => 'btn btn-primary', 'title' => app_lang('pontorh_add_manual_mark'), 'data-modal-lg' => '1')); ?>
                <a href="<?php echo get_uri('pontorh/tratamento'); ?>" class="btn btn-default"><?php echo app_lang('back'); ?></a>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_employee'); ?></div><div class="font-18 fw-bold"><?php echo esc($case->team_member_name ?? '-'); ?></div></div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_status'); ?></div><div class="font-18 fw-bold"><?php echo esc(pontorh_treatment_status_label($case->status ?? '')); ?></div></div></div></div>
                <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_type'); ?></div><div class="font-18 fw-bold"><?php echo esc(pontorh_treatment_pending_type_label($case->pending_type ?? '')); ?></div></div></div></div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_timeline'); ?></h4></div>
                        <div class="card-body">
                            <?php if ($records) { ?>
                                <div class="timeline">
                                    <?php foreach ($records as $record) { ?>
                                        <div class="mb-3 border-bottom pb-2">
                                            <div class="font-18 fw-bold"><?php echo esc(pontorh_extract_time($record->punch_time)); ?> - <?php echo esc(pontorh_punch_type_label($record->punch_type ?? '')); ?></div>
                                            <div class="text-muted small"><?php echo esc($record->source ?? '-'); ?> | <?php echo esc($record->status ?? '-'); ?></div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="text-muted"><?php echo app_lang('pontorh_records_empty'); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_summary'); ?></h4></div>
                        <div class="card-body">
                            <div class="mb-2"><strong><?php echo app_lang('pontorh_minutes_worked'); ?>:</strong> <?php echo esc(pontorh_minutes_to_hours_label((int) ($case->minutes_worked ?? 0))); ?></div>
                            <div class="mb-2"><strong><?php echo app_lang('pontorh_bank_hours'); ?>:</strong> <?php echo esc(pontorh_minutes_to_hours_label((int) ($case->bank_minutes ?? 0))); ?></div>
                            <div class="mb-2"><strong><?php echo app_lang('pontorh_type'); ?>:</strong> <?php echo esc(pontorh_treatment_pending_type_label($case->pending_type ?? '')); ?></div>
                            <div class="mb-2"><strong><?php echo app_lang('notes'); ?>:</strong> <?php echo nl2br(esc($case->diagnostics_json ?? '-')); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_action'); ?></h4></div>
                        <div class="card-body">
                            <?php echo form_open(get_uri('pontorh/tratamento/action'), array('id' => 'pontorh-treatment-action-form', 'class' => 'general-form')); ?>
                            <input type="hidden" name="case_id" value="<?php echo (int) ($case->id ?? 0); ?>" />
                            <div class="row g-2">
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
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
                                </div>
                            </div>
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_summary'); ?></h4></div>
                        <div class="card-body">
                            <pre class="mb-0"><?php echo esc(pontorh_safe_json($diagnostics)); ?></pre>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_action'); ?></h4></div>
                        <div class="card-body">
                            <table class="table table-bordered mb-0">
                                <thead><tr><th><?php echo app_lang('created_at'); ?></th><th><?php echo app_lang('pontorh_employee'); ?></th><th><?php echo app_lang('pontorh_action'); ?></th><th><?php echo app_lang('pontorh_reason'); ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($history as $item) { ?>
                                    <tr>
                                        <td><?php echo !empty($item->created_at) ? format_to_datetime($item->created_at) : '-'; ?></td>
                                        <td><?php echo esc($item->creator_name ?: '-'); ?></td>
                                        <td><?php echo esc($item->action ?: '-'); ?></td>
                                        <td><?php echo esc($item->justification ?: '-'); ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
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
        $("#pontorh-treatment-action-form").appForm();
    });
</script>
