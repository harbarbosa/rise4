<div class="page-title clearfix">
    <h1>Abastecimentos</h1>
    <div class="title-button-group"><button class="btn btn-default" data-bs-toggle="modal" data-bs-target="#fuelingModal"><i data-feather="plus-circle" class="icon-16"></i> Novo abastecimento</button></div>
</div>
<div class="card"><div class="card-body"><form method="get" action="<?= get_uri('frota/abastecimentos') ?>" class="row g-2 align-items-end">
<div class="col-md-4"><label>Veículo</label><?= form_dropdown('vehicle_id',$vehicleOptions,$vehicleId,'class="form-control"') ?></div>
<div class="col-md-3"><label>De</label><input type="date" name="date_from" value="<?= esc($dateFrom) ?>" class="form-control"></div>
<div class="col-md-3"><label>Até</label><input type="date" name="date_to" value="<?= esc($dateTo) ?>" class="form-control"></div>
<div class="col-md-2"><button class="btn btn-primary"><i data-feather="filter" class="icon-16"></i> Filtrar</button></div>
</form></div></div>
<div class="card mt-3"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Data</th><th>Veículo</th><th>KM</th><th>Litros</th><th>Preço/L</th><th>Total</th><th>Posto</th></tr></thead><tbody>
<?php foreach($fuelings as $r): ?><tr><td><?= esc($r['fueling_at']) ?></td><td><?= esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']) ?></td><td><?= number_format((float)$r['odometer'],0,',','.') ?> km</td><td><?= number_format((float)$r['liters'],2,',','.') ?> L</td><td><?= frota_money($r['unit_price']) ?></td><td><?= frota_money($r['total_amount']) ?></td><td><?= esc($r['station'] ?: '-') ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="modal fade" id="fuelingModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post" action="<?= get_uri('frota/abastecimentos/salvar') ?>">
<div class="modal-header"><h5 class="modal-title">Novo abastecimento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
<div class="mb-3"><label>Veículo *</label><?= form_dropdown('vehicle_id',$vehicleOptions,'','class="form-control" required') ?></div>
<div class="mb-3"><label>Data/hora</label><input type="datetime-local" name="fueling_at" value="<?= date('Y-m-d\TH:i') ?>" class="form-control"></div>
<div class="row"><div class="col-md-6 mb-3"><label>KM *</label><input type="number" step="0.1" name="odometer" class="form-control" required></div><div class="col-md-6 mb-3"><label>Litros *</label><input type="number" step="0.001" name="liters" class="form-control" required></div></div>
<div class="row"><div class="col-md-6 mb-3"><label>Preço/L</label><input type="number" step="0.001" name="unit_price" class="form-control"></div><div class="col-md-6 mb-3"><label>Total *</label><input type="number" step="0.01" name="total_amount" class="form-control" required></div></div>
<div class="mb-3"><label>Posto</label><input name="station" class="form-control"></div><div class="mb-3"><label>Observações</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar</button></div></form></div></div></div>
<script>if(window.feather) feather.replace();</script>
