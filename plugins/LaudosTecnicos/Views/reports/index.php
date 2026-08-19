<?php
$summary = is_array($summary ?? null) ? $summary : array();
$nc_stats = is_array($nc_stats ?? null) ? $nc_stats : array();
$report_types = is_array($report_types ?? null) ? $report_types : array();
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h1>Relatorios</h1>
        <div class="title-button-group">
            <?php echo anchor(get_uri('laudostecnicos/relatorios/print/laudos-status'), 'Imprimir laudos', array('class' => 'btn btn-outline-secondary')); ?>
            <?php echo anchor(get_uri('laudostecnicos/relatorios/pdf/laudos-status'), 'PDF laudos', array('class' => 'btn btn-outline-primary')); ?>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="rounded p-3 bg-primary text-white"><div class="small opacity-75">API requests</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($summary, 'api_requests'); ?></div></div></div>
        <div class="col-md-3"><div class="rounded p-3 bg-info text-white"><div class="small opacity-75">AI requests</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($summary, 'ai_requests'); ?></div></div></div>
        <div class="col-md-3"><div class="rounded p-3 bg-success text-white"><div class="small opacity-75">Sync records</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($summary, 'synced_records'); ?></div></div></div>
        <div class="col-md-3"><div class="rounded p-3 bg-secondary text-white"><div class="small opacity-75">Devices</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($summary, 'devices'); ?></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr><th>Chave</th><th>Descricao</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($report_types as $key => $label) { ?>
                        <tr>
                            <td><?php echo esc($key); ?></td>
                            <td><?php echo esc($label); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Resumo de nao conformidades</h5>
            <div class="row g-3">
                <div class="col-md-3"><div class="rounded p-3 bg-danger text-white"><div class="small opacity-75">Abertas</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'open'); ?></div></div></div>
                <div class="col-md-3"><div class="rounded p-3 bg-dark text-white"><div class="small opacity-75">Criticas</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'critical'); ?></div></div></div>
                <div class="col-md-3"><div class="rounded p-3 bg-warning text-dark"><div class="small opacity-75">Vencidas</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'expired'); ?></div></div></div>
                <div class="col-md-3"><div class="rounded p-3 bg-success text-white"><div class="small opacity-75">Corrigidas</div><div class="fs-3 fw-bold"><?php echo (int) get_array_value($nc_stats, 'corrected'); ?></div></div></div>
            </div>
        </div>
    </div>
</div>
