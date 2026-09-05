<div class="page-title clearfix">
    <h1>Veículos</h1>
    <div class="title-button-group">
        <button class="btn btn-default" data-bs-toggle="modal" data-bs-target="#vehicleModal"><i data-feather="plus-circle" class="icon-16"></i> Novo veículo</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" action="<?= get_uri('frota/veiculos') ?>" class="row g-2 align-items-end">
            <div class="col-md-5"><label>Buscar</label><input type="text" name="search" value="<?= esc($search) ?>" class="form-control" placeholder="Placa, prefixo, marca ou modelo"></div>
            <div class="col-md-3"><label>Status</label><select name="status" class="form-control"><option value="">Todos</option><option value="active" <?= $status==='active'?'selected':'' ?>>Ativo</option><option value="maintenance" <?= $status==='maintenance'?'selected':'' ?>>Em manutenção</option><option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inativo</option></select></div>
            <div class="col-md-4"><button class="btn btn-primary"><i data-feather="filter" class="icon-16"></i> Filtrar</button> <a href="<?= get_uri('frota/veiculos') ?>" class="btn btn-default">Limpar</a></div>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="table-responsive">
        <table class="table table-hover dataTable mb-0">
            <thead><tr><th>Placa</th><th>Prefixo</th><th>Veículo</th><th>Ano</th><th>KM atual</th><th>Próxima revisão</th><th>Status</th><th class="text-center">Ações</th></tr></thead>
            <tbody>
            <?php foreach($vehicles as $v): ?>
                <tr>
                    <td><strong><?= esc($v['plate']) ?></strong></td><td><?= esc($v['prefix'] ?: '-') ?></td><td><?= esc(trim(($v['make']??'').' '.$v['model'])) ?></td><td><?= esc($v['year'] ?: '-') ?></td><td><?= number_format((float)$v['current_odometer'],0,',','.') ?> km</td><td><?= esc($v['next_service_date'] ?: ($v['next_service_odometer'] ? number_format((float)$v['next_service_odometer'],0,',','.').' km' : '-')) ?></td><td><?= frota_status_badge($v['status']) ?></td>
                    <td class="text-center"><button class="btn btn-sm btn-default edit-vehicle" data-bs-toggle="modal" data-bs-target="#vehicleModal" data-vehicle='<?= esc(json_encode($v), 'attr') ?>'><i data-feather="edit-2" class="icon-14"></i></button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="vehicleModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="post" action="<?= get_uri('frota/veiculos/salvar') ?>"><div class="modal-header"><h5 class="modal-title">Veículo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
<input type="hidden" name="id" id="vehicle_id">
<div class="row"><div class="col-md-4 mb-3"><label>Placa *</label><input name="plate" id="vehicle_plate" class="form-control" required></div><div class="col-md-4 mb-3"><label>Prefixo</label><input name="prefix" id="vehicle_prefix" class="form-control"></div><div class="col-md-4 mb-3"><label>Ano</label><input name="year" id="vehicle_year" class="form-control"></div></div>
<div class="row"><div class="col-md-6 mb-3"><label>Marca</label><input name="make" id="vehicle_make" class="form-control"></div><div class="col-md-6 mb-3"><label>Modelo *</label><input name="model" id="vehicle_model" class="form-control" required></div></div>
<div class="row"><div class="col-md-4 mb-3"><label>Combustível</label><select name="fuel_type" id="vehicle_fuel_type" class="form-control"><option>Flex</option><option>Gasolina</option><option>Etanol</option><option>Diesel</option><option>Elétrico</option></select></div><div class="col-md-4 mb-3"><label>KM atual</label><input type="number" step="0.1" name="current_odometer" id="vehicle_current_odometer" class="form-control"></div><div class="col-md-4 mb-3"><label>Status</label><select name="status" id="vehicle_status" class="form-control"><option value="active">Ativo</option><option value="maintenance">Em manutenção</option><option value="inactive">Inativo</option></select></div></div>
<div class="row"><div class="col-md-6 mb-3"><label>Próxima revisão (km)</label><input type="number" step="0.1" name="next_service_odometer" id="vehicle_next_service_odometer" class="form-control"></div><div class="col-md-6 mb-3"><label>Próxima revisão</label><input type="date" name="next_service_date" id="vehicle_next_service_date" class="form-control"></div></div>
<div class="mb-3"><label>Observações</label><textarea name="notes" id="vehicle_notes" class="form-control" rows="3"></textarea></div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar</button></div></form>
</div></div></div>
<script>
$(document).on('click','.edit-vehicle',function(){var v=$(this).data('vehicle'); Object.keys(v).forEach(function(k){var el=$('#vehicle_'+k); if(el.length) el.val(v[k]);});});
$('#vehicleModal').on('hidden.bs.modal', function(){ $(this).find('form')[0].reset(); $('#vehicle_id').val(''); });
if(window.feather) feather.replace();
</script>
