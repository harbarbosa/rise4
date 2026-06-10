<?php

namespace RestApi\Controllers;

use App\Models\Timesheets_model;
use App\Models\Tasks_model;
use Config\Database;
use ProjectAnalizer\Models\Execution_schedule_model;
use ProjectAnalizer\Models\Photos_model;
use ProjectAnalizer\Models\Team_activities_model;

class ProjectAnalizerController extends ModuleApiController
{
    protected Team_activities_model $teamActivitiesModel;
    protected Timesheets_model $timesheetsModel;
    protected Tasks_model $tasksModel;
    protected Execution_schedule_model $executionScheduleModel;
    protected Photos_model $photosModel;
    protected $db;
    protected array $userNameCache = [];

    public function __construct()
    {
        parent::__construct();

        helper('date_time');
        $this->db = Database::connect('default');
        $this->teamActivitiesModel = model('ProjectAnalizer\Models\Team_activities_model');
        $this->timesheetsModel = model('App\Models\Timesheets_model');
        $this->tasksModel = model('App\Models\Tasks_model');
        $this->executionScheduleModel = model('ProjectAnalizer\Models\Execution_schedule_model');
        $this->photosModel = model('ProjectAnalizer\Models\Photos_model');
    }

    public function endpoints()
    {
        return $this->respond([
            'status' => true,
            'data' => [
                [
                    'key' => 'team_activities',
                    'method' => 'GET, POST',
                    'route' => get_uri('api/projectanalizer/team-activities'),
                    'description' => 'List or create project activities.',
                ],
                [
                    'key' => 'timelogs',
                    'method' => 'GET, POST',
                    'route' => get_uri('api/projectanalizer/timelogs'),
                    'description' => 'List or create project timelogs, including photos.',
                ],
                [
                    'key' => 'timelog_photos',
                    'method' => 'GET',
                    'route' => get_uri('api/projectanalizer/timelogs/{id}/photos'),
                    'description' => 'List timelog photos.',
                ],
                [
                    'key' => 'tasks',
                    'method' => 'GET',
                    'route' => get_uri('api/projectanalizer/tasks/{project_id}'),
                    'description' => 'List project tasks and execution percentage.',
                ],
				[
					'key' => 'task',
					'method' => 'GET',
					'route' => get_uri('api/projectanalizer/tasks/{project_id}/{task_id}'),
					'description' => 'Get one project task with execution percentage.',
				],
				[
					'key' => 'timesheets',
					'method' => 'GET, POST, PUT, PATCH, DELETE',
					'route' => get_uri('api/projectanalizer/timesheets/{project_id}') . ' / ' . get_uri('api/projectanalizer/timesheets/{project_id}/{id}'),
					'description' => 'List, create, update and delete project timesheets.',
				],
				[
					'key' => 'execution_schedules',
					'method' => 'GET, POST, DELETE',
					'route' => get_uri('api/projectanalizer/execution-schedules') . ' / ' . get_uri('api/projectanalizer/execution-schedules/{id}'),
					'description' => 'List, create, update and delete project execution schedules.',
				],
			],
		]);
	}

	public function executionSchedules($id = 0)
	{
		$id = (int) $id;
		$method = $this->request->getMethod(true);

		if ($method === 'POST') {
			return $this->saveExecutionSchedule($id);
		}

		if ($method === 'DELETE') {
			return $this->deleteExecutionSchedule($id);
		}

		if ($id > 0) {
			return $this->showExecutionSchedule($id);
		}

		return $this->listExecutionSchedules();
	}

    public function tasks($projectId = 0)
    {
        if ($this->request->getMethod(true) === 'POST') {
            return $this->createTask($projectId);
        }

        $projectId = (int) ($projectId ?: ($this->request->getGet('project_id') ?? 0));
        if ($projectId > 0 && $this->request->getGet('id')) {
            $taskId = (int) $this->request->getGet('id');
            return $this->task($projectId, $taskId);
        }

        $filters = $this->taskFilters($projectId);
        $result = $this->tasksModel->get_details($filters);
        $rows = [];
        if (is_array($result)) {
            $rows = $result['data'] ?? [];
        } elseif (is_object($result) && method_exists($result, 'getResult')) {
            $rows = $result->getResult();
        }
        $data = [];
        foreach ($rows as $row) {
            $data[] = $this->formatTaskRow($row);
        }

        return $this->respond([
            'status' => true,
            'resource' => 'projectanalizer_tasks',
            'project_id' => $projectId,
            'count' => count($data),
            'data' => $data,
        ]);
    }

