<?php echo form_open(get_uri("fotovoltaico/distributors/save"), array("id" => "distributor-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <input type="hidden" name="source" value="<?php echo esc($model_info->source ?: 'manual'); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('fotovoltaico_distributor_name'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "title", "name" => "title", "value" => $model_info->title, "class" => "form-control", "autofocus" => true, "data-rule-required" => true, "data-msg-required" => app_lang("field_required"))); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="legal_name" class="col-md-3"><?php echo app_lang('fotovoltaico_legal_name'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "legal_name", "name" => "legal_name", "value" => $model_info->legal_name, "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="document" class="col-md-3"><?php echo app_lang('fotovoltaico_document'); ?></label>
                <div class="col-md-3">
                    <?php echo form_input(array("id" => "document", "name" => "document", "value" => $model_info->document, "class" => "form-control")); ?>
                </div>
                <label for="aneel_code" class="col-md-3"><?php echo app_lang('fotovoltaico_aneel_code'); ?></label>
                <div class="col-md-3">
                    <?php echo form_input(array("id" => "aneel_code", "name" => "aneel_code", "value" => $model_info->aneel_code, "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="acronym" class="col-md-3"><?php echo app_lang('fotovoltaico_acronym'); ?></label>
                <div class="col-md-3">
                    <?php echo form_input(array("id" => "acronym", "name" => "acronym", "value" => $model_info->acronym, "class" => "form-control")); ?>
                </div>
                <label for="agent_type" class="col-md-3"><?php echo app_lang('fotovoltaico_agent_type'); ?></label>
                <div class="col-md-3">
                    <?php echo form_dropdown("agent_type", array(
                        'concessionaria' => app_lang('fotovoltaico_agent_type_concessionaria'),
                        'permissionaria' => app_lang('fotovoltaico_agent_type_permissionaria'),
                        'designada' => app_lang('fotovoltaico_agent_type_designada'),
                        'desconhecido' => app_lang('fotovoltaico_agent_type_desconhecido'),
                    ), $model_info->agent_type ?: 'desconhecido', "class='select2 form-control' id='agent_type'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="state_code" class="col-md-3"><?php echo app_lang('fotovoltaico_state_code'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "state_code", "name" => "state_code", "value" => $model_info->state_code, "class" => "form-control", "maxlength" => 2)); ?>
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
                <label for="sync_notes" class="col-md-3"><?php echo app_lang('fotovoltaico_sync_notes'); ?></label>
                <div class="col-md-9">
                    <?php echo form_textarea(array("id" => "sync_notes", "name" => "sync_notes", "value" => $model_info->sync_notes, "class" => "form-control", "rows" => 3)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="active" class="col-md-3"><?php echo app_lang('active'); ?></label>
                <div class="col-md-3">
                    <?php echo form_checkbox("active", "1", $model_info->active ? true : false, "id='active' class='form-check-input'"); ?>
                </div>
                <label for="show_in_registration" class="col-md-3"><?php echo app_lang('fotovoltaico_show_in_registration'); ?></label>
                <div class="col-md-3">
                    <?php echo form_checkbox("show_in_registration", "1", $model_info->show_in_registration ? true : false, "id='show_in_registration' class='form-check-input'"); ?>
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
        $("#distributor-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    $("#distributors-table").appTable({newData: result.data, dataId: result.id});
                }
            }
        });

        $("#distributor-form .select2").select2();
    });
</script>
