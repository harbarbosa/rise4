<?php
$model_info = $model_info ?? (object) array();
$source_type_dropdown = $source_type_dropdown ?? array();
$frequency_dropdown = $frequency_dropdown ?? array();

echo form_open(get_uri('licitaia/sources/save'), array('id' => 'licitaia-source-form', 'class' => 'general-form', 'role' => 'form'));
?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <?php echo form_hidden('id', (string) ($model_info->id ?? '')); ?>

        <div class="form-group">
            <div class="row">
                <label for="name" class="col-md-3"><?php echo app_lang('licitaia_source_name'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array(
                        'name' => 'name',
                        'id' => 'name',
                        'class' => 'form-control',
                        'value' => $model_info->name ?? '',
                        'required' => true,
                    )); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="source_type" class="col-md-3"><?php echo app_lang('licitaia_source_type'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('source_type', $source_type_dropdown, $model_info->source_type ?? 'pncp', "id='source_type' class='form-control'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="url" class="col-md-3"><?php echo app_lang('licitaia_source_url'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array(
                        'name' => 'url',
                        'id' => 'url',
                        'class' => 'form-control',
                        'value' => $model_info->url ?? '',
                        'placeholder' => 'https://',
                    )); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="city" class="col-md-3"><?php echo app_lang('licitaia_source_city'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array(
                        'name' => 'city',
                        'id' => 'city',
                        'class' => 'form-control',
                        'value' => $model_info->city ?? '',
                    )); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="state" class="col-md-3"><?php echo app_lang('licitaia_source_state'); ?></label>
                <div class="col-md-3">
                    <?php echo form_input(array(
                        'name' => 'state',
                        'id' => 'state',
                        'class' => 'form-control',
                        'maxlength' => 2,
                        'value' => $model_info->state ?? '',
                        'placeholder' => 'UF',
                    )); ?>
                </div>
                <label for="search_frequency" class="col-md-3"><?php echo app_lang('licitaia_source_frequency'); ?></label>
                <div class="col-md-3">
                    <?php echo form_dropdown('search_frequency', $frequency_dropdown, $model_info->search_frequency ?? 'manual', "id='search_frequency' class='form-control'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('status'); ?></label>
                <div class="col-md-9">
                    <label class="form-check">
                        <input type="checkbox" name="active" value="1" <?php echo !empty($model_info->active) ? 'checked' : ''; ?> />
                        <?php echo app_lang('licitaia_source_active'); ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="notes" class="col-md-3"><?php echo app_lang('notes'); ?></label>
                <div class="col-md-9">
                    <?php echo form_textarea(array(
                        'name' => 'notes',
                        'id' => 'notes',
                        'class' => 'form-control',
                        'rows' => 3,
                        'value' => $model_info->notes ?? '',
                    )); ?>
                </div>
            </div>
        </div>

        <?php if (!empty($model_info->id)) { ?>
            <div class="form-group">
                <div class="row">
                    <label class="col-md-3"><?php echo app_lang('licitaia_source_last_search'); ?></label>
                    <div class="col-md-9 pt5">
                        <div class="text-muted">
                            <?php echo !empty($model_info->last_search_at) ? esc(format_to_datetime($model_info->last_search_at, false)) : '-'; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#licitaia-source-form").appForm({
            onSuccess: function () {
                $("#licitaia-sources-table").appTable({reload: true});
            }
        });
    });
</script>
