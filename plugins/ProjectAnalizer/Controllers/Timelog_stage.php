<?php

namespace ProjectAnalizer\Controllers;

use App\Controllers\Security_Controller;

class Timelog_stage extends Security_Controller
{
    public function data($project_id = 0)
    {
        $this->access_only_team_members();

        $project_id = (int) $project_id;
        if (!$project_id) {
            return $this->response->setJSON([
                'success' => false,
                'stages' => [],
                'tasks' => []
            ]);
        }

        $db = db_connect('default');
        $milestones_table = $db->prefixTable('milestones');
        $tasks_table = $db->prefixTable('tasks');

        $stages = $db->table($milestones_table)
            ->select('id, title')
            ->where('project_id', $project_id)
            ->where('deleted', 0)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $tasks = $db->table($tasks_table)
            ->select('id, title, milestone_id')
            ->where('project_id', $project_id)
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $stage_options = [['id' => '', 'text' => '- Etapa -']];
        foreach ($stages as $stage) {
            $stage_options[] = [
                'id' => (string) $stage['id'],
                'text' => $stage['title']
            ];
        }

        $task_options = [];
        foreach ($tasks as $task) {
            $task_options[] = [
                'id' => (string) $task['id'],
                'text' => $task['id'] . ' - ' . $task['title'],
                'milestone_id' => (string) ($task['milestone_id'] ?: '')
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'stages' => $stage_options,
            'tasks' => $task_options
        ]);
    }
}
