<?php
$dashboard = $dashboard ?? array();
$summary = get_array_value($dashboard, 'summary') ?: array();
$alerts = get_array_value($dashboard, 'alerts') ?: array();
$attention = get_array_value($dashboard, 'attention') ?: array();
$urgent_not_started = get_array_value($dashboard, 'urgent_not_started') ?: array();
$procrastination_risks = get_array_value($dashboard, 'procrastination_risks') ?: array();
$forgotten_tasks = get_array_value($dashboard, 'forgotten_tasks') ?: array();
$suggestions = get_array_value($dashboard, 'suggestions') ?: array();
$priority_chart = get_array_value($dashboard, 'priority_chart') ?: array();
$status_chart = get_array_value($dashboard, 'status_chart') ?: array();
$category_ranking = get_array_value($dashboard, 'category_ranking') ?: array();

$summary_cards = array(
    array(
        'label' => app_lang('organizador_dashboard_today'),
        'count' => (int) get_array_value($summary, 'today'),
        'icon' => 'sun',
        'class' => 'text-primary',
        'filter' => 'today',
        'hint' => app_lang('organizador_dashboard_today_hint'),
    ),
    array(
        'label' => app_lang('organizador_dashboard_overdue'),
        'count' => (int) get_array_value($summary, 'overdue'),
        'icon' => 'alert-circle',
        'class' => 'text-danger',
        'filter' => 'overdue',
        'hint' => app_lang('organizador_dashboard_overdue_hint'),
    ),
    array(
        'label' => app_lang('organizador_dashboard_urgent'),
        'count' => (int) get_array_value($summary, 'urgent'),
        'icon' => 'zap',
        'class' => 'text-danger',
        'filter' => array('priority' => 'urgent'),
        'hint' => app_lang('organizador_dashboard_urgent_hint'),
    ),
    array(
        'label' => app_lang('organizador_dashboard_no_start'),
        'count' => (int) get_array_value($summary, 'no_start'),
        'icon' => 'play-circle',
        'class' => 'text-warning',
        'filter' => 'no_start',
        'hint' => app_lang('organizador_dashboard_no_start_hint'),
    ),
    array(
        'label' => app_lang('organizador_dashboard_completed_week'),
        'count' => (int) get_array_value($summary, 'completed_week'),
        'icon' => 'check-circle',
        'class' => 'text-success',
        'filter' => 'completed',
        'hint' => app_lang('organizador_dashboard_completed_week_hint'),
    ),
    array(
        'label' => app_lang('organizador_dashboard_procrastination'),
        'count' => (int) get_array_value($summary, 'procrastination'),
        'icon' => 'activity',
        'class' => 'text-warning',
        'filter' => 'procrastination',
        'hint' => app_lang('organizador_dashboard_procrastination_hint'),
    ),
);

function organizador_dashboard_link($filter)
{
    $params = array();
    if (is_array($filter)) {
        $params = $filter;
    } elseif ($filter !== null && $filter !== '') {
        $params['quick_filter'] = $filter;
    }

    return get_uri('organizador/tasks') . '?' . http_build_query($params);
}

function organizador_dashboard_alert_class($severity)
{
    $map = array(
        'critical' => 'border-danger',
        'attention' => 'border-warning',
        'informative' => 'border-primary',
    );

    return get_array_value($map, $severity) ?: 'border-secondary bg-light';
}

function organizador_dashboard_badge_class($severity)
{
    $map = array(
        'critical' => 'bg-danger',
        'attention' => 'bg-warning text-dark',
        'informative' => 'bg-primary',
        'success' => 'bg-success',
        'danger' => 'bg-danger',
    );

    return get_array_value($map, $severity) ?: 'bg-secondary';
}

$attention_severity = get_array_value($attention, 'severity') ?: 'success';
$attention_score = (int) get_array_value($attention, 'score');
$attention_label = get_array_value($attention, 'label') ?: app_lang('organizador_dashboard_attention_controlled');
$attention_message = get_array_value($attention, 'message') ?: '';

