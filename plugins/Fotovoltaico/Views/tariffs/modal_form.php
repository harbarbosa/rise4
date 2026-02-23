<?php
$tariff = $tariff ?? null;
?>

<div class="modal-body clearfix">
    <?php echo form_open(get_uri('fotovoltaico/tariffs_save'), array('id' => 'fv-tariff-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <input type="hidden" name="id" value="<?php echo $tariff->id ?? ''; ?>" />
        <input type="hidden" name="utility_id" value="<?php echo $utility_id ?? ($tariff->utility_id ?? 0); ?>" />

        <div class="form-group">
            <label for="group_type"><?php echo app_lang('group_type'); ?></label>
            <?php echo form_dropdown('group_type', array('A' => 'A', 'B' => 'B'), $tariff->group_type ?? 'B', "class='select2' id='group_type'"); ?>
        </div>

        <div class="form-group">
            <label for="modality"><?php echo app_lang('modality'); ?></label>
            <?php echo form_input(array('id' => 'modality', 'name' => 'modality', 'value' => $tariff->modality ?? '', 'class' => 'form-control')); ?>
        </div>

        <div class="form-group">
            <label for="te_value"><?php echo app_lang('fv_tariff_te'); ?></label>
            <?php echo form_input(array('id' => 'te_value', 'name' => 'te_value', 'value' => $tariff->te_value ?? ($tariff->te ?? ''), 'class' => 'form-control')); ?>
        </div>

        <div class="form-group">
            <label for="tusd_value"><?php echo app_lang('fv_tariff_tusd'); ?></label>
            <?php echo form_input(array('id' => 'tusd_value', 'name' => 'tusd_value', 'value' => $tariff->tusd_value ?? ($tariff->tusd ?? ''), 'class' => 'form-control')); ?>
        </div>

        <div class="form-group">
            <label for="flags_value"><?php echo app_lang('fv_tariff_flags'); ?></label>
            <?php echo form_input(array('id' => 'flags_value', 'name' => 'flags_value', 'value' => $tariff->flags_value ?? '', 'class' => 'form-control')); ?>
        </div>

        <div class="form-group">
            <label for="valid_from"><?php echo app_lang('valid_from'); ?></label>
            <?php echo form_input(array('id' => 'valid_from', 'name' => 'valid_from', 'value' => $tariff->valid_from ?? '', 'class' => 'form-control', 'type' => 'date')); ?>
        </div>

        <div class="form-group">
            <label for="valid_to"><?php echo app_lang('valid_to'); ?></label>
            <?php echo form_input(array('id' => 'valid_to', 'name' => 'valid_to', 'value' => $tariff->valid_to ?? '', 'class' => 'form-control', 'type' => 'date')); ?>
        </div>

        <div class="form-group">
            <label for="other"><?php echo app_lang('fv_tariff_other'); ?></label>
            <?php echo form_textarea(array('id' => 'other', 'name' => 'other', 'value' => $tariff->other ?? '', 'class' => 'form-control', 'rows' => 3)); ?>
        </div>
    <?php echo form_close(); ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="button" class="btn btn-primary" id="fv-tariff-save"><?php echo app_lang('save'); ?></button>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();
        $('#fv-tariff-form').appForm({
            onSuccess: function (result) {
                $('#fv-tariffs-table').appTable({newData: result.data, dataId: result.id});
            }
        });

        $('#fv-tariff-save').on('click', function () {
            $('#fv-tariff-form').trigger('submit');
        });
    });
</script>
