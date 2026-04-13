<?php
load_js(array(
    "assets/js/fullcalendar/fullcalendar.min.js",
    "assets/js/fullcalendar/locales-all.min.js"
));
?>

<div id="page-content" class="page-wrapper clearfix">
    <style>
        .organizador-priority-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            white-space: nowrap;
            line-height: 1;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .organizador-priority-low {
            background: #e8f1ff;
            color: #0d6efd;
        }
        .organizador-priority-medium {
            background: #eef2ff;
            color: #4f46e5;
        }
        .organizador-priority-high {
            background: #fff4db;
            color: #d97706;
        }
        .organizador-priority-urgent {
            background: #ffe3e3;
            color: #dc3545;
        }
    </style>
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('organizador_kanban'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('organizador_new_task'), array('class' => 'btn btn-primary', 'title' => app_lang('organizador_new_task'))); ?>
            </div>
        </div>
        <div class="card-body">
            <div id="organizador-kanban-wrapper">
                <ul id="organizador-kanban-container" class="kanban-container clearfix">
                    <?php foreach ($phases as $phase) { ?>
                        <?php
                        $status_key = $phase->key_name;
                        $status_label = $phase->title;
                        $tasks = isset($tasks_list[$status_key]) ? $tasks_list[$status_key] : array();
                        ?>
                        <li class="kanban-col">
                            <div class="kanban-col-title" style="border-bottom: 3px solid <?php echo esc($phase->color ?: '#2e4053'); ?>;">
                                <?php echo esc($status_label); ?>
                                <span class="kanban-item-count <?php echo esc($status_key); ?>-task-count float-end"><?php echo count($tasks); ?></span>
                            </div>
                            <div class="kanban-item-list" id="organizador-kanban-<?php echo esc($status_key); ?>" data-status_id="<?php echo esc($status_key); ?>">
                                <?php foreach ($tasks as $task) { ?>
                                    <div class="kanban-item" data-id="<?php echo $task->id; ?>" data-sort="<?php echo (int) $task->position; ?>">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <?php echo modal_anchor(get_uri('organizador/tasks/view'), esc($task->title), array('class' => 'font-bold flex-grow-1 text-start', 'data-post-id' => $task->id)); ?>
                                            <?php
                                                $priority_key = in_array($task->priority, array('low', 'medium', 'high', 'urgent')) ? $task->priority : 'medium';
                                            ?>
                                            <span class="organizador-priority-pill organizador-priority-<?php echo esc($priority_key); ?>"><?php echo app_lang('organizador_priority_' . $priority_key); ?></span>
                                        </div>
                                        <?php if (!empty($task->tags_html)) { ?>
                                            <div class="mt5 text-wrap"><?php echo $task->tags_html; ?></div>
                                        <?php } ?>
                                        <div class="small text-muted"><?php echo $task->due_date ? format_to_date($task->due_date) : '-'; ?></div>
                                        <div class="mt10">
                                            <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('edit'), 'data-post-id' => $task->id)); ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        window.organizadorRefreshAfterSave = function () {
            location.reload();
        };

        var kanbanContainerWidth = $("#organizador-kanban-container").width();

        function adjustKanbanLayout() {
            if (!$("#organizador-kanban-container").length) {
                return false;
            }

            var totalColumns = "<?php echo count($phases); ?>";
            var columnWidth = (335 * totalColumns) + 5;
            if (isMobile()) {
                columnWidth = (230 * totalColumns) + 5;
            }

            if (columnWidth > kanbanContainerWidth) {
                $("#organizador-kanban-container").css({width: columnWidth + "px"});
            } else {
                $("#organizador-kanban-container").css({width: "100%"});
            }

            if ($("#organizador-kanban-wrapper")[0].offsetWidth < $("#organizador-kanban-wrapper")[0].scrollWidth) {
                $("#organizador-kanban-wrapper").css("overflow-x", "scroll");
            } else {
                $("#organizador-kanban-wrapper").css("overflow-x", "hidden");
            }

            var columnHeight = $(window).height() - $(".kanban-item-list").offset().top - 57;
            if (isMobile()) {
                columnHeight = $(window).height() - 30;
            }

            $(".kanban-item-list").height(columnHeight);
            $(".kanban-item-list").each(function () {
                if ($(this)[0].offsetHeight < $(this)[0].scrollHeight) {
                    $(this).css("overflow-y", "scroll");
                } else {
                    $(this).css("overflow-y", "hidden");
                }
            });
        }

        adjustKanbanLayout();
        $(window).resize(function () {
            adjustKanbanLayout();
        });

        $(".kanban-item-list").each(function () {
            var id = this.id;
            Sortable.create($("#" + id)[0], {
                animation: 150,
                group: "organizador-kanban",
                onAdd: function (e) {
                    adjustKanbanLayout();
                    var status_id = $(e.item).closest(".kanban-item-list").attr("data-status_id");
                    $.post('<?php echo_uri("organizador/tasks/update_status"); ?>', {
                        id: $(e.item).attr("data-id"),
                        status: status_id,
                        position: e.newIndex
                    });
                    var $count = $("." + status_id + "-task-count");
                    $count.html(($count.html() * 1) + 1);
                },
                onUpdate: function (e) {
                    adjustKanbanLayout();
                    $.post('<?php echo_uri("organizador/tasks/update_status"); ?>', {
                        id: $(e.item).attr("data-id"),
                        status: $(e.item).closest(".kanban-item-list").attr("data-status_id"),
                        position: e.newIndex
                    });
                }
            });
        });
    });
</script>