$priority_labels = json_encode(get_array_value($priority_chart, 'labels') ?: array());
$priority_values = json_encode(get_array_value($priority_chart, 'data') ?: array());
$priority_colors = json_encode(get_array_value($priority_chart, 'colors') ?: array());
$status_labels = json_encode(get_array_value($status_chart, 'labels') ?: array());
$status_values = json_encode(get_array_value($status_chart, 'data') ?: array());
$status_colors = json_encode(get_array_value($status_chart, 'colors') ?: array());

$alerts_visible = array_slice($alerts, 0, 3);
$urgent_not_started_visible = array_slice($urgent_not_started, 0, 3);
$procrastination_risks_visible = array_slice($procrastination_risks, 0, 4);
$forgotten_tasks_visible = array_slice($forgotten_tasks, 0, 3);
$suggestions_visible = array_slice($suggestions, 0, 3);
$category_ranking_visible = array_slice($category_ranking, 0, 4);
?>

<div id="page-content" class="page-wrapper clearfix">
    <style>
        .organizador-dashboard .summary-card {
            transition: transform .15s ease, box-shadow .15s ease;
            border: 1px solid rgba(0,0,0,.06);
            min-height: 104px;
        }
        .organizador-dashboard .summary-card:hover,
        .organizador-dashboard .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1.2rem rgba(31, 41, 55, .08);
        }
        .organizador-dashboard .insight-band {
            background: linear-gradient(135deg, rgba(13,110,253,.08), rgba(220,53,69,.08));
            border: 1px solid rgba(13,110,253,.12);
        }
        .organizador-dashboard .insight-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            font-weight: 600;
        }
        .organizador-dashboard .alert-row {
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 12px;
            border-width: 1px;
            border-style: solid;
        }
        .organizador-dashboard .alert-row.border-danger {
            background: rgba(220, 53, 69, .05);
        }
        .organizador-dashboard .alert-row.border-warning {
            background: rgba(255, 193, 7, .08);
        }
        .organizador-dashboard .alert-row.border-primary {
            background: rgba(13, 110, 253, .05);
        }
        .organizador-dashboard .smart-list-item {
            border-bottom: 1px solid rgba(0,0,0,.06);
            padding: 12px 0;
        }
        .organizador-dashboard .smart-list-item:last-child {
            border-bottom: 0;
        }
        .organizador-dashboard .mini-bars {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .organizador-dashboard .mini-bar-row {
            display: grid;
            grid-template-columns: 110px 1fr 44px;
            gap: 10px;
            align-items: center;
        }
        .organizador-dashboard .mini-bar-track {
            height: 10px;
            background: rgba(0,0,0,.06);
            border-radius: 999px;
            overflow: hidden;
        }
        .organizador-dashboard .mini-bar-fill {
            height: 100%;
            border-radius: 999px;
        }
        .organizador-dashboard .mini-bar-value {
            text-align: right;
            font-weight: 600;
            color: #334155;
        }
        .organizador-dashboard .status-vertical-chart {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(72px, 1fr));
            gap: 12px;
            align-items: end;
        }
        .organizador-dashboard .status-vertical-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            min-height: 150px;
        }
        .organizador-dashboard .status-vertical-track {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            width: 48px;
            height: 96px;
            background: rgba(0,0,0,.06);
            border-radius: 12px;
            overflow: hidden;
        }
        .organizador-dashboard .status-vertical-fill {
            width: 100%;
            border-radius: 12px 12px 0 0;
        }
        .organizador-dashboard .status-vertical-label {
            text-align: center;
            font-size: 12px;
            line-height: 1.2;
            min-height: 30px;
        }
        .organizador-dashboard .status-vertical-value {
            font-weight: 700;
            color: #334155;
        }
        .organizador-dashboard .muted-note {
            color: #6c757d;
            font-size: 12px;
        }
        .organizador-dashboard .rank-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,.06);
        }
        .organizador-dashboard .rank-row:last-child {
            border-bottom: 0;
        }
    </style>

    <div class="card organizador-dashboard">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('organizador_dashboard'); ?></h1>
                <div class="muted-note"><?php echo app_lang('organizador_dashboard_intro'); ?></div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        window.organizadorRefreshAfterSave = function () {
            location.reload();
        };
    });
