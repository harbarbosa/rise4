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
#project-journey .journey-manager-row select{display:block;width:100%;height:38px;padding:6px 10px;border:1px solid #ced4da;border-radius:5px;background:#fff;color:#333}
#project-journey .journey-manager-row button{height:38px;min-width:42px}
#project-journey .journey-phase-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:24px}
#project-journey .journey-phase{position:relative;min-height:94px;padding:15px;border:1px solid #dde2e6;border-radius:7px;background:#f8f9fa}
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
#project-journey .journey-filter-row select{width:100%;height:38px;padding:6px 10px;border:1px solid #ced4da;border-radius:5px;background:#fff}
#project-journey .journey-resolve-action{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:32px;padding:6px 12px;border:1px solid #1c7ed6;border-radius:5px;background:#1c7ed6;color:#fff!important;font-size:12px;font-weight:600;line-height:1.2;text-decoration:none!important;white-space:nowrap;cursor:pointer;box-shadow:none}
#project-journey .journey-resolve-action:hover,#project-journey .journey-resolve-action:focus{border-color:#1864ab;background:#1864ab;color:#fff!important;text-decoration:none!important;outline:0}
#project-journey .journey-resolve-action svg{width:14px;height:14px;stroke:currentColor}
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
                            <?php echo form_dropdown("manager_id", $managers, $journey->manager_id ?? "", "id='journey-manager-select' class='form-control'"); ?>
                            <button class="btn btn-default" type="submit" title="Salvar responsável"><i data-feather="save" class="icon-16"></i></button>
                        </div>
                    <?php echo form_close(); ?>
                </div>
            </div>

            <div class="journey-phase-grid">
                <?php foreach ($phases as $number => $label):
                    $phaseClass = $number < $currentPhase ? "is-complete" : ($number == $currentPhase ? "is-current" : "is-locked");
                ?>
                    <div class="journey-phase <?php echo $phaseClass; ?>">
                        <span class="journey-phase-number"><?php echo $number; ?></span>
                        <span class="journey-phase-name"><?php echo esc($label); ?></span>
                        <span class="journey-phase-state"><?php echo $number < $currentPhase ? "Concluída" : ($number == $currentPhase ? "Em andamento" : "Bloqueada"); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <h4 class="journey-section-title">Checklist da fase atual</h4>
            <div class="journey-checklist">
                <?php foreach ($current_checks as $check): ?>
                    <div class="journey-check <?php echo $check["passed"] ? "is-done" : ""; ?>">
                        <div class="journey-check-main">
                            <span class="journey-check-icon"><?php echo $check["passed"] ? "✓" : "!"; ?></span>
                            <div>
                                <span class="journey-check-label">
                                    <?php echo esc($check["label"]); ?>
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
                            $localTarget = $localTargets[$check["key"]] ?? "";
                            ?>
                            <a class="journey-resolve-action"
                               href="<?php echo $localTarget ? $localTarget : esc($check["url"]); ?>">
                                <i data-feather="arrow-right-circle" class="icon-14"></i>
                                <span>Resolver</span>
                            </a>
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
            <a href="<?php echo get_uri("projectanalizer/project_materials/".$project_id); ?>" class="btn btn-default">
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
                <select id="material-milestone-filter" class="form-control">
                    <option value="">Todas as etapas</option>
                    <?php foreach ($materialMilestones as $id => $title): ?><option value="<?php echo (int)$id; ?>"><?php echo esc($title); ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <select id="material-task-filter" class="form-control">
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

    $(document).on("click.projectJourney", ".journey-resolve-action[href^='#']", function () {
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
