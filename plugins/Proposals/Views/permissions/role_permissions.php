<?php
$proposals_view = get_array_value($permissions, "proposals_view");
$proposals_manage = get_array_value($permissions, "proposals_manage");
$proposals_export_pdf = get_array_value($permissions, "proposals_export_pdf");
$proposals_settings_manage = get_array_value($permissions, "proposals_settings_manage");
?>

<li>
    <span data-feather="key" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang("proposals_permissions"); ?></h5>
    <div>
        <?php
        echo form_checkbox("proposals_view", "1", $proposals_view ? true : false, "id='proposals_view' class='form-check-input'");
        ?>
        <label for="proposals_view"><?php echo app_lang("proposals_view_permission"); ?></label>
    </div>
    <div>
        <?php
        echo form_checkbox("proposals_manage", "1", $proposals_manage ? true : false, "id='proposals_manage' class='form-check-input'");
        ?>
        <label for="proposals_manage"><?php echo app_lang("proposals_manage_permission"); ?></label>
    </div>
    <div>
        <?php
        echo form_checkbox("proposals_export_pdf", "1", $proposals_export_pdf ? true : false, "id='proposals_export_pdf' class='form-check-input'");
        ?>
        <label for="proposals_export_pdf"><?php echo app_lang("proposals_export_pdf_permission"); ?></label>
    </div>
    <div>
        <?php
        echo form_checkbox("proposals_settings_manage", "1", $proposals_settings_manage ? true : false, "id='proposals_settings_manage' class='form-check-input'");
        ?>
        <label for="proposals_settings_manage"><?php echo app_lang("proposals_settings_manage_permission"); ?></label>
    </div>
</li>