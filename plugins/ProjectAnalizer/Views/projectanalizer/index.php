<div class="clearfix default-bg">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb15">
                <div class="card-header">
                    <h4>Dashboard financeiro de Projetos</h4>
                </div>
                <div class="card-body">
                    <?php
                    $currency = get_setting("default_currency_symbol") ?: "";
                    $date_from_value = $date_from ? substr($date_from, 0, 7) : "";
                    $date_to_value = $date_to ? substr($date_to, 0, 7) : "";
                    ?>
                    <form class="general-form" method="get" action="<?php echo_uri("projectanalizer"); ?>">
                        <div class="row">
                            <div class="col-md-2 col-sm-6 mb10">
                                <label class="text-off">Período (de)</label>
                                <input type="month" name="date_from" class="form-control" value="<?php echo $date_from_value; ?>" />
                            </div>
                            <div class="col-md-2 col-sm-6 mb10">
                                <label class="text-off">Período (até)</label>
                                <input type="month" name="date_to" class="form-control" value="<?php echo $date_to_value; ?>" />
                            </div>
                            <div class="col-md-6 col-sm-12 mb10">
                                <label class="text-off">Projetos</label>
                                <select id="project-ids" name="project_ids[]" class="form-control select2" multiple>
                                    <?php foreach ($projects_dropdown as $project) { ?>
                                        <?php $selected = in_array((int)$project["id"], $selected_project_ids) ? "selected" : ""; ?>
                                        <option value="<?php echo $project["id"]; ?>" <?php echo $selected; ?>>
                                            <?php echo $project["text"]; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6 mb10">
                                <label class="text-off">&nbsp;</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="include_completed" value="1" <?php echo $include_completed ? "checked" : ""; ?>>
                                        Incluir finalizados
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm mt5">Aplicar</button>
                            </div>
                        </div>
                    </form>

                    <div class="row mt15">
                        <div class="col-md-2 col-sm-6 mb15">
                            <div class="card card-body">
                                <div class="text-off">Receitas planejadas</div>
                                <div class="strong"><?php echo to_currency($totals["planned_revenue"], $currency); ?></div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb15">
                            <div class="card card-body">
                                <div class="text-off">Despesas planejadas</div>
                                <div class="text-danger"><?php echo to_currency($totals["planned_expenses"], $currency); ?></div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb15">
                            <div class="card card-body">
                                <div class="text-off">Receitas realizadas</div>
                                <div class="strong"><?php echo to_currency($totals["realized_revenue"], $currency); ?></div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb15">
                            <div class="card card-body">
                                <div class="text-off">Despesas realizadas</div>
                                <div class="text-danger"><?php echo to_currency($totals["realized_expenses"], $currency); ?></div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb15">
                            <div class="card card-body">
                                <div class="text-off">Saldo realizado</div>
                                <div class="<?php echo $totals["realized_balance"] >= 0 ? "text-success" : "text-danger"; ?>">
                                    <?php echo to_currency($totals["realized_balance"], $currency); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb15">
                            <div class="card card-body">
                                <div class="text-off">Economia de budget</div>
                                <div class="<?php echo $totals["budget_saving"] >= 0 ? "text-success" : "text-danger"; ?>">
                                    <?php echo to_currency($totals["budget_saving"], $currency); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="card mb15">
                <div class="card-header">
                    <h4>Receitas e despesas planejadas</h4>
                </div>
                <div class="card-body">
                    <canvas id="dashboard-planned-chart" style="width: 100%; height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="card mb15">
                <div class="card-header">
                    <h4>Receitas e despesas realizadas</h4>
                </div>
                <div class="card-body">
                    <canvas id="dashboard-realized-chart" style="width: 100%; height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var currencySymbol = <?php echo json_encode($currency); ?>;
        function formatCurrency(value) {
            var num = parseFloat(value || 0);
            if (!isFinite(num)) {
                num = 0;
            }
            return currencySymbol + " " + num.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatLabels(labels) {
            if (!Array.isArray(labels)) {
                return [];
            }
            return labels.map(function (label) {
                if (typeof label !== "string") {
                    return label;
                }
                if (/^\d{4}-\d{2}$/.test(label)) {
                    var parts = label.split("-");
                    return parts[1] + "/" + parts[0];
                }
                return label;
            });
        }

        var labels = formatLabels(<?php echo json_encode($labels); ?>);
        var plannedRevenue = <?php echo json_encode($planned_revenue); ?>;
        var plannedExpenses = <?php echo json_encode(array_map(function ($v) { return -1 * (float)$v; }, $planned_expenses)); ?>;
        var plannedBalance = <?php echo json_encode($planned_balance); ?>;

        var realizedRevenue = <?php echo json_encode($realized_revenue); ?>;
        var realizedExpenses = <?php echo json_encode(array_map(function ($v) { return -1 * (float)$v; }, $realized_expenses)); ?>;
        var realizedBalance = <?php echo json_encode($realized_balance); ?>;

        if (labels.length) {
            labels.unshift("Início");
            plannedRevenue.unshift(0);
            plannedExpenses.unshift(0);
            plannedBalance.unshift(0);
            realizedRevenue.unshift(0);
            realizedExpenses.unshift(0);
            realizedBalance.unshift(0);
        }

        $("#project-ids").select2();

        var plannedCanvas = document.getElementById("dashboard-planned-chart");
        if (plannedCanvas && window.Chart) {
            new Chart(plannedCanvas, {
                type: "bar",
                data: {
                    labels: labels,
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
                                    return formatCurrency(value);
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
                                return label + ": " + formatCurrency(tooltipItem.yLabel);
                            }
                        }
                    }
                }
            });
        }

        var realizedCanvas = document.getElementById("dashboard-realized-chart");
        if (realizedCanvas && window.Chart) {
            new Chart(realizedCanvas, {
                type: "bar",
                data: {
                    labels: labels,
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
                                    return formatCurrency(value);
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
                                return label + ": " + formatCurrency(tooltipItem.yLabel);
                            }
                        }
                    }
                }
            });
        }
    })();
</script>
