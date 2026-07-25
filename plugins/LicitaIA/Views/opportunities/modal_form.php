<?php
$model_info = $model_info ?? (object) array();
$sources_dropdown = $sources_dropdown ?? array();
$status_dropdown = $status_dropdown ?? array();
$responsible_dropdown = $responsible_dropdown ?? array();

echo form_open(get_uri('licitaia/opportunities/save'), array('id' => 'licitaia-opportunity-form', 'class' => 'general-form', 'role' => 'form'));
?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <?php echo form_hidden('id', (string) ($model_info->id ?? '')); ?>

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('licitaia_opportunity'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="title" id="title" class="form-control" value="<?php echo esc($model_info->title ?? ''); ?>" required />
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="public_body"><?php echo app_lang('licitaia_public_body'); ?></label>
                <input type="text" name="public_body" id="public_body" class="form-control" value="<?php echo esc($model_info->public_body ?? ''); ?>" />
            </div>
            <div class="col-md-6 form-group">
                <label for="edital_number"><?php echo app_lang('licitaia_edital_number'); ?></label>
                <input type="text" name="edital_number" id="edital_number" class="form-control" value="<?php echo esc($model_info->edital_number ?? ''); ?>" />
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="process_number"><?php echo app_lang('licitaia_process_number'); ?></label>
                <input type="text" name="process_number" id="process_number" class="form-control" value="<?php echo esc($model_info->process_number ?? ''); ?>" />
            </div>
            <div class="col-md-6 form-group">
                <label for="modality"><?php echo app_lang('licitaia_modality'); ?></label>
                <input type="text" name="modality" id="modality" class="form-control" value="<?php echo esc($model_info->modality ?? ''); ?>" />
            </div>
        </div>

        <div class="form-group">
            <label for="object"><?php echo app_lang('licitaia_object'); ?></label>
            <textarea name="object" id="object" class="form-control" rows="4"><?php echo esc($model_info->object ?? ''); ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                <label for="source_id"><?php echo app_lang('licitaia_source'); ?></label>
                <?php echo form_dropdown('source_id', $sources_dropdown, $model_info->source_id ?? '', 'class="form-control select2" id="source_id"'); ?>
            </div>
            <div class="col-md-4 form-group">
                <label for="responsible_user_id"><?php echo app_lang('licitaia_responsible'); ?></label>
                <?php echo form_dropdown('responsible_user_id', $responsible_dropdown, $model_info->responsible_user_id ?? '', 'class="form-control select2" id="responsible_user_id"'); ?>
            </div>
            <div class="col-md-4 form-group">
                <label for="status"><?php echo app_lang('licitaia_status'); ?></label>
                <?php echo form_dropdown('status', $status_dropdown, $model_info->status ?? 'new', 'class="form-control select2" id="status"'); ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                <label for="publication_date"><?php echo app_lang('licitaia_publication_date'); ?></label>
                <input type="text" name="publication_date" id="publication_date" class="form-control datepicker" value="<?php echo esc(!empty($model_info->publication_date) ? format_to_date($model_info->publication_date, false) : ''); ?>" />
            </div>
            <div class="col-md-4 form-group">
                <label for="opening_date"><?php echo app_lang('licitaia_opening_date'); ?></label>
                <input type="text" name="opening_date" id="opening_date" class="form-control datepicker" value="<?php echo esc(!empty($model_info->opening_date) ? format_to_date($model_info->opening_date, false) : ''); ?>" />
            </div>
            <div class="col-md-4 form-group">
                <label for="submission_deadline"><?php echo app_lang('licitaia_deadline'); ?></label>
                <input type="text" name="submission_deadline" id="submission_deadline" class="form-control datepicker" value="<?php echo esc(!empty($model_info->submission_deadline) ? format_to_date($model_info->submission_deadline, false) : ''); ?>" />
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                <label for="estimated_value"><?php echo app_lang('licitaia_estimated_value'); ?></label>
                <input type="text" name="estimated_value" id="estimated_value" class="form-control" value="<?php echo esc($model_info->estimated_value ?? ''); ?>" />
            </div>
            <div class="col-md-4 form-group">
                <label for="city"><?php echo app_lang('city'); ?></label>
                <input type="text" name="city" id="city" class="form-control" value="<?php echo esc($model_info->city ?? ''); ?>" />
            </div>
            <div class="col-md-4 form-group">
                <label for="state"><?php echo app_lang('state'); ?></label>
                <input type="text" name="state" id="state" class="form-control" maxlength="2" value="<?php echo esc($model_info->state ?? ''); ?>" />
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="original_link"><?php echo app_lang('licitaia_original_link'); ?></label>
                <input type="text" name="original_link" id="original_link" class="form-control" value="<?php echo esc($model_info->original_link ?? ($model_info->document_url ?? '')); ?>" />
            </div>
            <div class="col-md-6 form-group">
                <label for="jurisdiction"><?php echo app_lang('licitaia_jurisdiction'); ?></label>
                <input type="text" name="jurisdiction" id="jurisdiction" class="form-control" value="<?php echo esc($model_info->jurisdiction ?? ''); ?>" />
            </div>
        </div>

        <div class="form-group">
            <label for="description"><?php echo app_lang('description'); ?></label>
            <textarea name="description" id="description" class="form-control" rows="4"><?php echo esc($model_info->description ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="notes"><?php echo app_lang('notes'); ?></label>
            <textarea name="notes" id="notes" class="form-control" rows="3"><?php echo esc($model_info->notes ?? ''); ?></textarea>
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
        $("#licitaia-opportunity-form .select2").select2();
        $("#licitaia-opportunity-form .datepicker").datepicker({
            format: getJsDateFormat(),
            autoclose: true,
            clearBtn: true
        });
        $("#licitaia-opportunity-form").appForm({
            onSuccess: function () {
                $("#licitaia-opportunities-table").appTable({reload: true});
            }
        });
    });
</script>
