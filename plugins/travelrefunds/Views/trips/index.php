<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix">
            <h1>Minhas Viagens</h1>
        </div>
        <div class="card-body">
            <?php echo form_open(get_uri('travelrefunds/trips/save'), array('class' => 'general-form')); ?>
                <input type="hidden" name="id" value="<?php echo $trip_edit->id ?? ''; ?>" />
                <div class="row">
                    <div class="col-md-4 mb10">
                        <label>Titulo</label>
                        <input type="text" name="title" class="form-control" value="<?php echo esc($trip_edit->title ?? ''); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Funcionario</label>
                        <select name="employee_id" class="form-control select2">
                            <option value="">-</option>
                            <?php foreach ($users as $user) { ?>
                                <option value="<?php echo $user->id; ?>" <?php echo (($trip_edit->employee_id ?? '') == $user->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($user->first_name . ' ' . $user->last_name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Projeto</label>
                        <select name="project_id" class="form-control select2">
                            <option value="">-</option>
                            <?php foreach ($projects as $project) { ?>
                                <option value="<?php echo $project->id; ?>" <?php echo (($trip_edit->project_id ?? '') == $project->id) ? 'selected' : ''; ?>>
                                    <?php echo esc($project->title); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Destino</label>
                        <input type="text" name="destination" class="form-control" value="<?php echo esc($trip_edit->destination ?? ''); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Saida</label>
                        <input type="date" name="departure_date" class="form-control" value="<?php echo esc($trip_edit->departure_date ?? ''); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Retorno</label>
                        <input type="date" name="return_date" class="form-control" value="<?php echo esc($trip_edit->return_date ?? ''); ?>" />
                    </div>
                    <div class="col-md-4 mb10">
                        <label>Status</label>
                        <select name="status" class="form-control select2">
                            <?php foreach ($status_options as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo (($trip_edit->status ?? 'draft') === $status) ? 'selected' : ''; ?>>
                                    <?php echo esc(travelrefunds_status_label($status)); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb10">
                        <label>Objetivo</label>
                        <textarea name="purpose" class="form-control" rows="3"><?php echo esc($trip_edit->purpose ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-6 mb10">
                        <label>Observacoes</label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo esc($trip_edit->notes ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-3 mb10">
                        <label>Estimado</label>
                        <input type="number" step="0.01" name="estimated_amount" class="form-control" value="<?php echo esc($trip_edit->estimated_amount ?? '0'); ?>" />
                    </div>
                    <div class="col-md-3 mb10">
                        <label>Real</label>
                        <input type="number" step="0.01" name="actual_amount" class="form-control" value="<?php echo esc($trip_edit->actual_amount ?? '0'); ?>" />
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4>Lista de viagens</h4></div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Funcionario</th>
                        <th>Destino</th>
                        <th>Projeto</th>
                        <th>Periodo</th>
                        <th>Status</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip) { ?>
                        <tr>
                            <td><?php echo esc($trip->title); ?></td>
                            <td><?php echo esc($trip->employee_name); ?></td>
                            <td><?php echo esc($trip->destination); ?></td>
                            <td><?php echo esc($trip->project_title); ?></td>
                            <td><?php echo esc($trip->departure_date . ' a ' . $trip->return_date); ?></td>
                            <td><?php echo esc(travelrefunds_status_label($trip->status)); ?></td>
                            <td class="text-end">
                                <a href="<?php echo get_uri('travelrefunds/trips?edit_id=' . $trip->id); ?>" class="btn btn-default btn-sm">Editar</a>
                                <?php echo form_open(get_uri('travelrefunds/trips/delete/' . $trip->id), array('class' => 'd-inline')); ?>
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Excluir esta viagem?');">Excluir</button>
                                <?php echo form_close(); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
