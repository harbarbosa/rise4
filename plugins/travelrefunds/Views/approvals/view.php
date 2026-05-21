<?php
$can_decide_trip = $can_decide_trip ?? false;
$approved_amount_value = isset($trip->approved_amount) ? $trip->approved_amount : ($trip_summary['approved_amount'] ?? 0);
$approved_amount_display = isset($trip->approved_amount) ? $trip->approved_amount : $approved_amount_value;
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Solicitacao de aprovacao</h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('travelrefunds/approvals'), '<i data-feather="arrow-left" class="icon-16"></i> Voltar', array('class' => 'btn btn-default')); ?>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small d-block">Titulo</label>
                                    <div class="fw-semibold"><?php echo esc($trip->title); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small d-block">Status</label>
                                    <div><?php echo esc(travelrefunds_status_label($trip->status)); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small d-block">Funcionario</label>
                                    <div><?php echo esc($trip->employee_name ?: '-'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small d-block">Projeto</label>
                                    <div><?php echo esc($trip->project_title ?: '-'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small d-block">Cliente</label>
                                    <div><?php echo esc($trip->client_name ?: '-'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small d-block">Destino</label>
                                    <div><?php echo esc($trip->destination ?: '-'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small d-block">Periodo</label>
                                    <div><?php echo esc(($trip->start_date ?: '-') . ' a ' . ($trip->end_date ?: '-')); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small d-block">Funcionario solicitante</label>
                                    <div><?php echo esc($trip->employee_name ?: '-'); ?></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="text-muted small d-block">Objetivo</label>
                                    <div><?php echo nl2br(esc($trip->purpose ?: '-')); ?></div>
                                </div>
                                <div class="col-12">
                                    <label class="text-muted small d-block">Observacoes</label>
                                    <div><?php echo nl2br(esc($trip->notes ?: '-')); ?></div>
                                </div>
                                <?php if (!empty($trip->approver_notes) || !empty($trip->rejection_reason)) { ?>
                                    <div class="col-12 mt-3">
                                        <div class="alert alert-light border mb-0">
                                            <?php if (!empty($trip->approver_notes)) { ?>
                                                <div><strong>Observacao do aprovador:</strong> <?php echo nl2br(esc($trip->approver_notes)); ?></div>
                                            <?php } ?>
                                            <?php if (!empty($trip->rejection_reason)) { ?>
                                                <div class="<?php echo !empty($trip->approver_notes) ? 'mt-2' : ''; ?>"><strong>Motivo da rejeicao:</strong> <?php echo nl2br(esc($trip->rejection_reason)); ?></div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Resumo financeiro</strong>
                                <span class="badge bg-info"><?php echo esc($trip_summary['expense_count']); ?> despesas</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Total solicitado</span>
                                <div class="fs-5 fw-semibold"><?php echo travelrefunds_currency($trip_summary['total_amount']); ?></div>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Total aprovado nas despesas</span>
                                <div class="fw-semibold"><?php echo travelrefunds_currency($trip_summary['approved_amount']); ?></div>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Total rejeitado nas despesas</span>
                                <div class="fw-semibold"><?php echo travelrefunds_currency($trip_summary['rejected_amount']); ?></div>
                            </div>
                            <div>
                                <span class="text-muted">Valor aprovado da viagem</span>
                                <div class="fw-semibold"><?php echo travelrefunds_currency($approved_amount_display); ?></div>
                            </div>
                            <?php if (!empty($special_approval_limit) && (float) $trip_summary['total_amount'] > (float) $special_approval_limit) { ?>
                                <div class="alert alert-warning mt-3 mb-0">
                                    Esta viagem excede o limite de aprovacao simples.
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <?php if ($can_decide_trip) { ?>
                        <?php echo form_open(get_uri('travelrefunds/approvals/trip/approve/' . $trip->id), array('id' => 'travelrefunds-trip-approve-form', 'class' => 'card border-0 shadow-sm mb-3')); ?>
                            <div class="card-body">
                                <strong class="d-block mb-3">Aprovar viagem</strong>
                                <div class="mb-3">
                                    <label class="form-label">Valor aprovado</label>
                                    <input type="text" name="approved_amount" class="form-control money" value="<?php echo esc($approved_amount_value); ?>" />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Observacao do aprovador</label>
                                    <textarea name="approver_notes" class="form-control" rows="3" placeholder="Observacao opcional"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100"><i data-feather="check-circle" class="icon-16"></i> Aprovar viagem</button>
                            </div>
                        <?php echo form_close(); ?>

                        <?php echo form_open(get_uri('travelrefunds/approvals/trip/reject/' . $trip->id), array('class' => 'card border-0 shadow-sm')); ?>
                            <div class="card-body">
                                <strong class="d-block mb-3">Rejeitar viagem</strong>
                                <div class="mb-3">
                                    <label class="form-label">Motivo da rejeicao</label>
                                    <textarea name="rejection_reason" class="form-control" rows="3" data-rule-required="true" data-msg-required="Campo obrigatorio"></textarea>
                                </div>
                                <button type="submit" class="btn btn-warning w-100"><i data-feather="x-circle" class="icon-16"></i> Rejeitar viagem</button>
                            </div>
                        <?php echo form_close(); ?>
                    <?php } else { ?>
                        <div class="alert alert-info mb-0">A viagem nao esta mais pendente de aprovacao.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb15">
        <div class="card-header">
            <h4 class="card-title mb-0">Despesas da viagem</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Data</th>
                            <th>Descricao</th>
                            <th>Valor</th>
                            <th>Pagamento</th>
                            <th>NF</th>
                            <th>Anexo</th>
                            <th>Status</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expenses)) { ?>
                            <?php foreach ($expenses as $expense) { ?>
                                <tr>
                                    <td><?php echo esc($expense->category_label); ?></td>
                                    <td><?php echo esc($expense->expense_date ?: '-'); ?></td>
                                    <td><?php echo esc($expense->description ?: '-'); ?></td>
                                    <td><?php echo travelrefunds_currency($expense->amount); ?></td>
                                    <td><?php echo esc($expense->payment_method ?: '-'); ?></td>
                                    <td>
                                        <?php echo $expense->has_invoice ? esc($expense->invoice_number ?: 'Sim') : '-'; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($expense->attachment_url)) { ?>
                                            <?php echo anchor($expense->attachment_url, '<i data-feather="paperclip" class="icon-16"></i> Ver anexo', array('target' => '_blank')); ?>
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>
                                    <td><?php echo esc(travelrefunds_status_label($expense->status)); ?></td>
                                    <td class="text-end">
                                        <?php if ($can_decide_trip) { ?>
                                            <?php echo form_open(get_uri('travelrefunds/approvals/expense/approve/' . $trip->id . '/' . $expense->id), array('class' => 'd-inline')); ?>
                                                <button type="submit" class="btn btn-success btn-sm">Aprovar</button>
                                            <?php echo form_close(); ?>
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#travelrefunds-expense-reject-modal" data-expense-id="<?php echo (int) $expense->id; ?>" data-expense-title="<?php echo esc($expense->description ?: ('#' . $expense->id)); ?>">
                                                Rejeitar
                                            </button>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Nenhuma despesa encontrada.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Resumo por categoria</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expense_summary)) { ?>
                            <?php foreach ($expense_summary as $category => $amount) { ?>
                                <tr>
                                    <td><?php echo esc($category); ?></td>
                                    <td class="text-end"><?php echo travelrefunds_currency($amount); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">Sem dados para resumir.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="travelrefunds-expense-reject-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php echo form_open('', array('id' => 'travelrefunds-expense-reject-form')); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Rejeitar despesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="travelrefunds-expense-reject-title"></p>
                    <div class="mb-3">
                        <label class="form-label">Motivo da rejeicao</label>
                        <textarea name="rejection_reason" class="form-control" rows="4" data-rule-required="true" data-msg-required="Campo obrigatorio"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Rejeitar</button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('travelrefunds-expense-reject-modal');
        if (!modal) {
            return;
        }

        modal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) {
                return;
            }

            var expenseId = button.getAttribute('data-expense-id');
            var expenseTitle = button.getAttribute('data-expense-title') || '';
            var form = document.getElementById('travelrefunds-expense-reject-form');
            var title = document.getElementById('travelrefunds-expense-reject-title');

            if (form) {
                form.action = '<?php echo get_uri('travelrefunds/approvals/expense/reject/' . $trip->id); ?>/' + expenseId;
            }

            if (title) {
                title.textContent = expenseTitle ? ('Despesa: ' + expenseTitle) : 'Informe o motivo da rejeicao.';
            }
        });
    })();
</script>
