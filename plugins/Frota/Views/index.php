<?php
$tab = $section ?? 'dashboard';
$vehicleOptions = ['' => '- Selecione -'];
foreach ($vehicles as $v) $vehicleOptions[$v['id']] = $v['plate'] . ' - ' . $v['model'];
?>
<div class="page-title clearfix">
    <h1>Gestão de Frota</h1>
</div>
<div class="card">
    <div class="card-body p-0">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><a class="nav-link <?= $tab==='dashboard'?'active':'' ?>" href="<?= get_uri('frota') ?>">Visão geral</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab==='vehicles'?'active':'' ?>" href="<?= get_uri('frota/veiculos') ?>">Veículos</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab==='fuelings'?'active':'' ?>" href="<?= get_uri('frota/abastecimentos') ?>">Abastecimentos</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab==='maintenances'?'active':'' ?>" href="<?= get_uri('frota/manutencoes') ?>">Manutenções</a></li>
            <li class="nav-item"><a class="nav-link <?= $tab==='issues'?'active':'' ?>" href="<?= get_uri('frota/ocorrencias') ?>">Ocorrências</a></li>
        </ul>
    </div>
</div>

<?php if ($tab === 'dashboard'): ?>
<div class="row mt-3">
    <?php foreach ([['Veículos ativos',count(array_filter($vehicles,fn($r)=>$r['status']==='active')),'truck'],['Ocorrências abertas',$openIssues,'alert-triangle'],['Manutenções próximas',$maintenanceDue,'tool'],['Abastecimentos no mês',frota_money($fuelMonth),'droplet'],['Manutenção no mês',frota_money($maintenanceMonth),'dollar-sign']] as $card): ?>
    <div class="col-md mb-3"><div class="card h-100"><div class="card-body"><div class="d-flex align-items-center"><i data-feather="<?= $card[2] ?>" class="icon-24 me-3"></i><div><div class="text-muted small"><?= esc($card[0]) ?></div><div class="h4 mb-0"><?= esc((string)$card[1]) ?></div></div></div></div></div></div>
    <?php endforeach; ?>
