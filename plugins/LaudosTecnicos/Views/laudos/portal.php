<?php
$documents = is_array($documents ?? null) ? $documents : array();
$login_user = $login_user ?? null;
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h1>Portal do cliente - Laudos</h1>
    </div>

    <div class="row g-3">
        <?php if ($documents) { ?>
            <?php foreach ($documents as $document) { ?>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <h4 class="mb-1"><?php echo esc($document->laudo_number ?? '-'); ?> - <?php echo esc($document->laudo_title ?? '-'); ?></h4>
                                    <div class="text-muted"><?php echo esc($document->type_name ?? '-'); ?> | <?php echo esc($document->category_name ?? '-'); ?></div>
                                </div>
                                <span class="badge text-white" style="background: <?php echo esc($document->status_color ?? '#6c757d'); ?>;"><?php echo esc($document->status_name ?? '-'); ?></span>
                            </div>

                            <div class="mt-3 small text-muted">
                                Revisao <?php echo esc($document->laudo_revision ?? '00'); ?> | Emissao <?php echo esc($document->issued_at ?? '-'); ?>
                            </div>

                            <div class="mt-3">
                                <div><strong>Compartilhamento:</strong> <?php echo esc($document->share_token ?? '-'); ?></div>
                                <div><strong>Validade:</strong> <?php echo esc($document->expires_at ?? '-'); ?></div>
                                <div><strong>Visitante:</strong> <?php echo esc($document->visitor_label ?? '-'); ?></div>
                            </div>

                            <div class="mt-3 d-flex gap-2 flex-wrap">
                                <a href="<?php echo esc(get_uri('laudostecnicos/laudos/share/' . $document->share_token)); ?>" class="btn btn-outline-primary" target="_blank">Abrir</a>
                                <?php if (!empty($document->allow_download)) { ?>
                                    <a href="<?php echo esc(get_uri('laudostecnicos/laudos/share/' . $document->share_token . '/download')); ?>" class="btn btn-outline-secondary">Baixar PDF</a>
                                <?php } ?>
                                <?php if (!empty($document->public_key)) { ?>
                                    <a href="<?php echo esc(laudostecnicos_public_validation_url((int) $document->laudo_id, (string) $document->public_key)); ?>" class="btn btn-outline-dark" target="_blank">Validar</a>
                                <?php } ?>
                            </div>

                            <div class="mt-4">
                                <form class="general-form portal-feedback-form" method="post" action="<?php echo esc(get_uri('laudostecnicos/portal/feedback')); ?>">
                                    <input type="hidden" name="share_token" value="<?php echo esc($document->share_token ?? ''); ?>">
                                    <input type="hidden" name="evidence_json" class="evidence-json" value="[]">
                                    <input type="hidden" name="visitor_label" value="<?php echo esc($login_user->client_name ?? ($document->visitor_label ?? 'Cliente')); ?>">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <select name="action" class="form-select form-select-sm">
                                                <option value="comment">Comentario</option>
                                                <option value="accept">Aceitar</option>
                                                <option value="reject">Rejeitar</option>
                                                <option value="received">Confirmar recebimento</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" name="comment" class="form-control form-control-sm" placeholder="Comentario ou observacao">
                                        </div>
                                        <div class="col-md-12">
                                            <input type="text" class="form-control form-control-sm evidence-text" placeholder="Evidencias ou anexos descritos">
                                        </div>
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-sm btn-primary">Enviar retorno</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="col-md-12">
                <div class="alert alert-info">Nenhum laudo compartilhado disponivel.</div>
            </div>
        <?php } ?>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $(".portal-feedback-form").appForm({
            beforeAjaxSubmit: function (formData, form) {
                var $form = $(form);
                $form.find(".evidence-json").val(JSON.stringify($form.find(".evidence-text").val() ? [$form.find(".evidence-text").val()] : []));
            },
            onSuccess: function (result) {
                if (result && result.success) {
                    appAlert.success(result.message || "Enviado com sucesso.");
                } else {
                    appAlert.error((result && result.message) ? result.message : "Erro ao enviar.");
                }
            }
        });
    });
</script>
