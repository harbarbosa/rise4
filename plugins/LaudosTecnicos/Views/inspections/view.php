<?php
$inspection = $inspection ?? (object) array();
$checkins = is_array($checkins ?? null) ? $checkins : array();
$photos = is_array($photos ?? null) ? $photos : array();
$completion_issues = is_array($completion_issues ?? null) ? $completion_issues : array();
$checklist_progress = is_object($checklist_progress ?? null) ? $checklist_progress : (object) array('total' => 0, 'answered' => 0, 'pending' => 0, 'non_conforming' => 0, 'critical' => 0);
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1 class="pl0">
                    <i data-feather="camera" class="icon"></i>
                    <?php echo esc($inspection->code ?? '-'); ?> - <?php echo esc($inspection->laudo_title ?? '-'); ?>
                </h1>
                <div class="mt10">
                    <span class="badge bg-secondary"><?php echo esc($inspection->status ?? '-'); ?></span>
                    <span class="badge bg-light text-dark ms10"><?php echo esc($inspection->inspection_date ?? '-'); ?> <?php echo esc($inspection->start_time ?? ''); ?></span>
                </div>
            </div>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('laudostecnicos/inspecoes/modal_form/' . $inspection->id), "<i data-feather='edit' class='icon-16'></i> Editar", array('class' => 'btn btn-outline-primary')); ?>
                <?php echo js_anchor("<i data-feather='play' class='icon-16'></i> Iniciar", array('class' => 'btn btn-success', 'data-id' => $inspection->id, 'data-action-url' => get_uri('laudostecnicos/inspecoes/start/' . $inspection->id), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true)); ?>
                <?php echo js_anchor("<i data-feather='pause' class='icon-16'></i> Pausar", array('class' => 'btn btn-warning', 'data-id' => $inspection->id, 'data-action-url' => get_uri('laudostecnicos/inspecoes/pause/' . $inspection->id), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true)); ?>
                <?php echo js_anchor("<i data-feather='check-circle' class='icon-16'></i> Finalizar", array('class' => 'btn btn-primary', 'data-id' => $inspection->id, 'data-action-url' => get_uri('laudostecnicos/inspecoes/finish/' . $inspection->id), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true)); ?>
            </div>
        </div>

        <ul class="nav nav-tabs scrollable-tabs rounded mb20" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#inspection-summary-tab">Resumo</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inspection-field-tab">Execucao</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inspection-photos-tab">Fotografias</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inspection-checkins-tab">Check-in</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inspection-completion-tab">Conclusao</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="inspection-summary-tab">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4"><strong>Cliente:</strong><br><?php echo esc($inspection->client_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Unidade:</strong><br><?php echo esc($inspection->unit_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Local:</strong><br><?php echo esc($inspection->location_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Responsavel:</strong><br><?php echo esc($inspection->responsible_name ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Veiculo:</strong><br><?php echo esc($inspection->vehicle ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Tipo:</strong><br><?php echo esc($inspection->inspection_type ?? '-'); ?></div>
                                    <div class="col-md-4"><strong>Equipe:</strong><br><?php echo esc($inspection->team_json ?? '[]'); ?></div>
                                    <div class="col-md-4"><strong>Equipamentos:</strong><br><?php echo esc($inspection->equipments_json ?? '[]'); ?></div>
                                    <div class="col-md-4"><strong>Progresso:</strong><br><?php echo esc($inspection->progress_percent ?? 0); ?>%</div>
                                    <div class="col-md-12"><strong>Endereco:</strong><br><?php echo nl2br(esc($inspection->address ?? '-')); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-2"><strong>Checklist concluido:</strong> <?php echo (int) ($checklist_progress->answered ?? 0); ?>/<?php echo (int) ($checklist_progress->total ?? 0); ?></p>
                                <p class="mb-2"><strong>Pendencias:</strong> <?php echo (int) ($checklist_progress->pending ?? 0); ?></p>
                                <p class="mb-2"><strong>Nao conformes:</strong> <?php echo (int) ($checklist_progress->non_conforming ?? 0); ?></p>
                                <p class="mb-2"><strong>Criticos:</strong> <?php echo (int) ($checklist_progress->critical ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="inspection-field-tab">
                <div class="alert alert-info">Tela mobile responsiva para operacao em campo. Use as acoes de check-in, fotos e conclusao nas demais abas.</div>
                <div class="row g-3">
                    <div class="col-md-3"><?php echo js_anchor("Check-in", array('class' => 'btn btn-outline-success w-100', 'data-id' => $inspection->id, 'data-action-url' => get_uri('laudostecnicos/inspecoes/checkin/' . $inspection->id), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true)); ?></div>
                    <div class="col-md-3"><?php echo js_anchor("Check-out", array('class' => 'btn btn-outline-secondary w-100', 'data-id' => $inspection->id, 'data-action-url' => get_uri('laudostecnicos/inspecoes/checkout/' . $inspection->id), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true)); ?></div>
                    <div class="col-md-3"><?php echo js_anchor("Marcar improdutiva", array('class' => 'btn btn-outline-danger w-100', 'data-id' => $inspection->id, 'data-action-url' => get_uri('laudostecnicos/inspecoes/improductive/' . $inspection->id), 'data-action' => 'delete-confirmation', 'data-reload-on-success' => true)); ?></div>
                    <div class="col-md-3"><?php echo anchor(get_uri('laudostecnicos/inspecoes/validate_completion/' . $inspection->id), "Validar conclusao", array('class' => 'btn btn-outline-primary w-100', 'target' => '_blank')); ?></div>
                </div>
            </div>

            <div class="tab-pane fade" id="inspection-photos-tab">
                <div class="card mb20">
                    <div class="card-body">
                        <?php echo form_open_multipart(get_uri('laudostecnicos/inspecoes/upload_photo'), array('id' => 'inspection-photo-form', 'class' => 'general-form')); ?>
                        <input type="hidden" name="inspection_id" value="<?php echo esc($inspection->id); ?>">
                        <input type="hidden" name="laudo_id" value="<?php echo esc($inspection->laudo_id); ?>">
                        <div class="row g-3">
                            <div class="col-md-4"><input type="file" name="photo" class="form-control" accept="image/*" capture="environment" required></div>
                            <div class="col-md-4"><input type="text" name="caption" class="form-control" placeholder="Legenda"></div>
                            <div class="col-md-4"><button class="btn btn-primary" type="submit">Enviar foto</button></div>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
                <div class="row g-3">
                    <?php foreach ($photos as $photo) { ?>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <img src="<?php echo esc(get_source_url_of_file(array('file_name' => (string) ($photo->thumbnail_path ?: $photo->file_path)), '')); ?>" class="card-img-top" style="height: 180px; object-fit: cover;">
                                <div class="card-body">
                                    <div class="fw-bold"><?php echo esc($photo->caption ?: ('Foto ' . ($photo->photo_number ?? ''))); ?></div>
                                    <div class="text-muted small"><?php echo esc($photo->original_file_name ?: '-'); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="tab-pane fade" id="inspection-checkins-tab">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Data/hora</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>Precisao</th>
                                    <th>Dispositivo</th>
                                    <th>Distancia</th>
                                    <th>Observacao</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($checkins as $checkin) { ?>
                                    <tr>
                                        <td><?php echo esc($checkin->check_type ?? '-'); ?></td>
                                        <td><?php echo esc($checkin->checked_at ?? '-'); ?></td>
                                        <td><?php echo esc($checkin->latitude ?? '-'); ?></td>
                                        <td><?php echo esc($checkin->longitude ?? '-'); ?></td>
                                        <td><?php echo esc($checkin->accuracy ?? '-'); ?></td>
                                        <td><?php echo esc($checkin->device ?? '-'); ?></td>
                                        <td><?php echo esc($checkin->distance_meters ?? '-'); ?></td>
                                        <td><?php echo esc($checkin->observation ?? '-'); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="inspection-completion-tab">
                <div class="card">
                    <div class="card-body">
                        <?php if ($completion_issues) { ?>
                            <div class="alert alert-warning">
                                <strong>Pendencias identificadas:</strong>
                                <ul class="mb-0">
                                    <?php foreach ($completion_issues as $issue) { ?>
                                        <li><?php echo esc($issue); ?></li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <div class="mb-3">
                                <?php echo form_open(get_uri('laudostecnicos/inspecoes/finish/' . $inspection->id), array('id' => 'finish-incomplete-form', 'class' => 'general-form')); ?>
                                    <input type="hidden" name="allow_incomplete" value="1">
                                    <div class="row g-3">
                                        <div class="col-md-10"><input type="text" name="incomplete_reason" class="form-control" placeholder="Justificativa para salvar incompleto"></div>
                                        <div class="col-md-2"><button class="btn btn-warning w-100" type="submit">Salvar incompleto</button></div>
                                    </div>
                                <?php echo form_close(); ?>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-success">A inspeção atende os requisitos de conclusão.</div>
                        <?php } ?>
                        <?php echo anchor(get_uri('laudostecnicos/inspecoes/validate_completion/' . $inspection->id), "Revalidar", array('class' => 'btn btn-outline-primary', 'target' => '_blank')); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $("#inspection-photo-form").on("submit", function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append(AppHelper.csrfTokenName, AppHelper.csrfHash);
            appAjaxRequest({
                url: "<?php echo get_uri('laudostecnicos/inspecoes/upload_photo'); ?>",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (result) {
                    if (result && result.success) {
                        window.location.reload();
                    }
                }
            });
        });

        $("#finish-incomplete-form").appForm({
            onSuccess: function () {
                window.location.reload();
            }
        });
    });
</script>
