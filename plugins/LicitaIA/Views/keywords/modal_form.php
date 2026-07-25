<?php
$model_info = $model_info ?? (object) array();
$type_dropdown = $type_dropdown ?? array();
echo form_open(get_uri('licitaia/keywords/save'), array('id' => 'licitaia-keyword-form', 'class' => 'general-form', 'role' => 'form'));
?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <?php echo form_hidden('id', (string) ($model_info->id ?? '')); ?>

        <div class="form-group">
            <div class="row">
                <label for="keyword" class="col-md-3"><?php echo app_lang('licitaia_keyword'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array(
                        'name' => 'keyword',
                        'id' => 'keyword',
                        'value' => (string) ($model_info->keyword ?? ''),
                        'class' => 'form-control',
                        'required' => true,
                    )); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="category" class="col-md-3"><?php echo app_lang('licitaia_category'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array(
                        'name' => 'category',
                        'id' => 'category',
                        'value' => (string) ($model_info->category ?? ''),
                        'class' => 'form-control',
                    )); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="keyword_type" class="col-md-3"><?php echo app_lang('licitaia_keyword_type'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('keyword_type', $type_dropdown, $model_info->keyword_type ?? 'include', 'class="form-control select2" id="keyword_type"'); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="weight" class="col-md-3"><?php echo app_lang('licitaia_weight'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array(
                        'type' => 'number',
                        'name' => 'weight',
                        'id' => 'weight',
                        'value' => (string) ((int) ($model_info->weight ?? 0)),
                        'class' => 'form-control',
                        'min' => '0',
                    )); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('status'); ?></label>
                <div class="col-md-9">
                    <div class="form-check">
                        <input type="checkbox" name="active" value="1" <?php echo !empty($model_info->active) ? 'checked' : ''; ?> />
                        <label class="form-check-label"><?php echo app_lang('licitaia_keyword_active'); ?></label>
                    </div>
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
        $("#licitaia-keyword-form").appForm({
            onSuccess: function () {
                $("#licitaia-keywords-table").appTable({reload: true});
            }
        });
    });
</script>
