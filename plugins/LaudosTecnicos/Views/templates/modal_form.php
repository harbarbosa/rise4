<?php
$model_info = $model_info ?? (object) array();
$structure = is_array($model_info->structure ?? null) ? $model_info->structure : array();
$sections = is_array(get_array_value($structure, 'sections')) ? get_array_value($structure, 'sections') : array();
$fields = is_array(get_array_value($structure, 'fields')) ? get_array_value($structure, 'fields') : array();
$rules = is_array(get_array_value($structure, 'rules')) ? get_array_value($structure, 'rules') : array();
$types_dropdown = is_array($types_dropdown ?? null) ? $types_dropdown : array();
$categories_dropdown = is_array($categories_dropdown ?? null) ? $categories_dropdown : array();
$status_options = is_array($status_options ?? null) ? $status_options : array();

if (!$sections) {
    $sections = array(array('key' => 'identificacao', 'title' => 'Identificacao', 'description' => '', 'sort' => 1, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0));
}

if (!$fields) {
    $fields = array(array('key' => 'title', 'section_key' => 'identificacao', 'type' => 'text', 'name' => 'title', 'label' => 'Titulo', 'description' => '', 'placeholder' => '', 'default_value' => '', 'required' => 1, 'sort' => 1, 'width' => '12', 'validation' => '', 'mask' => '', 'help' => '', 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'read_only' => 0, 'generated_ai' => 0));
}

if (!$rules) {
    $rules = array(array('name' => 'Foto quando nao conforme', 'trigger_field' => 'resultado', 'operator' => 'equals', 'trigger_value' => 'nao_conforme', 'action_type' => 'require_field', 'action_target' => 'foto', 'message' => '', 'sort' => 1, 'active' => 1));
}

$field_types = array(
    'text' => 'Texto simples',
    'text_long' => 'Texto longo',
    'rich_text' => 'Texto rico',
    'number' => 'Numero',
    'decimal' => 'Decimal',
    'currency' => 'Moeda',
    'percentage' => 'Percentual',
    'date' => 'Data',
    'time' => 'Hora',
    'datetime' => 'Data e hora',
    'yes_no' => 'Sim ou nao',
    'single_select' => 'Selecao unica',
    'multi_select' => 'Selecao multipla',
    'checkbox' => 'Checkbox',
    'dynamic_list' => 'Lista dinamica',
    'image' => 'Imagem',
    'file' => 'Arquivo',
    'signature' => 'Assinatura',
    'gps' => 'Localizacao GPS',
    'measurement' => 'Medicao',
    'dynamic_table' => 'Tabela dinamica',
    'calculated' => 'Campo calculado',
    'readonly' => 'Campo somente leitura',
    'ai_text' => 'Texto gerado por IA',
);

$operators = array(
    'equals' => 'Igual',
    'not_equals' => 'Diferente',
    'contains' => 'Contem',
    'not_contains' => 'Nao contem',
    'empty' => 'Vazio',
    'not_empty' => 'Nao vazio',
    'gt' => 'Maior que',
    'gte' => 'Maior ou igual',
    'lt' => 'Menor que',
    'lte' => 'Menor ou igual',
);

$actions = array(
    'show_field' => 'Exibir campo',
    'hide_field' => 'Ocultar campo',
    'require_field' => 'Exigir campo',
    'show_section' => 'Exibir secao',
    'hide_section' => 'Ocultar secao',
    'create_nc' => 'Criar nao conformidade',
    'block_progress' => 'Bloquear avanco',
    'classify_measurement' => 'Classificar medicao',
);
?>

