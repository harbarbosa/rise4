<?php echo form_open(get_uri('fotovoltaico/kits_save'), array("id" => "fv-kit-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo $kit->id ?? ''; ?>" />

    <div class="form-group">
        <label for="name"><?php echo app_lang('name'); ?></label>
        <input type="text" name="name" class="form-control" value="<?php echo esc($kit->name ?? ''); ?>" required />
    </div>

    <div class="form-group">
        <label for="description"><?php echo app_lang('description'); ?></label>
        <textarea name="description" class="form-control"><?php echo esc($kit->description ?? ''); ?></textarea>
    </div>

    <div class="form-group">
        <label for="default_losses_percent"><?php echo app_lang('fv_default_losses'); ?></label>
        <input type="text" name="default_losses_percent" class="form-control" value="<?php echo esc($kit->default_losses_percent ?? 14); ?>" />
    </div>

    <div class="form-group">
        <label for="default_markup_percent"><?php echo app_lang('fv_default_markup'); ?></label>
        <input type="text" name="default_markup_percent" class="form-control" value="<?php echo esc($kit->default_markup_percent ?? 0); ?>" />
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="is_active" value="1" <?php echo !isset($kit->is_active) || $kit->is_active ? 'checked' : ''; ?> />
            <?php echo app_lang('active'); ?>
        </label>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#fv-kit-form").appForm({
            onSuccess: function (result) {
                $("#fv-kits-table").appTable({newData: result.data, dataId: result.id});
            }
        });
    });
</script>
