<?php
$settings = $settings ?? array();
$provider_dropdown = $provider_dropdown ?? array();
$recipient_dropdown = $recipient_dropdown ?? array();
$selected_recipients = $selected_recipients ?? array();
$can_manage_settings = $can_manage_settings ?? false;
$can_manage_ai_settings = $can_manage_ai_settings ?? false;
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('licitaia_settings'); ?></h1>
        </div>

        <div class="card-body">
            <?php echo form_open(get_uri('licitaia/settings/save'), array('id' => 'licitaia-settings-form', 'class' => 'general-form', 'role' => 'form')); ?>
            <?php if ($can_manage_ai_settings) { ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><?php echo app_lang('licitaia_ai_provider'); ?></label>
                        <?php echo form_dropdown('ai_provider', $provider_dropdown, (isset($settings['ai_provider']) ? $settings['ai_provider'] : 'openai'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo app_lang('licitaia_ai_model'); ?></label>
                        <input type="text" name="ai_model" class="form-control" value="<?php echo esc(isset($settings['ai_model']) ? $settings['ai_model'] : 'gpt-4.1-mini'); ?>" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo app_lang('licitaia_ai_api_base_url'); ?></label>
                        <input type="text" name="ai_api_base_url" class="form-control" value="<?php echo esc(isset($settings['ai_api_base_url']) ? $settings['ai_api_base_url'] : ''); ?>" />
                        <small class="text-muted"><?php echo app_lang('licitaia_ai_base_url_hint'); ?></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo app_lang('licitaia_ai_api_key'); ?></label>
                        <input type="password" name="ai_api_key" class="form-control" value="<?php echo esc(isset($settings['ai_api_key']) ? $settings['ai_api_key'] : ''); ?>" />
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-4 pt-2">
                            <input type="checkbox" class="form-check-input" id="ai_enabled" name="ai_enabled" value="1" <?php echo (isset($settings['ai_enabled']) ? $settings['ai_enabled'] : '1') == '1' ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="ai_enabled"><?php echo app_lang('licitaia_ai_enabled'); ?></label>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($can_manage_settings) { ?>
                <?php if ($can_manage_ai_settings) { ?><hr class="my-4" /><?php } ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><?php echo app_lang('licitaia_default_opportunity_status'); ?></label>
                        <input type="text" name="opportunities_default_status" class="form-control" value="<?php echo esc(isset($settings['opportunities_default_status']) ? $settings['opportunities_default_status'] : 'new'); ?>" />
                    </div>
                    <div class="col-md-12">
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="checklist_enabled" name="checklist_enabled" value="1" <?php echo (isset($settings['checklist_enabled']) ? $settings['checklist_enabled'] : '1') == '1' ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="checklist_enabled"><?php echo app_lang('licitaia_checklist_enabled'); ?></label>
                        </div>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="reports_enabled" name="reports_enabled" value="1" <?php echo (isset($settings['reports_enabled']) ? $settings['reports_enabled'] : '1') == '1' ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="reports_enabled"><?php echo app_lang('licitaia_reports_enabled'); ?></label>
                        </div>
                    </div>
                </div>

                <hr class="my-4" />

                <div class="row g-3">
                    <div class="col-12">
                        <h5 class="mb-3"><?php echo app_lang('licitaia_alerts_settings'); ?></h5>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="alerts_enabled" name="alerts_enabled" value="1" <?php echo (isset($settings['alerts_enabled']) ? $settings['alerts_enabled'] : '1') == '1' ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="alerts_enabled"><?php echo app_lang('licitaia_alerts_enabled'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo app_lang('licitaia_alert_days_before_opening'); ?></label>
                        <input type="text" name="alerts_days_before_opening" class="form-control" value="<?php echo esc(isset($settings['alerts_days_before_opening']) ? $settings['alerts_days_before_opening'] : '7,3,1'); ?>" />
                        <small class="text-muted"><?php echo app_lang('licitaia_alert_days_hint'); ?></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo app_lang('licitaia_alert_days_before_submission'); ?></label>
                        <input type="text" name="alerts_days_before_submission" class="form-control" value="<?php echo esc(isset($settings['alerts_days_before_submission']) ? $settings['alerts_days_before_submission'] : '7,3,1'); ?>" />
                        <small class="text-muted"><?php echo app_lang('licitaia_alert_days_hint'); ?></small>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label"><?php echo app_lang('licitaia_alert_recipient_users'); ?></label>
                        <select name="alerts_recipient_user_ids[]" class="form-control select2" multiple="multiple">
                            <?php foreach ($recipient_dropdown as $user_id => $user_name) { ?>
                                <option value="<?php echo esc($user_id); ?>" <?php echo in_array((string) $user_id, $selected_recipients, true) ? 'selected' : ''; ?>><?php echo esc($user_name); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="alerts_email_enabled" name="alerts_email_enabled" value="1" <?php echo (isset($settings['alerts_email_enabled']) ? $settings['alerts_email_enabled'] : '1') == '1' ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="alerts_email_enabled"><?php echo app_lang('licitaia_alert_email_enabled'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="alerts_whatsapp_enabled" name="alerts_whatsapp_enabled" value="1" <?php echo (isset($settings['alerts_whatsapp_enabled']) ? $settings['alerts_whatsapp_enabled'] : '0') == '1' ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="alerts_whatsapp_enabled"><?php echo app_lang('licitaia_alert_whatsapp_enabled'); ?></label>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#licitaia-settings-form .select2").select2();

        $("#licitaia-settings-form [name='ai_provider']").on("change", function () {
            var provider = $(this).val();
            var $baseUrl = $("#licitaia-settings-form [name='ai_api_base_url']");

            if (provider === "openrouter" && !$baseUrl.val()) {
                $baseUrl.val("https://openrouter.ai/api/v1");
            }
        });

        $("#licitaia-settings-form").appForm({
            onSuccess: function () {
                appAlert.success("<?php echo app_lang('record_saved'); ?>");
            }
        });
    });
</script>
