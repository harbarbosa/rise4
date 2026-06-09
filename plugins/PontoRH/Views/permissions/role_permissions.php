<?php

if (defined('PONTORH_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

$pontorh_view_own = get_array_value($permissions, 'pontorh_view_own');
$pontorh_create_record = get_array_value($permissions, 'pontorh_create_record');
$pontorh_request_adjustment = get_array_value($permissions, 'pontorh_request_adjustment');
$pontorh_view_team = get_array_value($permissions, 'pontorh_view_team');
$pontorh_approve_adjustment = get_array_value($permissions, 'pontorh_approve_adjustment');
$pontorh_manage_schedules = get_array_value($permissions, 'pontorh_manage_schedules');
$pontorh_view_reports = get_array_value($permissions, 'pontorh_view_reports');
$pontorh_manage_settings = get_array_value($permissions, 'pontorh_manage_settings');
$pontorh_admin = get_array_value($permissions, 'pontorh_admin');
?>

<li>
    <span data-feather="clock" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang('pontorh_permissions'); ?></h5>
    <div>
        <?php echo form_checkbox('pontorh_admin', '1', $pontorh_admin ? true : false, "id='pontorh_admin' class='form-check-input'"); ?>
        <label for="pontorh_admin"><?php echo app_lang('pontorh_permission_admin'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('pontorh_view_own', '1', $pontorh_view_own ? true : false, "id='pontorh_view_own' class='form-check-input'"); ?>
        <label for="pontorh_view_own"><?php echo app_lang('pontorh_permission_view_own'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('pontorh_create_record', '1', $pontorh_create_record ? true : false, "id='pontorh_create_record' class='form-check-input'"); ?>
        <label for="pontorh_create_record"><?php echo app_lang('pontorh_permission_create_record'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('pontorh_request_adjustment', '1', $pontorh_request_adjustment ? true : false, "id='pontorh_request_adjustment' class='form-check-input'"); ?>
        <label for="pontorh_request_adjustment"><?php echo app_lang('pontorh_permission_request_adjustment'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('pontorh_view_team', '1', $pontorh_view_team ? true : false, "id='pontorh_view_team' class='form-check-input'"); ?>
        <label for="pontorh_view_team"><?php echo app_lang('pontorh_permission_view_team'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('pontorh_approve_adjustment', '1', $pontorh_approve_adjustment ? true : false, "id='pontorh_approve_adjustment' class='form-check-input'"); ?>
        <label for="pontorh_approve_adjustment"><?php echo app_lang('pontorh_permission_approve_adjustment'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('pontorh_manage_schedules', '1', $pontorh_manage_schedules ? true : false, "id='pontorh_manage_schedules' class='form-check-input'"); ?>
        <label for="pontorh_manage_schedules"><?php echo app_lang('pontorh_permission_manage_schedules'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('pontorh_view_reports', '1', $pontorh_view_reports ? true : false, "id='pontorh_view_reports' class='form-check-input'"); ?>
        <label for="pontorh_view_reports"><?php echo app_lang('pontorh_permission_view_reports'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('pontorh_manage_settings', '1', $pontorh_manage_settings ? true : false, "id='pontorh_manage_settings' class='form-check-input'"); ?>
        <label for="pontorh_manage_settings"><?php echo app_lang('pontorh_permission_manage_settings'); ?></label>
    </div>
</li>
