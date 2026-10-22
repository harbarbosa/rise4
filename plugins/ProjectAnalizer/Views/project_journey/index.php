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
    <div class="page-title clearfix"><h4>Prontidão por tarefa</h4></div>
    <div class="table-responsive">
        <table class="table table-hover mb0">
            <thead><tr><th>Tarefa</th><th>Datas</th><th>Responsável</th><th>Materiais</th><th>Atividades</th><th>Situação</th></tr></thead>
            <tbody>
            <?php if (!$task_readiness): ?>
                <tr><td colspan="6" class="text-center text-muted p20">Nenhuma tarefa cadastrada.</td></tr>
            <?php endif; ?>
            <?php foreach ($task_readiness as $task): ?>
                <tr>
                    <td><?php echo esc($task["title"]); ?></td>
                    <td><i data-feather="<?php echo $task["dates"] ? "check" : "alert-circle"; ?>" class="icon-16 <?php echo $task["dates"] ? "text-success" : "text-warning"; ?>"></i></td>
                    <td><i data-feather="<?php echo $task["assigned"] ? "check" : "alert-circle"; ?>" class="icon-16 <?php echo $task["assigned"] ? "text-success" : "text-warning"; ?>"></i></td>
                    <td><?php echo (int) $task["materials"]; ?> associados</td>
                    <td><?php echo (int) $task["activities"]; ?> lançadas</td>
                    <td><span class="badge bg-<?php echo $task["completed"] ? "success" : "secondary"; ?>"><?php echo $task["completed"] ? "Concluída" : "Pendente"; ?></span></td>
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
</script>
