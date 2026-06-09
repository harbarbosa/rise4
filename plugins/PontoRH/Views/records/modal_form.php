<?php
$model_info = $model_info ?? (object) array();
$team_members_dropdown = $team_members_dropdown ?? array();
$locations_dropdown = $locations_dropdown ?? array();
$punch_type_dropdown = $punch_type_dropdown ?? array();
$status_dropdown = $status_dropdown ?? array();

echo form_open(get_uri('pontorh/registros/save'), array('id' => 'pontorh-record-form', 'class' => 'general-form', 'role' => 'form'));
?>
<div class="modal-body clearfix">
    <?php echo form_hidden('id', (string) ($model_info->id ?? '')); ?>
    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="team_member_id" class="col-md-3"><?php echo app_lang('pontorh_employee'); ?></label>
                <div class="col-md-9">
                    <select name="team_member_id" id="team_member_id" class="form-control select2 w100p" required>
                        <option value="">-</option>
                        <?php foreach ($team_members_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>" <?php echo ((string) ($model_info->team_member_id ?? '') === (string) $value) ? 'selected' : ''; ?>>
                                <?php echo esc($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="date" class="col-md-3"><?php echo app_lang('pontorh_work_date'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="date" id="date" class="form-control datepicker" value="<?php echo esc($model_info->date ?? date('Y-m-d')); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="punch_time" class="col-md-3"><?php echo app_lang('time'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="punch_time" id="punch_time" class="form-control timepicker" value="<?php echo esc(!empty($model_info->punch_time) ? pontorh_extract_time($model_info->punch_time) : get_my_local_time('H:i')); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="punch_type" class="col-md-3"><?php echo app_lang('pontorh_type'); ?></label>
                <div class="col-md-9">
                    <select name="punch_type" id="punch_type" class="form-control select2 w100p">
                        <option value=""><?php echo app_lang('pontorh_automatic'); ?></option>
                        <?php foreach ($punch_type_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>" <?php echo ((string) ($model_info->punch_type ?? '') === (string) $value) ? 'selected' : ''; ?>>
                                <?php echo esc($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="location_id" class="col-md-3"><?php echo app_lang('pontorh_location'); ?></label>
                <div class="col-md-9">
                    <select name="location_id" id="location_id" class="form-control select2 w100p">
                        <option value="">-</option>
                        <?php foreach ($locations_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>" <?php echo ((string) ($model_info->location_id ?? '') === (string) $value) ? 'selected' : ''; ?>>
                                <?php echo esc($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="status" class="col-md-3"><?php echo app_lang('pontorh_status'); ?></label>
                <div class="col-md-9">
                    <select name="status" id="status" class="form-control select2 w100p">
                        <option value="">-</option>
                        <?php foreach ($status_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>" <?php echo ((string) ($model_info->status ?? 'pending') === (string) $value) ? 'selected' : ''; ?>>
                                <?php echo esc($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="source" class="col-md-3"><?php echo app_lang('pontorh_source'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="source" id="source" class="form-control" value="<?php echo esc($model_info->source ?? 'manual'); ?>" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="latitude" class="col-md-3">Latitude</label>
                <div class="col-md-9">
                    <input type="text" name="latitude" id="latitude" class="form-control" value="<?php echo esc((string) ($model_info->latitude ?? '0')); ?>" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="longitude" class="col-md-3">Longitude</label>
                <div class="col-md-9">
                    <input type="text" name="longitude" id="longitude" class="form-control" value="<?php echo esc((string) ($model_info->longitude ?? '0')); ?>" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="notes" class="col-md-3"><?php echo app_lang('notes'); ?></label>
                <div class="col-md-9">
                    <textarea name="notes" id="notes" class="form-control" rows="3"><?php echo esc($model_info->notes ?? ''); ?></textarea>
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
        $("#pontorh-record-form .select2").select2();
        setDatePicker("#date");
        setTimePicker("#punch_time");

        $("#pontorh-record-form").appForm({
            onSuccess: function () {
                $("#pontorh-records-table").appTable({reload: true});
            }
        });
    });
</script>