</script>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('organizador_new_task'), array('class' => 'btn btn-primary', 'title' => app_lang('organizador_new_task'))); ?>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="row g-3 mb-3">
                <div class="col-lg-12">
                    <div class="p-4 rounded-4 insight-band">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <div class="mb-2">
                                    <span class="insight-pill <?php echo organizador_dashboard_badge_class($attention_severity); ?>">
                                        <i data-feather="activity" class="icon-16"></i>
                                        <?php echo esc($attention_label); ?>
                                    </span>
                                </div>
                                <h4 class="mb-1"><?php echo esc($attention_message); ?></h4>
                                <div class="muted-note"><?php echo sprintf(app_lang('organizador_dashboard_attention_score'), $attention_score); ?></div>
                            </div>
                            <div class="text-end">
                                <div class="muted-note mb-1"><?php echo app_lang('organizador_dashboard_line_label'); ?></div>
                                <div class="fw-bold font-22"><?php echo esc($attention_label); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <?php foreach ($summary_cards as $card) { ?>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="<?php echo esc(organizador_dashboard_link($card['filter'])); ?>" class="text-decoration-none">
                            <div class="card summary-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="muted-note"><?php echo esc($card['label']); ?></div>
                                            <div class="font-26 fw-bold <?php echo esc($card['class']); ?>"><?php echo (int) $card['count']; ?></div>
                                        </div>
                                        <div class="<?php echo esc($card['class']); ?>">
                                            <i data-feather="<?php echo esc($card['icon']); ?>" class="icon-22"></i>
                                        </div>
                                    </div>
                                    <div class="mt-3 muted-note"><?php echo esc($card['hint']); ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-12">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo app_lang('organizador_dashboard_alerts'); ?></h4>
                            <span class="badge bg-dark"><?php echo count($alerts); ?></span>
                        </div>
                        <div class="card-body">
                            <?php if ($alerts_visible) { ?>
                                <?php foreach ($alerts_visible as $alert) { ?>
                                    <div class="alert-row <?php echo organizador_dashboard_alert_class(get_array_value($alert, 'severity')); ?>">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div class="d-flex gap-3">
                                                <div class="mt-1">
                                                    <span class="badge rounded-circle <?php echo organizador_dashboard_badge_class(get_array_value($alert, 'severity')); ?>" style="width:38px;height:38px;line-height:24px;">
                                                        <i data-feather="<?php echo esc(get_array_value($alert, 'icon') ?: 'bell'); ?>" class="icon-16"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold mb-1"><?php echo esc(get_array_value($alert, 'title')); ?></div>
                                                    <div class="muted-note"><?php echo esc(get_array_value($alert, 'message')); ?></div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="badge <?php echo organizador_dashboard_badge_class(get_array_value($alert, 'severity')); ?> mb-2"><?php echo (int) get_array_value($alert, 'count'); ?></div>
                                                <div>
                                                    <a href="<?php echo esc(organizador_dashboard_link(get_array_value($alert, 'filter'))); ?>" class="btn btn-sm btn-outline-primary"><?php echo app_lang('organizador_view_tasks'); ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if (count($alerts) > count($alerts_visible)) { ?>
                                    <div class="text-end mt-2">
                                        <a href="<?php echo esc(organizador_dashboard_link('procrastination')); ?>" class="btn btn-sm btn-outline-primary"><?php echo app_lang('organizador_view_tasks'); ?></a>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="text-center text-muted py-4">
                                    <?php echo app_lang('organizador_dashboard_alerts_empty'); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('organizador_dashboard_procrastination_risk'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if ($procrastination_risks_visible) { ?>
                                <?php foreach ($procrastination_risks_visible as $task) { ?>
                                    <div class="smart-list-item">
                                        <div class="d-flex justify-content-between gap-3">
                                            <div>
                                                <div class="fw-bold"><?php echo esc(get_array_value($task, 'title')); ?></div>
                                                <div class="muted-note">
                                                    <?php echo esc(get_array_value($task, 'category')); ?> ·
                                                    <?php echo esc(get_array_value($task, 'assigned_to')); ?> ·
                                                    <?php echo sprintf(app_lang('organizador_dashboard_days_without_update'), (int) get_array_value($task, 'days_without_update')); ?>
                                                </div>
                                            </div>
                                            <span class="badge <?php echo organizador_dashboard_badge_class(get_array_value($task, 'risk_level')); ?>">
                                                <?php echo app_lang('organizador_risk_' . get_array_value($task, 'risk_level')); ?>
                                            </span>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge bg-secondary me-1"><?php echo app_lang('organizador_priority_' . get_array_value($task, 'priority')); ?></span>
                                            <span class="badge bg-light text-dark border"><?php echo esc(get_array_value($task, 'due_date')); ?></span>
                                        </div>
                                        <div class="mt-2 muted-note"><?php echo esc(get_array_value($task, 'reason')); ?></div>
                                        <div class="mt-2">
                                            <a href="<?php echo esc(get_array_value($task, 'url')); ?>" class="btn btn-sm btn-default"><?php echo app_lang('view'); ?></a>
                                            <?php if (\Organizador\Plugin::canEditTasks($login_user) || (int) get_array_value($task, 'created_by') === (int) $login_user->id) { ?>
                                                <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), app_lang('edit'), array('class' => 'btn btn-sm btn-outline-primary', 'title' => app_lang('edit'), 'data-post-id' => (int) get_array_value($task, 'id'))); ?>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if (count($procrastination_risks) > count($procrastination_risks_visible)) { ?>
                                    <div class="mt-3">
                                        <a href="<?php echo esc(organizador_dashboard_link('procrastination')); ?>" class="btn btn-sm btn-outline-primary"><?php echo app_lang('organizador_view_tasks'); ?></a>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="text-center text-muted py-4"><?php echo app_lang('organizador_dashboard_empty_state'); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card h-100 mb-3">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('organizador_dashboard_urgent_not_started'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if ($urgent_not_started_visible) { ?>
                                <?php foreach ($urgent_not_started_visible as $task) { ?>
                                    <div class="smart-list-item">
                                        <div class="fw-bold"><?php echo esc(get_array_value($task, 'title')); ?></div>
                                        <div class="muted-note"><?php echo esc(get_array_value($task, 'reason')); ?></div>
                                    </div>
                                <?php } ?>
                                <?php if (count($urgent_not_started) > count($urgent_not_started_visible)) { ?>
                                    <div class="mt-3">
                                        <a href="<?php echo esc(organizador_dashboard_link('urgent_not_started')); ?>" class="btn btn-sm btn-outline-danger"><?php echo app_lang('organizador_view_tasks'); ?></a>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="text-muted"><?php echo app_lang('organizador_dashboard_no_urgent'); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('organizador_dashboard_forgotten_tasks'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if ($forgotten_tasks_visible) { ?>
                                <?php foreach ($forgotten_tasks_visible as $task) { ?>
                                    <div class="smart-list-item">
                                        <div class="fw-bold"><?php echo esc(get_array_value($task, 'title')); ?></div>
                                        <div class="muted-note">
                                            <?php echo esc(get_array_value($task, 'category')); ?> ·
                                            <?php echo esc(get_array_value($task, 'assigned_to')); ?>
                                        </div>
                                        <div class="muted-note">
                                            <?php echo sprintf(app_lang('organizador_dashboard_created_at'), esc(get_array_value($task, 'created_at'))); ?>
                                        </div>
                                        <div class="muted-note"><?php echo esc(get_array_value($task, 'reason')); ?></div>
                                    </div>
                                <?php } ?>
                                <?php if (count($forgotten_tasks) > count($forgotten_tasks_visible)) { ?>
                                    <div class="mt-3">
                                        <a href="<?php echo esc(organizador_dashboard_link('forgotten')); ?>" class="btn btn-sm btn-outline-warning"><?php echo app_lang('organizador_view_tasks'); ?></a>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="text-muted"><?php echo app_lang('organizador_dashboard_no_forgotten'); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo app_lang('organizador_dashboard_priority_view'); ?></h4>
                            <span class="badge bg-light text-dark border"><?php echo array_sum((array) get_array_value($priority_chart, 'data')); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="mini-bars">
                                <?php
                                $priority_labels_arr = get_array_value($priority_chart, 'labels') ?: array();
                                $priority_values_arr = get_array_value($priority_chart, 'data') ?: array();
                                $priority_colors_arr = get_array_value($priority_chart, 'colors') ?: array();
                                $priority_total = array_sum($priority_values_arr);
                                foreach ($priority_labels_arr as $index => $priority_label) {
                                    $value = (int) get_array_value($priority_values_arr, $index);
                                    $percent = $priority_total > 0 ? round(($value / $priority_total) * 100) : 0;
                                    $color = get_array_value($priority_colors_arr, $index) ?: '#0d6efd';
                                ?>
                                    <div class="mini-bar-row">
                                        <div class="small fw-semibold"><?php echo esc($priority_label); ?></div>
                                        <div class="mini-bar-track"><div class="mini-bar-fill" style="width: <?php echo (int) $percent; ?>%; background: <?php echo esc($color); ?>;"></div></div>
                                        <div class="mini-bar-value"><?php echo $value; ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><?php echo app_lang('organizador_dashboard_status_view'); ?></h4>
                            <span class="badge bg-light text-dark border"><?php echo array_sum((array) get_array_value($status_chart, 'data')); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="status-vertical-chart">
                                <?php
                                $status_labels_arr = get_array_value($status_chart, 'labels') ?: array();
                                $status_values_arr = get_array_value($status_chart, 'data') ?: array();
                                $status_colors_arr = get_array_value($status_chart, 'colors') ?: array();
                                $status_total = array_sum($status_values_arr);
                                foreach ($status_labels_arr as $index => $status_label) {
                                    $value = (int) get_array_value($status_values_arr, $index);
                                    $percent = $status_total > 0 ? round(($value / $status_total) * 100) : 0;
                                    $color = get_array_value($status_colors_arr, $index) ?: '#0d6efd';
                                ?>
                                    <div class="status-vertical-item">
                                        <div class="status-vertical-value"><?php echo $value; ?></div>
                                        <div class="status-vertical-track">
                                            <div class="status-vertical-fill" style="height: <?php echo max(8, (int) $percent); ?>%; background: <?php echo esc($color); ?>;"></div>
                                        </div>
                                        <div class="status-vertical-label"><?php echo esc($status_label); ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('organizador_dashboard_category_backlog'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if ($category_ranking_visible) { ?>
                                <?php foreach ($category_ranking_visible as $category) { ?>
                                    <div class="rank-row">
                                        <div>
                                            <div class="fw-bold"><?php echo esc(get_array_value($category, 'label')); ?></div>
                                            <div class="muted-note">
                                                <?php echo sprintf(app_lang('organizador_dashboard_category_backlog_details'), (int) get_array_value($category, 'pending'), (int) get_array_value($category, 'overdue')); ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold"><?php echo (int) get_array_value($category, 'total'); ?></div>
                                            <div class="muted-note"><?php echo app_lang('organizador_dashboard_category_total'); ?></div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if (count($category_ranking) > count($category_ranking_visible)) { ?>
                                    <div class="mt-3">
                                        <a href="<?php echo esc(organizador_dashboard_link('overdue')); ?>" class="btn btn-sm btn-outline-primary"><?php echo app_lang('organizador_view_tasks'); ?></a>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="text-muted"><?php echo app_lang('organizador_dashboard_empty_state'); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo app_lang('organizador_dashboard_action_suggestions'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if ($suggestions_visible) { ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($suggestions_visible as $suggestion) { ?>
                                        <li class="mb-3 d-flex align-items-start gap-2">
                                            <span class="badge bg-success mt-1"><i data-feather="arrow-right" class="icon-14"></i></span>
                                            <div><?php echo esc($suggestion); ?></div>
                                        </li>
                                    <?php } ?>
                                </ul>
                                <?php if (count($suggestions) > count($suggestions_visible)) { ?>
                                    <div class="mt-3">
                                        <a href="<?php echo esc(organizador_dashboard_link('today')); ?>" class="btn btn-sm btn-outline-success"><?php echo app_lang('organizador_view_tasks'); ?></a>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="text-muted"><?php echo app_lang('organizador_dashboard_empty_state'); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
