<?php

namespace ProjectAnalizer\Controllers;

use App\Controllers\Security_Controller;
use ProjectAnalizer\Libraries\ProjectAnalizerEvolutionService;
use ProjectAnalizer\Libraries\ProjectAnalizerCashflowService;
use ProjectAnalizer\Models\Project_reschedule_log_model;
use ProjectAnalizer\Models\Task_costs_model;
use ProjectAnalizer\Models\Task_metrics_model;
use ProjectAnalizer\Models\Cost_realized_model;
use ProjectAnalizer\Models\Project_snapshots_model;
use ProjectAnalizer\Models\Audit_logs_model;
use ProjectAnalizer\Models\Revenue_planned_model;
use ProjectAnalizer\Models\Revenue_realized_model;

class Projectanalizer_projects extends Security_Controller
{
    private $evolution_service;
    private $cashflow_service;

    public function __construct()
    {
        parent::__construct();
        $this->evolution_service = new ProjectAnalizerEvolutionService();
        $this->cashflow_service = new ProjectAnalizerCashflowService();
    }

    public function evolucao($project_id = 0)
    {

      
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            show_404();
        }

        $summary = $this->evolution_service->get_physical_summary($project_id);
        $baseline_range = $this->evolution_service->get_baseline_range($project_id);
        $reschedule_log_model = new Project_reschedule_log_model();
        $latest_log = $reschedule_log_model->get_latest($project_id);
        $tasks = $this->Tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $task_ids = array_map(function ($task) {
            return $task->id;
        }, $tasks);
        $task_costs_model = new Task_costs_model();
        $costs_query = $task_ids ? $task_costs_model->get_details(array("task_ids" => $task_ids)) : null;
        $task_costs = $costs_query ? $costs_query->getResult() : array();
        $metrics_map = $this->evolution_service->get_task_metrics_map($task_ids);
        $cost_realized_model = new Cost_realized_model();
        $filter_cost_type = $this->request->getGet("cost_type");
        $filter_date_from = $this->request->getGet("date_from");
        $filter_date_to = $this->request->getGet("date_to");
        $realized_query = $cost_realized_model->get_details(array(
            "project_id" => $project_id,
            "cost_type" => $filter_cost_type,
            "date_gte" => $filter_date_from,
            "date_lte" => $filter_date_to
        ));
        $realized_items = $realized_query ? $realized_query->getResult() : array();

        $today = date("Y-m-d");
        $overdue_tasks = array();
        foreach ($tasks as $task) {
            if ($task->deadline && $task->deadline < $today && (int)$task->status_id !== 3) {
                $overdue_tasks[] = $task;
            }
        }

        $blocked_tasks = array();
        foreach ($tasks as $task) {
            if ($task->blocked_by && trim($task->blocked_by)) {
                $blocked_tasks[] = $task;
            }
        }

        $snapshot_period = (int)$this->request->getGet("snapshot_period");
        if (!in_array($snapshot_period, array(30, 90, 180), true)) {
            $snapshot_period = 90;
        }

        $snapshot_from = date("Y-m-d", strtotime("-{$snapshot_period} days"));
        $snapshots_model = new Project_snapshots_model();
        $snapshots_query = $snapshots_model->get_details(array(
            "project_id" => $project_id,
            "date_from" => $snapshot_from,
            "date_to" => $today,
            "order" => "ASC"
        ));
        $snapshots = $snapshots_query ? $snapshots_query->getResult() : array();

        if ($snapshots) {
            $labels = array();
            $planned_series = array();
            $realized_series = array();
            $physical_planned_series = array();
            $physical_actual_series = array();

            foreach ($snapshots as $snapshot) {
                $labels[] = $snapshot->ref_date;
                $planned_series[] = (float)$snapshot->planned_financial_value;
                $realized_series[] = (float)$snapshot->realized_financial_value;
                $physical_planned_series[] = (float)$snapshot->planned_physical_percent;
                $physical_actual_series[] = (float)$snapshot->actual_physical_percent;
            }

            $summary["financial_labels"] = $labels;
            $summary["financial_planned_series"] = $planned_series;
            $summary["financial_realized_series"] = $realized_series;
            $summary["physical_planned_series"] = $physical_planned_series;
            $summary["physical_actual_series"] = $physical_actual_series;
        }

        $audit_model = new Audit_logs_model();
        $audit_logs = $audit_model->get_details(array("project_id" => $project_id));
        $audit_items = $audit_logs ? $audit_logs->getResult() : array();

        $revenue_planned_model = new Revenue_planned_model();
        $revenue_realized_model = new Revenue_realized_model();
        $revenue_planned_query = $revenue_planned_model->get_details(array("project_id" => $project_id));
        $revenue_realized_query = $revenue_realized_model->get_details(array("project_id" => $project_id));
        $revenue_planned_items = $revenue_planned_query ? $revenue_planned_query->getResult() : array();
        $revenue_realized_items = $revenue_realized_query ? $revenue_realized_query->getResult() : array();

        $cashflow_summary = $this->cashflow_service->getMonthlySummary($project_id);
        $cashflow_cards = $this->cashflow_service->getCards($project_id);

