<?php
echo form_open(get_uri("projectanalizer/save_execution_schedule"), array("id" => "execution-schedule-form", "class" => "general-form", "role" => "form"));
echo form_hidden("id", (string) $model_info->id);
?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group <?php echo $fixed_project ? "hide" : ""; ?>">
            <div class="row">
                <label for="project_id" class="col-md-3"><?php echo app_lang("project"); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_dropdown("project_id", $projects_dropdown, $selected_project_id, "class='form-control select2' id='project_id' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'");
                    ?>
                </div>
            </div>
        </div>

        <?php if ($fixed_project) { ?>
            <input type="hidden" name="project_id" value="<?php echo $selected_project_id; ?>" />
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="user_ids" class="col-md-3"><?php echo app_lang("member"); ?></label>
                <div class="col-md-9">
                    <?php
                    $member_select_attributes = "class='form-control select2' id='user_ids' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "' multiple='multiple'";

                    echo form_dropdown("user_ids[]", $members_dropdown, $selected_member_ids, $member_select_attributes);
                    ?>
                    <small class="text-muted"><?php echo app_lang("execution_schedule_multiple_members_help"); ?></small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="start_date" class="col-md-3"><?php echo app_lang("start_date"); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "start_date",
                        "name" => "start_date",
                        "value" => $model_info->start_date ? $model_info->start_date : $start_date,
                        "class" => "form-control",
                        "autocomplete" => "off",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="end_date" class="col-md-3"><?php echo app_lang("end_date"); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "end_date",
                        "name" => "end_date",
                        "value" => $model_info->end_date ? $model_info->end_date : $end_date,
                        "class" => "form-control",
                        "autocomplete" => "off",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="status" class="col-md-3"><?php echo app_lang("status"); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_dropdown("status", $status_dropdown, $model_info->status ? $model_info->status : "planned", "class='form-control select2' id='status'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="notes" class="col-md-3"><?php echo app_lang("notes"); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "notes",
                        "name" => "notes",
                        "value" => $model_info->notes,
                        "class" => "form-control",
                        "placeholder" => app_lang("notes")
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang("close"); ?></button>
    <?php if ($model_info->id) { ?>
        <button type="button" class="btn btn-danger" id="delete-execution-schedule"><span data-feather="trash-2" class="icon-16"></span> <?php echo app_lang("delete"); ?></button>
    <?php } ?>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang("save"); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#execution-schedule-form").appForm({
            closeModalOnSuccess: false,
            onSuccess: function (result) {
                if (window.executionScheduleCalendar) {
                    window.executionScheduleCalendar.refetchEvents();
                }
                if (result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }
                $("#execution-schedule-form").closest(".modal").modal("hide");
            }
        });

        $("#execution-schedule-form .select2").select2();
        setDatePicker("#start_date");
        setDatePicker("#end_date");

        $("#delete-execution-schedule").on("click", function () {
            appAjaxRequest({
                url: "<?php echo get_uri("projectanalizer/delete_execution_schedule"); ?>",
                type: "POST",
                dataType: "json",
                data: {id: "<?php echo (int) $model_info->id; ?>"},
                success: function (result) {
                    if (result.success) {
                        if (window.executionScheduleCalendar) {
                            window.executionScheduleCalendar.refetchEvents();
                        }
                        appAlert.success(result.message, {duration: 10000});
                        $("#execution-schedule-form").closest(".modal").modal("hide");
                    } else if (result.message) {
                        appAlert.error(result.message, {duration: 10000});
                    }
                }
            });
        });
    });
</script>
