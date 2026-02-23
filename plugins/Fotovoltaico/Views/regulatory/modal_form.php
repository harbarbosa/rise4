<?php echo form_open(get_uri('fotovoltaico/regulatory_save'), array("id" => "fv-regulatory-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo $item->id ?? ''; ?>" />

    <div class="form-group">
        <label><?php echo app_lang('name'); ?></label>
        <input type="text" name="name" class="form-control" value="<?php echo esc($item->name ?? ''); ?>" required />
    </div>

    <div class="form-group">
        <label><?php echo app_lang('description'); ?></label>
        <textarea name="description" class="form-control"><?php echo esc($item->description ?? ''); ?></textarea>
    </div>

    <div class="form-group">
        <label><?php echo app_lang('fv_rules_json'); ?></label>
        <textarea name="rules_json" class="form-control" rows="8"><?php echo esc($item->rules_json ?? ''); ?></textarea>
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="is_active" value="1" <?php echo !isset($item->is_active) || $item->is_active ? 'checked' : ''; ?> />
            <?php echo app_lang('active'); ?>
        </label>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script>
    $(function () {
        $("#fv-regulatory-form").appForm({
            onSuccess: function () {
                $("#fv-regulatory-table").appTable({newData: null});
            }
        });
    });
</script>
