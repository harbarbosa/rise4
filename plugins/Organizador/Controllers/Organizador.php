<?php

namespace Organizador\Controllers;

use App\Controllers\Security_Controller;
use Organizador\Models\My_task_categories_model;
use Organizador\Models\My_task_comments_model;
use Organizador\Models\My_task_notifications_model;
use Organizador\Models\My_task_phases_model;
use Organizador\Models\My_task_reminders_model;
use Organizador\Models\My_task_tags_model;
use Organizador\Models\My_task_settings_model;
use Organizador\Models\My_tasks_model;
use Organizador\Plugin;

class Organizador extends Security_Controller
{
    public $Tasks_model;
    private $Categories_model;
    public $Tags_model;
    public $Phases_model;
    public $Settings_model;
    public $Notifications_model;
    public $Comments_model;
    public $Reminders_model;

    function __construct()
    {
        parent::__construct();
        $this->Tasks_model = model(My_tasks_model::class);
        $this->Categories_model = model(My_task_categories_model::class);
        $this->Tags_model = model(My_task_tags_model::class);
        $this->Phases_model = model(My_task_phases_model::class);
        $this->Settings_model = model(My_task_settings_model::class);
        $this->Notifications_model = model(My_task_notifications_model::class);
        $this->Comments_model = model(My_task_comments_model::class);
        $this->Reminders_model = model(My_task_reminders_model::class);
    }

