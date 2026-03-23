<?php echo form_open(get_uri("projectanalizer_settings/save_labor_profile"), array("id" => "labor-profile-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info ? $model_info->id : ""; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="name" class=" col-md-3"><?php echo app_lang('labor_profile'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "name",
                        "name" => "name",
                        "value" => $model_info ? $model_info->name : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('labor_profile'),
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="hourly_cost" class=" col-md-3"><?php echo app_lang('labor_hourly_cost'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "hourly_cost",
                        "name" => "hourly_cost",
                        "value" => $model_info ? $model_info->hourly_cost : "",
                        "class" => "form-control",
                        "placeholder" => app_lang('labor_hourly_cost'),
                        "type" => "number",
                        "min" => "0.01",
                        "step" => "0.01",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="default_hours_per_day" class=" col-md-3"><?php echo app_lang('labor_default_hours_per_day'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "default_hours_per_day",
                        "name" => "default_hours_per_day",
                        "value" => $model_info ? $model_info->default_hours_per_day : "8",
                        "class" => "form-control",
                        "placeholder" => app_lang('labor_default_hours_per_day'),
                        "type" => "number",
                        "min" => "0.01",
                        "step" => "0.01"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class=" col-md-3"><?php echo app_lang('status'); ?></label>
                <div class=" col-md-9">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="active" value="1" <?php echo !$model_info || $model_info->active ? "checked" : ""; ?>> <?php echo app_lang('active'); ?>
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
        $("#labor-profile-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    $("#labor-profiles-table").appTable({reload: true});
                }
            }
        });
    });
</script>
