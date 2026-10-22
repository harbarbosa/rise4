<?php

namespace ProjectAnalizer\Libraries;

class ProjectJourneyService
{
    private $db;
    private $prefix;

    public const PHASES = [
        1 => "Cadastro inicial",
        2 => "Estruturação",
        3 => "Planejamento",
        4 => "Materiais e compras",
        5 => "Programação",
        6 => "Execução",
        7 => "Encerramento",
    ];

    public function __construct()
    {
        $this->db = db_connect("default");
        $this->prefix = $this->db->getPrefix();
    }

    public function isAvailable()
    {
        return $this->db->tableExists("pa_project_journeys");
    }

    public function getJourney($projectId, $userId = 0)
    {
        $projectId = (int) $projectId;
        if (!$this->isAvailable()) {
            return (object) ["project_id" => $projectId, "manager_id" => 0, "current_phase" => 7, "legacy_unlocked" => 1];
        }

        $row = $this->db->table("pa_project_journeys")
            ->where(["project_id" => $projectId, "deleted" => 0])
            ->get()->getRow();

        if ($row) {
            return $row;
        }

        $now = date("Y-m-d H:i:s");
        $this->db->table("pa_project_journeys")->insert([
            "project_id" => $projectId,
            "manager_id" => null,
            "current_phase" => 1,
            "status" => "active",
            "legacy_unlocked" => 0,
            "created_by" => $userId ?: null,
            "created_at" => $now,
            "updated_at" => $now,
            "deleted" => 0,
        ]);

        return $this->db->table("pa_project_journeys")
            ->where(["project_id" => $projectId, "deleted" => 0])
            ->get()->getRow();
    }

    public function getChecks($projectId, $phase)
    {
        $projectId = (int) $projectId;
        $phase = (int) $phase;
        $project = $this->db->table("projects")->where(["id" => $projectId, "deleted" => 0])->get()->getRow();

        if (!$project) {
            return [];
        }

        if ($phase === 1) {
            $journey = $this->getJourney($projectId);
            return [
                $this->check("manager", "Gerente responsável definido", !empty($journey->manager_id), true, get_uri("projectanalizer/journey/".$projectId)),
                $this->check("start_date", "Data de início definida", !empty($project->start_date) && $project->start_date !== "0000-00-00", true, get_uri("projects/view/".$projectId)),
                $this->check("deadline", "Data de término definida", !empty($project->deadline) && $project->deadline !== "0000-00-00", true, get_uri("projects/view/".$projectId)),
                $this->check("members", "Equipe inicial vinculada", $this->count("project_members", ["project_id" => $projectId, "deleted" => 0]) > 0, true, get_uri("projects/view/".$projectId)),
            ];
        }

        if ($phase === 2) {
            $milestones = $this->rows("milestones", ["project_id" => $projectId, "deleted" => 0]);
            $weight = 0;
            foreach ($milestones as $milestone) {
                $weight += (float) ($milestone->percentage ?? 0);
            }
            return [
                $this->check("milestones", "Projeto possui etapas", count($milestones) > 0, true, get_uri("projectanalizer/etapas/".$projectId)),
                $this->check("milestone_weight", "Pesos das etapas totalizam 100%", count($milestones) > 0 && abs($weight - 100) < 0.01, true, get_uri("projectanalizer/etapas/".$projectId), number_format($weight, 2, ",", ".")."%"),
            ];
        }

        if ($phase === 3) {
            $tasks = $this->rows("tasks", ["project_id" => $projectId, "deleted" => 0]);
            $complete = count($tasks) > 0;
            foreach ($tasks as $task) {
                if (empty($task->milestone_id) || empty($task->start_date) || empty($task->deadline) || empty($task->assigned_to)) {
                    $complete = false;
                    break;
                }
            }
            return [
                $this->check("tasks", "Projeto possui tarefas", count($tasks) > 0, true, get_uri("projectanalizer/tasks/".$projectId)),
                $this->check("task_plan", "Todas as tarefas têm etapa, datas e responsável", $complete, true, get_uri("projectanalizer/tasks/".$projectId)),
            ];
        }

        if ($phase === 4) {
            $materials = $this->countIfTable("pa_project_materials", ["project_id" => $projectId, "deleted" => 0]);
            $allocations = $this->countIfTable("pa_project_task_materials", ["project_id" => $projectId, "deleted" => 0]);
            return [
                $this->check("material_review", "Lista de materiais revisada", true, true, get_uri("projectanalizer/project_materials/".$projectId), $materials." materiais"),
                $this->check("material_allocation", "Materiais associados às tarefas", $materials === 0 || $allocations > 0, false, get_uri("projectanalizer/project_materials/".$projectId), $allocations." associações"),
            ];
        }

        if ($phase === 5) {
            $schedules = $this->countIfTable("projectanalizer_execution_schedule", ["project_id" => $projectId, "deleted" => 0]);
            return [
                $this->check("schedule", "Ao menos uma programação de execução cadastrada", $schedules > 0, true, get_uri("projectanalizer/execution_schedule/".$projectId)),
            ];
        }

        if ($phase === 6) {
            $activities = $this->countIfTable("project_time", ["project_id" => $projectId, "deleted" => 0]);
            return [
                $this->check("activity", "Ao menos uma atividade lançada", $activities > 0, true, get_uri("projectanalizer/timesheets/".$projectId)),
                $this->check("monitoring", "Andamento acompanhado por tarefa", true, true, get_uri("projectanalizer/tasks/".$projectId)),
            ];
        }

        $tasks = $this->rows("tasks", ["project_id" => $projectId, "deleted" => 0]);
        $finished = count($tasks) > 0;
        foreach ($tasks as $task) {
            if ((string) ($task->status_id ?? "") !== "3") {
                $finished = false;
                break;
            }
        }
        return [
            $this->check("tasks_finished", "Todas as tarefas concluídas", $finished, true, get_uri("projectanalizer/tasks/".$projectId)),
            $this->check("close_project", "Projeto pronto para encerramento", $finished, true, get_uri("projects/view/".$projectId)),
        ];
    }

