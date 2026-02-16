
<?php echo form_open(get_uri("projectanalizer/save_team_activity"), ["id" => "team-activity-form", "class" => "general-form", "role" => "form"]); ?>
<div class="modal-body clearfix">
<input type="hidden" name="id" value="<?= isset($model_info->id) ? $model_info->id : '' ?>" />

    <input type="" name="project_id" value="<?= $project_id ?>" />

    <div class="form-group">
        <label><?= app_lang("members") ?></label>
            
                <?= form_dropdown("member_id[]", $members_dropdown, "", "class='form-control select2' multiple required"); ?>
            
    </div>

    
    <div class="form-group">
        <label><?= app_lang("task") ?></label>
        <?= form_dropdown("task_id", $tasks_dropdown, "", "class='form-control select2' id='team-activity-task'"); ?>
    </div>

    <div class="form-group" id="team-activity-percentage-wrapper" style="display:none;">
        <label>Percentual Executado</label>
        <input type="number" name="percentage_executed" id="team-activity-percentage" class="form-control" min="0" max="100" step="0.01" placeholder="0.00">
    </div>

    <div class="form-group">
        <label><?= app_lang("date") ?></label>
        <input type="date" name="activity_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
    </div>

    <div class="form-group">
        <label><?= app_lang("time_mode") ?></label>
        <?= form_dropdown("time_mode", ["hours" => app_lang("by_hours"), "period" => app_lang("by_period")], "hours", "class='form-control' id='time-mode-selector'"); ?>
    </div>

    <div id="hours-mode">
        <div class="form-group">
            <label><?= app_lang("hours") ?></label>
            <input type="number" step="0.25" name="hours" class="form-control" placeholder="Ex: 2.5">
        </div>
    </div>

    <div id="period-mode" style="display:none;">
        <div class="form-group">
            <label><?= app_lang("start_datetime") ?></label>
            <input type="datetime-local" name="start_datetime" class="form-control">
        </div>
        <div class="form-group">
            <label><?= app_lang("end_datetime") ?></label>
            <input type="datetime-local" name="end_datetime" class="form-control">
        </div>
    </div>

    <div class="form-group">
        <label><?= app_lang("description") ?></label>
        <textarea name="description" class="form-control"></textarea>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= app_lang("close") ?></button>
    <button type="submit" class="btn btn-primary"><?= app_lang("save") ?></button>
</div>
<?php echo form_close(); ?>

<script>
$(document).ready(function () {
    $("#time-mode-selector").change(function() {
        if ($(this).val() === "period") {
            $("#period-mode").show();
            $("#hours-mode").hide();
        } else {
            $("#period-mode").hide();
            $("#hours-mode").show();
        }
    });

    function togglePercentageField() {
        var taskValue = $("#team-activity-task").val();
        var hasTask = taskValue && taskValue !== "";
        if (hasTask) {
            $("#team-activity-percentage-wrapper").show();
            $("#team-activity-percentage").prop("required", true);
        } else {
            $("#team-activity-percentage-wrapper").hide();
            $("#team-activity-percentage").prop("required", false).val("");
        }
    }

    $("#team-activity-task").on("change select2:select", function () {
        togglePercentageField();
    });

    togglePercentageField();
});
</script>
