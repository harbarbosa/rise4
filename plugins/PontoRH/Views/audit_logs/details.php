<?php
$log = $log ?? null;
?>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-6"><strong><?php echo app_lang('date'); ?>:</strong> <?php echo esc($log->created_at ?? '-'); ?></div>
        <div class="col-md-6"><strong><?php echo app_lang('pontorh_employee'); ?>:</strong> <?php echo esc($log->team_member_name ?? '-'); ?></div>
        <div class="col-md-6"><strong><?php echo app_lang('creator'); ?>:</strong> <?php echo esc($log->creator_name ?? '-'); ?></div>
        <div class="col-md-6"><strong><?php echo app_lang('pontorh_entity_type'); ?>:</strong> <?php echo esc($log->entity_type ?? '-'); ?></div>
        <div class="col-md-6"><strong><?php echo app_lang('pontorh_action'); ?>:</strong> <?php echo esc($log->action ?? '-'); ?></div>
        <div class="col-md-6"><strong><?php echo app_lang('status'); ?>:</strong> <?php echo esc($log->status ?? '-'); ?></div>
        <div class="col-12"><strong><?php echo app_lang('description'); ?>:</strong><br><?php echo nl2br(esc($log->description ?? '-')); ?></div>
        <div class="col-12"><strong>Payload:</strong><pre class="bg-light p-2"><?php echo esc($log->payload_json ?? ''); ?></pre></div>
    </div>
</div>
