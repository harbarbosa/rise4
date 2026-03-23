<div class="clearfix default-bg">

    <div class="row">
        <?php if (isset($project_result_summary)) { ?>
            <div class="col-md-6 col-sm-12 d-flex">
                <div class="card mb15 flex-fill">
                    <div class="card-header">
                        <h4>Resultado do projeto</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $project_value = get_array_value($project_result_summary, "project_value", 0);
                        $costs_realized = get_array_value($project_result_summary, "costs_realized", 0);
                        $budget_value = get_array_value($project_result_summary, "costs_planned", 0);
                        $consumed_value = get_array_value($project_result_summary, "costs_realized", 0);
                        $result_value = get_array_value($project_result_summary, "result_value", 0);
                        $tax_predicted = get_array_value($project_result_summary, "tax_predicted", 0);
                        $tax_service_percent = get_array_value($project_result_summary, "tax_service_percent", 0);
                        $currency = isset($project_info->currency_symbol) ? $project_info->currency_symbol : "";
                        ?>
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb10">
                                <div class="text-off">Valor de venda do projeto:</div>
                                <div class="strong"><?php echo to_currency($project_value, $currency); ?></div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb10">
                                <div class="text-off"><?php echo app_lang("realized_costs"); ?>:</div>
                                <div class="text-danger"><?php echo to_currency($costs_realized, $currency); ?></div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb10">
                                <div class="text-off">Lucro:</div>
                                <div class="<?php echo $result_value >= 0 ? "text-success" : "text-danger"; ?>"><?php echo to_currency($result_value, $currency); ?></div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb10">
                                <div class="text-off">Imposto previsto (<?php echo number_format((float)$tax_service_percent, 2, ",", "."); ?>%):</div>
                                <div class="text-danger"><?php echo to_currency($tax_predicted, $currency); ?></div>
                            </div>
                        </div>
                        <div class="mt15">
                            <canvas id="project-result-chart" style="width: 100%; height: 220px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if (isset($project_result_summary)) { ?>
            <div class="col-md-6 col-sm-12 d-flex">
                <div class="card mb15 flex-fill">
                    <div class="card-header">
                        <h4>Budget de custos</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-sm-12 mb10">
                                <div class="text-off">Budget:</div>
                                <div class="strong"><?php echo to_currency($budget_value, $currency); ?></div>
                            </div>
                            <div class="col-md-6 col-sm-12 mb10">
                                <div class="text-off">Consumido:</div>
                                <div class="text-danger"><?php echo to_currency($consumed_value, $currency); ?></div>
                            </div>
                        </div>
                         <div class="mt15">
                        <canvas id="project-budget-chart" style="width: 100%; height: 220px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if (!empty($planned_cashflow) && !empty($planned_cashflow["labels"])) { ?>
            <?php $chart_currency = isset($project_info->currency_symbol) ? $project_info->currency_symbol : ""; ?>
            <div class="col-md-6 col-sm-12">
                <div class="card mb15">
                    <div class="card-header">
                        <h4>Receitas e despesas planejadas</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="planned-cashflow-chart" style="width: 100%; height: 260px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="card mb15">
                    <div class="card-header">
                        <h4>Receitas e despesas realizadas</h4>
                    </div>
                    <div class="card-body">
                        
                        <canvas id="realized-cashflow-chart" style="width: 100%; height: 260px;"></canvas>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <?php echo view("projects/project_progress_chart_info"); ?>
                </div>
                <div class="col-md-6 col-sm-12">
                    <?php echo view("projects/project_task_pie_chart"); ?>
                </div>

                <?php if (get_setting('module_project_timesheet')) { ?>
                    <div class="col-md-12 col-sm-12">
                        <?php echo view("projects/widgets/total_hours_worked_widget"); ?>
                    </div>
                <?php } ?>

                <div class="col-md-12 col-sm-12 project-custom-fields">
                    <?php echo view('projects/custom_fields_list', array("custom_fields_list" => $custom_fields_list)); ?>
                </div>

                <?php if ($project_info->estimate_id) { ?>
                    <div class="col-md-12 col-sm-12">
                        <?php echo view("projects/estimates/index"); ?>
                    </div>
                <?php } ?>

                <?php if ($project_info->order_id) { ?>
                    <div class="col-md-12 col-sm-12">
                        <?php echo view("projects/orders/index"); ?>
                    </div>
                <?php } ?>

                <?php if ($project_info->proposal_id) { ?>
                    <div class="col-md-12 col-sm-12">
                        <?php echo view("projects/proposals/index"); ?>
                    </div>
                <?php } ?>

                <?php if ($can_add_remove_project_members) { ?>
                    <div class="col-md-12 col-sm-12">
                        <?php echo view("projects/project_members/index"); ?>
                    </div>
                <?php } ?>

                <?php if ($can_access_clients && $project_info->project_type === "client_project") { ?>
                    <div class="col-md-12 col-sm-12">
                        <?php echo view("projects/client_contacts/index"); ?>
                    </div>
                <?php } ?>

                <div class="col-md-12 col-sm-12">
                    <?php echo view("projects/project_description"); ?>
                </div>

            </div>
        </div>
        <div class="col-md-6">
            <div class="card project-activity-section">
                <div class="card-header">
                    <h4><?php echo app_lang('activity'); ?></h4>
                </div>
                <?php echo view("projects/history/index"); ?>
            </div>
        </div>
    </div>
</div>

<?php if (isset($project_result_summary)) { ?>
<script type="text/javascript">
    var resultChart = document.getElementById("project-result-chart");
    if (resultChart && window.Chart) {
        new Chart(resultChart, {
            type: "doughnut",
            data: {
                labels: ["Valor de venda", "Custos realizados", "Imposto previsto", "Lucro"],
                datasets: [
                    {
                        data: [
                            <?php echo (float)$project_value; ?>,
                            <?php echo (float)$costs_realized; ?>,
                            <?php echo (float)$tax_predicted; ?>,
                            <?php echo (float)$result_value; ?>
                        ],
                        backgroundColor: ["#2ecc71", "#e74c3c", "#f39c12", "#3498db"],
                        borderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: true,
                    position: "bottom",
                    labels: { fontColor: "#898fa9" }
                },
                animation: { animateScale: true }
            }
        });
    }
</script>
<?php } ?>

<?php if (isset($project_result_summary)) { ?>
<script type="text/javascript">
    var budgetChart = document.getElementById("project-budget-chart");
    if (budgetChart && window.Chart) {
        var budgetTotal = <?php echo (float)get_array_value($project_result_summary, "costs_planned", 0); ?>;
        var consumedTotal = <?php echo (float)get_array_value($project_result_summary, "costs_realized", 0); ?>;
        var budgetCurrency = <?php echo json_encode($currency ?? ""); ?>;

        function formatBudgetCurrency(value) {
            var num = parseFloat(value || 0);
            if (!isFinite(num)) {
                num = 0;
            }
            return budgetCurrency + " " + num.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        new Chart(budgetChart, {
            type: "bar",
            data: {
                labels: ["Custo"],
                datasets: [
                    {
                        label: "Budget",
                        data: [budgetTotal],
                        backgroundColor: "rgba(52, 152, 219, 0.7)",
                        borderColor: "rgba(52, 152, 219, 1)",
                        borderWidth: 1
                    },
                    {
                        label: "Consumido",
                        data: [consumedTotal],
                        backgroundColor: "rgba(231, 76, 60, 0.7)",
                        borderColor: "rgba(231, 76, 60, 1)",
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            fontColor: "#898fa9",
                            callback: function (value) {
                                return formatBudgetCurrency(value);
                            }
                        },
                        gridLines: { color: "rgba(0,0,0,0.05)" }
                    }],
                    xAxes: [{
                        ticks: { fontColor: "#898fa9" },
                        gridLines: { display: false }
                    }]
                },
                legend: {
                    display: true,
                    position: "bottom",
                    labels: { fontColor: "#898fa9" }
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem) {
                            return formatBudgetCurrency(tooltipItem.yLabel);
                        }
                    }
                }
            }
        });
    }
