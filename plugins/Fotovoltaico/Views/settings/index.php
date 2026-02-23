<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_settings'); ?></h1>
            </div>

            <div class="card p20">
                <p><?php echo app_lang('fv_settings'); ?></p>
                <a href="<?php echo get_uri('fotovoltaico/integrations/cec'); ?>" class="btn btn-default">
                    <i data-feather="link"></i> <?php echo app_lang('fv_integrations_cec'); ?>
                </a>
            </div>

            <div class="card p20 mtop20">
                <h4><?php echo app_lang('fv_electrical_settings'); ?></h4>
                <?php echo form_open(get_uri('fotovoltaico/settings_save'), ["id" => "fv-electrical-settings-form", "class" => "general-form"]); ?>

                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_temp_min_c'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="temp_min_c" value="<?php echo esc($electrical_settings['temp_min_c'] ?? 5); ?>" class="form-control" />
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_temp_max_c'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="temp_max_c" value="<?php echo esc($electrical_settings['temp_max_c'] ?? 70); ?>" class="form-control" />
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_safety_margin_vdc'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="safety_margin_vdc_percent" value="<?php echo esc($electrical_settings['safety_margin_vdc_percent'] ?? 2); ?>" class="form-control" />
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_safety_margin_current'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="safety_margin_current_percent" value="<?php echo esc($electrical_settings['safety_margin_current_percent'] ?? 0); ?>" class="form-control" />
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_assume_voc_coeff'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="assume_voc_temp_coeff_if_missing" value="<?php echo esc($electrical_settings['assume_voc_temp_coeff_if_missing'] ?? -0.28); ?>" class="form-control" />
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-md-3"><?php echo app_lang('fv_assume_vmpp_ratio'); ?></label>
                    <div class="col-md-9">
                        <input type="text" name="assume_vmpp_ratio" value="<?php echo esc($electrical_settings['assume_vmpp_ratio'] ?? 0.83); ?>" class="form-control" />
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-3"></label>
                    <div class="col-md-9">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="save"></i> <?php echo app_lang('save'); ?>
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
        $("#fv-electrical-settings-form").appForm({
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 3000});
            }
        });
    });
</script>
