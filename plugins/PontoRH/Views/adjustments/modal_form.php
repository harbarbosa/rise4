<?php
$model_info = $model_info ?? (object) array();
$team_members_dropdown = $team_members_dropdown ?? array();
$adjustment_type_dropdown = $adjustment_type_dropdown ?? array();

echo form_open(get_uri('pontorh/ajustes/save'), array('id' => 'pontorh-adjustment-form', 'class' => 'general-form', 'role' => 'form'));
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
                <label for="request_date" class="col-md-3"><?php echo app_lang('pontorh_work_date'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="request_date" id="request_date" class="form-control datepicker" value="<?php echo esc($model_info->request_date ?? date('Y-m-d')); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="requested_time" class="col-md-3"><?php echo app_lang('pontorh_adjustment_time'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="requested_time" id="requested_time" class="form-control timepicker" value="<?php echo esc(isset($model_info->requested_time) ? pontorh_extract_time($model_info->requested_time) : get_my_local_time('H:i')); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="adjustment_type" class="col-md-3"><?php echo app_lang('pontorh_type'); ?></label>
                <div class="col-md-9">
                    <select name="adjustment_type" id="adjustment_type" class="form-control select2 w100p" required>
                        <option value="">-</option>
                        <?php foreach ($adjustment_type_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>" <?php echo ((string) ($model_info->adjustment_type ?? 'in') === (string) $value) ? 'selected' : ''; ?>>
                                <?php echo esc($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="reason" class="col-md-3"><?php echo app_lang('pontorh_adjustment_justification'); ?></label>
                <div class="col-md-9">
                    <textarea name="reason" id="reason" class="form-control" rows="4" required><?php echo esc($model_info->reason ?? ''); ?></textarea>
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
        $("#pontorh-adjustment-form .select2").select2();
        setDatePicker("#request_date");
        setTimePicker("#requested_time");

        $("#pontorh-adjustment-form").appForm({
            onSuccess: function () {
                $("#pontorh-adjustments-table").appTable({reload: true});
            }
        });
    });
</script>
