<?php
$id = $model_info ? $model_info->id : 0;
echo form_open(get_uri("projectanalizer/revenue/save_planned"), array("id" => "revenue-planned-form", "class" => "general-form", "role" => "form"));
?>

<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="title" class=" col-md-4"><?php echo app_lang("revenue_title"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "class" => "form-control",
                        "value" => $model_info ? $model_info->title : "",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="planned_date" class=" col-md-4"><?php echo app_lang("planned_date"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "planned_date",
                        "name" => "planned_date",
                        "class" => "form-control",
                        "value" => $model_info ? $model_info->planned_date : "",
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
                <label for="planned_value" class=" col-md-4"><?php echo app_lang("planned_value"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "planned_value",
                        "name" => "planned_value",
                        "class" => "form-control",
                        "value" => $model_info ? $model_info->planned_value : "",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="percent_of_contract" class=" col-md-4"><?php echo app_lang("percent_of_contract"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "percent_of_contract",
                        "name" => "percent_of_contract",
                        "class" => "form-control",
                        "value" => $model_info ? $model_info->percent_of_contract : ""
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="notes" class=" col-md-4"><?php echo app_lang("notes"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_textarea(array(
                        "id" => "notes",
                        "name" => "notes",
                        "class" => "form-control",
                        "value" => $model_info ? $model_info->notes : ""
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang("close"); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang("save"); ?></button>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        setDatePicker("#planned_date");
        $("#revenue-planned-form").appForm({
            onSuccess: function () {
                location.reload();
            }
        });
    });
</script>
