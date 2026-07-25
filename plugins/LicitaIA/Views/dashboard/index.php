<?php
$summary = $summary ?? array();
$status_counts = $status_counts ?? array();
$recent_opportunities = $recent_opportunities ?? array();
$due_soon = $due_soon ?? array();
$pipeline_total = (int) ($pipeline_total ?? 0);
$chart = $chart ?? array();

include_once PLUGINPATH . 'LicitaIA/Views/shared/ui.php';

$cards = array(
    array('label' => app_lang('licitaia_total_opportunities'), 'value' => (int) get_array_value($summary, 'total', 0), 'icon' => 'file-text', 'color' => 'primary'),
    array('label' => app_lang('licitaia_new_opportunities'), 'value' => (int) get_array_value($status_counts, 'new', 0), 'icon' => 'plus-circle', 'color' => 'info'),
    array('label' => app_lang('licitaia_under_analysis'), 'value' => (int) get_array_value($status_counts, 'analyzing', 0), 'icon' => 'activity', 'color' => 'warning'),
    array('label' => app_lang('licitaia_participate'), 'value' => (int) get_array_value($status_counts, 'participate', 0), 'icon' => 'check-circle', 'color' => 'success'),
    array('label' => app_lang('licitaia_do_not_participate'), 'value' => (int) get_array_value($status_counts, 'not_participate', 0), 'icon' => 'slash', 'color' => 'secondary'),
    array('label' => app_lang('licitaia_in_progress_proposals'), 'value' => $pipeline_total, 'icon' => 'layers', 'color' => 'dark'),
    array('label' => app_lang('licitaia_upcoming_deadlines'), 'value' => count((array) $due_soon), 'icon' => 'clock', 'color' => 'danger'),
    array('label' => app_lang('licitaia_won_bids'), 'value' => (int) get_array_value($status_counts, 'won', 0), 'icon' => 'award', 'color' => 'success'),
    array('label' => app_lang('licitaia_lost_bids'), 'value' => (int) get_array_value($status_counts, 'lost', 0), 'icon' => 'x-circle', 'color' => 'danger'),
);

