<?php
/**
 * Helper function to render field preview
 */
function render_field_preview($field, $field_types) {
    $type = $field->field_type;
    $required = $field->is_required ? ' required' : '';
    $disabled = ' disabled';
    $placeholder = $field->placeholder ?? '';
    $default = $field->default_value ?? '';
    
    switch ($type) {
        case 'text':
            return '<input type="text" class="form-control" placeholder="' . $placeholder . '"' . $disabled . '>';
            
        case 'textarea':
            return '<textarea class="form-control" rows="3" placeholder="' . $placeholder . '"' . $disabled . '></textarea>';
            
        case 'number':
        case 'decimal':
        case 'currency':
        case 'percentage':
            return '<input type="number" class="form-control"' . $disabled . '>';
            
        case 'date':
            return '<input type="date" class="form-control"' . $disabled . '>';
            
        case 'time':
            return '<input type="time" class="form-control"' . $disabled . '>';
            
        case 'datetime':
            return '<input type="datetime-local" class="form-control"' . $disabled . '>';
            
        case 'yes_no':
            return '<div class="form-check"><input type="radio" class="form-check-input"' . $disabled . '> Sim &nbsp; <input type="radio" class="form-check-input"' . $disabled . '> Não</div>';
            
        case 'select':
            return '<select class="form-control"' . $disabled . '><option>Selecione...</option></select>';
            
        case 'checkbox':
            return '<div class="form-check"><input type="checkbox" class="form-check-input"' . $disabled . '></div>';
            
        case 'image':
            return '<div class="border rounded p-3 text-center text-muted"><i data-feather="image" class="icon-32"></i><p class="mt-2 mb-0">Clique para adicionar imagem</p></div>';
            
        case 'file':
            return '<div class="border rounded p-3 text-center text-muted"><i data-feather="upload" class="icon-32"></i><p class="mt-2 mb-0">Clique para upload</p></div>';
            
        case 'signature':
            return '<div class="border rounded p-3 text-center text-muted"><i data-feather="edit-3" class="icon-32"></i><p class="mt-2 mb-0">Assinatura digital</p></div>';
            
        case 'gps':
            return '<div class="border rounded p-3 text-center text-muted"><i data-feather="map-pin" class="icon-32"></i><p class="mt-2 mb-0">Capturar localização</p></div>';
            
        case 'measurement':
            return '<div class="input-group"><input type="number" class="form-control"' . $disabled . '><div class="input-group-append"><span class="input-group-text">Unidade</span></div></div>';
            
        case 'dynamic_table':
            return '<table class="table table-bordered table-sm"><thead><tr><th>Item</th><th>Valor</th></tr></thead><tbody><tr><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table><button type="button" class="btn btn-sm btn-outline-secondary">+ Linha</button>';
            
        case 'read_only':
            return '<p class="form-control-static">' . ($default ?: 'Valor calculado') . '</p>';
            
        case 'ai_text':
            return '<div class="input-group"><textarea class="form-control" rows="2"' . $disabled . '></textarea><div class="input-group-append"><button type="button" class="btn btn-outline-secondary">✨ Gerar IA</button></div></div>';
            
        default:
            return '<input type="text" class="form-control"' . $disabled . '>';
    }
}

$template = $template;
$sections = $sections;
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4><?php echo $template->name; ?></h4>
                <small class="text-muted"><?php echo $template->code; ?> v<?php echo $template->version; ?></small>
            </div>
            <div>
                <a href="<?php echo get_uri('laudos_templates'); ?>" class="btn btn-default">
                    <i data-feather="arrow-left" class="icon-16"></i> <?php echo app_lang('back'); ?>
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Preview Controls -->
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" onclick="setPreviewMode('web')">
                        <i data-feather="monitor" class="icon-14"></i> Web
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreviewMode('mobile')">
                        <i data-feather="smartphone" class="icon-14"></i> Mobile
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreviewMode('pdf')">
                        <i data-feather="file-text" class="icon-14"></i> PDF
                    </button>
                </div>
            </div>
            
            <!-- Preview Container -->
            <div id="preview-container" class="preview-web">
                <?php foreach ($sections as $section): ?>
                <div class="preview-section mb-4">
                    <?php if ($section->section_type === 'cover'): ?>
                    <!-- Capa -->
                    <div class="text-center p-5 border rounded" style="min-height: 400px;">
                        <h1 class="mt-5"><?php echo $template->name; ?></h1>
                        <h3 class="text-muted mt-3">Laudo Técnico</h3>
                        <hr />
                        <p class="mt-5">Versão <?php echo $template->version; ?></p>
                        <p><?php echo $template->description; ?></p>
                    </div>
                    <?php else: ?>
                    <!-- Outras Seções -->
                    <div class="section-header d-flex align-items-center mb-2">
                        <h5 class="mb-0">
                            <?php if ($section->show_numbering): ?>
                            <span class="badge bg-secondary mr-2"><?php echo $section->sort_order; ?></span>
                            <?php endif; ?>
                            <?php echo $section->name; ?>
                        </h5>
                        <?php if ($section->is_required): ?>
                        <span class="badge bg-danger ml-2">Obrigatório</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($section->description)): ?>
                    <p class="text-muted"><?php echo $section->description; ?></p>
                    <?php endif; ?>
                    
                    <!-- Campos -->
                    <?php if (!empty($section->fields)): ?>
                    <div class="fields-preview ml-3">
                        <?php foreach ($section->fields as $field): ?>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>
                                <?php echo $field->label; ?>
                                <?php if ($field->is_required): ?>
                                <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php
                            // Renderizar preview do campo
                            echo render_field_preview($field, $field_types);
                            ?>
                            
                            <?php if ($field->help_text): ?>
                            <small class="form-text text-muted"><?php echo $field->help_text; ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($section->page_break): ?>
                    <div style="page-break-after: always;"></div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.preview-mobile {
    max-width: 375px;
    margin: 0 auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
}

.preview-pdf {
    font-family: serif;
    font-size: 12pt;
    line-height: 1.5;
}

.preview-pdf .form-group {
    margin-bottom: 0.5rem;
}

.preview-pdf label {
    font-weight: bold;
}

.preview-pdf input,
.preview-pdf textarea,
.preview-pdf select {
    border: none;
    border-bottom: 1px solid #000;
    padding: 2px;
}
</style>

<script>
function setPreviewMode(mode) {
    var container = document.getElementById('preview-container');
    container.className = 'preview-' + mode;
    
    // Atualizar botões
    document.querySelectorAll('.btn-group button').forEach(function(btn) {
        btn.classList.remove('active');
    });
    event.target.closest('button').classList.add('active');
}
</script>