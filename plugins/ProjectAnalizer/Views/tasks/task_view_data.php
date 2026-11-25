<?php
$progress = 0;
if ($total_sub_tasks) {
    $progress = round($completed_sub_tasks / $total_sub_tasks * 100);
}
?>

<div class="row">
    <div class="col-lg-4 order-lg-last">
        <div class="clearfix">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 mb15 task-title-right d-none">
                        <strong><?php echo $model_info->title; ?></strong>
                    </div>

                    <div class="d-flex m0">
                        <div class="flex-shrink-0">
                            <span class="avatar avatar-sm">
                                <img id="task-assigned-to-avatar" src="<?php echo get_avatar($model_info->assigned_to_avatar); ?>" alt="..." />
                            </span>
                        </div>
                        <div class="w-100 ps-2 pt5">
                            <div>
                                <?php echo get_update_task_info_anchor_data($model_info, "user", $can_edit_tasks, "", $show_assign_to_dropdown); ?>
                            </div>
                            <p>
                                <span class='badge badge-light mr5' title='Point'><?php echo get_update_task_info_anchor_data($model_info, "points", $can_edit_tasks); ?></span>

                                <?php
                                echo "<span class='badge' style='background:$model_info->status_color; '>" . get_update_task_info_anchor_data($model_info, "status", $can_edit_task_status) . "</span>";
                                ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($total_sub_tasks) { ?>
                        <div class="col-md-12 mb15 mt15">
                            <span class=""><?php echo $completed_sub_tasks . "/" . $total_sub_tasks; ?> <?php echo app_lang("sub_tasks_completed"); ?></span>
                            <div class="progress mt5" style="height: 6px;" title="<?php echo $progress; ?>%">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>%" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->project_id) { ?>
                        <div class="col-md-12 mb15 mt15">
                            <strong><?php echo app_lang('milestone') . ": "; ?></strong> <?php echo get_update_task_info_anchor_data($model_info, "milestone", $can_edit_tasks); ?>
                        </div>
                    <?php } ?>

                    <div class="col-md-12 mb15 <?php echo $model_info->project_id ? "" : "mt15"; ?>">
                        <strong><?php echo app_lang('start_date') . ": "; ?></strong> <?php echo get_update_task_info_anchor_data($model_info, "start_date", $can_edit_tasks); ?>
                        <?php
                        if ($show_time_with_task) {
                            echo get_update_task_info_anchor_data($model_info, "start_time", $can_edit_tasks);
                        }
                        ?>
                    </div>

                    <div class="col-md-12 mb15">
                        <strong><?php echo app_lang('deadline') . ": "; ?></strong> <?php echo get_update_task_info_anchor_data($model_info, "deadline", $can_edit_tasks); ?>
                        <?php
                        if ($show_time_with_task) {
                            echo get_update_task_info_anchor_data($model_info, "end_time", $can_edit_tasks);
                        }
                        ?>
                        <?php if ($model_info->deadline_milestone_title) { ?>
                            <span class="help task-deadline-milestone-tooltip" data-bs-toggle="tooltip" title="<?php echo app_lang('milestone') . ": " . $model_info->deadline_milestone_title; ?>"><i data-feather="help-circle" class="icon-16"></i></span>
                        <?php } ?>
                    </div>

                    <div class="col-md-12 mb15">
                        <strong><?php echo app_lang('priority') . ": "; ?></strong> <?php echo get_update_task_info_anchor_data($model_info, "priority", $can_edit_tasks); ?>
                    </div>

                    <div class="col-md-12 mb15">
                        <strong><?php echo app_lang('label') . ": " ?></strong><?php echo get_update_task_info_anchor_data($model_info, "labels", $can_edit_tasks, $labels); ?>
                    </div>

                    <div class="col-md-12 mb15">
                        <strong><?php echo app_lang('collaborators') . ": "; ?> </strong>
                        <div class="mt5">
                            <?php echo get_update_task_info_anchor_data($model_info, "collaborators", $can_edit_tasks, $collaborators); ?>
                        </div>
                    </div>

                    <?php if ($model_info->ticket_id && $model_info->project_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang("ticket") . ": "; ?> </strong> <?php echo anchor(get_uri("tickets/view/" . $model_info->ticket_id), get_ticket_id($model_info->ticket_id) . " - " . $model_info->ticket_title); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->recurring_task_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('created_from') . ": "; ?> </strong>
                            <?php
                            echo modal_anchor(get_uri("tasks/view"), app_lang("task") . " " . $model_info->recurring_task_id, array("title" => app_lang('task_info') . " #$model_info->recurring_task_id", "data-post-id" => $model_info->recurring_task_id, "data-modal-lg" => "1"));
                            ?>
                        </div>
                    <?php } ?>

                    <!--recurring info-->
                    <?php if ($model_info->recurring) { ?>

                        <?php
                        $recurring_stopped = false;
                        $recurring_cycle_class = "";
                        if ($model_info->no_of_cycles_completed > 0 && $model_info->no_of_cycles_completed == $model_info->no_of_cycles) {
                            $recurring_stopped = true;
                            $recurring_cycle_class = "text-danger";
                        }
                        ?>

                        <?php
                        $cycles = $model_info->no_of_cycles_completed . "/" . $model_info->no_of_cycles;
                        if (!$model_info->no_of_cycles) { //if not no of cycles, so it's infinity
                            $cycles = $model_info->no_of_cycles_completed . "/&#8734;";
                        }
                        ?>

                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang("repeat_every") . ": "; ?> </strong> <?php echo $model_info->repeat_every . " " . app_lang("interval_" . $model_info->repeat_type); ?>
                        </div>

                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang("cycles") . ": "; ?> </strong> <span class="<?php echo $recurring_cycle_class; ?>"><?php echo $cycles; ?></span>
                        </div>

                        <?php if (!$recurring_stopped && (int) $model_info->next_recurring_date) { ?>
                            <div class="col-md-12 mb15">
                                <strong><?php echo app_lang("next_recurring_date") . ": "; ?> </strong> <?php echo format_to_date($model_info->next_recurring_date, false); ?>
                            </div>
                        <?php } ?>

                    <?php } ?>

                    <?php if ($model_info->context == "project") { ?>
                        <div class="col-md-12 mb15">
                            <?php
                            if ($show_timer) {
                                echo view("tasks/task_timer");
                            }
                            ?>
                        </div>

                        <?php if (get_setting("module_project_timesheet") == "1" && $show_timesheet_info) { ?>
                            <div class="col-md-12 mb15">
                                <strong><?php echo app_lang("total_time_logged") . ": "; ?></strong>
                                <?php
                                echo ajax_anchor(get_uri("projects/task_timesheet/" . $model_info->id . "/" . $model_info->project_id), $total_task_hours, array("data-real-target" => "#task-timesheet", "class" => "strong"));
                                ?>
                            </div>
                            <div class="col-md-12 mb15">
                                <div id="task-timesheet"></div>
                            </div>

                        <?php } ?>
                    <?php } ?>

                    <?php
                    $pinned_status = "hide";
                    if (count($pinned_comments)) {
                        $pinned_status = "";
                    }
                    ?>
                    <div class="col-md-12 mb15 <?php echo $pinned_status; ?>" id="pinned-comment">
                        <div class="mb5">
                            <strong><?php echo app_lang("pinned_comments") . ": "; ?> </strong>
                        </div>
                        <?php echo view("lib/pin_comments/comments_list"); ?>
                    </div>

                    <?php if (can_access_reminders_module()) { ?>
                        <div class="col-md-12 mb15" id="task-reminders">
                            <div class="mb15"><strong><?php echo app_lang("reminders") . " (" . app_lang('private') . ")" . ": "; ?> </strong></div>
                            <?php echo view("reminders/reminders_view_data", array("task_id" => $model_info->id, "hide_form" => true, "reminder_view_type" => "task")); ?>
                        </div>
                    <?php } ?>

                    <?php app_hooks()->do_action('app_hook_task_view_right_panel_extension'); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8 mb15">
        <div class="clearfix">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 mb15 task-title-left">
                        <strong><?php echo $model_info->title; ?></strong>
                    </div>

                    <?php if ($model_info->parent_task_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang("main_task") . ": "; ?></strong><?php echo modal_anchor(get_uri("tasks/view"), $parent_task_title, array("title" => app_lang('task_info') . " #$model_info->parent_task_id", "data-post-id" => $model_info->parent_task_id, "data-modal-lg" => "1", "id" => "parent-task-link")); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->description) { ?>
                        <div class="col-md-12 mb15 text-wrap">
                            <?php echo $model_info->description ? custom_nl2br(link_it(process_images_from_content($model_info->description))) : ""; ?>
                        </div>
                    <?php } ?>

                    <div class="col-md-12 mb15 b-t pt15 task-project-materials-section">
                        <div class="task-materials-heading">
                            <strong><i data-feather="package" class="icon-16"></i> Materiais da tarefa</strong>
                            <span class="badge bg-light text-dark"><?php echo count((array) $project_material_allocations); ?></span>
                        </div>

                        <?php if (!empty($project_material_allocations)) { ?>
                            <div class="table-responsive mt10">
                                <table class="table table-sm task-materials-table mb0">
                                    <thead><tr><th>Material</th><th class="text-end">Necessário</th><th class="text-end">Requisitado</th><th class="text-end">Pendente</th><?php if ($can_manage_project_task_materials) { ?><th class="text-end">Ações</th><?php } ?></tr></thead>
                                    <tbody>
                                    <?php foreach ($project_material_allocations as $allocation) {
                                        $requested_quantity = (float) $allocation->requested_quantity;
                                        $required_quantity = (float) $allocation->quantity;
                                        $pending_quantity = max(0, $required_quantity - $requested_quantity);
                                    ?>
                                        <tr>
                                            <td><strong><?php echo esc($allocation->description); ?></strong><div class="small text-off"><?php echo esc($allocation->unit); ?></div></td>
                                            <td class="text-end"><?php echo number_format($required_quantity, 3, ",", "."); ?></td>
                                            <td class="text-end"><?php echo number_format($requested_quantity, 3, ",", "."); ?></td>
                                            <td class="text-end"><span class="badge bg-<?php echo $pending_quantity > 0 ? "warning" : "success"; ?>"><?php echo number_format($pending_quantity, 3, ",", "."); ?></span></td>
                                            <?php if ($can_manage_project_task_materials) { ?>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-default btn-sm remove-task-project-material" data-allocation-id="<?php echo (int) $allocation->id; ?>" <?php echo $requested_quantity > 0 ? 'disabled title="Cancele a requisição antes de remover"' : 'title="Remover material da tarefa"'; ?>>
                                                        <i data-feather="unlink" class="icon-14"></i> Remover
                                                    </button>
                                                </td>
                                            <?php } ?>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="task-materials-empty mt10"><i data-feather="package" class="icon-18"></i> Nenhum material associado a esta tarefa.</div>
                        <?php } ?>

                        <?php if ($can_manage_project_task_materials && !empty($available_project_materials)) { ?>
                            <form id="task-project-material-add-form" class="task-material-add-form mt15">
                                <input type="hidden" name="project_id" value="<?php echo (int) $project_id; ?>">
                                <input type="hidden" name="task_id" value="<?php echo (int) $task_id; ?>">
                                <div class="task-material-select">
                                    <label>Adicionar material</label>
                                    <select name="project_material_id" id="task-project-material-select" class="form-control select2" data-placeholder="Selecione um material" required>
                                        <option value=""></option>
                                        <?php foreach ($available_project_materials as $project_material) { ?>
                                            <option value="<?php echo (int) $project_material->id; ?>" data-available="<?php echo number_format((float) $project_material->available_quantity, 4, ".", ""); ?>" data-unit="<?php echo esc($project_material->unit); ?>">
                                                <?php echo esc($project_material->description); ?> — disponível: <?php echo number_format((float) $project_material->available_quantity, 3, ",", "."); ?> <?php echo esc($project_material->unit); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="task-material-quantity">
                                    <label>Quantidade necessária</label>
                                    <input type="text" name="quantity" class="form-control" inputmode="decimal" placeholder="0,000" required>
                                    <div id="task-material-available-hint" class="small text-off"></div>
                                </div>
                                <div class="task-material-add-button"><button type="submit" class="btn btn-primary"><i data-feather="plus-circle" class="icon-16"></i> Adicionar</button></div>
                            </form>
                        <?php } elseif ($can_manage_project_task_materials && empty($available_project_materials)) { ?>
                            <div class="small text-off mt10">Todos os materiais disponíveis do projeto já estão associados ou não possuem saldo.</div>
                        <?php } ?>

                        <?php if (!empty($task_tools)) { ?>
                            <div class="mt15"><strong><?php echo app_lang("tools"); ?>:</strong><ul class="mb0"><?php foreach ($task_tools as $resource) { ?><li><?php echo esc($resource->tool_name); ?> — <?php echo esc($resource->quantity); ?><?php if ($resource->requirement) { ?> (<?php echo esc($resource->requirement); ?>)<?php } ?></li><?php } ?></ul></div>
                        <?php } ?>
                    </div>

                    <?php if ($model_info->project_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('project') . ": "; ?> </strong> <?php echo anchor(get_uri("projects/view/" . $model_info->project_id), $model_info->project_title); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->client_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('client') . ": "; ?> </strong> <?php echo anchor(get_uri("clients/view/" . $model_info->client_id), $model_info->company_name); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->lead_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('lead') . ": "; ?> </strong> <?php echo anchor(get_uri("leads/view/" . $model_info->lead_id), $model_info->company_name); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->invoice_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('invoice') . ": "; ?> </strong> <?php echo anchor(get_uri("invoices/view/" . $model_info->invoice_id), $model_info->invoice_display_id); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->estimate_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('estimate') . ": "; ?> </strong> <?php echo anchor(get_uri("estimates/view/" . $model_info->estimate_id), get_estimate_id($model_info->estimate_id)); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->order_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('order') . ": "; ?> </strong> <?php echo anchor(get_uri("orders/view/" . $model_info->order_id), get_order_id($model_info->order_id)); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->contract_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('contract') . ": "; ?> </strong> <?php echo anchor(get_uri("contracts/view/" . $model_info->contract_id), $model_info->contract_title); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->proposal_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('proposal') . ": "; ?> </strong> <?php echo anchor(get_uri("proposals/view/" . $model_info->proposal_id), get_proposal_id($model_info->proposal_id)); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->subscription_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('subscription') . ": "; ?> </strong> <?php echo anchor(get_uri("subscriptions/view/" . $model_info->subscription_id), $model_info->subscription_title); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->expense_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang('expense') . ": "; ?> </strong> <?php echo modal_anchor(get_uri("expenses/expense_details"), ($model_info->expense_title ? $model_info->expense_title : format_to_date($model_info->expense_date, false)), array("title" => app_lang("expense_details"), "data-post-id" => $model_info->expense_id, "data-modal-lg" => "1")); ?>
                        </div>
                    <?php } ?>

                    <?php if ($model_info->ticket_id && !$model_info->project_id) { ?>
                        <div class="col-md-12 mb15">
                            <strong><?php echo app_lang("ticket") . ": "; ?> </strong> <?php echo anchor(get_uri("tickets/view/" . $model_info->ticket_id), get_ticket_id($model_info->ticket_id) . " - " . $model_info->ticket_title); ?>
                        </div>
                    <?php } ?>

                    <?php
                    if (count($custom_fields_list)) {
                        foreach ($custom_fields_list as $data) {
                            if ($data->value) {
                    ?>
                                <div class="col-md-12 mb15 text-break">
                                    <strong><?php echo $data->title . ": "; ?> </strong> <?php echo view("custom_fields/output_" . $data->field_type, array("value" => $data->value)); ?>
                                </div>
                    <?php
                            }
                        }
                    }
                    ?>

                    <!--checklist-->
                    <?php echo form_open(get_uri("tasks/save_checklist_item"), array("id" => "checklist_form", "class" => "general-form", "role" => "form")); ?>
                    <div class="col-md-12 mb15 b-t">
                        <div class="pb10 pt10 clearfix">
                            <div class="float-start">
                                <strong class="float-start mr10"><?php echo app_lang("checklist"); ?></strong><span class="chcklists_status_count">0</span><span>/</span><span class="chcklists_count"></span>
                            </div>
                            <?php if ($can_edit_tasks) { ?>
                                <div class="float-end">
                                    <div class="form-check form-switch form-check-reverse">
                                        <input class="form-check-input" type="checkbox" role="switch" id="checklist-sortable-switch">
                                        <label class="form-check-label" for="checklist-sortable-switch"><?php echo app_lang("sortable"); ?></label>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />
                        <input type="hidden" id="is_checklist_group" name="is_checklist_group" value="" />

                        <div class="checklist-items" id="checklist-items">

                        </div>
                        <?php if ($can_edit_tasks) { ?>
                            <?php if (!empty($checklist_templates) || !empty($checklist_groups)) { ?>
                                <div class="mb5 btn-group checklist-options-panel hide" role="group">
                                    <button id="type-new-item-button" type="button" class="btn btn-default checklist_button active"> <?php echo app_lang('type_new_item'); ?></button>
                                    <?php if (!empty($checklist_templates)) { ?>
                                        <button id="select-from-template-button" type="button" class="btn btn-default checklist_button"> <?php echo app_lang('select_from_template'); ?></button>
                                    <?php } ?>
                                    <?php if (!empty($checklist_groups)) { ?>
                                        <button id="select-from-checklist-group-button" type="button" class="btn btn-default checklist_button"> <?php echo app_lang('select_from_checklist_group'); ?></button>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                            <div class="form-group">
                                <div class="mt5 p0">
                                    <?php
                                    echo form_input(array(
                                        "id" => "checklist-add-item",
                                        "name" => "checklist-add-item",
                                        "class" => "form-control",
                                        "placeholder" => app_lang('add_item'),
                                        "data-rule-required" => true,
                                        "data-msg-required" => app_lang("field_required"),
                                        "autocomplete" => "off"
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div class="mb10 p0 checklist-options-panel hide">
                                <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('add'); ?></button>
                                <button id="checklist-options-panel-close" type="button" class="btn btn-default ms-2"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('cancel'); ?></button>
                            </div>
                        <?php } ?>
                    </div>
                    <?php echo form_close(); ?>

                    <!--Sub tasks-->
                    <?php echo form_open(get_uri("tasks/save_sub_task"), array("id" => "sub_task_form", "class" => "general-form", "role" => "form")); ?>
                    <div class="col-md-12 mb15 b-t">
                        <div class="pb10 pt10">
                            <strong><?php echo app_lang("sub_tasks"); ?></strong>
                        </div>

                        <?php
                        foreach ($contexts as $context) {
                            if ($context == "general") {
                                continue;
                            }
                            $context_id_key = $context . "_id";
                        ?>
                            <input type="hidden" name="<?php echo $context_id_key; ?>" value="<?php echo $model_info->$context_id_key; ?>" />
                        <?php } ?>

                        <input type="hidden" name="context" value="<?php echo $model_info->context; ?>" />
                        <input type="hidden" name="parent_task_id" value="<?php echo $task_id; ?>" />
                        <input type="hidden" name="milestone_id" value="<?php echo $model_info->milestone_id; ?>" />

                        <div class="checklist-items" id="sub-tasks">

                        </div>
                        <?php if ($can_create_tasks) { ?>
                            <div class="form-group">
                                <div class="mt5 col-md-12 p0">
                                    <?php
                                    echo form_input(array(
                                        "id" => "sub-task-title",
                                        "name" => "sub-task-title",
                                        "class" => "form-control",
                                        "placeholder" => app_lang('create_a_sub_task'),
                                        "data-rule-required" => true,
                                        "data-msg-required" => app_lang("field_required")
                                    ));
                                    ?>
                                </div>
                            </div>
                            <div id="sub-task-options-panel" class="col-md-12 mb15 p0 hide">
                                <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('create'); ?></button>
                                <button id="sub-task-options-panel-close" type="button" class="btn btn-default ml10"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('cancel'); ?></button>
                            </div>
                        <?php } ?>
                    </div>
                    <?php echo form_close(); ?>

                    <!--Task dependency-->
                    <?php if ($can_edit_tasks && $model_info->context !== "general") { ?>
                        <div class="col-md-12 mb15">
                            <span class="dropdown">
                                <button class="btn btn-default dropdown-toggle btn-border" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                                    <i data-feather="shuffle" class="icon-16"></i> <?php echo app_lang('add_dependency'); ?>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li role="presentation"><?php echo js_anchor(app_lang("this_task_blocked_by"), array("class" => "add-dependency-btn dropdown-item", "data-dependency_type" => "blocked_by")); ?></li>
                                    <li role="presentation"><?php echo js_anchor(app_lang("this_task_blocking"), array("class" => "add-dependency-btn dropdown-item", "data-dependency_type" => "blocking")); ?></li>
                                </ul>
                            </span>
                        </div>
                    <?php } ?>

                    <div class="col-md-12 mb15 <?php echo ($blocked_by || $blocking) ? "" : "hide"; ?>" id="dependency-area">
                        <div class="pb10 pt10">
                            <strong><?php echo app_lang("dependency"); ?></strong>
                        </div>

                        <div class="p10 list-group-item mb15 dependency-section <?php echo $blocked_by ? "" : "hide"; ?>" id="blocked-by-area">
                            <div class="pb10"><strong><?php echo app_lang("blocked_by"); ?></strong></div>
                            <div id="blocked-by-tasks"><?php echo $blocked_by; ?></div>
                        </div>

                        <div class="p10 list-group-item mb15 dependency-section <?php echo $blocking ? "" : "hide"; ?>" id="blocking-area">
                            <div class="pb10"><strong><?php echo app_lang("blocking"); ?></strong></div>
                            <div id="blocking-tasks"><?php echo $blocking; ?></div>
                        </div>

                        <?php echo form_open(get_uri("tasks/save_dependency_tasks"), array("id" => "dependency_tasks_form", "class" => "general-form hide", "role" => "form")); ?>

                        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />

                        <div class="form-group mb0">
                            <div class="mt5 col-md-12 p0">
                                <?php
                                echo form_input(array(
                                    "id" => "dependency_task",
                                    "name" => "dependency_task",
                                    "class" => "form-control validate-hidden",
                                    "placeholder" => app_lang('tasks'),
                                    "data-rule-required" => true,
                                    "data-msg-required" => app_lang("field_required"),
                                ));
                                ?>
                            </div>
                        </div>

                        <div class="p0 mt10">
                            <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('add'); ?></button>
                            <button type="button" class="dependency-tasks-close btn btn-default"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('cancel'); ?></button>
                        </div>

                        <?php echo form_close(); ?>
                    </div>

                    <!--Task comment section-->
                    <div class="clearfix">
                        <div class="b-t pt10 list-container">
                            <?php if ($can_comment_on_tasks) { ?>
                                <?php echo view("projects/comments/comment_form"); ?>
                            <?php } ?>
                            <?php echo view("projects/comments/comment_list"); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($login_user->user_type === "staff") { ?>
    <div class="box-title"><span><?php echo app_lang("activity"); ?></span></div>
    <div class="pl15 pr15 mt15 list-container project-activity-logs-container">
        <?php echo activity_logs_widget(array("limit" => 20, "offset" => 0, "log_type" => "task", "log_type_id" => $model_info->id)); ?>
    </div>
<?php } ?>

<style>
.task-materials-heading{display:flex;align-items:center;gap:8px}
.task-materials-table th{font-size:11px;color:#667085}
.task-materials-table td{vertical-align:middle}
.task-materials-empty{padding:14px;border:1px dashed #d9dee5;border-radius:6px;background:#fafbfc;color:#7b8490}
.task-material-add-form{display:grid;grid-template-columns:minmax(260px,1fr) 170px auto;gap:10px;align-items:end;padding:12px;border:1px solid #e5e9ef;border-radius:7px;background:#f8f9fa}
.task-material-add-form label{display:block;margin-bottom:5px;font-size:11px;font-weight:600}
.task-material-add-button .btn{height:38px}
#task-project-material-select+.select2-container{width:100%!important}
@media(max-width:767px){.task-material-add-form{grid-template-columns:1fr}.task-material-add-button .btn{width:100%}}
</style>
<script>
(function initializeTaskProjectMaterials(attempt) {
    if (!window.jQuery || !window.jQuery.fn) {
        if (attempt < 100) window.setTimeout(function () { initializeTaskProjectMaterials(attempt + 1); }, 50);
        return;
    }
    var $ = window.jQuery;
    var $materialSelect = $("#task-project-material-select");

    if ($materialSelect.length && $.fn.select2 && !$materialSelect.data("select2") && !$materialSelect.hasClass("select2-hidden-accessible")) {
        $materialSelect.select2({width: "100%", dropdownParent: $("#ajaxModal")});
    }

    $materialSelect.off("change.taskMaterials").on("change.taskMaterials", function () {
        var $option = $(this).find("option:selected");
        var available = $option.data("available");
        var unit = $option.data("unit") || "";
        $("#task-material-available-hint").text(available ? "Disponível no projeto: " + available + " " + unit : "");
    });

    $("#task-project-material-add-form").off("submit.taskMaterials").on("submit.taskMaterials", function (event) {
        event.preventDefault();
        var $form = $(this), $button = $form.find("button[type=submit]");
        $button.prop("disabled", true);
        appAjaxRequest({
            url: "<?php echo get_uri('projectanalizer/project_materials/allocate'); ?>",
            type: "POST", dataType: "json", data: $form.serialize(),
            success: function (result) {
                if (result && result.success) {
                    appAlert.success(result.message);
                    window.location.reload();
                } else {
                    appAlert.error((result && result.message) || "Não foi possível adicionar o material.");
                    $button.prop("disabled", false);
                }
            },
            error: function (xhr) {
                appAlert.error((xhr.responseJSON && xhr.responseJSON.message) || "Não foi possível adicionar o material.");
                $button.prop("disabled", false);
            }
        });
    });

    $(".remove-task-project-material").off("click.taskMaterials").on("click.taskMaterials", function () {
        var $button = $(this);
        if (!confirm("Deseja remover este material da tarefa?")) return;
        $button.prop("disabled", true);
        appAjaxRequest({
            url: "<?php echo get_uri('projectanalizer/project_materials/remove_allocation'); ?>",
            type: "POST", dataType: "json",
            data: {project_id: "<?php echo (int) $project_id; ?>", allocation_id: $button.data("allocation-id")},
            success: function (result) {
                if (result && result.success) {
                    appAlert.success(result.message);
                    window.location.reload();
                } else {
                    appAlert.error((result && result.message) || "Não foi possível remover o material.");
                    $button.prop("disabled", false);
                }
            },
            error: function (xhr) {
                appAlert.error((xhr.responseJSON && xhr.responseJSON.message) || "Não foi possível remover o material.");
                $button.prop("disabled", false);
            }
        });
    });
})(0);
</script>
