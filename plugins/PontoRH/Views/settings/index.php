<?php
$settings = $settings ?? array();
echo form_open(get_uri('pontorh/configuracoes/save'), array('id' => 'pontorh-settings-form', 'class' => 'general-form', 'role' => 'form'));
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('pontorh_settings'); ?></h1>
        </div>

        <div class="card-body">
            <p class="text-muted"><?php echo app_lang('pontorh_settings_intro'); ?></p>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_workday_start'); ?></label>
                    <?php echo form_input(array(
                        'name' => 'workday_start',
                        'value' => get_array_value($settings, 'workday_start') ?: '08:00',
                        'class' => 'form-control timepicker',
                    )); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_workday_end'); ?></label>
                    <?php echo form_input(array(
                        'name' => 'workday_end',
                        'value' => get_array_value($settings, 'workday_end') ?: '18:00',
                        'class' => 'form-control timepicker',
                    )); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_break_minutes'); ?></label>
                    <?php echo form_input(array(
                        'name' => 'default_break_minutes',
                        'value' => (string) (get_array_value($settings, 'default_break_minutes') ?: 60),
                        'class' => 'form-control',
                        'type' => 'number',
                        'min' => '0',
                    )); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_mirror_days'); ?></label>
                    <?php echo form_input(array(
                        'name' => 'mirror_default_range_days',
                        'value' => (string) (get_array_value($settings, 'mirror_default_range_days') ?: 31),
                        'class' => 'form-control',
                        'type' => 'number',
                        'min' => '1',
                    )); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_reports_days'); ?></label>
                    <?php echo form_input(array(
                        'name' => 'reports_default_range_days',
                        'value' => (string) (get_array_value($settings, 'reports_default_range_days') ?: 31),
                        'class' => 'form-control',
                        'type' => 'number',
                        'min' => '1',
                    )); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_allow_manual_adjustments'); ?></label>
                    <div class="mt-2">
                        <?php echo form_checkbox('allow_manual_adjustments', '1', (int) get_array_value($settings, 'allow_manual_adjustments') === 1 || get_array_value($settings, 'allow_manual_adjustments') === '1', "class='form-check-input' id='pontorh-allow-manual-adjustments'"); ?>
                        <label for="pontorh-allow-manual-adjustments" class="form-check-label"><?php echo app_lang('active'); ?></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_require_gps'); ?></label>
                    <div class="mt-2">
                        <?php echo form_checkbox('require_gps', '1', (int) get_array_value($settings, 'require_gps') === 1 || get_array_value($settings, 'require_gps') === '1', "class='form-check-input' id='pontorh-require-gps'"); ?>
                        <label for="pontorh-require-gps" class="form-check-label"><?php echo app_lang('active'); ?></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_require_selfie'); ?></label>
                    <div class="mt-2">
                        <?php echo form_checkbox('require_selfie', '1', (int) get_array_value($settings, 'require_selfie') === 1 || get_array_value($settings, 'require_selfie') === '1', "class='form-check-input' id='pontorh-require-selfie'"); ?>
                        <label for="pontorh-require-selfie" class="form-check-label"><?php echo app_lang('active'); ?></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_allow_offline_marking'); ?></label>
                    <div class="mt-2">
                        <?php echo form_checkbox('allow_offline_marking', '1', (int) get_array_value($settings, 'allow_offline_marking') === 1 || get_array_value($settings, 'allow_offline_marking') === '1', "class='form-check-input' id='pontorh-allow-offline'"); ?>
                        <label for="pontorh-allow-offline" class="form-check-label"><?php echo app_lang('active'); ?></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_allowed_radius_meters'); ?></label>
                    <?php echo form_input(array(
                        'name' => 'allowed_radius_meters',
                        'value' => (string) (get_array_value($settings, 'allowed_radius_meters') ?: 200),
                        'class' => 'form-control',
                        'type' => 'number',
                        'min' => '0',
                    )); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_default_tolerance_minutes'); ?></label>
                    <?php echo form_input(array(
                        'name' => 'default_tolerance_minutes',
                        'value' => (string) (get_array_value($settings, 'default_tolerance_minutes') ?: 10),
                        'class' => 'form-control',
                        'type' => 'number',
                        'min' => '0',
                    )); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_bank_hours'); ?></label>
                    <div class="mt-2">
                        <?php echo form_checkbox('bank_hours_enabled', '1', (int) get_array_value($settings, 'bank_hours_enabled') === 1 || get_array_value($settings, 'bank_hours_enabled') === '1', "class='form-check-input' id='pontorh-bank-hours'"); ?>
                        <label for="pontorh-bank-hours" class="form-check-label"><?php echo app_lang('active'); ?></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?php echo app_lang('pontorh_google_maps_api_key'); ?></label>
                    <?php echo form_input(array(
                        'name' => 'google_maps_api_key',
                        'value' => get_array_value($settings, 'google_maps_api_key'),
                        'class' => 'form-control',
                        'autocomplete' => 'off',
                        'placeholder' => 'AIza...',
                    )); ?>
                </div>
            </div>
        </div>

        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
        </div>
    </div>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#pontorh-settings-form").appForm();
    });
</script>
