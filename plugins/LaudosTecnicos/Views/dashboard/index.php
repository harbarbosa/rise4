<?php
$cards = is_array($cards ?? null) ? $cards : array();
$recent_laudos = is_array($recent_laudos ?? null) ? $recent_laudos : array();
$nc_stats = is_array($nc_stats ?? null) ? $nc_stats : array();
$plan_stats = is_array($plan_stats ?? null) ? $plan_stats : array();
$platform_summary = is_array($platform_summary ?? null) ? $platform_summary : array();
$recent_activity = is_array($recent_activity ?? null) ? $recent_activity : array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_dashboard_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_settings)) { ?>
                    <?php echo anchor(get_uri('laudostecnicos/configuracoes'), "<i data-feather='settings' class='icon-16'></i> " . app_lang('laudostecnicos_settings'), array('class' => 'btn btn-outline-secondary')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <p class="text-muted mb-4"><?php echo app_lang('laudostecnicos_dashboard_subtitle'); ?></p>

            <div class="row g-3">
                <?php foreach ($cards as $card) { ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="rounded p-3 <?php echo esc($card['class']); ?>">
                            <div class="small opacity-75"><?php echo esc($card['title']); ?></div>
                            <div class="fs-3 fw-bold"><?php echo (int) $card['value']; ?></div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="row mt30 g-3">
                <div class="col-md-3"><div class="rounded p-3 bg-primary text-white"><div class="small opacity-75">API requests</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($platform_summary, 'api_requests'); ?></div></div></div>
                <div class="col-md-3"><div class="rounded p-3 bg-info text-white"><div class="small opacity-75">AI requests</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($platform_summary, 'ai_requests'); ?></div></div></div>
                <div class="col-md-3"><div class="rounded p-3 bg-success text-white"><div class="small opacity-75">Sync records</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($platform_summary, 'synced_records'); ?></div></div></div>
                <div class="col-md-3"><div class="rounded p-3 bg-secondary text-white"><div class="small opacity-75">Devices</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($platform_summary, 'devices'); ?></div></div></div>
            </div>

            <div class="mt30">
                <h5 class="mb15"><?php echo app_lang('laudostecnicos_nonconformities_title'); ?></h5>
                <div class="row g-3">
                    <div class="col-md-3"><div class="rounded p-3 bg-danger text-white"><div class="small opacity-75">NCs abertas</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'open'); ?></div></div></div>
                    <div class="col-md-3"><div class="rounded p-3 bg-dark text-white"><div class="small opacity-75">Criticas</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'critical'); ?></div></div></div>
                    <div class="col-md-3"><div class="rounded p-3 bg-warning text-dark"><div class="small opacity-75">Vencidas</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'expired'); ?></div></div></div>
                    <div class="col-md-3"><div class="rounded p-3 bg-success text-white"><div class="small opacity-75">Corrigidas</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'corrected'); ?></div></div></div>
                    <div class="col-md-3"><div class="rounded p-3 bg-info text-white"><div class="small opacity-75">Aguardando validacao</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'awaiting_validation'); ?></div></div></div>
                    <div class="col-md-3"><div class="rounded p-3 bg-primary text-white"><div class="small opacity-75">Media correcao</div><div class="fs-3 fw-bold"><?php echo esc(get_array_value($nc_stats, 'avg_correction_days')); ?></div></div></div>
                    <div class="col-md-3"><div class="rounded p-3 bg-danger text-white"><div class="small opacity-75">Planos atrasados</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($plan_stats, 'late'); ?></div></div></div>
                    <div class="col-md-3"><div class="rounded p-3 bg-secondary text-white"><div class="small opacity-75">Planos em aberto</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($plan_stats, 'open'); ?></div></div></div>
                </div>
            </div>

            <div class="row mt30 g-3">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0"><?php echo app_lang('laudostecnicos_recent_laudos'); ?></h5>
                            <span class="badge bg-light text-dark"><?php echo count($recent_laudos); ?></span>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th><?php echo app_lang('title'); ?></th>
                                        <th><?php echo app_lang('status'); ?></th>
                                        <th><?php echo app_lang('date'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_laudos) { ?>
                                        <?php foreach ($recent_laudos as $laudo) { ?>
                                            <tr>
                                                <td><?php echo esc($laudo->title ?? '-'); ?></td>
                                                <td><?php echo esc($laudo->status ?? '-'); ?></td>
                                                <td><?php echo esc($laudo->created_at ?? '-'); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="3" class="text-muted"><?php echo app_lang('laudostecnicos_empty_state'); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Atividade recente</h5>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Acao</th>
                                        <th>Entidade</th>
                                        <th>Usuario</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_activity) { ?>
                                        <?php foreach ($recent_activity as $item) { ?>
                                            <tr>
                                                <td><?php echo esc($item->action ?? '-'); ?></td>
                                                <td><?php echo esc(($item->entity_type ?? '-') . ' #' . (int) ($item->entity_id ?? 0)); ?></td>
                                                <td><?php echo esc($item->user_name ?? '-'); ?></td>
                                                <td><?php echo esc($item->created_at ?? '-'); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr><td colspan="4" class="text-muted"><?php echo app_lang('laudostecnicos_empty_state'); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
