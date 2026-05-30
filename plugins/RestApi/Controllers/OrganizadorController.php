<?php

namespace RestApi\Controllers;

use Organizador\Models\My_task_categories_model;
use Organizador\Models\My_task_comments_model;
use Organizador\Models\My_task_phases_model;
use Organizador\Models\My_task_reminders_model;
use Organizador\Models\My_task_settings_model;
use Organizador\Models\My_task_tags_model;
use Organizador\Models\My_tasks_model;

class OrganizadorController extends ModuleApiController
{
    protected My_tasks_model $tasksModel;
    protected My_task_categories_model $categoriesModel;
    protected My_task_tags_model $tagsModel;
    protected My_task_phases_model $phasesModel;
    protected My_task_settings_model $settingsModel;
    protected My_task_comments_model $commentsModel;
    protected My_task_reminders_model $remindersModel;

    public function __construct()
    {
        parent::__construct();
        $this->tasksModel = model(My_tasks_model::class);
        $this->categoriesModel = model(My_task_categories_model::class);
        $this->tagsModel = model(My_task_tags_model::class);
        $this->phasesModel = model(My_task_phases_model::class);
        $this->settingsModel = model(My_task_settings_model::class);
        $this->commentsModel = model(My_task_comments_model::class);
        $this->remindersModel = model(My_task_reminders_model::class);
    }

	public function tasks(int $id = 0)
	{
        if ($this->request->getMethod(true) === 'POST') {
            return $this->saveTask($id);
        }

		if ($id > 0) {
			$row = $this->tasksModel->get_one_with_details($id);
			if (!$row || !$row->id) {
                return $this->failNotFound('Task not found.');
            }

            return $this->respondData((array) $row, ['resource' => 'organizador_task', 'id' => $id]);
        }

        $rows = $this->tasksModel->get_details($this->taskFilters())->getResultArray();
        return $this->respond([
            'status' => true,
            'resource' => 'organizador_tasks',
            'count' => count($rows),
            'data' => $rows,
		]);
	}

	public function dashboard()
	{
		return $this->respond([
			'status' => true,
			'resource' => 'organizador_dashboard',
			'data' => $this->tasksModel->get_dashboard_intelligence(0, true),
		]);
	}

    public function saveTask(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('my_tasks', $payload, ['id']);
        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }

        $taskTitle = trim((string) ($payload['title'] ?? $payload['task_title'] ?? $payload['name'] ?? ($data['title'] ?? '')));
        if ($taskTitle === '') {
            return $this->failValidationErrors('title is required.');
        }
        $data['title'] = clean_data($taskTitle);

        if (array_key_exists('labels', $data)) {
            $data['labels'] = $this->normalizeLabels($data['labels']);
        }

        $this->normalizeIntFields($data, ['category_id', 'assigned_to', 'created_by', 'reminder_before_value', 'position', 'is_favorite', 'notify_assigned_to', 'notify_creator', 'email_notification']);

        if (!array_key_exists('status', $data) && !$id) {
            $data['status'] = 'pending';
        }
        if (!array_key_exists('priority', $data) && !$id) {
            $data['priority'] = 'medium';
        }
        if (!array_key_exists('position', $data)) {
            $data['position'] = $this->nextTaskPosition();
        }

        $saved = $this->tasksModel->ci_save($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save task.');
        }

