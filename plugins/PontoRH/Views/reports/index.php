<?php
$summary = $summary ?? array();
$charts = $charts ?? array();
$filters = $filters ?? array();
$labels = get_array_value($charts, 'labels', array());
$worked_hours = get_array_value($charts, 'worked_hours', array());
$extra_hours = get_array_value($charts, 'extra_hours', array());
$bank_hours = get_array_value($charts, 'bank_hours', array());
$absences = get_array_value($charts, 'absences', array());
$outside_area = get_array_value($charts, 'outside_area', array());
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('pontorh_reports'); ?></h1>
                <div class="text-muted"><?php echo esc($report_period ?? ''); ?></div>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form method="get" action="<?php echo get_uri('pontorh/relatorios'); ?>" class="general-form">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label"><?php echo app_lang('pontorh_employee'); ?></label>
                        <?php echo form_dropdown('team_member_id', $team_members_dropdown, $filters['team_member_id'] ?? '', 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php echo app_lang('month'); ?></label>
                        <?php echo form_dropdown('month', $month_dropdown, $filters['month'] ?? date('n'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php echo app_lang('year'); ?></label>
                        <?php echo form_dropdown('year', $year_dropdown, $filters['year'] ?? date('Y'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-sm"><?php echo app_lang('filter'); ?></button>
                        <a href="<?php echo get_uri('pontorh/relatorios'); ?>" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-lg-2 col-md-4">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_minutes_worked'); ?></div><div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label(get_array_value($summary, 'worked_minutes_total', 0)); ?></div></div></div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_extra_hours'); ?></div><div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label(get_array_value($summary, 'extra_minutes_total', 0)); ?></div></div></div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_bank_hours'); ?></div><div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label(get_array_value($summary, 'bank_minutes_end', 0)); ?></div></div></div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_absences'); ?></div><div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'absences_total', 0); ?></div></div></div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_out_of_area'); ?></div><div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'out_of_area_total', 0); ?></div></div></div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_lateness'); ?></div><div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label(get_array_value($summary, 'late_total', 0)); ?></div></div></div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_frequency_monthly'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div style="position:relative; height:280px;">
                                <canvas id="pontorh-report-worked-chart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_overtime_monthly'); ?></h4></div>
                        <div class="card-body">
                            <div style="position:relative; height:280px;">
                                <canvas id="pontorh-report-extra-chart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_bank_balance'); ?></h4></div>
                        <div class="card-body">
                            <div style="position:relative; height:280px;">
                                <canvas id="pontorh-report-bank-chart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_absences'); ?></h4></div>
                        <div class="card-body">
                            <div style="position:relative; height:280px;">
                                <canvas id="pontorh-report-absences-chart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h4 class="mb-0"><?php echo app_lang('pontorh_out_of_area'); ?></h4></div>
                        <div class="card-body">
                            <div style="position:relative; height:280px;">
                                <canvas id="pontorh-report-outside-chart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><?php echo app_lang('pontorh_summary'); ?></h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th><?php echo app_lang('pontorh_work_date'); ?></th>
                                <th><?php echo app_lang('pontorh_minutes_worked'); ?></th>
                                <th><?php echo app_lang('pontorh_extra_hours'); ?></th>
                                <th><?php echo app_lang('pontorh_bank_hours'); ?></th>
                                <th><?php echo app_lang('pontorh_absences'); ?></th>
                                <th><?php echo app_lang('pontorh_out_of_area'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($labels as $index => $label) { ?>
                                <tr>
                                    <td><?php echo esc($label); ?></td>
                                    <td><?php echo esc(pontorh_minutes_to_hours_label($worked_hours[$index] ?? 0)); ?></td>
                                    <td><?php echo esc(pontorh_minutes_to_hours_label($extra_hours[$index] ?? 0)); ?></td>
                                    <td><?php echo esc(pontorh_minutes_to_hours_label($bank_hours[$index] ?? 0)); ?></td>
                                    <td><?php echo (int) ($absences[$index] ?? 0); ?></td>
                                    <td><?php echo (int) ($outside_area[$index] ?? 0); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function () {
        const labels = <?php echo json_encode(array_values($labels)); ?>;
        const workedHours = <?php echo json_encode(array_values($worked_hours)); ?>;
        const extraHours = <?php echo json_encode(array_values($extra_hours)); ?>;
        const bankHours = <?php echo json_encode(array_values($bank_hours)); ?>;
        const absences = <?php echo json_encode(array_values($absences)); ?>;
        const outsideArea = <?php echo json_encode(array_values($outside_area)); ?>;
        const chartRegistry = window.pontorhReportCharts = window.pontorhReportCharts || {};

        function minutesLabel(value) {
            value = parseInt(value || 0, 10);
            const sign = value < 0 ? '-' : '';
            value = Math.abs(value);
            const hours = Math.floor(value / 60);
            const minutes = String(value % 60).padStart(2, '0');
            return sign + hours + 'h ' + minutes + 'm';
        }

        function destroyChart(chartKey) {
            if (chartRegistry[chartKey]) {
                chartRegistry[chartKey].destroy();
                delete chartRegistry[chartKey];
            }
        }

        function buildLineChart(canvasId, label, data, color) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            destroyChart(canvasId);

            chartRegistry[canvasId] = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: color,
                        backgroundColor: color.replace('1)', '0.15)'),
                        tension: 0.35,
                        fill: true,
                        pointRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'bottom' }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: function (value) { return minutesLabel(value); }
                            }
                        }
                    }
                }
            });
        }

        function buildBarChart(canvasId, label, data, color) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            destroyChart(canvasId);

            chartRegistry[canvasId] = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: color
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'bottom' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        }

        buildLineChart('pontorh-report-worked-chart', <?php echo json_encode(app_lang('pontorh_minutes_worked')); ?>, workedHours, 'rgba(33, 150, 243, 1)');
        buildLineChart('pontorh-report-extra-chart', <?php echo json_encode(app_lang('pontorh_extra_hours')); ?>, extraHours, 'rgba(46, 125, 50, 1)');
        buildLineChart('pontorh-report-bank-chart', <?php echo json_encode(app_lang('pontorh_bank_hours')); ?>, bankHours, 'rgba(255, 152, 0, 1)');
        buildBarChart('pontorh-report-absences-chart', <?php echo json_encode(app_lang('pontorh_absences')); ?>, absences, 'rgba(244, 67, 54, 0.65)');
        buildBarChart('pontorh-report-outside-chart', <?php echo json_encode(app_lang('pontorh_out_of_area')); ?>, outsideArea, 'rgba(156, 39, 176, 0.65)');
    })();
</script>
