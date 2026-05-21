<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Viagens e Reembolsos</h1>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-off">Viagens</div>
                        <div class="strong"><?php echo (int) $summary['trips_total']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-off">Reembolsos</div>
                        <div class="strong"><?php echo (int) $summary['reimbursements_total']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-off">Pendentes</div>
                        <div class="text-warning"><?php echo (int) $summary['pending_total']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-off">Total reembolsado</div>
                        <div class="text-success"><?php echo travelrefunds_currency($summary['spent_total']); ?></div>
                    </div>
                </div>
            </div>
            <div class="mt15">
                <a href="<?php echo get_uri('travelrefunds/trips'); ?>" class="btn btn-primary">Minhas Viagens</a>
                <a href="<?php echo get_uri('travelrefunds/reimbursements'); ?>" class="btn btn-default">Solicitacoes de Reembolso</a>
                <a href="<?php echo get_uri('travelrefunds/approvals'); ?>" class="btn btn-default">Aprovacoes</a>
                <a href="<?php echo get_uri('travelrefunds/categories'); ?>" class="btn btn-default">Categorias de Despesas</a>
                <a href="<?php echo get_uri('travelrefunds/settings'); ?>" class="btn btn-default">Configuracoes</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb15">
                <div class="card-header"><h4>Ultimas viagens</h4></div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Titulo</th>
                                <th>Destino</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_trips as $trip) { ?>
                                <tr>
                                    <td><?php echo esc($trip->title); ?></td>
                                    <td><?php echo esc($trip->destination); ?></td>
                                    <td><?php echo esc(travelrefunds_status_label($trip->status)); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb15">
                <div class="card-header"><h4>Ultimos reembolsos</h4></div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Descricao</th>
                                <th>Categoria</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reimbursements as $item) { ?>
                                <tr>
                                    <td><?php echo esc($item->description); ?></td>
                                    <td><?php echo esc($item->category_title); ?></td>
                                    <td><?php echo travelrefunds_currency($item->amount); ?></td>
                                    <td><?php echo esc(travelrefunds_status_label($item->status)); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