        $newId = $id ?: (int) db_connect('default')->insertID();
        return $this->respondCreated([
            'status' => true,
            'message' => 'Task saved successfully.',
            'id' => $newId,
            'data' => $this->tasksModel->get_one_with_details($newId),
        ]);
    }

    public function deleteTask(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid task id.');
        }

        if (!$this->tasksModel->delete($id)) {
            return $this->failValidationErrors('Could not delete task.');
        }

        return $this->respondDeleted(['status' => true, 'message' => 'Task deleted successfully.']);
    }

    public function duplicateTask(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        $row = $this->tasksModel->get_one_with_details($id);
        if (!$row || !$row->id) {
            return $this->failNotFound('Task not found.');
        }

        $data = (array) $row;
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['completed_at'], $data['reminder_sent_at']);
        $data['title'] = trim((string) $data['title']) . ' (Copy)';
        $data['status'] = 'pending';
        $data['is_favorite'] = 0;
        $data['created_by'] = 0;
        $data['position'] = $this->nextTaskPosition();

        $saved = $this->tasksModel->ci_save($data, 0);
        if (!$saved) {
            return $this->failValidationErrors('Could not duplicate task.');
        }

        return $this->respondCreated([
            'status' => true,
            'message' => 'Task duplicated successfully.',
            'id' => (int) db_connect('default')->insertID(),
        ]);
    }

    public function complete(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid task id.');
        }

        $data = [
            'status' => 'done',
            'completed_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
        ];

        if (!$this->tasksModel->ci_save($data, $id)) {
            return $this->failValidationErrors('Could not complete task.');
        }

        return $this->respond(['status' => true, 'message' => 'Task completed successfully.', 'id' => $id]);
    }

    public function toggleFavorite(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        $row = $this->tasksModel->get_one_with_details($id);
        if (!$row || !$row->id) {
            return $this->failNotFound('Task not found.');
        }

        $data = [
            'is_favorite' => (int) $row->is_favorite ? 0 : 1,
            'updated_at' => get_current_utc_time(),
        ];
        if (!$this->tasksModel->ci_save($data, $id)) {
            return $this->failValidationErrors('Could not update favorite flag.');
        }

        return $this->respond(['status' => true, 'message' => 'Task updated successfully.', 'id' => $id]);
    }

    public function updateStatus(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        $status = trim((string) $this->request->getPost('status'));
        if ($id <= 0 || $status === '') {
            return $this->failValidationErrors('Invalid request.');
        }

        $data = [
            'status' => $status,
            'updated_at' => get_current_utc_time(),
        ];
        if ($status === 'done') {
            $data['completed_at'] = get_current_utc_time();
        }

        if (!$this->tasksModel->ci_save($data, $id)) {
            return $this->failValidationErrors('Could not update task status.');
        }

        return $this->respond(['status' => true, 'message' => 'Task status updated.', 'id' => $id]);
    }

    public function kanban()
    {
        $filters = $this->taskFilters();
        $rows = $this->tasksModel->get_kanban_data(0, true, $filters);
        return $this->respond([
            'status' => true,
            'resource' => 'organizador_kanban',
            'data' => $rows,
        ]);
    }

    public function calendar()
    {
        $start = clean_data($this->request->getGet('start'));
        $end = clean_data($this->request->getGet('end'));
        return $this->respond([
            'status' => true,
            'resource' => 'organizador_calendar',
            'data' => $this->tasksModel->get_calendar_events(0, true, $start, $end),
        ]);
    }

    public function categories(int $id = 0)
    {
        if ($id > 0) {
            $row = $this->categoriesModel->get_one($id);
            if (!$row || !$row->id) {
                return $this->failNotFound('Category not found.');
            }

            return $this->respondData((array) $row, ['resource' => 'organizador_category', 'id' => $id]);
        }

        $rows = $this->categoriesModel->get_details(['id' => (int) $this->request->getGet('id')])->getResultArray();
        return $this->respond(['status' => true, 'resource' => 'organizador_categories', 'data' => $rows, 'count' => count($rows)]);
    }

    public function saveCategory(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('my_task_categories', $payload, ['id']);
        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }
        $saved = $this->categoriesModel->ci_save($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save category.');
        }
        return $this->respondCreated(['status' => true, 'message' => 'Category saved successfully.', 'id' => $id ?: (int) db_connect('default')->insertID()]);
    }

    public function deleteCategory(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if (!$this->categoriesModel->delete($id)) {
            return $this->failValidationErrors('Could not delete category.');
        }
        return $this->respondDeleted(['status' => true, 'message' => 'Category deleted successfully.']);
    }

    public function tags(int $id = 0)
    {
        if ($id > 0) {
            $row = $this->tagsModel->get_one($id);
            if (!$row || !$row->id) {
                return $this->failNotFound('Tag not found.');
            }

            return $this->respondData((array) $row, ['resource' => 'organizador_tag', 'id' => $id]);
        }

        $rows = $this->tagsModel->get_details(['id' => (int) $this->request->getGet('id')])->getResultArray();
        return $this->respond(['status' => true, 'resource' => 'organizador_tags', 'data' => $rows, 'count' => count($rows)]);
    }

    public function saveTag(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('my_task_tags', $payload, ['id']);
        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }
        $saved = $this->tagsModel->ci_save($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save tag.');
        }
        return $this->respondCreated(['status' => true, 'message' => 'Tag saved successfully.', 'id' => $id ?: (int) db_connect('default')->insertID()]);
    }

    public function deleteTag(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if (!$this->tagsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete tag.');
        }
        return $this->respondDeleted(['status' => true, 'message' => 'Tag deleted successfully.']);
    }

	public function phases(int $id = 0)
	{
		if ($id > 0) {
			$query = $this->phasesModel->get_details(['id' => $id]);
			$row = $query ? $query->getRow() : null;
			if (!$row) {
				return $this->failNotFound('Phase not found.');
			}

            return $this->respondData((array) $row, ['resource' => 'organizador_phase', 'id' => $id]);
        }

        return $this->respond([
            'status' => true,
            'resource' => 'organizador_phases',
            'data' => $this->phasesModel->get_all_phases(),
        ]);
    }

    public function savePhase(int $id = 0)
    {
        $payload = $this->payload();
        $id = $id ?: (int) ($payload['id'] ?? 0);
        $data = $this->filterPayload('my_task_phases', $payload, ['id']);
        if (!$data) {
            return $this->failValidationErrors('No valid fields were provided.');
        }
        $saved = $this->phasesModel->ci_save($data, $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not save phase.');
        }
        return $this->respondCreated(['status' => true, 'message' => 'Phase saved successfully.', 'id' => $id ?: (int) db_connect('default')->insertID()]);
    }

    public function deletePhase(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if (!$this->phasesModel->delete($id)) {
            return $this->failValidationErrors('Could not delete phase.');
        }
        return $this->respondDeleted(['status' => true, 'message' => 'Phase deleted successfully.']);
    }

    public function comments(int $taskId = 0)
    {
        $taskId = $taskId ?: (int) $this->request->getGet('task_id');
        if ($taskId <= 0) {
            return $this->failValidationErrors('Invalid task id.');
        }

        $rows = $this->commentsModel->get_details(['task_id' => $taskId])->getResultArray();
        return $this->respond(['status' => true, 'task_id' => $taskId, 'data' => $rows]);
    }

    public function saveComment(int $taskId = 0)
    {
        $payload = $this->payload();
        $taskId = $taskId ?: (int) ($payload['task_id'] ?? 0);
        if ($taskId <= 0) {
            return $this->failValidationErrors('Invalid task id.');
        }

        $data = $this->filterPayload('my_task_comments', $payload, ['id']);
        $data['task_id'] = $taskId;
        $saved = $this->commentsModel->ci_save($data, (int) ($payload['id'] ?? 0));
        if (!$saved) {
            return $this->failValidationErrors('Could not save comment.');
        }

        return $this->respondCreated(['status' => true, 'message' => 'Comment saved successfully.']);
    }

    public function deleteComment(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid comment id.');
        }

        if (!$this->commentsModel->delete($id)) {
            return $this->failValidationErrors('Could not delete comment.');
        }

        return $this->respondDeleted(['status' => true, 'message' => 'Comment deleted successfully.']);
    }

    public function reminders(int $taskId = 0)
    {
        $taskId = $taskId ?: (int) $this->request->getGet('task_id');
        if ($taskId <= 0) {
            return $this->failValidationErrors('Invalid task id.');
        }

        $rows = $this->remindersModel->get_details(['task_id' => $taskId])->getResultArray();
        return $this->respond(['status' => true, 'task_id' => $taskId, 'data' => $rows]);
    }

    public function saveReminder(int $taskId = 0)
    {
        $payload = $this->payload();
        $taskId = $taskId ?: (int) ($payload['task_id'] ?? 0);
        if ($taskId <= 0) {
            return $this->failValidationErrors('Invalid task id.');
        }

        $data = $this->filterPayload('my_task_reminders', $payload, ['id']);
        $data['task_id'] = $taskId;
        if (!array_key_exists('is_done', $data)) {
            $data['is_done'] = 0;
        }
        $saved = $this->remindersModel->ci_save($data, (int) ($payload['id'] ?? 0));
        if (!$saved) {
            return $this->failValidationErrors('Could not save reminder.');
        }

        return $this->respondCreated(['status' => true, 'message' => 'Reminder saved successfully.']);
    }

    public function deleteReminder(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid reminder id.');
        }

        if (!$this->remindersModel->delete($id)) {
            return $this->failValidationErrors('Could not delete reminder.');
        }

        return $this->respondDeleted(['status' => true, 'message' => 'Reminder deleted successfully.']);
    }

    public function updateReminderStatus(int $id = 0)
    {
        $id = $id ?: (int) $this->request->getPost('id');
        $isDone = $this->request->getPost('is_done') ? 1 : 0;
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid reminder id.');
        }

        $saved = $this->remindersModel->ci_save([
            'is_done' => $isDone,
            'updated_at' => get_current_utc_time(),
        ], $id);
        if (!$saved) {
            return $this->failValidationErrors('Could not update reminder.');
        }

        return $this->respond(['status' => true, 'message' => 'Reminder updated successfully.', 'id' => $id]);
    }

    public function settings()
    {
        return $this->respond([
            'status' => true,
            'resource' => 'organizador_settings',
            'data' => $this->settingsModel->get_all_settings(),
        ]);
    }

    public function saveSettings()
    {
        $payload = $this->payload();
        $settings = [];
        foreach ($payload as $key => $value) {
            if ($key === 'id') {
                continue;
            }
            $settings[$key] = $value;
        }

        if (!$settings) {
            return $this->failValidationErrors('No valid settings were provided.');
        }

        foreach ($settings as $name => $value) {
            $this->settingsModel->save_setting($name, $value);
        }

        return $this->respond(['status' => true, 'message' => 'Settings saved successfully.']);
    }

    public function calendarData()
    {
        return $this->calendar();
    }

    private function taskFilters(): array
    {
        return [
            'include_deleted' => $this->toBool($this->request->getGet('include_deleted')),
            'view_all' => true,
            'limit' => max(1, (int) ($this->request->getGet('limit') ?: 1000)),
            'offset' => max(0, ((int) ($this->request->getGet('page') ?: 1) - 1) * max(1, (int) ($this->request->getGet('limit') ?: 1000))),
            'id' => (int) $this->request->getGet('id'),
            'status' => clean_data($this->request->getGet('status')),
            'priority' => clean_data($this->request->getGet('priority')),
            'category_id' => (int) $this->request->getGet('category_id'),
            'assigned_to' => (int) $this->request->getGet('assigned_to'),
            'created_by' => (int) $this->request->getGet('created_by'),
            'search' => clean_data($this->request->getGet('search')),
            'quick_filter' => clean_data($this->request->getGet('quick_filter')),
        ];
    }

    private function nextTaskPosition(): int
    {
        $db = db_connect('default');
        $table = $db->prefixTable('my_tasks');
        $row = $db->table($table)->select('MAX(position) AS max_position')->get()->getRow();
        return (int) ($row->max_position ?? 0) + 1;
    }

    private function normalizeLabels($labels): string
    {
        if (is_array($labels)) {
            $labels = implode(',', $labels);
        }
        $labels = trim((string) $labels);
        if ($labels === '') {
            return '';
        }
        $ids = [];
        foreach (explode(',', $labels) as $labelId) {
            $labelId = (int) trim($labelId);
            if ($labelId) {
                $ids[] = $labelId;
            }
        }
        return implode(',', array_values(array_unique($ids)));
    }
}
