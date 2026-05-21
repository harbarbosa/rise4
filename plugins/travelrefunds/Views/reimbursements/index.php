<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Solicitacoes de Reembolso</h1>
        </div>
        <div class="card-body">
            <?php echo form_open(get_uri('travelrefunds/reimbursements/save'), array('class' => 'general-form')); ?>
                <input type="hidden" name="id" value="<?php echo $reimbursement_edit->id ?? ''; ?>" />
                <div class="row">
                    <div class="col-md-4 mb10">
                        <label>Viagem</label>
                        <select name="trip_id" class="form-control select2">
                            <option value="">-</option>
                            <?php foreach ($trips as $trip) { ?>
                                <option value="<?php echo $trip->id; ?>" <?php echo (($reimbursement_edit->trip_id ?? '') == $trip->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($trip->title); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Funcionario</label>
                        <select name="employee_id" class="form-control select2">
                            <option value="">-</option>
                            <?php foreach ($users as $user) { ?>
                                <option value="<?php echo $user->id; ?>" <?php echo (($reimbursement_edit->employee_id ?? '') == $user->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($user->first_name . ' ' . $user->last_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Categoria</label>
                        <select name="category_id" class="form-control select2">
                            <option value="">-</option>
                            <?php foreach ($categories as $category) { ?>
                                <option value="<?php echo $category->id; ?>" <?php echo (($reimbursement_edit->category_id ?? '') == $category->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($category->title); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Data da despesa</label>
                        <input type="date" name="expense_date" class="form-control" value="<?php echo esc($reimbursement_edit->expense_date ?? ''); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Valor</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo esc($reimbursement_edit->amount ?? '0'); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Status</label>
                        <select name="status" class="form-control select2">
                            <?php foreach ($status_options as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo (($reimbursement_edit->status ?? 'pending') === $status) ? 'selected' : ''; ?>>
                                    <?php echo esc(travelrefunds_status_label($status)); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Fornecedor</label>
                        <input type="text" name="vendor" class="form-control" value="<?php echo esc($reimbursement_edit->vendor ?? ''); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Numero do comprovante</label>
                        <input type="text" name="receipt_number" class="form-control" value="<?php echo esc($reimbursement_edit->receipt_number ?? ''); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Arquivo do comprovante</label>
                        <input type="text" name="receipt_file" class="form-control" value="<?php echo esc($reimbursement_edit->receipt_file ?? ''); ?>" placeholder="Caminho ou nome do arquivo" />
                    </div>
                    <div class="col-md-6 mb10">
                        <label>Descricao</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo esc($reimbursement_edit->description ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-6 mb10">
                        <label>Observacoes</label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo esc($reimbursement_edit->notes ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4>Lista de reembolsos</h4></div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Descricao</th>
                        <th>Viagem</th>
                        <th>Funcionario</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reimbursements as $item) { ?>
                        <tr>
                            <td><?php echo esc($item->description); ?></td>
                            <td><?php echo esc($item->trip_title); ?></td>
                            <td><?php echo esc($item->employee_name); ?></td>
                            <td><?php echo esc($item->category_title); ?></td>
                            <td><?php echo travelrefunds_currency($item->amount); ?></td>
                            <td><?php echo esc(travelrefunds_status_label($item->status)); ?></td>
                            <td class="text-end">
                                <a href="<?php echo get_uri('travelrefunds/reimbursements?edit_id=' . $item->id); ?>" class="btn btn-default btn-sm">Editar</a>
                                <?php echo form_open(get_uri('travelrefunds/reimbursements/delete/' . $item->id), array('class' => 'd-inline')); ?>
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Excluir este reembolso?');">Excluir</button>
                                <?php echo form_close(); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
