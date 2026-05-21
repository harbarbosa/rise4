<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix d-flex justify-content-between align-items-center">
            <h1>Minhas Viagens</h1>
            <?php if (!empty($can_create)) { ?>
                <a href="<?php echo get_uri('travelrefunds/trips/new'); ?>" class="btn btn-primary">Nova Viagem</a>
            <?php } ?>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Destino</th>
                        <th>Periodo</th>
                        <th>Valor total</th>
                        <th>Status</th>
                        <th class="text-end">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip) { ?>
                        <tr>
                            <td><?php echo esc($trip->title); ?></td>
                            <td><?php echo esc($trip->destination); ?></td>
                            <td><?php echo esc(($trip->start_date ?: $trip->departure_date) . ' a ' . ($trip->end_date ?: $trip->return_date)); ?></td>
                            <td><?php echo travelrefunds_currency($trip->total_amount ?: $trip->estimated_amount); ?></td>
                            <td><?php echo esc(travelrefunds_status_label($trip->status)); ?></td>
                            <td class="text-end">
                                <a href="<?php echo get_uri('travelrefunds/trips/view/' . $trip->id); ?>" class="btn btn-default btn-sm">Abrir</a>
                                <?php if ($trip->status === 'draft' || $trip->status === 'rejected') { ?>
                                    <a href="<?php echo get_uri('travelrefunds/trips/view/' . $trip->id . '?edit=1'); ?>" class="btn btn-default btn-sm">Editar</a>
                                <?php } ?>
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
