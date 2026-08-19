<?php

if (defined('LAUDOSTECNICOS_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

$laudostecnicos_access = get_array_value($permissions, 'laudostecnicos_access');
$laudostecnicos_view_dashboard = get_array_value($permissions, 'laudostecnicos_view_dashboard');
$laudostecnicos_view_laudos = get_array_value($permissions, 'laudostecnicos_view_laudos');
$laudostecnicos_create_laudos = get_array_value($permissions, 'laudostecnicos_create_laudos');
$laudostecnicos_edit_laudos = get_array_value($permissions, 'laudostecnicos_edit_laudos');
$laudostecnicos_change_status = get_array_value($permissions, 'laudostecnicos_change_status');
$laudostecnicos_delete_drafts = get_array_value($permissions, 'laudostecnicos_delete_drafts');
$laudostecnicos_view_inspections = get_array_value($permissions, 'laudostecnicos_view_inspections');
$laudostecnicos_manage_categories = get_array_value($permissions, 'laudostecnicos_manage_categories');
$laudostecnicos_manage_types = get_array_value($permissions, 'laudostecnicos_manage_types');
$laudostecnicos_manage_statuses = get_array_value($permissions, 'laudostecnicos_manage_statuses');
$laudostecnicos_manage_transitions = get_array_value($permissions, 'laudostecnicos_manage_transitions');
$laudostecnicos_manage_templates = get_array_value($permissions, 'laudostecnicos_manage_templates');
$laudostecnicos_manage_checklists = get_array_value($permissions, 'laudostecnicos_manage_checklists');
$laudostecnicos_manage_measurements = get_array_value($permissions, 'laudostecnicos_manage_measurements');
$laudostecnicos_manage_equipments = get_array_value($permissions, 'laudostecnicos_manage_equipments');
$laudostecnicos_manage_norms = get_array_value($permissions, 'laudostecnicos_manage_norms');
$laudostecnicos_manage_inspections = get_array_value($permissions, 'laudostecnicos_manage_inspections');
$laudostecnicos_manage_nonconformities = get_array_value($permissions, 'laudostecnicos_manage_nonconformities');
$laudostecnicos_manage_risk_matrix = get_array_value($permissions, 'laudostecnicos_manage_risk_matrix');
$laudostecnicos_manage_action_plans = get_array_value($permissions, 'laudostecnicos_manage_action_plans');
$laudostecnicos_manage_settings = get_array_value($permissions, 'laudostecnicos_manage_settings');
$laudostecnicos_manage_api = get_array_value($permissions, 'laudostecnicos_manage_api');
$laudostecnicos_manage_ai = get_array_value($permissions, 'laudostecnicos_manage_ai');
$laudostecnicos_view_reports = get_array_value($permissions, 'laudostecnicos_view_reports');
$laudostecnicos_manage_reports = get_array_value($permissions, 'laudostecnicos_manage_reports');
$laudostecnicos_manage_automations = get_array_value($permissions, 'laudostecnicos_manage_automations');
?>

<li>
    <span data-feather="clipboard" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang('laudostecnicos_permissions'); ?></h5>
    <div>
        <?php echo form_checkbox('laudostecnicos_access', '1', $laudostecnicos_access ? true : false, "id='laudostecnicos_access' class='form-check-input'"); ?>
        <label for="laudostecnicos_access"><?php echo app_lang('laudostecnicos_permission_access'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_view_dashboard', '1', $laudostecnicos_view_dashboard ? true : false, "id='laudostecnicos_view_dashboard' class='form-check-input'"); ?>
        <label for="laudostecnicos_view_dashboard"><?php echo app_lang('laudostecnicos_permission_view_dashboard'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_view_laudos', '1', $laudostecnicos_view_laudos ? true : false, "id='laudostecnicos_view_laudos' class='form-check-input'"); ?>
        <label for="laudostecnicos_view_laudos"><?php echo app_lang('laudostecnicos_permission_view_laudos'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_create_laudos', '1', $laudostecnicos_create_laudos ? true : false, "id='laudostecnicos_create_laudos' class='form-check-input'"); ?>
        <label for="laudostecnicos_create_laudos"><?php echo app_lang('laudostecnicos_permission_create_laudos'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_edit_laudos', '1', $laudostecnicos_edit_laudos ? true : false, "id='laudostecnicos_edit_laudos' class='form-check-input'"); ?>
        <label for="laudostecnicos_edit_laudos"><?php echo app_lang('laudostecnicos_permission_edit_laudos'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_change_status', '1', $laudostecnicos_change_status ? true : false, "id='laudostecnicos_change_status' class='form-check-input'"); ?>
        <label for="laudostecnicos_change_status"><?php echo app_lang('laudostecnicos_permission_change_status'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_delete_drafts', '1', $laudostecnicos_delete_drafts ? true : false, "id='laudostecnicos_delete_drafts' class='form-check-input'"); ?>
        <label for="laudostecnicos_delete_drafts"><?php echo app_lang('laudostecnicos_permission_delete_drafts'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_view_inspections', '1', $laudostecnicos_view_inspections ? true : false, "id='laudostecnicos_view_inspections' class='form-check-input'"); ?>
        <label for="laudostecnicos_view_inspections"><?php echo app_lang('laudostecnicos_permission_view_inspections'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_categories', '1', $laudostecnicos_manage_categories ? true : false, "id='laudostecnicos_manage_categories' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_categories"><?php echo app_lang('laudostecnicos_permission_manage_categories'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_types', '1', $laudostecnicos_manage_types ? true : false, "id='laudostecnicos_manage_types' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_types"><?php echo app_lang('laudostecnicos_permission_manage_types'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_statuses', '1', $laudostecnicos_manage_statuses ? true : false, "id='laudostecnicos_manage_statuses' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_statuses"><?php echo app_lang('laudostecnicos_permission_manage_statuses'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_transitions', '1', $laudostecnicos_manage_transitions ? true : false, "id='laudostecnicos_manage_transitions' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_transitions"><?php echo app_lang('laudostecnicos_permission_manage_transitions'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_templates', '1', $laudostecnicos_manage_templates ? true : false, "id='laudostecnicos_manage_templates' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_templates"><?php echo app_lang('laudostecnicos_permission_manage_templates'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_checklists', '1', $laudostecnicos_manage_checklists ? true : false, "id='laudostecnicos_manage_checklists' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_checklists"><?php echo app_lang('laudostecnicos_permission_manage_checklists'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_measurements', '1', $laudostecnicos_manage_measurements ? true : false, "id='laudostecnicos_manage_measurements' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_measurements"><?php echo app_lang('laudostecnicos_permission_manage_measurements'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_equipments', '1', $laudostecnicos_manage_equipments ? true : false, "id='laudostecnicos_manage_equipments' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_equipments"><?php echo app_lang('laudostecnicos_permission_manage_equipments'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_norms', '1', $laudostecnicos_manage_norms ? true : false, "id='laudostecnicos_manage_norms' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_norms"><?php echo app_lang('laudostecnicos_permission_manage_norms'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_inspections', '1', $laudostecnicos_manage_inspections ? true : false, "id='laudostecnicos_manage_inspections' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_inspections"><?php echo app_lang('laudostecnicos_permission_manage_inspections'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_nonconformities', '1', $laudostecnicos_manage_nonconformities ? true : false, "id='laudostecnicos_manage_nonconformities' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_nonconformities"><?php echo app_lang('laudostecnicos_permission_manage_nonconformities'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_risk_matrix', '1', $laudostecnicos_manage_risk_matrix ? true : false, "id='laudostecnicos_manage_risk_matrix' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_risk_matrix"><?php echo app_lang('laudostecnicos_permission_manage_risk_matrix'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_action_plans', '1', $laudostecnicos_manage_action_plans ? true : false, "id='laudostecnicos_manage_action_plans' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_action_plans"><?php echo app_lang('laudostecnicos_permission_manage_action_plans'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_settings', '1', $laudostecnicos_manage_settings ? true : false, "id='laudostecnicos_manage_settings' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_settings"><?php echo app_lang('laudostecnicos_permission_manage_settings'); ?></label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_api', '1', $laudostecnicos_manage_api ? true : false, "id='laudostecnicos_manage_api' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_api">Gerenciar API</label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_ai', '1', $laudostecnicos_manage_ai ? true : false, "id='laudostecnicos_manage_ai' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_ai">Gerenciar IA</label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_view_reports', '1', $laudostecnicos_view_reports ? true : false, "id='laudostecnicos_view_reports' class='form-check-input'"); ?>
        <label for="laudostecnicos_view_reports">Visualizar relatorios</label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_reports', '1', $laudostecnicos_manage_reports ? true : false, "id='laudostecnicos_manage_reports' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_reports">Gerenciar relatorios</label>
    </div>
    <div>
        <?php echo form_checkbox('laudostecnicos_manage_automations', '1', $laudostecnicos_manage_automations ? true : false, "id='laudostecnicos_manage_automations' class='form-check-input'"); ?>
        <label for="laudostecnicos_manage_automations">Gerenciar automacoes</label>
    </div>
</li>