    public function task($projectId = 0, $taskId = 0)
    {
        $projectId = (int) $projectId;
        $taskId = (int) $taskId;
        if ($projectId <= 0 || $taskId <= 0) {
            return $this->failValidationErrors('Invalid project id or task id.');
        }

        $row = $this->tasksModel->get_details([
            'project_id' => $projectId,
            'id' => $taskId,
        ])->getRow();

        if (!$row || empty($row->id)) {
            return $this->failNotFound('Task not found.');
        }

        return $this->respond([
            'status' => true,
            'resource' => 'projectanalizer_task',
            'project_id' => $projectId,
            'task_id' => $taskId,
            'data' => $this->formatTaskRow($row),
        ]);
    }

    public function teamActivities()
    {
        if ($this->request->getMethod(true) === 'GET') {
            return $this->listTeamActivities();
        }

        return $this->createTeamActivity();
    }

    public function timelogs()
    {
        if ($this->request->getMethod(true) === 'GET') {
            return $this->listTimelogs();
        }

        return $this->createTimelog();
    }

    public function timelogPhotos(int $timelogId)
    {
        $timelogId = (int) $timelogId;
        if ($timelogId <= 0) {
            return $this->failValidationErrors('Invalid timelog id.');
        }

        $photos = $this->photosModel->get_by_timelog($timelogId);

        return $this->respond([
            'status' => true,
            'timelog_id' => $timelogId,
            'data' => $photos,
        ]);
    }

	protected function listExecutionSchedules()
	{
		$filters = [];
		$id = (int) ($this->request->getGet('id') ?? 0);
		$projectId = (int) ($this->request->getGet('project_id') ?? 0);
		$userId = (int) ($this->request->getGet('user_id') ?? 0);
		$startDate = clean_data($this->request->getGet('start_date'));
		$endDate = clean_data($this->request->getGet('end_date'));

		if ($id > 0) {
			$filters['id'] = $id;
		}
		if ($projectId > 0) {
			$filters['project_id'] = $projectId;
		}
		if ($userId > 0) {
			$filters['user_id'] = $userId;
		}
		if ($startDate !== '') {
			$filters['start_date'] = $startDate;
		}
		if ($endDate !== '') {
			$filters['end_date'] = $endDate;
		}

		$result = $this->executionScheduleModel->get_details($filters);
		$rows = is_object($result) && method_exists($result, 'getResult') ? $result->getResult() : [];
		$data = [];
		foreach ($rows as $row) {
			$groupRows = [];
			if (!empty($row->group_key)) {
				$groupRows = $this->executionScheduleModel->get_group_rows($row->group_key, $row->id, true);
			}
			if (!$groupRows) {
				$groupRows = [$row];
			}

			$data[] = $this->formatExecutionScheduleRow($row, $groupRows);
		}

		return $this->respond([
			'status' => true,
			'resource' => 'projectanalizer_execution_schedules',
			'count' => count($data),
			'project_id' => $projectId,
			'data' => $data,
		]);
	}

	protected function showExecutionSchedule(int $id)
	{
		if ($id <= 0) {
			return $this->failValidationErrors('Invalid execution schedule id.');
		}

		$row = $this->executionScheduleModel->get_details(['id' => $id])->getRow();
		if (!$row || empty($row->id)) {
			return $this->failNotFound('Execution schedule not found.');
		}

		$groupRows = [];
		if (!empty($row->group_key)) {
			$groupRows = $this->executionScheduleModel->get_group_rows($row->group_key, $row->id, true);
		}
		if (!$groupRows) {
			$groupRows = [$row];
		}

		$data = [];
		foreach ($groupRows as $groupRow) {
			$data[] = $this->formatExecutionScheduleRow($groupRow, $groupRows);
		}

		return $this->respond([
			'status' => true,
			'resource' => 'projectanalizer_execution_schedule',
			'id' => $id,
			'project_id' => (int) ($row->project_id ?? 0),
			'group_key' => $row->group_key ?? null,
			'count' => count($data),
			'data' => $data,
		]);
	}