    private function _ensure_access()
    {
        if (!Plugin::canAccessModule($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    private function _task_filter_options()
    {
        return array(
            'today' => app_lang('organizador_filter_today'),
            'tomorrow' => app_lang('organizador_filter_tomorrow'),
            'this_week' => app_lang('organizador_filter_this_week'),
            'overdue' => app_lang('organizador_filter_overdue'),
            'completed' => app_lang('organizador_filter_completed'),
            'favorites' => app_lang('organizador_filter_favorites'),
            'no_start' => app_lang('organizador_filter_no_start'),
            'urgent_not_started' => app_lang('organizador_filter_urgent_not_started'),
            'procrastination' => app_lang('organizador_filter_procrastination'),
            'forgotten' => app_lang('organizador_filter_forgotten'),
        );
    }

    public function index()
    {
        return $this->dashboard();
    }

    public function dashboard()
    {
        $this->_ensure_access();

        $view_data['dashboard'] = $this->Tasks_model->get_dashboard_intelligence($this->login_user->id, Plugin::canViewAllTasks($this->login_user));
        $view_data['task_filters'] = $this->_task_filter_options();
        return $this->template->rander('Organizador\\Views\\dashboard\\index', $view_data);
    }

    public function tasks()
    {
        $this->_ensure_access();

        $view_data['categories_dropdown'] = $this->Categories_model->get_dropdown();
        $view_data['statuses_dropdown'] = $this->Phases_model->get_dropdown();
        $view_data['priorities_dropdown'] = array(
            '' => '-',
            'low' => app_lang('organizador_priority_low'),
            'medium' => app_lang('organizador_priority_medium'),
            'high' => app_lang('organizador_priority_high'),
            'urgent' => app_lang('organizador_priority_urgent'),
        );
        $view_data['filters_dropdown'] = $this->_task_filter_options();
        $view_data['selected_quick_filter'] = clean_data($this->request->getGet('quick_filter'));
        $view_data['selected_status'] = clean_data($this->request->getGet('status'));
        $view_data['selected_priority'] = clean_data($this->request->getGet('priority'));
        $view_data['selected_category_id'] = (int) $this->request->getGet('category_id');
        return $this->template->rander('Organizador\\Views\\tasks\\index', $view_data);
    }

    public function list_data()
    {
        $this->_ensure_access();

        $filter = clean_data($this->request->getPost('quick_filter'));
        $status = clean_data($this->request->getPost('status'));
        $priority = clean_data($this->request->getPost('priority'));
        $category_id = (int) $this->request->getPost('category_id');
        $search = clean_data($this->request->getPost('search'));

        $options = array(
            'current_user_id' => $this->login_user->id,
            'view_all' => Plugin::canViewAllTasks($this->login_user),
            'quick_filter' => $filter,
            'status' => $status,
            'priority' => $priority,
            'category_id' => $category_id,
            'search' => $search,
        );

        $rows = array();
        foreach ($this->Tasks_model->get_details($options)->getResult() as $task) {
            $rows[] = $this->_make_row($task);
        }

        echo json_encode(array('data' => $rows));
    }

    private function _make_row($task)
    {
        $title = esc($task->title);
        if ((int) $task->is_favorite) {
            $title = '<span class="text-warning"><i data-feather="star" class="icon-14"></i></span> ' . $title;
        }
        if ($task->priority === 'urgent') {
            $title = '<span class="badge bg-danger me-1">' . app_lang('organizador_priority_urgent') . '</span> ' . $title;
        }

        $status = app_lang('organizador_status_' . $task->status);
        if (!empty($task->status_title)) {
            $status = $task->status_title;
        }
        $priority = app_lang('organizador_priority_' . $task->priority);
        $category = $task->category_title ? esc($task->category_title) : '-';
        $due_date = $task->due_date ? format_to_datetime($task->due_date) : '-';
        $assigned_to = $task->assigned_to_name ? esc($task->assigned_to_name) : '-';
        $status_color = $task->status_color ? $task->status_color : $this->Tasks_model->get_status_color($task->status);
        $status = '<span class="badge rounded-pill" style="background:' . esc($status_color) . ';">' . esc($status) . '</span>';
        if ($task->status !== 'done' && $task->due_date && strtotime($task->due_date) < time()) {
            $due_date = '<span class="text-danger fw-bold">' . $due_date . '</span>';
        }

        $actions = '';
        if (Plugin::canEditTasks($this->login_user) || (int) $task->created_by === (int) $this->login_user->id) {
            $actions .= modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('edit'), 'data-post-id' => $task->id));
            $actions .= js_anchor("<i data-feather='copy' class='icon-14'></i>", array(
                'class' => 'action-icon',
                'title' => app_lang('organizador_duplicate_task'),
                'data-id' => $task->id,
                'data-action-url' => get_uri('organizador/tasks/duplicate'),
                'data-act' => 'ajax-request',
                'data-show-response' => '1',
                'data-reload-on-success' => '1'
            ));
            $favorite_title = (int) $task->is_favorite ? app_lang('remove_from_favorites') : app_lang('add_to_favorites');
            $favorite_class = (int) $task->is_favorite ? 'action-icon text-warning' : 'action-icon';
            $actions .= ajax_anchor(get_uri('organizador/tasks/toggle_favorite'), "<i data-feather='star' class='icon-14'></i>", array(
                'class' => $favorite_class,
                'title' => $favorite_title,
                'data-post-id' => $task->id,
                'data-reload-on-success' => '1',
                'data-show-response' => '1'
            ));
        }
        if (Plugin::canDeleteTasks($this->login_user)) {
            $actions .= js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => $task->id, 'data-action-url' => get_uri('organizador/tasks/delete'), 'data-action' => 'delete-confirmation'));
        }

