<?php

namespace RestApi\Controllers;

class ProjectAnalizerTimesheetsController extends Rest_api_Controller
{
    protected $timesheetsModel;
    protected $projectsModel;
    protected $usersModel;

    public function __construct()
    {
        parent::__construct();
        $this->timesheetsModel = model('App\Models\Timesheets_model');
        $this->projectsModel = model('App\Models\Projects_model');
        $this->usersModel = model('App\Models\Users_model');
    }

    public function listByProject(int $projectId)
    {
        if (!$this->projectExists($projectId)) {
            return $this->failNotFound('Project not found.');
        }

        $filters = $this->request->getGet();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = (int) ($filters['limit'] ?? 50);
        if ($limit < 1) {
            $limit = 50;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        $options = [
            'project_id' => $projectId,
            'status' => $filters['status'] ?? 'none_open',
            'user_id' => $filters['user_id'] ?? 0,
            'task_id' => $filters['task_id'] ?? 0,
            'client_id' => $filters['client_id'] ?? 0,
            'start_date' => $filters['start_date'] ?? '',
            'end_date' => $filters['end_date'] ?? '',
            'search_by' => $filters['q'] ?? '',
            'limit' => $limit,
            'skip' => ($page - 1) * $limit,
            'order_by' => $this->mapOrderBy((string) ($filters['sort'] ?? 'start_time')),
            'order_dir' => strtolower((string) ($filters['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
        ];

        $result = $this->timesheetsModel->get_details($options);
        $data = $this->decorateTimesheetRows($result['data'] ?? []);

        return $this->respond([
            'status' => true,
            'project_id' => $projectId,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) ($result['recordsTotal'] ?? 0),
                'filtered' => (int) ($result['recordsFiltered'] ?? 0),
            ],
            'summation' => $result['summation'] ?? new \stdClass(),
            'data' => $data,
        ]);
    }

    public function fetchOne(int $projectId, int $id)
    {
        if (!$this->projectExists($projectId)) {
            return $this->failNotFound('Project not found.');
        }

        $row = $this->timesheetsModel->get_details([
            'project_id' => $projectId,
            'id' => $id,
        ])->getRow();

        if (!$row) {
            return $this->failNotFound('Timesheet not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $this->decorateTimesheetRow($row),
        ]);
    }

    public function store(int $projectId)
    {
        if (!$this->projectExists($projectId)) {
            return $this->failNotFound('Project not found.');
        }

        $payload = $this->getPayload();
        $payload = $this->normalizeTimesheetCollaborators($payload);
        $data = $this->mapPayload($payload);
        $data['project_id'] = $projectId;

        if (!array_key_exists('user_id', $data) || !$data['user_id']) {
            return $this->failValidationErrors('user_id is required.');
        }

        if (array_key_exists('task_id', $data) && $data['task_id']) {
            if (!array_key_exists('percentage_executed', $data)) {
                return $this->failValidationErrors('percentage_executed is required when task_id is provided.');
            }

            $data['percentage_executed'] = max(0, min(100, round((float) $data['percentage_executed'], 2)));
            $percentageError = $this->validateTaskPercentage($data['task_id'], $data['percentage_executed']);
            if ($percentageError !== true) {
                return $this->failValidationErrors($percentageError);
            }
        } else {
            unset($data['percentage_executed']);
        }

        if (!array_key_exists('start_time', $data) && !array_key_exists('hours', $data)) {
            return $this->failValidationErrors('Either start_time/end_time or hours must be provided.');
        }

        $id = $this->timesheetsModel->ci_save($data);
        if (!$id) {
            return $this->failValidationErrors('Could not create timesheet.');
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Timesheet created successfully.',
            'id' => $id,
        ]);
    }

    public function modify(int $projectId, int $id)
    {
        if (!$this->projectExists($projectId)) {
            return $this->failNotFound('Project not found.');
        }

        $existing = $this->timesheetsModel->get_one($id);
        if (!$existing || !$existing->id || (int) $existing->project_id !== $projectId) {
            return $this->failNotFound('Timesheet not found.');
        }

        $payload = $this->getPayload();
        $payload = $this->normalizeTimesheetCollaborators($payload);
        $data = $this->mapPayload($payload);
        unset($data['project_id']);

        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided for update.');
        }

        if (array_key_exists('task_id', $data)) {
            if (!$data['task_id']) {
                unset($data['percentage_executed']);
            } else {
                if (!array_key_exists('percentage_executed', $data)) {
                    return $this->failValidationErrors('percentage_executed is required when task_id is provided.');
                }

                $taskId = $data['task_id'];
                $data['percentage_executed'] = max(0, min(100, round((float) $data['percentage_executed'], 2)));
                $currentPercentage = $data['percentage_executed'];
                $percentageError = $this->validateTaskPercentage($taskId, $currentPercentage, $id);
                if ($percentageError !== true) {
                    return $this->failValidationErrors($percentageError);
                }
            }
        }

        $savedId = $this->timesheetsModel->ci_save($data, $id);
        if (!$savedId) {
            return $this->failValidationErrors('Could not update timesheet.');
        }

        return $this->respond([
            'status' => true,
            'message' => 'Timesheet updated successfully.',
            'id' => $savedId,
        ]);
    }