	protected function saveExecutionSchedule(int $id = 0)
	{
		try {
			$payload = $this->payload();
			$payload = is_array($payload) ? $payload : [];

			if ($id > 0 && !array_key_exists('id', $payload)) {
				$payload['id'] = $id;
			}

			$existingRow = null;
			if ($id > 0) {
				$existingRow = $this->executionScheduleModel->get_one($id);
				if (!$existingRow || empty($existingRow->id)) {
					return $this->failNotFound('Execution schedule not found.');
				}
			}

			$projectId = (int) ($payload['project_id'] ?? ($existingRow->project_id ?? 0));
			if ($projectId <= 0) {
				return $this->failValidationErrors('project_id is required.');
			}

			$startDate = trim((string) ($payload['start_date'] ?? ($existingRow->start_date ?? '')));
			$endDate = trim((string) ($payload['end_date'] ?? ($existingRow->end_date ?? '')));
			if ($startDate === '' || $endDate === '') {
				return $this->failValidationErrors('start_date and end_date are required.');
			}

			if (strtotime($startDate) === false || strtotime($endDate) === false) {
				return $this->failValidationErrors('Invalid date range.');
			}

			if (strtotime($startDate) > strtotime($endDate)) {
				return $this->failValidationErrors('End date cannot be earlier than start date.');
			}

			if ($this->isCompletedProject($projectId)) {
				return $this->failValidationErrors('Completed projects cannot receive new execution schedules.');
			}

			$userIds = $this->extractExecutionScheduleUserIds($payload);
			if (!$userIds) {
				return $this->failValidationErrors('user_ids is required.');
			}

			$status = clean_data($payload['status'] ?? ($existingRow->status ?? 'planned'));
			if ($status === '') {
				$status = 'planned';
			}

			$notes = $payload['notes'] ?? ($existingRow->notes ?? null);
			$notes = $notes === '' ? null : $notes;

			$groupKey = $id > 0 ? ($existingRow->group_key ?? null) : uniqid('es_', true);
			$existingGroupRows = [];
			$existingRowByUser = [];

			if ($id > 0) {
				$existingGroupRows = !empty($existingRow->group_key)
					? $this->executionScheduleModel->get_group_rows($existingRow->group_key, $existingRow->id, true)
					: [$existingRow];

				foreach ($existingGroupRows as $groupRow) {
					$existingRowByUser[(int) $groupRow->user_id] = $groupRow;
				}
			}

			$conflictedMembers = [];
			foreach ($userIds as $userId) {
				if ($this->executionScheduleModel->has_conflict($userId, $startDate, $endDate, $id, $groupKey)) {
					$conflictedMembers[] = (string) $userId;
				}
			}

			if ($conflictedMembers) {
				return $this->failValidationErrors('Conflicting allocations found for users: ' . implode(', ', $conflictedMembers));
			}

			$data = [
				'project_id' => $projectId,
				'group_key' => $groupKey,
				'start_date' => $startDate,
				'end_date' => $endDate,
				'status' => $status,
				'notes' => $notes,
			];

			$lastSaveId = 0;
			if ($id > 0) {
				foreach ($existingGroupRows as $groupRow) {
					if (!in_array((int) $groupRow->user_id, $userIds, true)) {
						$this->executionScheduleModel->delete($groupRow->id);
					}
				}

				foreach ($userIds as $userId) {
					$memberData = $data;
					$memberData['user_id'] = $userId;

					if (isset($existingRowByUser[$userId])) {
						$lastSaveId = $this->executionScheduleModel->ci_save($memberData, $existingRowByUser[$userId]->id);
					} else {
						$memberData['created_by'] = 0;
						$lastSaveId = $this->executionScheduleModel->ci_save($memberData);
					}
				}

				if (!$lastSaveId) {
					return $this->failValidationErrors('Could not update execution schedule.');
				}

				return $this->respond([
					'status' => true,
					'resource' => 'projectanalizer_execution_schedule',
					'message' => 'record_saved',
					'id' => $lastSaveId,
					'count' => count($userIds),
				]);
			}

			foreach ($userIds as $userId) {
				$memberData = $data;
				$memberData['user_id'] = $userId;
				$memberData['created_by'] = 0;
				$lastSaveId = $this->executionScheduleModel->ci_save($memberData);
			}

			if (!$lastSaveId) {
				return $this->failValidationErrors('Could not save execution schedule.');
			}

			return $this->respondCreated([
				'status' => true,
				'resource' => 'projectanalizer_execution_schedule',
				'message' => 'record_saved',
				'id' => $lastSaveId,
				'count' => count($userIds),
			]);
		} catch (\Throwable $e) {
			log_message('error', '[ProjectAnalizerController] saveExecutionSchedule failed: {message}', ['message' => $e->getMessage()]);
			return $this->failServerError('Unable to save execution schedule.');
		}
	}

	public function deleteExecutionSchedule(int $id = 0)
	{
		$id = (int) $id;
		if ($id <= 0) {
			return $this->failValidationErrors('Invalid execution schedule id.');
		}

		$row = $this->executionScheduleModel->get_one($id);
		if (!$row || empty($row->id)) {
			return $this->failNotFound('Execution schedule not found.');
		}

		$groupRows = [];
		if (!empty($row->group_key)) {
			$groupRows = $this->executionScheduleModel->get_group_rows($row->group_key, $row->id, true);
		}
		if (!$groupRows) {
			$groupRows = [$row];
		}

		$deleted = 0;
		foreach ($groupRows as $groupRow) {
			if ($this->executionScheduleModel->delete($groupRow->id)) {
				$deleted++;
			}
		}

		if (!$deleted) {
			return $this->failServerError('Could not delete execution schedule.');
		}

		return $this->respond([
			'status' => true,
			'message' => 'record_deleted',
			'deleted' => $deleted,
		]);
	}

