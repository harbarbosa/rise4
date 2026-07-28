<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Configuração da Inteligência Artificial</h4>
                </div>
                <div class="card-body">
                    <form id="ai-config-form">
                        <input type="hidden" name="id" value="<?php echo isset($config) ? $config->id : ''; ?>" />
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Provedor</label>
                                    <select name="provider" class="form-control" id="provider">
                                        <?php foreach ($providers as $key => $p): ?>
                                        <option value="<?php echo $key; ?>" <?php echo (isset($config) && $config->provider === $key) ? 'selected' : ''; ?>>
                                            <?php echo $p['name']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Nome da Configuração</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo isset($config) ? $config->name : 'Principal'; ?>" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-2">
                            <label>URL da API</label>
                            <input type="url" name="api_url" class="form-control" value="<?php echo isset($config) ? $config->api_url : 'https://openrouter.ai/api/v1/chat/completions'; ?>" />
                        </div>
                        
                        <div class="form-group mb-2">
                            <label>API Key</label>
                            <input type="password" name="api_key" class="form-control" value="<?php echo isset($config) ? $config->api_key : ''; ?>" placeholder="Não exibida por segurança" />
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Modelo</label>
                                    <input type="text" name="model" class="form-control" value="<?php echo isset($config) ? $config->model : 'openai/gpt-3.5-turbo'; ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Temperatura</label>
                                    <input type="number" name="temperature" class="form-control" value="<?php echo isset($config) ? $config->temperature : 0.7; ?>" step="0.1" min="0" max="2" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Máx. Tokens</label>
                                    <input type="number" name="max_tokens" class="form-control" value="<?php echo isset($config) ? $config->max_tokens : 2000; ?>" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Timeout (segundos)</label>
                                    <input type="number" name="timeout" class="form-control" value="<?php echo isset($config) ? $config->timeout : 30; ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Limite Mensal</label>
                                    <input type="number" name="monthly_limit" class="form-control" value="<?php echo isset($config) ? $config->monthly_limit : 1000; ?>" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-2">
                            <label>Prompt do Sistema</label>
                            <textarea name="system_prompt" class="form-control" rows="3"><?php echo isset($config) ? $config->system_prompt : 'Você é um assistente técnico especializado em laudos técnicos, normas regulamentadoras (NRs), segurança do trabalho e legislação brasileira. Forneça respostas precisas, técnicas e profissionais.'; ?></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                <?php echo (!isset($config) || $config->is_active) ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="is_active">Ativo</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><i data-feather="save" class="icon-16"></i> Salvar</button>
                        <button type="button" class="btn btn-info" onclick="testAI()"><i data-feather="zap" class="icon-16"></i> Testar</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Recursos Disponíveis</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item">📝 Organizar anotações</li>
                        <li class="list-group-item">✨ Melhorar texto</li>
                        <li class="list-group-item">🎯 Criar objetivo</li>
                        <li class="list-group-item">📋 Definir escopo</li>
                        <li class="list-group-item">🔬 Metodologia</li>
                        <li class="list-group-item">🔍 Diagnóstico</li>
                        <li class="list-group-item">📌 Conclusão</li>
                        <li class="list-group-item">💡 Recomendações</li>
                        <li class="list-group-item">📊 Resumo executivo</li>
                        <li class="list-group-item">📸 Descrever foto</li>
                        <li class="list-group-item">⚠️ Verificar lacunas</li>
                        <li class="list-group-item">✅ Verificar inconsistências</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Uso Mensal</h5>
                </div>
                <div class="card-body text-center">
                    <h2><?php echo isset($config) ? $config->current_usage : 0; ?></h2>
                    <p class="text-muted">de <?php echo isset($config) ? $config->monthly_limit : 1000; ?> solicitações</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$('#ai-config-form').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: '<?php echo get_uri("laudo_ai/save_config"); ?>',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response.success) {
                appAlert.success(response.message);
            } else {
                appAlert.error(response.message);
            }
        }
    });
});

function testAI() {
    $.ajax({
        url: '<?php echo get_uri("laudo_ai/test"); ?>',
        type: 'POST',
        success: function(response) {
            if (response.success) {
                appAlert.success(response.message);
            } else {
                appAlert.error(response.message);
            }
        }
    });
}
</script>