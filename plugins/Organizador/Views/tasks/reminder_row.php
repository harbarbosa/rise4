<?php
$reminder = $reminder ?? (object) array();
$can_manage = !empty($can_manage);
$is_done = !empty($reminder->is_done);
?>
<div id="organizador-reminder-<?php echo (int) $reminder->id; ?>" class="d-flex align-items-start justify-content-between gap-2 py10 border-bottom">
    <div>
        <div class="<?php echo $is_done ? 'text-off text-decoration-line-through' : 'strong'; ?>"><?php echo esc($reminder->title); ?></div>
        <div class="text-off"><?php echo format_to_datetime($reminder->remind_at); ?></div>
        <?php if (!empty($reminder->description)) { ?>
            <div class="mt5 text-wrap"><?php echo nl2br(esc($reminder->description)); ?></div>
        <?php } ?>
    </div>
    <?php if ($can_manage) { ?>
        <div class="d-flex gap-2">
            <?php echo ajax_anchor(get_uri('organizador/tasks/update_reminder_status'), "<i data-feather='check-circle' class='icon-16'></i>", array('title' => app_lang('mark_as_done'), 'data-post-id' => $reminder->id, 'data-reload-on-success' => '1', 'data-show-response' => '1')); ?>
            <?php echo ajax_anchor(get_uri('organizador/tasks/delete_reminder'), "<i data-feather='x' class='icon-16'></i>", array('class' => 'text-danger', 'title' => app_lang('delete'), 'data-post-id' => $reminder->id, 'data-fade-out-on-success' => '#organizador-reminder-' . $reminder->id)); ?>
        </div>
    <?php } ?>
</div>
