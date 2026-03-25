<?php echo form_open(get_uri("projectanalizer/save_task"), array("id" => "task-form", "class" => "general-form", "role" => "form")); ?>
<div id="tasks-dropzone" class="post-dropzone">
    <div class="modal-body clearfix">
        <div class="container-fluid">
            <input type="hidden" name="id" value="<?php echo $add_type == "multiple" ? "" : $model_info->id; ?>" />
            <input type="hidden" name="add_type" value="<?php echo $add_type; ?>" />

            <?php
            $contexts_dropdown = array();

            foreach ($contexts as $context) {
                if ($context !== "general") {
                    $context_id_key = $context . "_id";
                    $contexts_dropdown[$context] = app_lang($context);
            ?>

                    <input type="hidden" name="<?php echo $context_id_key; ?>" value="<?php echo ${$context_id_key}; ?>" />

            <?php } else {
                    $contexts_dropdown[$context] = "-";
                }
            } ?>

            <?php if ($is_clone) { ?>
                <input type="hidden" name="is_clone" value="1" />
            <?php } ?>

            <div class="form-group">
                <div class="row">
                    <label for="title" class=" col-md-3"><?php echo app_lang('title'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "title",
                            "name" => "title",
                            "value" => $add_type == "multiple" ? "" : $model_info->title,
                            "class" => "form-control",
                            "placeholder" => app_lang('title'),
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
                    <label for="description" class=" col-md-3"><?php echo app_lang('description'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "description",
                            "name" => "description",
                            "value" => $add_type == "multiple" ? "" : process_images_from_content($model_info->description, false),
                            "class" => "form-control",
                            "placeholder" => app_lang('description'),
                            "data-rich-text-editor" => true
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <?php
            $related_to_dropdowns = array();
            if ($show_contexts_dropdown) {
                if (get_setting("support_only_project_related_tasks_globally")) {
            ?>
                    <input type="hidden" name="context" id="task-context" value="project" />
                <?php
                } else {
                ?>

                    <div class="form-group">
                        <div class="row">
                            <label for="context" class=" col-md-3"><?php echo app_lang('related_to'); ?></label>
                            <div class=" col-md-9">
                                <?php
                                echo form_dropdown(
                                    "context",
                                    $contexts_dropdown,
                                    $selected_context,
                                    "class='select2' id='task-context'"
                                );
                                ?>
                            </div>
                        </div>
                    </div>

                <?php }
            } else { ?>
                <input type="hidden" name="context" id="task-context" value="<?php echo $selected_context; ?>" />
            <?php } ?>

            <?php
            //when opening from global task creation link, there might be only one context perimission
            //and don't have any context_id selected. So, have to show the context dropdown
            if (!$show_contexts_dropdown) {
                $context_id_key = $selected_context . "_id";
                if ($selected_context === "general" || ($selected_context === "project" && $model_info->id) || !${$context_id_key}) {
                    $show_contexts_dropdown = true;
                }
            }

            if ($show_contexts_dropdown) {

                foreach ($contexts as $context) {
                    if ($context !== "general") {
                        $context_id_key = $context . "_id";
                        $dropdown_var = $context . "s_dropdown";
                        $related_to_dropdowns[$context] = isset($$dropdown_var) ? $$dropdown_var : array();
            ?>
                        <div class="form-group hide" id="<?php echo $context; ?>-dropdown">
                            <div class="row">
                                <label for="<?php echo $context_id_key; ?>" class=" col-md-3"><?php echo app_lang($context); ?></label>
                                <div class="col-md-9">
                                    <?php
                                    echo form_input(array(
                                        "id" => $context_id_key,
                                        "name" => $context_id_key,
                                        "value" => $model_info->$context_id_key,
                                        "class" => "form-control task-context-options",
                                        "placeholder" => app_lang($context),
                                        "data-msg-required" => app_lang("field_required"),
                                    ));
                                    ?>
                                </div>
                            </div>
                        </div>
            <?php
                    }
                }
            }
            ?>



            <div class="form-group">
                <div class="row">
                    <label for="points" class="col-md-3"><?php echo app_lang('points'); ?>
                        <span class="help" data-bs-toggle="tooltip" title="<?php echo app_lang('task_point_help_text'); ?>"><i data-feather="help-circle" class="icon-16"></i></span>
                    </label>

                    <div class="col-md-9">
                        <?php
                        echo form_dropdown("points", $points_dropdown, array($model_info->points), "class='select2 js_app_dropdown'");
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="percentage" class="col-md-3">Percentual da tarefa</label>
                    <div class="col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "percentage",
                            "name" => "percentage",
                            "type" => "number",
                            "min" => 0,
                            "max" => 100,
                            "step" => "0.01",
                            "value" => $add_type == "multiple" ? "" : $model_info->percentage,
                            "class" => "form-control",
                            "placeholder" => "0.00",
                            "data-rule-number" => true,
                            "data-rule-min" => 0,
                            "data-rule-max" => 100
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group" id="milestones-dropdown">
                <div class="row">
                    <label for="milestone_id" class=" col-md-3"><?php echo app_lang('milestone'); ?></label>
                    <div class="col-md-9" id="dropdown-apploader-section">
                        <?php
                        echo form_input(array(
                            "id" => "milestone_id",
                            "name" => "milestone_id",
                            "value" => $model_info->milestone_id,
                            "class" => "form-control",
                            "placeholder" => app_lang('milestone')
                        ));
                        ?>
                        <div id="milestone-percentage-summary" class="mt10 hide"></div>
                    </div>
                </div>
            </div>


            <?php if ($show_assign_to_dropdown) { ?>
                <div class="form-group">
                    <div class="row">
                        <label for="assigned_to" class=" col-md-3"><?php echo app_lang('assign_to'); ?></label>
                        <div class="col-md-9" id="dropdown-apploader-section">
                            <?php
                            echo form_input(array(
                                "id" => "assigned_to",
                                "name" => "assigned_to",
                                "value" => $model_info->assigned_to,
                                "class" => "form-control",
                                "placeholder" => app_lang('assign_to')
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="collaborators" class=" col-md-3"><?php echo app_lang('collaborators'); ?></label>
                        <div class="col-md-9" id="dropdown-apploader-section">
                            <?php
                            echo form_input(array(
                                "id" => "collaborators",
                                "name" => "collaborators",
                                "value" => $model_info->collaborators,
                                "class" => "form-control",
                                "placeholder" => app_lang('collaborators')
                            ));
                            ?>
                        </div>
                    </div>
                </div>

            <?php } ?>

            <?php
            $labor_profiles = isset($labor_profiles) && is_array($labor_profiles) ? $labor_profiles : array();
            $task_labor_profiles = isset($task_labor_profiles) && is_array($task_labor_profiles) ? $task_labor_profiles : array();
            $labor_profiles_options = "<option value=''>" . app_lang("select_labor_profile") . "</option>";
            foreach ($labor_profiles as $profile) {
                $labor_profiles_options .= "<option value='" . esc($profile->id) . "'>" . esc($profile->name) . "</option>";
            }
            ?>

            <div class="form-group">
                <div class="row">
                    <label class=" col-md-3"><?php echo app_lang("execution_team_by_profile"); ?></label>
                    <div class="col-md-9">
                        <input type="hidden" name="labor_profiles_present" value="1" />
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="min-width: 220px;"><?php echo app_lang("labor_profile"); ?></th>
                                        <th style="min-width: 140px;"><?php echo app_lang("labor_qty_people"); ?></th>
                                        <th style="width: 1%;"><?php echo app_lang("labor_remove"); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="labor-profiles-rows">
                                    <?php if (!empty($task_labor_profiles)) { ?>
                                        <?php foreach ($task_labor_profiles as $index => $row) { ?>
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="labor_profiles[<?php echo $index; ?>][id]" value="<?php echo esc($row->id); ?>" />
                                                    <select name="labor_profiles[<?php echo $index; ?>][labor_profile_id]" class="form-control labor-profile-select">
                                                        <?php echo str_replace("value='" . esc($row->labor_profile_id) . "'", "value='" . esc($row->labor_profile_id) . "' selected", $labor_profiles_options); ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" name="labor_profiles[<?php echo $index; ?>][qty_people]" class="form-control" value="<?php echo esc($row->qty_people); ?>" />
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-default btn-sm js-remove-labor-profile">
                                                        <i data-feather="x" class="icon-16"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-default btn-sm" id="add-labor-profile">
                            <i data-feather="plus-circle" class="icon-16"></i> <?php echo app_lang("labor_add_profile"); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="status_id" class=" col-md-3"><?php echo app_lang('status'); ?></label>
                    <div class="col-md-9">
                        <?php
                        $selected_status = get_array_value($statuses_dropdown[0], "id");

                        if (!$is_clone && $model_info->status_id) {
                            $selected_status = $model_info->status_id;
                        }

                        echo form_input(array(
                            "id" => "task_status_id",
                            "name" => "status_id",
                            "value" => $selected_status,
                            "class" => "form-control"
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="priority_id" class=" col-md-3"><?php echo app_lang('priority'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "priority_id",
                            "name" => "priority_id",
                            "value" => $model_info->priority_id,
                            "class" => "form-control",
                            "placeholder" => app_lang('priority')
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="project_labels" class=" col-md-3"><?php echo app_lang('labels'); ?></label>
                    <div class=" col-md-9" id="dropdown-apploader-section">
                        <?php
                        echo form_input(array(
                            "id" => "project_labels",
                            "name" => "labels",
                            "value" => $model_info->labels,
                            "class" => "form-control",
                            "placeholder" => app_lang('labels')
                        ));
                        ?>
                    </div>
                </div>
            </div>
                        
            </div>
            <div class="clearfix">
                <div class="row">
                    <label for="duration_days" class=" col-md-3">Duracao (dias)</label>
                    <div class=" col-md-9 form-group">
                        <?php
                        echo form_input(array(
                            "id" => "duration_days",
                            "name" => "duration_days",
                            "autocomplete" => "off",
                            "value" => isset($model_info->duration_days) ? $model_info->duration_days : "",
                            "class" => "form-control",
                            "type" => "number",
                            "min" => "1",
                            "step" => "1"
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="clearfix">
                <div class="row">
                    <label for="start_date" class="<?php echo $show_time_with_task ? "col-md-3 col-sm-3" : "col-md-3" ?>"><?php echo app_lang('start_date'); ?></label>
                    <div class="<?php echo $show_time_with_task ? "col-md-4 col-sm-4" : "col-md-9" ?> form-group">
                        <?php
                        echo form_input(array(
                            "id" => "start_date",
                            "name" => "start_date",
                            "autocomplete" => "off",
                            "value" => is_date_exists($model_info->start_date) ? format_to_date($model_info->start_date, false) : "",
                            "class" => "form-control",
                            "placeholder" => "YYYY-MM-DD"
                        ));
                        ?>
                    </div>

                    <?php if ($show_time_with_task) { ?>
                        <label for="start_time" class=" col-md-2 col-sm-2"><?php echo app_lang('start_time'); ?></label>
                        <div class=" col-md-3 col-sm-3 form-group">
                            <?php
                            $start_date = (is_date_exists($model_info->start_date)) ? $model_info->start_date : "";
                            if ($time_format_24_hours) {
                                $start_time = $start_date ? date("H:i", strtotime($start_date)) : "";
                            } else {
                                if (date("H:i:s", strtotime($start_date)) == "00:00:00") {
                                    $start_time = "";
                                } else {
                                    $start_time = $start_date ? convert_time_to_12hours_format(date("H:i:s", strtotime($start_date))) : "";
                                }
                            }
                            echo form_input(array(
                                "id" => "start_time",
                                "name" => "start_time",
                                "value" => $start_time,
                                "class" => "form-control",
                                "placeholder" => app_lang('start_time')
                            ));
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="clearfix">
                <div class="row">
                    <label for="deadline" class="<?php echo $show_time_with_task ? "col-md-3 col-sm-3" : "col-md-3" ?>"><?php echo app_lang('deadline'); ?></label>
                    <div class="<?php echo $show_time_with_task ? "col-md-4 col-sm-4" : "col-md-9" ?> form-group">
                        <?php
                        echo form_input(array(
                            "id" => "deadline",
                            "name" => "deadline",
                            "autocomplete" => "off",
                            "value" => is_date_exists($model_info->deadline) ? format_to_date($model_info->deadline, false) : "",
                            "class" => "form-control",
                            "placeholder" => "DD/MM/AAAA",
                            "data-rule-greaterThanOrEqual" => "#start_date",
                            "data-msg-greaterThanOrEqual" => app_lang("deadline_must_be_equal_or_greater_than_start_date")
                        ));
                        ?>
                    </div>

                    <?php if ($show_time_with_task) { ?>
                        <label for="end_time" class=" col-md-2 col-sm-2"><?php echo app_lang('end_time'); ?></label>
                        <div class=" col-md-3 col-sm-3 form-group">
                            <?php
                            $deadline = (is_date_exists($model_info->deadline)) ? $model_info->deadline : "";
                            if ($time_format_24_hours) {
                                $end_time = $deadline ? date("H:i", strtotime($deadline)) : "";
                            } else {
                                if (date("H:i:s", strtotime($deadline)) == "00:00:00") {
                                    $end_time = "";
                                } else {
                                    $end_time = $deadline ? convert_time_to_12hours_format(date("H:i:s", strtotime($deadline))) : "";
                                }
                            }
                            echo form_input(array(
                                "id" => "end_time",
                                "name" => "end_time",
                                "value" => $end_time,
                                "class" => "form-control",
                                "placeholder" => app_lang('end_time')
                            ));
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>



            <?php if (get_setting("enable_recurring_option_for_tasks")) { ?>

                <div class="form-group">
                    <div class="row">
                        <label for="recurring" class=" col-md-3"><?php echo app_lang('recurring'); ?> <span class="help" data-bs-toggle="tooltip" title="<?php echo app_lang('cron_job_required'); ?>"><i data-feather="help-circle" class="icon-16"></i></span></label>
                        <div class=" col-md-9">
                            <?php
                            echo form_checkbox("recurring", "1", $model_info->recurring ? true : false, "id='recurring' class='form-check-input'");
                            ?>
                        </div>
                    </div>
                </div>

                <div id="recurring_fields" class="<?php if (!$model_info->recurring) echo "hide"; ?>">
                    <div class="form-group">
                        <div class="row">
                            <label for="repeat_every" class=" col-md-3"><?php echo app_lang('repeat_every'); ?></label>
                            <div class="col-md-4">
                                <?php
                                echo form_input(array(
                                    "id" => "repeat_every",
                                    "name" => "repeat_every",
                                    "type" => "number",
                                    "value" => $model_info->repeat_every ? $model_info->repeat_every : 1,
                                    "min" => 1,
                                    "class" => "form-control recurring_element",
                                    "placeholder" => app_lang('repeat_every'),
                                    "data-rule-required" => true,
                                    "data-msg-required" => app_lang("field_required")
                                ));
                                ?>
                            </div>
                            <div class="col-md-5">
                                <?php
                                echo form_dropdown(
                                    "repeat_type",
                                    array(
                                        "days" => app_lang("interval_days"),
                                        "weeks" => app_lang("interval_weeks"),
                                        "months" => app_lang("interval_months"),
                                        "years" => app_lang("interval_years"),
                                    ),
                                    $model_info->repeat_type ? $model_info->repeat_type : "months",
                                    "class='select2 js_app_dropdown recurring_element' id='repeat_type'"
                                );
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="no_of_cycles" class=" col-md-3"><?php echo app_lang('cycles'); ?></label>
                            <div class="col-md-4">
                                <?php
                                echo form_input(array(
                                    "id" => "no_of_cycles",
                                    "name" => "no_of_cycles",
                                    "type" => "number",
                                    "min" => 1,
                                    "value" => $model_info->no_of_cycles ? $model_info->no_of_cycles : "",
                                    "class" => "form-control",
                                    "placeholder" => app_lang('cycles')
                                ));
                                ?>
                            </div>
                            <div class="col-md-5 mt5">
                                <span class="help" data-bs-toggle="tooltip" title="<?php echo app_lang('recurring_cycle_instructions'); ?>"><i data-feather="help-circle" class="icon-16"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group hide" id="next_recurring_date_container">
                        <div class="row">
                            <label for="next_recurring_date" class=" col-md-3"><?php echo app_lang('next_recurring_date'); ?> </label>
                            <div class=" col-md-9">
                                <?php
                                echo form_input(array(
                                    "id" => "next_recurring_date",
                                    "name" => "next_recurring_date",
                                    "class" => "form-control",
                                    "placeholder" => app_lang('next_recurring_date'),
                                    "autocomplete" => "off",
                                    "data-rule-required" => true,
                                    "data-msg-required" => app_lang("field_required"),
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php } ?>

            <?php echo view("custom_fields/form/prepare_context_fields", array("custom_fields" => $custom_fields, "label_column" => "col-md-3", "field_column" => " col-md-9")); ?>

            <?php echo view("includes/dropzone_preview"); ?>

            <?php if ($is_clone) { ?>
                <?php if ($has_checklist) { ?>
                    <div class="form-group">
                        <label for="copy_checklist" class=" col-md-12">
                            <?php
                            echo form_checkbox("copy_checklist", "1", true, "id='copy_checklist' class='float-start mr15 form-check-input'");
                            ?>
                            <?php echo app_lang('copy_checklist'); ?>
                        </label>
                    </div>
                <?php } ?>

                <?php if ($has_sub_task) { ?>
                    <div class="form-group">
                        <label for="copy_sub_tasks" class=" col-md-12">
                            <?php
                            echo form_checkbox("copy_sub_tasks", "1", false, "id='copy_sub_tasks' class='float-start mr15 form-check-input'");
                            ?>
                            <?php echo app_lang('copy_sub_tasks'); ?>
                        </label>
                    </div>
                <?php } ?>

                <?php if ($model_info->parent_task_id) { ?>
                    <input type="hidden" name="parent_task_id" value="<?php echo $model_info->parent_task_id; ?>" />
                    <div class="form-group">
                        <label for="create_as_a_non_subtask" class=" col-md-12">
                            <?php
                            echo form_checkbox("create_as_a_non_subtask", "1", false, "id='create_as_a_non_subtask' class='float-start mr15 form-check-input'");
                            ?>
                            <?php echo app_lang('create_as_a_non_subtask'); ?>
                        </label>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <div class="modal-footer">
        <div id="link-of-new-view" class="hide">
            <?php
            echo modal_anchor(get_uri("tasks/view"), "", array("data-modal-lg" => "1"));
            ?>
        </div>

        <?php
        if (!$model_info->id || $add_type == "multiple") {
            echo view("includes/upload_button");
        }
        ?>

        <button type="button" class="btn btn-default hidden-xs" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>

        <?php if ($add_type == "multiple") { ?>
            <button id="save-and-add-button" type="button" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save_and_add_more'); ?></button>
        <?php } else { ?>
            <?php if ($view_type !== "details") { ?>
                <button id="save-and-show-button" type="button" class="btn btn-info text-white"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save_and_show'); ?></button>
            <?php } ?>
            <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
        <?php } ?>
    </div>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {

        //send data to show the task after save
        window.showAddNewModal = false;

        $("#save-and-show-button, #save-and-add-button").click(function() {
            window.showAddNewModal = true;
            $("#task-form").trigger("submit");
        });

        var taskShowText = "<?php echo app_lang('task_info') ?>",
            multipleTaskAddText = "<?php echo app_lang('add_multiple_tasks') ?>",
            addType = "<?php echo $add_type; ?>";

        function escapeMilestoneSummaryText(text) {
            return $("<div>").text(text || "").html();
        }

        function renderMilestonePercentageSummary(result) {
            var allocated = parseFloat(result.allocated_percentage || 0);
            var remaining = parseFloat(result.remaining_percentage || 0);
            var currentTask = parseFloat(result.current_task_percentage || 0);
            var progressClass = allocated >= 100 ? "bg-success" : "bg-primary";
            var summaryHtml = "<div class='alert alert-info mb0'>" +
                "<div><strong>Etapa:</strong> " + escapeMilestoneSummaryText(result.milestone_title) + "</div>" +
                "<div><strong>Percentual já lançado:</strong> " + result.allocated_percentage + "%</div>" +
                "<div><strong>Saldo disponível:</strong> " + result.remaining_percentage + "%</div>";

            if (currentTask > 0) {
                summaryHtml += "<div><strong>Percentual atual desta tarefa:</strong> " + result.current_task_percentage + "%</div>";
            }

            summaryHtml += "<div class='progress mt10 mb0' title='" + result.allocated_percentage + "%'>" +
                    "<div class='progress-bar " + progressClass + "' role='progressbar' aria-valuenow='" + result.allocated_percentage + "' aria-valuemin='0' aria-valuemax='100' style='width: " + Math.min(100, allocated) + "%;'></div>" +
                "</div>" +
            "</div>";

            $("#milestone-percentage-summary").html(summaryHtml).removeClass("hide");
        }

        window.loadMilestonePercentageSummary = function (milestoneId) {
            if (!milestoneId) {
                $("#milestone-percentage-summary").addClass("hide").empty();
                return;
            }

            appAjaxRequest({
                url: "<?php echo get_uri('projectanalizer/get_milestone_percentage_summary'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    milestone_id: milestoneId,
                    project_id: "<?php echo (int) $project_id; ?>",
                    task_id: "<?php echo isset($model_info->id) ? (int) $model_info->id : 0; ?>"
                },
                success: function (result) {
                    if (result && result.success) {
                        renderMilestonePercentageSummary(result);
                    } else {
                        $("#milestone-percentage-summary").addClass("hide").empty();
                    }
                },
                error: function () {
                    $("#milestone-percentage-summary").addClass("hide").empty();
                }
            });
        };

        window.taskForm = $("#task-form").appForm({
            closeModalOnSuccess: false,
            onSuccess: function(result) {

                $("#task-table").appTable({
                    newData: result.data,
                    dataId: result.id
                });
                $("#reload-kanban-button:visible").trigger("click");

                $("#save_and_show_value").append(result.save_and_show_link);

                if (window.showAddNewModal) {
                    var $taskViewLink = $("#link-of-new-view").find("a");

                    if (addType === "multiple") {
                        //add multiple tasks
                        $taskViewLink.attr("data-action-url", "<?php echo get_uri("projectanalizer/task_modal_form"); ?>");
                        $taskViewLink.attr("data-title", multipleTaskAddText);
                        $taskViewLink.attr("data-post-last_id", result.id);
                        $taskViewLink.attr("data-post-project_id", "<?php echo isset($project_id) ? $project_id : ''; ?>");
                        $taskViewLink.attr("data-post-add_type", "multiple");
                    } else {
                        //save and show
                        $taskViewLink.attr("data-action-url", "<?php echo get_uri("tasks/view"); ?>");
                        $taskViewLink.attr("data-title", taskShowText + " #" + result.id);
                        $taskViewLink.attr("data-post-id", result.id);
                    }

                    $taskViewLink.trigger("click");
                } else {
                    window.taskForm.closeModal();

                    if (window.refreshAfterAddTask) {
                        window.refreshAfterAddTask = false;
                        location.reload();
                    }
                }

                window.reloadKanban = true;

                if (typeof window.reloadGantt === "function") {
                    window.reloadGantt(true);
                }
            },
            onAjaxSuccess: function(result) {
                if (!result.success && result.next_recurring_date_error) {
                    $("#next_recurring_date").val(result.next_recurring_date_value);
                    $("#next_recurring_date_container").removeClass("hide");

                    $("#task-form").data("validator").showErrors({
                        "next_recurring_date": result.next_recurring_date_error
                    });
                }
            }
        });
        $("#task-form .js_app_dropdown").appDropdown();

        setTimeout(function() {
            $("#title").focus();
        }, 200);

        setDatePicker("#start_date");

        var deadlineEndDate = "<?php echo $project_deadline; ?>";
        setTimeout(function () {
            var $deadline = $("#deadline");
            if (!$deadline.length) {
                return;
            }

            $deadline.removeAttr("readonly").prop("readonly", false).prop("disabled", false).css("pointer-events", "auto");
            try {
                $deadline.datepicker("destroy");
            } catch (e) {
                // ignore
            }

            var dateFormat = getJsDateFormat();
            var validEndDate = "";
            if (deadlineEndDate) {
                if (dateFormat === "yyyy-mm-dd" && /^\d{4}-\d{2}-\d{2}$/.test(deadlineEndDate)) {
                    validEndDate = deadlineEndDate;
                } else if (dateFormat === "dd/mm/yyyy" && /^\d{2}\/\d{2}\/\d{4}$/.test(deadlineEndDate)) {
                    validEndDate = deadlineEndDate;
                }
            }
            var value = $deadline.val();
            if (value && value.indexOf("-") > -1) {
                var dateArray = value.split("-"),
                    year = dateArray[0],
                    month = dateArray[1],
                    day = dateArray[2];

                if (year && month && day) {
                    value = dateFormat.replace("yyyy", year).replace("mm", month).replace("dd", day);
                    $deadline.val(value);
                }
            }

            $deadline.attr("data-convert-date-format", "1");

            var $container = $deadline.closest(".modal");
            if (!$container.length) {
                $container = $(document.body);
            }

            var deadlineOptions = {
                autoclose: true,
                language: "custom",
                todayHighlight: true,
                weekStart: AppHelper.settings.firstDayOfWeek,
                format: dateFormat,
                container: $container
            };
            if (validEndDate) {
                deadlineOptions.endDate = validEndDate;
            }

            $deadline.datepicker(deadlineOptions);
            $deadline.off("click.projectanalizer").on("click.projectanalizer", function () {
                $(this).datepicker("show");
            });
            $deadline.off("focus.projectanalizer").on("focus.projectanalizer", function () {
                if ($(this).data("datepicker")) {
                    $(this).datepicker("show");
                }
            });
        }, 50);

        setTimePicker("#start_time, #end_time");

        $('[data-bs-toggle="tooltip"]').tooltip();

        var dateFormat = getJsDateFormat();
        var isUpdatingDuration = false;
        var isUpdatingDeadline = false;

        function parseDateString(value) {
            if (!value) {
                return null;
            }
            value = $.trim(value);

            if (dateFormat === "dd/mm/yyyy") {
                var parts = value.split("/");
                if (parts.length !== 3) {
                    return null;
                }
                var day = parseInt(parts[0], 10);
                var month = parseInt(parts[1], 10) - 1;
                var year = parseInt(parts[2], 10);
                if (!year || isNaN(day) || isNaN(month)) {
                    return null;
                }
                return new Date(year, month, day, 12, 0, 0, 0);
            }

            if (dateFormat === "yyyy-mm-dd") {
                var partsYmd = value.split("-");
                if (partsYmd.length !== 3) {
                    return null;
                }
                var yearYmd = parseInt(partsYmd[0], 10);
                var monthYmd = parseInt(partsYmd[1], 10) - 1;
                var dayYmd = parseInt(partsYmd[2], 10);
                if (!yearYmd || isNaN(dayYmd) || isNaN(monthYmd)) {
                    return null;
                }
                return new Date(yearYmd, monthYmd, dayYmd, 12, 0, 0, 0);
            }

            return null;
        }

        function formatDate(date) {
            var dd = ("0" + date.getDate()).slice(-2);
            var mm = ("0" + (date.getMonth() + 1)).slice(-2);
            var yyyy = date.getFullYear();

            if (dateFormat === "dd/mm/yyyy") {
                return dd + "/" + mm + "/" + yyyy;
            }
            if (dateFormat === "yyyy-mm-dd") {
                return yyyy + "-" + mm + "-" + dd;
            }
            return dd + "/" + mm + "/" + yyyy;
        }

        var weekendDays = {};
        if (AppHelper.settings.weekends) {
            AppHelper.settings.weekends.split(",").forEach(function (value) {
                value = $.trim(value);
                if (value !== "" && !isNaN(value)) {
                    weekendDays[parseInt(value, 10)] = true;
                }
            });
        }

        function isWeekend(date) {
            var day = date.getDay();
            return weekendDays[day] === true;
        }

        function addBusinessDays(startDate, days) {
            var date = new Date(startDate.getTime());
            var added = 0;
            while (added < days) {
                if (!isWeekend(date)) {
                    added++;
                }
                if (added >= days) {
                    break;
                }
                date.setDate(date.getDate() + 1);
            }
            return date;
        }

        function businessDaysBetween(startDate, endDate) {
            if (!startDate || !endDate) {
                return 0;
            }
            var start = new Date(startDate.getTime());
            var end = new Date(endDate.getTime());
            if (end < start) {
                return 0;
            }
            var count = 0;
            while (start <= end) {
                if (!isWeekend(start)) {
                    count++;
                }
                start.setDate(start.getDate() + 1);
            }
            return count;
        }

        function updateDeadlineFromDuration() {
            if (isUpdatingDuration) {
                return;
            }
            var start = parseDateString($("#start_date").val());
            var duration = parseInt($("#duration_days").val(), 10);
            if (!start || !duration || duration < 1) {
                return;
            }
            isUpdatingDeadline = true;
            var endDate = addBusinessDays(start, duration);
            $("#deadline").val(formatDate(endDate)).trigger("change");
            isUpdatingDeadline = false;
        }

        function updateDurationFromDeadline() {
            if (isUpdatingDeadline) {
                return;
            }
            var start = parseDateString($("#start_date").val());
            var end = parseDateString($("#deadline").val());
            if (!start || !end) {
                return;
            }
            isUpdatingDuration = true;
            var duration = businessDaysBetween(start, end);
            $("#duration_days").val(duration ? duration : "");
            isUpdatingDuration = false;
        }

        $("#start_date").on("change.projectanalizer", function () {
            var durationValue = parseInt($("#duration_days").val(), 10);
            if (durationValue && durationValue > 0) {
                updateDeadlineFromDuration();
            } else {
                updateDurationFromDeadline();
            }
        });

        $("#duration_days").on("input.projectanalizer", function () {
            updateDeadlineFromDuration();
        });

        $("#deadline").on("change.projectanalizer", function () {
            updateDurationFromDeadline();
        });

        if (!$("#duration_days").val()) {
            updateDurationFromDeadline();
        }

        //show/hide recurring fields
        $("#recurring").click(function() {
            if ($(this).is(":checked")) {
                $("#recurring_fields").removeClass("hide");
            } else {
                $("#recurring_fields").addClass("hide");
            }
        });

        var dynamicDates = getDynamicDates();

        setDatePicker("#next_recurring_date", {
            startDate: dynamicDates.tomorrow //set min date = tomorrow
        });

        var laborIndex = $("#labor-profiles-rows tr").length;
        var laborTemplate = $("#labor-profile-row-template").html();

        function addLaborRow() {
            var rowHtml = laborTemplate.replace(/__INDEX__/g, laborIndex);
            var $row = $(rowHtml);
            $("#labor-profiles-rows").append($row);
            $row.find(".labor-profile-select").select2();
            laborIndex++;
        }

        $("#add-labor-profile").on("click", function () {
            addLaborRow();
        });

        $(document).on("click", ".js-remove-labor-profile", function () {
            $(this).closest("tr").remove();
        });

        $(".labor-profile-select").select2();

        $(document).on("change", "#milestone_id", function () {
            window.loadMilestonePercentageSummary($(this).val());
        });

        setTimeout(function () {
            window.loadMilestonePercentageSummary($("#milestone_id").val());
        }, 300);

    });
</script>

<?php
echo view("tasks/get_dropdowns_script", array(
    "related_to_dropdowns" => $related_to_dropdowns,
    "milestones_dropdown" => $milestones_dropdown,
    "assign_to_dropdown" => $assign_to_dropdown,
    "collaborators_dropdown" => $collaborators_dropdown,
    "statuses_dropdown" => $statuses_dropdown,
    "label_suggestions" => $label_suggestions,
    "priorities_dropdown" => $priorities_dropdown
));
?>

<script type="text/template" id="labor-profile-row-template">
    <tr>
        <td>
            <select name="labor_profiles[__INDEX__][labor_profile_id]" class="form-control labor-profile-select">
                <?php echo $labor_profiles_options; ?>
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="labor_profiles[__INDEX__][qty_people]" class="form-control" value="1" />
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-default btn-sm js-remove-labor-profile">
                <i data-feather="x" class="icon-16"></i>
            </button>
        </td>
    </tr>
</script>

