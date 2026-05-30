<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Aprovacoes</h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('travelrefunds'), '<i data-feather="grid" class="icon-16"></i>', array('class' => 'btn btn-default', 'title' => 'Dashboard')); ?>
                <?php echo anchor(get_uri('travelrefunds/trips'), '<i data-feather="map-pin" class="icon-16"></i>', array('class' => 'btn btn-default', 'title' => 'Minhas Viagens')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports'), '<i data-feather="bar-chart-2" class="icon-16"></i>', array('class' => 'btn btn-default', 'title' => 'Relatorios')); ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <p class="text-muted mb-0">Mostra apenas viagens enviadas para aprovacao. O aprovador pode aprovar a viagem inteira ou tratar cada despesa individualmente.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Titulo</th>
                            <th>Funcionario</th>
                            <th>Destino</th>
                            <th>Periodo</th>
                            <th>Total solicitado</th>
                            <th>Status</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_trips)) { ?>
                            <?php foreach ($pending_trips as $trip) { ?>
                                <tr>
                                    <td><?php echo esc($trip->title ?? '-'); ?></td>
                                    <td><?php echo esc($trip->employee_name ?? '-'); ?></td>
                                    <td><?php echo esc($trip->destination ?? '-'); ?></td>
                                    <td><?php echo esc((($trip->start_date ?? '-') ?: '-') . ' a ' . (($trip->end_date ?? '-') ?: '-')); ?></td>
                                    <td><?php echo travelrefunds_currency($trip->approval_summary['total_amount'] ?? ($trip->total_amount ?? 0)); ?></td>
                                    <td><?php echo esc(travelrefunds_status_label($trip->status ?? '')); ?></td>
                                    <td class="text-end">
                                        <?php echo anchor(get_uri('travelrefunds/approvals/view/' . $trip->id), '<i data-feather="eye" class="icon-16"></i>', array('class' => 'action-icon', 'title' => 'Abrir')); ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhuma viagem aguardando aprovacao.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="page-title clearfix">
            <h1>Historico de aprovacoes</h1>
        </div>
        <div class="card-body border-bottom">
            <p class="text-muted mb-0">Ultimas decisoes registradas no fluxo de aprovacao.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Item</th>
                            <th>Acao</th>
                            <th>Observacao</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)) { ?>
                            <?php foreach ($logs as $log) { ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($log->trip_id)) { ?>
                                            Viagem
                                        <?php } elseif (!empty($log->expense_id)) { ?>
                                            Despesa
                                        <?php } else { ?>
                                            Geral
                                        <?php } ?>
                                    </td>
                                    <td><?php echo !empty($log->trip_id) ? ('#' . (int) $log->trip_id) : (!empty($log->expense_id) ? ('#' . (int) $log->expense_id) : '-'); ?></td>
                                    <td><?php echo esc($log->action ?? '-'); ?></td>
                                    <td><?php echo esc($log->notes ?? '-'); ?></td>
                                    <td><?php echo esc($log->created_at ?? '-'); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhum historico encontrado.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
