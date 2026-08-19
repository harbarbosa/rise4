<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('engenharia_settings'); ?></h1>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label"><?php echo app_lang('engenharia_module_name'); ?></label>
                <input type="text" class="form-control" value="<?php echo esc($settings['module_name'] ?? 'Engenharia'); ?>" readonly>
            </div>
            <h5 class="mt20"><?php echo app_lang('engenharia_types'); ?></h5>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th><?php echo app_lang('name'); ?></th><th><?php echo app_lang('code'); ?></th><th><?php echo app_lang('status'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($types as $type) { ?>
                            <tr>
                                <td><?php echo esc($type->name); ?></td>
                                <td><?php echo esc($type->code); ?></td>
                                <td><span class="badge <?php echo $type->is_enabled ? 'bg-success' : 'bg-secondary'; ?>"><?php echo app_lang($type->is_enabled ? 'engenharia_enabled' : 'engenharia_disabled'); ?></span></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top"><h5><?php echo app_lang('engenharia_instruments'); ?></h5><div class="table-responsive"><table class="table"><thead><tr><th><?php echo app_lang('name'); ?></th><th><?php echo app_lang('engenharia_manufacturer_model'); ?></th><th><?php echo app_lang('engenharia_calibration_valid_until'); ?></th><th><?php echo app_lang('status'); ?></th></tr></thead><tbody><?php foreach(($instruments??array()) as $instrument){$expired=!empty($instrument->calibration_valid_until)&&strtotime($instrument->calibration_valid_until)<strtotime(date('Y-m-d')); ?><tr><td><?php echo esc($instrument->name); ?></td><td><?php echo esc(trim(($instrument->manufacturer??'').' '.($instrument->model??''))); ?></td><td><?php echo esc($instrument->calibration_valid_until?:'-'); ?> <?php if($expired){ ?><span class="badge bg-danger"><?php echo app_lang('engenharia_calibration_expired'); ?></span><?php } ?></td><td><span class="badge <?php echo !empty($instrument->is_active)?'bg-success':'bg-secondary'; ?>"><?php echo !empty($instrument->is_active)?app_lang('engenharia_active'):app_lang('engenharia_inactive'); ?></span></td></tr><?php } ?></tbody></table></div><h6 class="mt20"><?php echo app_lang('engenharia_add_instrument'); ?></h6><?php echo form_open(get_uri('engenharia/instruments/save'),array('id'=>'engenharia-instrument-form','class'=>'general-form')); ?><div class="row"><div class="col-md-4 form-group"><input name="name" class="form-control" placeholder="<?php echo app_lang('name'); ?>" required></div><div class="col-md-3 form-group"><input name="manufacturer" class="form-control" placeholder="<?php echo app_lang('engenharia_manufacturer'); ?>"></div><div class="col-md-3 form-group"><input name="model" class="form-control" placeholder="<?php echo app_lang('engenharia_model'); ?>"></div><div class="col-md-2 form-group"><input name="serial_number" class="form-control" placeholder="<?php echo app_lang('engenharia_serial_number'); ?>"></div></div><div class="row"><div class="col-md-4 form-group"><input name="calibration_certificate" class="form-control" placeholder="<?php echo app_lang('engenharia_calibration_certificate'); ?>"></div><div class="col-md-4 form-group"><input name="calibration_valid_until" type="date" class="form-control"></div><div class="col-md-4 form-group"><button class="btn btn-primary" type="submit"><?php echo app_lang('save'); ?></button></div></div><?php echo form_close(); ?></div>
        <div class="card-body border-top"><h5><?php echo app_lang('engenharia_report_settings'); ?></h5><?php echo form_open(get_uri('engenharia/report/settings'),array('id'=>'engenharia-report-settings-form','class'=>'general-form')); ?><div class="row"><div class="col-md-6 form-group"><label><?php echo app_lang('engenharia_company_data'); ?></label><textarea name="report_company_data" class="form-control"><?php echo esc($settings['report_company_data']??''); ?></textarea></div><div class="col-md-3 form-group"><label><?php echo app_lang('engenharia_primary_color'); ?></label><input name="report_primary_color" type="color" class="form-control form-control-color" value="<?php echo esc($settings['report_primary_color']??'#2d6ca2'); ?>"></div><div class="col-md-3 form-group"><label><?php echo app_lang('engenharia_photos_per_page'); ?></label><input name="report_photos_per_page" type="number" min="1" max="8" class="form-control" value="<?php echo esc($settings['report_photos_per_page']??4); ?>"></div></div><div class="row"><div class="col-md-6 form-group"><label><?php echo app_lang('engenharia_report_header'); ?></label><textarea name="report_header" class="form-control"><?php echo esc($settings['report_header']??''); ?></textarea></div><div class="col-md-6 form-group"><label><?php echo app_lang('engenharia_report_footer'); ?></label><textarea name="report_footer" class="form-control"><?php echo esc($settings['report_footer']??''); ?></textarea></div></div><div class="form-check mb10"><input type="checkbox" name="report_show_conforming" value="1" class="form-check-input" <?php echo ($settings['report_show_conforming']??'1')==='1'?'checked':''; ?>><label class="form-check-label"><?php echo app_lang('engenharia_show_conforming'); ?></label></div><button class="btn btn-primary" type="submit"><?php echo app_lang('save'); ?></button><?php echo form_close(); ?></div>
    </div>
</div>
<script>$(function(){$('#engenharia-instrument-form,#engenharia-report-settings-form').appForm({onSuccess:function(){location.reload();}});});</script>
