<?php
$model_info = $model_info ?? (object) array(
    'id' => 0,
    'name' => '',
    'portal_url' => '',
    'contact_name' => '',
    'contact_email' => '',
    'contact_phone' => '',
    'notes' => '',
    'is_active' => 1
);
?>

<?php echo form_open(get_uri('ged/suppliers/save'), array('id' => 'ged-supplier-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id); ?>" />

    <div class="form-group">
        <label for="name" class="form-label"><?php echo app_lang('ged_field_name'); ?> *</label>
        <input type="text" id="name" name="name" value="<?php echo esc($model_info->name); ?>" class="form-control" required />
    </div>

    <div class="form-group">
        <label for="portal_url" class="form-label"><?php echo app_lang('ged_field_portal_url'); ?></label>
        <input type="url" id="portal_url" name="portal_url" value="<?php echo esc($model_info->portal_url); ?>" class="form-control" placeholder="https://exemplo.com/portal" />
    </div>

    <div class="form-group">
        <label for="contact_name" class="form-label"><?php echo app_lang('ged_field_contact_name'); ?></label>
        <input type="text" id="contact_name" name="contact_name" value="<?php echo esc($model_info->contact_name); ?>" class="form-control" />
    </div>

    <div class="form-group">
        <label for="contact_email" class="form-label"><?php echo app_lang('ged_field_contact_email'); ?></label>
        <input type="email" id="contact_email" name="contact_email" value="<?php echo esc($model_info->contact_email); ?>" class="form-control" />
    </div>

    <div class="form-group">
        <label for="contact_phone" class="form-label"><?php echo app_lang('ged_field_contact_phone'); ?></label>
        <input type="text" id="contact_phone" name="contact_phone" value="<?php echo esc($model_info->contact_phone); ?>" class="form-control" />
    </div>

    <div class="form-group">
        <label for="notes" class="form-label"><?php echo app_lang('ged_field_notes'); ?></label>
        <textarea id="notes" name="notes" class="form-control" rows="4"><?php echo esc($model_info->notes); ?></textarea>
    </div>

    <div class="form-group">
        <div class="form-check">
            <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "id='is_active' class='form-check-input'"); ?>
            <label for="is_active" class="form-check-label"><?php echo app_lang('ged_field_active'); ?></label>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary btn-sm"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#ged-supplier-form .select2").select2();

        $("#ged-supplier-form").appForm({
            onSuccess: function (result) {
                $("#ged-suppliers-table").appTable({newData: result.data, dataId: result.id});
            }
        });
    });
</script>
