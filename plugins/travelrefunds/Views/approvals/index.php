<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Aprovacoes</h1>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Descricao</th>
                            <th>Funcionario</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_items as $item) { ?>
                            <tr>
                                <td><?php echo esc($item->description); ?></td>
                                <td><?php echo esc($item->employee_name); ?></td>
                                <td><?php echo esc($item->category_title); ?></td>
                                <td><?php echo travelrefunds_currency($item->amount); ?></td>
                                <td><?php echo esc(travelrefunds_status_label($item->status)); ?></td>
                                <td class="text-end">
                                    <?php echo form_open(get_uri('travelrefunds/approvals/approve/' . $item->id), array('class' => 'd-inline')); ?>
                                        <button type="submit" class="btn btn-success btn-sm">Aprovar</button>
                                    <?php echo form_close(); ?>
                                    <?php echo form_open(get_uri('travelrefunds/approvals/reject/' . $item->id), array('class' => 'd-inline')); ?>
                                        <button type="submit" class="btn btn-warning btn-sm">Rejeitar</button>
                                    <?php echo form_close(); ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4>Historico de aprovacoes</h4></div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Reembolso</th>
                        <th>Acao</th>
                        <th>Observacao</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) { ?>
                        <tr>
                            <td><?php echo (int) $log->reimbursement_id; ?></td>
                            <td><?php echo esc(travelrefunds_status_label($log->action)); ?></td>
                            <td><?php echo esc($log->notes); ?></td>
                            <td><?php echo esc($log->created_at); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
