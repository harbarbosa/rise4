<?php
$template = isset($template) ? $template : null;
$sections = isset($sections) ? $sections : array();
$rules = isset($rules) ? $rules : array();

$template_id = $template ? $template->id : 0;
$is_published = $template && $template->status === 'published';
?>

<form id="template-form" method="POST" class="dialog-form">
    <input type="hidden" name="id" value="<?php echo $template_id; ?>" />
    
    <?php if ($is_published): ?>
    <div class="alert alert-warning">
        <i data-feather="alert-triangle" class="icon-16"></i>
        Este template está publicado. Para editar, crie uma nova versão ou despublice.
    </div>
    <?php endif; ?>
    
    <!-- Informações Básicas -->
    <div class="card mb-3">
        <div class="card-header">
            <h5><?php echo app_lang('laudos_template_info'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_name'); ?> *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $template->name ?? ''; ?>" required <?php echo $is_published ? 'disabled' : ''; ?> />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_code'); ?> *</label>
                        <input type="text" name="code" class="form-control" value="<?php echo $template->code ?? ''; ?>" required <?php echo $is_published ? 'disabled' : ''; ?> />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_type'); ?></label>
                        <?php echo form_dropdown('laudo_type_id', $types_dropdown, $template->laudo_type_id ?? '', "class='form-control' " . ($is_published ? 'disabled' : '')); ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label><?php echo app_lang('description'); ?></label>
                        <textarea name="description" class="form-control" rows="2" <?php echo $is_published ? 'disabled' : ''; ?>><?php echo $template->description ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            <div class="form-check">
                <?php echo form_checkbox('is_default', '1', $template && $template->is_default ? true : false, "id='is_default' class='form-check-input' " . ($is_published ? 'disabled' : '')); ?>
                <label for="is_default"><?php echo app_lang('laudos_template_default'); ?></label>
            </div>
        </div>
    </div>

    <!-- Seções -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><?php echo app_lang('laudos_template_sections'); ?></h5>
            <?php if (!$is_published): ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="addSection()">
                <i data-feather="plus" class="icon-16"></i> <?php echo app_lang('laudos_add_section'); ?>
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body" id="sections-container">
            <?php if (empty($sections)): ?>
            <div class="text-center text-muted py-4">
                <i data-feather="layout" class="icon-32"></i>
                <p><?php echo app_lang('laudos_no_sections'); ?></p>
                <?php if (!$is_published): ?>
                <button type="button" class="btn btn-primary" onclick="addSection()"><?php echo app_lang('laudos_add_section'); ?></button>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($sections as $section_idx => $section): ?>
                <div class="section-item card mb-2" data-section-id="<?php echo $section->id; ?>">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center cursor-move">
                        <div class="d-flex align-items-center">
                            <i data-feather="menu" class="icon-16 mr-2 handle"></i>
                            <strong><?php echo $section->name; ?></strong>
                            <span class="badge bg-secondary ml-2"><?php echo $section->section_type; ?></span>
                            <?php if ($section->page_break): ?>
                            <span class="badge bg-info ml-1">Quebra página</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$is_published): ?>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addField(<?php echo $section->id; ?>)">
                                <i data-feather="plus" class="icon-14"></i> Campo
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSection(<?php echo $section->id; ?>)">
                                <i data-feather="trash-2" class="icon-14"></i>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="sections[<?php echo $section_idx; ?>][id]" value="<?php echo $section->id; ?>" />
                        <input type="hidden" name="sections[<?php echo $section_idx; ?>][name]" value="<?php echo $section->name; ?>" />
                        <input type="hidden" name="sections[<?php echo $section_idx; ?>][code]" value="<?php echo $section->code; ?>" />
                        <input type="hidden" name="sections[<?php echo $section_idx; ?>][section_type]" value="<?php echo $section->section_type; ?>" />
                        <input type="hidden" name="sections[<?php echo $section_idx; ?>][sort_order]" value="<?php echo $section->sort_order; ?>" />
                        <input type="hidden" name="sections[<?php echo $section_idx; ?>][page_break]" value="<?php echo $section->page_break; ?>" />
                        
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <div class="form-check">
                                    <?php echo form_checkbox("sections[$section_idx][page_break]", '1', $section->page_break ? true : false, "class='form-check-input' " . ($is_published ? 'disabled' : '')); ?>
                                    <label><?php echo app_lang('laudos_section_page_break'); ?></label>
                                    
                                    <?php echo form_checkbox("sections[$section_idx][visible_web]", '1', $section->visible_web ? true : false, "class='form-check-input ml-3' " . ($is_published ? 'disabled' : '')); ?>
                                    <label class="ml-4">Web</label>
                                    
                                    <?php echo form_checkbox("sections[$section_idx][visible_mobile]", '1', $section->visible_mobile ? true : false, "class='form-check-input ml-3' " . ($is_published ? 'disabled' : '')); ?>
                                    <label class="ml-4">Mobile</label>
                                    
                                    <?php echo form_checkbox("sections[$section_idx][visible_pdf]", '1', $section->visible_pdf ? true : false, "class='form-check-input ml-3' " . ($is_published ? 'disabled' : '')); ?>
                                    <label class="ml-4">PDF</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campos da Seção -->
                        <?php if (!empty($section->fields)): ?>
                        <div class="fields-container ml-4">
                            <?php foreach ($section->fields as $field_idx => $field): ?>
                            <div class="field-item card mb-2" data-field-id="<?php echo $field->id; ?>">
                                <div class="card-body py-2">
                                    <input type="hidden" name="sections[<?php echo $section_idx; ?>][fields][<?php echo $field_idx; ?>][id]" value="<?php echo $field->id; ?>" />
                                    <input type="hidden" name="sections[<?php echo $section_idx; ?>][fields][<?php echo $field_idx; ?>][field_name]" value="<?php echo $field->field_name; ?>" />
                                    <input type="hidden" name="sections[<?php echo $section_idx; ?>][fields][<?php echo $field_idx; ?>][field_key]" value="<?php echo $field->field_key; ?>" />
                                    <input type="hidden" name="sections[<?php echo $section_idx; ?>][fields][<?php echo $field_idx; ?>][field_type]" value="<?php echo $field->field_type; ?>" />
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <strong><?php echo $field->label; ?></strong>
                                            <small class="d-block text-muted"><?php echo $field->field_type; ?></small>
                                        </div>
                                        <div class="col-md-6">
                                            <small><?php echo $field->description ?? ''; ?></small>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            <?php if ($field->is_required): ?>
                                            <span class="badge bg-danger">Obrigatório</span>
                                            <?php endif; ?>
                                            <?php if (!$is_published): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeField(this)">
                                                <i data-feather="trash-2" class="icon-14"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Regras Condicionais -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><?php echo app_lang('laudos_template_rules'); ?></h5>
            <?php if (!$is_published): ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="addRule()">
                <i data-feather="plus" class="icon-16"></i> <?php echo app_lang('laudos_add_rule'); ?>
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body" id="rules-container">
            <?php if (empty($rules)): ?>
            <div class="text-center text-muted py-3">
                <p><?php echo app_lang('laudos_no_rules'); ?></p>
            </div>
            <?php else: ?>
                <?php foreach ($rules as $rule_idx => $rule): ?>
                <div class="rule-item card mb-2">
                    <div class="card-body py-2">
                        <input type="hidden" name="rules[<?php echo $rule_idx; ?>][id]" value="<?php echo $rule->id; ?>" />
                        <input type="hidden" name="rules[<?php echo $rule_idx; ?>][rule_type]" value="<?php echo $rule->rule_type; ?>" />
                        <input type="hidden" name="rules[<?php echo $rule_idx; ?>][condition_field]" value="<?php echo $rule->condition_field; ?>" />
                        <input type="hidden" name="rules[<?php echo $rule_idx; ?>][condition_operator]" value="<?php echo $rule->condition_operator; ?>" />
                        <input type="hidden" name="rules[<?php echo $rule_idx; ?>][condition_value]" value="<?php echo $rule->condition_value; ?>" />
                        <input type="hidden" name="rules[<?php echo $rule_idx; ?>][action]" value="<?php echo $rule->action; ?>" />
                        <input type="hidden" name="rules[<?php echo $rule_idx; ?>][action_target]" value="<?php echo $rule->action_target; ?>" />
                        <input type="hidden" name="rules[<?php echo $rule_idx; ?>][action_value]" value="<?php echo $rule->action_value; ?>" />
                        
                        <strong><?php echo $rule->rule_type; ?></strong>: 
                        Quando <em><?php echo $rule->condition_field; ?></em> <?php echo $rule->condition_operator; ?> <em><?php echo $rule->condition_value; ?></em>
                        → <strong><?php echo $rule->action; ?></strong> <?php echo $rule->action_target; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Templates JavaScript para novas seções/campos -->
