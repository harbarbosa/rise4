<?php
$model_info = $model_info ?? (object) array();
$structure = is_array($model_info->structure ?? null) ? $model_info->structure : array('groups' => array(), 'items' => array());
$groups = is_array(get_array_value($structure, 'groups')) ? get_array_value($structure, 'groups') : array();
$items = is_array(get_array_value($structure, 'items')) ? get_array_value($structure, 'items') : array();

if (!$groups) {
    $groups = array(array('key' => 'geral', 'title' => 'Grupo geral', 'description' => '', 'sort' => 1, 'active' => 1, 'hidden' => 0));
}

if (!$items) {
    $items = array(array('group_key' => 'geral', 'code' => 'ITEM-001', 'question' => 'Verificacao inicial', 'guidance' => '', 'response_type' => 'conforme', 'expected_response' => 'conforme', 'criticality' => 'media', 'weight' => 1, 'required' => 1, 'evidence_required' => 0, 'photo_required' => 0, 'measurement_required' => 0, 'observation_required' => 0, 'related_norm' => '', 'generates_nc' => 0, 'sort' => 1, 'active' => 1));
}
?>
<?php echo form_open(get_uri('laudostecnicos/checklists/save'), array('id' => 'laudostecnicos-checklist-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Nome</label>
            <input type="text" name="name" class="form-control" value="<?php echo esc($model_info->name ?? ''); ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Codigo</label>
            <input type="text" name="code" class="form-control" value="<?php echo esc($model_info->code ?? ''); ?>" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Descricao</label>
            <input type="text" name="description" class="form-control" value="<?php echo esc($model_info->description ?? ''); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Categoria</label>
            <?php echo form_dropdown('category_id', $categories_dropdown, $model_info->category_id ?? '', "class='form-select'"); ?>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <?php echo form_dropdown('type_id', $types_dropdown, $model_info->type_id ?? '', "class='form-select'"); ?>
        </div>
        <div class="col-md-2">
            <label class="form-label">Versao</label>
            <input type="number" name="version" class="form-control" min="1" value="<?php echo esc($model_info->version ?? 1); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <?php echo form_dropdown('status', $status_options, $model_info->status ?? 'draft', "class='form-select'"); ?>
        </div>
        <div class="col-md-2">
            <label class="form-label d-block">Padrao</label>
            <?php echo form_checkbox('is_default', '1', !empty($model_info->is_default), "class='form-check-input' id='checklist_is_default'"); ?>
        </div>
        <div class="col-md-2">
            <label class="form-label d-block">Ativo</label>
            <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "class='form-check-input' id='checklist_is_active'"); ?>
        </div>
    </div>

    <ul class="nav nav-tabs mt-4 mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#checklist-groups">Grupos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#checklist-items">Itens</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#checklist-meta">Responsavel</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="checklist-groups">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <p class="mb-0 text-muted">Configurar grupos do checklist.</p>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-checklist-group">Adicionar grupo</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Chave</th>
                            <th>Titulo</th>
                            <th>Descricao</th>
                            <th>Ordem</th>
                            <th>Ativo</th>
                            <th>Oculto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="checklist-groups-body">
                        <?php foreach ($groups as $index => $group) { ?>
                            <tr>
                                <td><input type="text" name="groups[<?php echo $index; ?>][key]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($group, 'key')); ?>"></td>
                                <td><input type="text" name="groups[<?php echo $index; ?>][title]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($group, 'title')); ?>"></td>
                                <td><input type="text" name="groups[<?php echo $index; ?>][description]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($group, 'description')); ?>"></td>
                                <td><input type="number" name="groups[<?php echo $index; ?>][sort]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($group, 'sort') ?: ($index + 1)); ?>"></td>
                                <td class="text-center"><?php echo form_checkbox("groups[$index][active]", '1', get_array_value($group, 'active', 1) ? true : false, "class='form-check-input'"); ?></td>
                                <td class="text-center"><?php echo form_checkbox("groups[$index][hidden]", '1', !empty(get_array_value($group, 'hidden')), "class='form-check-input'"); ?></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">X</button></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="checklist-items">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <p class="mb-0 text-muted">Configurar itens do checklist.</p>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-checklist-item">Adicionar item</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Codigo</th>
                            <th>Pergunta</th>
                            <th>Orientacao</th>
                            <th>Tipo resposta</th>
                            <th>Resposta esperada</th>
                            <th>Criticidade</th>
                            <th>Peso</th>
                            <th>Obrig.</th>
                            <th>Evid.</th>
                            <th>Foto</th>
                            <th>Med.</th>
                            <th>Obs.</th>
                            <th>Norma</th>
                            <th>NC</th>
                            <th>Ordem</th>
                            <th>Ativo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="checklist-items-body">
                        <?php foreach ($items as $index => $item) { ?>
                            <tr>
                                <td><input type="text" name="items[<?php echo $index; ?>][group_key]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($item, 'group_key')); ?>"></td>
                                <td><input type="text" name="items[<?php echo $index; ?>][code]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($item, 'code')); ?>"></td>
                                <td><input type="text" name="items[<?php echo $index; ?>][question]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($item, 'question')); ?>"></td>
                                <td><input type="text" name="items[<?php echo $index; ?>][guidance]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($item, 'guidance')); ?>"></td>
                                <td><?php echo form_dropdown("items[$index][response_type]", $response_types, get_array_value($item, 'response_type', 'conforme'), "class='form-select form-select-sm'"); ?></td>
                                <td><input type="text" name="items[<?php echo $index; ?>][expected_response]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($item, 'expected_response')); ?>"></td>
                                <td><?php echo form_dropdown("items[$index][criticality]", $criticalities, get_array_value($item, 'criticality', 'media'), "class='form-select form-select-sm'"); ?></td>
                                <td><input type="number" step="0.1" name="items[<?php echo $index; ?>][weight]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($item, 'weight') ?: 1); ?>"></td>
                                <td class="text-center"><?php echo form_checkbox("items[$index][required]", '1', get_array_value($item, 'required', 1) ? true : false, "class='form-check-input'"); ?></td>
                                <td class="text-center"><?php echo form_checkbox("items[$index][evidence_required]", '1', !empty(get_array_value($item, 'evidence_required')), "class='form-check-input'"); ?></td>
                                <td class="text-center"><?php echo form_checkbox("items[$index][photo_required]", '1', !empty(get_array_value($item, 'photo_required')), "class='form-check-input'"); ?></td>
                                <td class="text-center"><?php echo form_checkbox("items[$index][measurement_required]", '1', !empty(get_array_value($item, 'measurement_required')), "class='form-check-input'"); ?></td>
                                <td class="text-center"><?php echo form_checkbox("items[$index][observation_required]", '1', !empty(get_array_value($item, 'observation_required')), "class='form-check-input'"); ?></td>
                                <td><input type="text" name="items[<?php echo $index; ?>][related_norm]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($item, 'related_norm')); ?>"></td>
                                <td class="text-center"><?php echo form_checkbox("items[$index][generates_nc]", '1', !empty(get_array_value($item, 'generates_nc')), "class='form-check-input'"); ?></td>
                                <td><input type="number" name="items[<?php echo $index; ?>][sort]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($item, 'sort') ?: ($index + 1)); ?>"></td>
                                <td class="text-center"><?php echo form_checkbox("items[$index][active]", '1', get_array_value($item, 'active', 1) ? true : false, "class='form-check-input'"); ?></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">X</button></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="checklist-meta">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Responsavel</label>
                    <input type="number" name="responsible_id" class="form-control" value="<?php echo esc($model_info->responsible_id ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="button" id="save-checklist-btn" class="btn btn-primary">Salvar</button>
</div>
<?php echo form_close(); ?>

<template id="checklist-group-row-template">
    <tr>
        <td><input type="text" name="groups[__INDEX__][key]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="groups[__INDEX__][title]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="groups[__INDEX__][description]" class="form-control form-control-sm" value=""></td>
        <td><input type="number" name="groups[__INDEX__][sort]" class="form-control form-control-sm" value=""></td>
        <td class="text-center"><input type="checkbox" name="groups[__INDEX__][active]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="groups[__INDEX__][hidden]" value="1" class="form-check-input"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">X</button></td>
    </tr>
</template>

<template id="checklist-item-row-template">
    <tr>
        <td><input type="text" name="items[__INDEX__][group_key]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="items[__INDEX__][code]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="items[__INDEX__][question]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="items[__INDEX__][guidance]" class="form-control form-control-sm" value=""></td>
        <td><?php echo form_dropdown('items[__INDEX__][response_type]', $response_types, 'conforme', "class='form-select form-select-sm'"); ?></td>
        <td><input type="text" name="items[__INDEX__][expected_response]" class="form-control form-control-sm" value=""></td>
        <td><?php echo form_dropdown('items[__INDEX__][criticality]', $criticalities, 'media', "class='form-select form-select-sm'"); ?></td>
        <td><input type="number" step="0.1" name="items[__INDEX__][weight]" class="form-control form-control-sm" value="1"></td>
        <td class="text-center"><input type="checkbox" name="items[__INDEX__][required]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="items[__INDEX__][evidence_required]" value="1" class="form-check-input"></td>
        <td class="text-center"><input type="checkbox" name="items[__INDEX__][photo_required]" value="1" class="form-check-input"></td>
        <td class="text-center"><input type="checkbox" name="items[__INDEX__][measurement_required]" value="1" class="form-check-input"></td>
        <td class="text-center"><input type="checkbox" name="items[__INDEX__][observation_required]" value="1" class="form-check-input"></td>
        <td><input type="text" name="items[__INDEX__][related_norm]" class="form-control form-control-sm" value=""></td>
        <td class="text-center"><input type="checkbox" name="items[__INDEX__][generates_nc]" value="1" class="form-check-input"></td>
        <td><input type="number" name="items[__INDEX__][sort]" class="form-control form-control-sm" value=""></td>
        <td class="text-center"><input type="checkbox" name="items[__INDEX__][active]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">X</button></td>
    </tr>
</template>

<script type="text/javascript">
    $(function () {
        function nextIndex(selector) {
            var maxIndex = -1;
            $(selector + " input[name]").each(function () {
                var match = this.name.match(/\[(\d+)\]/);
                if (match) {
                    maxIndex = Math.max(maxIndex, parseInt(match[1], 10));
                }
            });
            return maxIndex + 1;
        }

        $("#add-checklist-group").on("click", function () {
            var index = nextIndex("#checklist-groups-body");
            $("#checklist-groups-body").append($("#checklist-group-row-template").html().replace(/__INDEX__/g, index));
        });

        $("#add-checklist-item").on("click", function () {
            var index = nextIndex("#checklist-items-body");
            $("#checklist-items-body").append($("#checklist-item-row-template").html().replace(/__INDEX__/g, index));
        });

        $(document).on("click", ".remove-row", function () {
            $(this).closest("tr").remove();
        });

        $("#save-checklist-btn").on("click", function () {
            $("#laudostecnicos-checklist-form").trigger("submit");
        });

        $("#laudostecnicos-checklist-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    appAlert.success(result.message || "Salvo com sucesso.");
                    $("#laudostecnicos-checklists-table").appTable({newData: result});
                }
            }
        });
    });
</script>
