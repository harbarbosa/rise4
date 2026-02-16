<?php
// Avoid loading a non-existent custom.css to prevent 404s.
?>

<div class="clearfix">
    <div class="page-title clearfix">
        <h1><?php echo app_lang("evolucao_ff"); ?></h1>
        <div class="title-button-group">
            <?php
            echo modal_anchor(get_uri("projectanalizer/evolucao/reschedule_modal_form/" . $project_id), "<i data-feather='calendar' class='icon-16'></i> " . app_lang("define_real_start"), array("class" => "btn btn-default", "title" => app_lang("define_real_start")));
            echo anchor(get_uri("projectanalizer/evolucao/export/" . $project_id), "<i data-feather='printer' class='icon-16'></i> " . app_lang("export_pdf"), array("class" => "btn btn-default", "target" => "_blank"));
            echo anchor(get_uri("projectanalizer/evolucao/export_costs_csv/" . $project_id), "<i data-feather='download' class='icon-16'></i> " . app_lang("export_csv"), array("class" => "btn btn-default"));
            ?>
        </div>
    </div>
</div>

<?php
$summary_default = array(
    "project_actual_percent" => 0,
    "project_planned_percent" => 0,
    "project_deviation_pp" => 0,
    "task_flags" => array("overdue" => 0, "blocked" => 0, "critical" => 0),
    "milestones" => array()
);
$summary = isset($summary) && is_array($summary) ? array_merge($summary_default, $summary) : $summary_default;

$cashflow_cards_default = array(
    "success" => false,
    "expenses_planned_today" => 0,
    "expenses_realized_today" => 0,
    "revenue_planned_today" => 0,
    "revenue_realized_today" => 0,
    "net_today" => 0,
    "net_cumulative" => 0,
    "alerts" => array()
);
$cashflow_cards = isset($cashflow_cards) && is_array($cashflow_cards) ? array_merge($cashflow_cards_default, $cashflow_cards) : $cashflow_cards_default;
$cashflow_summary_default = array(
    "success" => false,
    "labels" => array(),
    "expenses" => array(
        "planned_by_month" => array(),
        "realized_by_month" => array(),
        "planned_cum" => array(),
        "realized_cum" => array()
    ),
    "revenue" => array(
        "planned_by_month" => array(),
        "realized_by_month" => array(),
        "planned_cum" => array(),
        "realized_cum" => array()
    ),
    "net" => array(
        "planned_by_month" => array(),
        "realized_by_month" => array(),
        "cumulative" => array()
    ),
    "alerts" => array()
);
$cashflow_summary = isset($cashflow_summary) && is_array($cashflow_summary) ? array_merge($cashflow_summary_default, $cashflow_summary) : $cashflow_summary_default;
?>

<?php if (!empty($latest_reschedule) && !empty($latest_reschedule->new_start)) { ?>
    <div class="alert alert-info mb15">
        <?php echo app_lang("rescheduled_on") . ": " . format_to_date($latest_reschedule->new_start, false); ?>
        <?php if (!empty($latest_reschedule->mode)) { ?>
            (<?php echo $latest_reschedule->mode; ?>)
        <?php } ?>
    </div>
<?php } ?>

<?php if (!empty($baseline_range) && !empty($baseline_range["has_baseline"])) { ?>
    <div class="alert alert-secondary mb15">
        <?php
        $baseline_start = $baseline_range["start"] ? format_to_date($baseline_range["start"], false) : "-";
        $baseline_end = $baseline_range["end"] ? format_to_date($baseline_range["end"], false) : "-";
        echo app_lang("baseline_current") . ": " . $baseline_start . " - " . $baseline_end;
        ?>
    </div>
<?php } ?>

