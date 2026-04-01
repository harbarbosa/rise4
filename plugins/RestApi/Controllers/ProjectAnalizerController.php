<?php

namespace RestApi\Controllers;

use App\Models\Timesheets_model;
use Config\Database;
use ProjectAnalizer\Models\Photos_model;
use ProjectAnalizer\Models\Team_activities_model;

class ProjectAnalizerController extends Rest_api_Controller
{
    protected Team_activities_model $teamActivitiesModel;
    protected Timesheets_model $timesheetsModel;
    protected Photos_model $photosModel;
    protected $db;

    public function __construct()
    {
        parent::__construct();

        $this->db = Database::connect('default');
        $this->teamActivitiesModel = model('ProjectAnalizer\Models\Team_activities_model');
        $this->timesheetsModel = model('App\Models\Timesheets_model');
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
            ],
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

    protected function listTeamActivities()
    {
        $projectId = (int) ($this->request->getGet('project_id') ?? 0);
        if ($projectId <= 0) {
            return $this->failValidationErrors('project_id is required.');
        }

        $rows = $this->db->table('team_activities')
            ->where('project_id', $projectId)
            ->orderBy('activity_date', 'DESC')
            ->get()
            ->getResultArray();

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
        if ($projectId <= 0) {
            return $this->failValidationErrors('project_id is required.');
        }

        $options = [
            'project_id' => $projectId,
            'user_id' => $this->request->getGet('user_id'),
            'task_id' => $this->request->getGet('task_id'),
            'start_date' => $this->request->getGet('start_date'),
            'end_date' => $this->request->getGet('end_date'),
        ];

        $result = $this->timesheetsModel->get_details($options);
        $rows = is_object($result) && method_exists($result, 'getResult') ? $result->getResultArray() : [];

        return $this->respond([
            'status' => true,
            'resource' => 'timelogs',
            'project_id' => $projectId,
            'data' => $rows,
        ]);
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
}
