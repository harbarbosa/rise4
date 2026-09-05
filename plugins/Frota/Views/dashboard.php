<div class="page-title clearfix">
    <h1>Gestão de Frota</h1>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted">Veículos ativos</div><div class="h3 mb-0"><?= count(array_filter($vehicles, fn($v)=>($v['status'] ?? '') === 'active')) ?></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted">Ocorrências abertas</div><div class="h3 mb-0"><?= (int)$openIssues ?></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted">Manutenções próximas</div><div class="h3 mb-0"><?= (int)$maintenanceDue ?></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body"><div class="text-muted">Abastecimentos no mês</div><div class="h3 mb-0"><?= frota_money($fuelMonth) ?></div></div></div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h4>Ocorrências recentes</h4></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Veículo</th><th>Problema</th><th>Gravidade</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach(array_slice($issues, 0, 8) as $r): ?>
                        <tr><td><?= esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']) ?></td><td><?= esc($r['title']) ?></td><td><?= esc(ucfirst($r['severity'])) ?></td><td><?= frota_status_badge($r['status']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h4>Próximas manutenções</h4></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Veículo</th><th>Data</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach(array_slice($maintenances, 0, 8) as $r): ?>
                        <tr><td><?= esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']) ?></td><td><?= esc($r['service_date']) ?></td><td><?= frota_status_badge($r['status']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
