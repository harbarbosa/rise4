<?php

namespace ProjectAnalizer\Controllers;

use App\Controllers\Security_Controller;
use ProjectAnalizer\Libraries\ProjectJourneyService;

class Project_journey extends Security_Controller
{
    private $journeyService;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        $this->journeyService = new ProjectJourneyService();
    }

    public function index($projectId = 0)
    {
        $projectId = (int) $projectId;
        $project = $this->Projects_model->get_one($projectId);
        if (!$projectId || !$project || empty($project->id)) {
            show_404();
        }

        $this->init_project_permission_checker($projectId);
        if (!$this->can_manage_all_projects() && !$this->is_user_a_project_member) {
            app_redirect("forbidden");
        }

        $journey = $this->journeyService->getJourney($projectId, $this->login_user->id);
        $checksByPhase = [];
        foreach (ProjectJourneyService::PHASES as $phase => $label) {
            $checksByPhase[$phase] = $this->journeyService->getChecks($projectId, $phase);
        }

        $staff = $this->Users_model->get_details(["user_type" => "staff", "status" => "active"])->getResult();
        $managers = ["" => "- Selecione -"];
        foreach ($staff as $user) {
            $managers[$user->id] = trim($user->first_name." ".$user->last_name);
        }

        return $this->template->view("ProjectAnalizer\\Views\\project_journey\\index", [
            "project_id" => $projectId,
            "project_info" => $project,
            "journey" => $journey,
            "phases" => ProjectJourneyService::PHASES,
            "checks_by_phase" => $checksByPhase,
            "current_checks" => $checksByPhase[(int) $journey->current_phase] ?? [],
            "can_advance" => $this->journeyService->canAdvance($projectId, (int) $journey->current_phase),
            "task_readiness" => $this->journeyService->getTaskReadiness($projectId),
            "managers" => $managers,
            "is_admin" => (bool) $this->login_user->is_admin,
        ]);
    }

    public function save_manager()
    {
        $projectId = (int) $this->request->getPost("project_id");
        $managerId = (int) $this->request->getPost("manager_id");
        $this->init_project_permission_checker($projectId);

        if (!$this->can_edit_projects($projectId)) {
            return $this->response->setJSON(["success" => false, "message" => app_lang("error_occurred")]);
        }

        if ($managerId) {
            $user = $this->Users_model->get_one($managerId);
            if (!$user || empty($user->id) || $user->user_type !== "staff" || $user->status !== "active") {
                return $this->response->setJSON(["success" => false, "message" => "Selecione um usuário ativo da equipe."]);
            }
        }

        $saved = $this->journeyService->saveManager($projectId, $managerId, $this->login_user->id);
        return $this->response->setJSON([
            "success" => (bool) $saved,
            "message" => $saved ? app_lang("record_saved") : app_lang("error_occurred"),
        ]);
    }

    public function advance()
    {
        $projectId = (int) $this->request->getPost("project_id");
        $override = (bool) $this->request->getPost("override");
        $reason = trim((string) $this->request->getPost("reason"));
        $this->init_project_permission_checker($projectId);

        if (!$this->can_edit_projects($projectId) || ($override && !$this->login_user->is_admin)) {
            return $this->response->setJSON(["success" => false, "message" => app_lang("error_occurred")]);
        }

        $result = $this->journeyService->advance($projectId, $this->login_user->id, $override, $reason);
        return $this->response->setJSON($result);
    }

    public function task_block()
    {
        $projectId = (int) $this->request->getPost("project_id");
        $taskId = (int) $this->request->getPost("task_id");
        $blocked = (bool) $this->request->getPost("blocked");
        $reason = trim((string) $this->request->getPost("reason"));
        $this->init_project_permission_checker($projectId);

        if (!$this->can_edit_projects($projectId)) {
            return $this->response->setJSON(["success" => false, "message" => app_lang("error_occurred")]);
        }

        return $this->response->setJSON(
            $this->journeyService->setTaskBlock($projectId, $taskId, $blocked, $reason, $this->login_user->id)
        );
    }

    public function task_release()
    {
        $projectId = (int) $this->request->getPost("project_id");
        $taskId = (int) $this->request->getPost("task_id");
        $override = (bool) $this->request->getPost("override");
        $notes = trim((string) $this->request->getPost("notes"));
        $this->init_project_permission_checker($projectId);

        if (!$this->can_edit_projects($projectId) || ($override && !$this->login_user->is_admin)) {
            return $this->response->setJSON(["success" => false, "message" => app_lang("error_occurred")]);
        }

        return $this->response->setJSON(
            $this->journeyService->releaseTask($projectId, $taskId, $this->login_user->id, $override, $notes)
        );
    }

    public function task_revoke()
    {
        $projectId = (int) $this->request->getPost("project_id");
        $taskId = (int) $this->request->getPost("task_id");
        $this->init_project_permission_checker($projectId);

        if (!$this->can_edit_projects($projectId)) {
            return $this->response->setJSON(["success" => false, "message" => app_lang("error_occurred")]);
        }

        return $this->response->setJSON(
            $this->journeyService->revokeTaskRelease($projectId, $taskId, $this->login_user->id)
        );
    }
}
