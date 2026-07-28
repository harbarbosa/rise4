<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="page-title clearfix">
                    <h1>Responsáveis Técnicos</h1>
                    <div class="title-button-group">
                        <?php echo modal_anchor(get_uri("laudo_review/professional_form"), "<i data-feather='plus' class='icon-16'></i> Novo Responsável", array("class" => "btn btn-primary", "title" => "Novo Responsável")); ?>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="display" width="100%">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Conselho</th>
                                <th>Especialidade</th>
                                <th>E-mail</th>
                                <th>Validade</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($professionals as $p): ?>
                            <tr>
                                <td><?php echo $p->name; ?></td>
                                <td><?php echo $p->cpf; ?></td>
                                <td><?php echo $p->council_type; ?> <?php echo $p->council_number; ?>/<?php echo $p->council_state; ?></td>
                                <td><?php echo $p->specialty; ?></td>
                                <td><?php echo $p->email; ?></td>
                                <td><?php echo $p->validity_end; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $p->status === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $p->status; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo modal_anchor(get_uri("laudo_review/professional_form/" . $p->id), "<i data-feather='edit-2' class='icon-16'></i>", array("class" => "btn btn-default btn-sm", "title" => "Editar")); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>