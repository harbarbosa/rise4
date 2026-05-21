<?php
$trip_id = $trip->id ?? 0;
$trip_is_saved = !empty($trip_id);
$trip_status = $trip->status ?? 'draft';
$can_edit_trip = $can_edit_trip ?? true;
$can_edit_expenses = $can_edit_expenses ?? true;
$expense_edit_id = $expense_edit->id ?? '';
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix d-flex justify-content-between align-items-center">
            <div>
                <h1><?php echo $trip_is_saved ? esc($trip->title) : 'Nova Viagem'; ?></h1>
                <?php if ($trip_is_saved) { ?>
                    <div class="text-off"><?php echo esc(travelrefunds_status_label($trip_status)); ?></div>
                <?php } ?>
            </div>
            <div>
                <a href="<?php echo get_uri('travelrefunds/trips'); ?>" class="btn btn-default">Voltar</a>
            </div>
        </div>

        <div class="card-body">
            <?php echo form_open(get_uri('travelrefunds/trips/save'), array('class' => 'general-form')); ?>
                <input type="hidden" name="id" value="<?php echo $trip_id; ?>" />
                <div class="row">
                    <div class="col-md-6 mb10">
                        <label>Titulo da viagem</label>
                        <input type="text" name="title" class="form-control" value="<?php echo esc($trip->title ?? ''); ?>" <?php echo !$can_edit_trip ? 'readonly' : ''; ?> />
                    </div>
                    <div class="col-md-3 mb10">
                        <label>Projeto opcional</label>
                        <select name="project_id" class="form-control select2" <?php echo !$can_edit_trip ? 'disabled' : ''; ?>>
                            <option value="">-</option>
                            <?php foreach ($projects as $project) { ?>
                                <option value="<?php echo $project->id; ?>" <?php echo (($trip->project_id ?? '') == $project->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($project->title); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb10">
                        <label>Cliente opcional</label>
                        <select name="client_id" class="form-control select2" <?php echo !$can_edit_trip ? 'disabled' : ''; ?>>
                            <option value="">-</option>
                            <?php foreach ($clients as $client) { ?>
                                <option value="<?php echo $client->id; ?>" <?php echo (($trip->client_id ?? '') == $client->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($client->company_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb10">
                        <label>Destino</label>
                        <input type="text" name="destination" class="form-control" value="<?php echo esc($trip->destination ?? ''); ?>" <?php echo !$can_edit_trip ? 'readonly' : ''; ?> />
                    </div>
                    <div class="col-md-3 mb10">
                        <label>Data inicial</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo esc($trip->start_date ?? ($trip->departure_date ?? '')); ?>" <?php echo !$can_edit_trip ? 'readonly' : ''; ?> />
                    </div>
                    <div class="col-md-3 mb10">
                        <label>Data final</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo esc($trip->end_date ?? ($trip->return_date ?? '')); ?>" <?php echo !$can_edit_trip ? 'readonly' : ''; ?> />
                    </div>
                    <div class="col-md-6 mb10">
                        <label>Objetivo da viagem</label>
                        <textarea name="purpose" class="form-control" rows="3" <?php echo !$can_edit_trip ? 'readonly' : ''; ?>><?php echo esc($trip->purpose ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-6 mb10">
                        <label>Observacoes</label>
                        <textarea name="notes" class="form-control" rows="3" <?php echo !$can_edit_trip ? 'readonly' : ''; ?>><?php echo esc($trip->notes ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Total da viagem</label>
                        <input type="text" class="form-control" value="<?php echo travelrefunds_currency($trip_summary['total_amount'] ?? ($trip->total_amount ?? 0)); ?>" readonly />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Valor aprovado</label>
                        <input type="text" class="form-control" value="<?php echo travelrefunds_currency($trip_summary['approved_amount'] ?? ($trip->approved_amount ?? 0)); ?>" readonly />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Status</label>
                        <input type="text" class="form-control" value="<?php echo esc(travelrefunds_status_label($trip_status)); ?>" readonly />
                    </div>
                    <div class="col-md-12 mt10">
                        <?php if ($can_edit_trip) { ?>
                            <button type="submit" name="save_action" value="draft" class="btn btn-default">Salvar rascunho</button>
                            <button type="submit" name="save_action" value="submit" class="btn btn-primary">Enviar para aprovacao</button>
                        <?php } ?>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>

    <?php if ($trip_is_saved) { ?>
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-details" role="tab">Detalhes</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-expenses" role="tab">Despesas</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-summary" role="tab">Resumo por categoria</a></li>
                </ul>
            </div>
            <div class="card-body tab-content">
                <div id="tab-details" class="tab-pane fade show active">
                    <div class="row">
                        <div class="col-md-4 mb15">
                            <div class="card card-body">
                                <div class="text-off">Total de despesas</div>
                                <div class="strong"><?php echo travelrefunds_currency($trip_summary['total_amount'] ?? 0); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb15">
                            <div class="card card-body">
                                <div class="text-off">Despesas aprovadas</div>
                                <div class="strong"><?php echo travelrefunds_currency($trip_summary['approved_amount'] ?? 0); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb15">
                            <div class="card card-body">
                                <div class="text-off">Quantidade de despesas</div>
                                <div class="strong"><?php echo (int) ($trip_summary['expense_count'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-expenses" class="tab-pane fade">
                    <?php if ($can_edit_expenses) { ?>
                        <?php echo form_open(get_uri('travelrefunds/trips/save-expense/' . $trip_id), array('class' => 'general-form')); ?>
                            <input type="hidden" name="id" value="<?php echo $expense_edit_id; ?>" />
                            <input type="hidden" name="attachment_id" value="<?php echo esc($expense_edit->attachment_id ?? ''); ?>" />
                            <div class="row">
                                <div class="col-md-3 mb10">
                                    <label>Categoria</label>
                                    <select name="category_id" class="form-control select2" required>
                                        <option value="">-</option>
                                        <?php foreach ($categories as $category) { ?>
                                            <option value="<?php echo $category->id; ?>" <?php echo (($expense_edit->category_id ?? '') == $category->id) ? 'selected' : ''; ?>>
                                                <?php echo esc($category->name ?: $category->title); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb10">
                                    <label>Data</label>
                                    <input type="date" name="expense_date" class="form-control" value="<?php echo esc($expense_edit->expense_date ?? get_my_local_time('Y-m-d')); ?>" required />
                                </div>
                                <div class="col-md-3 mb10">
                                    <label>Valor</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo esc($expense_edit->amount ?? '0'); ?>" required />
                                </div>
                                <div class="col-md-3 mb10">
                                    <label>Forma de pagamento</label>
                                    <select name="payment_method" class="form-control select2">
                                        <option value="">-</option>
                                        <?php foreach ($payment_methods as $payment_method) { ?>
                                            <option value="<?php echo esc($payment_method); ?>" <?php echo (($expense_edit->payment_method ?? '') === $payment_method) ? 'selected' : ''; ?>>
                                                <?php echo esc($payment_method); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb10">
                                    <label>Descricao</label>
                                    <textarea name="description" class="form-control" rows="3" required><?php echo esc($expense_edit->description ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb10">
                                    <label>Fornecedor</label>
                                    <input type="text" name="supplier_name" class="form-control" value="<?php echo esc($expense_edit->supplier_name ?? ($expense_edit->vendor ?? '')); ?>" />
                                </div>
                                <div class="col-md-2 mb10">
                                    <label>Possui NF?</label><br />
                                    <label class="form-check">
                                        <input type="checkbox" name="has_invoice" value="1" class="form-check-input" <?php echo (($expense_edit->has_invoice ?? 0) ? 'checked' : ''); ?> />
                                        <span class="form-check-label">Sim</span>
                                    </label>
                                </div>
                                <div class="col-md-2 mb10">
                                    <label>Numero da NF</label>
                                    <input type="text" name="invoice_number" class="form-control" value="<?php echo esc($expense_edit->invoice_number ?? ($expense_edit->receipt_number ?? '')); ?>" />
                                </div>
                                <div class="col-md-12 mb10">
                                    <label>Upload de comprovante/NF</label>
                                    <div id="travelrefunds-expense-dropzone" class="post-dropzone">
                                        <?php echo view("includes/dropzone_preview"); ?>
                                        <?php echo view("includes/upload_button", array("single_file" => true, "upload_button_text" => "Anexar comprovante")); ?>
                                    </div>
                                    <?php if (!empty($expense_edit->attachment_id)) { ?>
                                        <div class="text-off mt10">Anexo existente preservado ate novo upload.</div>
                                    <?php } ?>
                                </div>
                                <div class="col-md-12 mb10">
                                    <label>Observacoes</label>
                                    <textarea name="notes" class="form-control" rows="3"><?php echo esc($expense_edit->notes ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-12 mb10">
                                    <button type="submit" class="btn btn-primary">Salvar despesa</button>
                                </div>
                            </div>
                        <?php echo form_close(); ?>
                    <?php } else { ?>
                        <div class="alert alert-info">As despesas nao podem ser alteradas porque a viagem foi enviada para aprovacao ou ja foi aprovada. Se a viagem for rejeitada, a edicao sera liberada novamente.</div>
                    <?php } ?>

                    <div class="table-responsive mt15">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th>Data</th>
                                    <th>Descricao</th>
                                    <th>Forma de pagamento</th>
                                    <th>NF</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th class="text-end">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense) { ?>
                                    <tr>
                                        <td><?php echo esc($expense->category_name ?: $expense->category_title); ?></td>
                                        <td><?php echo esc($expense->expense_date); ?></td>
                                        <td><?php echo esc($expense->description); ?></td>
                                        <td><?php echo esc($expense->payment_method); ?></td>
                                        <td><?php echo esc($expense->invoice_number ?: $expense->receipt_number); ?></td>
                                        <td><?php echo travelrefunds_currency($expense->amount); ?></td>
                                        <td><?php echo esc(travelrefunds_status_label($expense->status)); ?></td>
                                        <td class="text-end">
                                            <?php if ($can_edit_expenses) { ?>
                                                <a href="<?php echo get_uri('travelrefunds/trips/view/' . $trip_id . '?expense_edit_id=' . $expense->id); ?>" class="btn btn-default btn-sm">Editar</a>
                                                <?php echo form_open(get_uri('travelrefunds/trips/delete-expense/' . $trip_id . '/' . $expense->id), array('class' => 'd-inline')); ?>
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Excluir esta despesa?');">Excluir</button>
                                                <?php echo form_close(); ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="tab-summary" class="tab-pane fade">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expense_summary as $category_name => $category_total) { ?>
                                    <tr>
                                        <td><?php echo esc($category_name); ?></td>
                                        <td class="text-end"><?php echo travelrefunds_currency($category_total); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
