<div id="page-content" class="page-wrapper clearfix">
    <form method="post" action="<?php echo get_uri('laudo_documents/save_config'); ?>" id="config-form" class="general-form">
        <input type="hidden" name="laudo_id" value="<?php echo $laudo->id; ?>" />
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Aparência</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Cor Principal</label>
                                    <input type="color" name="primary_color" class="form-control" value="<?php echo $config->primary_color ?? '#007bff'; ?>" />
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Cor Secundária</label>
                                    <input type="color" name="secondary_color" class="form-control" value="<?php echo $config->secondary_color ?? '#6c757d'; ?>" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Fonte</label>
                            <select name="font_family" class="form-control">
                                <option value="Arial, sans-serif" <?php echo ($config->font_family ?? '') === 'Arial, sans-serif' ? 'selected' : ''; ?>>Arial</option>
                                <option value="'Times New Roman', serif" <?php echo ($config->font_family ?? '') === "'Times New Roman', serif" ? 'selected' : ''; ?>>Times New Roman</option>
                                <option value="'Courier New', monospace" <?php echo ($config->font_family ?? '') === "'Courier New', monospace" ? 'selected' : ''; ?>>Courier New</option>
                                <option value="Helvetica, Arial, sans-serif" <?php echo ($config->font_family ?? '') === 'Helvetica, Arial, sans-serif' ? 'selected' : ''; ?>>Helvetica</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tamanho da Fonte</label>
                            <input type="number" name="font_size" class="form-control" value="<?php echo $config->font_size ?? 12; ?>" min="8" max="18" />
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h4>Página</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Tamanho do Papel</label>
                                    <select name="paper_size" class="form-control">
                                        <option value="A4" <?php echo ($config->paper_size ?? 'A4') === 'A4' ? 'selected' : ''; ?>>A4</option>
                                        <option value="A5" <?php echo ($config->paper_size ?? '') === 'A5' ? 'selected' : ''; ?>>A5</option>
                                        <option value="LETTER" <?php echo ($config->paper_size ?? '') === 'LETTER' ? 'selected' : ''; ?>>Carta</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Orientação</label>
                                    <select name="orientation" class="form-control">
                                        <option value="portrait" <?php echo ($config->orientation ?? 'portrait') === 'portrait' ? 'selected' : ''; ?>>Retrato</option>
                                        <option value="landscape" <?php echo ($config->orientation ?? '') === 'landscape' ? 'selected' : ''; ?>>Paisagem</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-3">
                                <div class="form-group">
                                    <label>Margem Sup.</label>
                                    <input type="number" name="margin_top" class="form-control" value="<?php echo $config->margin_top ?? 20; ?>" />
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label>Margem Inf.</label>
                                    <input type="number" name="margin_bottom" class="form-control" value="<?php echo $config->margin_bottom ?? 20; ?>" />
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label>Margem Esq.</label>
                                    <input type="number" name="margin_left" class="form-control" value="<?php echo $config->margin_left ?? 20; ?>" />
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label>Margem Dir.</label>
                                    <input type="number" name="margin_right" class="form-control" value="<?php echo $config->margin_right ?? 20; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Conteúdo</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="show_cover" class="form-check-input" id="show_cover" 
                                <?php echo ($config->show_cover ?? 1) ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="show_cover">Mostrar Capa</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input type="checkbox" name="show_toc" class="form-check-input" id="show_toc" 
                                <?php echo ($config->show_toc ?? 1) ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="show_toc">Mostrar Sumário</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input type="checkbox" name="show_page_numbers" class="form-check-input" id="show_page_numbers" 
                                <?php echo ($config->show_page_numbers ?? 1) ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="show_page_numbers">Numeração de Páginas</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="show_qrcode" class="form-check-input" id="show_qrcode" 
                                <?php echo ($config->show_qrcode ?? 1) ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="show_qrcode">QR Code de Validação</label>
                        </div>
                        
                        <div class="form-group">
                            <label>Texto de Confidencialidade</label>
                            <textarea name="confidentiality_text" class="form-control" rows="3"><?php echo $config->confidentiality_text ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary"><i data-feather="save" class="icon-16"></i> Salvar Configurações</button>
                <a href="<?php echo get_uri('laudo_documents/view/' . $laudo->id); ?>" class="btn btn-default">Voltar</a>
            </div>
        </div>
    </form>
</div>

<script>
$('#config-form').submit(function(e) {
    e.preventDefault();
    var form = $(this);
    
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                appAlert.success(response.message);
            } else {
                appAlert.error(response.message);
            }
        }
    });
});
</script>