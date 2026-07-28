<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <!-- Stats Cards -->
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3><?php echo $stats->total_laudos ?? 0; ?></h3>
                    <p>Total de Laudos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3><?php echo $stats->completed ?? 0; ?></h3>
                    <p>Concluídos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h3><?php echo $stats->open_nc ?? 0; ?></h3>
                    <p>NCs Abertas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h3><?php echo $stats->today_inspections ?? 0; ?></h3>
                    <p>Inspeções Hoje</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <!-- Laudos por Status -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Laudos por Status</h5></div>
                <div class="card-body">
                    <?php if (empty($status_data)): ?>
                    <p class="text-muted">Nenhum laudo</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <?php foreach ($status_data as $s): ?>
                        <tr>
                            <td><?php echo $s->status; ?></td>
                            <td><span class="badge bg-<?php echo $s->status === 'completed' ? 'success' : 'secondary'; ?>"><?php echo $s->total; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Laudos por Tipo -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Laudos por Tipo</h5></div>
                <div class="card-body">
                    <?php if (empty($type_data)): ?>
                    <p class="text-muted">Nenhum dado</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <?php foreach ($type_data as $t): ?>
                        <tr>
                            <td><?php echo $t->name ?? 'Sem tipo'; ?></td>
                            <td><span class="badge bg-info"><?php echo $t->total; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <!-- Vencimentos -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning"><h5 class="mb-0">Próximos Vencimentos</h5></div>
                <div class="card-body">
                    <?php if (empty($expiring)): ?>
                    <p class="text-muted">Nenhum</p>
                    <?php else: ?>
                    <ul class="list-group">
                        <?php foreach (array_slice($expiring, 0, 5) as $e): ?>
                        <li class="list-group-item"><?php echo $e->laudo_number; ?><br><small><?php echo $e->validity_end; ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- NCs Críticas -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-danger text-white"><h5 class="mb-0">NCs Críticas</h5></div>
                <div class="card-body">
                    <?php if (empty($nc_critical)): ?>
                    <p class="text-muted">Nenhuma</p>
                    <?php else: ?>
                    <ul class="list-group">
                        <?php foreach (array_slice($nc_critical, 0, 5) as $nc): ?>
                        <li class="list-group-item"><?php echo $nc->code; ?>: <?php echo substr($nc->title, 0, 30); ?>...</li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Inspeções Hoje -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white"><h5 class="mb-0">Inspeções Hoje</h5></div>
                <div class="card-body">
                    <?php if (empty($today)): ?>
                    <p class="text-muted">Nenhuma</p>
                    <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($today as $t): ?>
                        <li class="list-group-item">
                            <strong><?php echo $t->laudo_number; ?></strong><br>
                            <small><?php echo $t->company_name; ?> - <?php echo $t->scheduled_time ?? ''; ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <!-- Atrasadas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white"><h5 class="mb-0">Inspeções Atrasadas</h5></div>
                <div class="card-body">
                    <?php if (empty($overdue)): ?>
                    <p class="text-muted">Nenhuma</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <tr><th>Laudo</th><th>Cliente</th><th>Data</th></tr>
                        <?php foreach (array_slice($overdue, 0, 5) as $o): ?>
                        <tr>
                            <td><?php echo $o->laudo_number; ?></td>
                            <td><?php echo $o->company_name; ?></td>
                            <td><?php echo $o->scheduled_date; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Planos Vencidos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning"><h5 class="mb-0">Planos de Ação Vencidos</h5></div>
                <div class="card-body">
                    <?php if (empty($plans_overdue)): ?>
                    <p class="text-muted">Nenhum</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <tr><th>Descrição</th><th>Prazo</th></tr>
                        <?php foreach (array_slice($plans_overdue, 0, 5) as $p): ?>
                        <tr>
                            <td><?php echo substr($p->action, 0, 40); ?>...</td>
                            <td><?php echo $p->deadline; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>