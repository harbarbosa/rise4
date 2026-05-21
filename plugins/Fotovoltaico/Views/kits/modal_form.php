<?php
$power_kwp = is_numeric($model_info->power_kwp ?? null) ? (float) $model_info->power_kwp : 0;
?>
<?php echo form_open(get_uri("fotovoltaico/kits/save"), array("id" => "kit-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('fotovoltaico_kit_name'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "title", "name" => "title", "value" => $model_info->title, "class" => "form-control", "autofocus" => true, "data-rule-required" => true, "data-msg-required" => app_lang("field_required"))); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="code" class="col-md-3"><?php echo app_lang('fotovoltaico_kit_code'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "code", "name" => "code", "value" => $model_info->code, "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="category_id" class="col-md-3"><?php echo app_lang('fotovoltaico_product_category'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("category_id", $categories_dropdown, $model_info->category_id, "class='select2 form-control' id='category_id'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="distributor_id" class="col-md-3"><?php echo app_lang('fotovoltaico_product_distributor'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("distributor_id", $distributors_dropdown, $model_info->distributor_id, "class='select2 form-control' id='distributor_id'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="power_kwp" class="col-md-3"><?php echo app_lang('fotovoltaico_kit_power_kwp'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "power_kwp", "name" => "power_kwp", "value" => to_decimal_format($power_kwp), "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="status" class="col-md-3"><?php echo app_lang('status'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("status", $status_dropdown, $model_info->status ?: 'draft', "class='select2 form-control' id='status'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label>
                <div class="col-md-9">
                    <?php echo form_textarea(array("id" => "description", "name" => "description", "value" => $model_info->description, "class" => "form-control", "rows" => 3)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="notes" class="col-md-3"><?php echo app_lang('fotovoltaico_notes'); ?></label>
                <div class="col-md-9">
                    <?php echo form_textarea(array("id" => "notes", "name" => "notes", "value" => $model_info->notes, "class" => "form-control", "rows" => 4)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="active" class="col-md-3"><?php echo app_lang('active'); ?></label>
                <div class="col-md-9">
                    <?php echo form_checkbox("active", "1", $model_info->active ? true : false, "id='active' class='form-check-input'"); ?>
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
        $("#kit-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    $("#kits-table").appTable({newData: result.data, dataId: result.id});
                }
            }
        });

        $("#kit-form .select2").select2();
        setTimeout(function () {
            $("#title").focus();
        }, 200);
    });
</script>
