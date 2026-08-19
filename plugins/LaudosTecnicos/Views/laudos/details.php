<?php
$model_info = $model_info ?? (object) array();
$history_logs = is_array($history_logs ?? null) ? $history_logs : array();
$audit_logs = is_array($audit_logs ?? null) ? $audit_logs : array();
$allowed_transitions = is_array($allowed_transitions ?? null) ? $allowed_transitions : array();
$document_version = $document_version ?? null;
$can_edit_laudos = !empty($can_edit_laudos);
$can_change_status = !empty($can_change_status);
$can_delete_drafts = !empty($can_delete_drafts);

$status_name = $model_info->status_name ?? $model_info->status ?? '-';
$status_color = $model_info->status_color ?? '#6c757d';
$status_icon = $model_info->status_icon ?? 'circle';
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1 class="pl0">
                    <i data-feather="file-text" class="icon"></i>
                    <?php echo esc($model_info->number ?? '-'); ?> - <?php echo esc($model_info->title ?? '-'); ?>
                </h1>
                <div class="mt10">
                    <span class="badge text-white" style="background: <?php echo esc($status_color); ?>;">
                        <i data-feather="<?php echo esc($status_icon); ?>" class="icon-14 me5"></i>
                        <?php echo esc($status_name); ?>
                    </span>
                    <span class="badge bg-light text-dark ms10"><?php echo esc($model_info->priority ?? '-'); ?></span>
                </div>
            </div>
            <div class="title-button-group">
                <div class="btn-group me-2">
                    <a class="btn btn-outline-secondary" href="<?php echo esc(get_uri('laudostecnicos/laudos/document/' . $model_info->id . '/full')); ?>" target="_blank">HTML</a>
                    <a class="btn btn-outline-primary" href="<?php echo esc(get_uri('laudostecnicos/laudos/pdf/' . $model_info->id . '/full')); ?>" target="_blank">PDF</a>
                </div>
                <?php if (!empty($document_version->public_key)) { ?>
                    <div class="btn-group me-2">
                        <a class="btn btn-outline-dark" href="<?php echo esc(laudostecnicos_public_validation_url((int) $model_info->id, (string) $document_version->public_key)); ?>" target="_blank">Validacao</a>
                    </div>
                <?php } ?>
                <?php echo modal_anchor(get_uri('laudostecnicos/laudos/share-modal/' . $model_info->id), "<i data-feather='share-2' class='icon-16'></i> Compartilhar", array('class' => 'btn btn-outline-success')); ?>
                <?php if ($can_change_status && $allowed_transitions) { ?>
                    <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#laudo-change-status-box" type="button">Alterar status</button>
                <?php } ?>
                <?php if ($can_edit_laudos) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/laudos/modal_form/' . $model_info->id), "<i data-feather='edit' class='icon-16'></i> Editar", array('class' => 'btn btn-primary')); ?>
                <?php } ?>
                <?php echo modal_anchor(get_uri('laudostecnicos/laudos/duplicate_modal_form/' . $model_info->id), "<i data-feather='copy' class='icon-16'></i> Duplicar", array('class' => 'btn btn-outline-secondary')); ?>
                <?php if ($can_delete_drafts && ($model_info->status ?? '') === 'draft') { ?>
                    <?php echo js_anchor("<i data-feather='trash-2' class='icon-16'></i> Excluir", array('class' => 'btn btn-outline-danger delete', 'data-id' => $model_info->id, 'data-action-url' => get_uri('laudostecnicos/laudos/delete'), 'data-action' => 'delete-confirmation')); ?>
                <?php } ?>
            </div>
        </div>

        <?php if ($can_change_status && $allowed_transitions) { ?>
            <div class="collapse" id="laudo-change-status-box">
                <div class="card card-body mb20">
                    <?php echo form_open(get_uri('laudostecnicos/laudos/change_status/' . $model_info->id), array('id' => 'laudo-change-status-form', 'class' => 'general-form')); ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Novo status</label>
                                <select name="to_status_code" class="form-select" required>
                                    <option value="">-</option>
                                    <?php foreach ($allowed_transitions as $transition) { ?>
                                        <option value="<?php echo esc($transition->to_status_code); ?>"><?php echo esc($transition->to_status_name ?: $transition->to_status_code); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Comentário</label>
                                <input type="text" name="comment" class="form-control">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Aplicar</button>
                            </div>
                        </div>
                        <input type="hidden" name="source" value="web">
                    <?php echo form_close(); ?>
                </div>
            </div>
        <?php } ?>

        <ul class="nav nav-tabs scrollable-tabs rounded mb20" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#laudo-resumo">Resumo</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#laudo-conteudo">Conteúdo técnico</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Inspeções</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Checklists</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Medições</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Fotografias</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Não conformidades</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Plano de ação</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Revisões</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Assinaturas</a></li>
            <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0);">Arquivos</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#laudo-historico">Histórico</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#laudo-auditoria">Auditoria</a></li>
        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade show active" id="laudo-resumo">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4"><strong>Cliente:</strong><br><?php echo esc($model_info->client_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Projeto:</strong><br><?php echo esc($model_info->project_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Tipo:</strong><br><?php echo esc($model_info->type_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Categoria:</strong><br><?php echo esc($model_info->category_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Responsável:</strong><br><?php echo esc($model_info->technical_responsible_name ?? $model_info->commercial_responsible_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Contato:</strong><br><?php echo esc($model_info->contact_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Solicitação:</strong><br><?php echo esc($model_info->request_date ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Inspeção:</strong><br><?php echo esc($model_info->inspection_date ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Emissão:</strong><br><?php echo esc($model_info->issue_date ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Validade:</strong><br><?php echo esc($model_info->validity_date ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Unidade:</strong><br><?php echo esc($model_info->unit_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Prioridade:</strong><br><?php echo esc($model_info->priority ?? '-'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header"><h5 class="mb-0">Resumo operacional</h5></div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Código:</strong> <?php echo esc($model_info->custom_code ?? '-'); ?></p>
                                <p class="mb-2"><strong>Revisão:</strong> <?php echo esc($model_info->revision ?? '00'); ?></p>
                                <p class="mb-2"><strong>Status:</strong> <?php echo esc($status_name); ?></p>
                                <p class="mb-2"><strong>Observações para o cliente:</strong><br><?php echo nl2br(esc($model_info->client_observations ?? '-')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane fade" id="laudo-conteudo">
                <div class="row g-3">
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Objetivo</strong><div class="mt10"><?php echo nl2br(esc($model_info->objective ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Escopo</strong><div class="mt10"><?php echo nl2br(esc($model_info->scope ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Metodologia</strong><div class="mt10"><?php echo nl2br(esc($model_info->methodology ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Premissas</strong><div class="mt10"><?php echo nl2br(esc($model_info->premises ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Limitações</strong><div class="mt10"><?php echo nl2br(esc($model_info->limitations ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Descrição da instalação</strong><div class="mt10"><?php echo nl2br(esc($model_info->installation_description ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Resultados</strong><div class="mt10"><?php echo nl2br(esc($model_info->results ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Diagnóstico</strong><div class="mt10"><?php echo nl2br(esc($model_info->diagnosis ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Conclusão</strong><div class="mt10"><?php echo nl2br(esc($model_info->conclusion ?? '-')); ?></div></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><strong>Recomendações</strong><div class="mt10"><?php echo nl2br(esc($model_info->recommendations ?? '-')); ?></div></div></div></div>
                    <div class="col-md-12"><div class="card"><div class="card-body"><strong>Observações internas</strong><div class="mt10"><?php echo nl2br(esc($model_info->internal_notes ?? '-')); ?></div></div></div></div>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane fade" id="laudo-historico">
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Status anterior</th>
                                    <th>Novo status</th>
                                    <th>Usuário</th>
                                    <th>Data/hora</th>
                                    <th>Comentário</th>
                                    <th>Origem</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($history_logs) { ?>
                                    <?php foreach ($history_logs as $log) { ?>
                                        <tr>
                                            <td><?php echo esc($log->from_status_name ?: $log->from_status_code ?: '-'); ?></td>
                                            <td><?php echo esc($log->to_status_name ?: $log->to_status_code ?: '-'); ?></td>
                                            <td><?php echo esc($log->user_name ?: '-'); ?></td>
                                            <td><?php echo esc($log->created_at ?: '-'); ?></td>
                                            <td><?php echo esc($log->comment ?: '-'); ?></td>
                                            <td><?php echo esc($log->source ?: '-'); ?></td>
                                            <td><?php echo esc($log->ip_address ?: '-'); ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td colspan="7" class="text-muted">Nenhum histórico registrado.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane fade" id="laudo-auditoria">
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ação</th>
                                    <th>Usuário</th>
                                    <th>Data/hora</th>
                                    <th>Origem</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($audit_logs) { ?>
                                    <?php foreach ($audit_logs as $log) { ?>
                                        <tr>
                                            <td><?php echo esc($log->action ?: '-'); ?></td>
                                            <td><?php echo esc($log->user_name ?: '-'); ?></td>
                                            <td><?php echo esc($log->created_at ?: '-'); ?></td>
                                            <td><?php echo esc($log->source ?: '-'); ?></td>
                                            <td><?php echo esc($log->description ?: '-'); ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td colspan="5" class="text-muted">Nenhum evento de auditoria registrado.</td></tr>
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
        $("#laudo-change-status-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    location.reload();
                }
            }
        });
    });
</script>