<script id="section-template" type="text/template">
    <div class="section-item card mb-2" data-section-id="NEW">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <strong>Nova Seção</strong>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSection(this)">
                <i data-feather="trash-2" class="icon-14"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="sections[__INDEX__][name]" class="form-control" value="" required />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" name="sections[__INDEX__][code]" class="form-control" value="" required />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="sections[__INDEX__][section_type]" class="form-control">
                            <?php foreach ($section_types as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" name="sections[__INDEX__][page_break]" value="1" class="form-check-input" />
                <label><?php echo app_lang('laudos_section_page_break'); ?></label>
            </div>
            <div class="fields-container ml-4 mt-2">
                <!-- Campos serão adicionados aqui -->
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addFieldToSection(this)">
                <i data-feather="plus" class="icon-14"></i> Adicionar Campo
            </button>
        </div>
    </div>
</script>

<script>
var sectionCount = <?php echo count($sections); ?>;
var fieldTypes = <?php echo json_encode($field_types); ?>;

function addSection() {
    var template = document.getElementById('section-template').innerHTML;
    var html = template.replace(/__INDEX__/g, sectionCount++);
    html = html.replace('data-section-id="NEW"', 'data-section-id="new_' + sectionCount + '"');
    document.getElementById('sections-container').insertAdjacentHTML('beforeend', html);
}

function removeSection(btn) {
    if (confirm('Remover esta seção?')) {
        btn.closest('.section-item').remove();
    }
}

function addField(sectionId) {
    var fieldName = prompt('Nome do campo:');
    if (!fieldName) return;
    
    var fieldKey = prompt('Chave do campo (sem espaços):');
    if (!fieldKey) return;
    
    var fieldType = prompt('Tipo do campo (' + Object.keys(fieldTypes).join(', ') + '):');
    if (!fieldType || !fieldTypes[fieldType]) {
        alert('Tipo inválido');
        return;
    }
    
    // Adicionar campo via AJAX em produção
    alert('Funcionalidade de adicionar campos: Use o construtor visual completo');
}

function addFieldToSection(btn) {
    addField(0);
}

function removeField(btn) {
    btn.closest('.field-item').remove();
}

function addRule() {
    alert('Funcionalidade de regras condicionais em desenvolvimento');
}

$(document).ready(function() {
    $('#template-form').appForm({
        onSuccess: function(response) {
            window.location.href = '<?php echo get_uri("laudos_templates"); ?>';
        }
    });
});
</script>