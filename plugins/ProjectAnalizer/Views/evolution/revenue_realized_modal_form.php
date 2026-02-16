<?php
$id = $model_info ? $model_info->id : 0;
echo form_open(get_uri("projectanalizer/revenue/save_realized"), array("id" => "revenue-realized-form", "class" => "general-form", "role" => "form"));
?>

<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="realized_date" class=" col-md-4"><?php echo app_lang("realized_date"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "realized_date",
                        "name" => "realized_date",
                        "class" => "form-control",
                        "value" => $model_info ? $model_info->realized_date : "",
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
                <label for="realized_value" class=" col-md-4"><?php echo app_lang("realized_value"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "realized_value",
                        "name" => "realized_value",
                        "class" => "form-control",
                        "value" => $model_info ? $model_info->realized_value : "",
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="planned_id" class=" col-md-4"><?php echo app_lang("planned_revenue"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_dropdown("planned_id", $planned_dropdown, $model_info ? $model_info->planned_id : "", "class='form-control'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="document_ref" class=" col-md-4"><?php echo app_lang("document_ref"); ?></label>
                <div class=" col-md-8">
                    <?php
                    echo form_input(array(
                        "id" => "document_ref",
                        "name" => "document_ref",
                        "class" => "form-control",
                        "value" => $model_info ? $model_info->document_ref : ""
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
        setDatePicker("#realized_date");
        $("#revenue-realized-form").appForm({
            onSuccess: function () {
                location.reload();
            }
        });
    });
</script>
