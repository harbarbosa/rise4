<?php
$model_info = $model_info ?? (object) array();
$team_members_dropdown = $team_members_dropdown ?? array();
$location = $location ?? null;

echo form_open(get_uri('pontorh/locais/assignment_save'), array('id' => 'pontorh-location-assignment-form', 'class' => 'general-form', 'role' => 'form'));
?>
<div class="modal-body clearfix">
    <?php echo form_hidden('location_id', (string) ($model_info->location_id ?? $location_id ?? '')); ?>
    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('pontorh_location'); ?></label>
                <div class="col-md-9">
                    <input type="text" class="form-control" value="<?php echo esc($location->name ?? '-'); ?>" readonly />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="team_member_ids" class="col-md-3"><?php echo app_lang('pontorh_employee'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('team_member_ids[]', $team_members_dropdown, '', 'class="form-control select2 w100p" id="team_member_ids" multiple required data-placeholder="' . app_lang('pontorh_employee') . '"'); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="week_start" class="col-md-3"><?php echo app_lang('pontorh_period_start'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="week_start" id="week_start" class="form-control datepicker" value="<?php echo esc($model_info->week_start ?? date('Y-m-d')); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="week_end" class="col-md-3"><?php echo app_lang('pontorh_period_end'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="week_end" id="week_end" class="form-control datepicker" value="<?php echo esc($model_info->week_end ?? date('Y-m-d')); ?>" autocomplete="off" required />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check mt10">
                        <?php echo form_checkbox('active', '1', !empty($model_info->active), "class='form-check-input' id='pontorh-location-assignment-active'"); ?>
                        <label for="pontorh-location-assignment-active" class="form-check-label"><?php echo app_lang('active'); ?></label>
                    </div>
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
        $("#pontorh-location-assignment-form .select2").select2();
        setDatePicker("#week_start, #week_end");

        $("#pontorh-location-assignment-form").appForm({
            onSuccess: function () {
                window.location.reload();
            }
        });
    });
</script>
