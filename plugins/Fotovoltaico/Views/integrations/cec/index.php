<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_integrations_cec'); ?></h1>
                <div class="title-button-group">
                    <a href="<?php echo get_uri('fotovoltaico/integrations/cec/logs'); ?>" class="btn btn-default">
                        <i data-feather="list"></i> <?php echo app_lang('fv_view_logs'); ?>
                    </a>
                </div>
            </div>

            <div class="card p20">
                <?php if (!empty($last_log)) { ?>
                    <div class="alert alert-info">
                        <strong><?php echo app_lang('fv_last_sync'); ?>:</strong>
                        <?php echo $last_log->finished_at ?: $last_log->started_at; ?>
                        |
                        <strong><?php echo app_lang('fv_log_status'); ?>:</strong> <?php echo $last_log->status; ?>
                        |
                        <strong><?php echo app_lang('fv_last_sync_total'); ?>:</strong>
                        <?php
                        $summary = $last_log->summary_json ? json_decode($last_log->summary_json, true) : [];
                        $total = 0;
                        if (is_array($summary)) {
                            $total += (int)($summary['modules']['inserted'] ?? 0);
                            $total += (int)($summary['inverters']['inserted'] ?? 0);
                        }
                        echo $total;
                        ?>
                    </div>
                <?php } ?>

                <?php echo form_open(get_uri('fotovoltaico/integrations/cec/save'), ["id" => "fv-cec-settings-form", "class" => "general-form"]); ?>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_cec_enable'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_checkbox("enabled", "1", !empty($settings['enabled']), "id='fv_cec_enabled'");
                        ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_cec_modules_url'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="cec_modules_url" value="<?php echo esc($settings['cec_modules_url'] ?? ''); ?>" class="form-control" />
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_cec_inverters_url'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="cec_inverters_url" value="<?php echo esc($settings['cec_inverters_url'] ?? ''); ?>" class="form-control" />
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_import_mode'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_dropdown(
                            "mode",
                            ["insert" => app_lang('fv_mode_insert'), "upsert" => app_lang('fv_mode_upsert')],
                            $settings['mode'] ?? 'insert',
                            "class='form-control' id='fv_cec_mode'"
                        );
                        ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_update_prices'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_checkbox("update_prices", "1", !empty($settings['update_prices']), "id='fv_cec_update_prices'"); ?>
                        <span class="text-muted"><?php echo app_lang('fv_update_prices_note'); ?></span>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_zero_prices'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_checkbox("zero_prices", "1", !empty($settings['zero_prices']), "id='fv_cec_zero_prices'"); ?>
                        <span class="text-muted"><?php echo app_lang('fv_zero_prices_note'); ?></span>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_deactivate_removed'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_checkbox("deactivate_removed", "1", !empty($settings['deactivate_removed']), "id='fv_cec_deactivate_removed'"); ?>
                        <span class="text-muted"><?php echo app_lang('fv_deactivate_removed_note'); ?></span>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_allow_external_url'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_checkbox("allow_external_url", "1", !empty($settings['allow_external_url']), "id='fv_cec_allow_external_url'"); ?>
                        <span class="text-muted"><?php echo app_lang('fv_allow_external_url_note'); ?></span>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_cron_token'); ?></label>
                    <div class="col-md-9">
                        <div class="form-control" style="height:auto;">
                            <?php echo esc($settings['cron_token'] ?? ''); ?>
                        </div>
                        <small class="text-muted"><?php echo app_lang('fv_cron_token_note'); ?></small>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_sync_frequency'); ?></label>
                    <div class="col-md-9">
                        <div class="form-control" style="height:auto;"><?php echo app_lang('fv_sync_weekly'); ?></div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"></label>
                    <div class="col-md-9">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="save"></i> <?php echo app_lang('save'); ?>
                        </button>
                        <button type="button" class="btn btn-default" id="fv-cec-test-btn">
                            <i data-feather="download-cloud"></i> <?php echo app_lang('fv_test_download'); ?>
                        </button>
                        <button type="button" class="btn btn-success" id="fv-cec-run-btn">
                            <i data-feather="play"></i> <?php echo app_lang('fv_run_sync'); ?>
                        </button>
                        <button type="button" class="btn btn-danger" id="fv-cec-force-btn">
                            <i data-feather="zap"></i> <?php echo app_lang('fv_run_sync_force'); ?>
                        </button>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $("#fv-cec-settings-form").appForm({
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 3000});
            }
        });

        $("#fv-cec-test-btn").on("click", function () {
            appLoader.show();
            $.post("<?php echo get_uri('fotovoltaico/integrations/cec/test'); ?>", function (result) {
                appLoader.hide();
                if (result && result.success) {
                    appAlert.success("<?php echo app_lang('fv_test_ok'); ?>");
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            });
        });

        $("#fv-cec-run-btn").on("click", function () {
            appLoader.show();
            $.post("<?php echo get_uri('fotovoltaico/integrations/cec/run'); ?>", function (result) {
                appLoader.hide();
                if (result && result.success) {
                    appAlert.success("<?php echo app_lang('fv_sync_done'); ?>");
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            });
        });

        $("#fv-cec-force-btn").on("click", function () {
            appLoader.show();
            $.post("<?php echo get_uri('fotovoltaico/integrations/cec/run'); ?>", {force: 1}, function (result) {
                appLoader.hide();
                if (result && result.success) {
                    appAlert.success("<?php echo app_lang('fv_sync_done'); ?>");
                } else {
                    appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                }
            });
        });
    });
</script>