</script>
<?php } ?>

<?php if (!empty($planned_cashflow) && !empty($planned_cashflow["labels"])) { ?>
<script type="text/javascript">
    var fvCurrencySymbol = <?php echo json_encode($chart_currency); ?>;
    function fvFormatCurrency(value) {
        var num = parseFloat(value || 0);
        if (!isFinite(num)) {
            num = 0;
        }
        return fvCurrencySymbol + " " + num.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    var plannedCashflowChart = document.getElementById("planned-cashflow-chart");
    if (plannedCashflowChart && window.Chart) {
        var plannedLabels = <?php echo json_encode($planned_cashflow["labels"]); ?>;
        var plannedRevenue = <?php echo json_encode(get_array_value($planned_cashflow["revenue"], "planned_by_month", array())); ?>;
        var plannedExpenses = <?php echo json_encode(array_map(function ($v) { return -1 * (float)$v; }, get_array_value($planned_cashflow["expenses"], "planned_by_month", array()))); ?>;
        var plannedBalance = <?php echo json_encode(get_array_value($planned_cashflow["net"], "planned_cumulative", array())); ?>;
        if (!Array.isArray(plannedLabels)) {
            plannedLabels = [];
        }
        plannedLabels = plannedLabels.map(function (label) {
            if (typeof label !== "string") {
                return label;
            }
            if (/^\d{4}-\d{2}$/.test(label)) {
                var partsDash = label.split("-");
                return partsDash[1] + "/" + partsDash[0];
            }
            var parts = label.split("/");
            if (parts.length === 2) {
                return parts[1] + "/" + parts[0];
            }
            return label;
        });
        if (!Array.isArray(plannedRevenue)) {
            plannedRevenue = [];
        }
        if (!Array.isArray(plannedExpenses)) {
            plannedExpenses = [];
        }

        var maxLen = plannedLabels.length;
        if (plannedRevenue.length > maxLen) {
            maxLen = plannedRevenue.length;
        }
        if (plannedExpenses.length > maxLen) {
            maxLen = plannedExpenses.length;
        }
        if (maxLen === 0) {
            plannedCashflowChart = null;
        }
        if (plannedLabels.length < maxLen) {
            for (var i = plannedLabels.length; i < maxLen; i++) {
                plannedLabels.push("");
            }
        }
        if (plannedRevenue.length < maxLen) {
            for (var j = plannedRevenue.length; j < maxLen; j++) {
                plannedRevenue.push(0);
            }
        }
        if (plannedExpenses.length < maxLen) {
            for (var k = plannedExpenses.length; k < maxLen; k++) {
                plannedExpenses.push(0);
            }
        }

        if (!plannedBalance.length) {
            var running = 0;
            plannedBalance = plannedRevenue.map(function (val, idx) {
                var exp = plannedExpenses[idx] || 0;
                running += (val + exp);
                return running;
            });
        }

        // força saldo iniciar em 0 no começo do gráfico
        plannedLabels.unshift("Início");
        plannedRevenue.unshift(0);
        plannedExpenses.unshift(0);
        plannedBalance.unshift(0);

        if (plannedCashflowChart) {
        new Chart(plannedCashflowChart, {
            type: "bar",
            data: {
                labels: plannedLabels,
                datasets: [
                    {
                        label: "Receitas planejadas",
                        data: plannedRevenue,
                        backgroundColor: "rgba(46, 204, 113, 0.7)",
                        borderColor: "rgba(46, 204, 113, 1)",
                        borderWidth: 1,
                        yAxisID: "y-axis-0",
                        stack: "planned"
                    },
                    {
                        label: "Despesas planejadas",
                        data: plannedExpenses,
                        backgroundColor: "rgba(231, 76, 60, 0.7)",
                        borderColor: "rgba(231, 76, 60, 1)",
                        borderWidth: 1,
                        yAxisID: "y-axis-0",
                        stack: "planned"
                    },
                    {
                        label: "Saldo",
                        data: plannedBalance,
                        type: "line",
                        borderColor: "rgba(52, 152, 219, 1)",
                        backgroundColor: "rgba(52, 152, 219, 0.1)",
                        borderWidth: 2,
                        fill: false,
                        tension: 0.2,
                        yAxisID: "y-axis-0"
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        id: "y-axis-0",
                        stacked: true,
                        ticks: {
                            beginAtZero: true,
                            fontColor: "#898fa9",
                            callback: function (value) {
                                return fvFormatCurrency(value);
                            }
                        },
                        gridLines: { color: "rgba(0,0,0,0.05)" }
                    }],
                    xAxes: [{
                        stacked: true,
                        ticks: { fontColor: "#898fa9" },
                        gridLines: { display: false }
                    }]
                },
                legend: {
                    display: true,
                    position: "bottom",
                    labels: { fontColor: "#898fa9" }
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem, data) {
                            var label = data.datasets[tooltipItem.datasetIndex].label || "";
                            return label + ": " + fvFormatCurrency(tooltipItem.yLabel);
                        }
                    }
                }
            }
        });
        }
    }
