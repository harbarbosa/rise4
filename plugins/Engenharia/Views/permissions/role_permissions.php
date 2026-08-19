<?php

if (defined('ENGENHARIA_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

define('ENGENHARIA_ROLE_PERMISSIONS_RENDERED', true);
$labels = array(
    'engenharia_access' => app_lang('engenharia_permission_access'),
    'engenharia_view_laudos' => app_lang('engenharia_permission_view_laudos'),
    'engenharia_create_laudos' => app_lang('engenharia_permission_create_laudos'),
    'engenharia_edit_laudos' => app_lang('engenharia_permission_edit_laudos'),
    'engenharia_inspect_laudos' => app_lang('engenharia_permission_inspect_laudos'),
    'engenharia_review_laudos' => app_lang('engenharia_permission_review_laudos'),
    'engenharia_finalize_laudos' => app_lang('engenharia_permission_finalize_laudos'),
    'engenharia_reopen_laudos' => app_lang('engenharia_permission_reopen_laudos'),
    'engenharia_delete_laudos' => app_lang('engenharia_permission_delete_laudos'),
    'engenharia_manage_checklists' => app_lang('engenharia_permission_manage_checklists'),
    'engenharia_manage_templates' => app_lang('engenharia_permission_manage_templates'),
    'engenharia_manage_settings' => app_lang('engenharia_permission_manage_settings'),
);
?>
<li>
    <span data-feather="tool" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang('engenharia_permissions'); ?></h5>
    <?php foreach ($labels as $key => $label) { ?>
        <div>
            <?php echo form_checkbox($key, '1', !empty($permissions[$key]), "id='$key' class='form-check-input'"); ?>
            <label for="<?php echo esc($key); ?>"><?php echo esc($label); ?></label>
        </div>
    <?php } ?>
</li>
