<?php
$filters = $filters ?? array();
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Gestão de Despesas</h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('travelrefunds/reports'), '<i data-feather="bar-chart-2" class="icon-16"></i> Relatorios', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/trips'), 'Minhas Viagens', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reimbursements'), 'Reembolsos', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/approvals'), 'Aprovacoes', array('class' => 'btn btn-default')); ?>
            </div>
        </div>

        <div class="card-body">
            <?php echo form_open(get_uri('travelrefunds'), array('method' => 'get', 'class' => 'general-form')); ?>
                <div class="row g-2 mb15">
                    <div class="col-md-3">
                        <select name="employee_id" class="form-control select2" style="width:100%;">
                            <option value="">- Funcionario -</option>
                            <?php foreach ($users as $user) { ?>
                                <option value="<?php echo (int) $user->id; ?>" <?php echo ((int) ($filters['employee_id'] ?? 0) === (int) $user->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($user->first_name . ' ' . $user->last_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="project_id" class="form-control select2" style="width:100%;">
                            <option value="">- Projeto -</option>
                            <?php foreach ($projects as $project) { ?>
                                <option value="<?php echo (int) $project->id; ?>" <?php echo ((int) ($filters['project_id'] ?? 0) === (int) $project->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($project->title); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="client_id" class="form-control select2" style="width:100%;">
                            <option value="">- Cliente -</option>
                            <?php foreach ($clients as $client) { ?>
                                <option value="<?php echo (int) $client->id; ?>" <?php echo ((int) ($filters['client_id'] ?? 0) === (int) $client->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($client->company_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="category_id" class="form-control select2" style="width:100%;">
                            <option value="">- Categoria -</option>
                            <?php foreach ($categories as $category) { ?>
                                <option value="<?php echo (int) $category->id; ?>" <?php echo ((int) ($filters['category_id'] ?? 0) === (int) $category->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($category->title ?: $category->name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="start_date" class="form-control" value="<?php echo esc($filters['start_date'] ?? ''); ?>" />
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="end_date" class="form-control" value="<?php echo esc($filters['end_date'] ?? ''); ?>" />
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control select2" style="width:100%;">
                            <option value="">- Status -</option>
                            <?php foreach ($status_options as $status) { ?>
                                <option value="<?php echo esc($status); ?>" <?php echo (($filters['status'] ?? '') === $status) ? 'selected' : ''; ?>>
                                    <?php echo esc(travelrefunds_status_label($status)); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-5 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="<?php echo get_uri('travelrefunds'); ?>" class="btn btn-default">Limpar</a>
                    </div>
                </div>
            <?php echo form_close(); ?>

            <div class="row">
                <div class="col-md-3 mb15">
                    <div class="card card-body h-100">
                        <div class="text-off">Total em aberto</div>
                        <div class="fs-4 fw-bold"><?php echo travelrefunds_currency($summary['open_total']); ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body h-100">
                        <div class="text-off">Total aprovado no mes</div>
                        <div class="fs-4 fw-bold text-success"><?php echo travelrefunds_currency($summary['approved_total']); ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body h-100">
                        <div class="text-off">Solicitacoes pendentes</div>
                        <div class="fs-4 fw-bold text-warning"><?php echo (int) $summary['pending_total']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body h-100">
                        <div class="text-off">Viagens rejeitadas</div>
                        <div class="fs-4 fw-bold text-danger"><?php echo (int) $summary['rejected_total']; ?></div>
                    </div>
                </div>
            </div>

            <div class="card mb15">
                <div class="card-header"><h4 class="card-title mb-0">Gastos por categoria</h4></div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (array_slice($spend_by_category, 0, 6, true) as $category => $amount) { ?>
                            <div class="col-md-4 mb10">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-off"><?php echo esc($category); ?></div>
                                    <div class="fw-bold"><?php echo travelrefunds_currency($amount); ?></div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if (empty($spend_by_category)) { ?>
                            <div class="col-12 text-center text-muted">Sem dados para exibir.</div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card mb15">
                        <div class="card-header"><h4 class="card-title mb-0">Ultimas viagens</h4></div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Titulo</th>
                                        <th>Destino</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_trips as $trip) { ?>
                                        <tr>
                                            <td><?php echo esc($trip->title); ?></td>
                                            <td><?php echo esc($trip->destination); ?></td>
                                            <td><?php echo esc(travelrefunds_status_label($trip->status)); ?></td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (empty($recent_trips)) { ?>
                                        <tr><td colspan="3" class="text-center text-muted">Sem registros.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card mb15">
                        <div class="card-header"><h4 class="card-title mb-0">Ultimas despesas</h4></div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Descricao</th>
                                        <th>Categoria</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_reimbursements as $item) { ?>
                                        <tr>
                                            <td><?php echo esc($item->description); ?></td>
                                            <td><?php echo esc($item->category_title); ?></td>
                                            <td><?php echo travelrefunds_currency($item->amount); ?></td>
                                            <td><?php echo esc(travelrefunds_status_label($item->status)); ?></td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (empty($recent_reimbursements)) { ?>
                                        <tr><td colspan="4" class="text-center text-muted">Sem registros.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".page-wrapper .select2").select2();
    });
</script>