    public function canAdvance($projectId, $phase)
    {
        foreach ($this->getChecks($projectId, $phase) as $check) {
            if ($check["required"] && !$check["passed"]) {
                return false;
            }
        }
        return true;
    }

    public function advance($projectId, $userId, $override = false, $reason = "")
    {
        $journey = $this->getJourney($projectId, $userId);
        $from = (int) $journey->current_phase;
        if ($from >= 7) {
            return ["success" => false, "message" => "A jornada já está na fase final."];
        }
        if (!$override && !$this->canAdvance($projectId, $from)) {
            return ["success" => false, "message" => "Conclua os itens obrigatórios antes de avançar."];
        }

        $to = $from + 1;
        $this->db->table("pa_project_journeys")->where("id", $journey->id)->update([
            "current_phase" => $to,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
        if ($this->db->tableExists("pa_project_journey_history")) {
            $this->db->table("pa_project_journey_history")->insert([
                "project_id" => (int) $projectId,
                "journey_id" => (int) $journey->id,
                "action" => $override ? "admin_override" : "phase_advanced",
                "phase_from" => $from,
                "phase_to" => $to,
                "details" => $reason,
                "created_by" => (int) $userId,
                "created_at" => date("Y-m-d H:i:s"),
                "deleted" => 0,
            ]);
        }
        return ["success" => true, "message" => "Fase concluída. Novas áreas do projeto foram liberadas."];
    }

    public function saveManager($projectId, $managerId, $userId)
    {
        $journey = $this->getJourney($projectId, $userId);
        return $this->db->table("pa_project_journeys")->where("id", $journey->id)->update([
            "manager_id" => $managerId ?: null,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
    }

    public function getAllowedTabs($phase)
    {
        $phase = (int) $phase;
        $tabs = ["projectanalizer", "project_journey"];
        if ($phase >= 2) $tabs = array_merge($tabs, ["etapas", "tasks", "tasks_kanban"]);
        if ($phase >= 3) $tabs = array_merge($tabs, ["evolution_project", "evolucao_ff", "revenues_expenses_section"]);
        if ($phase >= 4) $tabs[] = "materials";
        if ($phase >= 5) $tabs[] = "execution_schedule";
        if ($phase >= 6) $tabs[] = "teamactivities";
        if ($phase >= 7) $tabs[] = "project_items";
        return array_unique($tabs);
    }

    public function getTaskReadiness($projectId)
    {
        $tasks = $this->rows("tasks", ["project_id" => (int) $projectId, "deleted" => 0]);
        $rows = [];
        foreach ($tasks as $task) {
            $taskId = (int) $task->id;
            $rows[] = [
                "id" => $taskId,
                "title" => $task->title ?? ("Tarefa #".$taskId),
                "dates" => !empty($task->start_date) && !empty($task->deadline),
                "assigned" => !empty($task->assigned_to),
                "materials" => $this->countIfTable("pa_project_task_materials", ["task_id" => $taskId, "deleted" => 0]),
                "activities" => $this->countIfTable("project_time", ["task_id" => $taskId, "deleted" => 0]),
                "completed" => (string) ($task->status_id ?? "") === "3",
            ];
        }
        return $rows;
    }

    private function check($key, $label, $passed, $required = true, $url = "", $detail = "")
    {
        return compact("key", "label", "passed", "required", "url", "detail");
    }

    private function rows($table, $where)
    {
        if (!$this->db->tableExists($table)) return [];
        $result = $this->db->table($table)->where($where)->get();
        return $result ? $result->getResult() : [];
    }

    private function count($table, $where)
    {
        if (!$this->db->tableExists($table)) return 0;
        return (int) $this->db->table($table)->where($where)->countAllResults();
    }

    private function countIfTable($table, $where)
    {
        return $this->count($table, $where);
    }
}
