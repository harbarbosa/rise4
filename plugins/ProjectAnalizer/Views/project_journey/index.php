<?php
$currentPhase = (int) $journey->current_phase;
$totalRequired = 0;
$passedRequired = 0;
foreach ($current_checks as $check) {
    if ($check["required"]) {
        $totalRequired++;
        if ($check["passed"]) $passedRequired++;
    }
}
$progress = $totalRequired ? round(($passedRequired / $totalRequired) * 100) : 100;
?>
<div class="card">
    <div class="page-title clearfix">
        <h1><i data-feather="map"></i> Jornada do projeto</h1>
        <div class="title-button-group">
            <span class="badge bg-primary p10">Fase <?php echo $currentPhase; ?> de 7: <?php echo esc($phases[$currentPhase]); ?></span>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb20">
            <div class="col-md-8">
                <div class="progress" style="height: 12px;">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <small class="text-muted"><?php echo $passedRequired; ?> de <?php echo $totalRequired; ?> itens obrigatórios concluídos nesta fase</small>
            </div>
            <div class="col-md-4">
                <?php echo form_open(get_uri("projectanalizer/journey/save_manager"), ["id" => "journey-manager-form", "class" => "general-form"]); ?>
                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                <div class="input-group">
                    <?php echo form_dropdown("manager_id", $managers, $journey->manager_id ?? "", "class='select2 form-control' aria-label='Gerente do projeto'"); ?>
                    <button class="btn btn-default" type="submit"><i data-feather="save" class="icon-16"></i></button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>

        <div class="row">
            <?php foreach ($phases as $number => $label): 
                $state = $number < $currentPhase ? "success" : ($number == $currentPhase ? "primary" : "light");
            ?>
                <div class="col-md-3 col-sm-6 mb15">
                    <div class="card border border-<?php echo $state; ?> h-100">
                        <div class="card-body p15">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?php echo $state; ?> me-2"><?php echo $number; ?></span>
                                <strong><?php echo esc($label); ?></strong>
                            </div>
                            <small class="text-muted">
                                <?php echo $number < $currentPhase ? "Concluída" : ($number == $currentPhase ? "Em andamento" : "Bloqueada"); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h4 class="mt20">Checklist da fase atual</h4>
        <div class="list-group mb20">
            <?php foreach ($current_checks as $check): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <i data-feather="<?php echo $check["passed"] ? "check-circle" : "circle"; ?>" class="icon-18 <?php echo $check["passed"] ? "text-success" : "text-warning"; ?>"></i>
                        <span class="ms-2"><?php echo esc($check["label"]); ?></span>
                        <?php if (!$check["required"]): ?><span class="badge bg-light text-muted ms-2">Recomendado</span><?php endif; ?>
                        <?php if ($check["detail"]): ?><small class="text-muted ms-2"><?php echo esc($check["detail"]); ?></small><?php endif; ?>
                    </div>
                    <?php if (!$check["passed"] && $check["url"]): ?>
                        <a href="<?php echo $check["url"]; ?>" class="btn btn-default btn-sm">Resolver</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($currentPhase < 7): ?>
            <button id="advance-journey" class="btn btn-primary" <?php echo $can_advance ? "" : "disabled"; ?>>
                <i data-feather="unlock" class="icon-16"></i> Concluir fase e liberar próximas abas
            </button>
            <?php if ($is_admin && !$can_advance): ?>
                <button id="override-journey" class="btn btn-outline-danger ms-2">Avançar como administrador</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card mt20">
    <div class="page-title clearfix">
        <h4>Planejamento e prontidão das tarefas</h4>
        <div class="title-button-group"><small class="text-muted">A liberação é individual: uma tarefa pronta pode avançar sem aguardar as demais.</small></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb0">
            <thead>
                <tr>
                    <th>Tarefa</th>
                    <th>Etapa</th>
                    <th>Datas</th>
                    <th>Responsável</th>
                    <th>Peso</th>
                    <th>Impedimento</th>
                    <th>Liberação</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$task_readiness): ?>
                <tr><td colspan="8" class="text-center text-muted p20">Nenhuma tarefa cadastrada.</td></tr>
            <?php endif; ?>
            <?php foreach ($task_readiness as $task): ?>
                <tr class="<?php echo $task["blocked"] ? "table-warning" : ""; ?>">
                    <td>
                        <strong><?php echo esc($task["title"]); ?></strong>
                        <?php if ($task["missing"]): ?>
                            <div><small class="text-danger">Pendente: <?php echo esc(implode(", ", $task["missing"])); ?></small></div>
                        <?php endif; ?>
                    </td>
                    <td><i data-feather="<?php echo $task["milestone"] ? "check-circle" : "alert-circle"; ?>" class="icon-16 <?php echo $task["milestone"] ? "text-success" : "text-warning"; ?>"></i></td>
                    <td>
                        <i data-feather="<?php echo $task["dates_valid"] ? "check-circle" : "alert-circle"; ?>" class="icon-16 <?php echo $task["dates_valid"] ? "text-success" : "text-warning"; ?>"></i>
                        <?php if ($task["dates"] && !$task["dates_valid"]): ?><small class="text-danger">Fora do período</small><?php endif; ?>
                    </td>
                    <td><i data-feather="<?php echo $task["assigned"] ? "check-circle" : "alert-circle"; ?>" class="icon-16 <?php echo $task["assigned"] ? "text-success" : "text-warning"; ?>"></i></td>
                    <td>
                        <span class="<?php echo $task["weight_valid"] ? "text-success" : "text-warning"; ?>">
                            <?php echo number_format($task["weight"], 2, ",", "."); ?>%
                        </span>
                    </td>
                    <td>
                        <?php if ($task["blocked"]): ?>
                            <span class="badge bg-warning text-dark">Bloqueada</span>
                            <div><small><?php echo esc($task["blocker_reason"]); ?></small></div>
                        <?php else: ?>
                            <span class="badge bg-light text-muted">Sem impedimento</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($task["released"]): ?>
                            <span class="badge bg-success">Liberada</span>
                        <?php elseif ($task["ready_to_release"]): ?>
                            <span class="badge bg-info">Pronta para liberar</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Planejamento pendente</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($task["blocked"]): ?>
                            <button class="btn btn-default btn-sm task-unblock" data-task-id="<?php echo $task["id"]; ?>">Remover bloqueio</button>
                        <?php else: ?>
                            <button class="btn btn-default btn-sm task-block" data-task-id="<?php echo $task["id"]; ?>">Registrar impedimento</button>
                        <?php endif; ?>

                        <?php if ($task["released"]): ?>
                            <button class="btn btn-outline-danger btn-sm task-revoke" data-task-id="<?php echo $task["id"]; ?>">Revogar</button>
                        <?php elseif ($task["ready_to_release"]): ?>
                            <button class="btn btn-primary btn-sm task-release" data-task-id="<?php echo $task["id"]; ?>">Liberar tarefa</button>
                        <?php elseif ($is_admin): ?>
                            <button class="btn btn-outline-danger btn-sm task-force-release" data-task-id="<?php echo $task["id"]; ?>">Forçar liberação</button>
                        <?php else: ?>
                            <button class="btn btn-primary btn-sm" disabled>Liberação pendente</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function () {
    $(".select2").select2();
    $("#journey-manager-form").appForm({
        isModal: false,
        onSuccess: function (result) {
            appAlert.success(result.message, {duration: 10000});
            location.reload();
        }
    });

    function advanceJourney(override, reason) {
        appLoader.show();
        $.ajax({
            url: "<?php echo get_uri('projectanalizer/journey/advance'); ?>",
            type: "POST",
            dataType: "json",
            data: {project_id: <?php echo (int) $project_id; ?>, override: override ? 1 : 0, reason: reason || ""},
            success: function (result) {
                appLoader.hide();
                if (result.success) {
                    appAlert.success(result.message, {duration: 10000});
                    location.reload();
                } else {
                    appAlert.error(result.message);
                }
            },
            error: function () {
                appLoader.hide();
                appAlert.error("Não foi possível avançar a jornada.");
            }
        });
    }

    $("#advance-journey").on("click", function () { advanceJourney(false, ""); });
    $("#override-journey").on("click", function () {
        var reason = prompt("Informe o motivo para avançar sem concluir os itens obrigatórios:");
        if (reason) advanceJourney(true, reason);
    });
});

    function taskAction(url, data) {
        appLoader.show();
        $.ajax({
            url: url,
            type: "POST",
            dataType: "json",
            data: $.extend({project_id: <?php echo (int) $project_id; ?>}, data),
            success: function (result) {
                appLoader.hide();
                if (result.success) {
                    appAlert.success(result.message, {duration: 10000});
                    location.reload();
                } else {
                    appAlert.error(result.message);
                }
            },
            error: function () {
                appLoader.hide();
                appAlert.error("Não foi possível atualizar a tarefa.");
            }
        });
    }

    $(".task-block").on("click", function () {
        var reason = prompt("Descreva o impedimento desta tarefa:");
        if (reason) taskAction("<?php echo get_uri('projectanalizer/journey/task_block'); ?>", {task_id: $(this).data("task-id"), blocked: 1, reason: reason});
    });
    $(".task-unblock").on("click", function () {
        taskAction("<?php echo get_uri('projectanalizer/journey/task_block'); ?>", {task_id: $(this).data("task-id"), blocked: 0, reason: ""});
    });
    $(".task-release").on("click", function () {
        var notes = prompt("Observação da liberação (opcional):") || "";
        taskAction("<?php echo get_uri('projectanalizer/journey/task_release'); ?>", {task_id: $(this).data("task-id"), override: 0, notes: notes});
    });
    $(".task-force-release").on("click", function () {
        var notes = prompt("Justifique a liberação administrativa:");
        if (notes) taskAction("<?php echo get_uri('projectanalizer/journey/task_release'); ?>", {task_id: $(this).data("task-id"), override: 1, notes: notes});
    });
    $(".task-revoke").on("click", function () {
        if (confirm("Deseja revogar a liberação desta tarefa?")) {
            taskAction("<?php echo get_uri('projectanalizer/journey/task_revoke'); ?>", {task_id: $(this).data("task-id")});
        }
    });
</script>