</div>
<div class="row">
    <div class="col-md-7"><div class="card"><div class="card-header"><h4>Ocorrências recentes</h4></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Veículo</th><th>Problema</th><th>Gravidade</th><th>Status</th></tr></thead><tbody><?php foreach(array_slice($issues,0,8) as $r): ?><tr><td><?= esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']) ?></td><td><?= esc($r['title']) ?></td><td><?= esc(ucfirst($r['severity'])) ?></td><td><?= frota_status_badge($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <div class="col-md-5"><div class="card"><div class="card-header"><h4>Próximas manutenções</h4></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Veículo</th><th>Data</th><th>Status</th></tr></thead><tbody><?php foreach(array_slice($maintenances,0,8) as $r): ?><tr><td><?= esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']) ?></td><td><?= esc($r['service_date']) ?></td><td><?= frota_status_badge($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
<?php endif; ?>

<?php if ($tab === 'vehicles'): ?>
<div class="row mt-3"><div class="col-lg-4"><div class="card"><div class="card-header"><h4>Novo veículo</h4></div><div class="card-body">
<form method="post" action="<?= get_uri('frota/veiculos/salvar') ?>">
<div class="mb-3"><label>Placa *</label><input name="plate" class="form-control" required></div>
<div class="row"><div class="col"><div class="mb-3"><label>Prefixo</label><input name="prefix" class="form-control"></div></div><div class="col"><div class="mb-3"><label>Ano</label><input name="year" class="form-control"></div></div></div>
<div class="mb-3"><label>Marca</label><input name="make" class="form-control"></div><div class="mb-3"><label>Modelo *</label><input name="model" class="form-control" required></div>
<div class="row"><div class="col"><div class="mb-3"><label>Combustível</label><select name="fuel_type" class="form-control"><option>Flex</option><option>Gasolina</option><option>Etanol</option><option>Diesel</option><option>Elétrico</option></select></div></div><div class="col"><div class="mb-3"><label>KM atual</label><input type="number" step="0.1" name="current_odometer" class="form-control"></div></div></div>
<div class="mb-3"><label>Status</label><select name="status" class="form-control"><option value="active">Ativo</option><option value="maintenance">Em manutenção</option><option value="inactive">Inativo</option></select></div>
<div class="row"><div class="col"><div class="mb-3"><label>Próxima revisão (km)</label><input type="number" step="0.1" name="next_service_odometer" class="form-control"></div></div><div class="col"><div class="mb-3"><label>Próxima revisão</label><input type="date" name="next_service_date" class="form-control"></div></div></div>
<button class="btn btn-primary w-100">Salvar veículo</button></form></div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-header"><h4>Veículos cadastrados</h4></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Placa</th><th>Veículo</th><th>KM</th><th>Próxima revisão</th><th>Status</th></tr></thead><tbody><?php foreach($vehicles as $v): ?><tr><td><strong><?= esc($v['plate']) ?></strong></td><td><?= esc(trim(($v['make']??'').' '.$v['model'])) ?></td><td><?= number_format((float)$v['current_odometer'],0,',','.') ?></td><td><?= esc($v['next_service_date'] ?: ($v['next_service_odometer'] ? number_format((float)$v['next_service_odometer'],0,',','.').' km' : '-')) ?></td><td><?= frota_status_badge($v['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
<?php endif; ?>

<?php if ($tab === 'fuelings'): ?>
<div class="row mt-3"><div class="col-lg-4"><div class="card"><div class="card-header"><h4>Novo abastecimento</h4></div><div class="card-body"><form method="post" action="<?= get_uri('frota/abastecimentos/salvar') ?>">
<div class="mb-3"><label>Veículo *</label><?= form_dropdown('vehicle_id',$vehicleOptions,'','class="form-control select2" required') ?></div><div class="mb-3"><label>Data/hora</label><input type="datetime-local" name="fueling_at" value="<?= date('Y-m-d\TH:i') ?>" class="form-control"></div>
<div class="row"><div class="col"><div class="mb-3"><label>KM *</label><input type="number" step="0.1" name="odometer" class="form-control" required></div></div><div class="col"><div class="mb-3"><label>Litros *</label><input type="number" step="0.001" name="liters" class="form-control" required></div></div></div>
<div class="row"><div class="col"><div class="mb-3"><label>Preço/L</label><input type="number" step="0.001" name="unit_price" class="form-control"></div></div><div class="col"><div class="mb-3"><label>Total *</label><input type="number" step="0.01" name="total_amount" class="form-control" required></div></div></div><div class="mb-3"><label>Posto</label><input name="station" class="form-control"></div><button class="btn btn-primary w-100">Registrar abastecimento</button></form></div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-header"><h4>Últimos abastecimentos</h4></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Data</th><th>Veículo</th><th>KM</th><th>Litros</th><th>Total</th></tr></thead><tbody><?php foreach($fuelings as $r): ?><tr><td><?= esc($r['fueling_at']) ?></td><td><?= esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']) ?></td><td><?= number_format((float)$r['odometer'],0,',','.') ?></td><td><?= number_format((float)$r['liters'],2,',','.') ?> L</td><td><?= frota_money($r['total_amount']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
<?php endif; ?>

<?php if ($tab === 'maintenances'): ?>
<div class="row mt-3"><div class="col-lg-4"><div class="card"><div class="card-header"><h4>Nova manutenção</h4></div><div class="card-body"><form method="post" action="<?= get_uri('frota/manutencoes/salvar') ?>">
<div class="mb-3"><label>Veículo *</label><?= form_dropdown('vehicle_id',$vehicleOptions,'','class="form-control select2" required') ?></div><div class="row"><div class="col"><div class="mb-3"><label>Tipo</label><select name="type" class="form-control"><option value="preventive">Preventiva</option><option value="corrective">Corretiva</option></select></div></div><div class="col"><div class="mb-3"><label>Data *</label><input type="date" name="service_date" value="<?= date('Y-m-d') ?>" class="form-control" required></div></div></div><div class="mb-3"><label>Descrição *</label><textarea name="description" class="form-control" rows="3" required></textarea></div><div class="mb-3"><label>Fornecedor</label><input name="supplier" class="form-control"></div><div class="row"><div class="col"><div class="mb-3"><label>Custo</label><input type="number" step="0.01" name="cost" class="form-control"></div></div><div class="col"><div class="mb-3"><label>Status</label><select name="status" class="form-control"><option value="scheduled">Agendada</option><option value="in_progress">Em andamento</option><option value="completed">Concluída</option></select></div></div></div><div class="row"><div class="col"><div class="mb-3"><label>Próxima km</label><input type="number" step="0.1" name="next_service_odometer" class="form-control"></div></div><div class="col"><div class="mb-3"><label>Próxima data</label><input type="date" name="next_service_date" class="form-control"></div></div></div><button class="btn btn-primary w-100">Salvar manutenção</button></form></div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-header"><h4>Histórico e agenda</h4></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Data</th><th>Veículo</th><th>Tipo</th><th>Descrição</th><th>Custo</th><th>Status</th></tr></thead><tbody><?php foreach($maintenances as $r): ?><tr><td><?= esc($r['service_date']) ?></td><td><?= esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']) ?></td><td><?= $r['type']==='preventive'?'Preventiva':'Corretiva' ?></td><td><?= esc($r['description']) ?></td><td><?= frota_money($r['cost']) ?></td><td><?= frota_status_badge($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
<?php endif; ?>

<?php if ($tab === 'issues'): ?>
<div class="row mt-3"><div class="col-lg-4"><div class="card"><div class="card-header"><h4>Registrar problema</h4></div><div class="card-body"><form method="post" action="<?= get_uri('frota/ocorrencias/salvar') ?>">
<div class="mb-3"><label>Veículo *</label><?= form_dropdown('vehicle_id',$vehicleOptions,'','class="form-control select2" required') ?></div><div class="mb-3"><label>Título *</label><input name="title" class="form-control" required></div><div class="mb-3"><label>Descrição *</label><textarea name="description" class="form-control" rows="4" required></textarea></div><div class="row"><div class="col"><div class="mb-3"><label>Gravidade</label><select name="severity" class="form-control"><option value="low">Baixa</option><option value="medium" selected>Média</option><option value="high">Alta</option><option value="critical">Crítica</option></select></div></div><div class="col"><div class="mb-3"><label>KM</label><input type="number" step="0.1" name="odometer" class="form-control"></div></div></div><button class="btn btn-danger w-100">Registrar ocorrência</button></form></div></div></div>
<div class="col-lg-8"><div class="card"><div class="card-header"><h4>Ocorrências</h4></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Veículo</th><th>Problema</th><th>Gravidade</th><th>Data</th><th>Status</th></tr></thead><tbody><?php foreach($issues as $r): ?><tr><td><?= esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']) ?></td><td><?= esc($r['title']) ?></td><td><?= esc(ucfirst($r['severity'])) ?></td><td><?= esc($r['reported_at']) ?></td><td><?= frota_status_badge($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
<?php endif; ?>
<script>$(document).ready(function(){ if ($.fn.select2) $('.select2').select2({width:'100%'}); if (window.feather) feather.replace(); });</script>
