<?php
namespace ProjectAnalizer\Libraries;

use App\Models\Tasks_model;
use App\Models\Milestones_model;
use ProjectAnalizer\Models\Task_metrics_model;
use ProjectAnalizer\Models\Task_costs_model;
use ProjectAnalizer\Models\Cost_realized_model;
use ProjectAnalizer\Models\Project_snapshots_model;
use ProjectAnalizer\Models\Task_cashflow_manual_model;
use ProjectAnalizer\Models\Task_labor_profiles_model;

class ProjectAnalizerEvolutionService
{
    protected $task_metrics_model;
    protected $task_costs_model;
    protected $cost_realized_model;
    protected $project_snapshots_model;
    protected $task_cashflow_manual_model;
    protected $tasks_model;
    protected $milestones_model;
    protected $task_labor_profiles_model;

    public function __construct()
    {
        $this->task_metrics_model = new Task_metrics_model();
        $this->task_costs_model = new Task_costs_model();
        $this->cost_realized_model = new Cost_realized_model();
        $this->project_snapshots_model = new Project_snapshots_model();
        $this->task_cashflow_manual_model = new Task_cashflow_manual_model();
        $this->tasks_model = new Tasks_model();
        $this->milestones_model = new Milestones_model();
        $this->task_labor_profiles_model = new Task_labor_profiles_model();
    }

    protected function get_task_costs_map($task_ids = array())
    {
        $map = array();
        if (!$task_ids) {
            return $map;
        }

        try {
            $db = db_connect("default");
            $table = get_db_prefix() . "projectanalizer_task_costs";
            if (!$db->tableExists($table)) {
                return $map;
            }
        } catch (\Throwable $e) {
            return $map;
        }

        $query = $this->task_costs_model->get_details(array("task_ids" => $task_ids));
        if (!$query) {
            return $map;
        }

        $rows = $query->getResult();
        foreach ($rows as $row) {
            if (!isset($map[$row->task_id])) {
                $map[$row->task_id] = 0;
            }
            $map[$row->task_id] += (float)$row->planned_value;
        }

        return $map;
    }

    protected function get_task_manual_cashflow_map($task_ids = array(), $date_lte = "")
    {
        $map = array();
        if (!$task_ids) {
            return $map;
        }

        try {
            $db = db_connect("default");
            $table = get_db_prefix() . "projectanalizer_task_cashflow_manual";
            if (!$db->tableExists($table)) {
                return $map;
            }
        } catch (\Throwable $e) {
            return $map;
        }

        $query = $this->task_cashflow_manual_model->get_details(array(
            "task_ids" => $task_ids,
            "date_lte" => $date_lte
        ));
        if (!$query) {
            return $map;
        }

        $rows = $query->getResult();
        foreach ($rows as $row) {
            if (!isset($map[$row->task_id])) {
                $map[$row->task_id] = 0;
            }
            $map[$row->task_id] += (float)$row->value;
        }

        return $map;
    }

    protected function get_task_manual_cashflow_rows($task_ids = array())
    {
        $map = array();
        if (!$task_ids) {
            return $map;
        }

        try {
            $db = db_connect("default");
            $table = get_db_prefix() . "projectanalizer_task_cashflow_manual";
            if (!$db->tableExists($table)) {
                return $map;
            }
        } catch (\Throwable $e) {
            return $map;
        }

        $query = $this->task_cashflow_manual_model->get_details(array(
            "task_ids" => $task_ids
        ));
        if (!$query) {
            return $map;
        }

        $rows = $query->getResult();
        foreach ($rows as $row) {
            if (!isset($map[$row->task_id])) {
                $map[$row->task_id] = array();
            }
            $map[$row->task_id][] = array(
                "date" => $row->date,
                "value" => (float)$row->value
            );
        }

        return $map;
    }

    protected function get_planned_percent_at_date($baseline_start, $baseline_end, $date)
    {
        if (!$baseline_start || !$baseline_end) {
            return 0;
        }

        $start_ts = strtotime($baseline_start);
        $end_ts = strtotime($baseline_end);
        $date_ts = strtotime($date);

        if ($end_ts <= $start_ts) {
            return $date_ts >= $end_ts ? 100 : 0;
        }
        if ($date_ts <= $start_ts) {
            return 0;
        }
        if ($date_ts >= $end_ts) {
            return 100;
        }

        return (($date_ts - $start_ts) / ($end_ts - $start_ts)) * 100;
    }

