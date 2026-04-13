<?php
$settings = $settings ?? array();
$public_api_token = $settings['organizador_public_api_token'] ?? '';
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('organizador_settings'); ?></h1>
        </div>
        <div class="card-body">
            <?php echo form_open(get_uri('organizador/settings/save'), array('id' => 'organizador-settings-form', 'class' => 'general-form', 'role' => 'form')); ?>
            <div class="form-check mb-3">
                <?php echo form_checkbox('organizador_enable_internal_notifications', '1', !empty($settings['organizador_enable_internal_notifications']), "id='organizador_enable_internal_notifications' class='form-check-input'"); ?>
                <label for="organizador_enable_internal_notifications" class="form-check-label"><?php echo app_lang('organizador_enable_internal_notifications'); ?></label>
            </div>
            <div class="form-check mb-3">
                <?php echo form_checkbox('organizador_enable_email_notifications', '1', !empty($settings['organizador_enable_email_notifications']), "id='organizador_enable_email_notifications' class='form-check-input'"); ?>
                <label for="organizador_enable_email_notifications" class="form-check-label"><?php echo app_lang('organizador_enable_email_notifications'); ?></label>
            </div>
            <div class="form-check mb-3">
                <?php echo form_checkbox('organizador_enable_auto_reminders', '1', !empty($settings['organizador_enable_auto_reminders']), "id='organizador_enable_auto_reminders' class='form-check-input'"); ?>
                <label for="organizador_enable_auto_reminders" class="form-check-label"><?php echo app_lang('organizador_enable_auto_reminders'); ?></label>
            </div>
            <div class="form-check mb-3">
                <?php echo form_checkbox('organizador_enable_overdue_alerts', '1', !empty($settings['organizador_enable_overdue_alerts']), "id='organizador_enable_overdue_alerts' class='form-check-input'"); ?>
                <label for="organizador_enable_overdue_alerts" class="form-check-label"><?php echo app_lang('organizador_enable_overdue_alerts'); ?></label>
            </div>
            <div class="form-group mb-3">
                <label><?php echo app_lang('organizador_reminder_hours_before_due'); ?></label>
                <input type="number" name="organizador_reminder_hours_before_due" value="<?php echo esc($settings['organizador_reminder_hours_before_due'] ?? 24); ?>" class="form-control" min="1" />
            </div>
            <div class="form-check mb-3">
                <?php echo form_checkbox('organizador_sync_to_events_calendar', '1', !empty($settings['organizador_sync_to_events_calendar']), "id='organizador_sync_to_events_calendar' class='form-check-input'"); ?>
                <label for="organizador_sync_to_events_calendar" class="form-check-label"><?php echo app_lang('organizador_sync_to_events_calendar'); ?></label>
            </div>
            <hr class="my-4" />
            <h4 class="mb-3"><?php echo app_lang('organizador_public_api'); ?></h4>
            <div class="form-check mb-3">
                <?php echo form_checkbox('organizador_public_api_enabled', '1', !empty($settings['organizador_public_api_enabled']), "id='organizador_public_api_enabled' class='form-check-input'"); ?>
                <label for="organizador_public_api_enabled" class="form-check-label"><?php echo app_lang('organizador_public_api_enabled'); ?></label>
            </div>
            <div class="form-group mb-3">
                <label><?php echo app_lang('organizador_public_api_token'); ?></label>
                <div class="input-group">
                    <input type="text" value="<?php echo esc($public_api_token); ?>" class="form-control" readonly />
                    <button type="submit" name="regenerate_public_api_token" value="1" class="btn btn-outline-secondary"><?php echo app_lang('organizador_public_api_regenerate'); ?></button>
                </div>
                <div class="form-text"><?php echo app_lang('organizador_public_api_help'); ?></div>
            </div>
            <div class="mb-3">
                <div class="small text-muted"><?php echo app_lang('organizador_public_api_header_hint'); ?></div>
                <code>Authorization: Bearer <?php echo esc($public_api_token); ?></code>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#organizador-settings-form").appForm({
            onSuccess: function (result) {
                appAlert.success(result.message);
            }
        });
    });
</script>
