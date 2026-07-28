<div id="page-content" class="page-wrapper clearfix">
    <form method="post" action="<?php echo get_uri('laudo_nonconformities/save'); ?>" id="nc-form" class="general-form">
        <input type="hidden" name="id" value="<?php echo isset($model_info) ? $model_info->id : ''; ?>" />
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Dados da Não Conformidade</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Título <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?php echo isset($model_info) ? $model_info->title : ''; ?>" required />
                        </div>
                        
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo isset($model_info) ? $model_info->description : ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Laudo</label>
                                    <?php echo form_dropdown('laudo_id', array('' => '-- Selecionar --') + (isset($laudos_dropdown) ? $laudos_dropdown : array()), isset($model_info) ? $model_info->laudo_id : '', "class='form-control'"); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <?php echo form_dropdown('status', isset($status_list) ? $status_list : array(), isset($model_info) ? $model_info->status : 'open', "class='form-control'"); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Local</label>
                                    <input type="text" name="location" class="form-control" value="<?php echo isset($model_info) ? $model_info->location : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Setor</label>
                                    <input type="text" name="sector" class="form-control" value="<?php echo isset($model_info) ? $model_info->sector : ''; ?>" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Recomendação</label>
                            <textarea name="recommendation" class="form-control" rows="2"><?php echo isset($model_info) ? $model_info->recommendation : ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Data Identificação</label>
                                    <input type="date" name="identified_at" class="form-control" value="<?php echo isset($model_info) ? $model_info->identified_at : date('Y-m-d'); ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prazo Sugerido</label>
                                    <input type="date" name="suggested_deadline" class="form-control" value="<?php echo isset($model_info) ? $model_info->suggested_deadline : ''; ?>" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Responsável</label>
                            <?php echo form_dropdown('responsible_id', array('' => '-- Selecionar --') + (isset($team_dropdown) ? $team_dropdown : array()), isset($model_info) ? $model_info->responsible_id : '', "class='form-control'"); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Classificação de Risco</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Classificação</label>
                            <?php echo form_dropdown('classification', isset($classification_list) ? $classification_list : array(), isset($model_info) ? $model_info->classification : 'moderate', "class='form-control' id='classification'"); ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Probabilidade</label>
                                    <select name="probability" class="form-control" id="probability">
                                        <option value="1" <?php echo (isset($model_info) && $model_info->probability == 1) ? 'selected' : ''; ?>>1 - Rara</option>
                                        <option value="2" <?php echo (isset($model_info) && $model_info->probability == 2) ? 'selected' : ''; ?>>2 - Improvável</option>
                                        <option value="3" <?php echo (isset($model_info) && $model_info->probability == 3) ? 'selected' : ''; ?>>3 - Possível</option>
                                        <option value="4" <?php echo (isset($model_info) && $model_info->probability == 4) ? 'selected' : ''; ?>>4 - Provável</option>
                                        <option value="5" <?php echo (isset($model_info) && $model_info->probability == 5) ? 'selected' : ''; ?>>5 - Certeza</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>Impacto</label>
                                    <select name="impact" class="form-control" id="impact">
                                        <option value="1" <?php echo (isset($model_info) && $model_info->impact == 1) ? 'selected' : ''; ?>>1 - Insignificante</option>
                                        <option value="2" <?php echo (isset($model_info) && $model_info->impact == 2) ? 'selected' : ''; ?>>2 - Menor</option>
                                        <option value="3" <?php echo (isset($model_info) && $model_info->impact == 3) ? 'selected' : ''; ?>>3 - Moderado</option>
                                        <option value="4" <?php echo (isset($model_info) && $model_info->impact == 4) ? 'selected' : ''; ?>>4 - Maior</option>
                                        <option value="5" <?php echo (isset($model_info) && $model_info->impact == 5) ? 'selected' : ''; ?>>5 - Catastrófico</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info text-center" id="risk-display">
                            <strong>Nível de Risco: </strong>
                            <span id="risk-level">
                                <?php 
                                if (isset($model_info)) {
                                    $risk = $model_info->probability * $model_info->impact;
                                    echo 'N' . $risk;
                                } else {
                                    echo 'N4';
                                }
                                ?>
                            </span>
                        </div>
                        
                        <p class="text-muted small">
                            O nível de risco é calculado multiplicando Probabilidade × Impacto.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary"><i data-feather="save" class="icon-16"></i> Salvar</button>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    $('#probability, #impact').change(function() {
        var p = parseInt($('#probability').val());
        var i = parseInt($('#impact').val());
        var risk = p * i;
        $('#risk-level').text('N' + risk);
        
        // Atualizar cor
        var color = '#198754';
        if (risk >= 9) color = '#dc3545';
        else if (risk >= 6) color = '#fd7e14';
        else if (risk >= 3) color = '#ffc107';
        
        $('#risk-display').css('background-color', color).css('color', '#fff');
    });
    
    $('#nc-form').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    appAlert.success(response.message);
                    $('#nc-form')[0].reset();
                    setTimeout(function() {
                        window.location.href = '<?php echo get_uri('laudo_nonconformities'); ?>';
                    }, 1000);
                } else {
                    appAlert.error(response.message);
                }
            }
        });
    });
});
</script>