<?php
$ordemservico_manage = get_array_value($permissions, "ordemservico_manage");
?>

<li>
    <span data-feather="key" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang("ordemservico_permissions"); ?></h5>
    <div>
        <?php
        echo form_checkbox("ordemservico_manage", "1", $ordemservico_manage ? true : false, "id='ordemservico_manage' class='form-check-input'");
        ?>
        <label for="ordemservico_manage"><?php echo app_lang("ordemservico_manage_permission"); ?></label>
    </div>
</li>