    public function remove(int $projectId, int $id)
    {
        if (!$this->projectExists($projectId)) {
            return $this->failNotFound('Project not found.');
        }

        $existing = $this->timesheetsModel->get_one($id);
        if (!$existing || !$existing->id || (int) $existing->project_id !== $projectId) {
            return $this->failNotFound('Timesheet not found.');
        }

        if (!$this->timesheetsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete timesheet.');
        }

        return $this->respondDeleted([
            'status' => true,
            'message' => 'Timesheet deleted successfully.',
        ]);
    }

    protected function projectExists(int $projectId): bool
    {
        $project = $this->projectsModel->get_one($projectId);
        return (bool) ($project && $project->id && !(int) $project->deleted);
    }

    protected function mapOrderBy(string $sort): string
    {
        $map = [
            'member_name' => 'member_name',
            'task_title' => 'task_title',
            'start_time' => 'start_time',
            'end_time' => 'end_time',
            'project' => 'project',
            'client' => 'client',
        ];

        return $map[$sort] ?? 'start_time';
    }

    protected function mapPayload(array $payload): array
    {
        $allowed = [
            'user_id',
            'project_id',
            'start_time',
            'end_time',
            'note',
            'task_id',
            'hours',
            'observacoes',
            'atividade_realizada',
            'tempo_manha',
            'tempo_tarde',
            'tempo_noite',
            'percentage_executed',
            'date',
            'status',
        ];

        $data = [];
        foreach ($allowed as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $value = $payload[$field];
            if ($value === null || $value === '') {
                continue;
            }

            if ($field === 'user_id') {
                $data[$field] = is_array($value) ? implode(',', array_values(array_filter(array_map('get_only_numeric_value', $value)))) : trim((string) $value);
                continue;
            }

            if (in_array($field, ['project_id', 'task_id'], true)) {
                $data[$field] = (int) $value;
                continue;
            }

            if ($field === 'percentage_executed') {
                $data[$field] = (float) $value;
                continue;
            }

            $data[$field] = is_string($value) ? trim($value) : $value;
        }

        return $data;
    }

    protected function normalizeTimesheetCollaborators(array $payload): array
    {
        $userIds = $payload['user_ids'] ?? ($payload['collaborators'] ?? ($payload['user_id'] ?? []));
        $normalized = $this->normalizeUserIdList($userIds);
        if (!$normalized) {
            return $payload;
        }

        $payload['user_id'] = implode(',', $normalized);
        unset($payload['user_ids'], $payload['collaborators']);

        return $payload;
    }

    protected function normalizeUserIdList($value): array
    {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        } elseif (!is_array($value)) {
            $value = [$value];
        }

        $ids = [];
        foreach ($value as $item) {
            $id = get_only_numeric_value($item);
            if ($id) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function decorateTimesheetRows(array $rows): array
    {
        if (!$rows) {
            return $rows;
        }

        foreach ($rows as $index => $row) {
            $rows[$index] = $this->decorateTimesheetRow($row);
        }

        return $rows;
    }

    protected function decorateTimesheetRow($row)
    {
        if (!$row) {
            return $row;
        }

        $isArray = is_array($row);
        if ($isArray) {
            $row = (object) $row;
        }

        $userIds = $this->normalizeUserIdList($row->user_id ?? '');
        $row->collaborator_ids = $userIds;
        $row->collaborators = [];

        if (!$userIds) {
            return $row;
        }

        $db = db_connect('default');
        $usersTable = $db->prefixTable('users');
        $idsList = implode(',', $userIds);
        $users = $db->query("SELECT id, first_name, last_name, image FROM $usersTable WHERE deleted=0 AND id IN ($idsList) ORDER BY first_name ASC")->getResult();

        $names = [];
        foreach ($users as $user) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $names[] = $fullName;
            $row->collaborators[] = [
                'id' => (int) ($user->id ?? 0),
                'name' => $fullName,
                'image' => $user->image ?? null,
            ];
        }

        if (!empty($names)) {
            $row->logged_by_user = implode(', ', $names);
            if (!empty($row->collaborators[0]['image']) && empty($row->logged_by_avatar)) {
                $row->logged_by_avatar = $row->collaborators[0]['image'];
            }
        }

        return $isArray ? (array) $row : $row;
    }

    protected function validateTaskPercentage(int $taskId, float $percentageExecuted, int $excludeId = 0)
    {
        $db = db_connect('default');
        $timesheetTable = $db->prefixTable('project_time');

        if (!$db->fieldExists('percentage_executed', $timesheetTable)) {
            return 'Campo Percentual Executado nao existe. Atualize o plugin.';
        }

        $builder = $db->table($timesheetTable);
        $builder->select('SUM(percentage_executed) AS total_percentage');
        $builder->where('task_id', $taskId);
        if ($db->fieldExists('deleted', $timesheetTable)) {
            $builder->where('deleted', 0);
        }
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        $row = $builder->get()->getRow();
        $totalPercentage = $row && $row->total_percentage ? (float) $row->total_percentage : 0;
        if (($totalPercentage + $percentageExecuted) > 100.0001) {
            return 'A soma do percentual da tarefa nao pode ultrapassar 100%.';
        }

        return true;
    }

    protected function getPayload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && $json) {
            return $json;
        }

        $raw = $this->request->getRawInput();
        if (is_array($raw) && $raw) {
            return $raw;
        }

        return $this->request->getPost();
    }
}
