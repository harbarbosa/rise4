<?php
$model_info = $model_info ?? (object) array();
$team_members_dropdown = $team_members_dropdown ?? array();
$schedule_type_dropdown = $schedule_type_dropdown ?? array();
$selected_team_member_ids = $selected_team_member_ids ?? array();

echo form_open(get_uri('pontorh/jornadas/save'), array('id' => 'pontorh-shift-form', 'class' => 'general-form', 'role' => 'form'));
?>
<div class="modal-body clearfix">
    <?php echo form_hidden('id', (string) ($model_info->id ?? '')); ?>
    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="name" class="col-md-3"><?php echo app_lang('name'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo esc($model_info->name ?? ''); ?>" autocomplete="off" required autofocus />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="team_member_ids" class="col-md-3"><?php echo app_lang('pontorh_schedule_team_members'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('team_member_ids[]', $team_members_dropdown, $selected_team_member_ids, 'class="form-control select2 w100p" id="team_member_ids" multiple required data-placeholder="' . app_lang('pontorh_schedule_team_members') . '"'); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="schedule_type" class="col-md-3"><?php echo app_lang('pontorh_schedule_type'); ?></label>
                <div class="col-md-9">
                    <select name="schedule_type" id="schedule_type" class="form-control select2 w100p" required>
                        <option value="">-</option>
                        <?php foreach ($schedule_type_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>" <?php echo ((string) ($model_info->schedule_type ?? 'comercial') === (string) $value) ? 'selected' : ''; ?>>
                                <?php echo esc($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="start_time" class="col-md-3"><?php echo app_lang('pontorh_check_in'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="start_time" id="start_time" class="form-control timepicker" value="<?php echo esc($model_info->start_time ?? ''); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="end_time" class="col-md-3"><?php echo app_lang('pontorh_check_out'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="end_time" id="end_time" class="form-control timepicker" value="<?php echo esc($model_info->end_time ?? ''); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="break_minutes" class="col-md-3"><?php echo app_lang('pontorh_break_minutes'); ?></label>
                <div class="col-md-9">
                    <input type="number" name="break_minutes" id="break_minutes" class="form-control" value="<?php echo esc((string) ($model_info->break_minutes ?? 0)); ?>" min="0" step="1" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="tolerance_minutes" class="col-md-3"><?php echo app_lang('pontorh_tolerance_minutes'); ?></label>
                <div class="col-md-9">
                    <input type="number" name="tolerance_minutes" id="tolerance_minutes" class="form-control" value="<?php echo esc((string) ($model_info->tolerance_minutes ?? 0)); ?>" min="0" step="1" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="extra_tolerance_minutes" class="col-md-3"><?php echo app_lang('pontorh_extra_tolerance_minutes'); ?></label>
                <div class="col-md-9">
                    <input type="number" name="extra_tolerance_minutes" id="extra_tolerance_minutes" class="form-control" value="<?php echo esc((string) ($model_info->extra_tolerance_minutes ?? 0)); ?>" min="0" step="1" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="weekly_hours" class="col-md-3"><?php echo app_lang('pontorh_weekly_hours'); ?></label>
                <div class="col-md-9">
                    <input type="number" name="weekly_hours" id="weekly_hours" class="form-control" value="<?php echo esc((string) ($model_info->weekly_hours ?? '')); ?>" min="0" step="0.01" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="bank_hours" class="col-md-3"><?php echo app_lang('pontorh_bank_hours'); ?></label>
                <div class="col-md-9">
                    <input type="number" name="bank_hours" id="bank_hours" class="form-control" value="<?php echo esc((string) ($model_info->bank_hours ?? 0)); ?>" step="0.01" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check mt10">
                        <?php echo form_checkbox('active', '1', !empty($model_info->active), "class='form-check-input' id='pontorh-shift-active'"); ?>
                        <label for="pontorh-shift-active" class="form-check-label"><?php echo app_lang('active'); ?></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3"><?php echo app_lang('description'); ?></label>
                <div class="col-md-9">
                    <textarea name="description" id="description" class="form-control" rows="3"><?php echo esc($model_info->description ?? ''); ?></textarea>
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
        $("#pontorh-shift-form .select2").select2();
        setTimePicker("#start_time, #end_time");

        $("#pontorh-shift-form").appForm({
            onSuccess: function () {
                $("#pontorh-shifts-table").appTable({reload: true});
            }
        });
    });
</script>
