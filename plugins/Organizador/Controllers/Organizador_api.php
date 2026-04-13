<?php

namespace Organizador\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Organizador\Models\My_task_categories_model;
use Organizador\Models\My_task_phases_model;
use Organizador\Models\My_task_tags_model;
use Organizador\Models\My_tasks_model;
use Organizador\Plugin;

class Organizador_api extends ResourceController
{
    use \CodeIgniter\API\ResponseTrait;

    protected $format = 'json';
    protected $Tasks_model;
    protected $Categories_model;
    protected $Phases_model;
    protected $Tags_model;
    private $tagsMap = null;

    public function __construct()
    {
        parent::__construct();
        helper(array('general', 'date_time'));

        $this->Tasks_model = model(My_tasks_model::class);
        $this->Categories_model = model(My_task_categories_model::class);
        $this->Phases_model = model(My_task_phases_model::class);
        $this->Tags_model = model(My_task_tags_model::class);
    }

    public function index()
    {
        return $this->respond(array(
            'status' => true,
            'message' => 'Organizador public API',
            'enabled' => Plugin::publicApiEnabled(),
            'version' => 'v1',
            'auth' => array(
                'headers' => array('Authorization: Bearer <token>', 'X-Organizador-Token: <token>', 'authtoken: <token>'),
                'query' => '?token=<token>',
            ),
            'endpoints' => array(
                'GET /organizador-api/health',
                'GET /organizador-api/v1/dashboard',
                'GET /organizador-api/v1/tasks',
                'GET /organizador-api/v1/tasks/{id}',
                'GET /organizador-api/v1/calendar',
                'GET /organizador-api/v1/categories',
                'GET /organizador-api/v1/tags',
                'GET /organizador-api/v1/phases',
            ),
        ));
    }

    public function health()
    {
        return $this->respond(array(
            'status' => true,
            'enabled' => Plugin::publicApiEnabled(),
            'message' => 'Organizador public API healthy.',
            'version' => 'v1',
        ));
    }

    public function dashboard()
    {
        if (!$this->_authorize()) {
            return $this->respondUnauthorized();
        }

        return $this->respond(array(
            'status' => true,
            'data' => $this->Tasks_model->get_dashboard_intelligence(0, true),
        ));
    }

