<?php
$case = $case ?? null;
$team_members_dropdown = $team_members_dropdown ?? array();
$punch_type_dropdown = $punch_type_dropdown ?? array();
$selected_team_member_id = !empty($case) && !empty($case->team_member_id) ? (int) $case->team_member_id : '';
$selected_work_date = !empty($case) && !empty($case->work_date) ? $case->work_date : get_my_local_time('Y-m-d');
$selected_punch_type = !empty($case) && !empty($case->punch_type) ? $case->punch_type : '';
$selected_justification = !empty($case) && !empty($case->justification) ? $case->justification : '';
$selected_notes = !empty($case) && !empty($case->notes) ? $case->notes : '';
?>

<?php echo form_open(get_uri('pontorh/tratamento/save_manual'), array('id' => 'pontorh-treatment-manual-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <?php if ($case && !empty($case->id)) { ?>
            <?php echo form_hidden('case_id', (string) (int) $case->id); ?>
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="team_member_id" class="col-md-3"><?php echo app_lang('pontorh_employee'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('team_member_id', $team_members_dropdown, $selected_team_member_id, 'class="form-control select2 w100p" id="team_member_id" required'); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="work_date" class="col-md-3"><?php echo app_lang('pontorh_work_date'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="work_date" id="pontorh-treatment-work-date" class="form-control datepicker" value="<?php echo esc($selected_work_date); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="pontorh-treatment-work-time" class="col-md-3"><?php echo app_lang('pontorh_adjustment_time'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="punch_time" id="pontorh-treatment-work-time" class="form-control timepicker" value="<?php echo esc(get_my_local_time('H:i')); ?>" autocomplete="off" required />
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
                <label for="justification" class="col-md-3"><?php echo app_lang('pontorh_reason'); ?></label>
                <div class="col-md-9">
                    <textarea name="justification" id="justification" class="form-control" rows="3" required><?php echo esc($selected_justification); ?></textarea>
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
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#pontorh-treatment-manual-form .select2").select2();
        setDatePicker("#pontorh-treatment-work-date");
        setTimePicker("#pontorh-treatment-work-time");

        $("#pontorh-treatment-manual-form").appForm({
            onSuccess: function () {
                window.location.reload();
            }
        });
    });
</script>
