<?php
$case = $case ?? null;
$punch_type_dropdown = $punch_type_dropdown ?? array();
?>

<div class="modal-body clearfix">
    <?php echo form_open(get_uri('pontorh/tratamento/save_manual'), array('id' => 'pontorh-treatment-manual-form', 'class' => 'general-form')); ?>
    <div class="container-fluid">
        <?php if ($case && !empty($case->id)) { ?>
            <input type="hidden" name="case_id" value="<?php echo (int) $case->id; ?>" />
            <input type="hidden" name="team_member_id" value="<?php echo (int) $case->team_member_id; ?>" />
        <?php } else { ?>
            <div class="form-group">
                <div class="row">
                    <label class="col-md-3"><?php echo app_lang('pontorh_employee'); ?></label>
                    <div class="col-md-9">
                        <input type="number" name="team_member_id" class="form-control" required />
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('pontorh_work_date'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="work_date" id="pontorh-treatment-work-date" class="form-control datepicker" value="<?php echo esc($case->work_date ?? date('Y-m-d')); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('pontorh_adjustment_time'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="punch_time" id="pontorh-treatment-work-time" class="form-control timepicker" value="<?php echo esc(get_my_local_time('H:i')); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('pontorh_type'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('punch_type', $punch_type_dropdown, '', 'class="form-control select2 w100p" required'); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('pontorh_reason'); ?></label>
                <div class="col-md-9">
                    <textarea name="justification" class="form-control" rows="3" required></textarea>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('notes'); ?></label>
                <div class="col-md-9">
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
        <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
    </div>
    <?php echo form_close(); ?>
</div>

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
