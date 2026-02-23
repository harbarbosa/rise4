<?php
$utility = $utility ?? null;
?>

<div class="modal-body clearfix">
    <?php echo form_open(get_uri('fotovoltaico/utilities_save'), array('id' => 'fv-utility-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <input type="hidden" name="id" value="<?php echo $utility->id ?? ''; ?>" />

        <div class="form-group">
            <label for="name"><?php echo app_lang('title'); ?></label>
            <?php echo form_input(array('id' => 'name', 'name' => 'name', 'value' => $utility->name ?? '', 'class' => 'form-control', 'data-rule-required' => true, 'data-msg-required' => app_lang('field_required'))); ?>
        </div>

        <div class="form-group">
            <label for="uf"><?php echo app_lang('state'); ?></label>
            <?php echo form_input(array('id' => 'uf', 'name' => 'uf', 'value' => $utility->uf ?? '', 'class' => 'form-control')); ?>
        </div>

        <div class="form-group">
            <label for="code"><?php echo app_lang('code'); ?></label>
            <?php echo form_input(array('id' => 'code', 'name' => 'code', 'value' => $utility->code ?? '', 'class' => 'form-control')); ?>
        </div>
    <?php echo form_close(); ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="button" class="btn btn-primary" id="fv-utility-save"><?php echo app_lang('save'); ?></button>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();
        $('#fv-utility-form').appForm({
            onSuccess: function (result) {
                $('#fv-utilities-table').appTable({newData: result.data, dataId: result.id});
            }
        });

        $('#fv-utility-save').on('click', function () {
            $('#fv-utility-form').trigger('submit');
        });
    });
</script>
