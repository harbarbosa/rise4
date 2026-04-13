<?php
$model_info = $model_info ?? (object) array();
?>

<?php echo form_open(get_uri("organizador/tasks/save"), array("id" => "organizador-task-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? 0); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('organizador_task_title'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => $model_info->title ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('organizador_task_title'),
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "description",
                        "name" => "description",
                        "value" => $model_info->description ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('description'),
                        "style" => "height:120px;",
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="priority" class="col-md-3"><?php echo app_lang('organizador_priority'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('priority', $priorities_dropdown, $model_info->priority ?? 'medium', "class='select2 validate-hidden w100p'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="status" class="col-md-3"><?php echo app_lang('organizador_status'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('status', $statuses_dropdown, $model_info->status ?? 'pending', "class='select2 validate-hidden w100p'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="category_id" class="col-md-3"><?php echo app_lang('organizador_categories'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('category_id', $categories_dropdown, $model_info->category_id ?? '', "class='select2 validate-hidden w100p'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="assigned_to" class="col-md-3"><?php echo app_lang('organizador_task_assigned_to'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "assigned_to",
                        "name" => "assigned_to",
                        "value" => $model_info->assigned_to ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('organizador_task_assigned_to'),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('organizador_due_date'); ?></label>
                <div class="col-md-4">
                    <?php
                    echo form_input(array(
                        "id" => "due_date_date",
                        "name" => "due_date_date",
                        "value" => $model_info->due_date_date ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('organizador_due_date'),
                        "autocomplete" => "off",
                    ));
                    ?>
                </div>
                <div class="col-md-5">
                    <?php
                    echo form_input(array(
                        "id" => "due_date_time",
                        "name" => "due_date_time",
                        "value" => $model_info->due_date_time ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('time'),
                        "autocomplete" => "off",
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('organizador_reminder_before_due'); ?></label>
                <div class="col-md-4">
                    <?php
                    echo form_input(array(
                        "id" => "reminder_before_value",
                        "name" => "reminder_before_value",
                        "value" => $model_info->reminder_before_value ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('organizador_reminder_before_value'),
                        "min" => "0",
                        "autocomplete" => "off",
                    ));
                    ?>
                </div>
                <div class="col-md-5">
                    <?php
                    echo form_dropdown('reminder_before_unit', array(
                        'minutes' => app_lang('organizador_reminder_unit_minutes'),
                        'hours' => app_lang('organizador_reminder_unit_hours'),
                        'days' => app_lang('organizador_reminder_unit_days'),
                    ), $model_info->reminder_before_unit ?? 'days', "class='select2 validate-hidden w100p'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="labels" class="col-md-3"><?php echo app_lang('labels'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "labels",
                        "name" => "labels",
                        "value" => $model_info->labels ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('labels'),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check">
                        <?php echo form_checkbox("is_favorite", "1", !empty($model_info->is_favorite), "id='is_favorite' class='form-check-input'"); ?>
                        <label for="is_favorite" class="form-check-label"><?php echo app_lang('organizador_mark_favorite'); ?></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check">
                        <?php echo form_checkbox("notify_assigned_to", "1", isset($model_info->notify_assigned_to) ? (bool) $model_info->notify_assigned_to : true, "id='notify_assigned_to' class='form-check-input'"); ?>
                        <label for="notify_assigned_to" class="form-check-label"><?php echo app_lang('organizador_notify_assigned_to'); ?></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check">
                        <?php echo form_checkbox("notify_creator", "1", isset($model_info->notify_creator) ? (bool) $model_info->notify_creator : true, "id='notify_creator' class='form-check-input'"); ?>
                        <label for="notify_creator" class="form-check-label"><?php echo app_lang('organizador_notify_creator'); ?></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check">
                        <?php echo form_checkbox("email_notification", "1", isset($model_info->email_notification) ? (bool) $model_info->email_notification : true, "id='email_notification' class='form-check-input'"); ?>
                        <label for="email_notification" class="form-check-label"><?php echo app_lang('organizador_email_notification'); ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        window.organizadorRefreshAfterSave = window.organizadorRefreshAfterSave || function () {
            if ($("#organizador-table").length) {
                $("#organizador-table").appTable({reload: true});
                return;
            }

            if ($("#organizador-kanban-container").length || $("#organizador-dashboard-wrapper").length || $("#organizador-calendar-wrapper").length) {
                location.reload();
                return;
            }

            location.reload();
        };

        window.organizadorTaskForm = $("#organizador-task-form").appForm({
            closeModalOnSuccess: false,
            onSuccess: function (result) {
                if (typeof window.organizadorRefreshAfterSave === "function") {
                    window.organizadorRefreshAfterSave(result);
                } else if ($("#organizador-table").length) {
                    $("#organizador-table").appTable({reload: true});
                } else {
                    location.reload();
                }

                window.organizadorTaskForm.closeModal();
            }
        });

        $("#organizador-task-form .select2").select2();
        $("#assigned_to").appDropdown({
            list_data: <?php echo json_encode($team_members_dropdown); ?>
        });
        $("#labels").select2({
            multiple: true,
            data: <?php echo json_encode($tags_dropdown); ?>
        });
        setDatePicker("#due_date_date");
        setTimePicker("#due_date_time");

        setTimeout(function () {
            $("#title").focus();
        }, 200);
    });
</script>
