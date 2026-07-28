<?php
$nc = $nc;
$action_plans = $action_plans;
$logs = $logs;
?>

<div id="page-content" class="page-wrapper clearfix">
    <!-- Header -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><?php echo $nc->code; ?></h4>
                <small class="text-muted"><?php echo $nc->laudo_number ?? ''; ?></small>
            </div>
            <div>
                <span class="badge bg-<?php echo $this->_get_risk_color($nc->risk_level); ?> fs-6">
                    Risco N<?php echo $nc->risk_level; ?>
                </span>
                <span class="badge bg-<?php echo $this->_get_class_color($nc->classification); ?> fs-6">
                    <?php echo ucfirst($nc->classification); ?>
                </span>
                <span class="badge bg-<?php echo $this->_get_status_color($nc->status); ?> fs-6">
                    <?php echo $nc->status; ?>
                </span>
                <?php echo modal_anchor(get_uri("laudo_nonconformities/form/" . $nc->id), "<i data-feather='edit-2' class='icon-16'></i>", array("class" => "btn btn-default btn-sm", "title" => "Editar")); ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informações -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Descrição</h5>
                </div>
                <div class="card-body">
                    <h5><?php echo $nc->title; ?></h5>
                    <p><?php echo nl2br($nc->description); ?></p>
                    
                    <?php if ($nc->recommendation): ?>
                    <div class="alert alert-info mt-3">
                        <strong>Recomendação:</strong><br>
                        <?php echo nl2br($nc->recommendation); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Planos de Ação (<?php echo count($action_plans); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($action_plans)): ?>
                    <div class="text-center text-muted py-4">
                        <p>Nenhum plano de ação</p>
                        <?php echo modal_anchor(get_uri("laudo_nonconformities/action_plan_form/" . $nc->id), "<i data-feather='plus' class='icon-16'></i> Adicionar Plano de Ação", array("class" => "btn btn-primary btn-sm", "title" => "Novo Plano de Ação")); ?>
                    </div>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Ação</th>
                                <th>Responsável</th>
                                <th>Prazo</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($action_plans as $plan): ?>
                            <tr>
                                <td><?php echo substr($plan->action, 0, 50); ?>...</td>
                                <td><?php echo $plan->responsible_id ? 'User ' . $plan->responsible_id : '-'; ?></td>
                                <td><?php echo $plan->deadline; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $plan->status === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo $plan->status; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($plan->status !== 'completed'): ?>
                                    <button class="btn btn-xs btn-success" onclick="complete_plan(<?php echo $plan->id; ?>)">
                                        <i data-feather="check" class="icon-14"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (!$plan->task_id): ?>
                                    <button class="btn btn-xs btn-info" onclick="create_task(<?php echo $plan->id; ?>)" title="Criar tarefa">
                                        <i data-feather="check-square" class="icon-14"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo modal_anchor(get_uri("laudo_nonconformities/action_plan_form/" . $nc->id), "<i data-feather='plus' class='icon-16'></i> Adicionar", array("class" => "btn btn-primary btn-sm mt-2", "title" => "Novo Plano de Ação")); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Histórico -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Histórico</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                    <p class="text-muted">Nenhum registro</p>
                    <?php else: ?>
                    <ul class="timeline">
                        <?php foreach ($logs as $log): ?>
                        <li>
                            <small class="text-muted"><?php echo $log->created_at; ?></small>
                            <p>
                                <strong><?php echo $log->user_name ?? 'Usuário'; ?></strong>
                                alterou status de <strong><?php echo $log->old_status; ?></strong> para <strong><?php echo $log->new_status; ?></strong>
                                <?php if ($log->comment): ?>
                                <br><em><?php echo $log->comment; ?></em>
                                <?php endif; ?>
                            </p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Ações Laterais -->
        <div class="col-md-4">
            <!-- Informações -->
            <div class="card">
                <div class="card-header">
                    <h5>Informações</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Local</th><td><?php echo $nc->location ?? '-'; ?></td></tr>
                        <tr><th>Setor</th><td><?php echo $nc->sector ?? '-'; ?></td></tr>
                        <tr><th>Data ID</th><td><?php echo $nc->identified_at; ?></td></tr>
                        <tr><th>Prazo</th><td><?php echo $nc->suggested_deadline ?? '-'; ?></td></tr>
                        <tr><th>Classif.</th><td><?php echo ucfirst($nc->classification); ?></td></tr>
                        <tr><th>Prob.</th><td><?php echo $nc->probability; ?>/5</td></tr>
                        <tr><th>Impacto</th><td><?php echo $nc->impact; ?>/5</td></tr>
                    </table>
                </div>
            </div>
            
            <!-- Ações -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Ações</h5>
                </div>
                <div class="card-body">
                    <?php if ($nc->status === 'corrected' || $nc->status === 'waiting_validation'): ?>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="validate_nc()">
                            <i data-feather="check-circle" class="icon-16"></i> Validar
                        </button>
                        <button class="btn btn-danger" onclick="reject_nc()">
                            <i data-feather="x-circle" class="icon-16"></i> Rejeitar
                        </button>
                    </div>
                    <?php elseif ($nc->status === 'validated'): ?>
                    <div class="alert alert-success">
                        <i data-feather="check-circle" class="icon-16"></i> Validada em <?php echo $nc->validated_at; ?>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label>Alterar Status</label>
                        <?php echo form_dropdown('new_status', isset($status_list) ? $status_list : array(), $nc->status, "class='form-control' id='new_status' onchange='update_status(this.value)'"); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function update_status(status) {
    if (confirm('Alterar status?')) {
        $.ajax({
            url: '<?php echo get_uri("laudo_nonconformities/update_status/" . $nc->id); ?>',
            type: 'POST',
            data: { status: status },
            success: function() {
                location.reload();
            }
        });
    }
}

