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
<style>
#project-journey .journey-card{background:#fff;border:1px solid #e7e9ec;border-radius:8px;overflow:hidden;margin-bottom:20px}
#project-journey .journey-title{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:18px 20px;border-bottom:1px solid #e7e9ec}
#project-journey .journey-title h1{font-size:20px;line-height:1.3;margin:0;font-weight:600}
#project-journey .journey-phase-badge{display:inline-block;background:#1c7ed6;color:#fff;border-radius:20px;padding:7px 13px;font-size:12px;font-weight:600;white-space:nowrap}
#project-journey .journey-body{padding:20px}
#project-journey .journey-summary{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:24px;align-items:end;margin-bottom:22px}
#project-journey .journey-progress-track{height:12px;background:#e9ecef;border-radius:10px;overflow:hidden;margin-bottom:7px}
#project-journey .journey-progress-value{height:100%;background:#1c7ed6;border-radius:10px;transition:width .25s ease}
#project-journey .journey-progress-text{color:#6c757d;font-size:12px}
#project-journey .journey-manager label{display:block;font-size:12px;font-weight:600;color:#495057;margin-bottom:6px}
#project-journey .journey-manager-row{display:flex;gap:8px}
#project-journey .journey-manager-row select{width:100%}
#project-journey .journey-manager-row .select2-container{flex:1 1 auto;width:100%!important;min-width:0}
#project-journey .journey-manager-row .select2-container .select2-choice{height:38px;line-height:36px;border-color:#ced4da}
#project-journey .journey-manager-row .select2-container .select2-choice .select2-arrow b{background-position:0 6px}
#project-journey .journey-manager-row button{height:38px;min-width:42px}
#project-journey .journey-phase-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:24px}
#project-journey .journey-phase{position:relative;min-height:94px;padding:15px;border:1px solid #dde2e6;border-radius:7px;background:#f8f9fa;text-align:left;width:100%;font:inherit;cursor:pointer;transition:border-color .15s ease,box-shadow .15s ease,transform .15s ease}
#project-journey .journey-phase:hover,#project-journey .journey-phase:focus{border-color:#1c7ed6;box-shadow:0 3px 10px rgba(0,0,0,.08);transform:translateY(-1px);outline:0}
#project-journey .journey-phase.is-selected{box-shadow:0 0 0 3px rgba(28,126,214,.16)}
#project-journey .journey-phase.is-current{border-color:#1c7ed6;background:#eef7ff;box-shadow:0 0 0 1px rgba(28,126,214,.08)}
#project-journey .journey-phase.is-complete{border-color:#51cf66;background:#f3fff5}
#project-journey .journey-phase-number{display:inline-flex;align-items:center;justify-content:center;width:27px;height:27px;border-radius:50%;background:#adb5bd;color:#fff;font-size:12px;font-weight:700;margin-bottom:9px}
#project-journey .is-current .journey-phase-number{background:#1c7ed6}
#project-journey .is-complete .journey-phase-number{background:#37b24d}
#project-journey .journey-phase-name{display:block;font-size:13px;font-weight:600;color:#343a40;margin-bottom:3px}
#project-journey .journey-phase-state{font-size:11px;color:#868e96}
#project-journey .journey-section-title{font-size:16px;font-weight:600;margin:0 0 10px}
#project-journey .journey-checklist{border:1px solid #e7e9ec;border-radius:7px;overflow:hidden;margin-bottom:18px}
#project-journey .journey-check{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:13px 15px;border-bottom:1px solid #edf0f2;background:#fff}
#project-journey .journey-check:last-child{border-bottom:0}
#project-journey .journey-check[title]{cursor:help}
#project-journey .journey-check-help{display:inline-flex;align-items:center;justify-content:center;width:17px;height:17px;margin-left:6px;border-radius:50%;background:#e7f5ff;color:#1971c2;font-size:11px;font-weight:700;vertical-align:middle}
#project-journey .journey-check-main{display:flex;align-items:flex-start;gap:10px;min-width:0}
#project-journey .journey-check-icon{font-size:18px;line-height:1;color:#f59f00}
#project-journey .journey-check.is-done .journey-check-icon{color:#2f9e44}
#project-journey .journey-check-label{font-size:13px;color:#343a40}
#project-journey .journey-check-detail{display:block;font-size:11px;color:#868e96;margin-top:3px}
#project-journey .journey-optional{display:inline-block;background:#f1f3f5;color:#6c757d;border-radius:10px;padding:2px 7px;font-size:10px;margin-left:6px}
#project-journey .journey-actions{display:flex;gap:8px;flex-wrap:wrap}
#project-journey .journey-panel{background:#fff;border:1px solid #e7e9ec;border-radius:8px;overflow:hidden;margin-top:20px}
#project-journey .journey-panel-header{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:15px 18px;border-bottom:1px solid #e7e9ec;background:#fff}
#project-journey .journey-panel-header h4{font-size:16px;line-height:1.3;margin:0;font-weight:600;color:#343a40}
#project-journey .journey-panel-hint{font-size:12px;color:#868e96;text-align:right}
#project-journey .journey-table-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
#project-journey .journey-table{width:100%;margin:0;border-collapse:collapse;background:#fff}
#project-journey .journey-table th{padding:11px 12px;border-bottom:2px solid #e7e9ec;background:#f8f9fa;color:#495057;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.02em;white-space:nowrap;text-align:left}
#project-journey .journey-table td{padding:12px;border-bottom:1px solid #edf0f2;color:#343a40;font-size:12px;vertical-align:middle}
#project-journey .journey-table tbody tr:last-child td{border-bottom:0}
#project-journey .journey-table tbody tr:hover{background:#fbfcfd}
#project-journey .journey-table .table-warning{background:#fff9db}
#project-journey .journey-table small{font-size:11px;line-height:1.35}
#project-journey .journey-panel-body{padding:16px 18px}
#project-journey .journey-panel .badge{display:inline-block;padding:4px 8px;border-radius:12px;font-size:10px;font-weight:600;line-height:1.2}
#project-journey .journey-panel .bg-success{background:#d3f9d8!important;color:#237b36!important}
#project-journey .journey-panel .bg-warning{background:#fff3bf!important;color:#8a6100!important}
#project-journey .journey-panel .bg-info{background:#d0ebff!important;color:#1864ab!important}
#project-journey .journey-panel .bg-secondary{background:#e9ecef!important;color:#495057!important}
#project-journey .journey-panel .bg-light{background:#f1f3f5!important;color:#6c757d!important}
#project-journey .journey-panel .text-end{text-align:right}
#project-journey .journey-panel .text-nowrap{white-space:nowrap}
#project-journey .journey-panel .text-muted{color:#868e96!important}
#project-journey .journey-panel .text-danger{color:#e03131!important}
#project-journey .journey-panel .text-warning{color:#e67700!important}
#project-journey .journey-panel .text-success{color:#2f9e44!important}
#project-journey .journey-filter-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:15px}
#project-journey .journey-filter-row select{width:100%}
#project-journey .journey-filter-row .select2-container{width:100%!important}
#project-journey .journey-filter-row .select2-container .select2-choice{height:38px;line-height:36px;border-color:#ced4da}
#project-journey .journey-resolve-action{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:32px;padding:6px 12px;border:1px solid #1c7ed6;border-radius:5px;background:#1c7ed6;color:#fff!important;font-size:12px;font-weight:600;line-height:1.2;text-decoration:none!important;white-space:nowrap;cursor:pointer;box-shadow:none}
#project-journey .journey-resolve-action:hover,#project-journey .journey-resolve-action:focus{border-color:#1864ab;background:#1864ab;color:#fff!important;text-decoration:none!important;outline:0}
#project-journey .journey-resolve-action svg{width:14px;height:14px;stroke:currentColor}
#journey-resolver-modal .journey-resolver-row{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:12px 0;border-bottom:1px solid #edf0f2}
#journey-resolver-modal .journey-resolver-row:last-child{border-bottom:0}
#journey-resolver-modal .journey-resolver-actions{display:flex;gap:8px;flex-wrap:wrap}
#journey-resolver-modal .select2-container{width:100%!important}
#journey-resolver-modal .hide{display:none!important}
#project-journey #journey-manager-select,#project-journey #journey-task-readiness,#project-journey #journey-material-flow{scroll-margin-top:90px}
#project-journey .journey-highlight{animation:journey-highlight 1.8s ease}
@keyframes journey-highlight{0%,100%{box-shadow:none}35%{box-shadow:0 0 0 4px rgba(28,126,214,.2)}}
@media(max-width:991px){#project-journey .journey-phase-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:767px){#project-journey .journey-title{align-items:flex-start;flex-direction:column}#project-journey .journey-summary{grid-template-columns:1fr}#project-journey .journey-phase-grid{grid-template-columns:1fr}#project-journey .journey-check{align-items:flex-start;flex-direction:column}#project-journey .journey-panel-header{align-items:flex-start;flex-direction:column}#project-journey .journey-panel-hint{text-align:left}#project-journey .journey-filter-row{grid-template-columns:1fr}}
</style>

<div id="project-journey">
    <div class="journey-card">
        <div class="journey-title">
            <h1><i data-feather="map" class="icon-18"></i> Jornada do projeto</h1>
            <span class="journey-phase-badge">Fase <?php echo $currentPhase; ?> de 7 · <?php echo esc($phases[$currentPhase]); ?></span>
        </div>

        <div class="journey-body">
            <div class="journey-summary">
                <div>
                    <div class="journey-progress-track">
                        <div class="journey-progress-value" style="width:<?php echo $progress; ?>%"></div>
                    </div>
                    <div class="journey-progress-text"><?php echo $passedRequired; ?> de <?php echo $totalRequired; ?> itens obrigatórios concluídos nesta fase</div>
                </div>

                <div class="journey-manager">
                    <label for="journey-manager-select">Gerente responsável pelo projeto</label>
                    <?php echo form_open(get_uri("projectanalizer/journey/save_manager"), ["id" => "journey-manager-form", "class" => "general-form"]); ?>
                        <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">
                        <div class="journey-manager-row">
                            <?php echo form_dropdown("manager_id", $managers, $journey->manager_id ?? "", "id='journey-manager-select' class='form-control select2'"); ?>
                            <button class="btn btn-default" type="submit" title="Salvar responsável"><i data-feather="save" class="icon-16"></i></button>
                        </div>
                    <?php echo form_close(); ?>
                </div>
            </div>

            <div class="journey-phase-grid">
                <?php foreach ($phases as $number => $label):
                    $phaseClass = $number < $currentPhase ? "is-complete" : ($number == $currentPhase ? "is-current" : "is-locked");
                ?>
                    <button type="button" class="journey-phase <?php echo $phaseClass; ?>" data-journey-phase="<?php echo (int)$number; ?>" aria-label="Ver checklist da fase <?php echo (int)$number; ?>">
                        <span class="journey-phase-number"><?php echo $number; ?></span>
                        <span class="journey-phase-name"><?php echo esc($label); ?></span>
                        <span class="journey-phase-state"><?php echo $number < $currentPhase ? "Concluída" : ($number == $currentPhase ? "Em andamento" : "Bloqueada"); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php
            $checkHelpTexts = [
                "manager" => "Selecione o gerente responsável no campo acima e salve a alteração.",
                "start_date" => "Abra os dados do projeto e informe uma data de início válida.",
                "deadline" => "Abra os dados do projeto e informe a data prevista de término.",
                "members" => "Acesse a equipe do projeto e vincule pelo menos um membro.",
                "milestones" => "Acesse Etapas e cadastre a estrutura de execução do projeto.",
                "milestone_weight" => "Ajuste os percentuais das etapas até que a soma seja exatamente 100%.",
                "tasks" => "Acesse Tarefas e cadastre pelo menos uma tarefa para o projeto.",
                "task_release" => "Na tabela Planejamento e prontidão, complete os campos obrigatórios da tarefa e clique em Liberar tarefa.",
                "task_plan" => "Edite as tarefas indicadas e preencha etapa, datas, responsável e peso.",
                "task_dates" => "Edite as datas das tarefas indicadas. Elas devem estar dentro do período de início e término do projeto.",
                "task_weights" => "Edite o peso das tarefas para que cada etapa totalize exatamente 100%.",
                "material_review" => "Acesse Materiais do projeto e confira a lista importada da proposta.",
                "material_allocation" => "Associe os materiais às tarefas que irão utilizá-los.",
                "material_critical" => "Na tabela Fluxo de materiais e compras, regularize o estoque, recebimento ou entrega dos itens críticos.",
                "material_requests" => "Acesse Materiais e crie as requisições das quantidades ainda pendentes.",
                "material_delivery" => "Acompanhe as ordens de compra e registre o recebimento dos materiais.",
                "schedule" => "Acesse a Agenda de execução e cadastre pelo menos uma programação.",
                "activity" => "Acesse Atividades e registre pelo menos um lançamento de execução.",
                "monitoring" => "Acompanhe o percentual executado e o andamento de cada tarefa.",
                "tasks_finished" => "Conclua todas as tarefas pendentes do projeto.",
                "close_project" => "Revise as pendências finais antes de encerrar o projeto."
            ];
            ?>
            <h4 class="journey-section-title" id="journey-checklist-title">Checklist da fase atual</h4>
            <div class="journey-checklist" id="journey-phase-checklist">
                <?php foreach ($current_checks as $check):
                    $helpText = $checkHelpTexts[$check["key"]] ?? "Abra a tela indicada para corrigir esta pendência.";
                    if (!empty($check["detail"])) {
                        $helpText .= " Situação atual: ".$check["detail"].".";
                    }
                ?>
                    <div class="journey-check <?php echo $check["passed"] ? "is-done" : ""; ?>"
                         title="<?php echo esc($helpText); ?>"
                         aria-label="<?php echo esc($check["label"].". ".$helpText); ?>">
                        <div class="journey-check-main">
                            <span class="journey-check-icon"><?php echo $check["passed"] ? "✓" : "!"; ?></span>
                            <div>
                                <span class="journey-check-label">
                                    <?php echo esc($check["label"]); ?>
                                    <span class="journey-check-help" aria-hidden="true">?</span>
                                    <?php if (!$check["required"]): ?><span class="journey-optional">Recomendado</span><?php endif; ?>
                                </span>
                                <?php if ($check["detail"]): ?><span class="journey-check-detail"><?php echo esc($check["detail"]); ?></span><?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$check["passed"] && $check["url"]): ?>
                            <?php
                            $localTargets = [
                                "manager" => "#journey-manager-select",
                                "task_release" => "#journey-task-readiness",
                                "material_critical" => "#journey-material-flow"
                            ];
                            $projectTabTargets = [
                                "milestones" => "#project-etapas-section",
                                "milestone_weight" => "#project-etapas-section",
                                "tasks" => "#project-tasks-section",
                                "task_plan" => "#project-tasks-section",
                                "task_dates" => "#project-tasks-section",
                                "task_weights" => "#project-tasks-section",
                                "material_review" => "#project-materials-section",
                                "material_allocation" => "#project-materials-section",
                                "material_requests" => "#project-materials-section",
                                "schedule" => "#project-execution_schedule-section",
                                "activity" => "#project-teamactivities-section",
                                "monitoring" => "#project-tasks-section",
                                "tasks_finished" => "#project-tasks-section"
                            ];
                            $localTarget = $localTargets[$check["key"]] ?? "";
                            $projectTabTarget = $projectTabTargets[$check["key"]] ?? "";
                            ?>
                            <button type="button"
                                    class="journey-resolve-action journey-open-resolver"
                                    data-check-key="<?php echo esc($check["key"]); ?>"
                                    data-check-label="<?php echo esc($check["label"]); ?>"
                                    data-check-detail="<?php echo esc($check["detail"] ?? ""); ?>"
                                    data-check-help="<?php echo esc($helpText); ?>"
                                    data-check-url="<?php echo esc($check["url"]); ?>"
                                    data-local-target="<?php echo esc($localTarget); ?>"
                                    data-project-tab="<?php echo esc($projectTabTarget); ?>">
                                <i data-feather="edit-3" class="icon-14"></i>
                                <span>Resolver</span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="journey-actions">
                <?php if ($currentPhase < 7): ?>
                    <button id="advance-journey" class="btn btn-primary" <?php echo $can_advance ? "" : "disabled"; ?>>
                        <i data-feather="unlock" class="icon-16"></i> Concluir fase e liberar próximas abas
                    </button>
                    <?php if ($is_admin && !$can_advance): ?>
                        <button id="override-journey" class="btn btn-default text-danger">Avançar como administrador</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<div class="journey-panel" id="journey-task-readiness">
    <div class="journey-panel-header">
        <h4>Planejamento e prontidão das tarefas</h4>
        <div class="journey-panel-hint">A liberação é individual: uma tarefa pronta pode avançar sem aguardar as demais.</div>
    </div>
    <div class="journey-table-wrap">
        <table class="journey-table">
            <thead>
                <tr>
                    <th>Tarefa</th>
                    <th>Etapa</th>
                    <th>Datas</th>
                    <th>Responsável</th>
                    <th>Peso</th>
                    <th>Materiais</th>
                    <th>Impedimento</th>
                    <th>Liberação</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$task_readiness): ?>
                <tr><td colspan="9" class="text-center text-muted p20">Nenhuma tarefa cadastrada.</td></tr>
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
                        <span class="badge bg-<?php echo $task["material_ready"] ? "success" : "warning text-dark"; ?>">
                            <?php echo $task["material_ready"] ? "Disponíveis" : "Pendentes"; ?>
                        </span>
                        <?php if ($task["material_pending"] > 0): ?><div><small><?php echo number_format($task["material_pending"], 3, ",", "."); ?> pendente</small></div><?php endif; ?>
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

