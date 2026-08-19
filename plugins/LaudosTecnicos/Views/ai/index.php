<?php
$settings = is_array($settings ?? null) ? $settings : array();
$prompts = is_array($prompts ?? null) ? $prompts : array();
$usage_stats = is_array($usage_stats ?? null) ? $usage_stats : array();
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h1>Inteligencia artificial</h1>
        <div class="title-button-group">
            <button class="btn btn-primary" id="laudostecnicos-ai-save">Salvar configuracoes</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <?php echo form_open(get_uri('laudostecnicos/ia/save_settings'), array('id' => 'laudostecnicos-ai-settings-form', 'class' => 'general-form')); ?>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Provedor</label><input type="text" name="ai_provider" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_provider')); ?>"></div>
                            <div class="col-md-8"><label class="form-label">URL do endpoint</label><input type="text" name="ai_endpoint_url" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_endpoint_url')); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Token</label><input type="text" name="ai_api_token" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_api_token')); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Modelo</label><input type="text" name="ai_model" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_model')); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Temperatura</label><input type="number" step="0.1" name="ai_temperature" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_temperature')); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Limite tokens</label><input type="number" name="ai_token_limit" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_token_limit')); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Timeout</label><input type="number" name="ai_timeout" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_timeout')); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Limite usuario</label><input type="number" name="ai_user_limit" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_user_limit')); ?>"></div>
                            <div class="col-md-12"><label class="form-label">Template global</label><textarea name="ai_prompt_template" class="form-control" rows="4"><?php echo esc(get_array_value($settings, 'ai_prompt_template')); ?></textarea></div>
                            <div class="col-md-12"><label class="form-label">Recursos permitidos</label><input type="text" name="ai_allowed_resources" class="form-control" value="<?php echo esc(get_array_value($settings, 'ai_allowed_resources')); ?>"></div>
                        </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="small text-muted">Consumo</div>
                    <div class="fs-4 fw-bold"><?php echo (int) get_array_value($usage_stats, 'ai_requests'); ?> solicitações</div>
                    <div class="small text-muted mt-2">Use a IA apenas como rascunho assistido. Nunca aprova laudos.</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Biblioteca de prompts</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Codigo</th><th>Nome</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($prompts as $prompt) { ?>
                                    <tr>
                                        <td><?php echo esc($prompt->code ?? '-'); ?></td>
                                        <td><?php echo esc($prompt->name ?? '-'); ?></td>
                                        <td><?php echo !empty($prompt->is_active) ? 'Ativo' : 'Inativo'; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function () {
    $("#laudostecnicos-ai-save").on("click", function () {
        $("#laudostecnicos-ai-settings-form").trigger("submit");
    });

    $("#laudostecnicos-ai-settings-form").appForm({
        onSuccess: function (result) {
            if (result && result.success) {
                appAlert.success(result.message || "Salvo.");
            } else {
                appAlert.error((result && result.message) ? result.message : "Erro.");
            }
        }
    });
});
</script>