function validate_nc() {
    if (confirm('Validar esta não conformidade?')) {
        $.ajax({
            url: '<?php echo get_uri("laudo_nonconformities/validate/" . $nc->id); ?>',
            type: 'POST',
            data: {},
            success: function() {
                location.reload();
            }
        });
    }
}

function reject_nc() {
    var comment = prompt('Motivo da rejeição:');
    if (comment) {
        $.ajax({
            url: '<?php echo get_uri("laudo_nonconformities/reject/" . $nc->id); ?>',
            type: 'POST',
            data: { comment: comment },
            success: function() {
                location.reload();
            }
        });
    }
}

function complete_plan(id) {
    var evidence = prompt('Evidência da conclusão:');
    if (evidence !== null) {
        $.ajax({
            url: '<?php echo get_uri("laudo_nonconformities/action_plan_complete/"); ?>' + id,
            type: 'POST',
            data: { evidence: evidence },
            success: function() {
                location.reload();
            }
        });
    }
}

function create_task(id) {
    $.ajax({
        url: '<?php echo get_uri("laudo_nonconformities/action_plan_create_task/"); ?>' + id,
        type: 'POST',
        success: function(response) {
            if (response.success) {
                appAlert.success('Tarefa criada: ' + response.task_id);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                appAlert.error(response.message);
            }
        }
    });
}

function get_risk_color(level) {
    if (level >= 9) return 'danger';
    if (level >= 6) return 'warning';
    if (level >= 3) return 'info';
    return 'success';
}

function get_class_color(cl) {
    var colors = {
        'observation': 'secondary',
        'improvement': 'info',
        'low': 'success',
        'moderate': 'warning',
        'high': 'warning',
        'critical': 'danger',
        'emergential': 'dark'
    };
    return colors[cl] || 'secondary';
}

function get_status_color(st) {
    var colors = {
        'open': 'danger',
        'in_analysis': 'info',
        'waiting_correction': 'warning',
        'in_correction': 'primary',
        'corrected': 'success',
        'waiting_validation': 'warning',
        'validated': 'success',
        'rejected': 'dark',
        'canceled': 'secondary'
    };
    return colors[st] || 'secondary';
}
</script>