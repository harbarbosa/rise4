<?php
$task = $task ?? (object) array();
$can_edit = !empty($can_edit);
$can_delete = !empty($can_delete);
$comments = $comments ?? array();
$reminders = $reminders ?? array();
$reminder_before_label = trim((string) ($reminder_before_label ?? ''));

$status_key = trim((string) ($task->status ?? 'pending'));
$status_title = trim((string) ($task->status_title ?? '')) ?: app_lang('organizador_status_' . $status_key);
$status_color = trim((string) ($task->status_color ?? '')) ?: '#6c757d';
$priority_key = trim((string) ($task->priority ?? 'medium'));
$priority_title = app_lang('organizador_priority_' . $priority_key);
$category_title = trim((string) ($task->category_title ?? '')) ?: '-';
$assigned_to_name = trim((string) ($task->assigned_to_name ?? '')) ?: '-';
$due_date = !empty($task->due_date) ? format_to_datetime($task->due_date) : '-';
$created_at = !empty($task->created_at) ? format_to_datetime($task->created_at) : '-';
$updated_at = !empty($task->updated_at) ? format_to_datetime($task->updated_at) : '-';
$is_overdue = !empty($task->due_date) && $status_key !== 'done' && strtotime($task->due_date) < time();
?>

<div class="modal-body clearfix general-form task-view-modal-body organizador-task-view">
    <div class="row">
        <div class="col-lg-8">
            <div class="organizador-hero mb20">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <div class="text-off mb5"><?php echo app_lang('task_info'); ?> #<?php echo (int) $task->id; ?></div>
                        <h3 class="mt0 mb10"><?php echo esc($task->title ?? ''); ?></h3>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge rounded-pill" style="background:<?php echo esc($status_color); ?>;"><?php echo esc($status_title); ?></span>
                            <span class="badge rounded-pill bg-light text-dark border"><?php echo esc($priority_title); ?></span>
                            <span class="badge rounded-pill bg-light text-dark border"><?php echo esc($category_title); ?></span>
                            <?php if (!empty($task->is_favorite)) { ?>
                                <span class="badge rounded-pill bg-warning text-dark"><?php echo app_lang('organizador_mark_favorite'); ?></span>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($can_edit) { ?>
                            <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit_task'), array('class' => 'btn btn-default', 'data-post-id' => $task->id, 'title' => app_lang('edit_task'))); ?>
                        <?php } ?>
                        <?php if ($can_delete) { ?>
                            <?php echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array('class' => 'btn btn-default text-danger', 'title' => app_lang('delete'), 'data-id' => $task->id, 'data-action-url' => get_uri('organizador/tasks/delete'), 'data-action' => 'delete-confirmation')); ?>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="card mb20">
                <div class="card-body">
                    <div class="text-off mb10"><?php echo app_lang('description'); ?></div>
                    <div class="text-wrap organizador-task-description">
                        <?php echo !empty($task->description) ? nl2br(link_it(esc($task->description))) : '-'; ?>
                    </div>
                </div>
            </div>

            <div class="card mb20">
                <div class="card-header bg-white">
                    <strong><?php echo app_lang('comments'); ?></strong>
                </div>
                <div class="card-body pt15">
                    <?php echo view('Organizador\\Views\\tasks\\comment_form', array('task' => $task, 'login_user' => $login_user)); ?>
                    <?php echo view('Organizador\\Views\\tasks\\comment_list', array('comments' => $comments, 'can_edit' => $can_edit, 'login_user' => $login_user)); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb20">
                <div class="card-body">
                    <div class="d-flex align-items-center mb15">
                        <span class="avatar avatar-md me-2">
                            <img src="<?php echo get_avatar($task->assigned_to_avatar ?? ''); ?>" alt="..." />
                        </span>
                        <div>
                            <div class="strong"><?php echo esc($assigned_to_name); ?></div>
                            <div class="text-off"><?php echo app_lang('organizador_task_assigned_to'); ?></div>
                        </div>
                    </div>

                    <div class="mb10"><strong><?php echo app_lang('organizador_categories'); ?>:</strong> <?php echo esc($category_title); ?></div>
                    <div class="mb10"><strong><?php echo app_lang('organizador_due_date'); ?>:</strong> <span class="<?php echo $is_overdue ? 'text-danger strong' : ''; ?>"><?php echo esc($due_date); ?></span></div>
                    <div class="mb10"><strong><?php echo app_lang('organizador_task_reminder'); ?>:</strong> <?php echo $reminder_before_label !== '' ? esc($reminder_before_label) : '-'; ?></div>
                    <div class="mb10"><strong><?php echo app_lang('created_at'); ?>:</strong> <?php echo esc($created_at); ?></div>
                    <div class="mb0"><strong><?php echo app_lang('updated'); ?>:</strong> <?php echo esc($updated_at); ?></div>
                </div>
            </div>

            <div class="card mb20">
                <div class="card-header bg-white">
                    <strong><?php echo app_lang('reminders') . ' (' . app_lang('private') . ')'; ?></strong>
                </div>
                <div class="card-body">
                    <?php echo form_open(get_uri('organizador/tasks/save_reminder'), array('id' => 'organizador-reminder-form', 'class' => 'general-form', 'role' => 'form')); ?>
                    <input type="hidden" name="task_id" value="<?php echo (int) $task->id; ?>">
                    <div class="form-group mb10">
                        <input type="text" name="title" class="form-control" placeholder="<?php echo app_lang('title'); ?>" data-rule-required="true" data-msg-required="<?php echo app_lang('field_required'); ?>">
                    </div>
                    <div class="row">
                        <div class="col-6 form-group mb10">
                            <input type="text" id="organizador-remind-date" name="remind_date" class="form-control" placeholder="<?php echo app_lang('date'); ?>" autocomplete="off" data-rule-required="true" data-msg-required="<?php echo app_lang('field_required'); ?>">
                        </div>
                        <div class="col-6 form-group mb10">
                            <input type="text" id="organizador-remind-time" name="remind_time" class="form-control" placeholder="<?php echo app_lang('time'); ?>" autocomplete="off" data-rule-required="true" data-msg-required="<?php echo app_lang('field_required'); ?>">
                        </div>
                    </div>
                    <div class="form-group mb10">
                        <textarea name="description" class="form-control" placeholder="<?php echo app_lang('description'); ?>" style="min-height: 80px;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang('add_reminder'); ?></button>
                    <?php echo form_close(); ?>

                    <div id="organizador-reminders-list" class="mt15">
                        <?php foreach ($reminders as $reminder) { ?>
                            <?php echo view('Organizador\\Views\\tasks\\reminder_row', array('reminder' => $reminder, 'can_manage' => $can_edit || $login_user->is_admin || (int) $reminder->created_by === (int) $login_user->id)); ?>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($task->tags_html)) { ?>
                <div class="card">
                    <div class="card-body">
                        <strong><?php echo app_lang('labels'); ?></strong>
                        <div class="mt10"><?php echo $task->tags_html; ?></div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<div class="modal-footer">
    <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='copy' class='icon-16'></i> " . app_lang('clone_task'), array('class' => 'btn btn-default float-start', 'data-post-id' => $task->id, 'data-post-is_clone' => true, 'title' => app_lang('clone_task'))); ?>
    <?php if ($can_edit) { ?>
        <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='edit-2' class='icon-16'></i> " . app_lang('edit_task'), array('class' => 'btn btn-default', 'data-post-id' => $task->id, 'title' => app_lang('edit_task'))); ?>
    <?php } ?>
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
</div>

<style>
    .organizador-task-view .organizador-hero {
        padding: 18px 20px;
        border: 1px solid #e9ecef;
        border-radius: 16px;
        background: linear-gradient(180deg, #fbfcff 0%, #f5f8fc 100%);
    }

    .organizador-task-view .organizador-task-description {
        min-height: 120px;
        line-height: 1.65;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        setDatePicker("#organizador-remind-date");
        setTimePicker("#organizador-remind-time");

        $("#organizador-reminder-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                $("#organizador-reminders-list").prepend(result.data);
                $("#organizador-reminder-form").find("input[type='text'], textarea").val("");
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>
