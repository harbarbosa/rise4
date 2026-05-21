<?php
$te = is_numeric($model_info->te ?? null) ? (float) $model_info->te : 0;
$tusd = is_numeric($model_info->tusd ?? null) ? (float) $model_info->tusd : 0;
$flag_value = is_numeric($model_info->flag_value ?? null) ? (float) $model_info->flag_value : 0;
?>
<?php echo form_open(get_uri("fotovoltaico/tariffs/save"), array("id" => "tariff-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <input type="hidden" name="source" value="<?php echo esc($model_info->source ?: 'manual'); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="distributor_id" class="col-md-3"><?php echo app_lang('fotovoltaico_distributor_name'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("distributor_id", $distributors_dropdown, $model_info->distributor_id, "class='select2 form-control' id='distributor_id' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="modality" class="col-md-3"><?php echo app_lang('fotovoltaico_tariff_modality'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("modality", $modalities_dropdown, $model_info->modality, "class='select2 form-control' id='modality' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="subgroup" class="col-md-3"><?php echo app_lang('fotovoltaico_tariff_subgroup'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("subgroup", $subgroups_dropdown, $model_info->subgroup, "class='select2 form-control' id='subgroup' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'"); ?>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="tariff_class" class="form-label"><?php echo app_lang('fotovoltaico_tariff_class'); ?></label>
                <?php echo form_input(array("id" => "tariff_class", "name" => "tariff_class", "value" => $model_info->tariff_class, "class" => "form-control")); ?>
            </div>
            <div class="col-md-6">
                <label for="tariff_subclass" class="form-label"><?php echo app_lang('fotovoltaico_tariff_subclass'); ?></label>
                <?php echo form_input(array("id" => "tariff_subclass", "name" => "tariff_subclass", "value" => $model_info->tariff_subclass, "class" => "form-control")); ?>
            </div>
            <div class="col-md-4">
                <label for="group_name" class="form-label"><?php echo app_lang('fotovoltaico_tariff_group'); ?></label>
                <?php echo form_input(array("id" => "group_name", "name" => "group_name", "value" => $model_info->group_name, "class" => "form-control")); ?>
            </div>
            <div class="col-md-4">
                <label for="time_slot" class="form-label"><?php echo app_lang('fotovoltaico_tariff_time_slot'); ?></label>
                <?php echo form_input(array("id" => "time_slot", "name" => "time_slot", "value" => $model_info->time_slot, "class" => "form-control")); ?>
            </div>
            <div class="col-md-4">
                <label for="unit" class="form-label"><?php echo app_lang('fotovoltaico_tariff_unit'); ?></label>
                <?php echo form_input(array("id" => "unit", "name" => "unit", "value" => $model_info->unit, "class" => "form-control")); ?>
            </div>
            <div class="col-md-8">
                <label for="resolution" class="form-label"><?php echo app_lang('fotovoltaico_tariff_resolution'); ?></label>
                <?php echo form_input(array("id" => "resolution", "name" => "resolution", "value" => $model_info->resolution, "class" => "form-control")); ?>
            </div>
            <div class="col-md-4">
                <label for="tariff_base" class="form-label"><?php echo app_lang('fotovoltaico_tariff_base'); ?></label>
                <?php echo form_input(array("id" => "tariff_base", "name" => "tariff_base", "value" => $model_info->tariff_base, "class" => "form-control")); ?>
            </div>
            <div class="col-md-6">
                <label for="te" class="form-label"><?php echo app_lang('fotovoltaico_tariff_te'); ?></label>
                <?php echo form_input(array("id" => "te", "name" => "te", "value" => to_decimal_format($te), "class" => "form-control")); ?>
            </div>
            <div class="col-md-6">
                <label for="tusd" class="form-label"><?php echo app_lang('fotovoltaico_tariff_tusd'); ?></label>
                <?php echo form_input(array("id" => "tusd", "name" => "tusd", "value" => to_decimal_format($tusd), "class" => "form-control")); ?>
            </div>
            <div class="col-md-6">
                <label for="flag_name" class="form-label"><?php echo app_lang('fotovoltaico_tariff_flag'); ?></label>
                <?php echo form_dropdown("flag_name", $flags_dropdown, $model_info->flag_name, "class='select2 form-control' id='flag_name'"); ?>
            </div>
            <div class="col-md-6">
                <label for="flag_value" class="form-label"><?php echo app_lang('fotovoltaico_tariff_flag_value'); ?></label>
                <?php echo form_input(array("id" => "flag_value", "name" => "flag_value", "value" => to_decimal_format($flag_value), "class" => "form-control")); ?>
            </div>
            <div class="col-md-6">
                <label for="valid_from" class="form-label"><?php echo app_lang('fotovoltaico_valid_from'); ?></label>
                <?php echo form_input(array("id" => "valid_from", "name" => "valid_from", "value" => $model_info->valid_from, "class" => "form-control", "type" => "date", "data-rule-required" => true, "data-msg-required" => app_lang("field_required"))); ?>
            </div>
            <div class="col-md-6">
                <label for="valid_to" class="form-label"><?php echo app_lang('fotovoltaico_valid_to'); ?></label>
                <?php echo form_input(array("id" => "valid_to", "name" => "valid_to", "value" => $model_info->valid_to, "class" => "form-control", "type" => "date")); ?>
            </div>
            <div class="col-md-12">
                <label for="tariff_detail" class="form-label"><?php echo app_lang('fotovoltaico_tariff_detail'); ?></label>
                <?php echo form_input(array("id" => "tariff_detail", "name" => "tariff_detail", "value" => $model_info->tariff_detail, "class" => "form-control")); ?>
            </div>
            <div class="col-md-12">
                <label for="notes" class="form-label"><?php echo app_lang('fotovoltaico_notes'); ?></label>
                <?php echo form_textarea(array("id" => "notes", "name" => "notes", "value" => $model_info->notes, "class" => "form-control", "rows" => 4)); ?>
            </div>
            <div class="col-md-12">
                <label for="sync_notes" class="form-label"><?php echo app_lang('fotovoltaico_sync_notes'); ?></label>
                <?php echo form_textarea(array("id" => "sync_notes", "name" => "sync_notes", "value" => $model_info->sync_notes, "class" => "form-control", "rows" => 3)); ?>
            </div>
            <div class="col-md-12">
                <label for="active" class="form-label"><?php echo app_lang('active'); ?></label>
                <div><?php echo form_checkbox("active", "1", $model_info->active ? true : false, "id='active' class='form-check-input'"); ?></div>
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
        $("#tariff-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    $("#tariffs-table").appTable({newData: result.data, dataId: result.id});
                }
            }
        });

        $("#tariff-form .select2").select2();
    });
</script>