<div class="modal-body clearfix">
    <?php echo form_open(get_uri('laudostecnicos/templates/save'), array('id' => 'laudostecnicos-template-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <input type="hidden" name="id" value="<?php echo esc($model_info->id ?? ''); ?>">
        <input type="hidden" name="template_key" value="<?php echo esc($model_info->template_key ?? ''); ?>">
        <input type="hidden" name="version" value="<?php echo esc($model_info->version ?? 1); ?>">
        <div class="row">
            <div class="col-md-12">
                <?php if (($model_info->status ?? '') === 'published') { ?>
                    <div class="alert alert-warning">
                        Versao publicada. Alteracoes salvas aqui criarao uma nova versao automaticamente.
                    </div>
                <?php } ?>
                <ul class="nav nav-tabs mb20" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#template-info">Informacoes</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#template-structure">Estrutura</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#template-preview">Pre-visualizacao</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="template-info">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nome</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo esc($model_info->name ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Codigo</label>
                                <input type="text" name="code" class="form-control" value="<?php echo esc($model_info->code ?? ''); ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Descricao</label>
                                <input type="text" name="description" class="form-control" value="<?php echo esc($model_info->description ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo de laudo</label>
                                <?php echo form_dropdown('type_id', $types_dropdown, $model_info->type_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoria</label>
                                <?php echo form_dropdown('category_id', $categories_dropdown, $model_info->category_id ?? '', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Versao</label>
                                <input type="number" name="version" class="form-control" min="1" step="1" value="<?php echo esc($model_info->version ?? 1); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <?php echo form_dropdown('status', $status_options, $model_info->status ?? 'draft', "class='form-select'"); ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">Ativo</label>
                                <?php echo form_checkbox('is_active', '1', !empty($model_info->is_active), "class='form-check-input' id='template_is_active'"); ?>
                                <label for="template_is_active" class="form-check-label ms-1">Sim</label>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">Padrao</label>
                                <?php echo form_checkbox('is_default', '1', !empty($model_info->is_default), "class='form-check-input' id='template_is_default'"); ?>
                                <label for="template_is_default" class="form-check-label ms-1">Sim</label>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Publicado em</label>
                                <input type="text" class="form-control" value="<?php echo esc($model_info->published_at ?? '-'); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="template-structure">
                        <ul class="nav nav-pills mb-3" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#template-sections-pane">Seccoes</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#template-fields-pane">Campos</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#template-rules-pane">Regras</a></li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="template-sections-pane">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="mb-0 text-muted">Adicionar seccoes, reordenar e configurar visibilidade.</p>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-template-section">Adicionar seccao</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width:32px;"></th>
                                                <th>Chave</th>
                                                <th>Titulo</th>
                                                <th>Descricao</th>
                                                <th style="width:90px;">Ordem</th>
                                                <th style="width:90px;">Quebra</th>
                                                <th style="width:90px;">Num.</th>
                                                <th style="width:90px;">Web</th>
                                                <th style="width:90px;">Mobile</th>
                                                <th style="width:90px;">PDF</th>
                                                <th style="width:90px;">Obrig.</th>
                                                <th style="width:90px;">Oculta</th>
                                                <th style="width:70px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="template-sections-body">
                                            <?php foreach ($sections as $index => $section) { ?>
                                                <tr class="builder-row" draggable="true">
                                                    <td class="text-center"><i data-feather="move" class="icon-16 text-muted drag-handle"></i></td>
                                                    <td><input type="text" name="sections[<?php echo $index; ?>][key]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($section, 'key')); ?>"></td>
                                                    <td><input type="text" name="sections[<?php echo $index; ?>][title]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($section, 'title')); ?>"></td>
                                                    <td><input type="text" name="sections[<?php echo $index; ?>][description]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($section, 'description')); ?>"></td>
                                                    <td><input type="number" name="sections[<?php echo $index; ?>][sort]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($section, 'sort') ?: ($index + 1)); ?>"></td>
                                                    <td class="text-center"><?php echo form_checkbox("sections[$index][page_break]", '1', !empty(get_array_value($section, 'page_break')), "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("sections[$index][numbering]", '1', !empty(get_array_value($section, 'numbering', 1)), "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("sections[$index][visible_web]", '1', get_array_value($section, 'visible_web', 1) ? true : false, "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("sections[$index][visible_mobile]", '1', get_array_value($section, 'visible_mobile', 1) ? true : false, "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("sections[$index][visible_pdf]", '1', get_array_value($section, 'visible_pdf', 1) ? true : false, "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("sections[$index][required]", '1', !empty(get_array_value($section, 'required')), "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("sections[$index][hidden]", '1', !empty(get_array_value($section, 'hidden')), "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-builder-row">X</button></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="template-fields-pane">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="mb-0 text-muted">Adicionar campos dinamicos e associar a uma seccao.</p>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-template-field">Adicionar campo</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width:32px;"></th>
                                                <th>Chave</th>
                                                <th>Nome</th>
                                                <th>Secao</th>
                                                <th>Tipo</th>
                                                <th>Rotulo</th>
                                                <th>Descricao</th>
                                                <th>Placeholder</th>
                                                <th>Padrao</th>
                                                <th>Validacao</th>
                                                <th>Mask</th>
                                                <th>Ajuda</th>
                                                <th>Obrig.</th>
                                                <th>Ordem</th>
                                                <th>Largura</th>
                                                <th>Web</th>
                                                <th>Mobile</th>
                                                <th>PDF</th>
                                                <th>RO</th>
                                                <th>IA</th>
                                                <th style="width:70px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="template-fields-body">
                                            <?php foreach ($fields as $index => $field) { ?>
                                                <tr class="builder-row" draggable="true">
                                                    <td class="text-center"><i data-feather="move" class="icon-16 text-muted drag-handle"></i></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][key]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'key')); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][name]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'name')); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][section_key]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'section_key')); ?>"></td>
                                                    <td><?php echo form_dropdown("fields[$index][type]", $field_types, get_array_value($field, 'type', 'text'), "class='form-select form-select-sm'"); ?></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][label]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'label')); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][description]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'description')); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][placeholder]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'placeholder')); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][default_value]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'default_value')); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][validation]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'validation')); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][mask]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'mask')); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][help]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'help')); ?>"></td>
                                                    <td class="text-center"><?php echo form_checkbox("fields[$index][required]", '1', !empty(get_array_value($field, 'required')), "class='form-check-input'"); ?></td>
                                                    <td><input type="number" name="fields[<?php echo $index; ?>][sort]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'sort') ?: ($index + 1)); ?>"></td>
                                                    <td><input type="text" name="fields[<?php echo $index; ?>][width]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($field, 'width') ?: '12'); ?>"></td>
                                                    <td class="text-center"><?php echo form_checkbox("fields[$index][visible_web]", '1', get_array_value($field, 'visible_web', 1) ? true : false, "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("fields[$index][visible_mobile]", '1', get_array_value($field, 'visible_mobile', 1) ? true : false, "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("fields[$index][visible_pdf]", '1', get_array_value($field, 'visible_pdf', 1) ? true : false, "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("fields[$index][read_only]", '1', !empty(get_array_value($field, 'read_only')), "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><?php echo form_checkbox("fields[$index][generated_ai]", '1', !empty(get_array_value($field, 'generated_ai')), "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-builder-row">X</button></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="template-rules-pane">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="mb-0 text-muted">Criar regras condicionais e acoes automaticas.</p>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-template-rule">Adicionar regra</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width:32px;"></th>
                                                <th>Nome</th>
                                                <th>Campo</th>
                                                <th>Operador</th>
                                                <th>Valor</th>
                                                <th>Acao</th>
                                                <th>Destino</th>
                                                <th>Mensagem</th>
                                                <th>Ordem</th>
                                                <th>Ativa</th>
                                                <th style="width:70px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="template-rules-body">
                                            <?php foreach ($rules as $index => $rule) { ?>
                                                <tr class="builder-row" draggable="true">
                                                    <td class="text-center"><i data-feather="move" class="icon-16 text-muted drag-handle"></i></td>
                                                    <td><input type="text" name="rules[<?php echo $index; ?>][name]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($rule, 'name')); ?>"></td>
                                                    <td><input type="text" name="rules[<?php echo $index; ?>][trigger_field]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($rule, 'trigger_field')); ?>"></td>
                                                    <td><?php echo form_dropdown("rules[$index][operator]", $operators, get_array_value($rule, 'operator', 'equals'), "class='form-select form-select-sm'"); ?></td>
                                                    <td><input type="text" name="rules[<?php echo $index; ?>][trigger_value]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($rule, 'trigger_value')); ?>"></td>
                                                    <td><?php echo form_dropdown("rules[$index][action_type]", $actions, get_array_value($rule, 'action_type', 'require_field'), "class='form-select form-select-sm'"); ?></td>
                                                    <td><input type="text" name="rules[<?php echo $index; ?>][action_target]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($rule, 'action_target')); ?>"></td>
                                                    <td><input type="text" name="rules[<?php echo $index; ?>][message]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($rule, 'message')); ?>"></td>
                                                    <td><input type="number" name="rules[<?php echo $index; ?>][sort]" class="form-control form-control-sm" value="<?php echo esc(get_array_value($rule, 'sort') ?: ($index + 1)); ?>"></td>
                                                    <td class="text-center"><?php echo form_checkbox("rules[$index][active]", '1', get_array_value($rule, 'active', 1) ? true : false, "class='form-check-input'"); ?></td>
                                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-builder-row">X</button></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="template-preview">
                        <div class="alert alert-info">
                            A pre-visualizacao completa esta disponivel na pagina separada de preview.
                        </div>
                        <a href="<?php echo esc($preview_url); ?>" target="_blank" class="btn btn-outline-primary">Abrir preview</a>
                    </div>
                </div>
            </div>
        </div>
    <?php echo form_close(); ?>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="button" id="save-template-btn" class="btn btn-primary">Salvar</button>
</div>

<template id="section-row-template">
    <tr class="builder-row" draggable="true">
        <td class="text-center"><i data-feather="move" class="icon-16 text-muted drag-handle"></i></td>
        <td><input type="text" name="sections[__INDEX__][key]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="sections[__INDEX__][title]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="sections[__INDEX__][description]" class="form-control form-control-sm" value=""></td>
        <td><input type="number" name="sections[__INDEX__][sort]" class="form-control form-control-sm" value=""></td>
        <td class="text-center"><input type="checkbox" name="sections[__INDEX__][page_break]" value="1" class="form-check-input"></td>
        <td class="text-center"><input type="checkbox" name="sections[__INDEX__][numbering]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="sections[__INDEX__][visible_web]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="sections[__INDEX__][visible_mobile]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="sections[__INDEX__][visible_pdf]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="sections[__INDEX__][required]" value="1" class="form-check-input"></td>
        <td class="text-center"><input type="checkbox" name="sections[__INDEX__][hidden]" value="1" class="form-check-input"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-builder-row">X</button></td>
    </tr>
</template>

<template id="field-row-template">
    <tr class="builder-row" draggable="true">
        <td class="text-center"><i data-feather="move" class="icon-16 text-muted drag-handle"></i></td>
        <td><input type="text" name="fields[__INDEX__][key]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][name]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][section_key]" class="form-control form-control-sm" value=""></td>
        <td><?php echo form_dropdown('fields[__INDEX__][type]', $field_types, 'text', "class='form-select form-select-sm'"); ?></td>
        <td><input type="text" name="fields[__INDEX__][label]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][description]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][placeholder]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][default_value]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][validation]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][mask]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][help]" class="form-control form-control-sm" value=""></td>
        <td class="text-center"><input type="checkbox" name="fields[__INDEX__][required]" value="1" class="form-check-input"></td>
        <td><input type="number" name="fields[__INDEX__][sort]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="fields[__INDEX__][width]" class="form-control form-control-sm" value="12"></td>
        <td class="text-center"><input type="checkbox" name="fields[__INDEX__][visible_web]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="fields[__INDEX__][visible_mobile]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="fields[__INDEX__][visible_pdf]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><input type="checkbox" name="fields[__INDEX__][read_only]" value="1" class="form-check-input"></td>
        <td class="text-center"><input type="checkbox" name="fields[__INDEX__][generated_ai]" value="1" class="form-check-input"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-builder-row">X</button></td>
    </tr>
</template>

<template id="rule-row-template">
    <tr class="builder-row" draggable="true">
        <td class="text-center"><i data-feather="move" class="icon-16 text-muted drag-handle"></i></td>
        <td><input type="text" name="rules[__INDEX__][name]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="rules[__INDEX__][trigger_field]" class="form-control form-control-sm" value=""></td>
        <td><?php echo form_dropdown('rules[__INDEX__][operator]', $operators, 'equals', "class='form-select form-select-sm'"); ?></td>
        <td><input type="text" name="rules[__INDEX__][trigger_value]" class="form-control form-control-sm" value=""></td>
        <td><?php echo form_dropdown('rules[__INDEX__][action_type]', $actions, 'require_field', "class='form-select form-select-sm'"); ?></td>
        <td><input type="text" name="rules[__INDEX__][action_target]" class="form-control form-control-sm" value=""></td>
        <td><input type="text" name="rules[__INDEX__][message]" class="form-control form-control-sm" value=""></td>
        <td><input type="number" name="rules[__INDEX__][sort]" class="form-control form-control-sm" value=""></td>
        <td class="text-center"><input type="checkbox" name="rules[__INDEX__][active]" value="1" class="form-check-input" checked></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-builder-row">X</button></td>
    </tr>
</template>

<script type="text/javascript">
    $(function () {
        function nextIndex(selector) {
            var maxIndex = -1;
            $(selector + " .builder-row input[name]").each(function () {
                var match = this.name.match(/\[(\d+)\]/);
                if (match) {
                    maxIndex = Math.max(maxIndex, parseInt(match[1], 10));
                }
            });
            return maxIndex + 1;
        }

        function bindSortable($tbody) {
            var dragSource = null;

            $tbody.on("dragstart", ".builder-row", function (e) {
                dragSource = this;
                e.originalEvent.dataTransfer.effectAllowed = "move";
                try {
                    e.originalEvent.dataTransfer.setData("text/plain", "");
                } catch (err) {}
            });

            $tbody.on("dragover", ".builder-row", function (e) {
                e.preventDefault();
                if (!dragSource || dragSource === this) {
                    return;
                }
                var target = this;
                var rect = target.getBoundingClientRect();
                var after = (e.originalEvent.clientY - rect.top) > (rect.height / 2);
                if (after) {
                    target.parentNode.insertBefore(dragSource, target.nextSibling);
                } else {
                    target.parentNode.insertBefore(dragSource, target);
                }
                renumberAll();
            });
        }

        function renumber($tbody, fieldSelector) {
            $tbody.find(".builder-row").each(function (index) {
                $(this).find(fieldSelector).val(index + 1);
            });
        }

        function renumberAll() {
            renumber($("#template-sections-body"), "input[name$='[sort]']");
            renumber($("#template-fields-body"), "input[name$='[sort]']");
            renumber($("#template-rules-body"), "input[name$='[sort]']");
        }

        function appendRow(templateId, tbodySelector) {
            var index = nextIndex(tbodySelector);
            var html = $("#" + templateId).html().replace(/__INDEX__/g, index);
            $(tbodySelector).append(html);
            renumberAll();
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        $("#add-template-section").on("click", function () {
            appendRow("section-row-template", "#template-sections-body");
        });

        $("#add-template-field").on("click", function () {
            appendRow("field-row-template", "#template-fields-body");
        });

        $("#add-template-rule").on("click", function () {
            appendRow("rule-row-template", "#template-rules-body");
        });

        $(document).on("click", ".remove-builder-row", function () {
            $(this).closest("tr").remove();
            renumberAll();
        });

        bindSortable($("#template-sections-body"));
        bindSortable($("#template-fields-body"));
        bindSortable($("#template-rules-body"));

        $("#save-template-btn").on("click", function () {
            $("#laudostecnicos-template-form").trigger("submit");
        });

        $("#laudostecnicos-template-form").appForm({
            onSuccess: function (result) {
                if (result && result.redirect_to) {
                    window.location = result.redirect_to;
                    return;
                }
                if (result && result.id) {
                    window.location = "<?php echo get_uri('laudostecnicos/templates/preview/'); ?>" + result.id;
                    return;
                }
                appAlert.success(result.message || "Salvo com sucesso.");
            }
        });

        renumberAll();
        if (typeof feather !== "undefined") {
            feather.replace();
        }
    });
</script>
