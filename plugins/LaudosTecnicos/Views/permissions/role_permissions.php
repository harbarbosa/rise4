<?php

if (defined('LAUDOS_TECNICOS_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

$laudos_view = get_array_value($permissions, 'laudos_view');
$laudos_create = get_array_value($permissions, 'laudos_create');
$laudos_edit = get_array_value($permissions, 'laudos_edit');
$laudos_delete_draft = get_array_value($permissions, 'laudos_delete_draft');
$laudos_manage_types = get_array_value($permissions, 'laudos_manage_types');
$laudos_manage_templates = get_array_value($permissions, 'laudos_manage_templates');
$laudos_manage_categories = get_array_value($permissions, 'laudos_manage_categories');
$laudos_manage_status = get_array_value($permissions, 'laudos_manage_status');
$laudos_manage_transitions = get_array_value($permissions, 'laudos_manage_transitions');
$laudos_change_status = get_array_value($permissions, 'laudos_change_status');
$laudos_settings = get_array_value($permissions, 'laudos_settings');
?>

<li>
    <span data-feather="file-text" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang('laudos_permissions'); ?></h5>
    <div>
        <?php echo form_checkbox('laudos_view', '1', $laudos_view ? true : false, "id='laudos_view' class='form-check-input'"); ?>
        <label for="laudos_view"><?php echo app_lang('laudos_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_create', '1', $laudos_create ? true : false, "id='laudos_create' class='form-check-input'"); ?>
        <label for="laudos_create"><?php echo app_lang('laudos_create_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_edit', '1', $laudos_edit ? true : false, "id='laudos_edit' class='form-check-input'"); ?>
        <label for="laudos_edit"><?php echo app_lang('laudos_edit_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_delete_draft', '1', $laudos_delete_draft ? true : false, "id='laudos_delete_draft' class='form-check-input'"); ?>
        <label for="laudos_delete_draft"><?php echo app_lang('laudos_delete_draft_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_change_status', '1', $laudos_change_status ? true : false, "id='laudos_change_status' class='form-check-input'"); ?>
        <label for="laudos_change_status"><?php echo app_lang('laudos_change_status_permission'); ?></label>
    </div>
    <hr style="margin: 10px 0; opacity: 0.3;">
    <div>
        <?php echo form_checkbox('laudos_manage_categories', '1', $laudos_manage_categories ? true : false, "id='laudos_manage_categories' class='form-check-input'"); ?>
        <label for="laudos_manage_categories"><?php echo app_lang('laudos_manage_categories_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_manage_types', '1', $laudos_manage_types ? true : false, "id='laudos_manage_types' class='form-check-input'"); ?>
        <label for="laudos_manage_types"><?php echo app_lang('laudos_manage_types_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_manage_status', '1', $laudos_manage_status ? true : false, "id='laudos_manage_status' class='form-check-input'"); ?>
        <label for="laudos_manage_status"><?php echo app_lang('laudos_manage_status_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_manage_transitions', '1', $laudos_manage_transitions ? true : false, "id='laudos_manage_transitions' class='form-check-input'"); ?>
        <label for="laudos_manage_transitions"><?php echo app_lang('laudos_manage_transitions_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_manage_templates', '1', $laudos_manage_templates ? true : false, "id='laudos_manage_templates' class='form-check-input'"); ?>
        <label for="laudos_manage_templates"><?php echo app_lang('laudos_manage_templates_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudos_settings', '1', $laudos_settings ? true : false, "id='laudos_settings' class='form-check-input'"); ?>
        <label for="laudos_settings"><?php echo app_lang('laudos_settings_permission'); ?></label>
    </div>
</li>