<?php
if (defined('GED_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

$ged_access = get_array_value($permissions, 'ged_access');
$ged_view_documents = get_array_value($permissions, 'ged_view_documents');
$ged_create_documents = get_array_value($permissions, 'ged_create_documents');
$ged_edit_documents = get_array_value($permissions, 'ged_edit_documents');
$ged_delete_documents = get_array_value($permissions, 'ged_delete_documents');
$ged_download_documents = get_array_value($permissions, 'ged_download_documents');
$ged_manage_document_types = get_array_value($permissions, 'ged_manage_document_types');
$ged_view_reports = get_array_value($permissions, 'ged_view_reports');
$ged_manage_settings = get_array_value($permissions, 'ged_manage_settings');
$ged_manage_notifications = get_array_value($permissions, 'ged_manage_notifications');
?>

<li>
    <span data-feather="file-text" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang('ged_permissions_title'); ?></h5>

    <div>
        <?php echo form_checkbox('ged_access', '1', $ged_access ? true : false, "id='ged_access' class='form-check-input'"); ?>
        <label for="ged_access"><?php echo app_lang('ged_permission_access'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_view_documents', '1', $ged_view_documents ? true : false, "id='ged_view_documents' class='form-check-input'"); ?>
        <label for="ged_view_documents"><?php echo app_lang('ged_permission_view_documents'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_create_documents', '1', $ged_create_documents ? true : false, "id='ged_create_documents' class='form-check-input'"); ?>
        <label for="ged_create_documents"><?php echo app_lang('ged_permission_create_documents'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_edit_documents', '1', $ged_edit_documents ? true : false, "id='ged_edit_documents' class='form-check-input'"); ?>
        <label for="ged_edit_documents"><?php echo app_lang('ged_permission_edit_documents'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_delete_documents', '1', $ged_delete_documents ? true : false, "id='ged_delete_documents' class='form-check-input'"); ?>
        <label for="ged_delete_documents"><?php echo app_lang('ged_permission_delete_documents'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_download_documents', '1', $ged_download_documents ? true : false, "id='ged_download_documents' class='form-check-input'"); ?>
        <label for="ged_download_documents"><?php echo app_lang('ged_permission_download_documents'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_manage_document_types', '1', $ged_manage_document_types ? true : false, "id='ged_manage_document_types' class='form-check-input'"); ?>
        <label for="ged_manage_document_types"><?php echo app_lang('ged_permission_manage_document_types'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_view_reports', '1', $ged_view_reports ? true : false, "id='ged_view_reports' class='form-check-input'"); ?>
        <label for="ged_view_reports"><?php echo app_lang('ged_permission_view_reports'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_manage_settings', '1', $ged_manage_settings ? true : false, "id='ged_manage_settings' class='form-check-input'"); ?>
        <label for="ged_manage_settings"><?php echo app_lang('ged_permission_manage_settings'); ?></label>
    </div>

    <div>
        <?php echo form_checkbox('ged_manage_notifications', '1', $ged_manage_notifications ? true : false, "id='ged_manage_notifications' class='form-check-input'"); ?>
        <label for="ged_manage_notifications"><?php echo app_lang('ged_permission_manage_notifications'); ?></label>
    </div>
</li>
