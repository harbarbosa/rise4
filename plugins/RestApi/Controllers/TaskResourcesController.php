<?php

namespace RestApi\Controllers;

use ProjectAnalizer\Models\Task_materials_model;
use ProjectAnalizer\Models\Task_tools_model;

class TaskResourcesController extends ModuleApiController
{
    protected Task_materials_model $taskMaterialsModel;
    protected Task_tools_model $taskToolsModel;

    public function __construct()
    {
        parent::__construct();
        $this->taskMaterialsModel = model('ProjectAnalizer\\Models\\Task_materials_model');
        $this->taskToolsModel = model('ProjectAnalizer\\Models\\Task_tools_model');
    }

    public function show($projectId = 0, $taskId = 0)
    {
        $projectId = (int) $projectId;
        $taskId = (int) $taskId;

        if ($projectId <= 0 || $taskId <= 0) {
            return $this->failValidationErrors('Invalid project id or task id.');
        }

        $tasksTable = $this->db->prefixTable('tasks');
        $task = $this->db->table($tasksTable)
            ->select('id, project_id, title')
            ->where('id', $taskId)
            ->where('project_id', $projectId)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        if (!$task) {
            return $this->failNotFound('Task not found.');
        }

        $materials = [];
        $materialRows = $this->taskMaterialsModel->get_details([
            'project_id' => $projectId,
            'task_id' => $taskId,
        ])->getResult();

        foreach ($materialRows as $row) {
            $materials[] = [
                'id' => (int) ($row->id ?? 0),
                'proposal_item_id' => (int) ($row->proposal_item_id ?? 0),
                'item_id' => isset($row->item_id) ? (int) $row->item_id : null,
                'item_type' => $row->item_type ?? null,
                'name' => $row->material_description ?? null,
                'description' => $row->material_description ?? null,
                'quantity' => isset($row->quantity) ? (float) $row->quantity : 0.0,
                'unit' => $row->item_unit ?? null,
                'notes' => $row->notes ?? null,
                'section_id' => isset($row->section_id) ? (int) $row->section_id : null,
            ];
        }

        $tools = [];
        $toolRows = $this->taskToolsModel->get_details([
            'project_id' => $projectId,
            'task_id' => $taskId,
        ])->getResult();

        foreach ($toolRows as $row) {
            $tools[] = [
                'id' => (int) ($row->id ?? 0),
                'tool_id' => (int) ($row->tool_id ?? 0),
                'name' => $row->tool_name ?? null,
                'quantity' => isset($row->quantity) ? (float) $row->quantity : 0.0,
                'requirement' => $row->requirement ?? null,
            ];
        }

        return $this->respond([
            'status' => true,
            'resource' => 'projectanalizer_task_resources',
            'project_id' => $projectId,
            'task_id' => $taskId,
            'data' => [
                'materials' => $materials,
                'tools' => $tools,
            ],
        ]);
    }
}
