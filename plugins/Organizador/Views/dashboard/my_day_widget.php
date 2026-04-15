<style>
    .organizador-my-day-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    .organizador-my-day-stat {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 14px;
        background: #fff;
    }

    .organizador-my-day-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .organizador-my-day-value {
        font-size: 28px;
        line-height: 1;
        font-weight: 700;
        color: #1f2937;
        margin-top: 8px;
    }

    .organizador-my-day-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .organizador-my-day-item {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 14px;
        background: #fff;
    }

    .organizador-my-day-empty {
        border: 1px dashed #ced4da;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        color: #6c757d;
        background: #f8f9fb;
    }

    .organizador-my-day-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 22px;
        padding: 0 8px;
        border-radius: 999px;
        white-space: nowrap;
        font-size: 11px;
        font-weight: 600;
    }

    .organizador-my-day-priority-low {
        background: #e8f1ff;
        color: #0d6efd;
    }

    .organizador-my-day-priority-medium {
        background: #eef2ff;
        color: #4f46e5;
    }

    .organizador-my-day-priority-high {
        background: #fff4db;
        color: #d97706;
    }

    .organizador-my-day-priority-urgent {
        background: #ffe3e3;
        color: #dc3545;
    }

    @media (max-width: 991px) {
        .organizador-my-day-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<?php
$summary = $summary ?? array();
$focus_tasks = $focus_tasks ?? array();
$stat_cards = array(
    array('label' => app_lang('organizador_dashboard_overdue'), 'value' => (int) get_array_value($summary, 'overdue'), 'class' => 'text-danger', 'filter' => 'overdue'),
    array('label' => app_lang('organizador_dashboard_today'), 'value' => (int) get_array_value($summary, 'today'), 'class' => 'text-primary', 'filter' => 'today'),
    array('label' => app_lang('organizador_dashboard_urgent'), 'value' => (int) get_array_value($summary, 'urgent'), 'class' => 'text-danger', 'filter' => 'priority=urgent'),
    array('label' => app_lang('organizador_dashboard_procrastination'), 'value' => (int) get_array_value($summary, 'procrastination'), 'class' => 'text-warning', 'filter' => 'procrastination'),
);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0"><?php echo app_lang('organizador_dashboard_my_day_widget'); ?></h4>
        <?php echo anchor(get_uri('organizador/tasks'), app_lang('see_more'), array('class' => 'btn btn-primary')); ?>
    </div>
    <div class="card-body">
        <div class="organizador-my-day-grid">
            <?php foreach ($stat_cards as $stat) { ?>
                <?php
                $url = get_uri('organizador/tasks');
                if ($stat['filter'] === 'priority=urgent') {
                    $url .= '?priority=urgent';
                } else {
                    $url .= '?quick_filter=' . $stat['filter'];
                }
                ?>
                <a href="<?php echo $url; ?>" class="text-decoration-none">
                    <div class="organizador-my-day-stat">
                        <div class="organizador-my-day-label"><?php echo esc($stat['label']); ?></div>
                        <div class="organizador-my-day-value <?php echo esc($stat['class']); ?>"><?php echo (int) $stat['value']; ?></div>
                    </div>
                </a>
            <?php } ?>
        </div>

        <?php if ($focus_tasks) { ?>
            <div class="organizador-my-day-list">
                <?php foreach ($focus_tasks as $task) { ?>
                    <?php $priority_key = in_array($task['priority'], array('low', 'medium', 'high', 'urgent')) ? $task['priority'] : 'medium'; ?>
                    <div class="organizador-my-day-item">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <?php echo modal_anchor(get_uri('organizador/tasks/view'), esc($task['title']), array('class' => 'font-weight-bold text-start flex-grow-1', 'title' => app_lang('organizador_task_details'), 'data-post-id' => $task['id'])); ?>
                            <span class="organizador-my-day-pill organizador-my-day-priority-<?php echo esc($priority_key); ?>"><?php echo app_lang('organizador_priority_' . $priority_key); ?></span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge rounded-pill" style="background: <?php echo esc($task['status_color']); ?>;"><?php echo esc($task['status_title']); ?></span>
                            <span class="text-muted small"><?php echo esc($task['assigned_to']); ?></span>
                            <span class="text-muted small"><?php echo esc($task['due_date']); ?></span>
                        </div>
                        <?php if (!empty($task['reason'])) { ?>
                            <div class="small text-muted mt-2"><?php echo esc($task['reason']); ?></div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="organizador-my-day-empty"><?php echo app_lang('organizador_dashboard_my_day_empty'); ?></div>
        <?php } ?>
    </div>
</div>
