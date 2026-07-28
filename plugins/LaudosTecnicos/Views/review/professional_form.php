<div id="page-content" class="page-wrapper clearfix">
    <form method="post" action="<?php echo get_uri('laudo_review/professional_save'); ?>" id="professional-form" class="general-form">
        <input type="hidden" name="id" value="<?php echo isset($model_info) ? $model_info->id : ''; ?>" />
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Dados Pessoais</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nome <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo isset($model_info) ? $model_info->name : ''; ?>" required />
                        </div>
                        
                        <div class="form-group">
                            <label>CPF</label>
                            <input type="text" name="cpf" class="form-control" value="<?php echo isset($model_info) ? $model_info->cpf : ''; ?>" />
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>E-mail</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo isset($model_info) ? $model_info->email : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Telefone</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo isset($model_info) ? $model_info->phone : ''; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Registro Profissional</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <select name="council_type" class="form-control">
                                        <option value="CREA" <?php echo (isset($model_info) && $model_info->council_type === 'CREA') ? 'selected' : ''; ?>>CREA</option>
                                        <option value="CAU" <?php echo (isset($model_info) && $model_info->council_type === 'CAU') ? 'selected' : ''; ?>>CAU</option>
                                        <option value="CRM" <?php echo (isset($model_info) && $model_info->council_type === 'CRM') ? 'selected' : ''; ?>>CRM</option>
                                        <option value="CREF" <?php echo (isset($model_info) && $model_info->council_type === 'CREF') ? 'selected' : ''; ?>>CREF</option>
                                        <option value="OUTROS" <?php echo (isset($model_info) && $model_info->council_type === 'OUTROS') ? 'selected' : ''; ?>>Outros</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Número</label>
                                    <input type="text" name="council_number" class="form-control" value="<?php echo isset($model_info) ? $model_info->council_number : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <input type="text" name="council_state" class="form-control" value="<?php echo isset($model_info) ? $model_info->council_state : ''; ?>" maxlength="2" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Especialidade</label>
                                    <input type="text" name="specialty" class="form-control" value="<?php echo isset($model_info) ? $model_info->specialty : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cargo</label>
                                    <input type="text" name="role" class="form-control" value="<?php echo isset($model_info) ? $model_info->role : ''; ?>" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Validade Início</label>
                                    <input type="date" name="validity_start" class="form-control" value="<?php echo isset($model_info) ? $model_info->validity_start : ''; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Validade Fim</label>
                                    <input type="date" name="validity_end" class="form-control" value="<?php echo isset($model_info) ? $model_info->validity_end : ''; ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>ART / RRT</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Número ART</label>
                            <input type="text" name="art_number" class="form-control" value="<?php echo isset($model_info) ? $model_info->art_number : ''; ?>" />
                        </div>
                        <div class="form-group">
                            <label>Número RRT</label>
                            <input type="text" name="rrt_number" class="form-control" value="<?php echo isset($model_info) ? $model_info->rrt_number : ''; ?>" />
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Status</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Situação</label>
                            <select name="status" class="form-control">
                                <option value="active" <?php echo (isset($model_info) && $model_info->status === 'active') ? 'selected' : ''; ?>>Ativo</option>
                                <option value="inactive" <?php echo (isset($model_info) && $model_info->status === 'inactive') ? 'selected' : ''; ?>>Inativo</option>
                                <option value="suspended" <?php echo (isset($model_info) && $model_info->status === 'suspended') ? 'selected' : ''; ?>>Suspenso</option>
                            </select>
                        </div>
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
$('#professional-form').submit(function(e) {
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
                    window.location.href = '<?php echo get_uri('laudo_review/professionals'); ?>';
                }, 1000);
            } else {
                appAlert.error(response.message);
            }
        }
    });
});
</script>