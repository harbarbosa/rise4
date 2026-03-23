<?php echo form_open(get_uri("projectanalizer/evolucao/save_task_cost/" . $project_id), array("id" => "task-cost-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="task_id" class=" col-md-4"><?php echo app_lang("tasks"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_dropdown("task_id", $task_dropdown, "", "class='form-control' id='task_id' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="cost_type" class=" col-md-4"><?php echo app_lang("cost_type"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_dropdown("cost_type", array(
                        "material" => app_lang("cost_material"),
                        "mao_obra" => app_lang("cost_labor"),
                        "servico" => app_lang("cost_service"),
                        "terceiros" => app_lang("cost_third_party"),
                        "outros" => app_lang("cost_other")
                    ), "", "class='form-control' id='cost_type' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="planned_value" class=" col-md-4"><?php echo app_lang("planned_value"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "planned_value",
                        "name" => "planned_value",
                        "value" => "",
                        "class" => "form-control",
                        "placeholder" => app_lang("planned_value"),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="distribution_type" class=" col-md-4"><?php echo app_lang("distribution_type"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_dropdown("distribution_type", array(
                        "linear" => app_lang("distribution_linear"),
                        "inicio" => app_lang("distribution_start"),
                        "fim" => app_lang("distribution_end"),
                        "curva_s" => app_lang("distribution_curve_s"),
                        "manual" => app_lang("distribution_manual")
                    ), "linear", "class='form-control' id='distribution_type'");
                    ?>
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
        $("#task-cost-form").appForm({
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }
                location.reload();
            }
        });
    });
</script>
