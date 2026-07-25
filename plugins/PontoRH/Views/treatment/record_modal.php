<?php
$case = $case ?? (object) array();
$record = $record ?? (object) array();
$mode = $mode ?? 'edit';
$team_members_dropdown = $team_members_dropdown ?? array();
$locations_dropdown = $locations_dropdown ?? array();
$punch_type_dropdown = $punch_type_dropdown ?? array();
$status_dropdown = $status_dropdown ?? array();

$is_delete = $mode === 'delete';
$selected_team_member_id = (string) ($record->team_member_id ?? '');
$selected_work_date = !empty($record->date) ? $record->date : get_my_local_time('Y-m-d');
$selected_punch_time = !empty($record->punch_time) ? pontorh_extract_time($record->punch_time) : get_my_local_time('H:i');
$selected_punch_type = (string) ($record->punch_type ?? '');
$selected_location_id = (string) ($record->location_id ?? '');
$selected_status = (string) ($record->status ?? 'pending');
$selected_source = (string) ($record->source ?? 'manual');
$selected_latitude = (string) ($record->latitude ?? '0');
$selected_longitude = (string) ($record->longitude ?? '0');
$selected_notes = (string) ($record->notes ?? '');
?>

<?php echo form_open(get_uri('pontorh/tratamento/record_action'), array('id' => 'pontorh-treatment-record-action-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <?php echo form_hidden('case_id', (string) (int) ($case->id ?? 0)); ?>
        <?php echo form_hidden('record_id', (string) (int) ($record->id ?? 0)); ?>
        <?php echo form_hidden('action_type', $is_delete ? 'delete' : 'edit'); ?>

        <div class="alert alert-info">
            <div class="fw-bold"><?php echo esc($record->team_member_name ?? '-'); ?></div>
            <div><?php echo esc(format_to_date($record->date ?? '', false)); ?> - <?php echo esc($record->punch_time ? pontorh_extract_time($record->punch_time) : '-'); ?></div>
            <div><?php echo esc(pontorh_punch_type_label($record->punch_type ?? '')); ?></div>
        </div>

        <?php if ($is_delete) { ?>
            <div class="text-muted mb-2"><?php echo app_lang('pontorh_record_action_delete'); ?></div>
            <div class="alert alert-warning mb-3"><?php echo app_lang('pontorh_record_action_delete_help'); ?></div>
        <?php } else { ?>
            <div class="form-group">
                <div class="row">
                    <label for="team_member_id" class="col-md-3"><?php echo app_lang('pontorh_employee'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_hidden('team_member_id', $selected_team_member_id); ?>
                        <?php echo form_dropdown('team_member_id_view', $team_members_dropdown, $selected_team_member_id, 'class="form-control select2 w100p" id="team_member_id" disabled'); ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="work_date" class="col-md-3"><?php echo app_lang('pontorh_work_date'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="work_date" id="pontorh-record-work-date" class="form-control datepicker" value="<?php echo esc($selected_work_date); ?>" autocomplete="off" readonly required />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="punch_time" class="col-md-3"><?php echo app_lang('time'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="punch_time" id="pontorh-record-work-time" class="form-control timepicker" value="<?php echo esc($selected_punch_time); ?>" autocomplete="off" required />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="punch_type" class="col-md-3"><?php echo app_lang('pontorh_type'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_dropdown('punch_type', $punch_type_dropdown, $selected_punch_type, 'class="form-control select2 w100p" id="punch_type" required'); ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="location_id" class="col-md-3"><?php echo app_lang('pontorh_location'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_dropdown('location_id', $locations_dropdown, $selected_location_id, 'class="form-control select2 w100p" id="location_id"'); ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="status" class="col-md-3"><?php echo app_lang('pontorh_status'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_dropdown('status', $status_dropdown, $selected_status, 'class="form-control select2 w100p" id="status"'); ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="source" class="col-md-3"><?php echo app_lang('pontorh_source'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="source" id="source" class="form-control" value="<?php echo esc($selected_source); ?>" />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="latitude" class="col-md-3">Latitude</label>
                    <div class="col-md-9">
                        <input type="text" name="latitude" id="latitude" class="form-control" value="<?php echo esc($selected_latitude); ?>" />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="longitude" class="col-md-3">Longitude</label>
                    <div class="col-md-9">
                        <input type="text" name="longitude" id="longitude" class="form-control" value="<?php echo esc($selected_longitude); ?>" />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="notes" class="col-md-3"><?php echo app_lang('notes'); ?></label>
                    <div class="col-md-9">
                        <textarea name="notes" id="notes" class="form-control" rows="3"><?php echo esc($selected_notes); ?></textarea>
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="justification" class="col-md-3"><?php echo app_lang('pontorh_record_action_reason'); ?></label>
                <div class="col-md-9">
                    <textarea name="justification" id="justification" class="form-control" rows="4" required></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo $is_delete ? app_lang('delete') : app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#pontorh-treatment-record-action-form .select2").select2();
        setDatePicker("#pontorh-record-work-date");
        setTimePicker("#pontorh-record-work-time");

        $("#pontorh-treatment-record-action-form").appForm({
            onSuccess: function () {
                window.location.reload();
            }
        });
    });
</script>