    public function get_task_labor_cost($task_id, $baseline_start, $baseline_end)
    {
        $task_id = (int)$task_id;
        if (!$task_id || !$baseline_start || !$baseline_end) {
            return array("total" => 0, "profiles" => 0);
        }

        helper("projectanalizer_general_helper");
        $days = business_days($baseline_start, $baseline_end);
        if ($days < 1) {
            $days = 1;
        }

        $query = $this->task_labor_profiles_model->get_task_profiles($task_id);
        $rows = $query ? $query->getResult() : array();
        if (!$rows) {
            return array("total" => 0, "profiles" => 0);
        }

        $total = 0;
        foreach ($rows as $row) {
            $hours_day = $row->hours_per_day;
            if ($hours_day === null || $hours_day === "") {
                $hours_day = $row->labor_default_hours_per_day;
            }
            if ($hours_day === null || $hours_day === "") {
                $hours_day = 8;
            }

            $qty_people = $row->qty_people ? (float)$row->qty_people : 1;
            $total_hours = $days * (float)$hours_day * $qty_people;
            $total += $total_hours * (float)$row->labor_hourly_cost;
        }

        return array("total" => $total, "profiles" => count($rows));
    }

    public function get_baseline_range($project_id)
    {
        $tasks = $this->tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $task_ids = array_map(function ($task) {
            return $task->id;
        }, $tasks);
        $metrics_map = $this->get_task_metrics_map($task_ids);

        $range_start = "";
        $range_end = "";
        foreach ($metrics_map as $metric) {
            if ($metric->baseline_start && (!$range_start || $metric->baseline_start < $range_start)) {
                $range_start = substr($metric->baseline_start, 0, 10);
            }
            if ($metric->baseline_end && (!$range_end || $metric->baseline_end > $range_end)) {
                $range_end = substr($metric->baseline_end, 0, 10);
            }
        }

        if (!$range_start || !$range_end) {
            return array(
                "has_baseline" => false,
                "start" => "",
                "end" => ""
            );
        }

        return array(
            "has_baseline" => true,
            "start" => $range_start,
            "end" => $range_end
        );
    }

    public function build_month_range($range_start, $range_end)
    {
        $labels = array();
        $month_ends = array();

        if (!$range_start || !$range_end) {
            return array("labels" => $labels, "month_ends" => $month_ends);
        }

        $start_dt = new \DateTime($range_start);
        $start_dt->modify("first day of this month");
        $end_dt = new \DateTime($range_end);
        $end_dt->modify("first day of this month");

        $cursor = clone $start_dt;
        while ($cursor <= $end_dt) {
            $labels[] = $cursor->format("Y-m");
            $month_ends[] = $cursor->format("Y-m-t");
            $cursor->modify("+1 month");
        }

        return array("labels" => $labels, "month_ends" => $month_ends);
    }

