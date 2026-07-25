<?php
$model_info = $model_info ?? (object) array();
$categories_dropdown = $categories_dropdown ?? array();
echo form_open(get_uri('licitaia/checklist/save'), array('id' => 'licitaia-checklist-form', 'class' => 'general-form', 'role' => 'form'));
?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <?php echo form_hidden('id', (string) ($model_info->id ?? '')); ?>

        <div class="form-group">
            <div class="row">
                <label for="item_name" class="col-md-3"><?php echo app_lang('licitaia_checklist_item'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="item_name" id="item_name" class="form-control" value="<?php echo esc($model_info->item_name ?? ''); ?>" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="category" class="col-md-3"><?php echo app_lang('licitaia_category'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('category', $categories_dropdown, $model_info->category ?? '', 'class="form-control select2" id="category"'); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3"><?php echo app_lang('licitaia_checklist_description'); ?></label>
                <div class="col-md-9">
                    <textarea name="description" id="description" class="form-control" rows="3"><?php echo esc($model_info->description ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('licitaia_required_default'); ?></label>
                <div class="col-md-9">
                    <label class="form-check">
                        <input type="checkbox" name="is_required" value="1" <?php echo !empty($model_info->is_required) ? 'checked' : ''; ?> />
                        <?php echo app_lang('licitaia_required'); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('active'); ?></label>
                <div class="col-md-9">
                    <label class="form-check">
                        <input type="checkbox" name="active" value="1" <?php echo !empty($model_info->active) ? 'checked' : ''; ?> />
                        <?php echo app_lang('yes'); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="sort" class="col-md-3"><?php echo app_lang('sort'); ?></label>
                <div class="col-md-9">
                    <input type="number" name="sort" id="sort" class="form-control" value="<?php echo esc($model_info->sort ?? 0); ?>" />
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#licitaia-checklist-form .select2").select2();
        $("#licitaia-checklist-form").appForm({
            onSuccess: function () {
                $("#licitaia-checklist-table").appTable({reload: true});
            }
        });
    });
</script>
