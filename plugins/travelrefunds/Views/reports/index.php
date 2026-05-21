<?php
$filters = $filters ?? array();
$report_data = $report_data ?? array();
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Relatorios</h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('travelrefunds/reports/export/summary') . '?' . http_build_query($filters), '<i data-feather="download" class="icon-16"></i> CSV Resumo', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports/export-xlsx/summary') . '?' . http_build_query($filters), '<i data-feather="download" class="icon-16"></i> Excel Resumo', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports/export/employee') . '?' . http_build_query($filters), '<i data-feather="download" class="icon-16"></i> Despesas por funcionario', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports/export-xlsx/employee') . '?' . http_build_query($filters), '<i data-feather="download" class="icon-16"></i> Excel Funcionario', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports/export/project') . '?' . http_build_query($filters), '<i data-feather="download" class="icon-16"></i> Despesas por projeto', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports/export-xlsx/project') . '?' . http_build_query($filters), '<i data-feather="download" class="icon-16"></i> Excel Projeto', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports/export/category') . '?' . http_build_query($filters), '<i data-feather="download" class="icon-16"></i> Despesas por categoria', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports/export-xlsx/category') . '?' . http_build_query($filters), '<i data-feather="download" class="icon-16"></i> Excel Categoria', array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted">Os relatorios usam os mesmos filtros do painel principal.</p>

            <?php echo form_open(get_uri('travelrefunds/reports'), array('method' => 'get', 'class' => 'general-form')); ?>
                <div class="row g-2 mb15">
                    <div class="col-md-3">
                        <select name="employee_id" class="select2" style="width:100%;">
                            <option value="">- Funcionario -</option>
                            <?php foreach ($users as $user) { ?>
                                <option value="<?php echo (int) $user->id; ?>" <?php echo ((int) ($filters['employee_id'] ?? 0) === (int) $user->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($user->first_name . ' ' . $user->last_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="project_id" class="select2" style="width:100%;">
                            <option value="">- Projeto -</option>
                            <?php foreach ($projects as $project) { ?>
                                <option value="<?php echo (int) $project->id; ?>" <?php echo ((int) ($filters['project_id'] ?? 0) === (int) $project->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($project->title); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="client_id" class="select2" style="width:100%;">
                            <option value="">- Cliente -</option>
                            <?php foreach ($clients as $client) { ?>
                                <option value="<?php echo (int) $client->id; ?>" <?php echo ((int) ($filters['client_id'] ?? 0) === (int) $client->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($client->company_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="category_id" class="select2" style="width:100%;">
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
                        <select name="status" class="select2" style="width:100%;">
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
                        <a href="<?php echo get_uri('travelrefunds/reports'); ?>" class="btn btn-default">Limpar</a>
                    </div>
                </div>
            <?php echo form_close(); ?>

            <div class="row">
                <div class="col-lg-4">
                    <div class="card mb15">
                        <div class="card-header"><h4 class="card-title mb-0">Despesas por funcionario</h4></div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Funcionario</th>
                                        <th class="text-end">Qtd</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['by_employee'] ?? array() as $row) { ?>
                                        <tr>
                                            <td><?php echo esc($row['label']); ?></td>
                                            <td class="text-end"><?php echo (int) $row['count']; ?></td>
                                            <td class="text-end"><?php echo travelrefunds_currency($row['total']); ?></td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (empty($report_data['by_employee'])) { ?>
                                        <tr><td colspan="3" class="text-center text-muted">Sem dados.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb15">
                        <div class="card-header"><h4 class="card-title mb-0">Despesas por projeto</h4></div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Projeto</th>
                                        <th class="text-end">Qtd</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['by_project'] ?? array() as $row) { ?>
                                        <tr>
                                            <td><?php echo esc($row['label']); ?></td>
                                            <td class="text-end"><?php echo (int) $row['count']; ?></td>
                                            <td class="text-end"><?php echo travelrefunds_currency($row['total']); ?></td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (empty($report_data['by_project'])) { ?>
                                        <tr><td colspan="3" class="text-center text-muted">Sem dados.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb15">
                        <div class="card-header"><h4 class="card-title mb-0">Despesas por categoria</h4></div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th class="text-end">Qtd</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['by_category'] ?? array() as $row) { ?>
                                        <tr>
                                            <td><?php echo esc($row['label']); ?></td>
                                            <td class="text-end"><?php echo (int) $row['count']; ?></td>
                                            <td class="text-end"><?php echo travelrefunds_currency($row['total']); ?></td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (empty($report_data['by_category'])) { ?>
                                        <tr><td colspan="3" class="text-center text-muted">Sem dados.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Resumo mensal</h4></div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th class="text-end">Aprovado</th>
                                <th class="text-end">Em aberto</th>
                                <th class="text-end">Enviadas</th>
                                <th class="text-end">Rejeitadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['monthly'] ?? array() as $row) { ?>
                                <tr>
                                    <td><?php echo esc($row['label']); ?></td>
                                    <td class="text-end"><?php echo travelrefunds_currency($row['approved_total']); ?></td>
                                    <td class="text-end"><?php echo travelrefunds_currency($row['open_total']); ?></td>
                                    <td class="text-end"><?php echo (int) $row['submitted']; ?></td>
                                    <td class="text-end"><?php echo (int) $row['rejected']; ?></td>
                                </tr>
                            <?php } ?>
                            <?php if (empty($report_data['monthly'])) { ?>
                                <tr><td colspan="5" class="text-center text-muted">Sem dados.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
