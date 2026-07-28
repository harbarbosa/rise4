<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_settings_title'); ?></h1>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo_uri('laudos_tecnicos/save_settings'); ?>" id="settings-form">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module_name"><?php echo app_lang('laudos_settings_module_name'); ?></label>
                            <input type="text" name="module_name" id="module_name" class="form-control" value="<?php echo $settings->module_name; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="laudo_prefix"><?php echo app_lang('laudos_settings_prefix'); ?></label>
                            <input type="text" name="laudo_prefix" id="laudo_prefix" class="form-control" value="<?php echo $settings->laudo_prefix; ?>" maxlength="10">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="number_format"><?php echo app_lang('laudos_settings_number_format'); ?></label>
                            <input type="text" name="number_format" id="number_format" class="form-control" value="<?php echo $settings->number_format; ?>">
                            <small class="text-muted">{PREFIX}-{YEAR}{MONTH}{SEQUENCE}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="next_number"><?php echo app_lang('laudos_settings_next_number'); ?></label>
                            <input type="number" name="next_number" id="next_number" class="form-control" value="<?php echo $settings->next_number; ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="primary_color"><?php echo app_lang('laudos_settings_primary_color'); ?></label>
                            <input type="color" name="primary_color" id="primary_color" class="form-control" value="<?php echo $settings->primary_color; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="timezone"><?php echo app_lang('laudos_settings_timezone'); ?></label>
                            <select name="timezone" id="timezone" class="form-control">
                                <option value="America/Sao_Paulo" <?php echo $settings->timezone == 'America/Sao_Paulo' ? 'selected' : ''; ?>>América/São Paulo</option>
                                <option value="America/Manaus" <?php echo $settings->timezone == 'America/Manaus' ? 'selected' : ''; ?>>América/Manaus</option>
                                <option value="America/Recife" <?php echo $settings->timezone == 'America/Recife' ? 'selected' : ''; ?>>América/Recife</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date_format"><?php echo app_lang('laudos_settings_date_format'); ?></label>
                            <select name="date_format" id="date_format" class="form-control">
                                <option value="d/m/Y" <?php echo $settings->date_format == 'd/m/Y' ? 'selected' : ''; ?>>dd/mm/aaaa</option>
                                <option value="d-m-Y" <?php echo $settings->date_format == 'd-m-Y' ? 'selected' : ''; ?>>dd-mm-aaaa</option>
                                <option value="Y-m-d" <?php echo $settings->date_format == 'Y-m-d' ? 'selected' : ''; ?>>aaaa-mm-dd</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="default_validity_days"><?php echo app_lang('laudos_settings_default_validity'); ?></label>
                            <input type="number" name="default_validity_days" id="default_validity_days" class="form-control" value="<?php echo $settings->default_validity_days; ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="module_active" id="module_active" class="form-check-input" value="1" <?php echo $settings->module_active ? 'checked' : ''; ?>>
                                <label for="module_active" class="form-check-label"><?php echo app_lang('laudos_settings_module_active'); ?></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="enable_detailed_logs" id="enable_detailed_logs" class="form-check-input" value="1" <?php echo $settings->enable_detailed_logs ? 'checked' : ''; ?>>
                                <label for="enable_detailed_logs" class="form-check-label"><?php echo app_lang('laudos_settings_enable_logs'); ?></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="require_inspection" id="require_inspection" class="form-check-input" value="1" <?php echo $settings->require_inspection ? 'checked' : ''; ?>>
                                <label for="require_inspection" class="form-check-label"><?php echo app_lang('laudos_settings_require_inspection'); ?></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="require_approval" id="require_approval" class="form-check-input" value="1" <?php echo $settings->require_approval ? 'checked' : ''; ?>>
                                <label for="require_approval" class="form-check-label"><?php echo app_lang('laudos_settings_require_approval'); ?></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="auto_notify_client" id="auto_notify_client" class="form-check-input" value="1" <?php echo $settings->auto_notify_client ? 'checked' : ''; ?>>
                                <label for="auto_notify_client" class="form-check-label"><?php echo app_lang('laudos_settings_notify_client'); ?></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><?php echo app_lang('laudos_settings_save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#settings-form').appForm({
            success: function(response) {
                appAlert.success(response.message, {duration: 3000});
            }
        });
    });
</script>