        $view_data = array(
            "project_id" => $project_id,
            "project_info" => $project_info,
            "summary" => $summary,
            "baseline_range" => $baseline_range,
            "latest_reschedule" => $latest_log,
            "tasks" => $tasks,
            "task_costs" => $task_costs,
            "metrics_map" => $metrics_map,
            "realized_items" => $realized_items,
            "snapshot_period" => $snapshot_period,
            "has_snapshots" => (bool)$snapshots,
            "overdue_tasks" => $overdue_tasks,
            "blocked_tasks" => $blocked_tasks,
            "audit_items" => $audit_items,
            "revenue_planned_items" => $revenue_planned_items,
            "revenue_realized_items" => $revenue_realized_items,
            "cashflow_summary" => $cashflow_summary,
            "cashflow_cards" => $cashflow_cards,
            "filters" => array(
                "cost_type" => $filter_cost_type,
                "date_from" => $filter_date_from,
                "date_to" => $filter_date_to
            )
        );

        if ($this->request->isAJAX()) {
            return $this->template->view('ProjectAnalizer\\Views\\evolution\\index', $view_data);
        }

        return $this->template->render('ProjectAnalizer\\Views\\evolution\\index', $view_data);
    }

    public function generate_baseline($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("record_not_found")));
        }

        helper("projectanalizer_general_helper");

        $baseline_date = $this->request->getPost("baseline_date");
        $use_today = $this->request->getPost("use_today");
        $overwrite_labor = $this->request->getPost("overwrite_labor") ? true : false;
        $recalculate_baseline = $this->request->getPost("recalculate_baseline") ? true : false;
        $baseline_date = $use_today ? date("Y-m-d") : $baseline_date;
        if (!$baseline_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $baseline_date)) {
            $baseline_date = "2000-01-01";
        }

        $tasks_model = model("App\\Models\\Tasks_model");
        $task_metrics_model = model("ProjectAnalizer\\Models\\Task_metrics_model");

        $tasks = $tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $invalid_tasks = array();
        foreach ($tasks as $task) {
            if (!$task->start_date || !$task->deadline) {
                $invalid_tasks[] = "#" . $task->id . " - " . $task->title;
            }
        }

        if ($invalid_tasks) {
            return $this->response->setJSON(array(
                "success" => false,
                "message" => app_lang("baseline_missing_dates") . "<br>" . implode("<br>", $invalid_tasks),
                "invalid_tasks" => $invalid_tasks
            ));
        }

        $task_costs_model = model("ProjectAnalizer\\Models\\Task_costs_model");
        $processed = 0;
        $labor_overwritten = 0;
        $labor_ignored = 0;

        foreach ($tasks as $task) {
            $existing = $task_metrics_model->get_details(array("task_id" => $task->id))->getRow();
            $start_date = $task->start_date ? substr($task->start_date, 0, 10) : "";
            $end_date = $task->deadline ? substr($task->deadline, 0, 10) : "";
            if ($recalculate_baseline) {
                $baseline_start = $start_date;
                $baseline_end = $end_date;
            } else {
                $baseline_start = $existing && $existing->baseline_start ? substr($existing->baseline_start, 0, 10) : $start_date;
                $baseline_end = $existing && $existing->baseline_end ? substr($existing->baseline_end, 0, 10) : $end_date;
            }
            $duration_days = business_days($baseline_start, $baseline_end);
            if ($duration_days < 1) {
                $duration_days = 1;
            }

            $data = array(
                "task_id" => $task->id,
                "weight" => $existing && is_numeric($existing->weight) ? $existing->weight : 1,
                "baseline_start" => $baseline_start,
                "baseline_end" => $baseline_end,
                "baseline_duration_days" => $duration_days,
                "distribution_type" => $existing && $existing->distribution_type ? $existing->distribution_type : "linear"
            );

            if ($existing) {
                $data["id"] = $existing->id;
            }

            $task_metrics_model->save($data);

            $labor_info = $this->evolution_service->get_task_labor_cost($task->id, $baseline_start, $baseline_end);
            $labor_total = get_array_value($labor_info, "total");
            $labor_profiles = (int)get_array_value($labor_info, "profiles");
            if ($labor_profiles > 0) {
                $existing_cost = $task_costs_model->get_details(array(
                    "task_id" => $task->id,
                    "cost_type" => "mao_obra"
                ))->getRow();

                if ($existing_cost) {
                    if ($overwrite_labor) {
                        $task_costs_model->save(array(
                            "id" => $existing_cost->id,
                            "task_id" => $task->id,
                            "cost_type" => "mao_obra",
                            "planned_value" => $labor_total
                        ));
                        $labor_overwritten++;
                    } else {
                        $labor_ignored++;
                    }
                } else {
                    $task_costs_model->save(array(
                        "task_id" => $task->id,
                        "cost_type" => "mao_obra",
                        "planned_value" => $labor_total
                    ));
                }
            }

            $processed++;
        }

        $message = app_lang("baseline_generated");

        $this->log_audit(
            $project_id,
            "baseline_generated",
            "Baseline: " . $baseline_date . " | tasks=" . $processed . " | labor_overwritten=" . $labor_overwritten . " | labor_ignored=" . $labor_ignored
        );

        return $this->response->setJSON(array(
            "success" => true,
            "message" => $message
        ));
    }

    public function reschedule_modal_form($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            show_404();
        }

        $view_data = array(
            "project_id" => $project_id,
            "project_info" => $project_info
        );

        return $this->template->view("ProjectAnalizer\\Views\\evolution\\reschedule_modal_form", $view_data);
    }

    public function reschedule_project($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("record_not_found")));
        }

        $new_start = $this->request->getPost("new_start");
        $mode = $this->request->getPost("mode");
        $apply_scope = $this->request->getPost("apply_scope");
        $adjust_milestones = $this->request->getPost("adjust_milestones") ? 1 : 0;
        $clamp_before_start = $this->request->getPost("clamp_before_start") ? 1 : 0;
        $sequence_tasks = $this->request->getPost("sequence_tasks") ? 1 : 0;

        if (!$new_start || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_start)) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $tasks_model = model("App\\Models\\Tasks_model");
        $milestones_model = model("App\\Models\\Milestones_model");
        $reschedule_log_model = new Project_reschedule_log_model();

        $tasks = $tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $milestones = $milestones_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();

        $min_start = "";
        $min_deadline = "";
        foreach ($tasks as $task) {
            if ($task->start_date && (!$min_start || $task->start_date < $min_start)) {
                $min_start = $task->start_date;
            }
            if ($task->deadline && (!$min_deadline || $task->deadline < $min_deadline)) {
                $min_deadline = $task->deadline;
            }
        }

        if (!$apply_scope) {
            $apply_scope = "pending_only";
        }

        $apply_to_task = function ($task) use ($apply_scope) {
            if ((int)$task->status_id === 3) {
                return false;
            }
            if ($apply_scope === "all_except_completed") {
                return true;
            }
            return (int)$task->status_id === 1;
        };

        $min_start = "";
        $min_deadline = "";
        foreach ($tasks as $task) {
            if (!$apply_to_task($task)) {
                continue;
            }
            if ($task->start_date && (!$min_start || $task->start_date < $min_start)) {
                $min_start = $task->start_date;
            }
            if ($task->deadline && (!$min_deadline || $task->deadline < $min_deadline)) {
                $min_deadline = $task->deadline;
            }
        }

        $reference_date = $min_start;
        if (!$reference_date) {
            $reference_date = $project_info->start_date ? $project_info->start_date : "";
        }
        if (!$reference_date && $min_deadline) {
            $reference_date = $min_deadline;
        }
        if (!$reference_date) {
            $reference_date = date("Y-m-d");
        }

        $delta_days = (int)round((strtotime($new_start) - strtotime($reference_date)) / 86400);

        $has_dependencies = false;
        foreach ($tasks as $task) {
            if ($task->blocked_by && trim($task->blocked_by)) {
                $has_dependencies = true;
                break;
            }
        }

        $fallback_to_delta = false;
        if ($mode === "dependencies" && !$has_dependencies) {
            $mode = "delta";
            $fallback_to_delta = true;
        }

        $add_days = function ($date, $days) {
            if (!$date) {
                return "";
            }
            return date("Y-m-d", strtotime($date . " " . ($days >= 0 ? "+" : "") . $days . " day"));
        };

        $metrics_model = new Task_metrics_model();
        $metrics_map = $this->evolution_service->get_task_metrics_map(array_map(function ($task) {
            return $task->id;
        }, $tasks));

        $get_duration_days = function ($task) use ($metrics_map) {
            $duration_days = 0;
            if ($task->start_date && $task->deadline) {
                try {
                    $start_dt = new \DateTime($task->start_date);
                    $end_dt = new \DateTime($task->deadline);
                    $duration_days = (int)$start_dt->diff($end_dt)->days;
                } catch (\Throwable $e) {
                    $duration_days = (int)round((strtotime($task->deadline) - strtotime($task->start_date)) / 86400);
                }
            }
            if ($duration_days <= 0) {
                $metric = get_array_value($metrics_map, $task->id);
                if ($metric && is_numeric($metric->baseline_duration_days)) {
                    $duration_days = (int)$metric->baseline_duration_days;
                }
            }
            if ($duration_days <= 0) {
                $duration_days = 1;
            }
            return $duration_days;
        };

        $original_durations = array();
        foreach ($tasks as $task) {
            $original_durations[$task->id] = $get_duration_days($task);
        }

        if ($mode === "dependencies") {
            $task_map = array();
            foreach ($tasks as $task) {
                $task_map[$task->id] = $task;
            }

            $changed = true;
            $iterations = 0;
            while ($changed && $iterations < 50) {
                $changed = false;
                $iterations++;

                foreach ($tasks as $task) {
                    if (!$apply_to_task($task)) {
                        continue;
                    }

                    $duration_days = $get_duration_days($task);

                    $start = $task->start_date ? $task->start_date : $new_start;
                    if ($task->blocked_by && trim($task->blocked_by)) {
                        $pred_ids = array_values(array_filter(array_map("intval", explode(",", $task->blocked_by))));
                        $max_end = "";
                        foreach ($pred_ids as $pred_id) {
                            if (isset($task_map[$pred_id]) && $task_map[$pred_id]->deadline) {
                                if (!$max_end || $task_map[$pred_id]->deadline > $max_end) {
                                    $max_end = $task_map[$pred_id]->deadline;
                                }
                            }
                        }
                        if ($max_end) {
                            $start = $add_days($max_end, 1);
                        }
                    }

                    $end = $add_days($start, $duration_days);

                    if ($task->start_date !== $start || $task->deadline !== $end) {
                        $changed = true;
                        $task_update = array(
                            "id" => $task->id,
                            "start_date" => $start,
                            "deadline" => $end
                        );
                        $tasks_model->ci_save($task_update, $task->id);
                        $task->start_date = $start;
                        $task->deadline = $end;
                    }
                }
            }
        } else {
            foreach ($tasks as $task) {
                if (!$apply_to_task($task)) {
                    continue;
                }

                if (!$task->start_date && !$task->deadline) {
                    continue;
                }

                $duration_days = (int)get_array_value($original_durations, $task->id, 1);
                if ($duration_days < 1) {
                    $duration_days = 1;
                }

                $new_start_date = $task->start_date ? $add_days($task->start_date, $delta_days) : "";
                if (!$new_start_date && $task->deadline) {
                    $new_start_date = $add_days($task->deadline, $delta_days);
                }

                if ($clamp_before_start && $new_start_date && $new_start_date < $new_start) {
                    $new_start_date = $new_start;
                }

                $new_deadline = $new_start_date ? $add_days($new_start_date, $duration_days) : "";

                $update = array(
                    "id" => $task->id,
                    "start_date" => $new_start_date,
                    "deadline" => $new_deadline
                );
                $tasks_model->save($update);
                $task->start_date = $new_start_date;
                $task->deadline = $new_deadline;
            }

            if ($sequence_tasks && !$has_dependencies) {
                $tasks_by_milestone = array();
                foreach ($tasks as $task) {
                    if (!$apply_to_task($task)) {
                        continue;
                    }
                    if (!$task->milestone_id) {
                        continue;
                    }
                    if (!isset($tasks_by_milestone[$task->milestone_id])) {
                        $tasks_by_milestone[$task->milestone_id] = array();
                    }
                    $tasks_by_milestone[$task->milestone_id][] = $task;
                }

                foreach ($tasks_by_milestone as $milestone_id => $milestone_tasks) {
                    usort($milestone_tasks, function ($a, $b) {
                        if ($a->start_date == $b->start_date) {
                            return $a->id <=> $b->id;
                        }
                        return $a->start_date < $b->start_date ? -1 : 1;
                    });

                    $previous_end = "";
                    foreach ($milestone_tasks as $task) {
                        if (!$task->start_date || !$task->deadline) {
                            continue;
                        }
                        if ($previous_end && $task->start_date <= $previous_end) {
                            $duration_days = $get_duration_days($task);
                            $new_start_date = $add_days($previous_end, 1);
                            $new_end_date = $add_days($new_start_date, $duration_days);
                            $task_update = array(
                                "id" => $task->id,
                                "start_date" => $new_start_date,
                                "deadline" => $new_end_date
                            );
                            $tasks_model->save($task_update);
                            $task->start_date = $new_start_date;
                            $task->deadline = $new_end_date;
                        }
                        $previous_end = $task->deadline ? $task->deadline : $previous_end;
                    }
                }
            }
        }

        if ($adjust_milestones) {
            foreach ($milestones as $milestone) {
                $milestone_tasks = array_filter($tasks, function ($task) use ($milestone) {
                    return (int)$task->milestone_id === (int)$milestone->id;
                });
                $max_deadline = "";
                foreach ($milestone_tasks as $task) {
                    if ($task->deadline && $task->deadline > $max_deadline) {
                        $max_deadline = $task->deadline;
                    }
                }
                if ($max_deadline) {
                    $milestone_update = array(
                        "id" => $milestone->id,
                        "due_date" => $max_deadline
                    );
                    $milestones_model->ci_save($milestone_update, $milestone->id);
                }
            }
        }

        $log_saved = false;
        try {
            $db = db_connect("default");
            $log_table = get_db_prefix() . "projectanalizer_project_reschedule_log";
            if ($db->tableExists($log_table)) {
                $reschedule_log_model->save(array(
                    "project_id" => $project_id,
                    "old_start" => $reference_date,
                    "new_start" => $new_start,
                    "mode" => $mode,
                    "apply_scope" => $apply_scope,
                    "adjust_milestones" => $adjust_milestones,
                    "clamp_enabled" => $clamp_before_start,
                    "sequenced_enabled" => $sequence_tasks,
                    "created_by" => $this->login_user->id
                ));
                $log_saved = true;
            }
        } catch (\Throwable $e) {
            $log_saved = false;
        }

        $message = $fallback_to_delta ? app_lang("dependencies_not_configured") : app_lang("reschedule_success");

        if (!$log_saved) {
            $message .= " " . app_lang("reschedule_log_missing");
        }

        $this->log_audit($project_id, "rescheduled", "Old: {$reference_date} | New: {$new_start} | Mode: {$mode}");

        return $this->response->setJSON(array("success" => true, "message" => $message));
    }

    public function cost_modal_form($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            show_404();
        }

        $tasks = $this->Tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $milestones = $this->Milestones_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $milestone_title_map = array();
        foreach ($milestones as $milestone) {
            $milestone_title_map[$milestone->id] = $milestone->title;
        }

        $task_dropdown = array();
        foreach ($tasks as $task) {
            $milestone_title = get_array_value($milestone_title_map, $task->milestone_id, "");
            $task_dropdown[$task->id] = ($milestone_title ? ($milestone_title . " - ") : "") . $task->title;
        }

        $metrics_model = new Task_metrics_model();
        $metrics_map = $this->evolution_service->get_task_metrics_map(array_keys($task_dropdown));

        $view_data = array(
            "project_id" => $project_id,
            "task_dropdown" => $task_dropdown,
            "metrics_map" => $metrics_map
        );

        return $this->template->view("ProjectAnalizer\\Views\\evolution\\cost_modal_form", $view_data);
    }

    public function save_task_cost($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $task_id = (int)$this->request->getPost("task_id");
        $cost_type = $this->request->getPost("cost_type");
        $planned_value = unformat_currency($this->request->getPost("planned_value"));
        $distribution_type = $this->request->getPost("distribution_type");

        if (!$task_id || !$cost_type) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $task_costs_model = new Task_costs_model();
        $task_costs_model->save(array(
            "task_id" => $task_id,
            "cost_type" => $cost_type,
            "planned_value" => $planned_value ? $planned_value : 0
        ));

        $metrics_model = new Task_metrics_model();
        $metric = $metrics_model->get_details(array("task_id" => $task_id))->getRow();
        $metric_data = array(
            "task_id" => $task_id,
            "distribution_type" => $distribution_type ? $distribution_type : "linear",
            "weight" => $metric && is_numeric($metric->weight) ? $metric->weight : 1
        );
        if ($metric) {
            $metric_data["id"] = $metric->id;
        }
        $metrics_model->save($metric_data);

        $this->log_audit($project_id, "planned_cost_saved", "Task {$task_id} - {$cost_type} = {$planned_value}");

        return $this->response->setJSON(array("success" => true, "message" => app_lang("record_saved")));
    }

    public function delete_task_cost($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $id = (int)$this->request->getPost("id");
        if (!$id) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $task_costs_model = new Task_costs_model();
        $task_costs_model->delete($id);

        $this->log_audit($project_id, "planned_cost_deleted", "ID {$id}");

        return $this->response->setJSON(array("success" => true, "message" => app_lang("record_deleted")));
    }

    public function realized_modal_form($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            show_404();
        }

        $tasks = $this->Tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $task_dropdown = array("" => "-");
        foreach ($tasks as $task) {
            $task_dropdown[$task->id] = $task->title;
        }

        $view_data = array(
            "project_id" => $project_id,
            "task_dropdown" => $task_dropdown
        );

        return $this->template->view("ProjectAnalizer\\Views\\evolution\\realized_modal_form", $view_data);
    }

    public function revenue_planned_modal_form($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            show_404();
        }

        $id = (int)$this->request->getPost("id");
        $model = new Revenue_planned_model();
        $info = $id ? $model->get_details(array("id" => $id))->getRow() : null;

        $view_data = array(
            "project_id" => $project_id,
            "model_info" => $info
        );

        return $this->template->view("ProjectAnalizer\\Views\\evolution\\revenue_planned_modal_form", $view_data);
    }

    public function revenue_realized_modal_form($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            show_404();
        }

        $id = (int)$this->request->getPost("id");
        $model = new Revenue_realized_model();
        $info = $id ? $model->get_details(array("id" => $id))->getRow() : null;

        $planned_model = new Revenue_planned_model();
        $planned_rows = $planned_model->get_details(array("project_id" => $project_id));
        $planned_items = $planned_rows ? $planned_rows->getResult() : array();
        $planned_dropdown = array("" => "-");
        foreach ($planned_items as $item) {
            $planned_dropdown[$item->id] = $item->title;
        }

        $view_data = array(
            "project_id" => $project_id,
            "model_info" => $info,
            "planned_dropdown" => $planned_dropdown
        );

        return $this->template->view("ProjectAnalizer\\Views\\evolution\\revenue_realized_modal_form", $view_data);
    }

    public function save_realized($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $date = $this->request->getPost("date");
        $value = unformat_currency($this->request->getPost("value"));
        $cost_type = $this->request->getPost("cost_type");
        $task_id = (int)$this->request->getPost("task_id");
        $description = $this->request->getPost("description");

        if (!$date || !$cost_type || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $cost_realized_model = new Cost_realized_model();
        $cost_realized_model->save(array(
            "project_id" => $project_id,
            "task_id" => $task_id ? $task_id : null,
            "cost_type" => $cost_type,
            "date" => $date,
            "value" => $value ? $value : 0,
            "description" => $description,
            "created_by" => $this->login_user->id
        ));

        $this->log_audit($project_id, "realized_saved", "Task {$task_id} - {$cost_type} = {$value}");

        return $this->response->setJSON(array("success" => true, "message" => app_lang("record_saved")));
    }

    public function delete_realized($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $id = (int)$this->request->getPost("id");
        if (!$id) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $cost_realized_model = new Cost_realized_model();
        $cost_realized_model->delete($id);

        $this->log_audit($project_id, "realized_deleted", "ID {$id}");

        return $this->response->setJSON(array("success" => true, "message" => app_lang("record_deleted")));
    }

    public function revenue_list()
    {
        $project_id = (int)$this->request->getGet("project_id");
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $planned_model = new Revenue_planned_model();
        $realized_model = new Revenue_realized_model();

        $planned = $planned_model->get_details(array("project_id" => $project_id));
        $realized = $realized_model->get_details(array("project_id" => $project_id));

        return $this->response->setJSON(array(
            "success" => true,
            "planned" => $planned ? $planned->getResult() : array(),
            "realized" => $realized ? $realized->getResult() : array()
        ));
    }

    public function save_revenue_planned()
    {
        $project_id = (int)$this->request->getPost("project_id");
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $id = (int)$this->request->getPost("id");
        $planned_date = $this->_normalize_revenue_date($this->request->getPost("planned_date"));
        $planned_value = unformat_currency($this->request->getPost("planned_value"));
        $percent_of_contract = $this->request->getPost("percent_of_contract");

        $data = array(
            "project_id" => $project_id,
            "title" => trim($this->request->getPost("title")),
            "planned_date" => $planned_date,
            "planned_value" => $planned_value ? $planned_value : 0,
            "percent_of_contract" => $percent_of_contract !== null && $percent_of_contract !== "" ? $percent_of_contract : null,
            "notes" => $this->request->getPost("notes"),
            "created_by" => $this->login_user->id
        );

        $planned_model = new Revenue_planned_model();
        $saved = $id ? $planned_model->update_planned($id, $data) : $planned_model->create_planned($data);

        if (!$saved) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $this->log_audit($project_id, "revenue_planned_saved", "ID {$id}");

        return $this->response->setJSON(array("success" => true, "message" => app_lang("record_saved")));
    }

    public function delete_revenue_planned()
    {
        $project_id = (int)$this->request->getPost("project_id");
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $id = (int)$this->request->getPost("id");
        if (!$id) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $planned_model = new Revenue_planned_model();
        $planned_model->delete_planned($id);

        $this->log_audit($project_id, "revenue_planned_deleted", "ID {$id}");

        return $this->response->setJSON(array("success" => true, "message" => app_lang("record_deleted")));
    }

    public function save_revenue_realized()
    {
        $project_id = (int)$this->request->getPost("project_id");
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $id = (int)$this->request->getPost("id");
        $realized_date = $this->_normalize_revenue_date($this->request->getPost("realized_date"));
        $realized_value = unformat_currency($this->request->getPost("realized_value"));
        $planned_id = (int)$this->request->getPost("planned_id");

        $data = array(
            "project_id" => $project_id,
            "planned_id" => $planned_id ? $planned_id : null,
            "realized_date" => $realized_date,
            "realized_value" => $realized_value ? $realized_value : 0,
            "document_ref" => trim($this->request->getPost("document_ref")),
            "notes" => $this->request->getPost("notes"),
            "created_by" => $this->login_user->id
        );

        $realized_model = new Revenue_realized_model();
        $saved = $id ? $realized_model->update_realized($id, $data) : $realized_model->create_realized($data);

        if (!$saved) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $this->log_audit($project_id, "revenue_realized_saved", "ID {$id}");

        return $this->response->setJSON(array("success" => true, "message" => app_lang("record_saved")));
    }

    public function delete_revenue_realized()
    {
        $project_id = (int)$this->request->getPost("project_id");
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $id = (int)$this->request->getPost("id");
        if (!$id) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $realized_model = new Revenue_realized_model();
        $realized_model->delete_realized($id);

        $this->log_audit($project_id, "revenue_realized_deleted", "ID {$id}");

        return $this->response->setJSON(array("success" => true, "message" => app_lang("record_deleted")));
    }

    public function revenue_summary()
    {
        $project_id = (int)$this->request->getGet("project_id");
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $group = $this->request->getGet("group") ? $this->request->getGet("group") : "month";
        if ($group !== "month") {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $baseline_range = $this->evolution_service->get_baseline_range($project_id);
        if (!$baseline_range["has_baseline"]) {
            return $this->response->setJSON(array(
                "success" => false,
                "message" => "Gere o baseline antes de consultar o resumo."
            ));
        }

        $planned_model = new Revenue_planned_model();
        $realized_model = new Revenue_realized_model();

        $planned_rows = $planned_model->get_details(array("project_id" => $project_id));
        $realized_rows = $realized_model->get_details(array("project_id" => $project_id));
        $planned_items = $planned_rows ? $planned_rows->getResult() : array();
        $realized_items = $realized_rows ? $realized_rows->getResult() : array();

        $range_start = $baseline_range["start"];
        $range_end = $baseline_range["end"];

        foreach ($planned_items as $item) {
            if ($item->planned_date && $item->planned_date < $range_start) {
                $range_start = $item->planned_date;
            }
            if ($item->planned_date && $item->planned_date > $range_end) {
                $range_end = $item->planned_date;
            }
        }
        foreach ($realized_items as $item) {
            if ($item->realized_date && $item->realized_date < $range_start) {
                $range_start = $item->realized_date;
            }
            if ($item->realized_date && $item->realized_date > $range_end) {
                $range_end = $item->realized_date;
            }
        }

        $range = $this->evolution_service->build_month_range($range_start, $range_end);
        $labels = $range["labels"];
        $month_ends = $range["month_ends"];

        $planned_by_month = array();
        $planned_cum = array();
        $realized_by_month = array();
        $realized_cum = array();
        $delta_by_month = array();

        $planned_running = 0;
        $realized_running = 0;

        foreach ($month_ends as $month_end) {
            $month_key = substr($month_end, 0, 7);
            $planned_month = 0;
            foreach ($planned_items as $item) {
                if ($item->planned_date && substr($item->planned_date, 0, 7) === $month_key) {
                    $planned_month += (float)$item->planned_value;
                }
            }

            $realized_month = 0;
            foreach ($realized_items as $item) {
                if ($item->realized_date && substr($item->realized_date, 0, 7) === $month_key) {
                    $realized_month += (float)$item->realized_value;
                }
            }

            $planned_running += $planned_month;
            $realized_running += $realized_month;

            $planned_by_month[] = round($planned_month, 2);
            $realized_by_month[] = round($realized_month, 2);
            $planned_cum[] = round($planned_running, 2);
            $realized_cum[] = round($realized_running, 2);
            $delta_by_month[] = round($planned_month - $realized_month, 2);
        }

        return $this->response->setJSON(array(
            "success" => true,
            "labels" => $labels,
            "planned_by_month" => $planned_by_month,
            "realized_by_month" => $realized_by_month,
            "planned_cum" => $planned_cum,
            "realized_cum" => $realized_cum,
            "delta_by_month" => $delta_by_month
        ));
    }

    public function cashflow_summary()
    {
        $project_id = (int)$this->request->getGet("project_id");
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $group = $this->request->getGet("group") ? $this->request->getGet("group") : "month";
        if ($group !== "month") {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("invalid_request")));
        }

        $baseline_range = $this->evolution_service->get_baseline_range($project_id);
        if (!$baseline_range["has_baseline"]) {
            return $this->response->setJSON(array(
                "success" => false,
                "message" => "Gere o baseline antes de consultar o resumo."
            ));
        }

        $planned_model = new Revenue_planned_model();
        $realized_model = new Revenue_realized_model();
        $planned_rows = $planned_model->get_details(array("project_id" => $project_id));
        $realized_rows = $realized_model->get_details(array("project_id" => $project_id));
        $planned_items = $planned_rows ? $planned_rows->getResult() : array();
        $realized_items = $realized_rows ? $realized_rows->getResult() : array();

        $range_start = $baseline_range["start"];
        $range_end = $baseline_range["end"];

        foreach ($planned_items as $item) {
            if ($item->planned_date && $item->planned_date < $range_start) {
                $range_start = $item->planned_date;
            }
            if ($item->planned_date && $item->planned_date > $range_end) {
                $range_end = $item->planned_date;
            }
        }
        foreach ($realized_items as $item) {
            if ($item->realized_date && $item->realized_date < $range_start) {
                $range_start = $item->realized_date;
            }
            if ($item->realized_date && $item->realized_date > $range_end) {
                $range_end = $item->realized_date;
            }
        }

        $expense_summary = $this->evolution_service->get_expenses_monthly_summary($project_id, $range_start, $range_end);
        $labels = $expense_summary["labels"];

        $revenue_range = $this->evolution_service->build_month_range($range_start, $range_end);
        $month_ends = $revenue_range["month_ends"];

        $revenue_planned_by_month = array();
        $revenue_realized_by_month = array();
        $revenue_planned_cum = array();
        $revenue_realized_cum = array();
        $net_cashflow_by_month = array();
        $net_cumulative = array();

        $planned_running = 0;
        $realized_running = 0;
        $net_running = 0;

        foreach ($month_ends as $index => $month_end) {
            $month_key = substr($month_end, 0, 7);
            $planned_month = 0;
            foreach ($planned_items as $item) {
                if ($item->planned_date && substr($item->planned_date, 0, 7) === $month_key) {
                    $planned_month += (float)$item->planned_value;
                }
            }

            $realized_month = 0;
            foreach ($realized_items as $item) {
                if ($item->realized_date && substr($item->realized_date, 0, 7) === $month_key) {
                    $realized_month += (float)$item->realized_value;
                }
            }

            $planned_running += $planned_month;
            $realized_running += $realized_month;

            $revenue_planned_by_month[] = round($planned_month, 2);
            $revenue_realized_by_month[] = round($realized_month, 2);
            $revenue_planned_cum[] = round($planned_running, 2);
            $revenue_realized_cum[] = round($realized_running, 2);

            $expense_month = get_array_value($expense_summary["realized_by_month"], $index, 0);
            $net_month = round($realized_month - $expense_month, 2);
            $net_running += $net_month;
            $net_cashflow_by_month[] = $net_month;
            $net_cumulative[] = round($net_running, 2);
        }

        return $this->response->setJSON(array(
            "success" => true,
            "labels" => $labels,
            "expenses_planned_by_month" => $expense_summary["planned_by_month"],
            "expenses_realized_by_month" => $expense_summary["realized_by_month"],
            "revenue_planned_by_month" => $revenue_planned_by_month,
            "revenue_realized_by_month" => $revenue_realized_by_month,
            "net_cashflow_by_month" => $net_cashflow_by_month,
            "net_cumulative" => $net_cumulative
        ));
    }

    public function cron_snapshots()
    {
        $key = $this->request->getGet("key");
        $saved_key = get_setting("projectanalizer_cron_key");

        if (!$saved_key || $key !== $saved_key) {
            return $this->response->setJSON(array("success" => false, "message" => app_lang("cron_key_invalid")));
        }

        $projects = $this->Projects_model->get_details(array("status_id" => 1))->getResult();
        $today = date("Y-m-d");
        $updated = 0;

        foreach ($projects as $project) {
            if ($this->evolution_service->generate_snapshot($project->id, $today)) {
                $updated++;
            }
        }

        return $this->response->setJSON(array(
            "success" => true,
            "message" => app_lang("snapshots_updated"),
            "date" => $today,
            "projects_total" => count($projects),
            "snapshots_saved" => $updated
        ));
    }

    public function export_report($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $project_info = $this->Projects_model->get_details(array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id
        ))->getRow();

        if (!$project_info) {
            show_404();
        }

        $summary = $this->evolution_service->get_physical_summary($project_id);
        $tasks = $this->Tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $task_costs_model = new Task_costs_model();
        $task_costs = $task_costs_model->get_details(array("task_ids" => array_map(function ($task) {
            return $task->id;
        }, $tasks)))->getResult();
        $cost_realized_model = new Cost_realized_model();
        $realized_items = $cost_realized_model->get_details(array("project_id" => $project_id))->getResult();

        $view_data = array(
            "project_info" => $project_info,
            "summary" => $summary,
            "tasks" => $tasks,
            "task_costs" => $task_costs,
            "realized_items" => $realized_items
        );

        $html = view("ProjectAnalizer\\Views\\evolution\\export", $view_data);
        $mode = $this->request->getGet("mode") === "download" ? "download" : "print";

        $pdf = new \App\Libraries\Pdf();
        return $pdf->PreparePDF($html, "evolucao_ff_" . $project_id, $mode);
    }

    public function export_costs_csv($project_id = 0)
    {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $tasks = $this->Tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $task_title_map = array();
        foreach ($tasks as $task) {
            $task_title_map[$task->id] = $task->title;
        }

        $task_costs_model = new Task_costs_model();
        $task_costs = $task_costs_model->get_details(array("task_ids" => array_keys($task_title_map)))->getResult();

        $cost_realized_model = new Cost_realized_model();
        $realized_items = $cost_realized_model->get_details(array("project_id" => $project_id))->getResult();

        $filename = "projectanalizer_costs_" . $project_id . ".csv";
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $output = fopen("php://output", "w");
        fputcsv($output, array("type", "task", "cost_type", "date", "value", "description"));

        foreach ($task_costs as $cost) {
            fputcsv($output, array(
                "planned",
                get_array_value($task_title_map, $cost->task_id, ""),
                $cost->cost_type,
                "",
                $cost->planned_value,
                ""
            ));
        }

        foreach ($realized_items as $item) {
            fputcsv($output, array(
                "realized",
                get_array_value($task_title_map, $item->task_id, ""),
                $item->cost_type,
                $item->date,
                $item->value,
                $item->description
            ));
        }

        fclose($output);
        exit;
    }

    private function log_audit($project_id, $action, $details = "")
    {
        try {
            $model = new Audit_logs_model();
            $model->save(array(
                "project_id" => $project_id,
                "action" => $action,
                "details" => $details,
                "created_by" => $this->login_user->id
            ));
        } catch (\Throwable $e) {
            log_message("error", "[ProjectAnalizer] Audit log error: " . $e->getMessage());
        }
    }

    private function _normalize_revenue_date($date)
    {
        if (!$date) {
            return "";
        }
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
            return $date;
        }
        if (preg_match('/^\\d{2}\\/\\d{2}\\/\\d{4}$/', $date)) {
            $parts = explode("/", $date);
            return $parts[2] . "-" . $parts[1] . "-" . $parts[0];
        }
        return $date;
    }
}