<div class="card mb15">
    <div class="card-body">
        <?php echo form_open(get_uri("projectanalizer/evolucao/generate_baseline/" . $project_id), array("id" => "baseline-form", "class" => "general-form", "role" => "form")); ?>
        <div class="row">
            <div class="col-md-4">
                <label for="baseline_date" class="mb5"><?php echo app_lang("baseline_date"); ?></label>
                <?php
                echo form_input(array(
                    "id" => "baseline_date",
                    "name" => "baseline_date",
                    "value" => date("Y-m-d"),
                    "class" => "form-control",
                    "autocomplete" => "off"
                ));
                ?>
            </div>
            <div class="col-md-4">
                <label class="mb5">&nbsp;</label>
                <div class="mt5">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="use_today" value="1"> <?php echo app_lang("use_today"); ?>
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="overwrite_labor" value="1"> <?php echo app_lang("baseline_overwrite_labor"); ?>
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="recalculate_baseline" value="1"> <?php echo app_lang("baseline_recalculate"); ?>
                    </label>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <label class="mb5">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="calendar" class="icon-16"></i> <?php echo app_lang("generate_baseline"); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("physical_progress"); ?></div>
                <h3 class="mb0"><?php echo round($summary['project_actual_percent'], 2); ?>%</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("planned_today"); ?></div>
                <h3 class="mb0"><?php echo round($summary['project_planned_percent'], 2); ?>%</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("deviation_pp"); ?></div>
                <h3 class="mb0"><?php echo round($summary['project_deviation_pp'], 2); ?> p.p.</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("overdue"); ?></div>
                <h3 class="mb0"><?php echo $summary['task_flags']['overdue']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("blocked"); ?></div>
                <h3 class="mb0"><?php echo $summary['task_flags']['blocked']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("planned_financial_today"); ?></div>
                <h3 class="mb0"><?php echo to_currency($summary['financial_planned_today']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("realized_financial_today"); ?></div>
                <h3 class="mb0"><?php echo to_currency($summary['financial_realized_today']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("financial_deviation"); ?></div>
                <h3 class="mb0"><?php echo to_currency($summary['financial_deviation']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("expenses_planned_today"); ?></div>
                <h3 class="mb0"><?php echo to_currency($cashflow_cards['expenses_planned_today']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("expenses_realized_today"); ?></div>
                <h3 class="mb0"><?php echo to_currency($cashflow_cards['expenses_realized_today']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("revenue_planned_today"); ?></div>
                <h3 class="mb0"><?php echo to_currency($cashflow_cards['revenue_planned_today']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("revenue_realized_today"); ?></div>
                <h3 class="mb0"><?php echo to_currency($cashflow_cards['revenue_realized_today']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("net_cumulative"); ?></div>
                <h3 class="mb0"><?php echo to_currency($cashflow_cards['net_cumulative']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("spi"); ?></div>
                <h3 class="mb0"><?php echo $summary['spi']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb15">
            <div class="card-body">
                <div class="text-off"><?php echo app_lang("cpi"); ?></div>
                <h3 class="mb0"><?php echo $summary['cpi']; ?></h3>
            </div>
        </div>
    </div>
    </div>
</div>

<div class="card mb15">
    <ul id="project-evolution-tabs" class="nav nav-tabs bg-white title" role="tablist">
        <li class="nav-item title-tab">
            <h4 class="pl15 pt10 pr15"><?php echo app_lang("evolucao_ff"); ?></h4>
        </li>
        <li class="nav-item">
            <a class="nav-link active" role="presentation" data-bs-toggle="tab" href="#evolution-summary">
                <?php echo app_lang("summary"); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" role="presentation" data-bs-toggle="tab" href="#evolution-revenue">
                <?php echo app_lang("revenues_expenses_section"); ?>
            </a>
        </li>
    </ul>

    <div class="tab-content p15">
        <div role="tabpanel" class="tab-pane fade show active" id="evolution-summary">

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("schedule"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Resumo por etapa: planejado, real e peso das tarefas.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("milestone"); ?></th>
                    <th><?php echo app_lang("planned"); ?></th>
                    <th><?php echo app_lang("progress"); ?></th>
                    <th><?php echo app_lang("planned_financial"); ?></th>
                    <th><?php echo app_lang("weight"); ?></th>
                    <th><?php echo app_lang("tasks"); ?></th>
                    <th><?php echo app_lang("completed"); ?></th>
                </tr>
                </thead>
                <tbody>
                    <?php if (!empty($summary['milestones'])) { ?>
                        <?php foreach ($summary['milestones'] as $milestone) { ?>
                            <tr>
                                <td><?php echo $milestone['title']; ?></td>
                                <td><?php echo $milestone['planned_percent']; ?>%</td>
                                <td><?php echo $milestone['actual_percent']; ?>%</td>
                                <td><?php echo to_currency($milestone['planned_financial']); ?></td>
                                <td><?php echo number_format($milestone['total_weight'], 2); ?></td>
                                <td><?php echo $milestone['tasks_count']; ?></td>
                                <td><?php echo $milestone['completed_count']; ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
        </div>

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("top_delays"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Tarefas vencidas e não concluídas.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <?php if (!empty($overdue_tasks)) { ?>
            <ul class="list-unstyled mb0">
                <?php foreach (array_slice($overdue_tasks, 0, 10) as $task) { ?>
                    <li class="mb5">
                        <strong><?php echo $task->title; ?></strong>
                        <span class="text-muted">(<?php echo format_to_date($task->deadline, false); ?>)</span>
                    </li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            <div class="text-muted"><?php echo app_lang("no_data"); ?></div>
        <?php } ?>
    </div>
</div>

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("blocked_tasks"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Tarefas marcadas como bloqueadas.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <?php if (!empty($blocked_tasks)) { ?>
            <ul class="list-unstyled mb0">
                <?php foreach (array_slice($blocked_tasks, 0, 10) as $task) { ?>
                    <li class="mb5">
                        <strong><?php echo $task->title; ?></strong>
                        <span class="text-muted">(<?php echo app_lang("blocked_by"); ?>: <?php echo $task->blocked_by; ?>)</span>
                    </li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            <div class="text-muted"><?php echo app_lang("no_data"); ?></div>
        <?php } ?>
    </div>
</div>

<div class="card mb15">
    <div class="card-body">
        <form id="snapshot-filter-form" method="GET" action="<?php echo_uri("projectanalizer/evolucao/" . $project_id); ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="mb5">
                    <?php echo app_lang("snapshot_period"); ?>
                    <span class="help" data-bs-toggle="tooltip" title="Período usado para carregar snapshots e gráficos.">
                        <i data-feather="help-circle" class="icon-16"></i>
                    </span>
                </label>
                <?php
                $snapshot_period = isset($snapshot_period) ? $snapshot_period : 90;
                echo form_dropdown("snapshot_period", array(
                    30 => app_lang("snapshots_30_days"),
                    90 => app_lang("snapshots_90_days"),
                    180 => app_lang("snapshots_180_days")
                ), $snapshot_period, "class='form-control'");
                ?>
            </div>
            <div class="col-md-4">
                <button class="btn btn-default" type="submit"><?php echo app_lang("filter"); ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("financial_curve"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Curva S financeira (planejado x realizado).">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <canvas id="financial-chart" style="width: 100%; height: 260px;"></canvas>
    </div>
</div>

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("physical_curve"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Curva S física (planejado x real).">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <canvas id="physical-chart" style="width: 100%; height: 260px;"></canvas>
    </div>
</div>
<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("cashflow_section"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Fluxo de caixa mensal (receitas - despesas).">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <?php if (!empty($cashflow_cards["alerts"])) { ?>
            <div class="alert alert-warning">
                <?php echo implode(" / ", $cashflow_cards["alerts"]); ?>
            </div>
        <?php } ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("month"); ?></th>
                    <th><?php echo app_lang("planned"); ?></th>
                    <th><?php echo app_lang("realized"); ?></th>
                    <th><?php echo app_lang("cumulative"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($cashflow_summary["labels"])) { ?>
                    <?php foreach ($cashflow_summary["labels"] as $index => $label) { ?>
                        <?php
                        $planned_month = get_array_value($cashflow_summary["net"]["planned_by_month"], $index, 0);
                        $realized_month = get_array_value($cashflow_summary["net"]["realized_by_month"], $index, 0);
                        $cum = get_array_value($cashflow_summary["net"]["cumulative"], $index, 0);
                        ?>
                        <tr>
                            <td><?php echo $label; ?></td>
                            <td><?php echo to_currency($planned_month); ?></td>
                            <td><?php echo to_currency($realized_month); ?></td>
                            <td><?php echo to_currency($cum); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


        </div>
<div role="tabpanel" class="tab-pane fade" id="evolution-revenue">

<div class="card mb15">
    <div class="card-header clearfix">
        <h4 class="mb0 pull-left">
            <?php echo app_lang("expenses_section"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Custos planejados por tarefa e tipo.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
        <div class="pull-right">
            <?php
            echo modal_anchor(get_uri("projectanalizer/evolucao/cost_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_cost"), array("class" => "btn btn-default", "title" => app_lang("add_cost")));
            ?>
            
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("milestone"); ?></th>
                    <th><?php echo app_lang("tasks"); ?></th>
                    <th><?php echo app_lang("cost_type"); ?></th>
                    <th><?php echo app_lang("planned_value"); ?></th>
                    <th><?php echo app_lang("distribution_type"); ?></th>
                    <th class="text-center"><?php echo app_lang("actions"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $task_title_map = array();
                $task_milestone_map = array();
                $milestone_title_map = array();
                if (!empty($summary["milestones"])) {
                    foreach ($summary["milestones"] as $milestone) {
                        $milestone_title_map[$milestone["milestone_id"]] = $milestone["title"];
                    }
                }
                if (!empty($tasks)) {
                    foreach ($tasks as $task) {
                        $milestone_title = get_array_value($milestone_title_map, $task->milestone_id, "");
                        $task_milestone_map[$task->id] = $milestone_title ? $milestone_title : "-";
                        $task_title_map[$task->id] = $task->title;
                    }
                }
                ?>
                <?php if (!empty($task_costs)) { ?>
                    <?php foreach ($task_costs as $cost) { ?>
                        <tr>
                            <td><?php echo get_array_value($task_milestone_map, $cost->task_id, "-"); ?></td>
                            <td><?php echo get_array_value($task_title_map, $cost->task_id, ""); ?></td>
                            <td><?php echo app_lang("cost_" . $cost->cost_type); ?></td>
                            <td><?php echo to_currency($cost->planned_value); ?></td>
                            <td>
                                <?php
                                $metric = isset($metrics_map[$cost->task_id]) ? $metrics_map[$cost->task_id] : null;
                                echo $metric && $metric->distribution_type ? app_lang("distribution_" . $metric->distribution_type) : app_lang("distribution_linear");
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                                    "title" => app_lang("delete"),
                                    "class" => "delete",
                                    "data-id" => $cost->id,
                                    "data-action-url" => get_uri("projectanalizer/evolucao/delete_task_cost/" . $project_id),
                                    "data-action" => "delete-confirmation"
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("expenses_monthly_schedule"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Cronograma mensal de desembolso (planejado x realizado).">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("month"); ?></th>
                    <th><?php echo app_lang("planned"); ?></th>
                    <th><?php echo app_lang("realized"); ?></th>
                    <th><?php echo app_lang("delta"); ?></th>
                    <th><?php echo app_lang("cumulative"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($cashflow_summary["labels"])) { ?>
                    <?php foreach ($cashflow_summary["labels"] as $index => $label) { ?>
                        <?php
                        $planned_month = get_array_value($cashflow_summary["expenses"]["planned_by_month"], $index, 0);
                        $realized_month = get_array_value($cashflow_summary["expenses"]["realized_by_month"], $index, 0);
                        $delta = $planned_month - $realized_month;
                        $cum = get_array_value($cashflow_summary["expenses"]["realized_cum"], $index, 0);
                        ?>
                        <tr>
                            <td><?php echo $label; ?></td>
                            <td><?php echo to_currency($planned_month); ?></td>
                            <td><?php echo to_currency($realized_month); ?></td>
                            <td><?php echo to_currency($delta); ?></td>
                            <td><?php echo to_currency($cum); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card mb15">
    <div class="card-header clearfix">
        <h4 class="mb0 pull-left">
            <?php echo app_lang("realized_costs"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Lançamentos de custos realizados no projeto.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
        <div class="pull-right">
            <?php
            echo modal_anchor(get_uri("projectanalizer/evolucao/realized_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_realized"), array("class" => "btn btn-default", "title" => app_lang("add_realized")));
            ?>
        </div>
    </div>
    <div class="card-body">
        <?php
        $filter_cost_type = isset($filters["cost_type"]) ? $filters["cost_type"] : "";
        $filter_date_from = isset($filters["date_from"]) ? $filters["date_from"] : "";
        $filter_date_to = isset($filters["date_to"]) ? $filters["date_to"] : "";
        ?>
        <form id="realized-filter-form" class="mb15" method="GET" action="<?php echo_uri("projectanalizer/evolucao/" . $project_id); ?>">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="date_from" class="form-control" placeholder="<?php echo app_lang("date_from"); ?>" value="<?php echo $filter_date_from; ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="date_to" class="form-control" placeholder="<?php echo app_lang("date_to"); ?>" value="<?php echo $filter_date_to; ?>">
                </div>
                <div class="col-md-3">
                    <?php
                    echo form_dropdown("cost_type", array(
                        "" => "-",
                        "material" => app_lang("cost_material"),
                        "mao_obra" => app_lang("cost_labor"),
                        "servico" => app_lang("cost_service"),
                        "terceiros" => app_lang("cost_third_party"),
                        "outros" => app_lang("cost_other")
                    ), $filter_cost_type, "class='form-control'");
                    ?>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-default" type="submit"><?php echo app_lang("filter"); ?></button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                        <th><?php echo app_lang("date"); ?></th>
                        <th><?php echo app_lang("cost_type"); ?></th>
                        <th><?php echo app_lang("value"); ?></th>
                        <th><?php echo app_lang("description"); ?></th>
                        <th class="text-center"><?php echo app_lang("actions"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($realized_items)) { ?>
                    <?php foreach ($realized_items as $item) { ?>
                        <tr>
                            <td><?php echo format_to_date($item->date, false); ?></td>
                            <td><?php echo app_lang("cost_" . $item->cost_type); ?></td>
                            <td><?php echo to_currency($item->value); ?></td>
                            <td>
                                <?php
                                $is_ca_desc = isset($item->reference) && strpos((string)$item->reference, "ca:") === 0;
                                if ($is_ca_desc) {
                                    echo "<span class='badge bg-primary me-1'>CA</span>";
                                }
                                echo $item->description;
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                                    "title" => app_lang("delete"),
                                    "class" => "delete",
                                    "data-id" => $item->id,
                                    "data-action-url" => get_uri("projectanalizer/evolucao/delete_realized/" . $project_id),
                                    "data-action" => "delete-confirmation"
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card mb15">
    <div class="card-header clearfix">
        <h4 class="mb0 pull-left">
            <?php echo app_lang("revenues_section"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Receitas planejadas e realizadas do projeto.">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
        <div class="pull-right">
            <?php
            echo modal_anchor(get_uri("projectanalizer/evolucao/revenue_planned_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_planned_revenue"), array("class" => "btn btn-default", "title" => app_lang("add_planned_revenue")));
            echo modal_anchor(get_uri("projectanalizer/evolucao/revenue_realized_modal_form/" . $project_id), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang("add_realized_revenue"), array("class" => "btn btn-default", "title" => app_lang("add_realized_revenue")));
            ?>
        </div>
    </div>
    <div class="card-body">
        <h5 class="mt0"><?php echo app_lang("planned_revenues"); ?></h5>
        <div class="table-responsive mb20">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("revenue_title"); ?></th>
                    <th><?php echo app_lang("planned_date"); ?></th>
                    <th><?php echo app_lang("planned_value"); ?></th>
                    <th><?php echo app_lang("percent_of_contract"); ?></th>
                    <th><?php echo app_lang("notes"); ?></th>
                    <th class="text-center"><?php echo app_lang("actions"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($revenue_planned_items)) { ?>
                    <?php foreach ($revenue_planned_items as $item) { ?>
                        <tr>
                            <td><?php echo $item->title; ?></td>
                            <td><?php echo format_to_date($item->planned_date, false); ?></td>
                            <td><?php echo to_currency($item->planned_value); ?></td>
                            <td><?php echo $item->percent_of_contract ? number_format($item->percent_of_contract, 2) . "%" : "-"; ?></td>
                            <td><?php echo $item->notes; ?></td>
                            <td class="text-center">
                                <?php
                                echo modal_anchor(get_uri("projectanalizer/evolucao/revenue_planned_modal_form/" . $project_id), "<i data-feather='edit' class='icon-16'></i>", array(
                                    "title" => app_lang("edit"),
                                    "data-post-id" => $item->id
                                ));
                                echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                                    "title" => app_lang("delete"),
                                    "class" => "delete",
                                    "data-id" => $item->id,
                                    "data-action-url" => get_uri("projectanalizer/revenue/delete_planned"),
                                    "data-action" => "delete-confirmation"
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <h5 class="mt0"><?php echo app_lang("realized_revenues"); ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("realized_date"); ?></th>
                    <th><?php echo app_lang("realized_value"); ?></th>
                    <th><?php echo app_lang("planned_revenue"); ?></th>
                    <th><?php echo app_lang("document_ref"); ?></th>
                    <th><?php echo app_lang("notes"); ?></th>
                    <th class="text-center"><?php echo app_lang("actions"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($revenue_realized_items)) { ?>
                    <?php foreach ($revenue_realized_items as $item) { ?>
                        <tr>
                            <td><?php echo format_to_date($item->realized_date, false); ?></td>
                            <td><?php echo to_currency($item->realized_value); ?></td>
                            <td>
                                <?php
                                $planned_title = "-";
                                if (!empty($revenue_planned_items)) {
                                    foreach ($revenue_planned_items as $planned_item) {
                                        if ((int)$planned_item->id === (int)$item->planned_id) {
                                            $planned_title = $planned_item->title;
                                            break;
                                        }
                                    }
                                }
                                echo $planned_title;
                                ?>
                            </td>
                            <td><?php echo $item->document_ref; ?></td>
                            <td>
                                <?php
                                $is_ca_notes = isset($item->document_ref) && strpos((string)$item->document_ref, "ca:") === 0;
                                if ($is_ca_notes) {
                                    echo "<span class='badge bg-primary me-1'>CA</span>";
                                }
                                echo $item->notes;
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                echo modal_anchor(get_uri("projectanalizer/evolucao/revenue_realized_modal_form/" . $project_id), "<i data-feather='edit' class='icon-16'></i>", array(
                                    "title" => app_lang("edit"),
                                    "data-post-id" => $item->id
                                ));
                                echo js_anchor("<i data-feather='trash-2' class='icon-16'></i>", array(
                                    "title" => app_lang("delete"),
                                    "class" => "delete",
                                    "data-id" => $item->id,
                                    "data-action-url" => get_uri("projectanalizer/revenue/delete_realized"),
                                    "data-action" => "delete-confirmation"
                                ));
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb15">
    <div class="card-header">
        <h4 class="mb0">
            <?php echo app_lang("revenue_monthly_schedule"); ?>
            <span class="help" data-bs-toggle="tooltip" title="Cronograma mensal de faturamento (planejado x realizado).">
                <i data-feather="help-circle" class="icon-16"></i>
            </span>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th><?php echo app_lang("month"); ?></th>
                    <th><?php echo app_lang("planned"); ?></th>
                    <th><?php echo app_lang("realized"); ?></th>
                    <th><?php echo app_lang("delta"); ?></th>
                    <th><?php echo app_lang("cumulative"); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($cashflow_summary["labels"])) { ?>
                    <?php foreach ($cashflow_summary["labels"] as $index => $label) { ?>
                        <?php
                        $planned_month = get_array_value($cashflow_summary["revenue"]["planned_by_month"], $index, 0);
                        $realized_month = get_array_value($cashflow_summary["revenue"]["realized_by_month"], $index, 0);
                        $delta = $planned_month - $realized_month;
                        $cum = get_array_value($cashflow_summary["revenue"]["realized_cum"], $index, 0);
                        ?>
                        <tr>
                            <td><?php echo $label; ?></td>
                            <td><?php echo to_currency($planned_month); ?></td>
                            <td><?php echo to_currency($realized_month); ?></td>
                            <td><?php echo to_currency($delta); ?></td>
                            <td><?php echo to_currency($cum); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted"><?php echo app_lang("no_data"); ?></td>
                    </tr>
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
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
        setDatePicker("#baseline_date");
        $("#baseline-form").appForm({
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }
                location.reload();
            }
        });

        setDatePicker("input[name='date_from']");
        setDatePicker("input[name='date_to']");



        $(document).on("click", "a.delete[data-action='delete-confirmation']", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var url = $btn.attr("data-action-url");
            var id = $btn.attr("data-id");
            if (!url) {
                return;
            }
            if (!confirm("<?php echo app_lang('delete_confirmation_message'); ?>")) {
                return;
            }
            $.ajax({
                url: url,
                type: "POST",
                dataType: "json",
                data: {id: id},
                success: function (result) {
                    if (result && result.success) {
                        location.reload();
                    } else {
                        appAlert.error(result.message || "<?php echo app_lang('error_occurred'); ?>");
                    }
                }
            });
        });

        if (window.Chart) {
            var financialLabels = <?php echo json_encode($summary["financial_labels"]); ?>;
            var plannedSeries = <?php echo json_encode($summary["financial_planned_series"]); ?>;
            var realizedSeries = <?php echo json_encode($summary["financial_realized_series"]); ?>;
            var physicalPlanned = <?php echo json_encode($summary["physical_planned_series"]); ?>;
            var physicalActual = <?php echo json_encode($summary["physical_actual_series"]); ?>;

            new Chart($("#financial-chart"), {
                type: "line",
                data: {
                    labels: financialLabels,
                    datasets: [
                        {
                            label: "<?php echo app_lang("planned"); ?>",
                            data: plannedSeries,
                            borderColor: "#1f77b4",
                            fill: false
                        },
                        {
                            label: "<?php echo app_lang("realized"); ?>",
                            data: realizedSeries,
                            borderColor: "#2ca02c",
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    legend: {display: true},
                    scales: {
                        yAxes: [{ticks: {beginAtZero: true}}]
                    }
                }
            });

            new Chart($("#physical-chart"), {
                type: "line",
                data: {
                    labels: financialLabels,
                    datasets: [
                        {
                            label: "<?php echo app_lang("planned"); ?>",
                            data: physicalPlanned,
                            borderColor: "#ff7f0e",
                            fill: false
                        },
                        {
                            label: "<?php echo app_lang("physical_progress"); ?>",
                            data: physicalActual,
                            borderColor: "#9467bd",
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    legend: {display: true},
                    scales: {
                        yAxes: [{ticks: {beginAtZero: true, max: 100}}]
                    }
                }
            });
        }

        $("#snapshot-filter-form").on("submit", function (e) {
            e.preventDefault();
            var url = $(this).attr("action");
            var query = $(this).serialize();
            var target = $("#project-evolucao_ff-section");
            if (target.length) {
                appLoader.show();
                $.ajax({
                    url: url + (query ? ("?" + query) : ""),
                    type: "GET",
                    success: function (result) {
                        target.html(result);
                        if (window.feather) {
                            feather.replace();
                        }
                    },
                    complete: function () {
                        appLoader.hide();
                    }
                });
            } else {
                window.location = url + (query ? ("?" + query) : "");
            }
        });

        $("#realized-filter-form").on("submit", function (e) {
            e.preventDefault();
            var url = $(this).attr("action");
            var query = $(this).serialize();
            var target = $("#project-evolucao_ff-section");
            if (target.length) {
                appLoader.show();
                $.ajax({
                    url: url + (query ? ("?" + query) : ""),
                    type: "GET",
                    success: function (result) {
                        target.html(result);
                        if (window.feather) {
                            feather.replace();
                        }
                    },
                    complete: function () {
                        appLoader.hide();
                    }
                });
            } else {
                window.location = url + (query ? ("?" + query) : "");
            }
        });
    });
</script>
