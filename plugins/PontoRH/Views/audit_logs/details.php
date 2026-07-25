<?php
$log = $log ?? null;

$description = trim((string) ($log->description ?? ''));
$payload = trim((string) ($log->payload_json ?? ''));
?>

<div class="modal-body clearfix">
    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
        <div>
            <div class="text-muted text-uppercase small"><?php echo app_lang('pontorh_audit_logs'); ?></div>
            <h5 class="mb-1"><?php echo esc($log->entity_type_label ?? ($log->entity_type ?? '-')); ?></h5>
            <div class="text-muted"><?php echo esc($log->created_at_formatted ?? '-'); ?></div>
        </div>
        <div class="text-end">
            <div class="mb-2"><span class="badge bg-secondary"><?php echo esc($log->status_label ?? ($log->status ?? '-')); ?></span></div>
            <div class="text-muted small"><?php echo esc($log->action_label ?? ($log->action ?? '-')); ?></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('date'); ?></div>
                            <div class="fw-bold"><?php echo esc($log->created_at_formatted ?? '-'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('creator'); ?></div>
                            <div class="fw-bold"><?php echo esc($log->creator_name ?? '-'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('pontorh_employee'); ?></div>
                            <div class="fw-bold"><?php echo esc($log->team_member_name ?? '-'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('pontorh_source'); ?></div>
                            <div class="fw-bold"><?php echo esc($log->source_label ?? ($log->source ?? '-')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('pontorh_entity_type'); ?></div>
                            <div class="fw-bold"><?php echo esc($log->entity_type_label ?? ($log->entity_type ?? '-')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small"><?php echo app_lang('pontorh_action'); ?></div>
                            <div class="fw-bold"><?php echo esc($log->action_label ?? ($log->action ?? '-')); ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small"><?php echo app_lang('description'); ?></div>
                            <div class="bg-light border rounded p-3">
                                <?php echo nl2br(esc($description !== '' ? $description : '-')); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">ID</div>
                            <div class="fw-bold"><?php echo esc($log->id ?? '-'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">IP</div>
                            <div class="fw-bold"><?php echo esc($log->ip_address ?? '-'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2"><?php echo app_lang('pontorh_payload'); ?></div>
                    <?php if ($payload !== '') { ?>
                        <pre class="bg-light border rounded p-3 mb-0" style="white-space: pre-wrap; word-break: break-word;"><?php echo esc($payload); ?></pre>
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
