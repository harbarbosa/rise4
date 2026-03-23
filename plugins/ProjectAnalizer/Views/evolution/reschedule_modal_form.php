<?php echo form_open(get_uri("projectanalizer/evolucao/reschedule_project/" . $project_id), array("id" => "reschedule-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="new_start" class=" col-md-4"><?php echo app_lang("new_start_date"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "new_start",
                        "name" => "new_start",
                        "value" => "",
                        "class" => "form-control",
                        "placeholder" => app_lang("new_start_date"),
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
                <label class=" col-md-4"><?php echo app_lang("reschedule_mode"); ?></label>
                <div class=" col-md-8">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode_delta" value="delta" checked>
                        <label class="form-check-label" for="mode_delta"><?php echo app_lang("reschedule_delta"); ?></label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode_dependencies" value="dependencies">
                        <label class="form-check-label" for="mode_dependencies"><?php echo app_lang("reschedule_dependencies"); ?></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class=" col-md-4"><?php echo app_lang("apply_scope"); ?></label>
                <div class=" col-md-8">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="apply_scope" id="apply_pending" value="pending_only" checked>
                        <label class="form-check-label" for="apply_pending"><?php echo app_lang("pending_only"); ?></label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="apply_scope" id="apply_all" value="all_except_completed">
                        <label class="form-check-label" for="apply_all"><?php echo app_lang("all_except_completed"); ?></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class=" col-md-4"><?php echo app_lang("adjust_milestones"); ?></label>
                <div class=" col-md-8">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="adjust_milestones" value="1"> <?php echo app_lang("yes"); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class=" col-md-4"><?php echo app_lang("clamp_before_start"); ?></label>
                <div class=" col-md-8">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="clamp_before_start" value="1" checked> <?php echo app_lang("yes"); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class=" col-md-4"><?php echo app_lang("sequence_tasks"); ?></label>
                <div class=" col-md-8">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="sequence_tasks" value="1"> <?php echo app_lang("yes"); ?>
                    </label>
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
        setDatePicker("#new_start");
        $("#reschedule-form").appForm({
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }
                location.reload();
            }
        });
    });
</script>
