<?php
if (defined('TRAVELREFUNDS_ROLE_PERMISSIONS_RENDERED')) {
    return;
}

$travelrefunds_view = get_array_value($permissions, 'travelrefunds_view');
$travelrefunds_create = get_array_value($permissions, 'travelrefunds_create');
$travelrefunds_edit = get_array_value($permissions, 'travelrefunds_edit');
$travelrefunds_delete = get_array_value($permissions, 'travelrefunds_delete');
$travelrefunds_approve = get_array_value($permissions, 'travelrefunds_approve');
$travelrefunds_manage_settings = get_array_value($permissions, 'travelrefunds_manage_settings');
?>

<li>
    <span data-feather="map" class="icon-14 ml-20"></span>
    <h5>TravelRefunds</h5>

    <div>
        <?php echo form_checkbox('travelrefunds_view', '1', $travelrefunds_view ? true : false, "id='travelrefunds_view' class='form-check-input'"); ?>
        <label for="travelrefunds_view">Visualizar viagens e reembolsos</label>
    </div>

    <div>
        <?php echo form_checkbox('travelrefunds_create', '1', $travelrefunds_create ? true : false, "id='travelrefunds_create' class='form-check-input'"); ?>
        <label for="travelrefunds_create">Criar viagens e reembolsos</label>
    </div>

    <div>
        <?php echo form_checkbox('travelrefunds_edit', '1', $travelrefunds_edit ? true : false, "id='travelrefunds_edit' class='form-check-input'"); ?>
        <label for="travelrefunds_edit">Editar viagens e reembolsos</label>
    </div>

    <div>
        <?php echo form_checkbox('travelrefunds_delete', '1', $travelrefunds_delete ? true : false, "id='travelrefunds_delete' class='form-check-input'"); ?>
        <label for="travelrefunds_delete">Excluir viagens e reembolsos</label>
    </div>

    <div>
        <?php echo form_checkbox('travelrefunds_approve', '1', $travelrefunds_approve ? true : false, "id='travelrefunds_approve' class='form-check-input'"); ?>
        <label for="travelrefunds_approve">Aprovar reembolsos</label>
    </div>

    <div>
        <?php echo form_checkbox('travelrefunds_manage_settings', '1', $travelrefunds_manage_settings ? true : false, "id='travelrefunds_manage_settings' class='form-check-input'"); ?>
        <label for="travelrefunds_manage_settings">Gerenciar configuracoes</label>
    </div>
</li>
