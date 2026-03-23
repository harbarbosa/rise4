<div class="clearfix default-bg">

    <div class="row">
        <?php if (isset($project_result_summary)) { ?>
            <div class="col-md-12 col-sm-12">
                <div class="card mb15">
                    <div class="card-header">
                        <h4>Resultado do projeto</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $project_value = get_array_value($project_result_summary, "project_value", 0);
                        $costs_realized = get_array_value($project_result_summary, "costs_realized", 0);
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
