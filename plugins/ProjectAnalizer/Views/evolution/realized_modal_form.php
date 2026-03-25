<?php
$model_info = isset($model_info) ? $model_info : null;
echo form_open(get_uri("projectanalizer/evolucao/save_realized/" . $project_id), array("id" => "realized-form", "class" => "general-form", "role" => "form"));
echo form_hidden("id", (string) ($model_info ? $model_info->id : 0));
?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="date" class=" col-md-4"><?php echo app_lang("date"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "date",
                        "name" => "date",
                        "value" => $model_info && $model_info->date ? $model_info->date : date("Y-m-d"),
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
                <label for="value" class=" col-md-4"><?php echo app_lang("value"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "value",
                        "name" => "value",
                        "value" => $model_info ? to_currency($model_info->value) : "",
                        "class" => "form-control",
                        "placeholder" => app_lang("value"),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class=" col-md-4"><?php echo app_lang("description"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_textarea(array(
                        "id" => "description",
                        "name" => "description",
                        "value" => $model_info ? $model_info->description : "",
                        "class" => "form-control",
                        "placeholder" => app_lang("description"),
                        "rows" => 3
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button id="save-and-add-realized-button" type="button" class="btn btn-default"><span data-feather="plus-circle" class="icon-16"></span> <?php echo app_lang('save_and_add_more'); ?></button>
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

        setDatePicker("#date");
        $("#save-and-add-realized-button").click(function () {
            saveAndAddMore = true;
            $("#realized-form").trigger("submit");
        });

        var $realizedForm = $("#realized-form").appForm({
            closeModalOnSuccess: false,
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }

                if (saveAndAddMore) {
                    saveAndAddMore = false;
                    resetModalLoadingState();
                    refreshExpensesSection();
                    $("#date").val("<?php echo date("Y-m-d"); ?>");
                    $("#realized-form input[name='id']").val("0");
                    $("#cost_type").prop("selectedIndex", 0).trigger("change");
                    $("#value").val("");
                    $("#description").val("");
                    $("#value").focus();
                    return;
                }

                $realizedForm.closeModal();
                refreshExpensesSection();
            }
        });
    });
</script>