    protected function listTeamActivities()
    {
        $projectId = (int) ($this->request->getGet('project_id') ?? 0);
        $builder = $this->db->table('team_activities');
        if ($projectId > 0) {
            $builder->where('project_id', $projectId);
        }
        if ($this->db->fieldExists('deleted', 'team_activities')) {
            $builder->where('deleted', 0);
        }
        $rows = $builder->orderBy('activity_date', 'DESC')->get()->getResultArray();

        return $this->respond([
            'status' => true,
            'resource' => 'team_activities',
            'project_id' => $projectId,
            'data' => $rows,
        ]);
    }

    protected function createTeamActivity()
    {
        try {
            $projectId = (int) ($this->request->getPost('project_id') ?? 0);
            if ($projectId <= 0) {
                return $this->failValidationErrors('project_id is required.');
            }

            $members = $this->request->getPost('member_id');
            if (is_string($members) && str_contains($members, ',')) {
                $members = array_values(array_filter(array_map('trim', explode(',', $members))));
            }
            if (!is_array($members)) {
                $members = [$members];
            }
            $members = array_values(array_filter($members, static fn ($value) => $value !== '' && $value !== null));
            if (!$members) {
                return $this->failValidationErrors('member_id is required.');
            }

            $taskId = (int) ($this->request->getPost('task_id') ?? 0);
            $timeMode = trim((string) $this->request->getPost('time_mode'));
            if (!in_array($timeMode, ['hours', 'period'], true)) {
                return $this->failValidationErrors('time_mode must be hours or period.');
            }

            $activityDate = trim((string) $this->request->getPost('activity_date'));
            if ($activityDate === '') {
                return $this->failValidationErrors('activity_date is required.');
            }

            $percentageExecuted = $this->request->getPost('percentage_executed');
            $percentageExecuted = is_null($percentageExecuted) || $percentageExecuted === '' ? null : round((float) str_replace(',', '.', (string) $percentageExecuted), 2);

            if ($taskId > 0) {
                if ($percentageExecuted === null) {
                    return $this->failValidationErrors('percentage_executed is required when task_id is provided.');
                }

                $percentageExecuted = max(0, min(100, $percentageExecuted));
                $builder = $this->db->table('team_activities');
                $builder->select('SUM(percentage_executed) AS total_percentage');
                $builder->where('task_id', $taskId);
                if ($this->db->fieldExists('deleted', 'team_activities')) {
                    $builder->where('deleted', 0);
                }

                $row = $builder->get()->getRow();
                $currentTotal = $row && $row->total_percentage ? (float) $row->total_percentage : 0;
                if (($currentTotal + $percentageExecuted) > 100.0001) {
                    return $this->failValidationErrors('The task percentage cannot exceed 100%.');
                }
            } else {
                $percentageExecuted = null;
            }

            $createdBy = 0;

            $data = [
                'project_id' => $projectId,
                'members_ids' => json_encode($members),
                'task_id' => $taskId ?: null,
                'activity_date' => $activityDate,
                'time_mode' => $timeMode,
                'hours' => $timeMode === 'hours' ? $this->request->getPost('hours') : null,
                'start_datetime' => $timeMode === 'period' ? $this->request->getPost('start_datetime') : null,
                'end_datetime' => $timeMode === 'period' ? $this->request->getPost('end_datetime') : null,
                'percentage_executed' => $percentageExecuted,
                'description' => $this->request->getPost('description'),
                'created_by' => $createdBy,
            ];

            $saveId = $this->teamActivitiesModel->ci_save($data);
            if (!$saveId) {
                return $this->fail(['status' => false, 'message' => 'Activity save failed.']);
            }

            $savedRow = $this->db->table('team_activities')->where('id', $saveId)->get()->getRowArray();

            return $this->respondCreated([
                'status' => true,
                'message' => 'record_saved',
                'id' => $saveId,
                'data' => $savedRow,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[ProjectAnalizerController] teamActivities failed: {message}', ['message' => $e->getMessage()]);
            return $this->failServerError('Unable to save team activity.');
        }
    }

    protected function listTimelogs()
    {
        $projectId = (int) ($this->request->getGet('project_id') ?? 0);
        $options = [
            'user_id' => $this->request->getGet('user_id'),
            'task_id' => $this->request->getGet('task_id'),
            'start_date' => $this->request->getGet('start_date'),
            'end_date' => $this->request->getGet('end_date'),
        ];
        if ($projectId > 0) {
            $options['project_id'] = $projectId;
        }

        $result = $this->timesheetsModel->get_details($options);
        $rows = is_object($result) && method_exists($result, 'getResult') ? $result->getResultArray() : [];

        return $this->respond([
            'status' => true,
            'resource' => 'timelogs',
            'project_id' => $projectId,
            'data' => $rows,
        ]);
    }

    protected function createTask(int $projectId = 0)
    {
        try {
            $payload = $this->payload();
            $payload = is_array($payload) ? $payload : [];

            if ($projectId > 0 && !array_key_exists('project_id', $payload)) {
                $payload['project_id'] = $projectId;
            }

            $projectId = (int) ($payload['project_id'] ?? 0);
            if ($projectId <= 0) {
                return $this->failValidationErrors('project_id is required.');
            }

            $title = trim((string) ($payload['title'] ?? ''));
            if ($title === '') {
                return $this->failValidationErrors('title is required.');
            }

            $statusId = (int) ($payload['status_id'] ?? 0);
            if ($statusId <= 0) {
                $statusId = 1;
            }

            $data = $this->filterPayload('tasks', $payload, ['id']);
            $data['title'] = $title;
            $data['description'] = $payload['description'] ?? ($data['description'] ?? '');
            $data['project_id'] = $projectId;
            $data['milestone_id'] = (int) ($payload['milestone_id'] ?? ($data['milestone_id'] ?? 0));
            $data['points'] = $payload['points'] ?? ($data['points'] ?? 0);
            $data['status_id'] = $statusId;
            $data['priority_id'] = (int) ($payload['priority_id'] ?? ($data['priority_id'] ?? 0));
            $data['labels'] = $this->normalizeCommaSeparatedIds($payload['labels'] ?? ($data['labels'] ?? ''));
            $data['start_date'] = $payload['start_date'] ?? ($data['start_date'] ?? null);
            $data['deadline'] = $payload['deadline'] ?? ($data['deadline'] ?? null);
            $data['assigned_to'] = (int) ($payload['assigned_to'] ?? ($data['assigned_to'] ?? 0));
            $data['collaborators'] = $this->normalizeCommaSeparatedIds($payload['collaborators'] ?? ($data['collaborators'] ?? ''));
            $data['context'] = 'project';
            $data['created_by'] = 0;

            if (array_key_exists('percentage', $payload)) {
                $data['percentage'] = max(0, min(100, round((float) str_replace(',', '.', (string) $payload['percentage']), 2)));
            }

            $now = get_current_utc_time();
            $taskColumns = $this->db->getFieldNames($this->db->prefixTable('tasks')) ?: [];
            if (in_array('created_date', $taskColumns, true)) {
                $data['created_date'] = $now;
            }
            if (in_array('created_at', $taskColumns, true)) {
                $data['created_at'] = $now;
            }
            if (in_array('updated_at', $taskColumns, true)) {
                $data['updated_at'] = $now;
            }
            if (in_array('deleted', $taskColumns, true)) {
                $data['deleted'] = 0;
            }
            if (in_array('sort', $taskColumns, true)) {
                $data['sort'] = $this->tasksModel->get_next_sort_value($projectId, $statusId);
            }

            $data = clean_data($data);
            if (empty($data['start_date'])) {
                $data['start_date'] = null;
            }
            if (empty($data['deadline'])) {
                $data['deadline'] = null;
            }

            $saveId = $this->tasksModel->ci_save($data);
            if (!$saveId) {
                return $this->failValidationErrors('Could not save task.');
            }

            $saved = $this->tasksModel->get_details(['id' => $saveId])->getRow();
            return $this->respondCreated([
                'status' => true,
                'resource' => 'projectanalizer_task',
                'message' => 'Task saved successfully.',
                'id' => (int) $saveId,
                'data' => $saved ? $this->formatTaskRow($saved) : null,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[ProjectAnalizerController] createTask failed: {message}', ['message' => $e->getMessage()]);
            return $this->failServerError('Unable to save task.');
        }
    }

    protected function taskFilters(int $projectId = 0): array
    {
        $filters = [
            'limit' => max(1, (int) ($this->request->getGet('limit') ?: 50)),
            'skip' => max(0, ((int) ($this->request->getGet('page') ?: 1) - 1) * max(1, (int) ($this->request->getGet('limit') ?: 50))),
            'search_by' => clean_data($this->request->getGet('q') ?? $this->request->getGet('search')),
            'status_ids' => clean_data($this->request->getGet('status_ids')),
            'assigned_to' => (int) $this->request->getGet('assigned_to'),
            'priority_id' => (int) $this->request->getGet('priority_id'),
            'milestone_id' => (int) $this->request->getGet('milestone_id'),
            'label_id' => (int) $this->request->getGet('label_id'),
            'task_ids' => clean_data($this->request->getGet('task_ids')),
            'exclude_task_ids' => clean_data($this->request->getGet('exclude_task_ids')),
            'quick_filter' => clean_data($this->request->getGet('quick_filter')),
            'context' => 'project',
        ];

        $projectId = (int) ($projectId ?: ($this->request->getGet('project_id') ?? 0));
        if ($projectId > 0) {
            $filters['project_id'] = $projectId;
        }

        $status = clean_data($this->request->getGet('status'));
        if ($status !== '') {
            $filters['status_ids'] = $status;
        }

        return array_filter($filters, static function ($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function normalizeCommaSeparatedIds($value): string
    {
        if (is_array($value)) {
            return implode(',', array_values(array_filter(array_map(static fn ($item) => (int) $item, $value))));
        }

        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (str_contains($value, ',')) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $value))));
            $parts = array_values(array_filter(array_map(static fn ($item) => (int) $item, $parts)));
            return implode(',', $parts);
        }

        return (string) (int) $value;
    }

    protected function createTimelog()
    {
        try {
            $projectId = (int) ($this->request->getPost('project_id') ?? 0);
            if ($projectId <= 0) {
                return $this->failValidationErrors('project_id is required.');
            }

            $userId = $this->request->getPost('user_id');
            if ($userId === null || $userId === '') {
                $apiUser = $this->api_settings_model->get_one_where(['token' => get_token()]);
                $userId = (int) ($apiUser->id ?? 0);
            }

            $taskId = (int) ($this->request->getPost('task_id') ?? 0);
            $note = $this->request->getPost('note');
            $percentageExecuted = $this->request->getPost('percentage_executed');
            $percentageExecuted = is_null($percentageExecuted) || $percentageExecuted === '' ? null : round((float) str_replace(',', '.', (string) $percentageExecuted), 2);

            $startDateTime = '';
            $endDateTime = '';
            $hours = '';

            $startTime = $this->request->getPost('start_time');
            $endTime = $this->request->getPost('end_time');

            if ($startTime) {
                if (get_setting('time_format') != '24_hours') {
                    $startTime = convert_time_to_24hours_format($startTime);
                    $endTime = convert_time_to_24hours_format($endTime);
                }

                $startDate = $this->request->getPost('start_date');
                $endDate = $this->request->getPost('end_date');
                if (!$startDate || !$endDate) {
                    return $this->failValidationErrors('start_date and end_date are required when start_time/end_time are provided.');
                }

                $startDateTime = convert_date_local_to_utc($startDate . ' ' . $startTime);
                $endDateTime = convert_date_local_to_utc($endDate . ' ' . $endTime);
            } else {
                $date = $this->request->getPost('date');
                if (!$date) {
                    return $this->failValidationErrors('date is required when start_time/end_time are not provided.');
                }

                $startDateTime = $date . ' 00:00:00';
                $endDateTime = $date . ' 00:00:00';

                $hoursInput = trim((string) $this->request->getPost('hours'));
                if ($hoursInput === '') {
                    return $this->failValidationErrors(app_lang('hour_log_time_error_message'));
                }

                if (is_numeric(str_replace(',', '.', $hoursInput))) {
                    $hours = round((float) str_replace(',', '.', $hoursInput), 2);
                } else {
                    $hours = convert_humanize_data_to_hours($hoursInput);
                }

                if (!$hours) {
                    return $this->failValidationErrors(app_lang('hour_log_time_error_message'));
                }
            }

            if ($taskId > 0) {
                if ($percentageExecuted === null) {
                    return $this->failValidationErrors('percentage_executed is required when task_id is provided.');
                }

                $percentageExecuted = max(0, min(100, $percentageExecuted));
                $timesheetTable = $this->db->prefixTable('project_time');
                if (!$this->db->fieldExists('percentage_executed', $timesheetTable)) {
                    return $this->failValidationErrors('Campo Percentual Executado nao existe. Atualize o plugin.');
                }

                $builder = $this->db->table($timesheetTable);
                $builder->select('SUM(percentage_executed) AS total_percentage');
                $builder->where('task_id', $taskId);
                if ($this->db->fieldExists('deleted', $timesheetTable)) {
                    $builder->where('deleted', 0);
                }

                $row = $builder->get()->getRow();
                $currentTotal = $row && $row->total_percentage ? (float) $row->total_percentage : 0;
                if (($currentTotal + $percentageExecuted) > 100.0001) {
                    return $this->failValidationErrors('A soma do percentual da tarefa nao pode ultrapassar 100%.');
                }
            } else {
                $percentageExecuted = null;
            }

            $data = [
                'user_id' => $userId,
                'project_id' => $projectId,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'note' => $note ?: '',
                'task_id' => $taskId ?: 0,
                'hours' => $hours,
                'observacoes' => $this->request->getPost('observacoes'),
                'atividade_realizada' => $this->request->getPost('atividade_realizada'),
                'tempo_manha' => $this->request->getPost('tempo_manha'),
                'tempo_tarde' => $this->request->getPost('tempo_tarde'),
                'tempo_noite' => $this->request->getPost('tempo_noite'),
                'percentage_executed' => $percentageExecuted,
            ];

            $saveId = $this->timesheetsModel->ci_save($data);
            if (!$saveId) {
                return $this->fail(['status' => false, 'message' => 'Timelog save failed.']);
            }

            $uploadedPhotos = $this->saveTimelogPhotos($saveId);
            $savedRow = $this->db->table('project_time')->where('id', $saveId)->get()->getRowArray();

            return $this->respondCreated([
                'status' => true,
                'message' => 'record_saved',
                'id' => $saveId,
                'data' => $savedRow,
                'photos' => $uploadedPhotos,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[ProjectAnalizerController] timelogs failed: {message}', ['message' => $e->getMessage()]);
            return $this->failServerError('Unable to save timelog.');
        }
    }

    protected function saveTimelogPhotos(int $timelogId): array
    {
        $files = $this->request->getFiles();
        $photos = $files['photos'] ?? [];

        if ($photos instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            $photos = [$photos];
        }

        if (!is_array($photos) || !$photos) {
            return [];
        }

        $targetDir = FCPATH . 'files/projectanalizer/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $uploadedBy = 0;
        $saved = [];

        foreach ($photos as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $newName = $file->getRandomName();
            $file->move($targetDir, $newName);

            $photoId = $this->photosModel->insert([
                'timelog_id' => $timelogId,
                'file_name' => $newName,
                'file_path' => 'files/projectanalizer/' . $newName,
                'uploaded_by' => $uploadedBy,
                'created_at' => get_current_utc_time(),
            ]);

            $saved[] = [
                'id' => $photoId,
                'file_name' => $newName,
                'file_path' => 'files/projectanalizer/' . $newName,
                'url' => base_url('files/projectanalizer/' . $newName),
            ];
        }

        return $saved;
    }

	protected function formatExecutionScheduleRow(object $row, array $groupRows = []): array
	{
		$scheduleMembers = [];
		$seenUserIds = [];
		foreach ($groupRows as $groupRow) {
			$memberId = (int) ($groupRow->user_id ?? 0);
			if ($memberId <= 0 || isset($seenUserIds[$memberId])) {
				continue;
			}

			$seenUserIds[$memberId] = true;
			$memberName = trim((string) ($groupRow->member_name ?? ''));
			if ($memberName === '') {
				$memberName = $this->getUserNameById($memberId) ?: null;
			}
			$scheduleMembers[] = [
				'user_id' => $memberId,
				'member_name' => $memberName,
			];
		}

		$rowMemberName = trim((string) ($row->member_name ?? ''));
		if ($rowMemberName === '') {
			$rowMemberName = $this->getUserNameById((int) ($row->user_id ?? 0)) ?: null;
		}

		$leaderName = trim((string) ($row->leader_name ?? ''));
		if ($leaderName === '' && !empty($row->leader_id)) {
			$leaderName = $this->getUserNameById((int) $row->leader_id) ?: null;
		}

		return [
			'id' => (int) ($row->id ?? 0),
			'group_key' => $row->group_key ?? null,
			'project_id' => (int) ($row->project_id ?? 0),
			'project_title' => $row->project_title ?? null,
			'user_id' => (int) ($row->user_id ?? 0),
			'member_name' => $rowMemberName,
			'leader_id' => isset($row->leader_id) ? (int) $row->leader_id : null,
			'leader_name' => $leaderName,
			'schedule_members' => $scheduleMembers,
			'start_date' => $row->start_date ?? null,
			'end_date' => $row->end_date ?? null,
			'status' => $row->status ?? null,
			'notes' => $row->notes ?? null,
			'created_by' => isset($row->created_by) ? (int) $row->created_by : null,
			'deleted' => isset($row->deleted) ? (int) $row->deleted : null,
		];
	}

	protected function extractExecutionScheduleUserIds(array $payload): array
	{
		$source = $payload['user_ids'] ?? ($payload['user_id'] ?? ($payload['member_id'] ?? ($payload['members_ids'] ?? [])));

		if (is_string($source) && str_contains($source, ',')) {
			$source = array_values(array_filter(array_map('trim', explode(',', $source))));
		}

		if (!is_array($source)) {
			$source = [$source];
		}

		return array_values(array_filter(array_map(static fn ($value) => (int) $value, $source)));
	}

	protected function isCompletedProject(int $projectId): bool
	{
		if ($projectId <= 0) {
			return false;
		}

		$projectsTable = $this->db->prefixTable('projects');
		$projectStatusTable = $this->db->prefixTable('project_status');
		if (!$this->db->tableExists($projectsTable) || !$this->db->tableExists($projectStatusTable)) {
			return false;
		}

		$row = $this->db->table($projectsTable . ' p')
			->select('ps.key_name')
			->join($projectStatusTable . ' ps', 'ps.id = p.status_id', 'left')
			->where('p.id', $projectId)
			->get()
			->getRow();

		return $row && (($row->key_name ?? '') === 'completed');
	}

    protected function formatTaskRow(object $row): array
    {
        $executionPercentage = $this->getTaskExecutionPercentage((int) ($row->id ?? 0));
        $statusColor = $row->status_color ?? '';
        $barClass = ((float) $executionPercentage >= 100) ? 'progress-bar-success' : 'bg-primary';
        $percentageBar = sprintf(
            "<div class='progress-bar %s' role='progressbar' aria-valuenow='%.2f' aria-valuemin='0' aria-valuemax='100' style='width: %.2f%%'></div>",
            esc($barClass),
            $executionPercentage,
            $executionPercentage
        );

        return [
            'id' => (int) ($row->id ?? 0),
            'project_id' => (int) ($row->project_id ?? 0),
            'project_title' => $row->project_title ?? null,
            'title' => $row->title ?? null,
            'description' => $row->description ?? null,
            'status_id' => $row->status_id ?? null,
            'status_key_name' => $row->status_key_name ?? null,
            'status_title' => $row->status_title ?? null,
            'status_color' => $statusColor,
            'priority_id' => $row->priority_id ?? null,
            'priority_title' => $row->priority_title ?? null,
            'priority_icon' => $row->priority_icon ?? null,
            'priority_color' => $row->priority_color ?? null,
            'assigned_to' => $row->assigned_to ?? null,
            'assigned_to_user' => $row->assigned_to_user ?? null,
            'assigned_to_avatar' => $row->assigned_to_avatar ?? null,
            'milestone_id' => $row->milestone_id ?? null,
            'milestone_title' => $row->milestone_title ?? null,
            'milestone_percentage_label' => isset($row->percentage) ? to_decimal_format($row->percentage, false) . '%' : null,
            'points' => $row->points ?? null,
            'start_date' => $row->start_date ?? null,
            'deadline' => $row->deadline ?? null,
            'deadline_milestone_title' => $row->deadline_milestone_title ?? null,
            'ticket_title' => $row->ticket_title ?? null,
            'collaborator_list' => $row->collaborator_list ?? null,
            'labels' => $row->labels ?? null,
            'labels_list' => $row->labels_list ?? null,
            'unread' => $row->unread ?? null,
            'has_sub_tasks' => $row->has_sub_tasks ?? null,
            'percentage' => isset($row->percentage) ? (float) $row->percentage : null,
            'execution_percentage' => $executionPercentage,
            'percentage_bar' => $percentageBar,
        ];
    }

    protected function getTaskExecutionPercentage(int $taskId): float
    {
        if ($taskId <= 0) {
            return 0.0;
        }

        $percentageTotal = 0.0;

        $timesheetTable = $this->db->prefixTable('project_time');
        if ($this->db->tableExists($timesheetTable) && $this->db->fieldExists('percentage_executed', $timesheetTable)) {
            $builder = $this->db->table($timesheetTable);
            $builder->select('SUM(percentage_executed) AS total_percentage');
            $builder->where('task_id', $taskId);
            if ($this->db->fieldExists('deleted', $timesheetTable)) {
                $builder->where('deleted', 0);
            }
            $row = $builder->get()->getRow();
            if ($row && $row->total_percentage !== null) {
                $percentageTotal += (float) $row->total_percentage;
            }
        }

        $activitiesTable = $this->db->prefixTable('team_activities');
        if ($this->db->tableExists($activitiesTable) && $this->db->fieldExists('percentage_executed', $activitiesTable)) {
            $builder = $this->db->table($activitiesTable);
            $builder->select('SUM(percentage_executed) AS total_percentage');
            $builder->where('task_id', $taskId);
            if ($this->db->fieldExists('deleted', $activitiesTable)) {
                $builder->where('deleted', 0);
            }
            $row = $builder->get()->getRow();
            if ($row && $row->total_percentage !== null) {
                $percentageTotal += (float) $row->total_percentage;
            }
        }

        return max(0, min(100, round($percentageTotal, 2)));
    }

    protected function getUserNameById(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }

        if (array_key_exists($userId, $this->userNameCache)) {
            return $this->userNameCache[$userId];
        }

        $usersTable = $this->db->prefixTable('users');
        if (!$this->db->tableExists($usersTable)) {
            return null;
        }

        $row = $this->db->table($usersTable)
            ->select("CONCAT(TRIM(COALESCE(first_name, '')), ' ', TRIM(COALESCE(last_name, ''))) AS user_name")
            ->where('id', $userId)
            ->get()
            ->getRow();

        $name = $row && isset($row->user_name) ? trim((string) $row->user_name) : '';
        $this->userNameCache[$userId] = $name !== '' ? $name : null;

        return $this->userNameCache[$userId];
    }
}