$chart_labels = json_encode(get_array_value($chart, 'labels') ?: array());
$chart_data = json_encode(get_array_value($chart, 'data') ?: array());
$chart_colors = json_encode(get_array_value($chart, 'colors') ?: array());
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div class="d-flex flex-column">
                <h1 class="mb-1"><?php echo app_lang('licitaia_dashboard'); ?></h1>
                <div class="text-muted"><?php echo app_lang('licitaia_dashboard_intro'); ?></div>
            </div>
            <div class="title-button-group">
                <?php if ($can_manage ?? false) { ?>
                    <?php echo modal_anchor(get_uri('licitaia/opportunities/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('licitaia_new_opportunity'), array('class' => 'btn btn-primary', 'title' => app_lang('licitaia_new_opportunity'))); ?>
                    <?php echo anchor(get_uri('licitaia/opportunities'), "<i data-feather='upload' class='icon-16'></i> " . app_lang('licitaia_import_notice'), array('class' => 'btn btn-default ms-1')); ?>
                    <?php echo anchor(get_uri('licitaia/sources'), "<i data-feather='search' class='icon-16'></i> " . app_lang('licitaia_search_notices'), array('class' => 'btn btn-default ms-1')); ?>
                <?php } ?>
                <?php if ($can_manage_settings ?? false) { ?>
                    <?php echo anchor(get_uri('licitaia/settings'), "<i data-feather='settings' class='icon-16'></i> " . app_lang('licitaia_settings'), array('class' => 'btn btn-default ms-1')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <?php foreach ($cards as $card) { ?>
                    <div class="col-xl-4 col-md-6 widget-container">
                        <div class="card dashboard-icon-widget">
                            <div class="row row-middle">
                                <div class="col-4">
                                    <div class="widget-icon bg-<?php echo esc($card['color']); ?>">
                                        <i data-feather="<?php echo esc($card['icon']); ?>" class="icon-32"></i>
                                    </div>
                                </div>
                                <div class="col-8">
                                    <div class="widget-details">
                                        <h1 class="mb-0"><?php echo (int) $card['value']; ?></h1>
                                        <span class="bg-transparent-white"><?php echo esc($card['label']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h4 class="mb-0"><?php echo app_lang('licitaia_recent_opportunities'); ?></h4>
                            <a href="<?php echo get_uri('licitaia/opportunities'); ?>" class="btn btn-sm btn-default"><?php echo app_lang('view'); ?></a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo app_lang('licitaia_opportunity'); ?></th>
                                        <th><?php echo app_lang('licitaia_edital_number'); ?></th>
                                        <th><?php echo app_lang('licitaia_status'); ?></th>
                                        <th><?php echo app_lang('licitaia_ai_status'); ?></th>
                                        <th><?php echo app_lang('licitaia_deadline'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_opportunities)) { ?>
                                        <?php foreach ($recent_opportunities as $opportunity) { ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo esc($opportunity->title ?: '-'); ?></td>
                                                <td><?php echo esc($opportunity->edital_number ?: '-'); ?></td>
                                                <td><?php echo licitaia_badge_html(app_lang('licitaia_status_' . $opportunity->status), licitaia_status_badge_class($opportunity->status)); ?></td>
                                                <td><?php echo licitaia_badge_html(app_lang('licitaia_ai_' . $opportunity->ai_status), 'light text-dark border'); ?></td>
                                                <td><?php echo esc(!empty($opportunity->submission_deadline) ? format_to_date($opportunity->submission_deadline, false) : '-'); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4"><?php echo app_lang('licitaia_empty_state'); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h4 class="mb-0"><?php echo app_lang('licitaia_recent_deadlines'); ?></h4>
                            <span class="badge bg-light text-dark border"><?php echo count((array) $due_soon); ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (!empty($due_soon)) { ?>
                                    <?php foreach ($due_soon as $item) { ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold"><?php echo esc($item->title ?: '-'); ?></div>
                                                    <div class="text-muted small"><?php echo esc($item->edital_number ?: '-'); ?></div>
                                                </div>
                                                <?php echo licitaia_badge_html(!empty($item->submission_deadline) ? format_to_date($item->submission_deadline, false) : '-', 'light text-danger border', 'clock'); ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="list-group-item text-muted text-center py-4"><?php echo app_lang('licitaia_empty_state'); ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('licitaia_status'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div style="height: 320px;">
                                <canvas id="licitaia-status-chart" style="width: 100%; height: 320px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('licitaia_shortcuts'); ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="<?php echo get_uri('licitaia/opportunities'); ?>" class="card border h-100 text-decoration-none">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-1"><?php echo app_lang('licitaia_opportunities'); ?></div>
                                            <div class="text-muted small"><?php echo app_lang('licitaia_new_opportunity'); ?></div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo get_uri('licitaia/keywords'); ?>" class="card border h-100 text-decoration-none">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-1"><?php echo app_lang('licitaia_keywords'); ?></div>
                                            <div class="text-muted small"><?php echo app_lang('licitaia_permission_keywords'); ?></div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo get_uri('licitaia/sources'); ?>" class="card border h-100 text-decoration-none">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-1"><?php echo app_lang('licitaia_sources'); ?></div>
                                            <div class="text-muted small"><?php echo app_lang('licitaia_search_notices'); ?></div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="<?php echo get_uri('licitaia/reports'); ?>" class="card border h-100 text-decoration-none">
                                        <div class="card-body">
                                            <div class="fw-semibold mb-1"><?php echo app_lang('licitaia_reports'); ?></div>
                                            <div class="text-muted small"><?php echo app_lang('licitaia_permission_reports'); ?></div>
                                        </div>
                                    </a>
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
    $(function () {
        if (typeof Chart === "undefined") {
            return;
        }

        var canvas = document.getElementById("licitaia-status-chart");
        if (!canvas) {
            return;
        }

        new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: <?php echo $chart_labels; ?>,
                datasets: [{
                    data: <?php echo $chart_data; ?>,
                    backgroundColor: <?php echo $chart_colors; ?>,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });
    });
</script>
