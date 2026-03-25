<?php
$model_info = isset($model_info) ? $model_info : null;
echo form_open(get_uri("projectanalizer/evolucao/save_task_cost/" . $project_id), array("id" => "task-cost-form", "class" => "general-form", "role" => "form"));
echo form_hidden("id", (string) ($model_info ? $model_info->id : 0));
?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="planned_date" class=" col-md-4"><?php echo app_lang("date"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "planned_date",
                        "name" => "planned_date",
                        "value" => $model_info && $model_info->planned_date ? $model_info->planned_date : date("Y-m-d"),
                        "class" => "form-control",
                        "placeholder" => app_lang("date"),
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
                <label for="cost_type" class=" col-md-4"><?php echo app_lang("cost_type"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_dropdown("cost_type", array(
                        "material" => app_lang("cost_material"),
                        "mao_obra" => app_lang("cost_labor"),
                        "servico" => app_lang("cost_service"),
                        "terceiros" => app_lang("cost_third_party"),
                        "outros" => app_lang("cost_other")
                    ), $model_info ? $model_info->cost_type : "", "class='form-control' id='cost_type' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'");
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
                        "value" => $model_info ? to_currency($model_info->planned_value) : "",
                        "class" => "form-control",
                        "placeholder" => app_lang("planned_value"),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button id="save-and-add-task-cost-button" type="button" class="btn btn-default"><span data-feather="plus-circle" class="icon-16"></span> <?php echo app_lang('save_and_add_more'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var saveAndAddMore = false;
        var resetModalLoadingState = function () {
            var $modalBody = $("#ajaxModalContent").find(".modal-body");
            $modalBody.removeClass("hide");
            $modalBody.closest(".modal-dialog").find("[type='submit']").removeAttr("disabled");
            $(".modal-mask").remove();
        };

        var refreshExpensesSection = function () {
            if (typeof window.refreshProjectRevenuesExpensesSection === "function") {
                window.refreshProjectRevenuesExpensesSection();
                return;
            }

            location.reload();
        };

        setDatePicker("#planned_date");
        $("#save-and-add-task-cost-button").click(function () {
            saveAndAddMore = true;
            $("#task-cost-form").trigger("submit");
        });

        var $taskCostForm = $("#task-cost-form").appForm({
            closeModalOnSuccess: false,
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }

                if (saveAndAddMore) {
                    saveAndAddMore = false;
                    resetModalLoadingState();
                    refreshExpensesSection();
                    $("#planned_date").val("<?php echo date("Y-m-d"); ?>");
                    $("#task-cost-form input[name='id']").val("0");
                    $("#planned_value").val("");
                    $("#cost_type").prop("selectedIndex", 0).trigger("change");
                    $("#planned_value").focus();
                    return;
                }

                $taskCostForm.closeModal();
                refreshExpensesSection();
            }
        });
    });
</script>
