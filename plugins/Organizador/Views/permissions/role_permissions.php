<?php

if (defined('ORGANIZADOR_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

$mytasks_view = get_array_value($permissions, 'mytasks_view');
$mytasks_add = get_array_value($permissions, 'mytasks_add');
$mytasks_edit = get_array_value($permissions, 'mytasks_edit');
$mytasks_delete = get_array_value($permissions, 'mytasks_delete');
$mytasks_view_all = get_array_value($permissions, 'mytasks_view_all');
$mytasks_manage_categories = get_array_value($permissions, 'mytasks_manage_categories');
$mytasks_manage_tags = get_array_value($permissions, 'mytasks_manage_tags');
$mytasks_manage_phases = get_array_value($permissions, 'mytasks_manage_phases');
$mytasks_manage_settings = get_array_value($permissions, 'mytasks_manage_settings');
?>

<li>
    <span data-feather="check-square" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang('organizador_permissions'); ?></h5>
    <div>
        <?php echo form_checkbox('mytasks_view', '1', $mytasks_view ? true : false, "id='mytasks_view' class='form-check-input'"); ?>
        <label for="mytasks_view"><?php echo app_lang('organizador_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('mytasks_add', '1', $mytasks_add ? true : false, "id='mytasks_add' class='form-check-input'"); ?>
        <label for="mytasks_add"><?php echo app_lang('organizador_add_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('mytasks_edit', '1', $mytasks_edit ? true : false, "id='mytasks_edit' class='form-check-input'"); ?>
        <label for="mytasks_edit"><?php echo app_lang('organizador_edit_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('mytasks_delete', '1', $mytasks_delete ? true : false, "id='mytasks_delete' class='form-check-input'"); ?>
        <label for="mytasks_delete"><?php echo app_lang('organizador_delete_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('mytasks_view_all', '1', $mytasks_view_all ? true : false, "id='mytasks_view_all' class='form-check-input'"); ?>
        <label for="mytasks_view_all"><?php echo app_lang('organizador_view_all_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('mytasks_manage_categories', '1', $mytasks_manage_categories ? true : false, "id='mytasks_manage_categories' class='form-check-input'"); ?>
        <label for="mytasks_manage_categories"><?php echo app_lang('organizador_manage_categories_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('mytasks_manage_tags', '1', $mytasks_manage_tags ? true : false, "id='mytasks_manage_tags' class='form-check-input'"); ?>
        <label for="mytasks_manage_tags"><?php echo app_lang('organizador_manage_tags_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('mytasks_manage_phases', '1', $mytasks_manage_phases ? true : false, "id='mytasks_manage_phases' class='form-check-input'"); ?>
        <label for="mytasks_manage_phases"><?php echo app_lang('organizador_manage_phases_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('mytasks_manage_settings', '1', $mytasks_manage_settings ? true : false, "id='mytasks_manage_settings' class='form-check-input'"); ?>
        <label for="mytasks_manage_settings"><?php echo app_lang('organizador_manage_settings_permission'); ?></label>
    </div>
</li>
