<?php
if (defined('LICITAIA_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

$licitaia_view = get_array_value($permissions, 'licitaia_view');
$licitaia_manage = get_array_value($permissions, 'licitaia_manage');
$licitaia_keywords = get_array_value($permissions, 'licitaia_keywords');
$licitaia_sources = get_array_value($permissions, 'licitaia_sources');
$licitaia_checklist = get_array_value($permissions, 'licitaia_checklist');
$licitaia_reports = get_array_value($permissions, 'licitaia_reports');
$licitaia_settings = get_array_value($permissions, 'licitaia_settings');
$licitaia_ai_settings = get_array_value($permissions, 'licitaia_ai_settings');
$licitaia_generate_report = get_array_value($permissions, 'licitaia_generate_report');
$licitaia_delete_records = get_array_value($permissions, 'licitaia_delete_records');
$licitaia_ai = get_array_value($permissions, 'licitaia_ai');
$licitaia_admin = get_array_value($permissions, 'licitaia_admin');
?>
<li>
    <span data-feather="shield" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang('licitaia_permissions'); ?></h5>
    <div><?php echo form_checkbox('licitaia_admin', '1', $licitaia_admin ? true : false, "id='licitaia_admin' class='form-check-input'"); ?> <label for="licitaia_admin"><?php echo app_lang('licitaia_permission_admin'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_view', '1', $licitaia_view ? true : false, "id='licitaia_view' class='form-check-input'"); ?> <label for="licitaia_view"><?php echo app_lang('licitaia_permission_view'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_manage', '1', $licitaia_manage ? true : false, "id='licitaia_manage' class='form-check-input'"); ?> <label for="licitaia_manage"><?php echo app_lang('licitaia_permission_manage'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_keywords', '1', $licitaia_keywords ? true : false, "id='licitaia_keywords' class='form-check-input'"); ?> <label for="licitaia_keywords"><?php echo app_lang('licitaia_permission_keywords'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_sources', '1', $licitaia_sources ? true : false, "id='licitaia_sources' class='form-check-input'"); ?> <label for="licitaia_sources"><?php echo app_lang('licitaia_permission_sources'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_checklist', '1', $licitaia_checklist ? true : false, "id='licitaia_checklist' class='form-check-input'"); ?> <label for="licitaia_checklist"><?php echo app_lang('licitaia_permission_checklist'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_reports', '1', $licitaia_reports ? true : false, "id='licitaia_reports' class='form-check-input'"); ?> <label for="licitaia_reports"><?php echo app_lang('licitaia_permission_reports'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_settings', '1', $licitaia_settings ? true : false, "id='licitaia_settings' class='form-check-input'"); ?> <label for="licitaia_settings"><?php echo app_lang('licitaia_permission_settings'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_ai_settings', '1', $licitaia_ai_settings ? true : false, "id='licitaia_ai_settings' class='form-check-input'"); ?> <label for="licitaia_ai_settings"><?php echo app_lang('licitaia_permission_ai_settings'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_generate_report', '1', $licitaia_generate_report ? true : false, "id='licitaia_generate_report' class='form-check-input'"); ?> <label for="licitaia_generate_report"><?php echo app_lang('licitaia_permission_generate_report'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_delete_records', '1', $licitaia_delete_records ? true : false, "id='licitaia_delete_records' class='form-check-input'"); ?> <label for="licitaia_delete_records"><?php echo app_lang('licitaia_permission_delete_records'); ?></label></div>
    <div><?php echo form_checkbox('licitaia_ai', '1', $licitaia_ai ? true : false, "id='licitaia_ai' class='form-check-input'"); ?> <label for="licitaia_ai"><?php echo app_lang('licitaia_permission_ai'); ?></label></div>
</li>