    public function get_expenses_monthly_summary($project_id, $range_start, $range_end)
    {
        $tasks = $this->tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $task_ids = array_map(function ($task) {
            return $task->id;
        }, $tasks);

        $metrics_map = $this->get_task_metrics_map($task_ids);
        $costs_map = $this->get_task_costs_map($task_ids);
        $manual_rows_map = $this->get_task_manual_cashflow_rows($task_ids);
        $planned_query = $this->task_costs_model->get_details(array(
            "project_id" => $project_id
        ));
        $planned_rows = $planned_query ? $planned_query->getResult() : array();

        $realized_query = $this->cost_realized_model->get_details(array(
            "project_id" => $project_id
        ));
        $realized_rows = $realized_query ? $realized_query->getResult() : array();

        $range = $this->build_month_range($range_start, $range_end);
        $labels = $range["labels"];
        $month_ends = $range["month_ends"];

        $planned_cum = array();
        $planned_by_month = array();
        $realized_cum = array();
        $realized_by_month = array();

        $prev_planned = 0;
        $prev_realized = 0;

        foreach ($month_ends as $month_end) {
            $planned_month_total = 0;

            foreach ($tasks as $task) {
                $metric = isset($metrics_map[$task->id]) ? $metrics_map[$task->id] : null;
                $baseline_start = $metric && $metric->baseline_start ? substr($metric->baseline_start, 0, 10) : "";
                $baseline_end = $metric && $metric->baseline_end ? substr($metric->baseline_end, 0, 10) : "";
                $planned_percent = $this->get_planned_percent_at_date($baseline_start, $baseline_end, $month_end);

                $planned_total_task = isset($costs_map[$task->id]) ? (float)$costs_map[$task->id] : 0;
                $distribution_type = $metric && $metric->distribution_type ? $metric->distribution_type : "linear";
                $planned_value = 0;

                if ($planned_total_task > 0) {
                    if ($distribution_type === "inicio") {
                        $planned_value = ($baseline_start && $month_end >= $baseline_start) ? $planned_total_task : 0;
                    } elseif ($distribution_type === "fim") {
                        $planned_value = ($baseline_end && $month_end >= $baseline_end) ? $planned_total_task : 0;
                    } elseif ($distribution_type === "curva_s") {
                        $p = min(100, max(0, $planned_percent));
                        if ($p <= 20) {
                            $curve_percent = ($p / 20) * 10;
                        } elseif ($p <= 40) {
                            $curve_percent = 10 + (($p - 20) / 20) * 20;
                        } elseif ($p <= 60) {
                            $curve_percent = 30 + (($p - 40) / 20) * 40;
                        } elseif ($p <= 80) {
                            $curve_percent = 70 + (($p - 60) / 20) * 20;
                        } else {
                            $curve_percent = 90 + (($p - 80) / 20) * 10;
                        }
                        $planned_value = ($planned_total_task * $curve_percent) / 100;
                    } elseif ($distribution_type === "manual") {
                        $planned_value = 0;
                        if (isset($manual_rows_map[$task->id])) {
                            foreach ($manual_rows_map[$task->id] as $entry) {
                                if ($entry["date"] <= $month_end) {
                                    $planned_value += (float)$entry["value"];
                                }
                            }
                        }
                    } else {
                        $planned_value = ($planned_total_task * min(100, max(0, $planned_percent))) / 100;
                    }
                }

                $planned_month_total += $planned_value;
            }

            foreach ($planned_rows as $row) {
                if (!empty($row->planned_date) && (empty($row->task_id) || (int)$row->task_id === 0) && $row->planned_date <= $month_end) {
                    $planned_month_total += (float)$row->planned_value;
                }
            }

            $realized_total = 0;
            foreach ($realized_rows as $row) {
                if ($row->date <= $month_end) {
                    $realized_total += (float)$row->value;
                }
            }

            $planned_cum[] = round($planned_month_total, 2);
            $realized_cum[] = round($realized_total, 2);

            $planned_by_month[] = round($planned_month_total - $prev_planned, 2);
            $realized_by_month[] = round($realized_total - $prev_realized, 2);

            $prev_planned = $planned_month_total;
            $prev_realized = $realized_total;
        }

        return array(
            "labels" => $labels,
            "planned_by_month" => $planned_by_month,
            "planned_cum" => $planned_cum,
            "realized_by_month" => $realized_by_month,
            "realized_cum" => $realized_cum
        );
    }

    public function get_task_metrics_map($task_ids = [])
    {
        $map = array();
        if (!$task_ids) {
            return $map;
        }

        try {
            $db = db_connect("default");
            $table = get_db_prefix() . "projectanalizer_task_metrics";
            if (!$db->tableExists($table)) {
                return $map;
            }
        } catch (\Throwable $e) {
            return $map;
        }

        $metrics_query = $this->task_metrics_model->get_details(array("task_ids" => $task_ids));
        if (!$metrics_query) {
            return $map;
        }

        $metrics = $metrics_query->getResult();

        foreach ($metrics as $metric) {
            $map[$metric->task_id] = $metric;
        }

        return $map;
    }

