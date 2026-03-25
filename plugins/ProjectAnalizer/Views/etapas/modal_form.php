<?php echo form_open(get_uri("projectanalizer/save_etapa"), array("id" => "milestone-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
        <div class="form-group">
            <div class="row">
                <label for="title" class=" col-md-3"><?php echo app_lang('title'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => $model_info->title,
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
                        "value" => process_images_from_content($model_info->description, false),
                        "class" => "form-control",
                        "placeholder" => app_lang('description'),
                        "data-rich-text-editor" => true
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="percentage" class=" col-md-3"><?php echo app_lang('milestone_percentage'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "percentage",
                        "name" => "percentage",
                        "value" => $model_info->percentage ?? 0,
                        "class" => "form-control",
                        "placeholder" => app_lang('milestone_percentage'),
                        "type" => "number",
                        "min" => "0",
                        "max" => "100",
                        "step" => "0.01"
                    ));
                    ?>
                    <div id="milestone-allocation-summary" class="mt10 hide"></div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="due_date" class=" col-md-3"><?php echo app_lang('due_date'); ?></label>
                <div class=" col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "due_date",
                        "name" => "due_date",
                        "value" => $model_info->due_date,
                        "class" => "form-control",
                        "placeholder" => app_lang('due_date'),
                        "autocomplete" => "off",
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
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var projectId = "<?php echo (int) $project_id; ?>";
        var milestoneId = "<?php echo (int) $model_info->id; ?>";

        function renderMilestoneAllocationSummary(result) {
            var allocated = parseFloat(result.allocated_percentage || 0);
            var currentPercentage = parseFloat(result.current_milestone_percentage || 0);
            var inputPercentage = parseFloat($("#percentage").val() || 0);
            var projectedTotal = allocated + inputPercentage;
            var projectedRemaining = Math.max(0, 100 - projectedTotal);
            var projectedClass = projectedTotal >= 100 ? "bg-success" : "bg-primary";
            var summaryHtml = "<div class='alert alert-info mb0'>" +
                "<div><strong>Percentual já lançado nas etapas:</strong> " + result.allocated_percentage + "%</div>" +
                "<div><strong>Saldo disponível:</strong> " + result.remaining_percentage + "%</div>";

            if (currentPercentage > 0) {
                summaryHtml += "<div><strong>Percentual atual desta etapa:</strong> " + result.current_milestone_percentage + "%</div>";
            }

            summaryHtml += "<div><strong>Total projetado com este valor:</strong> " + projectedTotal.toFixed(2) + "%</div>" +
                "<div><strong>Saldo projetado após salvar:</strong> " + projectedRemaining.toFixed(2) + "%</div>" +
                "<div class='progress mt10 mb0' title='" + projectedTotal.toFixed(2) + "%'>" +
                    "<div class='progress-bar " + projectedClass + "' role='progressbar' aria-valuenow='" + projectedTotal.toFixed(2) + "' aria-valuemin='0' aria-valuemax='100' style='width: " + Math.min(100, projectedTotal) + "%;'></div>" +
                "</div>" +
            "</div>";

            $("#milestone-allocation-summary").html(summaryHtml).removeClass("hide").data("base-summary", result);
        }

        function refreshMilestoneProjectedSummary() {
            var baseSummary = $("#milestone-allocation-summary").data("base-summary");
            if (baseSummary) {
                renderMilestoneAllocationSummary(baseSummary);
            }
        }

        function loadMilestoneAllocationSummary() {
            appAjaxRequest({
                url: "<?php echo get_uri('projectanalizer/get_project_milestone_percentage_summary'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    project_id: projectId,
                    milestone_id: milestoneId
                },
                success: function (result) {
                    if (result && result.success) {
                        renderMilestoneAllocationSummary(result);
                    } else {
                        $("#milestone-allocation-summary").addClass("hide").empty();
                    }
                },
                error: function () {
                    $("#milestone-allocation-summary").addClass("hide").empty();
                }
            });
        }

        $("#milestone-form").appForm({
            onSuccess: function (result) {
                $("#milestone-table").appTable({newData: result.data, dataId: result.id});
            }
        });
        setTimeout(function () {
            $("#title").focus();
        }, 200);

        setDatePicker("#due_date");
        $("#percentage").on("input", refreshMilestoneProjectedSummary);
        loadMilestoneAllocationSummary();
    });
</script>
