<?php
$dashboard = $dashboard ?? array();
$summary = $summary ?? array();
$charts = $charts ?? array();
$recent_records = $recent_records ?? array();
$dashboard_filters = $dashboard_filters ?? array();
$month_dropdown = $month_dropdown ?? array();
$year_dropdown = $year_dropdown ?? array();

$present_today = (int) get_array_value($summary, 'present_today', 0);
$absent_today = (int) get_array_value($summary, 'absent_today', 0);
$late_today = (int) get_array_value($summary, 'late_today', 0);
$extra_minutes_month = (int) get_array_value($summary, 'extra_minutes_month', 0);
$pending_adjustments = (int) get_array_value($summary, 'pending_adjustments', 0);
$inconsistent_records = (int) get_array_value($summary, 'inconsistent_records', 0);

$chart_labels = get_array_value($charts, 'labels', array());
$frequency_series = get_array_value($charts, 'frequency', array());
$extra_series = get_array_value($charts, 'extra_hours', array());
$bank_series = get_array_value($charts, 'bank_hours', array());
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('pontorh_dashboard_title'); ?></h1>
                <div class="text-muted"><?php echo app_lang('pontorh_dashboard_intro'); ?></div>
                <div class="text-muted small mt5"><?php echo esc($dashboard_period ?? ''); ?></div>
            </div>
            <div class="title-button-group">
                <?php if ($records_can_manage) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/registros/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('pontorh_records'), array('class' => 'btn btn-primary', 'title' => app_lang('pontorh_records'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form method="get" action="<?php echo get_uri('pontorh'); ?>" class="general-form">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><?php echo app_lang('month'); ?></label>
                        <?php echo form_dropdown('month', $month_dropdown, $dashboard_filters['month'] ?? date('n'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><?php echo app_lang('year'); ?></label>
                        <?php echo form_dropdown('year', $year_dropdown, $dashboard_filters['year'] ?? date('Y'), 'class="form-control select2"'); ?>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary btn-sm"><?php echo app_lang('filter'); ?></button>
                        <a href="<?php echo get_uri('pontorh'); ?>" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_present_today'); ?></div>
                            <div class="font-26 fw-bold"><?php echo $present_today; ?></div>
                            <div class="text-muted small"><?php echo app_lang('pontorh_today'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_absent_today'); ?></div>
                            <div class="font-26 fw-bold"><?php echo $absent_today; ?></div>
                            <div class="text-muted small"><?php echo app_lang('pontorh_today'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_late_today'); ?></div>
                            <div class="font-26 fw-bold"><?php echo $late_today; ?></div>
                            <div class="text-muted small"><?php echo app_lang('pontorh_today'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_extra_hours'); ?></div>
                            <div class="font-26 fw-bold"><?php echo pontorh_minutes_to_hours_label($extra_minutes_month); ?></div>
                            <div class="text-muted small"><?php echo app_lang('pontorh_month_to_date'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_pending_adjustments'); ?></div>
                            <div class="font-26 fw-bold"><?php echo $pending_adjustments; ?></div>
                            <div class="text-muted small"><?php echo app_lang('pontorh_adjustments'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_inconsistent_records'); ?></div>
                            <div class="font-26 fw-bold"><?php echo $inconsistent_records; ?></div>
                            <div class="text-muted small"><?php echo app_lang('pontorh_month_to_date'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_frequency_monthly'); ?></h4>
                            <span class="text-muted small"><?php echo esc($dashboard_period ?? ''); ?></span>
                        </div>
                        <div class="card-body">
                            <div style="position:relative; height:280px;">
                                <canvas id="pontorh-frequency-chart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_overtime_monthly'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div style="position:relative; height:280px;">
                                <canvas id="pontorh-overtime-chart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_bank_balance'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div style="position:relative; height:280px;">
                                <canvas id="pontorh-bank-chart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo app_lang('pontorh_records'); ?></h4>
                            <a class="btn btn-default btn-sm" href="<?php echo get_uri('pontorh/registros'); ?>"><?php echo app_lang('view'); ?></a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                <tr>
                                    <th><?php echo app_lang('pontorh_employee'); ?></th>
                                    <th><?php echo app_lang('pontorh_work_date'); ?></th>
                                    <th><?php echo app_lang('pontorh_check_in'); ?></th>
                                    <th><?php echo app_lang('pontorh_check_out'); ?></th>
                                    <th><?php echo app_lang('pontorh_status'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($recent_records) { ?>
                                    <?php foreach (array_slice($recent_records, 0, 5) as $record) { ?>
                                        <tr>
                                            <td><?php echo esc($record->team_member_name ?: '-'); ?></td>
                                            <td><?php echo esc($record->work_date ?: '-'); ?></td>
                                            <td><?php echo $record->check_in ? pontorh_extract_time($record->check_in) : '-'; ?></td>
                                            <td><?php echo $record->check_out ? pontorh_extract_time($record->check_out) : '-'; ?></td>
                                            <td><span class="badge bg-secondary"><?php echo esc(app_lang('pontorh_status_' . $record->status)); ?></span></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted"><?php echo app_lang('pontorh_records_empty'); ?></td>
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

<script type="text/javascript">
    (function () {
        const labels = <?php echo json_encode(array_values($chart_labels)); ?>;
        const frequencyData = <?php echo json_encode(array_values($frequency_series)); ?>;
        const overtimeData = <?php echo json_encode(array_values($extra_series)); ?>;
        const bankData = <?php echo json_encode(array_values($bank_series)); ?>;
        const chartRegistry = window.pontorhDashboardCharts = window.pontorhDashboardCharts || {};

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

        function createLineChart(canvasId, label, data, color) {
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
                        pointRadius: 2,
                        pointHoverRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' + minutesLabel(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function (value) {
                                    return minutesLabel(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        const frequencyCanvas = document.getElementById('pontorh-frequency-chart');
        if (frequencyCanvas && typeof Chart !== 'undefined') {
            destroyChart('pontorh-frequency-chart');

            chartRegistry['pontorh-frequency-chart'] = new Chart(frequencyCanvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: <?php echo json_encode(app_lang('pontorh_present_today')); ?>,
                        data: frequencyData,
                        backgroundColor: 'rgba(33, 150, 243, 0.55)',
                        borderColor: 'rgba(33, 150, 243, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        createLineChart('pontorh-overtime-chart', <?php echo json_encode(app_lang('pontorh_extra_hours')); ?>, overtimeData, 'rgba(46, 125, 50, 1)');
        createLineChart('pontorh-bank-chart', <?php echo json_encode(app_lang('pontorh_bank_balance')); ?>, bankData, 'rgba(255, 152, 0, 1)');
    })();
</script>
