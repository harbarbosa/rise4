<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix"><h1><?php echo app_lang('assistente_ia_settings'); ?></h1></div>
        <form id="assistente-ia-settings" class="p-3 general-form">
            <div class="form-group">
                <label>Token do OpenRouter</label>
                <input type="password" name="openrouter_key" class="form-control" autocomplete="new-password">
                <small class="text-muted">O token fica no servidor e nunca é enviado ao navegador.</small>
            </div>
            <div class="form-group">
                <label>Modelo</label>
                <input type="text" name="model" class="form-control" value="openai/gpt-4o-mini" placeholder="provedor/modelo">
            </div>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </form>
    </div>
</div>
<script>
document.getElementById('assistente-ia-settings').addEventListener('submit', async function (event) {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(event.target));
    const response = await fetch('<?php echo get_uri('assistente-ia/configuracoes/save'); ?>', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: new URLSearchParams(data)});
    const result = await response.json();
    if (result.success) appAlert.success('Configurações salvas.'); else appAlert.error(result.error || 'Não foi possível salvar.');
});
</script>
