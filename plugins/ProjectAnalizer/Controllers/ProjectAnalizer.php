<?php

namespace ProjectAnalizer\Controllers;
use App\Models\Project_settings_model;
use App\Controllers\Security_Controller;
use App\Controllers\Projects;
use App\Controllers\Tasks;
use ProjectAnalizer\Models\Team_activities_model;
use ContaAzul\Libraries\ContaAzulClient;
use ProjectAnalizer\Libraries\ProjectAnalizerCashflowService;

class ProjectAnalizer extends Security_Controller {
    protected $Team_activities_model;

    function __construct() {
        $this->Project_settings_model = new Project_settings_model();
        $this->Task_priority_model = model("App\\Models\\Task_priority_model");
        $this->Checklist_items_model = model("App\\Models\\Checklist_items_model");
        $this->Pin_comments_model = model("App\\Models\\Pin_comments_model");
        
        parent::__construct();
    }

    function index() {
        $this->access_only_team_members();

        $date_from = $this->request->getGet("date_from");
        $date_to = $this->request->getGet("date_to");
        $include_completed = $this->request->getGet("include_completed") ? 1 : 0;
        $project_ids_input = $this->request->getGet("project_ids");

        $selected_project_ids = array();
        if (is_array($project_ids_input)) {
            $selected_project_ids = array_filter(array_map("intval", $project_ids_input));
        } elseif (is_string($project_ids_input) && $project_ids_input !== "") {
            $selected_project_ids = array_filter(array_map("intval", explode(",", $project_ids_input)));
        }

        $range_start = $date_from;
        $range_end = $date_to;
        if (!$range_start || !$range_end) {
            $now = new \DateTime(get_my_local_time("Y-m-d"));
            if (!$range_start) {
                $range_start = $now->format("Y-m-01");
            }
            if (!$range_end) {
                $end = clone $now;
                $end->modify("+5 months");
                $range_end = $end->format("Y-m-t");
            }
        } else {
            if (strlen($range_start) === 7) {
                $range_start .= "-01";
            }
            if (strlen($range_end) === 7) {
                $end_dt = new \DateTime($range_end . "-01");
                $range_end = $end_dt->format("Y-m-t");
            }
        }

        $projects_options = array();
        if (!$this->can_manage_all_projects()) {
            $projects_options["user_id"] = $this->login_user->id;
        }
        $projects = $this->Projects_model->get_details($projects_options)->getResult();

        $completed_status_id = null;
        try {
            $db = db_connect("default");
            $status_table = $db->prefixTable("project_status");
            if ($db->tableExists($status_table)) {
                $row = $db->table($status_table)->select("id")->where("key_name", "completed")->get()->getRow();
                if ($row && isset($row->id)) {
                    $completed_status_id = (int)$row->id;
                }
            }
        } catch (\Throwable $e) {
            $completed_status_id = null;
        }

        $projects_dropdown = array();
        $allowed_project_ids = array();
        foreach ($projects as $project) {
            if (!$include_completed && $completed_status_id && (int)$project->status_id === $completed_status_id) {
                continue;
            }
            $projects_dropdown[] = array("id" => $project->id, "text" => $project->title);
            $allowed_project_ids[] = (int)$project->id;
        }

        if (empty($selected_project_ids)) {
            $selected_project_ids = $allowed_project_ids;
        } else {
            $selected_project_ids = array_values(array_intersect($selected_project_ids, $allowed_project_ids));
        }

        $evolution_service = new ProjectAnalizerCashflowService();
        $range_builder = new \ProjectAnalizer\Libraries\ProjectAnalizerEvolutionService();
        $range = $range_builder->build_month_range($range_start, $range_end);
        $labels = $range["labels"];
        $label_index = array();
        foreach ($labels as $idx => $label) {
            $label_index[$label] = $idx;
        }

        $planned_revenue = array_fill(0, count($labels), 0.0);
        $planned_expenses = array_fill(0, count($labels), 0.0);
        $realized_revenue = array_fill(0, count($labels), 0.0);
        $realized_expenses = array_fill(0, count($labels), 0.0);

        foreach ($selected_project_ids as $project_id) {
            $summary = $evolution_service->getMonthlySummary($project_id);
            if (!get_array_value($summary, "success")) {
                continue;
            }

            $summary_labels = get_array_value($summary, "labels", array());
            $rev_planned = get_array_value($summary, "revenue", array());
            $exp_planned = get_array_value($summary, "expenses", array());

            foreach ($summary_labels as $i => $label) {
                if (!isset($label_index[$label])) {
                    continue;
                }
                $idx = $label_index[$label];

                $planned_revenue[$idx] += (float)get_array_value($rev_planned["planned_by_month"], $i, 0);
                $realized_revenue[$idx] += (float)get_array_value($rev_planned["realized_by_month"], $i, 0);
                $planned_expenses[$idx] += (float)get_array_value($exp_planned["planned_by_month"], $i, 0);
                $realized_expenses[$idx] += (float)get_array_value($exp_planned["realized_by_month"], $i, 0);
            }
        }

        $planned_balance = array();
        $realized_balance = array();
        $planned_running = 0;
        $realized_running = 0;
        foreach ($labels as $i => $label) {
            $planned_running += ($planned_revenue[$i] - $planned_expenses[$i]);
            $realized_running += ($realized_revenue[$i] - $realized_expenses[$i]);
            $planned_balance[] = round($planned_running, 2);
            $realized_balance[] = round($realized_running, 2);
        }

        $totals = array(
            "planned_revenue" => round(array_sum($planned_revenue), 2),
            "planned_expenses" => round(array_sum($planned_expenses), 2),
            "realized_revenue" => round(array_sum($realized_revenue), 2),
            "realized_expenses" => round(array_sum($realized_expenses), 2)
        );
        $totals["realized_balance"] = round($totals["realized_revenue"] - $totals["realized_expenses"], 2);
        $totals["budget_saving"] = round($totals["planned_expenses"] - $totals["realized_expenses"], 2);

        $view_data = array(
            "projects_dropdown" => $projects_dropdown,
            "selected_project_ids" => $selected_project_ids,
            "include_completed" => $include_completed,
            "date_from" => $range_start,
            "date_to" => $range_end,
            "labels" => $labels,
            "planned_revenue" => $planned_revenue,
            "planned_expenses" => $planned_expenses,
            "realized_revenue" => $realized_revenue,
            "realized_expenses" => $realized_expenses,
            "planned_balance" => $planned_balance,
            "realized_balance" => $realized_balance,
            "totals" => $totals
        );

        return $this->template->rander('ProjectAnalizer\Views\projectanalizer\index', $view_data);
    }

