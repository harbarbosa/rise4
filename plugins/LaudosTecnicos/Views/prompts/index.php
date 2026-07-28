<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="page-title clearfix">
                    <h1>Biblioteca de Prompts</h1>
                </div>
                
                <div class="card-body">
                    <p class="text-muted">Selecione um prompt e preencha as variáveis para gerar conteúdo com IA.</p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <select id="prompt_select" class="form-control">
                                <option value="">Selecione um prompt...</option>
                                <?php foreach ($prompts as $p): ?>
                                <option value="<?php echo $p->code; ?>"><?php echo $p->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary" onclick="loadPrompt()">Carregar</button>
                        </div>
                    </div>
                    
                    <div id="prompt_form" style="display: none;">
                        <h4 id="prompt_name"></h4>
                        <p id="prompt_variables" class="text-muted"></p>
                        
                        <div id="variables_container"></div>
                        
                        <button class="btn btn-success mt-3" onclick="executePrompt()">
                            <i data-feather="zap" class="icon-16"></i> Executar
                        </button>
                    </div>
                    
                    <div id="prompt_result" class="mt-3" style="display: none;">
                        <h5>Resultado:</h5>
                        <div class="alert alert-info" id="result_text"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var currentPromptId = null;
var currentVariables = [];

function loadPrompt() {
    var code = $('#prompt_select').val();
    if (!code) return;
    
    $.ajax({
        url: '<?php echo get_uri("laudo_prompts_lib/use_prompt/"); ?>' + code,
        success: function(response) {
            if (response.success) {
                currentPromptId = response.data.id;
                currentVariables = response.data.variables || [];
                
                $('#prompt_name').text(response.data.name);
                $('#prompt_form').show();
                
                // Criar campos para variáveis
                var html = '';
                currentVariables.forEach(function(v) {
                    html += '<div class="form-group mb-2">';
                    html += '<label>' + v.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</label>';
                    html += '<textarea id="var_' + v + '" class="form-control" rows="3"></textarea>';
                    html += '</div>';
                });
                $('#variables_container').html(html);
            }
        }
    });
}

function executePrompt() {
    var variables = {};
    currentVariables.forEach(function(v) {
        variables[v] = $('#var_' + v).val();
    });
    
    $.ajax({
        url: '<?php echo get_uri("laudo_prompts_lib/execute"); ?>',
        type: 'POST',
        data: { prompt_id: currentPromptId, variables: variables },
        success: function(response) {
            if (response.success) {
                $('#result_text').text(response.data.response);
                $('#prompt_result').show();
            } else {
                appAlert.error(response.message);
            }
        }
    });
}
</script>