<?php
$materialStatusLabels = [
    "planned" => "Planejado", "allocated" => "Associado", "requested" => "Solicitado",
    "purchased" => "Comprado", "partially_received" => "Recebido parcialmente", "received" => "Recebido",
    "partially_delivered" => "Entregue parcialmente", "delivered" => "Entregue à tarefa",
    "available" => "Disponível", "partially_used" => "Utilizado parcialmente", "used" => "Utilizado"
];
$materialMilestones = [];
$materialTasks = [];
foreach ($material_flow["rows"] as $flowRow) {
    $materialMilestones[$flowRow["milestone_id"]] = $flowRow["milestone"];
    $materialTasks[$flowRow["task_id"]] = $flowRow["task"];
}
?>
<div class="journey-panel" id="journey-material-flow">
    <div class="journey-panel-header">
        <h4>Fluxo de materiais e compras</h4>
        <div class="title-button-group">
            <a href="#" class="btn btn-default" data-project-tab="#project-materials-section">
                <i data-feather="package" class="icon-16"></i> Gerenciar materiais e requisições
            </a>
        </div>
    </div>
    <div class="journey-panel-body">
        <div class="row text-center">
            <?php
            $materialCards = [
                ["Associado", "allocated", "primary"], ["Solicitado", "requested", "info"],
                ["Comprado", "purchased", "warning"], ["Recebido", "received", "success"],
                ["Entregue", "delivered", "primary"], ["Utilizado", "used", "secondary"], ["A solicitar", "to_request", "danger"]
            ];
            foreach ($materialCards as $cardItem):
            ?>
                <div class="col-md-2 col-6 mb15">
                    <div class="p10 border rounded">
                        <div class="text-muted"><?php echo $cardItem[0]; ?></div>
                        <strong class="text-<?php echo $cardItem[2]; ?>"><?php echo number_format((float)($material_flow["totals"][$cardItem[1]] ?? 0), 3, ",", "."); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (($material_flow["totals"]["critical"] ?? 0) > 0): ?>
            <div class="alert alert-warning">
                <i data-feather="alert-triangle" class="icon-16"></i>
                <?php echo (int)$material_flow["totals"]["critical"]; ?> material(is) estão bloqueando tarefas já liberadas.
            </div>
        <?php endif; ?>
        <div class="journey-filter-row">
            <div>
                <select id="material-milestone-filter" class="form-control select2">
                    <option value="">Todas as etapas</option>
                    <?php foreach ($materialMilestones as $id => $title): ?><option value="<?php echo (int)$id; ?>"><?php echo esc($title); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <select id="material-task-filter" class="form-control select2">
                    <option value="">Todas as tarefas</option>
                    <?php foreach ($materialTasks as $id => $title): ?><option value="<?php echo (int)$id; ?>"><?php echo esc($title); ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="journey-table-wrap">
        <table class="journey-table" id="material-flow-table">
            <thead>
                <tr>
                    <th>Etapa / tarefa</th><th>Material</th><th>Associado</th><th>Solicitado</th>
                    <th>Comprado</th><th>Recebido</th><th>Entregue</th><th>Na tarefa</th><th>Utilizado</th>
                    <th>Status</th><th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$material_flow["rows"]): ?>
                <tr><td colspan="11" class="text-center text-muted p20">Nenhum material associado às tarefas.</td></tr>
            <?php endif; ?>
            <?php foreach ($material_flow["rows"] as $flowRow): ?>
                <tr data-milestone-id="<?php echo $flowRow["milestone_id"]; ?>" data-task-id="<?php echo $flowRow["task_id"]; ?>" class="<?php echo $flowRow["critical"] ? "table-warning" : ""; ?>">
                    <td>
                        <strong><?php echo esc($flowRow["milestone"]); ?></strong>
                        <div><small><?php echo esc($flowRow["task"]); ?></small></div>
                    </td>
                    <td>
                        <?php echo esc($flowRow["material"]); ?>
                        <div><small class="text-muted"><?php echo esc($flowRow["unit"]); ?></small></div>
                        <?php if ($flowRow["request_codes"]): ?><div><small class="text-muted"><?php echo esc(implode(", ", $flowRow["request_codes"])); ?></small></div><?php endif; ?>
                    </td>
                    <td><?php echo number_format($flowRow["allocated"], 3, ",", "."); ?></td>
                    <td>
                        <?php echo number_format($flowRow["requested"], 3, ",", "."); ?>
                        <?php if ($flowRow["to_request"] > 0): ?><div><small class="text-danger">Falta <?php echo number_format($flowRow["to_request"], 3, ",", "."); ?></small></div><?php endif; ?>
                    </td>
                    <td>
                        <?php echo number_format($flowRow["purchased"], 3, ",", "."); ?>
                        <?php if ($flowRow["pending_purchase"] > 0): ?><div><small class="text-warning">Cotação/OC pendente</small></div><?php endif; ?>
                    </td>
                    <td>
                        <?php echo number_format($flowRow["received"], 3, ",", "."); ?>
                        <?php if ($flowRow["pending_delivery"] > 0): ?><div><small class="text-warning">Aguardando <?php echo number_format($flowRow["pending_delivery"], 3, ",", "."); ?></small></div><?php endif; ?>
                    </td>
                    <td>
                        <?php echo number_format($flowRow["delivered"], 3, ",", "."); ?>
                        <?php if ($flowRow["remaining_delivery"] > 0): ?><div><small class="text-muted">Falta entregar <?php echo number_format($flowRow["remaining_delivery"], 3, ",", "."); ?></small></div><?php endif; ?>
                    </td>
                    <td><?php echo number_format($flowRow["available"], 3, ",", "."); ?></td>
                    <td><?php echo number_format($flowRow["used"], 3, ",", "."); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $flowRow["critical"] ? "warning text-dark" : ($flowRow["material_ready"] ? "success" : "secondary"); ?>">
                            <?php echo esc($materialStatusLabels[$flowRow["status"]] ?? $flowRow["status"]); ?>
                        </span>
                        <?php if ($flowRow["critical"]): ?><div><small class="text-danger">Bloqueia execução</small></div><?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-default btn-sm material-stock"
                                data-material-id="<?php echo $flowRow["material_id"]; ?>"
                                data-current="<?php echo $flowRow["stock_quantity"]; ?>">Saldo</button>
                        <button class="btn btn-info btn-sm material-deliver"
                                data-allocation-id="<?php echo $flowRow["allocation_id"]; ?>"
                                data-available="<?php echo $flowRow["project_available"]; ?>"
                                <?php echo $flowRow["project_available"] <= 0 || $flowRow["remaining_delivery"] <= 0 ? "disabled" : ""; ?>>Entregar</button>
                        <button class="btn btn-primary btn-sm material-use"
                                data-allocation-id="<?php echo $flowRow["allocation_id"]; ?>"
                                data-available="<?php echo $flowRow["available"]; ?>"
                                <?php echo $flowRow["available"] <= 0 || $flowRow["remaining_use"] <= 0 ? "disabled" : ""; ?>>Utilizar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<div class="modal fade" id="journey-resolver-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i data-feather="check-square" class="icon-18"></i> Corrigir pendência</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo app_lang("close"); ?>"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb20">
                    <strong id="journey-resolver-label"></strong>
                    <div id="journey-resolver-help" class="mt5"></div>
                    <div id="journey-resolver-detail" class="mt5 text-muted"></div>
                </div>

                <div id="journey-resolver-manager" class="journey-resolver-section hide">
                    <label for="journey-resolver-manager-select">Gerente responsável pelo projeto</label>
                    <?php echo form_open(get_uri("projectanalizer/journey/save_manager"), ["id" => "journey-resolver-manager-form", "class" => "general-form"]); ?>
                        <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">
                        <?php echo form_dropdown("manager_id", $managers, $journey->manager_id ?? "", "id='journey-resolver-manager-select' class='form-control select2'"); ?>
                        <div class="mt15 text-end">
                            <button class="btn btn-primary" type="submit"><i data-feather="save" class="icon-16"></i> Salvar e atualizar</button>
                        </div>
                    <?php echo form_close(); ?>
                </div>

                <div id="journey-resolver-tasks" class="journey-resolver-section hide">
                    <?php foreach ($task_readiness as $resolverTask): ?>
                        <div class="journey-resolver-row">
                            <div>
                                <strong><?php echo esc($resolverTask["title"]); ?></strong>
                                <?php if (!empty($resolverTask["missing"])): ?>
                                    <div class="text-muted"><?php echo esc(implode(", ", $resolverTask["missing"])); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php echo modal_anchor(
                                get_uri("projectanalizer/task_modal_form"),
                                "<i data-feather='edit-3' class='icon-14'></i> Editar tarefa",
                                ["class" => "btn btn-default btn-sm journey-nested-modal", "title" => "Editar tarefa", "data-post-id" => $resolverTask["id"], "data-post-project_id" => $project_id]
                            ); ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$task_readiness): ?><div class="text-muted">Nenhuma tarefa cadastrada.</div><?php endif; ?>
                </div>

                <div id="journey-resolver-generic" class="journey-resolver-section hide">
                    <p id="journey-resolver-instruction">Use o formulário indicado para concluir esta correção.</p>
                    <div class="journey-resolver-actions">
                        <a href="#" id="journey-resolver-open-form" class="btn btn-primary">
                            <i data-feather="edit-3" class="icon-16"></i> Abrir formulário de correção
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function initializeProjectJourney(attempt) {
    if (!window.jQuery) {
        if (attempt < 100) {
            window.setTimeout(function () { initializeProjectJourney(attempt + 1); }, 50);
        }
        return;
    }

    var $ = window.jQuery;
    $(function () {
    $("#project-journey .select2").each(function () {
        var select = $(this);
        if ($.fn.select2 && !select.data("select2") && !select.hasClass("select2-hidden-accessible")) {
            select.select2();
        }
    });

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

    var resolverModal = $("#journey-resolver-modal");
    var journeyCurrentPhase = <?php echo (int)$currentPhase; ?>;
    var journeyJsonFlags = <?php echo JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT; ?>;
    var journeyPhases = <?php echo json_encode($phases, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: "{}"; ?>;
    var journeyChecks = <?php echo json_encode($checks_by_phase, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: "{}"; ?>;
    var journeyHelpTexts = <?php echo json_encode($checkHelpTexts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: "{}"; ?>;
    var journeyProjectTabs = {
        milestones:"#project-etapas-section", milestone_weight:"#project-etapas-section",
        tasks:"#project-tasks-section", task_plan:"#project-tasks-section", task_dates:"#project-tasks-section",
        task_weights:"#project-tasks-section", material_review:"#project-materials-section",
        material_allocation:"#project-materials-section", material_requests:"#project-materials-section",
        schedule:"#project-execution_schedule-section", activity:"#project-teamactivities-section",
        monitoring:"#project-tasks-section", tasks_finished:"#project-tasks-section"
    };
    var journeyLocalTargets = {manager:"#journey-manager-select", task_release:"#journey-task-readiness", material_critical:"#journey-material-flow"};

    function escapeJourney(value) {
        return $("<div>").text(value == null ? "" : String(value)).html();
    }

    function renderJourneyPhaseChecklist(phase) {
        phase = parseInt(phase, 10);
        var checks = journeyChecks[phase] || journeyChecks[String(phase)] || [];
        var rows = [];

        $.each(checks, function (_, check) {
            var help = journeyHelpTexts[check.key] || "Abra o formulário indicado para corrigir esta pendência.";
            if (check.detail) help += " Situação atual: " + check.detail + ".";
            var optional = check.required ? "" : '<span class="journey-optional">Recomendado</span>';
            var detail = check.detail ? '<span class="journey-check-detail">' + escapeJourney(check.detail) + '</span>' : "";
            var resolve = "";

            if (!check.passed && check.url) {
                resolve = '<button type="button" class="journey-resolve-action journey-open-resolver"' +
                    ' data-check-key="' + escapeJourney(check.key) + '"' +
                    ' data-check-label="' + escapeJourney(check.label) + '"' +
                    ' data-check-detail="' + escapeJourney(check.detail || "") + '"' +
                    ' data-check-help="' + escapeJourney(help) + '"' +
                    ' data-check-url="' + escapeJourney(check.url) + '"' +
                    ' data-local-target="' + escapeJourney(journeyLocalTargets[check.key] || "") + '"' +
                    ' data-project-tab="' + escapeJourney(journeyProjectTabs[check.key] || "") + '">' +
                    '<i data-feather="edit-3" class="icon-14"></i><span>Resolver</span></button>';
            }

            rows.push('<div class="journey-check ' + (check.passed ? "is-done" : "") + '" title="' + escapeJourney(help) + '">' +
                '<div class="journey-check-main"><span class="journey-check-icon">' + (check.passed ? "✓" : "!") + '</span>' +
                '<div><span class="journey-check-label">' + escapeJourney(check.label) +
                '<span class="journey-check-help" aria-hidden="true">?</span>' + optional + '</span>' + detail + '</div></div>' +
                resolve + '</div>');
        });

        $("#journey-checklist-title").text(phase === journeyCurrentPhase ? "Checklist da fase atual" : "Checklist da fase " + phase + ": " + (journeyPhases[phase] || ""));
        $("#journey-phase-checklist").html(rows.join("") || '<div class="p15 text-muted">Nenhum item configurado nesta fase.</div>');
        $(".journey-phase").removeClass("is-selected").filter("[data-journey-phase='" + phase + "']").addClass("is-selected");
        if (window.feather) feather.replace();
    }

    $(document).on("click.projectJourney", ".journey-phase", function () {
        renderJourneyPhaseChecklist($(this).data("journey-phase"));
    });

    $(".journey-phase[data-journey-phase='" + journeyCurrentPhase + "']").addClass("is-selected");

    function refreshJourneyAfterSave() {
        resolverModal.modal("hide");
        window.setTimeout(function () { window.location.reload(); }, 250);
    }

    $("#journey-resolver-manager-form").appForm({
        isModal: false,
        onSuccess: function (result) {
            appAlert.success(result.message, {duration: 10000});
            refreshJourneyAfterSave();
        }
    });

    $(document).on("click.projectJourney", ".journey-open-resolver", function () {
        var button = $(this);
        var key = String(button.data("check-key") || "");
        var taskKeys = ["task_release", "task_plan", "task_dates", "task_weights", "monitoring", "tasks_finished"];

        $("#journey-resolver-label").text(button.data("check-label") || "");
        $("#journey-resolver-help").text(button.data("check-help") || "");
        $("#journey-resolver-detail").text(button.data("check-detail") ? "Situação atual: " + button.data("check-detail") : "");
        $(".journey-resolver-section").addClass("hide");

        if (key === "manager") {
            $("#journey-resolver-manager").removeClass("hide");
            window.setTimeout(function () {
                var managerSelect = $("#journey-resolver-manager-select");
                if ($.fn.select2 && !managerSelect.data("select2") && !managerSelect.hasClass("select2-hidden-accessible")) {
                    managerSelect.select2({dropdownParent: resolverModal});
                }
            }, 100);
        } else if (taskKeys.indexOf(key) !== -1) {
            $("#journey-resolver-tasks").removeClass("hide");
        } else {
            $("#journey-resolver-generic").removeClass("hide");
            $("#journey-resolver-open-form")
                .data("check-key", key)
                .data("check-url", button.data("check-url") || "")
                .data("project-tab", button.data("project-tab") || "")
                .data("local-target", button.data("local-target") || "");
        }

        resolverModal.modal("show");
        if (window.feather) feather.replace();
    });

    $("#journey-resolver-open-form").on("click", function (event) {
        event.preventDefault();
        var action = $(this);
        var key = String(action.data("check-key") || "");
        var projectTab = String(action.data("project-tab") || "");
        var localTarget = String(action.data("local-target") || "");
        var modalUrl = "";
        var modalData = {};

        if (key === "start_date" || key === "deadline" || key === "close_project") {
            modalUrl = "<?php echo get_uri('projects/modal_form'); ?>";
            modalData = {id: <?php echo (int)$project_id; ?>};
        } else if (key === "members") {
            modalUrl = "<?php echo get_uri('projects/project_member_modal_form'); ?>";
            modalData = {project_id: <?php echo (int)$project_id; ?>};
        } else if (key === "milestones") {
            modalUrl = "<?php echo get_uri('projectanalizer/etapa_modal_form'); ?>";
            modalData = {project_id: <?php echo (int)$project_id; ?>};
        } else if (key === "schedule") {
            modalUrl = "<?php echo get_uri('projectanalizer/execution_schedule_modal_form'); ?>";
            modalData = {project_id: <?php echo (int)$project_id; ?>};
        } else if (key === "activity") {
            modalUrl = "<?php echo get_uri('projectanalizer/timelog_modal_form'); ?>";
            modalData = {project_id: <?php echo (int)$project_id; ?>};
        }

        resolverModal.modal("hide");

        if (modalUrl) {
            var opener = $("<a href='#' data-act='ajax-modal'></a>")
                .attr("data-action-url", modalUrl)
                .attr("data-title", $("#journey-resolver-label").text());
            $.each(modalData, function (name, value) {
                opener.attr("data-post-" + name, value);
            });
            $("body").append(opener);
            window.setTimeout(function () {
                opener.trigger("click");
                window.setTimeout(function () { opener.remove(); }, 500);
            }, 250);
            return;
        }

        if (projectTab) {
            window.setTimeout(function () {
                $("#project-tabs a[data-bs-target='" + projectTab + "']").first().trigger("click");
            }, 250);
            return;
        }

        if (localTarget) {
            window.setTimeout(function () {
                var target = $(localTarget);
                target.addClass("journey-highlight");
                target[0] && target[0].scrollIntoView({behavior: "smooth", block: "center"});
            }, 250);
            return;
        }

        var url = String(action.data("check-url") || "");
        if (url) window.location.href = url;
    });

    $(document).ajaxSuccess(function (event, xhr, settings) {
        var url = String(settings.url || "");
        if (/projects\/(save|add_project_member)|projectanalizer\/(save_task|save_etapa|save_execution_schedule|save_timelog)/.test(url)) {
            window.setTimeout(function () { window.location.reload(); }, 350);
        }
    });

    $(document).on("click.projectJourney", "[data-project-tab]", function (event) {
        event.preventDefault();
        var tabTarget = String($(this).data("project-tab") || "");
        var projectTab = $("#project-tabs a[data-bs-target='" + tabTarget + "']").first();

        if (!projectTab.length) {
            appAlert.warning("Esta aba ainda não está liberada na fase atual da jornada.");
            return;
        }

        projectTab.trigger("click");
    });

    $(document).on("click.projectJourney", ".journey-resolve-action[href^='#']:not([data-project-tab])", function (event) {
        event.preventDefault();
        var target = $($(this).attr("href"));
        if (!target.length) return;

        target.addClass("journey-highlight");
        window.setTimeout(function () { target.removeClass("journey-highlight"); }, 1900);
        if (target.is("select, input, textarea, button")) {
            window.setTimeout(function () { target.trigger("focus"); }, 50);
        }
    });

    $("#advance-journey").on("click", function () { advanceJourney(false, ""); });
    $("#override-journey").on("click", function () {
        var reason = prompt("Informe o motivo para avançar sem concluir os itens obrigatórios:");
        if (reason) advanceJourney(true, reason);
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

    function materialAction(url, data) {
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
                appAlert.error("Não foi possível atualizar o fluxo de materiais.");
            }
        });
    }

    $("#material-milestone-filter, #material-task-filter").on("change", function () {
        var milestoneId = $("#material-milestone-filter").val();
        var taskId = $("#material-task-filter").val();
        $("#material-flow-table tbody tr[data-task-id]").each(function () {
            var show = (!milestoneId || String($(this).data("milestone-id")) === String(milestoneId))
                && (!taskId || String($(this).data("task-id")) === String(taskId));
            $(this).toggle(show);
        });
    });

    $(".material-stock").on("click", function () {
        var quantity = prompt("Informe a quantidade disponível no estoque do projeto:", $(this).data("current"));
        if (quantity !== null && quantity !== "") {
            materialAction("<?php echo get_uri('projectanalizer/journey/material_stock'); ?>", {material_id: $(this).data("material-id"), quantity: quantity});
        }
    });

    $(".material-deliver").on("click", function () {
        var quantity = prompt("Quantidade entregue à tarefa (disponível no projeto: " + $(this).data("available") + "):");
        if (!quantity) return;
        var notes = prompt("Observação da entrega (opcional):") || "";
        materialAction("<?php echo get_uri('projectanalizer/journey/material_delivery'); ?>", {allocation_id: $(this).data("allocation-id"), quantity: quantity, notes: notes});
    });

    $(".material-use").on("click", function () {
        var quantity = prompt("Quantidade utilizada (disponível: " + $(this).data("available") + "):");
        if (!quantity) return;
        var notes = prompt("Observação da utilização (opcional):") || "";
        materialAction("<?php echo get_uri('projectanalizer/journey/material_usage'); ?>", {allocation_id: $(this).data("allocation-id"), quantity: quantity, notes: notes});
    });
    });
})(0);
</script>