</script>
<script type="text/javascript">
    var realizedCashflowChart = document.getElementById("realized-cashflow-chart");
    if (realizedCashflowChart && window.Chart) {
        var realizedLabels = <?php echo json_encode($planned_cashflow["labels"]); ?>;
        var realizedRevenue = <?php echo json_encode(get_array_value($planned_cashflow["revenue"], "realized_by_month", array())); ?>;
        var realizedExpenses = <?php echo json_encode(array_map(function ($v) { return -1 * (float)$v; }, get_array_value($planned_cashflow["expenses"], "realized_by_month", array()))); ?>;
        var realizedBalance = <?php echo json_encode(get_array_value($planned_cashflow["net"], "cumulative", array())); ?>;

        if (!Array.isArray(realizedLabels)) {
            realizedLabels = [];
        }
        realizedLabels = realizedLabels.map(function (label) {
            if (typeof label !== "string") {
                return label;
            }
            if (/^\d{4}-\d{2}$/.test(label)) {
                var partsDash = label.split("-");
                return partsDash[1] + "/" + partsDash[0];
            }
            var parts = label.split("/");
            if (parts.length === 2) {
                return parts[1] + "/" + parts[0];
            }
            return label;
        });
        if (!Array.isArray(realizedRevenue)) {
            realizedRevenue = [];
        }
        if (!Array.isArray(realizedExpenses)) {
            realizedExpenses = [];
        }

        var realizedMaxLen = realizedLabels.length;
        if (realizedRevenue.length > realizedMaxLen) {
            realizedMaxLen = realizedRevenue.length;
        }
        if (realizedExpenses.length > realizedMaxLen) {
            realizedMaxLen = realizedExpenses.length;
        }
        if (realizedMaxLen === 0) {
            realizedCashflowChart = null;
        }
        if (realizedLabels.length < realizedMaxLen) {
            for (var r = realizedLabels.length; r < realizedMaxLen; r++) {
                realizedLabels.push("");
            }
        }
        if (realizedRevenue.length < realizedMaxLen) {
            for (var rj = realizedRevenue.length; rj < realizedMaxLen; rj++) {
                realizedRevenue.push(0);
            }
        }
        if (realizedExpenses.length < realizedMaxLen) {
            for (var rk = realizedExpenses.length; rk < realizedMaxLen; rk++) {
                realizedExpenses.push(0);
            }
        }

        if (!realizedBalance.length) {
            var runningReal = 0;
            realizedBalance = realizedRevenue.map(function (val, idx) {
                var exp = realizedExpenses[idx] || 0;
                runningReal += (val + exp);
                return runningReal;
            });
        }

        // força saldo iniciar em 0 no começo do gráfico realizado
        realizedLabels.unshift("Início");
        realizedRevenue.unshift(0);
        realizedExpenses.unshift(0);
        realizedBalance.unshift(0);

        if (realizedCashflowChart) {
        new Chart(realizedCashflowChart, {
            type: "bar",
            data: {
                labels: realizedLabels,
                datasets: [
                    {
                        label: "Receitas realizadas",
                        data: realizedRevenue,
                        backgroundColor: "rgba(46, 204, 113, 0.7)",
                        borderColor: "rgba(46, 204, 113, 1)",
                        borderWidth: 1,
                        yAxisID: "y-axis-0",
                        stack: "realized"
                    },
                    {
                        label: "Despesas realizadas",
                        data: realizedExpenses,
                        backgroundColor: "rgba(231, 76, 60, 0.7)",
                        borderColor: "rgba(231, 76, 60, 1)",
                        borderWidth: 1,
                        yAxisID: "y-axis-0",
                        stack: "realized"
                    },
                    {
                        label: "Saldo",
                        data: realizedBalance,
                        type: "line",
                        borderColor: "rgba(52, 152, 219, 1)",
                        backgroundColor: "rgba(52, 152, 219, 0.1)",
                        borderWidth: 2,
                        fill: false,
                        tension: 0.2,
                        yAxisID: "y-axis-0"
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        id: "y-axis-0",
                        stacked: true,
                        ticks: {
                            beginAtZero: true,
                            fontColor: "#898fa9",
                            callback: function (value) {
                                return fvFormatCurrency(value);
                            }
                        },
                        gridLines: { color: "rgba(0,0,0,0.05)" }
                    }],
                    xAxes: [{
                        stacked: true,
                        ticks: { fontColor: "#898fa9" },
                        gridLines: { display: false }
                    }]
                },
                legend: {
                    display: true,
                    position: "bottom",
                    labels: { fontColor: "#898fa9" }
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem, data) {
                            var label = data.datasets[tooltipItem.datasetIndex].label || "";
                            return label + ": " + fvFormatCurrency(tooltipItem.yLabel);
                        }
                    }
                }
            }
        });
        }
    }
</script>
<?php } ?>
