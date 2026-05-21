<?php

if (defined('FOTOVOLTAICO_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

$fotovoltaico_view = get_array_value($permissions, 'fotovoltaico_view');
$fotovoltaico_manage = get_array_value($permissions, 'fotovoltaico_manage');
$fotovoltaico_products_view = get_array_value($permissions, 'fotovoltaico_products_view');
$fotovoltaico_products_manage = get_array_value($permissions, 'fotovoltaico_products_manage');
$fotovoltaico_kits_view = get_array_value($permissions, 'fotovoltaico_kits_view');
$fotovoltaico_kits_manage = get_array_value($permissions, 'fotovoltaico_kits_manage');
$fotovoltaico_proposals_view = get_array_value($permissions, 'fotovoltaico_proposals_view');
$fotovoltaico_proposals_create = get_array_value($permissions, 'fotovoltaico_proposals_create');
$fotovoltaico_proposals_manage = get_array_value($permissions, 'fotovoltaico_proposals_manage');
$fotovoltaico_proposals_approve = get_array_value($permissions, 'fotovoltaico_proposals_approve');
$fotovoltaico_tariffs_view = get_array_value($permissions, 'fotovoltaico_tariffs_view');
$fotovoltaico_tariffs_manage = get_array_value($permissions, 'fotovoltaico_tariffs_manage');
$fotovoltaico_integrations_view = get_array_value($permissions, 'fotovoltaico_integrations_view');
$fotovoltaico_integrations_manage = get_array_value($permissions, 'fotovoltaico_integrations_manage');
$fotovoltaico_belenus_view = get_array_value($permissions, 'fotovoltaico_belenus_view');
$fotovoltaico_belenus_manage = get_array_value($permissions, 'fotovoltaico_belenus_manage');
$fotovoltaico_pdf_generate = get_array_value($permissions, 'fotovoltaico_pdf_generate');
$fotovoltaico_audit_view = get_array_value($permissions, 'fotovoltaico_audit_view');
$fotovoltaico_admin = get_array_value($permissions, 'fotovoltaico_admin');
?>

<li>
    <span data-feather="sun" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang('fotovoltaico_permissions'); ?></h5>
    <div class="mt10"><strong><?php echo app_lang('fotovoltaico_permissions_general'); ?></strong></div>
    <div>
        <?php echo form_checkbox('fotovoltaico_view', '1', $fotovoltaico_view ? true : false, "id='fotovoltaico_view' class='form-check-input'"); ?>
        <label for="fotovoltaico_view"><?php echo app_lang('fotovoltaico_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_manage', '1', $fotovoltaico_manage ? true : false, "id='fotovoltaico_manage' class='form-check-input'"); ?>
        <label for="fotovoltaico_manage"><?php echo app_lang('fotovoltaico_manage_permission'); ?></label>
    </div>
    <div class="mt10"><strong><?php echo app_lang('fotovoltaico_permissions_products'); ?></strong></div>
    <div>
        <?php echo form_checkbox('fotovoltaico_products_view', '1', $fotovoltaico_products_view ? true : false, "id='fotovoltaico_products_view' class='form-check-input'"); ?>
        <label for="fotovoltaico_products_view"><?php echo app_lang('fotovoltaico_products_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_products_manage', '1', $fotovoltaico_products_manage ? true : false, "id='fotovoltaico_products_manage' class='form-check-input'"); ?>
        <label for="fotovoltaico_products_manage"><?php echo app_lang('fotovoltaico_products_manage_permission'); ?></label>
    </div>
    <div class="mt10"><strong><?php echo app_lang('fotovoltaico_permissions_kits'); ?></strong></div>
    <div>
        <?php echo form_checkbox('fotovoltaico_kits_view', '1', $fotovoltaico_kits_view ? true : false, "id='fotovoltaico_kits_view' class='form-check-input'"); ?>
        <label for="fotovoltaico_kits_view"><?php echo app_lang('fotovoltaico_kits_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_kits_manage', '1', $fotovoltaico_kits_manage ? true : false, "id='fotovoltaico_kits_manage' class='form-check-input'"); ?>
        <label for="fotovoltaico_kits_manage"><?php echo app_lang('fotovoltaico_kits_manage_permission'); ?></label>
    </div>
    <div class="mt10"><strong><?php echo app_lang('fotovoltaico_permissions_proposals'); ?></strong></div>
    <div>
        <?php echo form_checkbox('fotovoltaico_proposals_view', '1', $fotovoltaico_proposals_view ? true : false, "id='fotovoltaico_proposals_view' class='form-check-input'"); ?>
        <label for="fotovoltaico_proposals_view"><?php echo app_lang('fotovoltaico_proposals_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_proposals_create', '1', $fotovoltaico_proposals_create ? true : false, "id='fotovoltaico_proposals_create' class='form-check-input'"); ?>
        <label for="fotovoltaico_proposals_create"><?php echo app_lang('fotovoltaico_proposals_create_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_proposals_manage', '1', $fotovoltaico_proposals_manage ? true : false, "id='fotovoltaico_proposals_manage' class='form-check-input'"); ?>
        <label for="fotovoltaico_proposals_manage"><?php echo app_lang('fotovoltaico_proposals_manage_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_proposals_approve', '1', $fotovoltaico_proposals_approve ? true : false, "id='fotovoltaico_proposals_approve' class='form-check-input'"); ?>
        <label for="fotovoltaico_proposals_approve"><?php echo app_lang('fotovoltaico_proposals_approve_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_pdf_generate', '1', $fotovoltaico_pdf_generate ? true : false, "id='fotovoltaico_pdf_generate' class='form-check-input'"); ?>
        <label for="fotovoltaico_pdf_generate"><?php echo app_lang('fotovoltaico_pdf_generate_permission'); ?></label>
    </div>
    <div class="mt10"><strong><?php echo app_lang('fotovoltaico_permissions_tariffs'); ?></strong></div>
    <div>
        <?php echo form_checkbox('fotovoltaico_tariffs_view', '1', $fotovoltaico_tariffs_view ? true : false, "id='fotovoltaico_tariffs_view' class='form-check-input'"); ?>
        <label for="fotovoltaico_tariffs_view"><?php echo app_lang('fotovoltaico_tariffs_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_tariffs_manage', '1', $fotovoltaico_tariffs_manage ? true : false, "id='fotovoltaico_tariffs_manage' class='form-check-input'"); ?>
        <label for="fotovoltaico_tariffs_manage"><?php echo app_lang('fotovoltaico_tariffs_manage_permission'); ?></label>
    </div>
    <div class="mt10"><strong><?php echo app_lang('fotovoltaico_permissions_integrations'); ?></strong></div>
    <div>
        <?php echo form_checkbox('fotovoltaico_integrations_view', '1', $fotovoltaico_integrations_view ? true : false, "id='fotovoltaico_integrations_view' class='form-check-input'"); ?>
        <label for="fotovoltaico_integrations_view"><?php echo app_lang('fotovoltaico_integrations_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_integrations_manage', '1', $fotovoltaico_integrations_manage ? true : false, "id='fotovoltaico_integrations_manage' class='form-check-input'"); ?>
        <label for="fotovoltaico_integrations_manage"><?php echo app_lang('fotovoltaico_integrations_manage_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_belenus_view', '1', $fotovoltaico_belenus_view ? true : false, "id='fotovoltaico_belenus_view' class='form-check-input'"); ?>
        <label for="fotovoltaico_belenus_view"><?php echo app_lang('fotovoltaico_belenus_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_belenus_manage', '1', $fotovoltaico_belenus_manage ? true : false, "id='fotovoltaico_belenus_manage' class='form-check-input'"); ?>
        <label for="fotovoltaico_belenus_manage"><?php echo app_lang('fotovoltaico_belenus_manage_permission'); ?></label>
    </div>
    <div class="mt10"><strong><?php echo app_lang('fotovoltaico_permissions_admin'); ?></strong></div>
    <div>
        <?php echo form_checkbox('fotovoltaico_audit_view', '1', $fotovoltaico_audit_view ? true : false, "id='fotovoltaico_audit_view' class='form-check-input'"); ?>
        <label for="fotovoltaico_audit_view"><?php echo app_lang('fotovoltaico_audit_view_permission'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('fotovoltaico_admin', '1', $fotovoltaico_admin ? true : false, "id='fotovoltaico_admin' class='form-check-input'"); ?>
        <label for="fotovoltaico_admin"><?php echo app_lang('fotovoltaico_admin_permission'); ?></label>
    </div>
</li>