        return array(
            modal_anchor(get_uri('organizador/tasks/view'), $title, array('class' => 'font-bold', 'title' => app_lang('organizador_task_details'), 'data-post-id' => $task->id)),
            $priority,
            $status,
            $category,
            $due_date,
            $assigned_to,
            $actions,
        );
    }

    public function modal_form($id = 0)
    {
        $this->_ensure_access();
        $id = $id ? (int) $id : (int) $this->request->getPost('id');

        if ($id) {
            $view_data['model_info'] = $this->Tasks_model->get_one_with_details($id);
            $view_data['model_info']->due_date_date = $this->_extract_date($view_data['model_info']->due_date);
            $view_data['model_info']->due_date_time = $this->_extract_time($view_data['model_info']->due_date);
            if (isset($view_data['model_info']->reminder_before_value) && $view_data['model_info']->reminder_before_value !== null && $view_data['model_info']->reminder_before_value !== '') {
                $view_data['model_info']->reminder_before_value = (int) $view_data['model_info']->reminder_before_value;
                $view_data['model_info']->reminder_before_unit = $view_data['model_info']->reminder_before_unit ?: 'days';
            } else {
                $reminder_offset = $this->_extract_reminder_offset($view_data['model_info']->due_date, $view_data['model_info']->reminder_at);
                $view_data['model_info']->reminder_before_value = get_array_value($reminder_offset, 'value');
                $view_data['model_info']->reminder_before_unit = get_array_value($reminder_offset, 'unit') ?: 'days';
            }
        } else {
            $view_data['model_info'] = (object) array(
                'id' => 0,
                'title' => '',
                'description' => '',
                'status' => 'pending',
                'priority' => 'medium',
                'category_id' => '',
                'assigned_to' => '',
                'due_date_date' => '',
                'due_date_time' => '',
                'reminder_before_value' => '',
                'reminder_before_unit' => 'days',
                'is_favorite' => 0,
                'labels' => '',
                'notify_assigned_to' => 1,
                'notify_creator' => 1,
                'email_notification' => 1,
            );
        }

        $view_data['categories_dropdown'] = $this->Categories_model->get_dropdown();
        $view_data['statuses_dropdown'] = $this->Phases_model->get_dropdown();
        $view_data['priorities_dropdown'] = Plugin::taskPriorities();
        $view_data['tags_dropdown'] = $this->Tags_model->get_suggestions();
        $team_members = model('App\\Models\\Users_model')->get_team_members_id_and_name()->getResult();
        $view_data['team_members_dropdown'] = array(
            array("id" => "", "text" => "-"),
        );
        foreach ($team_members as $team_member) {
            $view_data['team_members_dropdown'][] = array(
                "id" => $team_member->id,
                "text" => $team_member->user_name,
            );
        }

        return view('Organizador\\Views\\tasks\\modal_form', $view_data);
    }

    private function _extract_date($datetime)
    {
        if (!$datetime) {
            return '';
        }

        $timestamp = strtotime($datetime);
        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    private function _extract_time($datetime)
    {
        if (!$datetime) {
            return '';
        }

        $timestamp = strtotime($datetime);
        return $timestamp ? date('H:i', $timestamp) : '';
    }

    private function _combine_datetime($date, $time)
    {
        $date = trim((string) $date);
        $time = trim((string) $time);

        if (!$date && !$time) {
            return null;
        }

        if (!$date) {
            return null;
        }

        if (!$time) {
            $time = '00:00';
        }

        $datetime = strtotime($date . ' ' . $time);
        return $datetime ? date('Y-m-d H:i:s', $datetime) : null;
    }

    private function _format_reminder_before_label($value, $unit)
    {
        $value = (int) $value;
        $unit = trim((string) $unit);

        if ($value <= 0 || $unit === '') {
            return '';
        }

        $unit_labels = array(
            'minutes' => app_lang('organizador_reminder_unit_minutes'),
            'hours' => app_lang('organizador_reminder_unit_hours'),
            'days' => app_lang('organizador_reminder_unit_days'),
        );

        $unit_label = get_array_value($unit_labels, $unit) ?: $unit;

        $before_due_text = app_lang('organizador_task_reminder') === 'Task reminder' ? 'before due date' : 'antes do prazo';

        return $value . ' ' . $unit_label . ' ' . $before_due_text;
    }

    private function _get_task_view_data($task)
    {
        $view_data = array(
            'task' => $task,
            'can_edit' => false,
            'can_delete' => false,
            'reminder_before_label' => '',
            'comments' => array(),
            'reminders' => array(),
            'login_user' => $this->login_user,
        );

        if ($task && $task->id) {
            $task->tags_html = $this->Tags_model->get_badges_html($task->labels ?? '');
            $view_data['can_edit'] = Plugin::canEditTasks($this->login_user) || (int) $task->created_by === (int) $this->login_user->id;
            $view_data['can_delete'] = Plugin::canDeleteTasks($this->login_user);
            $view_data['reminder_before_label'] = $this->_format_reminder_before_label(
                $task->reminder_before_value ?? null,
                $task->reminder_before_unit ?? null
            );
            $view_data['comments'] = $this->Comments_model->get_details(array('task_id' => $task->id))->getResult();
            $view_data['reminders'] = $this->Reminders_model->get_details(array(
                'task_id' => $task->id,
                'created_by' => $this->login_user->id,
            ))->getResult();
        }

        return $view_data;
    }

    private function _can_manage_task_comment($comment)
    {
        if (!$comment || !$comment->id) {
            return false;
        }

        return $this->login_user->is_admin || (int) $comment->created_by === (int) $this->login_user->id || Plugin::canEditTasks($this->login_user);
    }

    private function _can_manage_task_reminder($reminder)
    {
        if (!$reminder || !$reminder->id) {
            return false;
        }

        return $this->login_user->is_admin || (int) $reminder->created_by === (int) $this->login_user->id || Plugin::canEditTasks($this->login_user);
    }

    private function _calculate_reminder_at($due_date, $value, $unit)
    {
        $due_date = trim((string) $due_date);
        $value = (int) $value;
        $unit = trim((string) $unit);

        if (!$due_date || $value <= 0) {
            return null;
        }

        $due_timestamp = strtotime($due_date);
        if (!$due_timestamp) {
            return null;
        }

        $unit_seconds_map = array(
            'minutes' => 60,
            'hours' => 3600,
            'days' => 86400,
        );
        $unit_seconds = get_array_value($unit_seconds_map, $unit);
        if (!$unit_seconds) {
            $unit_seconds = 86400;
        }

        $reminder_timestamp = $due_timestamp - ($value * $unit_seconds);
        if ($reminder_timestamp <= 0) {
            return null;
        }

        return date('Y-m-d H:i:s', $reminder_timestamp);
    }

    private function _extract_reminder_offset($due_date, $reminder_at)
    {
        $due_timestamp = strtotime((string) $due_date);
        $reminder_timestamp = strtotime((string) $reminder_at);

        if (!$due_timestamp || !$reminder_timestamp || $reminder_timestamp >= $due_timestamp) {
            return array('value' => '', 'unit' => 'days');
        }

        $diff = $due_timestamp - $reminder_timestamp;
        if ($diff % 86400 === 0) {
            return array('value' => (int) ($diff / 86400), 'unit' => 'days');
        }
        if ($diff % 3600 === 0) {
            return array('value' => (int) ($diff / 3600), 'unit' => 'hours');
        }

        return array('value' => max(1, (int) round($diff / 60)), 'unit' => 'minutes');
    }

    public function save()
    {
        $this->_ensure_access();
        $this->validate_submitted_data(array(
            'title' => 'required'
        ));

        $id = (int) $this->request->getPost('id');
        $previous_task = $id ? $this->Tasks_model->get_one_with_details($id) : null;
        $labels = $this->_normalize_labels($this->request->getPost('labels'));
        $status = clean_data($this->request->getPost('status'));
        $available_statuses = array_keys($this->Phases_model->get_dropdown());
        if (!$status || !in_array($status, $available_statuses)) {
            $status = $this->Phases_model->get_default_key();
        }
        $data = array(
            'title' => clean_data($this->request->getPost('title')),
            'description' => clean_data($this->request->getPost('description')),
            'status' => $status,
            'priority' => clean_data($this->request->getPost('priority')) ?: 'medium',
            'category_id' => (int) $this->request->getPost('category_id') ?: null,
            'assigned_to' => (int) $this->request->getPost('assigned_to') ?: null,
            'due_date' => $this->_combine_datetime($this->request->getPost('due_date_date'), $this->request->getPost('due_date_time')),
            'position' => (int) $this->request->getPost('position'),
            'is_favorite' => $this->request->getPost('is_favorite') ? 1 : 0,
            'labels' => $labels,
            'notify_assigned_to' => $this->request->getPost('notify_assigned_to') ? 1 : 0,
            'notify_creator' => $this->request->getPost('notify_creator') ? 1 : 0,
            'email_notification' => $this->request->getPost('email_notification') ? 1 : 0,
            'deleted' => 0,
        );

        if (!$id) {
            $data['created_by'] = $this->login_user->id;
        }

        $reminder_before_value = $this->request->getPost('reminder_before_value');
        $reminder_before_unit = clean_data($this->request->getPost('reminder_before_unit')) ?: 'days';

        if ($id && ($reminder_before_value === null || $reminder_before_value === '') && $previous_task) {
            $reminder_before_value = $previous_task->reminder_before_value ?? '';
        }
        if ($id && (!$reminder_before_unit || $reminder_before_unit === 'days') && $previous_task && !empty($previous_task->reminder_before_unit)) {
            $reminder_before_unit = $previous_task->reminder_before_unit;
        }

        $data['start_date'] = $data['due_date'];
        $data['reminder_at'] = $this->_calculate_reminder_at(
            $data['due_date'],
            $reminder_before_value,
            $reminder_before_unit
        );
        $db = db_connect('default');
        $tasks_table = $db->prefixTable('my_tasks');
        if ($db->fieldExists('reminder_before_value', $tasks_table)) {
            $data['reminder_before_value'] = $reminder_before_value !== '' ? (int) $reminder_before_value : null;
        }
        if ($db->fieldExists('reminder_before_unit', $tasks_table)) {
            $data['reminder_before_unit'] = $reminder_before_value !== '' ? $reminder_before_unit : null;
        }

        $save_id = $this->Tasks_model->ci_save($data, $id);
        if ($save_id) {
            $task = $this->Tasks_model->get_one_with_details($save_id);
            Plugin::syncTaskEventToEventsCalendar($task);
            $event = $id ? 'organizador_task_updated' : 'organizador_task_created';
            Plugin::sendTaskNotification($event, $this->login_user->id, array(
                'task_id' => $save_id,
                'project_id' => null,
                'assigned_to' => $task->notify_assigned_to ? $task->assigned_to : null,
                'creator_id' => $task->notify_creator ? $task->created_by : null,
                'email_notification' => $task->email_notification,
            ));
            if ($id && $task->assigned_to && $task->notify_assigned_to && (!$previous_task || (int) $previous_task->assigned_to !== (int) $task->assigned_to)) {
                Plugin::sendTaskNotification('organizador_task_assigned', $this->login_user->id, array(
                    'task_id' => $save_id,
                    'assigned_to' => $task->assigned_to,
                    'creator_id' => $task->notify_creator ? $task->created_by : null,
                    'email_notification' => $task->email_notification,
                ));
            }
            echo json_encode(array('success' => true, 'data' => $this->_make_row($task), 'id' => $save_id, 'message' => app_lang('record_saved')));
            return;
        }

        echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function delete()
    {
        $this->_ensure_access();
        $id = (int) $this->request->getPost('id');
        if (!$id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $success = $this->Tasks_model->delete($id);
        if ($success) {
            $this->Comments_model->delete_by_task($id);
            $this->Reminders_model->delete_by_task($id);
            Plugin::deleteTaskEventFromEventsCalendar($id);
        }
        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    public function duplicate()
    {
        $this->_ensure_access();
        $id = (int) $this->request->getPost('id');
        $save_id = $this->Tasks_model->duplicate_task($id, $this->login_user->id);
        if ($save_id) {
            $task = $this->Tasks_model->get_one_with_details($save_id);
            Plugin::syncTaskEventToEventsCalendar($task);
            echo json_encode(array('success' => true, 'data' => $this->_make_row($task), 'id' => $save_id, 'message' => app_lang('record_saved')));
            return;
        }

        echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function complete()
    {
        $this->_ensure_access();
        $id = (int) $this->request->getPost('id');
        $done_key = $this->Phases_model->get_phase_by_key('done')->key_name ?? $this->Phases_model->get_default_key();
        $complete_data = array(
            'status' => $done_key,
            'completed_at' => get_current_utc_time(),
        );
        $success = $this->Tasks_model->ci_save($complete_data, $id);
        if ($success) {
            $task = $this->Tasks_model->get_one_with_details($id);
            Plugin::syncTaskEventToEventsCalendar($task);
            Plugin::sendTaskNotification('organizador_task_completed', $this->login_user->id, array(
                'task_id' => $id,
                'assigned_to' => $task->notify_assigned_to ? $task->assigned_to : null,
                'creator_id' => $task->notify_creator ? $task->created_by : null,
                'email_notification' => $task->email_notification,
            ));
        }

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function toggle_favorite()
    {
        $this->_ensure_access();
        $id = (int) $this->request->getPost('id');
        $task = $this->Tasks_model->get_one_with_details($id);
        $success = false;
        if ($task && $task->id) {
            $favorite_data = array(
                'is_favorite' => $task->is_favorite ? 0 : 1,
            );
            $success = $this->Tasks_model->ci_save($favorite_data, $id);
        }

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function update_status()
    {
        $this->_ensure_access();
        $id = (int) $this->request->getPost('id');
        $status = clean_data($this->request->getPost('status'));
        $position = (int) $this->request->getPost('position');
        $available_statuses = array_keys($this->Phases_model->get_dropdown());
        if (!$status || !in_array($status, $available_statuses)) {
            $status = $this->Phases_model->get_default_key();
        }
        $done_key = $this->Phases_model->get_phase_by_key('done')->key_name ?? 'done';
        $status_data = array(
            'status' => $status,
            'position' => $position,
            'completed_at' => $status === $done_key ? get_current_utc_time() : null,
        );
        $success = $this->Tasks_model->ci_save($status_data, $id);
        if ($success) {
            $task = $this->Tasks_model->get_one_with_details($id);
            Plugin::syncTaskEventToEventsCalendar($task);
        }

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function view($id = 0)
    {
        $this->_ensure_access();
        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        return view('Organizador\\Views\\tasks\\view', $this->_get_task_view_data($this->Tasks_model->get_one_with_details($id)));
    }

    public function save_comment()
    {
        $this->_ensure_access();

        $task_id = (int) $this->request->getPost('task_id');
        $task = $this->Tasks_model->get_one_with_details($task_id);
        if (!$task || !$task->id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $description = trim((string) $this->request->getPost('description'));
        $files_data = move_files_from_temp_dir_to_permanent_dir(get_setting('timeline_file_path'), 'organizador_comment');

        if ($description === '' && (!$files_data || $files_data === "a:0:{}")) {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $data = array(
            'task_id' => $task_id,
            'description' => $description,
            'files' => $files_data && $files_data !== "a:0:{}" ? $files_data : '',
            'created_by' => $this->login_user->id,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        $save_id = $this->Comments_model->ci_save($data);
        if (!$save_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $comment = $this->Comments_model->get_details(array('id' => $save_id))->getRow();
        $view_data = array(
            'comment' => $comment,
            'can_manage' => $this->_can_manage_task_comment($comment),
        );

        echo json_encode(array(
            'success' => true,
            'data' => view('Organizador\\Views\\tasks\\comment_row', $view_data),
            'message' => app_lang('comment_submited'),
        ));
    }

    public function delete_comment()
    {
        $this->_ensure_access();

        $id = (int) $this->request->getPost('id');
        $comment = $this->Comments_model->get_one($id);
        if (!$this->_can_manage_task_comment($comment)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        if (!empty($comment->files)) {
            delete_app_files(get_setting('timeline_file_path'), unserialize($comment->files));
        }

        $success = $this->Comments_model->ci_save(array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        ), $id);

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    public function download_comment_files($id = 0)
    {
        $this->_ensure_access();

        $comment = $this->Comments_model->get_one((int) $id);
        if (!$comment || !$comment->id || empty($comment->files)) {
            app_redirect('forbidden');
        }

        return $this->download_app_files(get_setting('timeline_file_path'), $comment->files);
    }

    public function save_reminder()
    {
        $this->_ensure_access();
        $this->validate_submitted_data(array(
            'title' => 'required',
            'remind_date' => 'required',
            'remind_time' => 'required',
        ));

        $task_id = (int) $this->request->getPost('task_id');
        $task = $this->Tasks_model->get_one_with_details($task_id);
        if (!$task || !$task->id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $data = array(
            'task_id' => $task_id,
            'title' => clean_data($this->request->getPost('title')),
            'description' => clean_data($this->request->getPost('description')),
            'remind_at' => $this->_combine_datetime($this->request->getPost('remind_date'), $this->request->getPost('remind_time')),
            'created_by' => $this->login_user->id,
            'is_done' => 0,
            'created_at' => get_current_utc_time(),
            'updated_at' => get_current_utc_time(),
            'deleted' => 0,
        );

        $save_id = $this->Reminders_model->ci_save($data);
        if (!$save_id) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $reminder = $this->Reminders_model->get_details(array('task_id' => $task_id, 'created_by' => $this->login_user->id))->getRow();
        $latest_reminder = $this->Reminders_model->get_one($save_id);
        $latest_reminder->created_by_user = trim($this->login_user->first_name . ' ' . $this->login_user->last_name);
        $latest_reminder->created_by_avatar = $this->login_user->image;
        $view_data = array(
            'reminder' => $latest_reminder,
            'can_manage' => $this->_can_manage_task_reminder($latest_reminder),
        );

        echo json_encode(array(
            'success' => true,
            'data' => view('Organizador\\Views\\tasks\\reminder_row', $view_data),
            'message' => app_lang('record_saved'),
        ));
    }

    public function delete_reminder()
    {
        $this->_ensure_access();

        $id = (int) $this->request->getPost('id');
        $reminder = $this->Reminders_model->get_one($id);
        if (!$this->_can_manage_task_reminder($reminder)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $success = $this->Reminders_model->ci_save(array(
            'deleted' => 1,
            'updated_at' => get_current_utc_time(),
        ), $id);

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    public function update_reminder_status()
    {
        $this->_ensure_access();

        $id = (int) $this->request->getPost('id');
        $reminder = $this->Reminders_model->get_one($id);
        if (!$this->_can_manage_task_reminder($reminder)) {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
            return;
        }

        $success = $this->Reminders_model->ci_save(array(
            'is_done' => $reminder->is_done ? 0 : 1,
            'updated_at' => get_current_utc_time(),
        ), $id);

        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function kanban()
    {
        $this->_ensure_access();
        $view_data['phases'] = $this->Phases_model->get_all_phases();
        $view_data['tasks_list'] = $this->Tasks_model->get_kanban_data($this->login_user->id, Plugin::canViewAllTasks($this->login_user));
        $this->_attach_task_tags($view_data['tasks_list']);
        $view_data['can_edit'] = Plugin::canEditTasks($this->login_user);
        return $this->template->rander('Organizador\\Views\\kanban\\index', $view_data);
    }

    public function kanban_data()
    {
        $this->_ensure_access();
        $tasks_list = $this->Tasks_model->get_kanban_data($this->login_user->id, Plugin::canViewAllTasks($this->login_user));
        $this->_attach_task_tags($tasks_list);
        echo json_encode(array('success' => true, 'data' => $tasks_list));
    }

    public function calendar()
    {
        $this->_ensure_access();
        return $this->template->rander('Organizador\\Views\\calendar\\index');
    }

    public function calendar_data()
    {
        $this->_ensure_access();
        $start_date = clean_data($this->request->getGet('start'));
        $end_date = clean_data($this->request->getGet('end'));

        return $this->response->setJSON($this->Tasks_model->get_calendar_events(
            $this->login_user->id,
            Plugin::canViewAllTasks($this->login_user),
            $start_date,
            $end_date
        ));
    }

    private function _normalize_labels($labels)
    {
        if (is_array($labels)) {
            $labels = implode(',', $labels);
        }

        $labels = trim((string) $labels);
        if ($labels === '') {
            return '';
        }

        $ids = array();
        foreach (explode(',', $labels) as $label_id) {
            $label_id = (int) trim($label_id);
            if ($label_id) {
                $ids[] = $label_id;
            }
        }

        return implode(',', array_unique($ids));
    }

    private function _attach_task_tags(&$tasks_list)
    {
        foreach ($tasks_list as $status => $tasks) {
            foreach ($tasks as $task) {
                $task->tags_html = $this->Tags_model->get_badges_html($task->labels ?? '');
            }
        }
    }
}
