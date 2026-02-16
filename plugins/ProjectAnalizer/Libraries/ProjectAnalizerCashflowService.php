<?php

namespace ProjectAnalizer\Libraries;

use ProjectAnalizer\Models\Revenue_planned_model;
use ProjectAnalizer\Models\Revenue_realized_model;

class ProjectAnalizerCashflowService
{
    protected $evolution_service;
    protected $revenue_planned_model;
    protected $revenue_realized_model;

    public function __construct()
    {
        $this->evolution_service = new ProjectAnalizerEvolutionService();
        $this->revenue_planned_model = new Revenue_planned_model();
        $this->revenue_realized_model = new Revenue_realized_model();
    }

    public function getMonthlySummary($project_id, $options = array())
    {
        $negative_days_threshold = (int)get_array_value($options, "negative_days_threshold", 7);
        $revenue_delay_threshold = (float)get_array_value($options, "revenue_delay_threshold", 10);

        $baseline_range = $this->evolution_service->get_baseline_range($project_id);
        if (!$baseline_range["has_baseline"]) {
            return array(
                "success" => false,
                "message" => "Gere o baseline antes de consultar o resumo."
            );
        }

        $planned_rows = $this->revenue_planned_model->get_details(array("project_id" => $project_id));
        $realized_rows = $this->revenue_realized_model->get_details(array("project_id" => $project_id));
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

        $expenses_summary = $this->evolution_service->get_expenses_monthly_summary($project_id, $range_start, $range_end);

        $revenue_planned_by_month = array();
        $revenue_realized_by_month = array();
        $revenue_planned_cum = array();
        $revenue_realized_cum = array();

        $net_planned_by_month = array();
        $net_realized_by_month = array();
        $net_cumulative = array();

        $planned_running = 0;
        $realized_running = 0;
        $net_running = 0;

        $negative_months = 0;

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

            $expense_planned_month = get_array_value($expenses_summary["planned_by_month"], $index, 0);
            $expense_realized_month = get_array_value($expenses_summary["realized_by_month"], $index, 0);

            $net_planned = round($planned_month - $expense_planned_month, 2);
            $net_realized = round($realized_month - $expense_realized_month, 2);

            $net_planned_by_month[] = $net_planned;
            $net_realized_by_month[] = $net_realized;

            $net_running += $net_realized;
            $net_cumulative[] = round($net_running, 2);

            if ($net_running < 0) {
                $negative_months++;
            } else {
                $negative_months = 0;
            }
        }

        $alerts = array();
        $negative_month_threshold = (int)ceil($negative_days_threshold / 30);
        if ($net_running < 0 && $negative_months >= $negative_month_threshold) {
            $alerts[] = "Caixa negativo";
        }

        $last_planned = end($revenue_planned_cum);
        $last_realized = end($revenue_realized_cum);
        $delay_limit = $last_planned ? ($last_planned * (1 - ($revenue_delay_threshold / 100))) : 0;
        if ($last_planned > 0 && $last_realized < $delay_limit) {
            $alerts[] = "Faturamento atrasado";
        }

        return array(
            "success" => true,
            "labels" => $labels,
            "expenses" => array(
                "planned_by_month" => $expenses_summary["planned_by_month"],
                "realized_by_month" => $expenses_summary["realized_by_month"],
                "planned_cum" => $expenses_summary["planned_cum"],
                "realized_cum" => $expenses_summary["realized_cum"]
            ),
            "revenue" => array(
                "planned_by_month" => $revenue_planned_by_month,
                "realized_by_month" => $revenue_realized_by_month,
                "planned_cum" => $revenue_planned_cum,
                "realized_cum" => $revenue_realized_cum
            ),
            "net" => array(
                "planned_by_month" => $net_planned_by_month,
                "realized_by_month" => $net_realized_by_month,
                "cumulative" => $net_cumulative
            ),
            "alerts" => $alerts
        );
    }

    public function getCards($project_id, $options = array())
    {
        $summary = $this->getMonthlySummary($project_id, $options);
        if (!get_array_value($summary, "success")) {
            return $summary;
        }

        $expenses_planned_today = (float)end($summary["expenses"]["planned_cum"]);
        $expenses_realized_today = (float)end($summary["expenses"]["realized_cum"]);
        $revenue_planned_today = (float)end($summary["revenue"]["planned_cum"]);
        $revenue_realized_today = (float)end($summary["revenue"]["realized_cum"]);
        $net_today = $revenue_realized_today - $expenses_realized_today;
        $net_cumulative = (float)end($summary["net"]["cumulative"]);

        return array(
            "success" => true,
            "expenses_planned_today" => round($expenses_planned_today, 2),
            "expenses_realized_today" => round($expenses_realized_today, 2),
            "revenue_planned_today" => round($revenue_planned_today, 2),
            "revenue_realized_today" => round($revenue_realized_today, 2),
            "net_today" => round($net_today, 2),
            "net_cumulative" => round($net_cumulative, 2),
            "alerts" => $summary["alerts"]
        );
    }
}