    function sync_cost_centers() {
        // sincroniza centros de custo do ContaAzul ao abrir o modal de projeto
        $this->access_only_team_members();

        $clientId = get_setting("contaazul_client_id");
        $clientSecret = get_setting("contaazul_client_secret");
        $redirectUri = get_setting("contaazul_redirect_uri") ?: get_uri("contaazul/callback");
        $scope = get_setting("contaazul_scope") ?: "openid profile aws.cognito.signin.user.admin";

        if (!$clientId || !$clientSecret) {
            return $this->response->setJSON(array(
                "success" => false,
                "message" => "Conta Azul nÃ£o configurado.",
                "cost_centers" => array()
            ));
        }

        $client = new ContaAzulClient($clientId, $clientSecret, $redirectUri, $scope);
        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $tokens = $client->getTokens();
                if (!empty($tokens["access_token"])) {
                    $Settings_model = new \App\Models\Settings_model();
                    $Settings_model->save_setting("contaazul_access_token", $tokens["access_token"]);
                    $Settings_model->save_setting("contaazul_refresh_token", $tokens["refresh_token"] ?? "");
                    $Settings_model->save_setting("contaazul_token_expires_at", $tokens["expires_at"] ?? "");
                }
            }
        }

        $db = db_connect('default');
        $table = $db->prefixTable('contaazul_cost_centers');
        if (!$db->tableExists($table)) {
            return $this->response->setJSON(array(
                "success" => false,
                "message" => "Tabela de centros de custo nÃ£o encontrada.",
                "cost_centers" => array()
            ));
        }

        $page = 1;
        $size = 100;
        $imported = 0;
        $updated = 0;
        $items = array();

        do {
            $resp = $client->listCostCenters($page, $size);
            if (!$resp["ok"]) {
                break;
            }

            $data = is_array($resp["data"]) ? $resp["data"] : array();
            $pageItems = array();
            $fallbackKeys = array("centrosDeCusto", "centros_de_custo", "centros_custo", "centros", "itens", "items", "data", "content");
            foreach ($fallbackKeys as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    $pageItems = $data[$key];
                    break;
                }
            }
            if (empty($pageItems) && isset($data[0])) {
                $pageItems = $data;
            }

            foreach ($pageItems as $item) {
                $item = is_array($item) ? $item : (array)$item;
                $caId = $item["id"] ?? "";
                $code = $item["codigo"] ?? ($item["code"] ?? "");
                $title = $item["descricao"] ?? ($item["description"] ?? ($item["nome"] ?? ($item["name"] ?? "")));
                $isActive = isset($item["ativo"]) ? (int)!!$item["ativo"] : (isset($item["active"]) ? (int)!!$item["active"] : 1);

                if (!$caId || !$title) {
                    continue;
                }

                $existing = $db->table($table)->where("ca_id", $caId)->get()->getRow();
                if ($existing) {
                    $db->table($table)->where("id", $existing->id)->update(array(
                        "code" => $code,
                        "title" => $title,
                        "is_active" => $isActive,
                        "updated_at" => get_current_utc_time()
                    ));
                    $updated++;
                } else {
                    $db->table($table)->insert(array(
                        "ca_id" => $caId,
                        "code" => $code,
                        "title" => $title,
                        "is_active" => $isActive,
                        "created_at" => get_current_utc_time(),
                        "updated_at" => get_current_utc_time()
                    ));
                    $imported++;
                }
            }

            $items = array_merge($items, $pageItems);
            $page++;
        } while (count($items) < ($resp["total"] ?? 0) && !empty($resp["ok"]));

        $costCenters = $db->table($table)
            ->like("title", "PROJETO", "after")
            ->orderBy("title", "ASC")
            ->get()
            ->getResult();

        $list = array();
        foreach ($costCenters as $cc) {
            $list[] = array("id" => $cc->id, "title" => $cc->title);
        }

        return $this->response->setJSON(array(
            "success" => true,
            "imported" => $imported,
            "updated" => $updated,
            "cost_centers" => $list
        ));
    }

    function overview($project_id) 
        {
        

            validate_numeric_value($project_id);
            $this->access_only_team_members();
            $this->init_project_permission_checker($project_id);

            $view_data = $this->_get_project_info_data($project_id);
            $view_data["task_statuses"] = $this->Tasks_model->get_task_statistics(array("project_id" => $project_id))->task_statuses;

            $view_data['project_id'] = $project_id;
            $offset = 0;
            $view_data['offset'] = $offset;
            $view_data['activity_logs_params'] = array("log_for" => "project", "log_for_id" => $project_id, "limit" => 20, "offset" => $offset);

            $view_data["can_add_remove_project_members"] = $this->can_add_remove_project_members();
            $view_data["can_access_clients"] = $this->can_access_clients(true);

            $view_data['custom_fields_list'] = $this->Custom_fields_model->get_combined_details("projects", $project_id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

            //count total worked hours
            $options = array("project_id" => $project_id);

            //get allowed member ids
            $members = $this->_get_members_to_manage_timesheet();
            if ($members != "all") {
                //if user has permission to access all members, query param is not required
                $options["allowed_members"] = $members;
            }

            $info = $this->Timesheets_model->count_total_time($options);
            $base_total_hours = (float)$info->timesheet_total / 60 / 60;

            // soma de horas das atividades (multiplicando pela quantidade de pessoas)
            $activities_total_hours = 0;
            $activities_table_exists = false;
            try {
                $db = db_connect("default");
                $activities_table = $db->prefixTable("team_activities");
                if ($db->tableExists($activities_table)) {
                    $activities_table_exists = true;
                    $builder = $db->table($activities_table);
                    $builder->where("project_id", $project_id);
                    if ($db->fieldExists("deleted", $activities_table)) {
                        $builder->where("deleted", 0);
                    }
                    $rows = $builder->get()->getResult();
                    foreach ($rows as $row) {
                        $members_ids = array();
                        if (!empty($row->members_ids)) {
                            $decoded = json_decode($row->members_ids, true);
                            if (is_array($decoded)) {
                                $members_ids = $decoded;
                            }
                        }
                        $people_count = count($members_ids);
                        if ($people_count <= 0) {
                            $people_count = 1;
                        }

                        $hours = 0;
                        if ($row->time_mode === "hours") {
                            $hours = (float)$row->hours;
                        } elseif ($row->time_mode === "period" && $row->start_datetime && $row->end_datetime) {
                            $start_ts = strtotime($row->start_datetime);
                            $end_ts = strtotime($row->end_datetime);
                            if ($start_ts && $end_ts && $end_ts > $start_ts) {
                                $hours = ($end_ts - $start_ts) / 3600;
                            }
                        }

                        $activities_total_hours += ($hours * $people_count);
                    }
                }
            } catch (\Throwable $e) {
                $activities_total_hours = 0;
            }

            $total_project_hours = $activities_total_hours > 0 ? $activities_total_hours : $base_total_hours;
            $view_data["total_project_hours"] = to_decimal_format($total_project_hours);

            // resultado do projeto = valor de venda - custos realizados
            $project_value = 0;
            if (isset($view_data["project_info"]) && isset($view_data["project_info"]->price)) {
                $project_value = (float)$view_data["project_info"]->price;
            }

            $cost_realized_total = 0;
            try {
                $db = db_connect("default");
                $table = get_db_prefix() . "projectanalizer_cost_realized";
                if ($db->tableExists($table)) {
                    $cost_realized_model = new \ProjectAnalizer\Models\Cost_realized_model();
                    $cost_realized_query = $cost_realized_model->get_details(array("project_id" => $project_id));
                    $cost_realized_rows = $cost_realized_query ? $cost_realized_query->getResult() : array();
                    foreach ($cost_realized_rows as $row) {
                        $cost_realized_total += (float)$row->value;
                    }
                }
            } catch (\Throwable $e) {
                $cost_realized_total = 0;
            }

            $cost_planned_total = 0;
            try {
                $db = db_connect("default");
                $planned_table = get_db_prefix() . "projectanalizer_task_costs";
                if ($db->tableExists($planned_table)) {
                    $task_ids = array();
                    $tasks_table = $db->prefixTable("tasks");
                    if ($db->tableExists($tasks_table)) {
                        $task_rows = $db->table($tasks_table)->select("id")->where("project_id", $project_id)->get()->getResult();
                        foreach ($task_rows as $task_row) {
                            $task_ids[] = (int)$task_row->id;
                        }
                    }
                    if (!empty($task_ids)) {
                        $task_costs_model = new \ProjectAnalizer\Models\Task_costs_model();
                        $planned_rows = $task_costs_model->get_details(array("task_ids" => $task_ids));
                        $planned_items = $planned_rows ? $planned_rows->getResult() : array();
                        foreach ($planned_items as $row) {
                            $cost_planned_total += (float)$row->planned_value;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $cost_planned_total = 0;
            }

            $tax_service_percent = 0;
            $tax_predicted = 0;
            try {
                $company_id = 0;
                if (isset($this->login_user->company_id) && $this->login_user->company_id) {
                    $company_id = (int)$this->login_user->company_id;
                } else {
                    $company_id = (int)get_default_company_id();
                }
                $settings_model = new \Proposals\Models\Proposals_module_settings_model();
                $settings = $settings_model->get_settings($company_id);
                if (!empty($settings->taxes_json)) {
                    $decoded = json_decode($settings->taxes_json, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $tax) {
                            $name = strtolower(trim((string)($tax['name'] ?? '')));
                            if ($name === 'imposto servico') {
                                $tax_service_percent = (float)($tax['percent'] ?? 0);
                                break;
                            }
                        }
                    }
                }
                if ($tax_service_percent > 0) {
                    $tax_predicted = $project_value * ($tax_service_percent / 100);
                }
            } catch (\Throwable $e) {
                $tax_service_percent = 0;
                $tax_predicted = 0;
            }

            $view_data["project_result_summary"] = array(
                "project_value" => $project_value,
                "costs_realized" => $cost_realized_total,
                "costs_planned" => $cost_planned_total,
                "result_value" => $project_value - ($cost_realized_total + $tax_predicted),
                "tax_service_percent" => $tax_service_percent,
                "tax_predicted" => $tax_predicted
            );

            $planned_cashflow = null;
            try {
                $cashflow_service = new ProjectAnalizerCashflowService();
                $summary = $cashflow_service->getMonthlySummary($project_id);
                if (get_array_value($summary, "success")) {
                    $planned_cum = array();
                    $running = 0;
                    $planned_revenue = get_array_value($summary, "revenue", array());
                    $planned_expenses = get_array_value($summary, "expenses", array());
                    $rev_by_month = get_array_value($planned_revenue, "planned_by_month", array());
                    $exp_by_month = get_array_value($planned_expenses, "planned_by_month", array());
                    $max = max(count($rev_by_month), count($exp_by_month));
                    for ($i = 0; $i < $max; $i++) {
                        $rev = isset($rev_by_month[$i]) ? (float)$rev_by_month[$i] : 0;
                        $exp = isset($exp_by_month[$i]) ? (float)$exp_by_month[$i] : 0;
                        $running += ($rev - $exp);
                        $planned_cum[] = round($running, 2);
                    }
                    $summary["net"]["planned_cumulative"] = $planned_cum;
                    $planned_cashflow = $summary;
                }
            } catch (\Throwable $e) {
                $planned_cashflow = null;
            }
            $view_data["planned_cashflow"] = $planned_cashflow;

        return $this->template->view('ProjectAnalizer\Views\projectanalizer\overview', $view_data);
        }

    function etapas($project_id) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $view_data["project_id"] = $project_id;
        $view_data["can_create_milestones"] = $this->can_create_milestones();
        $view_data["can_edit_milestones"] = $this->can_edit_milestones();
        $view_data["can_delete_milestones"] = $this->can_delete_milestones();

        return $this->template->view("ProjectAnalizer\\Views\\etapas\\index", $view_data);
    }

    function tasks($project_id) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        if (!$this->_can_view_project_tasks($project_id)) {
            app_redirect("forbidden");
        }

        $view_data['project_id'] = $project_id;
        $view_data['view_type'] = "project_tasks";

        $view_data['can_create_tasks'] = $this->_can_create_project_tasks($project_id);
        $view_data['can_edit_tasks'] = $this->_can_edit_project_tasks($project_id);
        $view_data['can_delete_tasks'] = $this->_can_delete_project_tasks($project_id);
        $view_data["show_milestone_info"] = $this->can_view_milestones();

        $view_data['milestone_dropdown'] = $this->_get_milestones_dropdown_list($project_id);
        $view_data['priorities_dropdown'] = $this->_get_priorities_dropdown_list();
        $view_data['assigned_to_dropdown'] = $this->_get_project_members_dropdown_list($project_id);
        $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("tasks", $this->login_user->is_admin, $this->login_user->user_type);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("tasks", $this->login_user->is_admin, $this->login_user->user_type);

        $exclude_status_ids = $this->_get_removed_task_status_ids($project_id);
        $view_data['task_statuses'] = $this->Task_status_model->get_details(array("exclude_status_ids" => $exclude_status_ids))->getResult();

        $view_data["show_assigned_tasks_only"] = get_array_value($this->login_user->permissions, "show_assigned_tasks_only");
        $view_data['labels_dropdown'] = json_encode($this->make_labels_dropdown("task", "", true));

        return $this->template->view("ProjectAnalizer\\Views\\tasks\\index", $view_data);
    }

    function task_modal_form() {
        $id = $this->request->getPost('id');
        $add_type = $this->request->getPost('add_type');
        $last_id = $this->request->getPost('last_id');
        $project_id = $this->request->getPost('project_id');

        $this->validate_submitted_data(array(
            "id" => "numeric",
            "last_id" => "numeric",
            "project_id" => "numeric"
        ));

        $model_info = $this->Tasks_model->get_one($id);
        if ($add_type == "multiple" && $last_id) {
            $model_info = $this->Tasks_model->get_one($last_id);
        }

        if (!$project_id && $model_info && $model_info->project_id) {
            $project_id = $model_info->project_id;
        }

        if ($id) {
            if (!$this->can_edit_tasks($model_info)) {
                app_redirect("forbidden");
            }
        } else {
            if (!$this->_can_create_project_tasks($project_id)) {
                app_redirect("forbidden");
            }
        }

        $view_data = array();
        $view_data["show_contexts_dropdown"] = false;
        $view_data['selected_context'] = "project";
        $view_data['contexts'] = array("project");
        $view_data['project_id'] = $project_id;
        $view_data['model_info'] = $model_info;
        $view_data["add_type"] = $add_type;
        $view_data['is_clone'] = $this->request->getPost('is_clone');
        $view_data['view_type'] = $this->request->getPost("view_type");

        $view_data['show_assign_to_dropdown'] = true;
        if ($this->login_user->user_type == "client") {
            if (!get_setting("client_can_assign_tasks")) {
                $view_data['show_assign_to_dropdown'] = false;
            }
        } else {
            if (!$id && !$view_data['model_info']->assigned_to) {
                $view_data['model_info']->assigned_to = $this->login_user->id;
            }
        }

        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("tasks", $view_data['model_info']->id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();
        $view_data['has_checklist'] = $this->Checklist_items_model->get_details(array("task_id" => $id))->resultID->num_rows;
        $view_data['has_sub_task'] = count($this->Tasks_model->get_all_where(array("parent_task_id" => $id, "deleted" => 0))->getResult());

        $project_deadline = "";
        if ($project_id) {
            $project_info = $this->Projects_model->get_one($project_id);
            $project_deadline = $project_info && $project_info->deadline ? $project_info->deadline : "";
        }
        $view_data["project_deadline"] = $project_deadline;
        $view_data["show_time_with_task"] = (get_setting("show_time_with_task_start_date_and_deadline")) ? true : false;
        $view_data['time_format_24_hours'] = get_setting("time_format") == "24_hours" ? true : false;

        $view_data = array_merge($view_data, $this->_get_task_related_dropdowns_for_project($project_id));
        $view_data["projects_dropdown"] = array();
        if ($project_id) {
            $project_info = $this->Projects_model->get_one($project_id);
            $project_title = $project_info && $project_info->title ? $project_info->title : ("#" . $project_id);
            $view_data["projects_dropdown"] = array(array("id" => $project_id, "text" => $project_title));
        }

        $labor_profiles_model = model("ProjectAnalizer\\Models\\Labor_profiles_model");
        $task_labor_profiles_model = model("ProjectAnalizer\\Models\\Task_labor_profiles_model");
        $labor_profiles = $labor_profiles_model->get_active_profiles();
        $labor_profiles = $labor_profiles ? $labor_profiles->getResult() : array();
        $task_labor_profiles = array();
        if ($model_info && $model_info->id) {
            $task_profiles_query = $task_labor_profiles_model->get_task_profiles($model_info->id);
            $task_labor_profiles = $task_profiles_query ? $task_profiles_query->getResult() : array();
        }
        $view_data["labor_profiles"] = $labor_profiles;
        $view_data["task_labor_profiles"] = $task_labor_profiles;

        return $this->template->view('ProjectAnalizer\\Views\\tasks\\modal_form', $view_data);
    }

    function task_view($task_id = 0) {
        validate_numeric_value($task_id);
        $view_type = "";

        if ($task_id) {
            $view_type = "details";
        } else {
            $task_id = $this->request->getPost('id');
        }

        $model_info = $this->Tasks_model->get_details(array("id" => $task_id))->getRow();
        if (!$model_info || !$model_info->id) {
            show_404();
        }

        $project_id = $model_info->project_id;
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id);

        if (!$this->_can_view_project_tasks($project_id)) {
            app_redirect("forbidden");
        }

        if ($model_info->context == "project" && $this->has_all_projects_restricted_role()) {
            app_redirect("forbidden");
        }

        $view_data = $this->_get_task_related_dropdowns_for_project($project_id);

        $view_data['show_assign_to_dropdown'] = true;
        if ($this->login_user->user_type == "client" && !get_setting("client_can_assign_tasks")) {
            $view_data['show_assign_to_dropdown'] = false;
        }

        $view_data['can_edit_tasks'] = $this->can_edit_tasks($model_info);
        $view_data['can_edit_task_status'] = $this->_can_edit_task_status($model_info);
        $view_data['can_comment_on_tasks'] = $this->_can_comment_on_tasks($model_info);

        $view_data['model_info'] = $model_info;
        $view_data['collaborators'] = $this->_get_collaborators($model_info->collaborator_list, false);
        $view_data['labels'] = make_labels_view_data($model_info->labels_list);

        $options = array("task_id" => $task_id, "login_user_id" => $this->login_user->id);
        $view_data['comments'] = $this->Project_comments_model->get_details($options)->getResult();
        $view_data['task_id'] = $task_id;

        $view_data['custom_fields_list'] = $this->Custom_fields_model->get_combined_details("tasks", $task_id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();
        $view_data['pinned_comments'] = $this->Pin_comments_model->get_details(array("task_id" => $task_id, "pinned_by" => $this->login_user->id))->getResult();

        $checklist_items_array = array();
        $checklist_items = $this->Checklist_items_model->get_details(array("task_id" => $task_id))->getResult();
        foreach ($checklist_items as $checklist_item) {
            $checklist_items_array[] = $this->_make_checklist_item_row($checklist_item);
        }
        $view_data["checklist_items"] = json_encode($checklist_items_array);

        $sub_tasks_array = array();
        $sub_tasks = $this->Tasks_model->get_details(array("parent_task_id" => $task_id))->getResult();
        foreach ($sub_tasks as $sub_task) {
            $sub_tasks_array[] = $this->_make_sub_task_row($sub_task);
        }
        $view_data["sub_tasks"] = json_encode($sub_tasks_array);
        $view_data["total_sub_tasks"] = $this->Tasks_model->count_sub_task_status(array("parent_task_id" => $task_id));
        $view_data["completed_sub_tasks"] = $this->Tasks_model->count_sub_task_status(array("parent_task_id" => $task_id, "status_id" => 3));

        $view_data["show_timer"] = get_setting("module_project_timesheet") ? true : false;
        if ($this->login_user->user_type === "client") {
            $view_data["show_timer"] = false;
        }

        $view_data["disable_timer"] = false;
        $user_has_any_timer = $this->Timesheets_model->user_has_any_timer($this->login_user->id);
        if ($user_has_any_timer && !get_setting("users_can_start_multiple_timers_at_a_time")) {
            $view_data["disable_timer"] = true;
        }

        $timer = $this->Timesheets_model->get_task_timer_info($task_id, $this->login_user->id)->getRow();
        $view_data['timer_status'] = $timer ? "open" : "";

        $view_data['project_id'] = $project_id;
        $view_data['can_create_tasks'] = $this->_can_create_project_tasks($project_id);
        $view_data['parent_task_title'] = $this->Tasks_model->get_one($model_info->parent_task_id)->title;
        $view_data["view_type"] = $view_type;

        $view_data["blocked_by"] = $this->_make_dependency_tasks_view_data($this->_get_all_dependency_for_this_task_specific($model_info->blocked_by, $task_id, "blocked_by"), $task_id, "blocked_by");
        $view_data["blocking"] = $this->_make_dependency_tasks_view_data($this->_get_all_dependency_for_this_task_specific($model_info->blocking, $task_id, "blocking"), $task_id, "blocking");

        $view_data["project_deadline"] = $this->_get_project_deadline_for_task($project_id);

        $timesheet_options = array("project_id" => $project_id, "task_id" => $model_info->id);
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all" && $this->login_user->user_type == "staff") {
            $timesheet_options["allowed_members"] = $members;
        }

        $info = $this->Timesheets_model->count_total_time($timesheet_options);
        $view_data["total_task_hours"] = convert_seconds_to_time_format($info->timesheet_total);
        $view_data["show_timesheet_info"] = $this->can_view_timesheet($project_id);
        $view_data["show_time_with_task"] = (get_setting("show_time_with_task_start_date_and_deadline")) ? true : false;

        $view_data['contexts'] = array("project");
        $view_data["checklist_templates"] = $this->Checklist_template_model->get_details()->getResult();
        $view_data["checklist_groups"] = $this->Checklist_groups_model->get_details()->getResult();

        if ($view_type == "details") {
            return $this->template->rander('ProjectAnalizer\\Views\\tasks\\view', $view_data);
        } else {
            return $this->template->view('ProjectAnalizer\\Views\\tasks\\view', $view_data);
        }
    }

    function update_task_percentage() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $percentage = $this->request->getPost('percentage');
        $percentage = is_null($percentage) ? 0 : floatval(str_replace(",", ".", $percentage));
        $percentage = max(0, min(100, round($percentage, 2)));

        $task_info = $this->Tasks_model->get_one($id);
        if (!$task_info || !$task_info->id) {
            echo json_encode(array("success" => false, "message" => app_lang("invalid_request")));
            return;
        }

        if (!$this->can_edit_tasks($task_info)) {
            app_redirect("forbidden");
        }

        $project_id = $task_info->project_id;
        $milestone_id = $task_info->milestone_id;
        if ($project_id && $milestone_id) {
            $db = db_connect('default');
            $tasks_table = $db->prefixTable('tasks');
            $builder = $db->table($tasks_table);
            $builder->select("SUM(percentage) AS total_percentage");
            $builder->where("project_id", $project_id);
            $builder->where("milestone_id", $milestone_id);
            $builder->where("deleted", 0);
            $builder->where("id !=", $id);
            $row = $builder->get()->getRow();
            $total_percentage = $row && $row->total_percentage ? (float)$row->total_percentage : 0;
            if (($total_percentage + $percentage) > 100.0001) {
                echo json_encode(array("success" => false, "message" => "A soma do percentual das tarefas por etapa nao pode ultrapassar 100%"));
                return;
            }
        }

        $save_id = $this->Tasks_model->ci_save(array("percentage" => $percentage), $id);
        if ($save_id) {
            echo json_encode(array(
                "success" => true,
                "percentage" => number_format($percentage, 2, ".", "")
            ));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang("error_occurred")));
        }
    }

    function tasks_list_data($project_id = 0, $is_mobile = 0) {
        validate_numeric_value($project_id);
        validate_numeric_value($is_mobile);

        if (!$this->_can_view_project_tasks($project_id)) {
            app_redirect("forbidden");
        }

        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("tasks", $this->login_user->is_admin, $this->login_user->user_type);

        $milestone_id = $this->request->getPost('milestone_id');
        $quick_filter = $this->request->getPost('quick_filter');
        if ($quick_filter) {
            $status = "";
        } else {
            $status = $this->request->getPost('status_id') ? implode(",", $this->request->getPost('status_id')) : "";
        }

        $show_time_with_task = (get_setting("show_time_with_task_start_date_and_deadline")) ? true : false;
        $id = get_only_numeric_value($this->request->getPost('id'));

        $options = array(
            "id" => $id,
            "assigned_to" => $this->request->getPost('assigned_to'),
            "deadline" => $this->request->getPost('deadline'),
            "status_ids" => $status,
            "milestone_id" => $milestone_id,
            "priority_id" => $this->request->getPost('priority_id'),
            "custom_fields" => $custom_fields,
            "unread_status_user_id" => $this->login_user->id,
            "quick_filter" => $quick_filter,
            "label_id" => $this->request->getPost('label_id'),
            "custom_field_filter" => $this->prepare_custom_field_filter_values("tasks", $this->login_user->is_admin, $this->login_user->user_type),
            "project_id" => $project_id,
            "show_assigned_tasks_only_user_id" => $this->show_assigned_tasks_only_user_id()
        );

        $all_options = append_server_side_filtering_commmon_params($options);
        $result = $this->Tasks_model->get_details($all_options);

        if (get_array_value($all_options, "server_side")) {
            $list_data = get_array_value($result, "data");
        } else {
            $list_data = $result->getResult();
            $result = array();
        }

        $tasks_edit_permissions = $this->_get_tasks_edit_permissions($list_data);
        $tasks_status_edit_permissions = $this->_get_tasks_status_edit_permissions($list_data, $tasks_edit_permissions);

        $result_data = array();
        foreach ($list_data as $data) {
            $result_data[] = $this->_make_task_row($data, $custom_fields, $show_time_with_task, $tasks_edit_permissions, $tasks_status_edit_permissions, $is_mobile);
        }

        $result["data"] = $result_data;
        echo json_encode($result);
    }

    function save_task() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $project_id = $this->request->getPost('project_id');
        $id = $this->request->getPost('id');
        $add_type = $this->request->getPost('add_type');
        $now = get_current_utc_time();

        if ($id) {
            $task_info = $this->Tasks_model->get_one($id);
            if (!$this->can_edit_tasks($task_info)) {
                app_redirect("forbidden");
            }
        } else {
            if (!$this->_can_create_project_tasks($project_id)) {
                app_redirect("forbidden");
            }
        }

        $percentage = $this->request->getPost('percentage');
        $percentage = is_null($percentage) ? 0 : floatval(str_replace(",", ".", $percentage));
        $percentage = max(0, min(100, round($percentage, 2)));

        $milestone_id = $this->request->getPost('milestone_id');
        if ($project_id && $milestone_id) {
            $db = db_connect('default');
            $tasks_table = $db->prefixTable('tasks');
            $builder = $db->table($tasks_table);
            $builder->select("SUM(percentage) AS total_percentage");
            $builder->where("project_id", $project_id);
            $builder->where("milestone_id", $milestone_id);
            $builder->where("deleted", 0);
            if ($id) {
                $builder->where("id !=", $id);
            }
            $row = $builder->get()->getRow();
            $total_percentage = $row && $row->total_percentage ? (float)$row->total_percentage : 0;
            if (($total_percentage + $percentage) > 100.0001) {
                echo json_encode(array("success" => false, "message" => "A soma do percentual das tarefas por etapa nao pode ultrapassar 100%"));
                return;
            }
        }

        $collaborators = $this->request->getPost('collaborators');
        validate_list_of_numbers($collaborators);

        $labels = $this->request->getPost('labels');
        validate_list_of_numbers($labels);

        $assigned_to = $this->request->getPost('assigned_to');
        $status_id = $this->request->getPost('status_id');
        $priority_id = $this->request->getPost('priority_id');
        $milestone_id = $this->request->getPost('milestone_id');

        $start_date = $this->request->getPost('start_date');
        $deadline = $this->request->getPost('deadline');
        $duration_days = (int)$this->request->getPost('duration_days');

        $start_time = $this->request->getPost('start_time');
        $end_time = $this->request->getPost('end_time');
        if (get_setting("time_format") != "24_hours") {
            $start_time = convert_time_to_24hours_format($start_time);
            $end_time = convert_time_to_24hours_format($end_time);
        }

        if ($start_time && (strlen($start_time) == 4 || strlen($start_time) == 7)) {
            $start_time = "0" . $start_time;
        }

        if ($end_time && (strlen($end_time) == 4 || strlen($end_time) == 7)) {
            $end_time = "0" . $end_time;
        }

        if ($start_date) {
            $start_date = $this->_normalize_task_date($start_date);
            if ($start_date && $start_time) {
                $start_date = $start_date . " " . $start_time;
            }
        }
        if ($deadline) {
            $deadline = $this->_normalize_task_date($deadline);
            if ($deadline && $end_time) {
                $deadline = $deadline . " " . $end_time;
            }
        }
        if ($duration_days <= 0 && $start_date && $deadline) {
            $duration_days = $this->_calculate_business_days($start_date, $deadline);
        }

        if ($this->login_user->user_type == "client") {
            if (!get_setting("client_can_assign_tasks")) {
                $assigned_to = 0;
                $collaborators = "";
            }
        }

        $data = array(
            "title" => $this->request->getPost('title'),
            "description" => $this->request->getPost('description'),
            "project_id" => $project_id ? $project_id : 0,
            "milestone_id" => $milestone_id ? $milestone_id : 0,
            "points" => $this->request->getPost('points'),
            "status_id" => $status_id,
            "priority_id" => $priority_id ? $priority_id : 0,
            "labels" => $labels,
            "start_date" => $start_date,
            "deadline" => $deadline,
            "duration_days" => $duration_days > 0 ? $duration_days : null,
            "assigned_to" => $assigned_to,
            "collaborators" => $collaborators,
            "percentage" => $percentage,
            "context" => "project"
        );

        if (!$id) {
            $data["created_date"] = $now;
            $data["sort"] = $this->Tasks_model->get_next_sort_value($project_id, $status_id);
            $data["created_by"] = $this->login_user->id;
        }

        $data = clean_data($data);

        if (!$data["start_date"]) {
            $data["start_date"] = NULL;
        }

        if (!$data["deadline"]) {
            $data["deadline"] = NULL;
        }

        if ($data["start_date"] && $data["deadline"] && $data["deadline"] < $data["start_date"]) {
            echo json_encode(array("success" => false, 'message' => app_lang('deadline_must_be_equal_or_greater_than_start_date')));
            return;
        }

        $save_id = $this->Tasks_model->ci_save($data, $id);
        if ($save_id) {
            $labor_profiles_present = $this->request->getPost("labor_profiles_present");
            $labor_profiles = $this->request->getPost("labor_profiles");
            if ($labor_profiles_present || $labor_profiles !== null) {
                if (is_string($labor_profiles)) {
                    $decoded = json_decode($labor_profiles, true);
                    if (is_array($decoded)) {
                        $labor_profiles = $decoded;
                    }
                }

                $labor_items = array();
                if (is_array($labor_profiles)) {
                    foreach ($labor_profiles as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $labor_profile_id = get_array_value($item, "labor_profile_id");
                        if (!$labor_profile_id) {
                            continue;
                        }
                        if (!array_key_exists("qty_people", $item) || $item["qty_people"] === "" || $item["qty_people"] === null) {
                            $item["qty_people"] = 1;
                        }
                        $item["project_id"] = $project_id;
                        $labor_items[] = $item;
                    }
                }

                $task_labor_profiles_model = model("ProjectAnalizer\\Models\\Task_labor_profiles_model");
                $task_labor_profiles_model->upsert_task_profiles($save_id, $labor_items);
            }

            $activity_log_id = get_array_value($data, "activity_log_id");
            save_custom_fields("tasks", $save_id, $this->login_user->is_admin, $this->login_user->user_type, $activity_log_id);

            $row_data = $this->_task_row_data($save_id);
            echo json_encode(array("success" => true, "data" => $row_data, 'id' => $save_id, 'message' => app_lang('record_saved'), "add_type" => $add_type));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    private function _task_row_data($id) {
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("tasks", $this->login_user->is_admin, $this->login_user->user_type);
        $options = array("id" => $id, "custom_fields" => $custom_fields);
        $data = $this->Tasks_model->get_details($options)->getRow();

        $show_time_with_task = (get_setting("show_time_with_task_start_date_and_deadline")) ? true : false;
        $tasks_edit_permissions = $this->_get_tasks_edit_permissions(array($data));
        $tasks_status_edit_permissions = $this->_get_tasks_status_edit_permissions(array($data), $tasks_edit_permissions);
        $is_mobile = $this->request->getPost('mobile_mirror') ? 1 : 0;

        return $this->_make_task_row($data, $custom_fields, $show_time_with_task, $tasks_edit_permissions, $tasks_status_edit_permissions, $is_mobile);
    }

    private function _make_task_row($data, $custom_fields, $show_time_with_task, $tasks_edit_permissions, $tasks_status_edit_permissions, $is_mobile = 0) {
        $task_title_class = "js-selection-id ";
        $icon = "";
        if (isset($data->unread) && $data->unread && $data->unread != "0") {
            $task_title_class .= " unread-comments-of-tasks";
            $icon = "<i data-feather='message-circle' class='icon-16 ml5 unread-comments-of-tasks-icon'></i>";
        }

        $title = "";
        $main_task_id = "#" . $data->id;
        $sub_task_search_column = "#" . $data->id;

        $sub_task = "";
        if ($data->parent_task_id) {
            $sub_task_search_column = "#" . $data->parent_task_id;
            $sub_task = "<span class='sub-task-icon mr5' title='" . app_lang("sub_task") . "'><i data-feather='git-merge' class='icon-14'></i></span>";
            $title = $sub_task;
        }

        $toggle_sub_task_icon = "";
        if ($data->has_sub_tasks) {
            $toggle_sub_task_icon = "<span class='filter-sub-task-button clickable ml5' title='" . app_lang("show_sub_tasks") . "' main-task-id= '$main_task_id'><i data-feather='filter' class='icon-16'></i></span>";
        }

        $title .= modal_anchor(get_uri("projectanalizer/task_view"), $data->title . $icon, array("title" => app_lang('task_info') . " #$data->id", "data-post-id" => $data->id, "data-search" => $sub_task_search_column, "class" => $task_title_class, "data-id" => $data->id, "data-modal-lg" => "1"));

        $task_point = "";
        if ($data->points > 1) {
            $task_point .= "<span class='badge badge-light clickable mt0' title='" . app_lang('points') . "'>" . $data->points . "</span> ";
        }
        $title .= "<span class='float-end ml5'>" . $task_point . "</span>";

        $task_priority = "";
        if ($data->priority_id) {
            $task_priority = "<span class='float-end circle-badge' title='" . app_lang('priority') . ": " . $data->priority_title . "'>
                            <span class='sub-task-icon priority-badge' style='background: $data->priority_color'><i data-feather='$data->priority_icon' class='icon-14'></i></span> $toggle_sub_task_icon
                      </span>";

            $title .= $task_priority;
        } else {
            $title .= "<span class='float-end'>" . $toggle_sub_task_icon . "</span>";
        }

        $task_labels = make_labels_view_data($data->labels_list, $is_mobile ? false : true);
        $title .= "<span class='float-end mr5'>" . $task_labels . "</span>";

        $milestone_title = "-";
        if ($data->milestone_title) {
            $milestone_title = $data->milestone_title;
        }

        $milestone_percentage_label = "-";
        if (isset($data->percentage)) {
            $milestone_percentage_label = to_decimal_format($data->percentage, false) . "%";
        }

        $collaborators = $this->_get_collaborators($data->collaborator_list);
        if (!$collaborators) {
            $collaborators = "-";
        }

        $checkbox_class = "checkbox-blank";
        if ($data->status_key_name === "done") {
            $checkbox_class = "checkbox-checked";
        }

        if (get_array_value($tasks_status_edit_permissions, $data->id)) {
            $check_status = js_anchor("<span class='$checkbox_class mr15 float-start'></span>", array('title' => "", "data-id" => $data->id, "data-value" => $data->status_key_name === "done" ? "1" : "3", "data-act" => "update-task-status-checkbox")) . $data->id;
            $status = js_anchor($data->status_key_name ? app_lang($data->status_key_name) : $data->status_title, array('title' => "", "class" => "", "data-id" => $data->id, "data-value" => $data->status_id, "data-act" => "update-task-status", "data-modifier-group" => "task_info"));
        } else {
            if ($checkbox_class == "checkbox-blank") {
                $checkbox_class = "checkbox-un-checked";
            }
            $check_status = "<span class='$checkbox_class mr15 float-start'></span> " . $data->id;
            $status = $data->status_key_name ? app_lang($data->status_key_name) : $data->status_title;
        }

        $id = $data->id;
        if (get_setting("show_the_status_checkbox_in_tasks_list")) {
            $id = $check_status;
        }

        $deadline_text = "-";
        if ($data->deadline && is_date_exists($data->deadline)) {
            if ($show_time_with_task) {
                if (date("H:i:s", strtotime($data->deadline)) == "00:00:00") {
                    $deadline_text = format_to_date($data->deadline, false);
                } else {
                    $deadline_text = format_to_relative_time($data->deadline, false, false, true);
                }
            } else {
                $deadline_text = format_to_date($data->deadline, false);
            }

            if (get_my_local_time("Y-m-d") > $data->deadline && $data->status_id != "3") {
                $deadline_text = "<span class='text-danger'>" . $deadline_text . "</span> ";
            } else if (format_to_date(get_my_local_time(), false) == format_to_date($data->deadline, false) && $data->status_id != "3") {
                $deadline_text = "<span class='text-warning'>" . $deadline_text . "</span> ";
            }
        }

        $start_date = "-";
        if (is_date_exists($data->start_date)) {
            if ($show_time_with_task) {
                if (date("H:i:s", strtotime($data->start_date)) == "00:00:00") {
                    $start_date = format_to_date($data->start_date, false);
                } else {
                    $start_date = format_to_relative_time($data->start_date, false, false, true);
                }
            } else {
                $start_date = format_to_date($data->start_date, false);
            }
        }

        if ($is_mobile) {
            $title = "<div class='box-wrapper'>
              <div class='box-avatar hover'>$assigned_to_avatar</div>" .
                  modal_anchor(
                      get_uri("projectanalizer/task_view"),
                      "<div class='dark text-wrap'>" . $sub_task . " <span class='mini-view-task-id'>" . $data->id . " - </span>" . $data->title . "</div>
                          <div class='d-flex'>" . $task_point . $task_priority . $task_labels . "</div>",
                      array(
                          "class" => "box-label",
                          "data-post-id" => $data->id,
                          "data-modal-lg" => "1"
                      )
                  ) .
                  "</div>";
        }

        $options = "";
        if (get_array_value($tasks_edit_permissions, $data->id)) {
            $options .= modal_anchor(get_uri("projectanalizer/task_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_task'), "data-post-id" => $data->id));
        }
        if ($this->can_delete_tasks($data)) {
            $options .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_task'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("tasks/delete"), "data-action" => "delete-confirmation"));
        }

        $percentage_value = "0.00";
        $db = db_connect('default');
        $percentage_total = 0;

        $timesheet_table = $db->prefixTable('project_time');
        if ($db->tableExists($timesheet_table) && $db->fieldExists('percentage_executed', $timesheet_table)) {
            $builder = $db->table($timesheet_table);
            $builder->select("SUM(percentage_executed) AS total_percentage");
            $builder->where("task_id", $data->id);
            if ($db->fieldExists('deleted', $timesheet_table)) {
                $builder->where("deleted", 0);
            }
            $query = $builder->get();
            if ($query) {
                $row = $query->getRow();
                if ($row && $row->total_percentage !== null) {
                    $percentage_total += (float)$row->total_percentage;
                }
            }
        }

        $activities_table = $db->prefixTable('team_activities');
        if ($db->tableExists($activities_table) && $db->fieldExists('percentage_executed', $activities_table)) {
            $builder = $db->table($activities_table);
            $builder->select("SUM(percentage_executed) AS total_percentage");
            $builder->where("task_id", $data->id);
            if ($db->fieldExists('deleted', $activities_table)) {
                $builder->where("deleted", 0);
            }
            $query = $builder->get();
            if ($query) {
                $row = $query->getRow();
                if ($row && $row->total_percentage !== null) {
                    $percentage_total += (float)$row->total_percentage;
                }
            }
        }

        $percentage_total = max(0, min(100, $percentage_total));
        $percentage_value = number_format($percentage_total, 2, ".", "");
        $percentage_progress_class = ((float)$percentage_value >= 100) ? "progress-bar-success" : "bg-primary";
        $percentage_bar = "<div class='ml10 mr10 clearfix'><span class='float-start'>{$percentage_value}%</span></div>
            <div class='progress mt0' title='{$percentage_value}%'>
                <div class='progress-bar {$percentage_progress_class}' role='progressbar' aria-valuenow='{$percentage_value}' aria-valuemin='0' aria-valuemax='100' style='width: {$percentage_value}%'>
                </div>
            </div>";

        $row_data = array(
            $data->status_color,
            $id,
            $title,
            $milestone_percentage_label,
            $percentage_bar,
            $data->title,
            $task_labels,
            $data->priority_title,
            $data->points,
            $data->start_date,
            $start_date,
            $data->deadline,
            $deadline_text,
            $milestone_title,
            $collaborators,
            $status
        );

        foreach ($custom_fields as $field) {
            $cf_id = "cfv_" . $field->id;
            $row_data[] = $this->template->view("custom_fields/output_" . $field->field_type, array("value" => $data->$cf_id));
        }

        $row_data[] = $options;
        return $row_data;
    }

    private function _get_tasks_edit_permissions($tasks = array()) {
        $permissions = array();
        foreach ($tasks as $task) {
            if ($task && isset($task->id)) {
                $permissions[$task->id] = $this->can_edit_tasks($task);
            }
        }
        return $permissions;
    }

    private function _get_tasks_status_edit_permissions($tasks = array(), $tasks_edit_permissions = array()) {
        $permissions = array();
        foreach ($tasks as $task) {
            if ($task && isset($task->id)) {
                $permissions[$task->id] = get_array_value($tasks_edit_permissions, $task->id) ? true : false;
            }
        }
        return $permissions;
    }

    private function _update_task_status_by_percentage($task_id) {
        $task_id = get_only_numeric_value($task_id);
        if (!$task_id) {
            return;
        }

        $percentage_total = $this->_get_task_execution_percentage_total($task_id);
        if ($percentage_total <= 0) {
            return;
        }

        $Task_status_model = new \App\Models\Task_status_model();
        $status_list = $Task_status_model->get_details()->getResult();
        $done_status_id = null;
        $in_progress_status_id = null;

        foreach ($status_list as $status) {
            if ($status->key_name === "done") {
                $done_status_id = $status->id;
            } elseif ($status->key_name === "in_progress") {
                $in_progress_status_id = $status->id;
            }
        }

        $target_status_id = null;
        if ($percentage_total >= 100 && $done_status_id) {
            $target_status_id = $done_status_id;
        } elseif ($percentage_total > 0 && $percentage_total < 100 && $in_progress_status_id) {
            $target_status_id = $in_progress_status_id;
        }

        if ($target_status_id) {
            $status_data = array("status_id" => $target_status_id);
            $this->Tasks_model->ci_save($status_data, $task_id);
        }
    }

    private function _get_task_execution_percentage_total($task_id) {
        $task_id = get_only_numeric_value($task_id);
        if (!$task_id) {
            return 0;
        }

        $db = db_connect('default');
        $percentage_total = 0;

        $timesheet_table = $db->prefixTable('project_time');
        if ($db->tableExists($timesheet_table) && $db->fieldExists('percentage_executed', $timesheet_table)) {
            $builder = $db->table($timesheet_table);
            $builder->select("SUM(percentage_executed) AS total_percentage");
            $builder->where("task_id", $task_id);
            if ($db->fieldExists('deleted', $timesheet_table)) {
                $builder->where("deleted", 0);
            }
            $query = $builder->get();
            if ($query) {
                $row = $query->getRow();
                if ($row && $row->total_percentage !== null) {
                    $percentage_total += (float) $row->total_percentage;
                }
            }
        }

        $activities_table = $db->prefixTable('team_activities');
        if ($db->tableExists($activities_table) && $db->fieldExists('percentage_executed', $activities_table)) {
            $builder = $db->table($activities_table);
            $builder->select("SUM(percentage_executed) AS total_percentage");
            $builder->where("task_id", $task_id);
            if ($db->fieldExists('deleted', $activities_table)) {
                $builder->where("deleted", 0);
            }
            $query = $builder->get();
            if ($query) {
                $row = $query->getRow();
                if ($row && $row->total_percentage !== null) {
                    $percentage_total += (float) $row->total_percentage;
                }
            }
        }

        return max(0, min(100, round($percentage_total, 2)));
    }

    private function _can_edit_task_status($task_info) {
        if ($task_info->project_id && get_array_value($this->login_user->permissions, "can_update_only_assigned_tasks_status") == "1") {
            $collaborators_array = explode(',', $task_info->collaborators);
            if ($task_info->assigned_to == $this->login_user->id || in_array($this->login_user->id, $collaborators_array)) {
                return true;
            }
        } else {
            return $this->can_edit_tasks($task_info);
        }
    }

    private function _can_comment_on_tasks($task_info) {
        $project_id = $task_info->project_id;

        if ($this->login_user->user_type != "staff") {
            if ($project_id && get_setting("client_can_comment_on_tasks") && $this->_is_clients_project($project_id)) {
                return true;
            }

            return false;
        }

        if ($project_id && $this->can_manage_all_projects()) {
            return true;
        } else if ($project_id && $this->_user_has_project_task_comment_permission() && $this->_is_user_a_project_member($project_id)) {
            return true;
        } else if (!$project_id) {
            return $this->can_edit_tasks($task_info);
        }
    }

    private function _user_has_project_task_comment_permission() {
        return get_array_value($this->login_user->permissions, "can_comment_on_tasks") == "1";
    }

    private function _make_dependency_tasks_view_data($task_ids = "", $task_id = 0, $type = "") {
        if ($task_ids) {
            $tasks = "";
            $tasks_list = $this->Tasks_model->get_details(array("task_ids" => $task_ids))->getResult();

            foreach ($tasks_list as $task) {
                $tasks .= $this->_make_dependency_tasks_row_data($task, $task_id, $type);
            }

            return $tasks;
        }
    }

    private function _make_dependency_tasks_row_data($task_info, $task_id, $type) {
        $tasks = "";

        $tasks .= "<div id='dependency-task-row-$task_info->id' class='list-group-item mb5 dependency-task-row b-a rounded' style='border-left: 5px solid $task_info->status_color !important;'>";

        if ($this->can_edit_tasks($task_info)) {
            $tasks .= ajax_anchor(get_uri("projectanalizer/delete_dependency_task/$task_info->id/$task_id/$type"), "<div class='float-end'><i data-feather='x' class='icon-16'></i></div>", array("class" => "delete-dependency-task", "title" => app_lang("delete"), "data-fade-out-on-success" => "#dependency-task-row-$task_info->id", "data-dependency-type" => $type));
        }

        $tasks .= modal_anchor(get_uri("projectanalizer/task_view"), $task_info->title, array("data-post-id" => $task_info->id, "data-modal-lg" => "1"));

        $tasks .= "</div>";

        return $tasks;
    }

    private function _get_all_dependency_for_this_task_specific($task_ids = "", $task_id = 0, $type = "") {
        if ($task_id && $type) {
            $dependency_tasks = $this->Tasks_model->get_all_dependency_for_this_task($task_id, $type);

            if ($dependency_tasks) {
                if ($task_ids) {
                    $task_ids .= "," . $dependency_tasks;
                } else {
                    $task_ids = $dependency_tasks;
                }
            }

            return $task_ids;
        }
    }

    function delete_dependency_task($dependency_task_id, $task_id, $type) {
        validate_numeric_value($dependency_task_id);
        validate_numeric_value($task_id);
        $task_info = $this->Tasks_model->get_one($task_id);

        if (!$this->can_edit_tasks($task_info)) {
            app_redirect("forbidden");
        }

        $dependency_tasks_of_own = $task_info->$type;
        if ($type == "blocked_by") {
            $dependency_tasks_of_others = $this->Tasks_model->get_one($dependency_task_id)->blocking;
        } else {
            $dependency_tasks_of_others = $this->Tasks_model->get_one($dependency_task_id)->blocked_by;
        }

        if (!strpos($dependency_tasks_of_own, ',') && $dependency_tasks_of_own == $dependency_task_id) {
            $data = array($type => "");
            $this->Tasks_model->update_custom_data($data, $task_id);
        } else if (!strpos($dependency_tasks_of_others, ',') && $dependency_tasks_of_others == $task_id) {
            $data = array((($type == "blocked_by") ? "blocking" : "blocked_by") => "");
            $this->Tasks_model->update_custom_data($data, $dependency_task_id);
        } else {
            $dependency_tasks_of_own_array = explode(',', $dependency_tasks_of_own);
            $dependency_tasks_of_others_array = explode(',', $dependency_tasks_of_others);

            if (in_array($dependency_task_id, $dependency_tasks_of_own_array)) {
                unset($dependency_tasks_of_own_array[array_search($dependency_task_id, $dependency_tasks_of_own_array)]);
                $dependency_tasks_of_own_array = implode(',', $dependency_tasks_of_own_array);
                $data = array($type => $dependency_tasks_of_own_array);
                $this->Tasks_model->update_custom_data($data, $task_id);
            } else if (in_array($task_id, $dependency_tasks_of_others_array)) {
                unset($dependency_tasks_of_others_array[array_search($task_id, $dependency_tasks_of_others_array)]);
                $dependency_tasks_of_others_array = implode(',', $dependency_tasks_of_others_array);
                $data = array((($type == "blocked_by") ? "blocking" : "blocked_by") => $dependency_tasks_of_others_array);
                $this->Tasks_model->update_custom_data($data, $dependency_task_id);
            }
        }
    }

    private function _get_project_deadline_for_task($project_id = 0) {
        if (!$project_id) {
            return "";
        }

        $project_deadline_date = "";
        $project_deadline = $this->Projects_model->get_one($project_id)->deadline;
        if (get_setting("task_deadline_should_be_before_project_deadline") && is_date_exists($project_deadline)) {
            $project_deadline_date = format_to_date($project_deadline, false);
        }

        return $project_deadline_date;
    }

    private function _get_collaborators($collaborator_list, $clickable = true) {
        $collaborators = "";
        if ($collaborator_list) {
            $collaborators_array = explode(",", $collaborator_list);
            foreach ($collaborators_array as $collaborator) {
                $collaborator_parts = explode("--::--", $collaborator);

                $collaborator_id = get_array_value($collaborator_parts, 0);
                $collaborator_name = get_array_value($collaborator_parts, 1);
                $image_url = get_avatar(get_array_value($collaborator_parts, 2));
                $user_type = get_array_value($collaborator_parts, 3);

                $_comma = "";
                if ($collaborators) {
                    $_comma = ", ";
                }
                $collaboratr_image = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt='...'></span><span class='hide'>$_comma $collaborator_name</span>";

                if ($clickable) {
                    if ($user_type == "staff") {
                        $collaborators .= get_team_member_profile_link($collaborator_id, $collaboratr_image, array("title" => $collaborator_name));
                    } else if ($user_type == "client") {
                        $collaborators .= get_client_contact_profile_link($collaborator_id, $collaboratr_image, array("title" => $collaborator_name));
                    }
                } else {
                    $collaborators .= "<span title='$collaborator_name'>$collaboratr_image</span>";
                }
            }
        }
        return $collaborators;
    }

    private function can_edit_tasks($_task = null) {
        $task_info = is_object($_task) ? $_task : $this->Tasks_model->get_one($_task);
        if (!$task_info || !$task_info->project_id) {
            return false;
        }

        if ($this->login_user->is_admin) {
            return true;
        }

        if ($this->login_user->user_type !== "staff") {
            return false;
        }

        if ($this->can_manage_all_projects()) {
            return true;
        }

        if (!$this->_user_has_project_task_edit_permission()) {
            return false;
        }

        return $this->Project_members_model->is_user_a_project_member($task_info->project_id, $this->login_user->id);
    }

    private function can_delete_tasks($_task = null) {
        $task_info = is_object($_task) ? $_task : $this->Tasks_model->get_one($_task);
        if (!$task_info || !$task_info->project_id) {
            return false;
        }

        if ($this->login_user->is_admin) {
            return true;
        }

        if ($this->login_user->user_type !== "staff") {
            return false;
        }

        if ($this->can_manage_all_projects()) {
            return true;
        }

        if (!$this->_user_has_project_task_delete_permission()) {
            return false;
        }

        return $this->Project_members_model->is_user_a_project_member($task_info->project_id, $this->login_user->id);
    }

    private function _get_task_related_dropdowns_for_project($project_id) {
        $milestones_dropdown = array(array("id" => "", "text" => "-"));
        if ($project_id) {
            $milestones = $this->Milestones_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
            foreach ($milestones as $milestone) {
                $milestones_dropdown[] = array("id" => $milestone->id, "text" => $milestone->title);
            }
        }

        $assign_to_dropdown = array(array("id" => "", "text" => "-"));
        $collaborators_dropdown = array();

        if ($project_id) {
            $show_client_contacts = $this->can_access_clients(true);
            if ($this->login_user->user_type === "client" && get_setting("client_can_assign_tasks")) {
                $show_client_contacts = true;
            }

            $user_ids = array();
            if (get_array_value($this->login_user->permissions, "hide_team_members_list_from_dropdowns") == "1") {
                $user_ids[] = $this->login_user->id;
            }

            $project_members = $this->Project_members_model->get_project_members_id_and_text_dropdown($project_id, $user_ids, $show_client_contacts, true);
        } else {
            $project_members = array();
        }

        if ($project_members) {
            $assign_to_dropdown = array_merge($assign_to_dropdown, $project_members);
            $collaborators_dropdown = array_merge($collaborators_dropdown, $project_members);
        }

        $label_suggestions = $this->make_labels_dropdown("task");

        $task_status_options = array();
        if ($project_id) {
            $task_status_options["exclude_status_ids"] = $this->_get_removed_task_status_ids($project_id);
        }

        $statuses_dropdown = array();
        $statuses = $this->Task_status_model->get_details($task_status_options)->getResult();
        foreach ($statuses as $status) {
            $statuses_dropdown[] = array("id" => $status->id, "text" => $status->key_name ? app_lang($status->key_name) : $status->title);
        }

        $task_points = array();
        $task_point_range = get_setting("task_point_range");
        $task_point_start = 1;
        if (str_starts_with($task_point_range, '0')) {
            $task_point_start = 0;
        }

        for ($i = $task_point_start; $i <= $task_point_range * 1; $i++) {
            if ($i <= 1) {
                $task_points[$i] = $i . " " . app_lang('point');
            } else {
                $task_points[$i] = $i . " " . app_lang('points');
            }
        }

        $priorities = $this->Task_priority_model->get_details()->getResult();
        $priorities_dropdown = array(array("id" => "", "text" => "-"));
        foreach ($priorities as $priority) {
            $priorities_dropdown[] = array("id" => $priority->id, "text" => $priority->title);
        }

        return array(
            "related_to_dropdowns" => array(),
            "milestones_dropdown" => $milestones_dropdown,
            "assign_to_dropdown" => $assign_to_dropdown,
            "collaborators_dropdown" => $collaborators_dropdown,
            "label_suggestions" => $label_suggestions,
            "statuses_dropdown" => $statuses_dropdown,
            "priorities_dropdown" => $priorities_dropdown,
            "points_dropdown" => $task_points
        );
    }

    function etapas_list_data($project_id = 0) {
        validate_numeric_value($project_id);
        $this->init_project_permission_checker($project_id);

        $Milestones_model = model("App\\Models\\Milestones_model");
        $list_data = $Milestones_model->get_details(array("project_id" => $project_id))->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_etapa_row($data);
        }

        echo json_encode(array("data" => $result));
    }

    function etapa_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $project_id = $this->request->getPost('project_id');
        $this->init_project_permission_checker($project_id);

        if ($id) {
            if (!$this->can_edit_milestones()) {
                app_redirect("forbidden");
            }
        } else {
            if (!$this->can_create_milestones()) {
                app_redirect("forbidden");
            }
        }

        $Milestones_model = model("App\\Models\\Milestones_model");
        $view_data["model_info"] = $Milestones_model->get_one($id);
        $view_data["project_id"] = $project_id;

        return $this->template->view("ProjectAnalizer\\Views\\etapas\\modal_form", $view_data);
    }

    function save_etapa() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $project_id = $this->request->getPost('project_id');
        $this->init_project_permission_checker($project_id);

        if ($id) {
            if (!$this->can_edit_milestones()) {
                app_redirect("forbidden");
            }
        } else {
            if (!$this->can_create_milestones()) {
                app_redirect("forbidden");
            }
        }

        $percentage = $this->request->getPost('percentage');
        $percentage = is_null($percentage) ? 0 : floatval(str_replace(",", ".", $percentage));
        $percentage = max(0, min(100, round($percentage, 2)));

        $Milestones_model = model("App\\Models\\Milestones_model");
        $db = db_connect('default');
        $milestones_table = $db->prefixTable('milestones');
        $where = "project_id = " . (int)$project_id . " AND deleted = 0";
        if ($id) {
            $where .= " AND id <> " . (int)$id;
        }
        $sum_row = $db->query("SELECT IFNULL(SUM(percentage), 0) AS total FROM $milestones_table WHERE $where")->getRow();
        $current_total = $sum_row && isset($sum_row->total) ? (float)$sum_row->total : 0;
        if (($current_total + $percentage) > 100.0001) {
            echo json_encode(array("success" => false, "message" => app_lang('milestone_percentage_total_exceeded')));
            return;
        }

        $data = array(
            "title" => $this->request->getPost('title'),
            "description" => $this->request->getPost('description'),
            "project_id" => $this->request->getPost('project_id'),
            "due_date" => $this->request->getPost('due_date'),
            "percentage" => $percentage
        );

        $save_id = $Milestones_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_etapa_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete_etapa() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $Milestones_model = model("App\\Models\\Milestones_model");
        $info = $Milestones_model->get_one($id);
        $this->init_project_permission_checker($info->project_id);

        if (!$this->can_delete_milestones()) {
            app_redirect("forbidden");
        }

        if ($this->request->getPost('undo')) {
            if ($Milestones_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_etapa_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($Milestones_model->delete($id)) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    public function team_activities($project_id) //ABRE INDEX ATIVIDADES
        {
            validate_numeric_value($project_id);
            $this->init_project_permission_checker($project_id);
            $this->init_project_settings($project_id);
        
            if (!$this->can_view_timesheet($project_id)) {
                app_redirect("forbidden");
            }
        
            $view_data["project_id"] = $project_id;
            $view_data["can_add_activity"] = ($this->login_user->user_type === "staff");
        
            // carrega dropdowns
            $this->Project_members_model = new \App\Models\Project_members_model();
            $this->Tasks_model = new \App\Models\Tasks_model();
        
            $view_data["project_members_dropdown"] = json_encode($this->_get_project_members_dropdown_list_for_filter($project_id));
            $view_data["tasks_dropdown"] = $this->_get_timesheet_tasks_dropdown($project_id, true);
        
            return $this->template->view("ProjectAnalizer\\Views\\team_activities\\index", $view_data);

        }
    

    public function team_activities_list($project_id) //LISTA ATIVIDADES DO PROJETO
        {
            $Team_activities_model = new \ProjectAnalizer\Models\Team_activities_model();
            $Users_model = new \App\Models\Users_model();
        
            $list_data = $Team_activities_model->get_details(["project_id" => $project_id])->getResult();
            $result = [];
        
            foreach ($list_data as $data) {
                // 🔹 Inicializa as variáveis para cada loop
                $members_html = "";
                $members = json_decode($data->members_ids, true) ?? [];
        
                // 🔹 Monta os avatares dos membros
                foreach ($members as $member_id) {
                    $user = $Users_model->get_one($member_id);
                    if ($user) {
                        $image_url = get_avatar($user->image, "small");
                        $members_html .= "<img src='{$image_url}' title='" . esc($user->first_name . ' ' . $user->last_name) . "' class='avatar-xs rounded-circle me-1' />";
                    }
                }
        
                // 🔹 Calcula horas
                $hours = 0;
                if ($data->time_mode === 'period' && $data->start_datetime && $data->end_datetime) {
                    $start = new \DateTime($data->start_datetime);
                    $end = new \DateTime($data->end_datetime);
                    $interval = $start->diff($end);
                    $hours = $interval->h + ($interval->i / 60);
                } else {
                    $hours = (float)$data->hours;
                }
        
                $members_count = count($members);
                $total_hours = round($hours * $members_count, 2);
        
                // 🔹 Exibe apenas data inicial
                $display_date = $data->time_mode === 'period'
                    ? format_to_date($data->start_datetime, false)
                    : format_to_date($data->activity_date, false);
        
                // 🔹 Botões de ação (editar / excluir)
                $edit_url = get_uri("projectanalizer/team_activity_modal_form/" . $data->project_id . "/" . $data->id);
                $delete_url = get_uri("projectanalizer/delete_team_activity/" . $data->id);
        
                $options = modal_anchor(
                    $edit_url,
                    "<i data-feather='edit' class='icon-16'></i>",
                    ["class" => "btn btn-default btn-sm", "title" => app_lang("edit_activity")]
                );
        
                $options .= js_anchor(
                    "<i data-feather='trash-2' class='icon-16 text-danger'></i>",
                    [
                        'title' => app_lang('delete_activity'),
                        "class" => "delete btn btn-default btn-sm",
                        "data-id" => $data->id,
                        "data-action-url" => $delete_url,
                        "data-action" => "delete-confirmation"
                    ]
                );
        
                // 🔹 Monta o array de retorno
                  $percentage_label = "-";
                  if (!is_null($data->percentage_executed) && $data->percentage_executed !== "") {
                      $percentage_label = number_format((float)$data->percentage_executed, 2, ".", "") . "%";
                  }

                  $result[] = [
                      $members_html ?: "<span class='text-muted'>-</span>",
                      esc($data->task_title ?? "-"),
                      $display_date,
                      $percentage_label,
                      $total_hours . "h",
                      esc($data->description ?? "-"),
                      $options
                  ];
            }
        
            echo json_encode(["data" => $result]);
        }
    

    public function delete_team_activity($id = 0) //APAGA ATIVIDADES DO PROJETO
        {
            $Team_activities_model = new \ProjectAnalizer\Models\Team_activities_model();

            if ($Team_activities_model->delete($id)) {
                echo json_encode(["success" => true, "message" => app_lang("record_deleted")]);
            } else {
                echo json_encode(["success" => false, "message" => app_lang("error_occurred")]);
            }
        }

    public function team_activity_modal_form($project_id) //ABRE MODAL PARA ADD E EDITAR ATIVIDADES
        {
            validate_numeric_value($project_id);
        
            $this->Project_members_model = new \App\Models\Project_members_model();
            $this->Tasks_model = new \App\Models\Tasks_model();
        
            // 🔹 converte resultado de membros em array
            $members_result = $this->Project_members_model->get_project_members_dropdown_list($project_id, array(), false, true)->getResultArray();
            $members_dropdown = [];
            
            foreach ($members_result as $m) {
                $members_dropdown[$m['user_id']] = $m['member_name']; // ou $m['user_id'] se for o caso
            }
            
           
            // 🔹 converte resultado de tarefas em array
            $view_data["tasks_dropdown"] = $this->Tasks_model
            ->get_dropdown_list(["title"], "id", ["project_id" => $project_id]);
        
            $view_data["project_id"] = $project_id;
            $view_data["members_dropdown"] = $members_dropdown;
           
        
            return $this->template->view("ProjectAnalizer\\Views\\team_activities\\modal_form", $view_data);
        }
       
          public function save_team_activity() //SALVA ATIVIDADES
          {
              $Team_activities_model = new \ProjectAnalizer\Models\Team_activities_model();
          
              $id = $this->request->getPost('id');
              $members = $this->request->getPost('member_id');
              $members_ids = is_array($members) ? json_encode($members) : json_encode([$members]);
              $task_id = $this->request->getPost("task_id");
          
              $time_mode = $this->request->getPost('time_mode');
              $percentage_executed = $this->request->getPost("percentage_executed");
              $percentage_executed = is_null($percentage_executed) ? null : floatval(str_replace(",", ".", $percentage_executed));
              if ($task_id) {
                  if (is_null($percentage_executed)) {
                      echo json_encode(["success" => false, "message" => "Percentual Executado e obrigatorio."]);
                      return;
                  }
                  $percentage_executed = max(0, min(100, round($percentage_executed, 2)));

                  $db = db_connect('default');
            $activities_table = $db->prefixTable('team_activities');
            $builder = $db->table($activities_table);
            $builder->select("SUM(percentage_executed) AS total_percentage");
            $builder->where("task_id", $task_id);
            if ($db->fieldExists('deleted', $activities_table)) {
                $builder->where("deleted", 0);
            }
            if ($id) {
                $builder->where("id !=", $id);
            }
                  $row = $builder->get()->getRow();
                  $total_percentage = $row && $row->total_percentage ? (float)$row->total_percentage : 0;
                  if (($total_percentage + $percentage_executed) > 100.0001) {
                      echo json_encode(["success" => false, "message" => "A soma do percentual da tarefa nao pode ultrapassar 100%."]);
                      return;
                  }
              } else {
                  $percentage_executed = null;
              }
          
              $data = [
                  "project_id" => $this->request->getPost("project_id"),
                  "members_ids" => $members_ids,
                  "task_id" => $task_id,
                  "activity_date" => $this->request->getPost("activity_date"),
                  "time_mode" => $time_mode,
                  "hours" => $time_mode === 'hours' ? $this->request->getPost("hours") : null,
                  "start_datetime" => $time_mode === 'period' ? $this->request->getPost("start_datetime") : null,
                  "end_datetime" => $time_mode === 'period' ? $this->request->getPost("end_datetime") : null,
                  "percentage_executed" => $percentage_executed,
                  "description" => $this->request->getPost("description"),
                  "created_by" => $this->login_user->id
              ];
        
            $save_id = $Team_activities_model->ci_save($data, $id);
            if ($save_id && $task_id) {
                $this->_update_task_status_by_percentage($task_id);
            }
        
            echo json_encode([
                "success" => $save_id ? true : false,
                "message" => app_lang("record_saved")
            ]);
        }

    //TESTES DO DIARIA DE OBRA DO APP

    function timesheets($project_id) //ABRE INDEX DO DIARIO
    {
        validate_numeric_value($project_id);

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id); //since we'll check this permission project wise


        if (!$this->can_view_timesheet($project_id)) {
            app_redirect("forbidden");
        }

        $view_data['project_id'] = $project_id;

        //client can't add log or update settings
        $view_data['can_add_log'] = false;

        if ($this->login_user->user_type === "staff") {
            $view_data['can_add_log'] = true;
        }

        $view_data['project_members_dropdown'] = json_encode($this->_get_project_members_dropdown_list_for_filter($project_id));
        $view_data['tasks_dropdown'] = $this->_get_timesheet_tasks_dropdown($project_id, true);

        $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("timesheets", $this->login_user->is_admin, $this->login_user->user_type);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        $view_data["show_members_dropdown"] = true;
        $timesheet_access_info = $this->get_access_info("timesheet_manage_permission");
        $timesheet_access_type = $timesheet_access_info->access_type;

        if (!$timesheet_access_type || $timesheet_access_type === "own") {
            $view_data["show_members_dropdown"] = false;
        }

        return $this->template->view("ProjectAnalizer\Views\\timesheets/index", $view_data);
    }

    function timesheet_list_data($user_id = 0) //LISTA DIARIO DE OBRA
    {

        $project_id = $this->request->getPost("project_id");

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id); //since we'll check this permission project wise


        if (!$this->can_view_timesheet($project_id, true)) {
            app_redirect("forbidden");
        }

        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        $user_id = $user_id ? $user_id : $this->request->getPost("user_id");
        validate_numeric_value($user_id);

        $options = array(
            "project_id" => $project_id,
            "status" => "none_open",
            "user_id" => $user_id,
            "start_date" => $this->request->getPost("start_date"),
            "end_date" => $this->request->getPost("end_date"),
            "task_id" => $this->request->getPost("task_id"),
            "client_id" => $this->request->getPost("client_id"),
            "custom_fields" => $custom_fields,
            "custom_field_filter" => $this->prepare_custom_field_filter_values("timesheets", $this->login_user->is_admin, $this->login_user->user_type)
        );

        //get allowed member ids
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all" && $this->login_user->user_type == "staff") {
            //if user has permission to access all members, query param is not required
            //client can view all timesheet
            $options["allowed_members"] = $members;
        }

        $all_options = append_server_side_filtering_commmon_params($options);

        $result = $this->Timesheets_model->get_details($all_options);

        //by this, we can handel the server side or client side from the app table prams.
        if (get_array_value($all_options, "server_side")) {
            $list_data = get_array_value($result, "data");
        } else {
            $list_data = $result->getResult();
            $result = array();
        }

        $result_data = array();
        foreach ($list_data as $data) {
            $result_data[] = $this->_make_timesheet_row($data, $custom_fields);
        }

        $result["data"] = $result_data;

        echo json_encode($result);
    }

    function timelog_modal_form() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric"
        ));

        $view_data['time_format_24_hours'] = get_setting("time_format") == "24_hours" ? true : false;
        $model_info = $this->Timesheets_model->get_one($this->request->getPost('id'));
        $project_id = $this->request->getPost('project_id') ? $this->request->getPost('project_id') : $model_info->project_id;

        //set the login user as a default selected member
        if (!$model_info->user_id) {
            $model_info->user_id = $this->login_user->id;
        }

        if (!$model_info->id) {
            //set today's date 
            $model_info->start_time = get_current_utc_time("Y-m-d 12:00:00");
            $model_info->end_time   = get_current_utc_time("Y-m-d 20:00:00");
        }

        
        //get related data
        $related_data = $this->_prepare_all_related_data_for_timelog($project_id);
        $show_porject_members_dropdown = get_array_value($related_data, "show_porject_members_dropdown");
        $view_data["tasks_dropdown"] = get_array_value($related_data, "tasks_dropdown");
        $view_data["project_members_dropdown"] = get_array_value($related_data, "project_members_dropdown");
        $view_data["add_type"] = "";

        $view_data["model_info"] = $model_info;

        if ($model_info->id) {
            $show_porject_members_dropdown = false; //don't allow to edit the user on update.
        }

        $view_data["project_id"] = $project_id;
        $view_data['show_porject_members_dropdown'] = $show_porject_members_dropdown;
        $view_data["projects_dropdown"] = $this->_get_projects_dropdown();

        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("timesheets", $view_data['model_info']->id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

        return $this->template->view('ProjectAnalizer\Views\\timesheets/modal_form', $view_data);
    }

    function get_task_execution_percentage() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "task_id" => "required|numeric"
        ));

        $task_id = get_only_numeric_value($this->request->getPost("task_id"));
        $task_info = $this->Tasks_model->get_one($task_id);

        if (!$task_info || !$task_info->id) {
            echo json_encode(array(
                "success" => false,
                "message" => app_lang("record_not_found")
            ));
            return;
        }

        $percentage_total = $this->_get_task_execution_percentage_total($task_id);

        echo json_encode(array(
            "success" => true,
            "task_id" => $task_id,
            "task_title" => $task_info->title,
            "percentage" => number_format($percentage_total, 2, ".", ""),
            "remaining_percentage" => number_format(max(0, 100 - $percentage_total), 2, ".", "")
        ));
    }

    function get_milestone_percentage_summary() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "milestone_id" => "required|numeric",
            "project_id" => "numeric",
            "task_id" => "numeric"
        ));

        $milestone_id = get_only_numeric_value($this->request->getPost("milestone_id"));
        $project_id = get_only_numeric_value($this->request->getPost("project_id"));
        $task_id = get_only_numeric_value($this->request->getPost("task_id"));

        $milestone_info = $this->Milestones_model->get_one($milestone_id);
        if (!$milestone_info || !$milestone_info->id || $milestone_info->deleted) {
            echo json_encode(array("success" => false, "message" => app_lang("record_not_found")));
            return;
        }

        $project_id = $project_id ?: (int) $milestone_info->project_id;
        $this->init_project_permission_checker($project_id);
        if (!$this->_can_view_project_tasks($project_id)) {
            app_redirect("forbidden");
        }

        $builder = db_connect("default")->table(db_connect("default")->prefixTable("tasks"));
        $builder->select("SUM(percentage) AS total_percentage");
        $builder->where("project_id", $project_id);
        $builder->where("milestone_id", $milestone_id);
        $builder->where("deleted", 0);
        if ($task_id) {
            $builder->where("id !=", $task_id);
        }
        $row = $builder->get()->getRow();
        $allocated_percentage = $row && $row->total_percentage ? (float) $row->total_percentage : 0;

        $current_task_percentage = 0;
        if ($task_id) {
            $task_info = $this->Tasks_model->get_one($task_id);
            if ($task_info && (int) $task_info->id && (int) $task_info->milestone_id === $milestone_id) {
                $current_task_percentage = (float) $task_info->percentage;
            }
        }

        echo json_encode(array(
            "success" => true,
            "milestone_id" => $milestone_id,
            "milestone_title" => $milestone_info->title,
            "allocated_percentage" => number_format($allocated_percentage, 2, ".", ""),
            "remaining_percentage" => number_format(max(0, 100 - $allocated_percentage), 2, ".", ""),
            "current_task_percentage" => number_format($current_task_percentage, 2, ".", "")
        ));
    }

    function get_project_milestone_percentage_summary() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "project_id" => "required|numeric",
            "milestone_id" => "numeric"
        ));

        $project_id = get_only_numeric_value($this->request->getPost("project_id"));
        $milestone_id = get_only_numeric_value($this->request->getPost("milestone_id"));

        $this->init_project_permission_checker($project_id);
        if (!$this->_can_view_project_tasks($project_id)) {
            app_redirect("forbidden");
        }

        $db = db_connect("default");
        $milestones_table = $db->prefixTable("milestones");
        $where = "project_id = " . (int) $project_id . " AND deleted = 0";
        if ($milestone_id) {
            $where .= " AND id <> " . (int) $milestone_id;
        }

        $sum_row = $db->query("SELECT IFNULL(SUM(percentage), 0) AS total FROM $milestones_table WHERE $where")->getRow();
        $allocated_percentage = $sum_row && isset($sum_row->total) ? (float) $sum_row->total : 0;

        $current_milestone_percentage = 0;
        if ($milestone_id) {
            $milestone_info = $this->Milestones_model->get_one($milestone_id);
            if ($milestone_info && (int) $milestone_info->id && !$milestone_info->deleted) {
                $current_milestone_percentage = (float) $milestone_info->percentage;
            }
        }

        echo json_encode(array(
            "success" => true,
            "allocated_percentage" => number_format($allocated_percentage, 2, ".", ""),
            "remaining_percentage" => number_format(max(0, 100 - $allocated_percentage), 2, ".", ""),
            "current_milestone_percentage" => number_format($current_milestone_percentage, 2, ".", "")
        ));
    }

    function delete_timelog() {
        
        $this->access_only_team_members();
        
       
        

        $id = $this->request->getPost('id');
        

        

        $this->check_timelog_update_permission($id);

        if ($this->request->getPost('undo')) {
            if ($this->Timesheets_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_timesheet_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->Timesheets_model->delete($id)) {
                $this->_remove_labor_cost_from_timelog($id);
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    function save_timelog() {
        $this->access_only_team_members();
        $Photos_model = new \ProjectAnalizer\Models\Photos_model();
        
        $id = $this->request->getPost('id');

        $files = $this->request->getFiles();

       

       

        $start_date_time = "";
        $end_date_time = "";
        $hours = "";

        $start_time = $this->request->getPost('start_time');
        $end_time = $this->request->getPost('end_time');
        $note = $this->request->getPost("note");
        $task_id = $this->request->getPost("task_id");
        $percentage_executed = $this->request->getPost("percentage_executed");
        $percentage_executed = is_null($percentage_executed) ? null : floatval(str_replace(",", ".", $percentage_executed));

             

        if ($start_time) {
            //start time and end time mode
            //convert to 24hrs time format
            if (get_setting("time_format") != "24_hours") {
                $start_time = convert_time_to_24hours_format($start_time);
                $end_time = convert_time_to_24hours_format($end_time);
            }

            //join date with time
            $start_date_time = $this->request->getPost('start_date') . " " . $start_time;
            $end_date_time = $this->request->getPost('end_date') . " " . $end_time;

            //add time offset
            $start_date_time = convert_date_local_to_utc($start_date_time);
            $end_date_time = convert_date_local_to_utc($end_date_time);
        } else {
            //date and hour mode
            $date = $this->request->getPost("date");
            $start_date_time = $date . " 00:00:00";
            $end_date_time = $date . " 00:00:00";

            //prepare hours
            $hours = convert_humanize_data_to_hours($this->request->getPost("hours"));
            if (!$hours) {
                echo json_encode(array("success" => false, 'message' => app_lang("hour_log_time_error_message")));
                return false;
            }
        }
        if ($task_id) {
            if (is_null($percentage_executed)) {
                echo json_encode(array("success" => false, "message" => "Percentual Executado e obrigatorio."));
                return false;
            }

            $percentage_executed = max(0, min(100, round($percentage_executed, 2)));
            $db = db_connect('default');
            $timesheet_table = $db->prefixTable('project_time');
            if (!$db->fieldExists('percentage_executed', $timesheet_table)) {
                echo json_encode(array("success" => false, "message" => "Campo Percentual Executado nao existe. Atualize o plugin."));
                return false;
            }
            $builder = $db->table($timesheet_table);
            $builder->select("SUM(percentage_executed) AS total_percentage");
            $builder->where("task_id", $task_id);
            if ($db->fieldExists('deleted', $timesheet_table)) {
                $builder->where("deleted", 0);
            }
            if ($id) {
                $builder->where("id !=", $id);
            }
            $query = $builder->get();
            if (!$query) {
                echo json_encode(array("success" => false, "message" => app_lang("error_occurred")));
                return false;
            }
            $row = $query->getRow();
            $total_percentage = $row && $row->total_percentage ? (float)$row->total_percentage : 0;
            if (($total_percentage + $percentage_executed) > 100.0001) {
                echo json_encode(array("success" => false, "message" => "A soma do percentual da tarefa nao pode ultrapassar 100%."));
                return false;
            }
        } else {
            $percentage_executed = null;
        }

        $collaborators = $this->request->getPost('user_id');

        $project_id = $this->request->getPost('project_id');
        $data = array(
            "user_id"=> $collaborators,
            "project_id" => $project_id,
            "start_time" => $start_date_time,
            "end_time" => $end_date_time,
            "note" => $note ? $note : "",
            "task_id" => $task_id ? $task_id : 0,
            "hours" => $hours,
            "observacoes" => $this->request->getPost('observacoes'),
            "atividade_realizada" => $this->request->getPost('atividade_realizada'),
            "tempo_manha" => $this->request->getPost('tempo_manha'),
            "tempo_tarde" => $this->request->getPost('tempo_tarde'),
            "tempo_noite" => $this->request->getPost('tempo_noite'),
            "percentage_executed" => $percentage_executed,
        );

        //save user_id only on insert and it will not be editable
       
       
        $this->check_timelog_update_permission($id, $project_id, get_array_value($data, "user_id"));
        

        $save_id = $this->Timesheets_model->ci_save($data, $id);
        
        if ($save_id) {

            if (isset($files['photos']) && !empty($files['photos'])) {
                $uploaded_photos = [];
    
                foreach ($files['photos'] as $file) {
                    if ($file->isValid() && !$file->hasMoved()) {
                        $newName = $file->getRandomName();
                        $file->move(FCPATH . 'files/projectanalizer/', $newName);
                        $uploaded_photos[] = $newName;
    
                        $Photos_model->insert([
                            'timelog_id'  => $save_id,
                            'file_name'   => $newName,
                            'file_path'   => 'files/projectanalizer/' . $newName,
                           
                        ]);
                        
                    }
                }
    
                // salva nomes no banco (ex: campo 'photos' separado por vírgulas)
                if (!empty($uploaded_photos)) {
                    $photos_field = implode(",", $uploaded_photos);
                    $_POST['photos'] = $photos_field;
                }
            }



            save_custom_fields("timesheets", $save_id, $this->login_user->is_admin, $this->login_user->user_type);
            if ($task_id) {
                $this->_update_task_status_by_percentage($task_id);
            }

            $this->_log_labor_cost_from_timelog($save_id, $data, $hours);

            echo json_encode(array("success" => true, "data" => $this->_timesheet_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    private function _log_labor_cost_from_timelog($timelog_id, $data, $hours)
    {
        $user_id = get_array_value($data, "user_id");
        $project_id = get_array_value($data, "project_id");
        $task_id = get_array_value($data, "task_id");

        if (!$user_id || !$project_id) {
            return;
        }

        $logged_hours = $hours;
        if (!$logged_hours && get_array_value($data, "start_time") && get_array_value($data, "end_time")) {
            $start_ts = strtotime($data["start_time"]);
            $end_ts = strtotime($data["end_time"]);
            if ($start_ts && $end_ts && $end_ts > $start_ts) {
                $logged_hours = ($end_ts - $start_ts) / 3600;
            }
        }
        if (!$logged_hours || $logged_hours <= 0) {
            return;
        }

        $db = db_connect("default");
        $custom_fields_table = $db->prefixTable("custom_fields");
        $custom_field_values_table = $db->prefixTable("custom_field_values");
        $labor_profiles_table = $db->prefixTable("pa_labor_profiles");
        $cost_realized_table = $db->prefixTable("projectanalizer_cost_realized");

        $field_query = $db->query("SELECT id FROM $custom_fields_table WHERE related_to='team_members' AND title_language_key='labor_profile' AND deleted=0 ORDER BY id DESC LIMIT 1");
        if (!$field_query) {
            return;
        }
        $field_row = $field_query->getRow();
        if (!$field_row || !$field_row->id) {
            return;
        }

        $user_ids = array();
        if (is_string($user_id)) {
            foreach (explode(",", $user_id) as $part) {
                $part = trim($part);
                if ($part !== "" && is_numeric($part)) {
                    $user_ids[] = (int)$part;
                }
            }
        } elseif (is_numeric($user_id)) {
            $user_ids[] = (int)$user_id;
        }

        if (!$user_ids) {
            return;
        }

        $date = "";
        if (get_array_value($data, "start_time")) {
            $date = date("Y-m-d", strtotime($data["start_time"]));
        }
        if (!$date && get_array_value($data, "date")) {
            $date = $data["date"];
        }
        if (!$date) {
            $date = date("Y-m-d");
        }

        $total_value = 0;
        $member_ids = array();
        foreach ($user_ids as $member_id) {
            $value_query = $db->query("SELECT value FROM $custom_field_values_table WHERE related_to_type='team_members' AND related_to_id={$member_id} AND custom_field_id={$field_row->id} AND deleted=0 LIMIT 1");
            if (!$value_query) {
                continue;
            }
            $value_row = $value_query->getRow();
            if (!$value_row || !$value_row->value) {
                continue;
            }

            $profile_name = $db->escapeString(trim($value_row->value));
            if (!$profile_name) {
                continue;
            }

            $profile_query = $db->query("SELECT id, hourly_cost FROM $labor_profiles_table WHERE name='{$profile_name}' AND active=1 ORDER BY id DESC LIMIT 1");
            if (!$profile_query) {
                continue;
            }
            $profile_row = $profile_query->getRow();
            if (!$profile_row || !$profile_row->hourly_cost) {
                continue;
            }

            $total_value += (float)$logged_hours * (float)$profile_row->hourly_cost;
            $member_ids[] = (int)$member_id;
        }

        if ($total_value <= 0) {
            return;
        }

        $users_table = $db->prefixTable("users");
        $names = array();
        if ($member_ids) {
            $id_list = implode(",", $member_ids);
            $users_query = $db->query("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM $users_table WHERE id IN ($id_list)");
            if ($users_query) {
                foreach ($users_query->getResult() as $user_row) {
                    if ($user_row->full_name) {
                        $names[] = trim($user_row->full_name);
                    }
                }
            }
        }

        $description = "Registro de atividade #" . $timelog_id;
        if ($names) {
            $description .= " - " . implode(", ", $names);
        }

        $reference = "timelog:" . $timelog_id;
        $existing_query = $db->query("SELECT id FROM $cost_realized_table WHERE reference='{$reference}' AND deleted=0 LIMIT 1");
        $existing_row = $existing_query ? $existing_query->getRow() : null;

        $payload = array(
            "project_id" => $project_id,
            "task_id" => $task_id ? $task_id : null,
            "cost_type" => "mao_obra",
            "date" => $date,
            "value" => $total_value,
            "description" => $description,
            "reference" => $reference,
            "created_by" => $this->login_user->id
        );
        if ($existing_row && $existing_row->id) {
            $payload["id"] = $existing_row->id;
        }

        $cost_realized_model = model("ProjectAnalizer\\Models\\Cost_realized_model");
        $cost_realized_model->save($payload);
    }

    private function _remove_labor_cost_from_timelog($timelog_id)
    {
        $timelog_id = (int)$timelog_id;
        if (!$timelog_id) {
            return;
        }

        $db = db_connect("default");
        $cost_table = $db->prefixTable("projectanalizer_cost_realized");
        $reference = "timelog:" . $timelog_id;
        $reference_like = "timelog:" . $timelog_id . ":%";
        $rows = $db->query("SELECT id FROM $cost_table WHERE (reference='{$reference}' OR reference LIKE '{$reference_like}') AND deleted=0")->getResult();
        if (!$rows) {
            return;
        }

        $cost_realized_model = model("ProjectAnalizer\\Models\\Cost_realized_model");
        foreach ($rows as $row) {
            if ($row->id) {
                $cost_realized_model->delete($row->id);
            }
        }
    }

    public function delete_photo()
{
    $photo_id = $this->request->getPost('id');
    $Photos_model = new \ProjectAnalizer\Models\Photos_model();

    $photo = $Photos_model->find($photo_id);
    if ($photo) {
        // Apaga o arquivo físico
        @unlink(FCPATH . $photo['file_path']);

        // Remove do banco
        $Photos_model->delete($photo_id);

        return $this->response->setJSON(['success' => true]);
    }

    return $this->response->setJSON(['success' => false]);
}



    
    function timesheet_summary($project_id)
    {
        validate_numeric_value($project_id);

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id); //since we'll check this permission project wise

        if (!$this->can_view_timesheet($project_id)) {
            app_redirect("forbidden");
        }



        $view_data['project_id'] = $project_id;

        $view_data['group_by_dropdown'] = json_encode(
            array(
                array("id" => "", "text" => "- " . app_lang("group_by") . " -"),
                array("id" => "member", "text" => app_lang("member")),
                array("id" => "task", "text" => app_lang("task"))
            )
        );

        $view_data['project_members_dropdown'] = json_encode($this->_get_project_members_dropdown_list_for_filter($project_id));
        $view_data['tasks_dropdown'] = $this->_get_timesheet_tasks_dropdown($project_id, true);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        $view_data["show_members_dropdown"] = true;
        $timesheet_access_info = $this->get_access_info("timesheet_manage_permission");
        $timesheet_access_type = $timesheet_access_info->access_type;

        if (!$timesheet_access_type || $timesheet_access_type === "own") {
            $view_data["show_members_dropdown"] = false;
        }

        return $this->template->view("ProjectAnalizer\Views\\timesheets/summary_list", $view_data);
    }

    function timesheet_chart($project_id = 0)
    {
        validate_numeric_value($project_id);
        $members = $this->_get_members_to_manage_timesheet();

        $view_data['members_dropdown'] = json_encode($this->_prepare_members_dropdown_for_timesheet_filter($members));
        $view_data['projects_dropdown'] = json_encode($this->_get_all_projects_dropdown_list_for_timesheets_filter());
        $view_data["project_id"] = $project_id;

        return $this->template->view("ProjectAnalizer\Views\\timesheets/timesheet_chart", $view_data);
    }
    
    function timesheet_summary_list_data()
    {

        $project_id = $this->request->getPost("project_id");

        //client can't view all projects timesheet. project id is required.
        if (!$project_id) {
            $this->access_only_team_members();
        }

        if ($project_id) {
            $this->init_project_permission_checker($project_id);
            $this->init_project_settings($project_id); //since we'll check this permission project wise

            if (!$this->can_view_timesheet($project_id, true)) {
                app_redirect("forbidden");
            }
        }


        $group_by = $this->request->getPost("group_by");

        $options = array(
            "project_id" => $project_id,
            "status" => "none_open",
            "user_id" => $this->request->getPost("user_id"),
            "start_date" => $this->request->getPost("start_date"),
            "end_date" => $this->request->getPost("end_date"),
            "task_id" => $this->request->getPost("task_id"),
            "group_by" => $group_by,
            "client_id" => $this->request->getPost("client_id"),
            "custom_field_filter" => $this->prepare_custom_field_filter_values("timesheets", $this->login_user->is_admin, $this->login_user->user_type)
        );

        //get allowed member ids
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all" && $this->login_user->user_type == "staff") {
            //if user has permission to access all members, query param is not required
            //client can view all timesheet
            $options["allowed_members"] = $members;
        }

        $list_data = $this->Timesheets_model->get_summary_details($options)->getResult();

        $result = array();
        
        foreach ($list_data as $data) {
            $user_ids = explode(',', $data->user_id); // Dividir os IDs separados por vírgula
            foreach ($user_ids as $user_id) { // Iterar sobre cada ID de usuário

                $Collabor = $this->Users_model->get_details(array('id'=>$user_id))->getRow();

                
                $collaborator_id = $Collabor->id;
                $collaborator_name = $Collabor->first_name.' '.$Collabor->last_name;
                
                
        
                $member = "-";
                $task_title = "-";
        
                if ($group_by != "task") {
                    $image_url = get_avatar($Collabor->image);
                    $user = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span> $collaborator_name";
        
                    // Substituir $data->user_id pelo ID atual do foreach
                    $member = get_team_member_profile_link(trim($user_id), $user);
                }
        
                $project_title = anchor(get_uri("projects/view/" . $data->project_id), $data->project_title);
        
                if ($group_by != "member") {
                    $task_title = modal_anchor(get_uri("tasks/view"), $data->task_title, array("title" => app_lang('task_info') . " #$data->task_id", "data-post-id" => $data->task_id, "data-modal-lg" => "1"));
                    if (!$data->task_title) {
                        $task_title = app_lang("not_specified");
                    }
                }
        
                $duration = convert_seconds_to_time_format(abs($data->total_duration));
        
                $client_name = "-";
                if ($data->timesheet_client_company_name) {
                    $client_name = anchor(get_uri("clients/view/" . $data->timesheet_client_id), $data->timesheet_client_company_name);
                }
        
                // Adicionar uma entrada para cada usuário no array de resultados
                $result[] = array(
                    $project_title,
                    $client_name,
                    $member,
                    $task_title,
                    $duration,
                    to_decimal_format(convert_time_string_to_decimal($duration)),
                    to_decimal_format(convert_time_string_to_decimal($duration), false), // Sempre retorna ponto para o Excel.
                );
            }
        }
        
        echo json_encode(array("data" => $result));
    }
    
    function timesheet_chart_data($project_id = 0)
    {
        if (!$project_id) {
            $project_id = $this->request->getPost("project_id");
        }

        validate_numeric_value($project_id);

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id); //since we'll check this permission project wise

        if (!$this->can_view_timesheet($project_id, true)) {
            app_redirect("forbidden");
        }

        $timesheets = array();
        $timesheets_array = array();
        $ticks = array();

        $start_date = $this->request->getPost("start_date");
        $end_date = $this->request->getPost("end_date");
        $user_id = $this->request->getPost("user_id");

       

        $options = array(
            "start_date" => $start_date,
            "end_date" => $end_date,
            "user_id" => $user_id,
            "project_id" => $project_id
        );

      
        //get allowed member ids
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all" && $this->login_user->user_type == "staff") {
            //if user has permission to access all members, query param is not required
            //client can view all timesheet
            $options["allowed_members"] = $members;
        }

    

        $timesheets_result = $this->Timesheets_model->get_timesheet_statistics($options)->timesheets_data;
        $timesheet_users_result = $this->Timesheets_model->get_timesheet_statistics($options)->timesheet_users_data;

        $user_result = array();
        foreach ($timesheet_users_result as $user) {
            $time = convert_seconds_to_time_format($user->total_sec);
            $user_result[] = "<div class='user-avatar avatar-30 avatar-circle' data-bs-toggle='tooltip' title='" . $user->user_name . " - " . $time . "'><img alt='' src='" . get_avatar($user->user_avatar) . "'></div>";
        }

        $days_of_month = date("t", strtotime($start_date));

        for ($i = 1; $i <= $days_of_month; $i++) {
            $timesheets[$i] = 0;
        }

        foreach ($timesheets_result as $value) {
            $timesheets[$value->day * 1] = $value->total_sec / 60 / 60;
        }

        foreach ($timesheets as $value) {
            $timesheets_array[] = $value;
        }

        for ($i = 1; $i <= $days_of_month; $i++) {
            $ticks[] = $i;
        }
       

        echo json_encode(array("timesheets" => $timesheets_array, "ticks" => $ticks, "timesheet_users_result" => $user_result));
    }


    //FUNÇÕES AUXILIARES
     /* return a row of timesheet list  table */

     private function _prepare_members_dropdown_for_timesheet_filter($members)
     {
         $where = array("user_type" => "staff", "status" => "active");
 
         if ($members != "all" && is_array($members) && count($members)) {
             $where["where_in"] = array("id" => $members);
         }
 
         $users = $this->Users_model->get_dropdown_list(array("first_name", "last_name"), "id", $where);
 
         $members_dropdown = array(array("id" => "", "text" => "- " . app_lang("member") . " -"));
         foreach ($users as $id => $name) {
             $members_dropdown[] = array("id" => $id, "text" => $name);
         }
         return $members_dropdown;
     }

         /* get all projects list according to the login user */

    private function _get_all_projects_dropdown_list_for_timesheets_filter()
    {
        $options = array();

        if (!$this->can_manage_all_projects()) {
            $options["user_id"] = $this->login_user->id;
        }

        $projects = $this->Projects_model->get_details($options)->getResult();

        $projects_dropdown = array(array("id" => "", "text" => "- " . app_lang("project") . " -"));
        foreach ($projects as $project) {
            $projects_dropdown[] = array("id" => $project->id, "text" => $project->title);
        }

        return $projects_dropdown;
    }

     private function _timesheet_row_data($id) {
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        $options = array("id" => $id, "custom_fields" => $custom_fields);
        $data = $this->Timesheets_model->get_details($options)->getRow();
        return $this->_make_timesheet_row($data, $custom_fields);
    }


    private function check_timelog_update_permission($log_id = null, $project_id = null, $user_id = null) {
        if ($log_id) {
            $info = $this->Timesheets_model->get_one($log_id);
            $user_id = $info->user_id;
        }

        if (!$log_id && $user_id === $this->login_user->id) { //adding own timelogs
            return true;
        }

        $members = $this->_get_members_to_manage_timesheet();

        if ($members === "all") {
            return true;
        } else if (is_array($members) && count($members) && in_array($user_id, $members)) {
            //permission: no / own / specific / specific_excluding_own
            $timesheet_manage_permission = get_array_value($this->login_user->permissions, "timesheet_manage_permission");

            if (!$timesheet_manage_permission && $log_id) { //permission: no
                app_redirect("forbidden");
            }

            if ($timesheet_manage_permission === "specific_excluding_own" && $log_id && $user_id === $this->login_user->id) { //permission: specific_excluding_own
                app_redirect("forbidden");
            }

            //permission: own / specific
            return true;
        } else if ($members === "own_project_members" || $members === "own_project_members_excluding_own") {
            if (!$project_id) { //there has $log_id or $project_id
                $project_id = $info->project_id;
            }

            if ($this->Project_members_model->is_user_a_project_member($project_id, $user_id) || $this->Project_members_model->is_user_a_project_member($project_id, $this->login_user->id)) { //check if the login user and timelog user is both on same project
                if ($members === "own_project_members") {
                    return true;
                } else if ($this->login_user->id !== $user_id) {
                    //can't edit own but can edit other user's of project
                    //no need to check own condition here for new timelogs since it's already checked before
                    return true;
                }
            }
        }

        app_redirect("forbidden");
    }


    private function _prepare_all_related_data_for_timelog($project_id = 0) 
    {
        //we have to check if any defined project exists, then go through with the project id
        $show_porject_members_dropdown = false;
        if ($project_id) {
            $tasks_dropdown = $this->_get_timesheet_tasks_dropdown($project_id, true);

            //prepare members dropdown list
            $allowed_members = $this->_get_members_to_manage_timesheet();
            $project_members = "";

            if ($allowed_members === "all") {
                $project_members = $this->Project_members_model->get_project_members_dropdown_list($project_id, array(), false, true)->getResult(); //get all active members of this project
            } else {
                $project_members = $this->Project_members_model->get_project_members_dropdown_list($project_id, $allowed_members, false, true)->getResult();
            }

            $project_members_dropdown = array();
            if ($project_members) {
                foreach ($project_members as $member) {

                    if ($member->user_id !== $this->login_user->id) {
                        $show_porject_members_dropdown = true; //user can manage other users time.
                    }

                    $project_members_dropdown[] = array("id" => $member->user_id, "text" => $member->member_name);
                }
            }
        } else {
            //we have show an empty dropdown when there is no project_id defined
            $tasks_dropdown = json_encode(array(array("id" => "", "text" => "-")));
            $project_members_dropdown = array(array("id" => "", "text" => "-"));
            $show_porject_members_dropdown = true;
        }

        return array(
            "project_members_dropdown" => $project_members_dropdown,
            "tasks_dropdown" => $tasks_dropdown,
            "show_porject_members_dropdown" => $show_porject_members_dropdown
        );
    }


    private function _make_timesheet_row($data, $custom_fields) 
    {
        $image_url = get_avatar($data->logged_by_avatar);
        $user = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span> $data->logged_by_user";

        $start_time = $data->start_time;
        $end_time = $data->end_time;
        $project_title = anchor(get_uri("projects/view/" . $data->project_id), $data->project_title);
        $task_title = modal_anchor(get_uri("tasks/view"), $data->task_title, array("title" => app_lang('task_info') . " #$data->task_id", "data-post-id" => $data->task_id, "data-modal-lg" => "1"));

        $client_name = "-";
        if ($data->timesheet_client_company_name) {
            $client_name = anchor(get_uri("clients/view/" . $data->timesheet_client_id), $data->timesheet_client_company_name);
        }
        $collaborators = explode(",", $data->user_id);
        $count = count( $collaborators);

        $seconds = $data->hours 
        ? (round(($data->hours * 60), 0) * 60) 
        : (abs(strtotime($end_time) - strtotime($start_time)));
    
        $duration_in_seconds = $seconds * $count; // Multiplica o resultado em segundos pela variável
        
        $duration = convert_seconds_to_time_format($duration_in_seconds); // Converte para o formato desejado

        $collaborators = $this->_get_collaborators_by_ids($data->user_id);

        
        $row_data = array(
            $collaborators,
            $project_title,
            $client_name,
            $task_title,
            $data->start_time,
            ($data->hours || get_setting("users_can_input_only_total_hours_instead_of_period")) ? format_to_date($data->start_time) : format_to_datetime($data->start_time),
            $data->end_time,
            $data->hours ? format_to_date($data->end_time) : format_to_datetime($data->end_time),
            $duration,
            to_decimal_format(convert_time_string_to_decimal($duration), false), //alwasy return dot for excel.
            to_decimal_format(convert_time_string_to_decimal($duration)), //alwasy return dot to export.
            $data->note
        );

        foreach ($custom_fields as $field) {
            $cf_id = "cfv_" . $field->id;
            $row_data[] = $this->template->view("custom_fields/output_" . $field->field_type, array("value" => $data->$cf_id));
        }

        $options = modal_anchor(get_uri("projectanalizer/timelog_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_timelog'), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_timelog'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("projectanalizer/delete_timelog"), "data-action" => "delete"));

        $timesheet_manage_permission = get_array_value($this->login_user->permissions, "timesheet_manage_permission");
        if ($data->user_id === $this->login_user->id && ($timesheet_manage_permission === "own_project_members_excluding_own" || $timesheet_manage_permission === "specific_excluding_own")) {
            $options = "";
        }

        $row_data[] = $options;

        return $row_data;
    }

    private function _get_collaborators_by_ids($collaborator_list, $clickable = true) {
        $collaborators = "";

       
       

        if ($collaborator_list) {

            $collaborators_array = explode(",", $collaborator_list);
         
            foreach ($collaborators_array as $collaborator) {
                $collaborator = trim((string) $collaborator);
                if ($collaborator === '') {
                    continue;
                }

                $Collabor = $this->Users_model->get_details(array('id' => $collaborator))->getRow();
                if (!$Collabor || empty($Collabor->id)) {
                    continue;
                }

                $collaborator_id = $Collabor->id;
                $collaborator_name = $Collabor->first_name.' '.$Collabor->last_name;
                $image_url = get_avatar($Collabor->image);
                
                $user_type = $Collabor->user_type;

                $collaboratr_image = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt='...'></span>";

                if ($clickable) {
                    if ($user_type == "staff") {
                        $collaborators .= get_team_member_profile_link($collaborator_id, $collaboratr_image, array("title" => $collaborator_name));
                    } else if ($user_type == "client") {
                        $collaborators .= get_client_contact_profile_link($collaborator_id, $collaboratr_image, array("title" => $collaborator_name));
                    }
                } else {
                    $collaborators .= "<span title='$collaborator_name'>$collaboratr_image</span>";
                }
            }
        }

        return $collaborators ?: "-";
    }


    private function _get_project_members_dropdown_list_for_filter($project_id)
    {

        $project_members = $this->Project_members_model->get_project_members_dropdown_list($project_id, array(), false, true)->getResult();
        
        
        foreach ($project_members as $member) {
            $project_members_dropdown[] = array("id" => $member->user_id, "text" => $member->member_name);
        }
        return $project_members_dropdown;
    }

    private function _get_timesheet_tasks_dropdown($project_id, $return_json = false)
    {
        $tasks_dropdown = array("" => "-");
        $tasks_dropdown_json = array(array("id" => "", "text" => "- " . app_lang("task") . " -"));

        $show_assigned_tasks_only_user_id = $this->show_assigned_tasks_only_user_id();
        if (!$show_assigned_tasks_only_user_id) {
            $timesheet_manage_permission = get_array_value($this->login_user->permissions, "timesheet_manage_permission");
            if (!$timesheet_manage_permission || $timesheet_manage_permission === "own") {
                //show only own tasks when the permission is no/own
                $show_assigned_tasks_only_user_id = $this->login_user->id;
            }
        }

        $options = array(
            "project_id" => $project_id,
            "show_assigned_tasks_only_user_id" => $show_assigned_tasks_only_user_id
        );

        $tasks = $this->Tasks_model->get_details($options)->getResult();

        foreach ($tasks as $task) {
            $tasks_dropdown_json[] = array("id" => $task->id, "text" => $task->id . " - " . $task->title);
            $tasks_dropdown[$task->id] = $task->id . " - " . $task->title;
        }

        if ($return_json) {
            return json_encode($tasks_dropdown_json);
        } else {
            return $tasks_dropdown;
        }
    }


    private function _get_project_info_data($project_id) {
        $options = array(
            "id" => $project_id,
            "client_id" => $this->login_user->client_id,
        );

        if (!$this->can_manage_all_projects()) {
            $options["user_id"] = $this->login_user->id;
        }

        $project_info = $this->Projects_model->get_details($options)->getRow();
        $view_data['project_info'] = $project_info;

        if ($project_info) {
            $view_data['project_info'] = $project_info;
            $timer = $this->Timesheets_model->get_timer_info($project_id, $this->login_user->id)->getRow();
            $user_has_any_timer_except_this_project = $this->Timesheets_model->user_has_any_timer_except_this_project($project_id, $this->login_user->id);

            //disable the start timer button if the setting is disabled
            $view_data["disable_timer"] = false;
            if ($user_has_any_timer_except_this_project && !get_setting("users_can_start_multiple_timers_at_a_time")) {
                $view_data["disable_timer"] = true;
            }

            if ($timer) {
                $view_data['timer_status'] = "open";
            } else {
                $view_data['timer_status'] = "";
            }

            $view_data['project_progress'] = $this->_get_project_progress_by_milestones($project_id);

            return $view_data;
        } else {
            show_404();
        }
    }

    private function can_add_remove_project_members() {
        if ($this->login_user->user_type == "staff") {
            if ($this->login_user->is_admin) {
                return true;
            } else {
                if (get_array_value($this->login_user->permissions, "show_assigned_tasks_only") !== "1") {
                    if ($this->can_manage_all_projects()) {
                        return true;
                    } else if (get_array_value($this->login_user->permissions, "can_add_remove_project_members") == "1") {
                        return true;
                    }
                }
            }
        }
    }

    private function can_create_milestones() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_create_milestones") == "1") {
                return $this->is_user_a_project_member;
            }
        }
    }

    private function _can_view_project_tasks($project_id) {
        if ($this->login_user->user_type !== "staff") {
            if (get_setting("client_can_view_tasks") && $this->is_clients_project && $this->can_client_access("project", false)) {
                return true;
            }
            return false;
        }

        if ($this->can_manage_all_projects()) {
            return true;
        }

        return $this->is_user_a_project_member ? true : false;
    }

    private function _can_create_project_tasks($project_id) {
        if ($this->login_user->user_type !== "staff") {
            return get_setting("client_can_create_tasks") && $this->is_clients_project;
        }

        if ($this->can_manage_all_projects()) {
            return true;
        }

        return $this->_user_has_project_task_creation_permission() && $this->is_user_a_project_member;
    }

    private function _can_edit_project_tasks($project_id) {
        if ($this->login_user->user_type !== "staff") {
            return get_setting("client_can_edit_tasks") && $this->is_clients_project;
        }

        if ($this->can_manage_all_projects()) {
            return true;
        }

        return $this->_user_has_project_task_edit_permission() && $this->is_user_a_project_member;
    }

    private function _can_delete_project_tasks($project_id) {
        if ($this->login_user->user_type !== "staff") {
            return get_setting("client_can_delete_tasks") && $this->is_clients_project;
        }

        if ($this->can_manage_all_projects()) {
            return true;
        }

        return $this->_user_has_project_task_delete_permission() && $this->is_user_a_project_member;
    }

    private function _user_has_project_task_creation_permission() {
        return get_array_value($this->login_user->permissions, "can_create_tasks") == "1";
    }

    private function _user_has_project_task_edit_permission() {
        return get_array_value($this->login_user->permissions, "can_edit_tasks") == "1";
    }

    private function _user_has_project_task_delete_permission() {
        return get_array_value($this->login_user->permissions, "can_delete_tasks") == "1";
    }

    private function _get_removed_task_status_ids($project_id = 0) {
        if (!$project_id) {
            return "";
        }

        $this->init_project_settings($project_id);
        return get_setting("remove_task_statuses");
    }

    private function _get_milestones_dropdown_list($project_id = 0) {
        $milestones = $this->Milestones_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $milestone_dropdown = array(array("id" => "", "text" => "- " . app_lang("milestone") . " -"));

        foreach ($milestones as $milestone) {
            $milestone_dropdown[] = array("id" => $milestone->id, "text" => $milestone->title);
        }

        return json_encode($milestone_dropdown);
    }

    private function _get_priorities_dropdown_list($priority_id = 0) {
        $priorities = $this->Task_priority_model->get_details()->getResult();
        $priorities_dropdown = array(array("id" => "", "text" => "- " . app_lang("priority") . " -"));

        $selected_status = false;
        foreach ($priorities as $priority) {
            if (isset($priority_id) && $priority_id) {
                if ($priority->id == $priority_id) {
                    $selected_status = true;
                } else {
                    $selected_status = false;
                }
            }

            $priorities_dropdown[] = array("id" => $priority->id, "text" => $priority->title, "isSelected" => $selected_status);
        }

        return json_encode($priorities_dropdown);
    }

    private function _get_project_members_dropdown_list($project_id = 0) {
        if ($this->login_user->user_type === "staff") {
            $assigned_to_dropdown = array(array("id" => "", "text" => "- " . app_lang("assigned_to") . " -"));
            $user_ids = array();
            if (get_array_value($this->login_user->permissions, "hide_team_members_list_from_dropdowns") == "1") {
                array_push($user_ids, $this->login_user->id);
            }
            $assigned_to_list = $this->Project_members_model->get_project_members_id_and_text_dropdown($project_id, $user_ids, true, true);
            $assigned_to_dropdown = array_merge($assigned_to_dropdown, $assigned_to_list);
        } else {
            $assigned_to_dropdown = array(
                array("id" => "", "text" => app_lang("all_tasks")),
                array("id" => $this->login_user->id, "text" => app_lang("my_tasks"))
            );
        }

        return json_encode($assigned_to_dropdown);
    }

    private function can_edit_milestones() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_edit_milestones") == "1") {
                return $this->is_user_a_project_member;
            }
        }
    }

    private function can_delete_milestones() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_delete_milestones") == "1") {
                return $this->is_user_a_project_member;
            }
        }
    }

    private function _etapa_row_data($id) {
        $Milestones_model = model("App\\Models\\Milestones_model");
        $data = $Milestones_model->get_details(array("id" => $id))->getRow();
        $this->init_project_permission_checker($data->project_id);

        return $this->_make_etapa_row($data);
    }

    private function _get_milestone_task_progress($milestone_id) {
        $db = db_connect('default');
        $tasks_table = $db->prefixTable('tasks');
        $timesheet_table = $db->prefixTable('project_time');
        $activities_table = $db->prefixTable('team_activities');
        $pt_deleted_filter = $db->tableExists($timesheet_table) && $db->fieldExists('deleted', $timesheet_table) ? " AND deleted=0" : "";
        $ta_deleted_filter = $db->tableExists($activities_table) && $db->fieldExists('deleted', $activities_table) ? " AND deleted=0" : "";

        $timesheet_select = $db->tableExists($timesheet_table)
            ? "(SELECT IFNULL(SUM(percentage_executed),0) FROM $timesheet_table WHERE task_id=t.id$pt_deleted_filter)"
            : "0";
        $activities_select = $db->tableExists($activities_table)
            ? "(SELECT IFNULL(SUM(percentage_executed),0) FROM $activities_table WHERE task_id=t.id$ta_deleted_filter)"
            : "0";

        $task_rows = $db->query(
            "SELECT t.id, t.percentage,
                $timesheet_select + $activities_select AS executed_total
             FROM $tasks_table t
             WHERE t.milestone_id=? AND t.deleted=0",
            [$milestone_id]
        )->getResult();

        $weighted_total = 0;
        $weight_sum = 0;
        foreach ($task_rows as $task_row) {
            $task_weight = is_numeric($task_row->percentage) ? (float)$task_row->percentage : 0;
            if ($task_weight <= 0) {
                continue;
            }
            $executed = is_numeric($task_row->executed_total) ? (float)$task_row->executed_total : 0;
            $executed = max(0, min(100, $executed));
            $weighted_total += ($task_weight * $executed) / 100;
            $weight_sum += $task_weight;
        }

        if ($weight_sum <= 0) {
            return 0;
        }

        return round(max(0, min(100, $weighted_total)), 0);
    }

    private function _get_project_progress_by_milestones($project_id) {
        $milestones = $this->Milestones_model->get_details(array("project_id" => $project_id, "deleted" => 0))->getResult();
        $weighted_total = 0;
        $weight_sum = 0;

        foreach ($milestones as $milestone) {
            $milestone_weight = isset($milestone->percentage) && is_numeric($milestone->percentage)
                ? (float)$milestone->percentage
                : 0;
            if ($milestone_weight <= 0) {
                continue;
            }

            $milestone_progress = $this->_get_milestone_task_progress($milestone->id);
            $weighted_total += ($milestone_weight * $milestone_progress) / 100;
            $weight_sum += $milestone_weight;
        }

        if ($weight_sum <= 0) {
            return 0;
        }

        return round(max(0, min(100, $weighted_total)), 0);
    }

    private function _make_etapa_row($data) {
        $progress = $this->_get_milestone_task_progress($data->id);
        $class = "bg-primary";
        if ($progress == 100) {
            $class = "progress-bar-success";
        }

        $total_tasks = $data->total_tasks ? $data->total_tasks : 0;
        $completed_tasks = $data->completed_tasks ? $data->completed_tasks : 0;

        $progress_bar = "<div class='ml10 mr10 clearfix'><span class='float-start'>$completed_tasks/$total_tasks</span><span class='float-end'>$progress%</span></div><div class='progress mt0' title='$progress%'>
            <div  class='progress-bar $class' role='progressbar' aria-valuenow='$progress' aria-valuemin='0' aria-valuemax='100' style='width: $progress%'>
            </div>
        </div>";

        $label_class = "";
        if ($progress == 100) {
            $label_class = "bg-success";
        } else if ($progress !== 100 && get_my_local_time("Y-m-d") > $data->due_date) {
            $label_class = "bg-danger";
        } else if ($progress !== 100 && get_my_local_time("Y-m-d") == $data->due_date) {
            $label_class = "bg-warning";
        } else {
            $label_class = "bg-primary";
        }

        $day_or_year_name = "";
        if (date("Y", strtotime(get_current_utc_time())) === date("Y", strtotime($data->due_date))) {
            $day_or_year_name = app_lang(strtolower(date("l", strtotime($data->due_date))));
        } else {
            $day_or_year_name = date("Y", strtotime($data->due_date));
        }

        $month_name = app_lang(strtolower(date("F", strtotime($data->due_date))));
        $due_date = "<div class='milestone float-start' title='" . format_to_date($data->due_date) . "'>
            <span class='badge $label_class'>" . $month_name . "</span>
            <h1>" . date("d", strtotime($data->due_date)) . "</h1>
            <span>" . $day_or_year_name . "</span>
            </div>
            ";

        $optoins = "";
        if ($this->can_edit_milestones()) {
            $optoins .= modal_anchor(get_uri("projectanalizer/etapa_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_milestone'), "data-post-id" => $data->id, "data-post-project_id" => $data->project_id));
        }

        if ($this->can_delete_milestones()) {
            $optoins .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_milestone'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("projectanalizer/delete_etapa"), "data-action" => "delete"));
        }

        $title = "<div><b>" . $data->title . "</b></div>";
        if ($data->description) {
            $title .= "<div>" . custom_nl2br($data->description) . "<div>";
        }

        $percentage = isset($data->percentage) ? to_decimal_format($data->percentage, false) : "0.00";

        return array(
            $data->due_date,
            $due_date,
            $title,
            $percentage . "%",
            $progress_bar,
            $optoins
        );
    }

    private function _normalize_task_date($value) {
        $value = trim($value);
        if (!$value) {
            return "";
        }

        $date_format = get_setting("date_format");
        if ($date_format && $date_format !== "Y-m-d") {
            $d = \DateTime::createFromFormat($date_format, $value);
            if ($d && $d->format($date_format) === $value) {
                return $d->format("Y-m-d");
            }
        }

        return $this->_check_valid_date($value);
    }

    private function _calculate_business_days($start_date, $end_date) {
        if (!$start_date || !$end_date) {
            return 0;
        }

        $start = new \DateTime(substr($start_date, 0, 10));
        $end = new \DateTime(substr($end_date, 0, 10));

        if ($end <= $start) {
            return 0;
        }

        $count = 0;
        while ($start < $end) {
            $start->modify("+1 day");
            $weekday = (int)$start->format("N");
            if ($weekday < 6) {
                $count++;
            }
        }

        return $count;
    }


}
