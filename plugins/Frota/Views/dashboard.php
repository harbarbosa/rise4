<div id="page-content" class="page-wrapper clearfix">
    <div class="card grid-button">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('frota'); ?></h1>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-primary"><i data-feather="truck" class="icon-24"></i></div>
                            <div class="widget-details"><h1><?php echo count(array_filter($vehicles, fn($v) => ($v['status'] ?? '') === 'active')); ?></h1><span>Veículos ativos</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-danger"><i data-feather="alert-triangle" class="icon-24"></i></div>
                            <div class="widget-details"><h1><?php echo (int)$openIssues; ?></h1><span>Ocorrências abertas</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-warning"><i data-feather="tool" class="icon-24"></i></div>
                            <div class="widget-details"><h1><?php echo (int)$maintenanceDue; ?></h1><span>Manutenções próximas</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card dashboard-icon-widget">
                        <div class="card-body">
                            <div class="widget-icon bg-success"><i data-feather="droplet" class="icon-24"></i></div>
                            <div class="widget-details"><h1><?php echo frota_money($fuelMonth); ?></h1><span>Abastecimentos no mês</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="page-title clearfix"><h4>Ocorrências recentes</h4></div>
                <div class="table-responsive">
                    <table class="table table-hover mb0">
                        <thead><tr><th>Veículo</th><th>Problema</th><th>Gravidade</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if ($issues) { foreach (array_slice($issues, 0, 8) as $r) { ?>
                            <tr>
                                <td><?php echo esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']); ?></td>
                                <td><?php echo esc($r['title']); ?></td>
                                <td><?php echo esc(ucfirst($r['severity'])); ?></td>
                                <td><?php echo frota_status_badge($r['status']); ?></td>
                            </tr>
                        <?php }} else { ?>
                            <tr><td colspan="4" class="text-center text-off p20"><?php echo app_lang('no_record_found'); ?></td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="page-title clearfix"><h4>Manutenções recentes</h4></div>
                <div class="table-responsive">
                    <table class="table table-hover mb0">
                        <thead><tr><th>Veículo</th><th>Data</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if ($maintenances) { foreach (array_slice($maintenances, 0, 8) as $r) { ?>
                            <tr>
                                <td><?php echo esc($vehicleMap[$r['vehicle_id']] ?? '#'.$r['vehicle_id']); ?></td>
                                <td><?php echo esc($r['service_date']); ?></td>
                                <td><?php echo frota_status_badge($r['status']); ?></td>
                            </tr>
                        <?php }} else { ?>
                            <tr><td colspan="3" class="text-center text-off p20"><?php echo app_lang('no_record_found'); ?></td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
