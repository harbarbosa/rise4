<style>
    .organizador-dashboard-kanban {
        overflow-x: auto;
        overflow-y: hidden;
    }

    .organizador-dashboard-kanban-grid {
        display: flex;
        gap: 16px;
        min-width: max-content;
        padding-bottom: 4px;
    }

    .organizador-dashboard-kanban-col {
        width: 300px;
        max-width: 300px;
        background: #f8f9fb;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
    }

    .organizador-dashboard-kanban-head {
        padding: 12px 14px;
        background: #ffffff;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
    }

    .organizador-dashboard-kanban-count {
        background: #eef2f7;
        color: #495057;
        border-radius: 999px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: 600;
    }

    .organizador-dashboard-kanban-list {
        padding: 12px;
        max-height: 360px;
        overflow-y: auto;
    }

    .organizador-dashboard-kanban-item {
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 10px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    .organizador-dashboard-kanban-item:last-child {
        margin-bottom: 0;
    }

    .organizador-dashboard-kanban-empty {
        color: #6c757d;
        font-size: 13px;
        text-align: center;
        padding: 22px 12px;
    }

    .organizador-dashboard-priority {
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

    .organizador-dashboard-priority-low {
        background: #e8f1ff;
        color: #0d6efd;
    }

    .organizador-dashboard-priority-medium {
        background: #eef2ff;
        color: #4f46e5;
    }

    .organizador-dashboard-priority-high {
        background: #fff4db;
        color: #d97706;
    }

    .organizador-dashboard-priority-urgent {
        background: #ffe3e3;
        color: #dc3545;
    }
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0"><?php echo app_lang('organizador_dashboard_kanban_widget'); ?></h4>
        <div class="d-flex gap-2">
            <?php if (!empty($can_edit)) { ?>
                <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i>", array('class' => 'btn btn-default', 'title' => app_lang('organizador_new_task'))); ?>
            <?php } ?>
            <?php echo anchor(get_uri('organizador/kanban'), app_lang('organizador_open_full_kanban'), array('class' => 'btn btn-primary')); ?>
        </div>
    </div>
    <div class="card-body">
        <div class="organizador-dashboard-kanban">
            <div class="organizador-dashboard-kanban-grid">
                <?php foreach ($phases as $phase) { ?>
                    <?php
                    $status_key = $phase->key_name;
                    $tasks = get_array_value($tasks_list, $status_key) ?: array();
                    ?>
                    <div class="organizador-dashboard-kanban-col">
                        <div class="organizador-dashboard-kanban-head d-flex justify-content-between align-items-center" style="border-top: 3px solid <?php echo esc($phase->color ?: '#2e4053'); ?>;">
                            <span><?php echo esc($phase->title); ?></span>
                            <span class="organizador-dashboard-kanban-count"><?php echo count($tasks); ?></span>
                        </div>
                        <div class="organizador-dashboard-kanban-list">
                            <?php if ($tasks) { ?>
                                <?php foreach ($tasks as $task) { ?>
                                    <?php $priority_key = in_array($task->priority, array('low', 'medium', 'high', 'urgent')) ? $task->priority : 'medium'; ?>
                                    <div class="organizador-dashboard-kanban-item">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <?php echo modal_anchor(get_uri('organizador/tasks/view'), esc($task->title), array('class' => 'font-weight-bold text-start flex-grow-1', 'title' => app_lang('organizador_task_details'), 'data-post-id' => $task->id)); ?>
                                            <span class="organizador-dashboard-priority organizador-dashboard-priority-<?php echo esc($priority_key); ?>"><?php echo app_lang('organizador_priority_' . $priority_key); ?></span>
                                        </div>
                                        <?php if (!empty($task->tags_html)) { ?>
                                            <div class="mt-2 text-wrap"><?php echo $task->tags_html; ?></div>
                                        <?php } ?>
                                        <div class="small text-muted mt-2">
                                            <?php echo $task->due_date ? format_to_datetime($task->due_date) : '-'; ?>
                                        </div>
                                        <?php if (!empty($task->assigned_to_name)) { ?>
                                            <div class="small text-muted"><?php echo esc($task->assigned_to_name); ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="organizador-dashboard-kanban-empty"><?php echo app_lang('no_data_found'); ?></div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        if (window.feather) {
            feather.replace();
        }
    });
</script>