    public function get_physical_summary($project_id, $default_in_progress = 50, $as_of_date = "")
    {
        $today = $as_of_date ? $as_of_date : date("Y-m-d");
        $tasks = $this->tasks_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $milestones = $this->milestones_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $task_ids = array_map(function ($task) {
            return $task->id;
        }, $tasks);
        $metrics_map = $this->get_task_metrics_map($task_ids);
        $costs_map = $this->get_task_costs_map($task_ids);
        $manual_cashflow_map = $this->get_task_manual_cashflow_map($task_ids, $today);
        $manual_rows_map = $this->get_task_manual_cashflow_rows($task_ids);

        $overdue = 0;
        $blocked = 0;
        $critical = 0;

        $milestone_summary = array();
        $project_weight = 0;
        $project_weighted_actual = 0;
        $project_weighted_planned = 0;
        $project_financial_planned = 0;

        $task_grouped = array();
        foreach ($tasks as $task) {
            $task_grouped[$task->milestone_id][] = $task;
        }

        foreach ($milestones as $milestone) {
            $milestone_tasks = isset($task_grouped[$milestone->id]) ? $task_grouped[$milestone->id] : array();
            $milestone_weight_sum = 0;
            $milestone_weighted_actual = 0;
            $milestone_weighted_planned = 0;
            $milestone_financial_planned = 0;
            $milestone_completed = 0;
            $milestone_tasks_count = count($milestone_tasks);

            foreach ($milestone_tasks as $task) {
                $status = (int)$task->status_id;
                $weight = isset($metrics_map[$task->id]) && is_numeric($metrics_map[$task->id]->weight) ? (float)$metrics_map[$task->id]->weight : 1;
                $metric = isset($metrics_map[$task->id]) ? $metrics_map[$task->id] : null;
                $task_percent = 0;
                if ($status === 3) {
                    $task_percent = 100;
                    $milestone_completed++;
                } elseif ($status === 2) {
                    $task_percent = is_numeric($task->percentage) ? (float)$task->percentage : $default_in_progress;
                    $critical += ($task_percent < 50 && !$task->blocked_by) ? 1 : 0;
                } else {
                    $task_percent = 0;
                }

                if ($task->deadline && $task->deadline < $today && $status !== 3) {
                    $overdue++;
                }

                if ($task->blocked_by && trim($task->blocked_by)) {
                    $blocked++;
                }

                $milestone_weight_sum += $weight;
                $milestone_weighted_actual += ($weight * min(100, max(0, $task_percent))) / 100;

                $baseline_start = $metric && $metric->baseline_start ? substr($metric->baseline_start, 0, 10) : "";
                $baseline_end = $metric && $metric->baseline_end ? substr($metric->baseline_end, 0, 10) : "";
                $planned_percent = 0;
                if ($baseline_start && $baseline_end) {
                    $start_ts = strtotime($baseline_start);
                    $end_ts = strtotime($baseline_end);
                    $today_ts = strtotime($today);
                    if ($end_ts <= $start_ts) {
                        $planned_percent = $today_ts >= $end_ts ? 100 : 0;
                    } elseif ($today_ts <= $start_ts) {
                        $planned_percent = 0;
                    } elseif ($today_ts >= $end_ts) {
                        $planned_percent = 100;
                    } else {
                        $planned_percent = (($today_ts - $start_ts) / ($end_ts - $start_ts)) * 100;
                    }
                }
                $milestone_weighted_planned += ($weight * min(100, max(0, $planned_percent))) / 100;

                $planned_total_task = isset($costs_map[$task->id]) ? (float)$costs_map[$task->id] : 0;
                $distribution_type = $metric && $metric->distribution_type ? $metric->distribution_type : "linear";
                $planned_financial_value = 0;
                if ($planned_total_task > 0) {
                    if ($distribution_type === "inicio") {
                        $planned_financial_value = ($baseline_start && $today >= $baseline_start) ? $planned_total_task : 0;
                    } elseif ($distribution_type === "fim") {
                        $planned_financial_value = ($baseline_end && $today >= $baseline_end) ? $planned_total_task : 0;
                    } elseif ($distribution_type === "curva_s") {
                        $p = min(100, max(0, $planned_percent));
                        if ($p <= 20) {
                            $curve_percent = ($p / 20) * 10;
                        } elseif ($p <= 40) {
                            $curve_percent = 10 + (($p - 20) / 20) * 20;
                        } elseif ($p <= 60) {
                            $curve_percent = 30 + (($p - 40) / 20) * 40;
                        } elseif ($p <= 80) {
                            $curve_percent = 70 + (($p - 60) / 20) * 20;
                        } else {
                            $curve_percent = 90 + (($p - 80) / 20) * 10;
                        }
                        $planned_financial_value = ($planned_total_task * $curve_percent) / 100;
                    } elseif ($distribution_type === "manual") {
                        $planned_financial_value = isset($manual_cashflow_map[$task->id]) ? (float)$manual_cashflow_map[$task->id] : 0;
                    } else {
                        $planned_financial_value = ($planned_total_task * min(100, max(0, $planned_percent))) / 100;
                    }
                }

                $milestone_financial_planned += $planned_financial_value;
            }

            $milestone_actual_percent = $milestone_weight_sum > 0 ? round(($milestone_weighted_actual / $milestone_weight_sum) * 100, 2) : 0;
            $milestone_planned_percent = $milestone_weight_sum > 0 ? round(($milestone_weighted_planned / $milestone_weight_sum) * 100, 2) : 0;
            $milestone_summary[] = array(
                "milestone_id" => $milestone->id,
                "title" => $milestone->title,
                "actual_percent" => $milestone_actual_percent,
                "planned_percent" => $milestone_planned_percent,
                "planned_financial" => round($milestone_financial_planned, 2),
                "total_weight" => $milestone_weight_sum,
                "tasks_count" => $milestone_tasks_count,
                "completed_count" => $milestone_completed
            );
            $project_weight += $milestone_weight_sum;
            $project_weighted_actual += $milestone_weighted_actual;
            $project_weighted_planned += $milestone_weighted_planned;
            $project_financial_planned += $milestone_financial_planned;
        }

        $project_actual_percent = $project_weight > 0 ? round(($project_weighted_actual / $project_weight) * 100, 2) : 0;
        $project_planned_percent = $project_weight > 0 ? round(($project_weighted_planned / $project_weight) * 100, 2) : 0;
        $project_deviation_pp = round($project_actual_percent - $project_planned_percent, 2);
        $project_bac = 0;
        foreach ($costs_map as $task_cost_total) {
            $project_bac += $task_cost_total;
        }
        $project_ev = ($project_actual_percent / 100) * $project_bac;

        $realized_query = $this->cost_realized_model->get_details(array(
            "project_id" => $project_id,
            "date_lte" => $today
        ));
        $realized_rows = $realized_query ? $realized_query->getResult() : array();
        $project_ac = 0;
        foreach ($realized_rows as $row) {
            $project_ac += (float)$row->value;
        }

        $project_pv = $project_financial_planned;
        $project_spi = $project_pv > 0 ? round($project_ev / $project_pv, 4) : 0;
        $project_cpi = $project_ac > 0 ? round($project_ev / $project_ac, 4) : 0;

        $range_start = $today;
        foreach ($metrics_map as $metric) {
            if ($metric->baseline_start && $metric->baseline_start < $range_start) {
                $range_start = substr($metric->baseline_start, 0, 10);
            }
        }
        if ($realized_rows) {
            foreach ($realized_rows as $row) {
                if ($row->date && $row->date < $range_start) {
                    $range_start = $row->date;
                }
            }
        }

        $labels = array();
        $planned_series = array();
        $realized_series = array();
        $physical_planned_series = array();
        $physical_actual_series = array();

        $start_dt = new \DateTime($range_start);
        $start_dt->modify("first day of this month");
        $end_dt = new \DateTime($today);
        $end_dt->modify("first day of this month");

        $cursor = clone $start_dt;
        while ($cursor <= $end_dt) {
            $label = $cursor->format("Y-m");
            $month_end = $cursor->format("Y-m-t");

            $planned_month_total = 0;
            $planned_physical_weighted = 0;
            $planned_physical_weight_sum = 0;

            foreach ($tasks as $task) {
                $metric = isset($metrics_map[$task->id]) ? $metrics_map[$task->id] : null;
                $baseline_start = $metric && $metric->baseline_start ? substr($metric->baseline_start, 0, 10) : "";
                $baseline_end = $metric && $metric->baseline_end ? substr($metric->baseline_end, 0, 10) : "";
                $planned_percent = $this->get_planned_percent_at_date($baseline_start, $baseline_end, $month_end);

                $weight = $metric && is_numeric($metric->weight) ? (float)$metric->weight : 1;
                $planned_physical_weight_sum += $weight;
                $planned_physical_weighted += ($weight * min(100, max(0, $planned_percent))) / 100;

                $planned_total_task = isset($costs_map[$task->id]) ? (float)$costs_map[$task->id] : 0;
                $distribution_type = $metric && $metric->distribution_type ? $metric->distribution_type : "linear";
                $planned_value = 0;

                if ($planned_total_task > 0) {
                    if ($distribution_type === "inicio") {
                        $planned_value = ($baseline_start && $month_end >= $baseline_start) ? $planned_total_task : 0;
                    } elseif ($distribution_type === "fim") {
                        $planned_value = ($baseline_end && $month_end >= $baseline_end) ? $planned_total_task : 0;
                    } elseif ($distribution_type === "curva_s") {
                        $p = min(100, max(0, $planned_percent));
                        if ($p <= 20) {
                            $curve_percent = ($p / 20) * 10;
                        } elseif ($p <= 40) {
                            $curve_percent = 10 + (($p - 20) / 20) * 20;
                        } elseif ($p <= 60) {
                            $curve_percent = 30 + (($p - 40) / 20) * 40;
                        } elseif ($p <= 80) {
                            $curve_percent = 70 + (($p - 60) / 20) * 20;
                        } else {
                            $curve_percent = 90 + (($p - 80) / 20) * 10;
                        }
                        $planned_value = ($planned_total_task * $curve_percent) / 100;
                    } elseif ($distribution_type === "manual") {
                        $planned_value = 0;
                        if (isset($manual_rows_map[$task->id])) {
                            foreach ($manual_rows_map[$task->id] as $entry) {
                                if ($entry["date"] <= $month_end) {
                                    $planned_value += (float)$entry["value"];
                                }
                            }
                        }
                    } else {
                        $planned_value = ($planned_total_task * min(100, max(0, $planned_percent))) / 100;
                    }
                }

                $planned_month_total += $planned_value;
            }

            $realized_total = 0;
            foreach ($realized_rows as $row) {
                if ($row->date <= $month_end) {
                    $realized_total += (float)$row->value;
                }
            }

            $labels[] = $label;
            $planned_series[] = round($planned_month_total, 2);
            $realized_series[] = round($realized_total, 2);
            $planned_physical_percent = $planned_physical_weight_sum > 0 ? round(($planned_physical_weighted / $planned_physical_weight_sum) * 100, 2) : 0;
            $physical_planned_series[] = $planned_physical_percent;
            $physical_actual_series[] = $project_actual_percent;

            $cursor->modify("+1 month");
        }

        return array(
            "project_actual_percent" => $project_actual_percent,
            "project_planned_percent" => $project_planned_percent,
            "project_deviation_pp" => $project_deviation_pp,
            "financial_planned_today" => round($project_financial_planned, 2),
            "financial_realized_today" => round($project_ac, 2),
            "financial_deviation" => round($project_ac - $project_financial_planned, 2),
            "spi" => $project_spi,
            "cpi" => $project_cpi,
            "bac" => round($project_bac, 2),
            "ev" => round($project_ev, 2),
            "pv" => round($project_pv, 2),
            "ac" => round($project_ac, 2),
            "financial_labels" => $labels,
            "financial_planned_series" => $planned_series,
            "financial_realized_series" => $realized_series,
            "physical_planned_series" => $physical_planned_series,
            "physical_actual_series" => $physical_actual_series,
            "milestones" => $milestone_summary,
            "task_flags" => array(
                "overdue" => $overdue,
                "blocked" => $blocked,
                "critical" => $critical
            )
        );
    }

    public function generate_snapshot($project_id, $ref_date = "")
    {
        $ref_date = $ref_date ? $ref_date : date("Y-m-d");
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $ref_date)) {
            return false;
        }

        $summary = $this->get_physical_summary($project_id, 50, $ref_date);
        if (!$summary) {
            return false;
        }

        $existing = $this->project_snapshots_model->get_details(array(
            "project_id" => $project_id,
            "ref_date" => $ref_date
        ))->getRow();

        $data = array(
            "project_id" => $project_id,
            "ref_date" => $ref_date,
            "planned_physical_percent" => $summary["project_planned_percent"],
            "actual_physical_percent" => $summary["project_actual_percent"],
            "planned_financial_value" => $summary["financial_planned_today"],
            "realized_financial_value" => $summary["financial_realized_today"],
            "spi" => $summary["spi"],
            "cpi" => $summary["cpi"],
            "forecast_end_date" => null,
            "delay_days" => 0
        );

        if ($existing) {
            $data["id"] = $existing->id;
        }

        return $this->project_snapshots_model->save($data);
    }
}
