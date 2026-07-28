<div id="page-content" class="page-wrapper clearfix">
    <form method="post" action="<?php echo get_uri('laudo_nonconformities/action_plan_save'); ?>" id="action-plan-form" class="general-form">
        <input type="hidden" name="id" value="" />
        <input type="hidden" name="nc_id" value="<?php echo isset($nc_info) ? $nc_info->id : ''; ?>" />
        
        <div class="card">
            <div class="card-header">
                <h4>Plano de Ação (5W2H)</h4>
            </div>
            <div class="card-body">
                <?php if (isset($nc_info)): ?>
                <div class="alert alert-info">
                    <strong>NC:</strong> <?php echo $nc_info->code; ?> - <?php echo $nc_info->title; ?>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>O que? (What) <span class="text-danger">*</span></label>
                    <textarea name="action" class="form-control" rows="2" required placeholder="Descrição da ação a ser tomada"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Por que? (Why)</label>
                    <textarea name="reason" class="form-control" rows="2" placeholder="Motivo/justificativa da ação"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Onde? (Where)</label>
                            <input type="text" name="location" class="form-control" placeholder="Local de execução" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Quando? (When) <span class="text-danger">*</span></label>
                            <input type="date" name="deadline" class="form-control" required />
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Quem? (Who)</label>
                            <?php echo form_dropdown('responsible_id', array('' => '-- Selecionar --') + (isset($team_dropdown) ? $team_dropdown : array()), '', "class='form-control'"); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Empresa Responsável</label>
                            <input type="text" name="company_name" class="form-control" />
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Como? (How)</label>
                    <textarea name="method" class="form-control" rows="2" placeholder="Método/procedimento a ser seguido"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Quanto? (How much) - Custo Estimado</label>
                            <input type="number" name="estimated_cost" class="form-control" step="0.01" placeholder="0.00" />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Prioridade</label>
                            <select name="priority" class="form-control">
                                <option value="low">Baixa</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="pending" selected>Pendente</option>
                                <option value="in_progress">Em Andamento</option>
                                <option value="completed">Concluído</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i data-feather="save" class="icon-16"></i> Salvar</button>
            </div>
        </div>
    </form>
</div>

<script>
$('#action-plan-form').submit(function(e) {
    e.preventDefault();
    var form = $(this);
    
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                appAlert.success(response.message);
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                appAlert.error(response.message);
            }
        }
    });
});
</script>