    public function tasks()
    {
        if (!$this->_authorize()) {
            return $this->respondUnauthorized();
        }

        $page = max(1, (int) $this->request->getGet('page'));
        $limit = (int) $this->request->getGet('limit');
        if ($limit <= 0) {
            $limit = 20;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $options = array(
            'include_deleted' => $this->toBool($this->request->getGet('include_deleted')),
            'view_all' => true,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'id' => (int) $this->request->getGet('id'),
            'status' => clean_data($this->request->getGet('status')),
            'priority' => clean_data($this->request->getGet('priority')),
            'category_id' => (int) $this->request->getGet('category_id'),
            'assigned_to' => (int) $this->request->getGet('assigned_to'),
            'created_by' => (int) $this->request->getGet('created_by'),
            'search' => clean_data($this->request->getGet('search')),
            'quick_filter' => clean_data($this->request->getGet('quick_filter')),
        );

        $query = $this->Tasks_model->get_details($options);
        $rows = $query ? $query->getResult() : array();
        $data = array();
        foreach ($rows as $row) {
            $data[] = $this->_format_task($row);
        }

        return $this->respond(array(
            'status' => true,
            'page' => $page,
            'limit' => $limit,
            'count' => count($data),
            'data' => $data,
        ));
    }

    public function task($id = 0)
    {
        if (!$this->_authorize()) {
            return $this->respondUnauthorized();
        }

        $id = (int) $id;
        if (!$id) {
            return $this->failValidationErrors('Invalid task id.');
        }

        $task = $this->Tasks_model->get_one_with_details($id);
        if (!$task || !$task->id) {
            return $this->failNotFound('Task not found.');
        }

        return $this->respond(array(
            'status' => true,
            'data' => $this->_format_task($task),
        ));
    }

    public function calendar()
    {
        if (!$this->_authorize()) {
            return $this->respondUnauthorized();
        }

        $start = clean_data($this->request->getGet('start'));
        $end = clean_data($this->request->getGet('end'));

        return $this->respond(array(
            'status' => true,
            'data' => $this->Tasks_model->get_calendar_events(0, true, $start, $end),
        ));
    }

    public function categories()
    {
        if (!$this->_authorize()) {
            return $this->respondUnauthorized();
        }

        $data = array();
        foreach ($this->Categories_model->get_details()->getResult() as $row) {
            $data[] = array(
                'id' => (int) $row->id,
                'title' => $row->title,
                'color' => $row->color ?: '#6c757d',
                'sort' => (int) $row->sort,
            );
        }

        return $this->respond(array('status' => true, 'data' => $data));
    }

    public function tags()
    {
        if (!$this->_authorize()) {
            return $this->respondUnauthorized();
        }

        $data = array();
        foreach ($this->Tags_model->get_details()->getResult() as $row) {
            $data[] = array(
                'id' => (int) $row->id,
                'title' => $row->title,
                'color' => $row->color ?: '#6c757d',
                'sort' => (int) $row->sort,
            );
        }

        return $this->respond(array('status' => true, 'data' => $data));
    }

    public function phases()
    {
        if (!$this->_authorize()) {
            return $this->respondUnauthorized();
        }

        $data = array();
        foreach ($this->Phases_model->get_all_phases() as $row) {
            $data[] = array(
                'id' => $row->key_name,
                'key_name' => $row->key_name,
                'title' => $row->title,
                'color' => $row->color ?: '#6c757d',
                'sort' => (int) $row->sort,
            );
        }

        return $this->respond(array('status' => true, 'data' => $data));
    }

    public function options()
    {
        return $this->respond(array('status' => true));
    }

    private function _authorize()
    {
        if (!Plugin::publicApiEnabled()) {
            return false;
        }

        $expected = Plugin::publicApiToken();
        if (!$expected) {
            return false;
        }

        $provided = $this->_get_token_from_request();
        if (!$provided) {
            return false;
        }

        if (strlen($provided) !== strlen($expected)) {
            return false;
        }

        return hash_equals($expected, $provided);
    }

    private function _get_token_from_request()
    {
        $token = trim((string) $this->request->getGet('token'));
        if ($token !== '') {
            return $token;
        }

        $token = trim((string) $this->request->getHeaderLine('X-Organizador-Token'));
        if ($token !== '') {
            return $token;
        }

        $token = trim((string) $this->request->getHeaderLine('authtoken'));
        if ($token !== '') {
            return $token;
        }

        $authorization = trim((string) $this->request->getHeaderLine('Authorization'));
        if ($authorization !== '' && stripos($authorization, 'Bearer ') === 0) {
            return trim(substr($authorization, 7));
        }

        return '';
    }

    private function _format_task($task)
    {
        $label_ids = array_filter(array_map('intval', explode(',', (string) ($task->labels ?? ''))));
        $labels = array();

        if ($label_ids) {
            $tags_by_id = $this->_get_tags_map();

            foreach ($label_ids as $id) {
                if (!isset($tags_by_id[$id])) {
                    continue;
                }

                $tag = $tags_by_id[$id];
                $labels[] = array(
                    'id' => (int) $tag->id,
                    'title' => $tag->title,
                    'color' => $tag->color ?: '#6c757d',
                );
            }
        }

        return array(
            'id' => (int) $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'status_title' => $task->status_title ?? '',
            'status_color' => $task->status_color ?? '',
            'priority' => $task->priority,
            'category' => array(
                'id' => $task->category_id ? (int) $task->category_id : null,
                'title' => $task->category_title ?? '',
                'color' => $task->category_color ?? '',
            ),
            'assigned_to' => array(
                'id' => $task->assigned_to ? (int) $task->assigned_to : null,
                'name' => $task->assigned_to_name ?? '',
            ),
            'created_by' => $task->created_by ? (int) $task->created_by : null,
            'start_date' => $task->start_date,
            'due_date' => $task->due_date,
            'reminder_at' => $task->reminder_at,
            'position' => (int) ($task->position ?? 0),
            'is_favorite' => (int) ($task->is_favorite ?? 0),
            'labels' => $labels,
            'notify_assigned_to' => (int) ($task->notify_assigned_to ?? 0),
            'notify_creator' => (int) ($task->notify_creator ?? 0),
            'email_notification' => (int) ($task->email_notification ?? 0),
            'reminder_sent_at' => $task->reminder_sent_at ?? null,
            'completed_at' => $task->completed_at ?? null,
            'created_at' => $task->created_at ?? null,
            'updated_at' => $task->updated_at ?? null,
        );
    }

    private function _get_tags_map()
    {
        if ($this->tagsMap !== null) {
            return $this->tagsMap;
        }

        $this->tagsMap = array();
        foreach ($this->Tags_model->get_details()->getResult() as $tag) {
            $this->tagsMap[(int) $tag->id] = $tag;
        }

        return $this->tagsMap;
    }

    private function respondUnauthorized()
    {
        return $this->failUnauthorized('Invalid or missing Organizador API token.');
    }
}
