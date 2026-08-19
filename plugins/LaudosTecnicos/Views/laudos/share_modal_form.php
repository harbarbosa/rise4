<?php
$model_info = $model_info ?? (object) array();
$document_version = $document_version ?? null;
$share = $share ?? null;
$variant = $document_version->variant ?? 'full';
$share_url = !empty($share->share_token) ? get_uri('laudostecnicos/laudos/share/' . $share->share_token) : '';
?>
<div class="modal-body clearfix">
    <?php echo form_open(get_uri('laudostecnicos/laudos/share/' . (int) $model_info->id), array('id' => 'laudo-share-form', 'class' => 'general-form', 'role' => 'form')); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Versao</label>
                <select name="variant" class="form-select">
                    <option value="full">Laudo completo</option>
                    <option value="executive">Resumo executivo</option>
                    <option value="photo">Relatorio fotografico</option>
                    <option value="nc">Nao conformidades</option>
                    <option value="action-plan">Plano de acao</option>
                    <option value="acceptance">Termo de aceite</option>
                    <option value="certificate">Certificado</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Rotulo do visitante</label>
                <input type="text" name="visitor_label" class="form-control" value="<?php echo esc($share->visitor_label ?? ''); ?>" placeholder="Cliente / responsavel / auditor">
            </div>
            <div class="col-md-4">
                <label class="form-label">Validade</label>
                <input type="datetime-local" name="expires_at" class="form-control" value="">
            </div>
            <div class="col-md-4">
                <label class="form-label">Limite de acessos</label>
                <input type="number" name="max_accesses" class="form-control" min="0" step="1" value="<?php echo esc($share->max_accesses ?? 0); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" placeholder="Opcional">
            </div>
            <div class="col-md-4 form-check mt-4">
                <?php echo form_checkbox('allow_download', '1', !empty($share->allow_download), "class='form-check-input' id='allow_download'"); ?>
                <label class="form-check-label" for="allow_download">Permitir download</label>
            </div>
            <div class="col-md-4 form-check mt-4">
                <?php echo form_checkbox('allow_comments', '1', !empty($share->allow_comments), "class='form-check-input' id='allow_comments'"); ?>
                <label class="form-check-label" for="allow_comments">Permitir comentarios</label>
            </div>
            <div class="col-md-4 form-check mt-4">
                <?php echo form_checkbox('require_visitor_id', '1', !empty($share->require_visitor_id), "class='form-check-input' id='require_visitor_id'"); ?>
                <label class="form-check-label" for="require_visitor_id">Exigir identificacao</label>
            </div>
            <div class="col-md-12">
                <label class="form-label">Link atual</label>
                <input type="text" class="form-control" readonly value="<?php echo esc($share_url ?: ''); ?>">
            </div>
        </div>
        <input type="hidden" name="current_variant" value="<?php echo esc($variant); ?>">
    <?php echo form_close(); ?>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
    <button type="button" class="btn btn-primary" id="save-share-btn">Salvar link</button>
</div>

<script type="text/javascript">
    $(function () {
        $("#save-share-btn").on("click", function () {
            $("#laudo-share-form").trigger("submit");
        });

        $("#laudo-share-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    appAlert.success(result.message || "Salvo com sucesso.");
                    if (result.share_url) {
                        window.open(result.share_url, "_blank");
                    }
                } else {
                    appAlert.error((result && result.message) ? result.message : "Erro ao salvar.");
                }
            }
        });
    });
</script>
