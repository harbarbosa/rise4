<?php echo form_open(get_uri("projectanalizer/evolucao/save_realized/" . $project_id), array("id" => "realized-form", "class" => "general-form", "role" => "form")); ?>
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
                        "value" => date("Y-m-d"),
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
                    ), "", "class='form-control' id='cost_type' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'");
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
                        "value" => "",
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
                        "value" => "",
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
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        setDatePicker("#date");
        $("#realized-form").appForm({
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }
                location.reload();
            }
        });
    });
</script>
