<?php
$template = $template ?? (object) array();
$structure = is_array($structure ?? null) ? $structure : array('sections' => array(), 'fields' => array(), 'rules' => array());
$sections = is_array(get_array_value($structure, 'sections')) ? get_array_value($structure, 'sections') : array();
$fields = is_array(get_array_value($structure, 'fields')) ? get_array_value($structure, 'fields') : array();
$rules = is_array(get_array_value($structure, 'rules')) ? get_array_value($structure, 'rules') : array();

$fields_by_section = array();
foreach ($fields as $field) {
    $section_key = trim((string) get_array_value($field, 'section_key'));
    if (!isset($fields_by_section[$section_key])) {
        $fields_by_section[$section_key] = array();
    }
    $fields_by_section[$section_key][] = $field;
}
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo esc($template->name ?? 'Template'); ?></h1>
            <div class="title-button-group">
                <button type="button" class="btn btn-outline-secondary preview-mode-btn active" data-mode="screen">Tela</button>
                <button type="button" class="btn btn-outline-secondary preview-mode-btn" data-mode="mobile">Mobile</button>
                <button type="button" class="btn btn-outline-secondary preview-mode-btn" data-mode="print">Impressao</button>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-lg-9">
                    <div id="preview-stage" class="preview-stage preview-screen">
                        <?php if ($sections) { ?>
                            <?php foreach ($sections as $section) { ?>
                                <?php if (!empty(get_array_value($section, 'hidden'))) { continue; } ?>
                                <div class="preview-section card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo esc(get_array_value($section, 'title') ?: get_array_value($section, 'key')); ?></strong>
                                            <?php if (get_array_value($section, 'required')) { ?><span class="badge bg-danger ms-2">Obrigatorio</span><?php } ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?php echo get_array_value($section, 'page_break') ? 'Quebra de pagina' : 'Continua'; ?>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3"><?php echo esc(get_array_value($section, 'description') ?: 'Sem descricao'); ?></p>
                                        <div class="row g-3">
                                            <?php foreach (get_array_value($fields_by_section, (string) get_array_value($section, 'key')) ?: array() as $field) { ?>
                                                <div class="col-md-<?php echo esc(get_array_value($field, 'width') ?: '12'); ?>">
                                                    <label class="form-label">
                                                        <?php echo esc(get_array_value($field, 'label') ?: get_array_value($field, 'key')); ?>
                                                        <?php if (get_array_value($field, 'required')) { ?><span class="text-danger">*</span><?php } ?>
                                                    </label>
                                                    <?php
                                                    $type = get_array_value($field, 'type') ?: 'text';
                                                    if (in_array($type, array('text_long', 'rich_text', 'ai_text'), true)) {
                                                        echo '<textarea class="form-control" rows="3" placeholder="' . esc(get_array_value($field, 'placeholder')) . '"></textarea>';
                                                    } else if (in_array($type, array('single_select', 'multi_select'), true)) {
                                                        echo form_dropdown('preview_' . esc(get_array_value($field, 'key')), array('' => '-', 'opcao_1' => 'Opcao 1', 'opcao_2' => 'Opcao 2'), '', "class='form-select'");
                                                    } else if (in_array($type, array('yes_no', 'checkbox'), true)) {
                                                        echo '<div class="form-check"><input type="checkbox" class="form-check-input"><label class="form-check-label">Sim</label></div>';
                                                    } else if (in_array($type, array('date', 'time', 'datetime'), true)) {
                                                        echo '<input type="' . esc($type === 'datetime' ? 'datetime-local' : $type) . '" class="form-control">';
                                                    } else if (in_array($type, array('number', 'decimal', 'currency', 'percentage'), true)) {
                                                        echo '<input type="number" class="form-control" placeholder="' . esc(get_array_value($field, 'placeholder')) . '">';
                                                    } else {
                                                        echo '<input type="text" class="form-control" placeholder="' . esc(get_array_value($field, 'placeholder')) . '">';
                                                    }
                                                    ?>
                                                    <?php if (get_array_value($field, 'help')) { ?>
                                                        <small class="text-muted"><?php echo esc(get_array_value($field, 'help')); ?></small>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="alert alert-info">Nenhuma estrutura cadastrada.</div>
                        <?php } ?>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Resumo</div>
                            <div class="small text-muted">Codigo: <?php echo esc($template->code ?: '-'); ?></div>
                            <div class="small text-muted">Versao: <?php echo esc($template->version ?: 1); ?></div>
                            <div class="small text-muted">Status: <?php echo esc($template->status ?: 'draft'); ?></div>
                            <div class="small text-muted">Default: <?php echo !empty($template->is_default) ? 'Sim' : 'Nao'; ?></div>
                            <div class="small text-muted">Ativo: <?php echo !empty($template->is_active) ? 'Sim' : 'Nao'; ?></div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Regras</div>
                            <?php if ($rules) { ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($rules as $rule) { ?>
                                        <li class="list-group-item px-0">
                                            <div class="fw-semibold"><?php echo esc(get_array_value($rule, 'name') ?: 'Regra'); ?></div>
                                            <div class="small text-muted"><?php echo esc(get_array_value($rule, 'trigger_field') . ' ' . get_array_value($rule, 'operator') . ' ' . get_array_value($rule, 'trigger_value')); ?></div>
                                            <div class="small"><?php echo esc(get_array_value($rule, 'message') ?: 'Sem mensagem'); ?></div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            <?php } else { ?>
                                <div class="text-muted">Sem regras configuradas.</div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Legenda</div>
                            <div class="small text-muted">Campos obrigatorios aparecem com *.</div>
                            <div class="small text-muted">Seccoes ocultas nao sao exibidas.</div>
                            <div class="small text-muted">Mobile reduz a largura da viewport.</div>
                            <div class="small text-muted">Impressao simula pagina A4.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .preview-stage {
        transition: all .2s ease;
        margin: 0 auto;
        min-height: 400px;
    }
    .preview-screen {
        max-width: 1180px;
    }
    .preview-mobile {
        max-width: 420px;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        padding: 16px;
        background: #fff;
    }
    .preview-print {
        max-width: 850px;
        background: #fff;
        padding: 32px;
        border: 1px solid #ced4da;
        box-shadow: 0 1rem 3rem rgba(0,0,0,.06);
    }
</style>

<script type="text/javascript">
    $(function () {
        $(".preview-mode-btn").on("click", function () {
            $(".preview-mode-btn").removeClass("active");
            $(this).addClass("active");
            var mode = $(this).data("mode");
            $("#preview-stage").removeClass("preview-screen preview-mobile preview-print").addClass("preview-" + mode);
        });
    });
</script>
