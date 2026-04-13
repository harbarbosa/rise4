<?php

namespace Organizador\Models;

use App\Models\Crud_model;

class My_tasks_model extends Crud_model
{
    protected $table = 'my_tasks';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $tasks_table = $this->db->prefixTable('my_tasks');
        $categories_table = $this->db->prefixTable('my_task_categories');
        $phases_table = $this->db->prefixTable('my_task_phases');
        $users_table = $this->db->prefixTable('users');

        $where = " AND $tasks_table.deleted=0";
        if (get_array_value($options, 'include_deleted')) {
            $where = "";
        }

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $tasks_table.id=$id";
        }

        $status = get_array_value($options, 'status');
        if ($status) {
            $where .= " AND $tasks_table.status=" . $this->db->escape($status);
        }

        $priority = get_array_value($options, 'priority');
        if ($priority) {
            $where .= " AND $tasks_table.priority=" . $this->db->escape($priority);
        }

        $category_id = (int) get_array_value($options, 'category_id');
        if ($category_id) {
            $where .= " AND $tasks_table.category_id=$category_id";
        }

        if (get_array_value($options, 'favorites_only')) {
            $where .= " AND $tasks_table.is_favorite=1";
        }

        $quick_filter = clean_data(get_array_value($options, 'quick_filter'));
        if ($quick_filter) {
            $where .= $this->get_quick_filter_sql($quick_filter, $tasks_table);
        }

        $assigned_to = (int) get_array_value($options, 'assigned_to');
        if ($assigned_to) {
            $where .= " AND $tasks_table.assigned_to=$assigned_to";
        }

        $created_by = (int) get_array_value($options, 'created_by');
        if ($created_by) {
            $where .= " AND $tasks_table.created_by=$created_by";
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== "") {
            $search = $this->db->escapeLikeString($search);
            $tags_table = $this->db->prefixTable('my_task_tags');
            $where .= " AND (" . implode(" OR ", array(
                "$tasks_table.title LIKE '%$search%'",
                "$tasks_table.description LIKE '%$search%'",
                "$tasks_table.labels LIKE '%$search%'",
                "EXISTS (SELECT 1 FROM $tags_table WHERE $tags_table.deleted=0 AND FIND_IN_SET($tags_table.id, $tasks_table.labels) AND $tags_table.title LIKE '%$search%')"
            )) . ")";
        }

        $view_all = get_array_value($options, 'view_all');
        $current_user_id = (int) get_array_value($options, 'current_user_id');
        if (!$view_all && $current_user_id) {
            $where .= " AND ($tasks_table.assigned_to=$current_user_id OR $tasks_table.created_by=$current_user_id)";
        }

        $date_filter = get_array_value($options, 'date_filter');
        if (is_array($date_filter)) {
            $type = get_array_value($date_filter, 'type');
            if ($type === 'today') {
                $where .= " AND DATE($tasks_table.due_date) = CURDATE()";
            } elseif ($type === 'tomorrow') {
                $where .= " AND DATE($tasks_table.due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
            } elseif ($type === 'this_week') {
                $where .= " AND YEARWEEK($tasks_table.due_date, 1) = YEARWEEK(CURDATE(), 1)";
            }
        }

        $date_filter_sql = trim((string) get_array_value($options, 'date_filter_sql'));
        if ($date_filter_sql !== '') {
            $where .= " " . $date_filter_sql;
        }

        if (get_array_value($options, 'overdue_only')) {
            $where .= " AND $tasks_table.due_date < NOW() AND $tasks_table.status != 'done' AND $tasks_table.status != 'canceled'";
        }

        $limit = (int) get_array_value($options, 'limit');
        if (!$limit) {
            $limit = 1000;
        }
        $offset = (int) get_array_value($options, 'offset');

        $sql = "SELECT $tasks_table.*,
                $categories_table.title AS category_title,
                $categories_table.color AS category_color,
                $phases_table.title AS status_title,
                $phases_table.color AS status_color,
                CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) AS assigned_to_name,
                $users_table.image AS assigned_to_avatar
            FROM $tasks_table
            LEFT JOIN $categories_table ON $categories_table.id = $tasks_table.category_id AND $categories_table.deleted=0
            LEFT JOIN $phases_table ON $phases_table.key_name = $tasks_table.status AND $phases_table.deleted=0
            LEFT JOIN $users_table ON $users_table.id = $tasks_table.assigned_to AND $users_table.deleted=0
            WHERE 1=1 $where
            ORDER BY $tasks_table.position ASC, $tasks_table.due_date ASC, $tasks_table.id DESC";

        if ($limit) {
            $sql .= " LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        }

        return $this->db->query($sql);
    }

    public function get_quick_filter_sql($quick_filter, $tasks_table = null)
    {
        $quick_filter = clean_data($quick_filter);
        if (!$quick_filter) {
            return "";
        }

        if (!$tasks_table) {
            $tasks_table = $this->db->prefixTable('my_tasks');
        }

        $open_status_sql = "$tasks_table.status NOT IN ('done', 'canceled')";
        $reference_datetime_sql = "COALESCE($tasks_table.updated_at, $tasks_table.created_at)";

        switch ($quick_filter) {
            case 'today':
                return " AND (DATE($tasks_table.due_date) = CURDATE() OR DATE($tasks_table.start_date) = CURDATE())";
            case 'tomorrow':
                return " AND (DATE($tasks_table.due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) OR DATE($tasks_table.start_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY))";
            case 'this_week':
                return " AND (YEARWEEK($tasks_table.due_date, 1) = YEARWEEK(CURDATE(), 1) OR YEARWEEK($tasks_table.start_date, 1) = YEARWEEK(CURDATE(), 1))";
            case 'overdue':
                return " AND $tasks_table.due_date < NOW() AND $open_status_sql";
            case 'completed':
                return " AND $tasks_table.status = 'done'";
            case 'favorites':
                return " AND $tasks_table.is_favorite=1";
            case 'no_start':
                return " AND $tasks_table.status = 'pending' AND ($tasks_table.start_date IS NULL OR $tasks_table.start_date = '0000-00-00 00:00:00')";
            case 'urgent_not_started':
                return " AND $tasks_table.priority = 'urgent' AND $tasks_table.status = 'pending' AND ($tasks_table.start_date IS NULL OR $tasks_table.start_date = '0000-00-00 00:00:00') AND ($tasks_table.due_date IS NULL OR DATE($tasks_table.due_date) <= DATE_ADD(CURDATE(), INTERVAL 2 DAY))";
            case 'forgotten':
                return " AND $tasks_table.status = 'pending' AND DATEDIFF(CURDATE(), DATE($tasks_table.created_at)) >= 7 AND DATEDIFF(CURDATE(), DATE($reference_datetime_sql)) >= 5";
            case 'procrastination':
                return " AND (
                    ($tasks_table.status = 'pending' AND DATEDIFF(CURDATE(), DATE($tasks_table.created_at)) >= 5)
                    OR ($tasks_table.priority IN ('high', 'urgent') AND $tasks_table.status = 'pending' AND ($tasks_table.start_date IS NULL OR $tasks_table.start_date = '0000-00-00 00:00:00'))
                    OR ($tasks_table.due_date IS NOT NULL AND DATE($tasks_table.due_date) <= DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND DATEDIFF(CURDATE(), DATE($reference_datetime_sql)) >= 2 AND $open_status_sql)
                    OR ($tasks_table.status = 'in_progress' AND DATEDIFF(CURDATE(), DATE($reference_datetime_sql)) >= 10)
                )";
            default:
                return "";
        }
    }

    public function get_one_with_details($id)
    {
        return $this->get_details(array('id' => (int) $id))->getRow();
    }

    public function get_dashboard_stats($current_user_id = 0, $view_all = false)
    {
        $tasks_table = $this->db->prefixTable('my_tasks');
        $where = "WHERE $tasks_table.deleted=0";
        if (!$view_all && $current_user_id) {
            $where .= " AND ($tasks_table.assigned_to=" . (int) $current_user_id . " OR $tasks_table.created_by=" . (int) $current_user_id . ")";
        }

        $sql = "SELECT
            SUM(CASE WHEN $tasks_table.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN DATE($tasks_table.due_date) = CURDATE() AND $tasks_table.status != 'done' AND $tasks_table.status != 'canceled' THEN 1 ELSE 0 END) AS today_count,
            SUM(CASE WHEN $tasks_table.due_date < NOW() AND $tasks_table.status != 'done' AND $tasks_table.status != 'canceled' THEN 1 ELSE 0 END) AS overdue_count,
            SUM(CASE WHEN $tasks_table.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN $tasks_table.status = 'done' THEN 1 ELSE 0 END) AS done_count,
            SUM(CASE WHEN $tasks_table.priority = 'urgent' AND $tasks_table.status != 'done' AND $tasks_table.status != 'canceled' THEN 1 ELSE 0 END) AS urgent_count
            FROM $tasks_table
            $where";

        return $this->db->query($sql)->getRow();
    }

    public function get_kanban_data($current_user_id = 0, $view_all = false, $filters = array())
    {
        $phases_model = model('Organizador\\Models\\My_task_phases_model');
        $statuses = array_keys($phases_model->get_dropdown());
        if (!$statuses) {
            $statuses = array('pending', 'in_progress', 'done', 'canceled');
        }
        $data = array();
        foreach ($statuses as $status) {
            $options = array_merge($filters, array(
                'status' => $status,
                'current_user_id' => $current_user_id,
                'view_all' => $view_all,
                'limit' => 500,
            ));
            $data[$status] = $this->get_details($options)->getResult();
        }
        return $data;
    }

    public function get_calendar_events($current_user_id = 0, $view_all = false, $start_date = null, $end_date = null)
    {
        $tasks_table = $this->db->prefixTable('my_tasks');
        $date_filter = "";
        if ($start_date && $end_date) {
            $start_date = $this->db->escape($start_date);
            $end_date = $this->db->escape($end_date);
            $date_filter = " AND ($tasks_table.due_date IS NOT NULL AND $tasks_table.due_date BETWEEN $start_date AND $end_date)";
        }

        $rows = $this->get_details(array(
            'current_user_id' => $current_user_id,
            'view_all' => $view_all,
            'date_filter_sql' => $date_filter,
            'limit' => 1000,
        ))->getResult();

        $events = array();
        foreach ($rows as $row) {
            if (!$row->due_date) {
                continue;
            }

            $timestamp = strtotime($row->due_date);
            if (!$timestamp) {
                continue;
            }

            $title = $row->title;
            if ($row->priority === 'urgent') {
                $title = '!' . $title;
            }

            $is_all_day = date('H:i:s', $timestamp) === '00:00:00';
            $event_start = date('Y-m-d H:i:s', $timestamp);
            $event_end = $event_start;
            if ($is_all_day) {
                $event_start = date('Y-m-d', $timestamp) . ' 00:00:00';
                $event_end = date('Y-m-d', $timestamp) . ' 23:59:59';
            }

            $class_names = array('event-deadline-border');
            if ($row->status !== 'done' && $row->status !== 'canceled' && $timestamp < time()) {
                $class_names[] = 'event-overdue';
            }

            $events[] = array(
                'id' => $row->id . '-due_date',
                'title' => $title,
                'start' => $event_start,
                'end' => $event_end,
                'allDay' => $is_all_day,
                'backgroundColor' => $this->get_status_color($row->status),
                'borderColor' => $this->get_status_color($row->status),
                'classNames' => $class_names,
                'extendedProps' => array(
                    'task_id' => $row->id,
                    'status' => $row->status,
                    'status_title' => $row->status_title ?? '',
                    'field' => 'due_date',
                    'priority' => $row->priority,
                ),
            );
        }

        return $events;
    }

    public function get_due_tasks($hours_before_due = 24)
    {
        $tasks_table = $this->db->prefixTable('my_tasks');
        $hours_before_due = (int) $hours_before_due;
        $sql = "SELECT *
            FROM $tasks_table
            WHERE $tasks_table.deleted=0
            AND $tasks_table.status NOT IN ('done','canceled')
            AND $tasks_table.due_date IS NOT NULL
            AND $tasks_table.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL $hours_before_due HOUR)
            ORDER BY $tasks_table.due_date ASC";

        return $this->db->query($sql);
    }

    public function get_overdue_tasks()
    {
        $tasks_table = $this->db->prefixTable('my_tasks');
        $sql = "SELECT *
            FROM $tasks_table
            WHERE $tasks_table.deleted=0
            AND $tasks_table.status NOT IN ('done','canceled')
            AND $tasks_table.due_date IS NOT NULL
            AND $tasks_table.due_date < NOW()
            ORDER BY $tasks_table.due_date ASC";

        return $this->db->query($sql);
    }

    public function mark_reminder_sent($task_id)
    {
        return $this->db->table($this->table)->where('id', (int) $task_id)->update(array(
            'reminder_sent_at' => get_current_utc_time(),
        ));
    }

    public function duplicate_task($task_id, $created_by = 0)
    {
        $task = $this->get_one_with_details($task_id);
        if (!$task || !$task->id) {
            return false;
        }

        $data = array(
            'title' => $task->title . ' - ' . app_lang('organizador_duplicate_task'),
            'description' => $task->description,
            'status' => model('Organizador\\Models\\My_task_phases_model')->get_default_key('pending'),
            'priority' => $task->priority,
            'category_id' => $task->category_id,
            'assigned_to' => $task->assigned_to,
            'created_by' => $created_by ?: $task->created_by,
            'start_date' => $task->start_date,
            'due_date' => $task->due_date,
            'reminder_at' => $task->reminder_at,
            'position' => (int) $task->position + 1,
            'is_favorite' => 0,
            'labels' => $task->labels,
            'notify_assigned_to' => $task->notify_assigned_to,
            'notify_creator' => $task->notify_creator,
            'email_notification' => $task->email_notification,
            'deleted' => 0,
        );

        return $this->ci_save($data);
    }

    public function get_status_color($status)
    {
        $phases_model = model('Organizador\\Models\\My_task_phases_model');
        return $phases_model->get_color_by_key($status);
    }

    public function get_status_title($status)
    {
        $phases_model = model('Organizador\\Models\\My_task_phases_model');
        $phase = $phases_model->get_phase_by_key($status);
        return $phase && $phase->title ? $phase->title : ucfirst(str_replace('_', ' ', (string) $status));
    }

    public function get_labels_html($labels = "")
    {
        $tags_model = model('Organizador\\Models\\My_task_tags_model');
        return $tags_model->get_badges_html($labels);
    }

    public function get_dashboard_intelligence($current_user_id = 0, $view_all = false)
    {
        $tasks_table = $this->db->prefixTable('my_tasks');
        $tasks = $this->get_details(array(
            'current_user_id' => $current_user_id,
            'view_all' => $view_all,
            'limit' => 5000,
        ));

        $rows = $tasks ? $tasks->getResult() : array();
        $now = time();
        $priority_counts = array(
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'urgent' => 0,
        );
        $status_counts = array();
        $category_counts = array();
        $category_overdue_counts = array();
        $task_rows = array();
        $summary = array(
            'today' => 0,
            'overdue' => 0,
            'urgent' => 0,
            'no_start' => 0,
            'completed_week' => 0,
            'procrastination' => 0,
            'in_progress_old' => 0,
            'forgotten' => 0,
            'due_soon' => 0,
            'no_update' => 0,
        );
        $urgent_not_started = array();
        $procrastination_risks = array();
        $forgotten_tasks = array();

        $week_start = strtotime('monday this week', $now);
        $week_end = strtotime('sunday this week', $now);
        $default_statuses = array('pending', 'in_progress', 'done', 'canceled');

        foreach ($rows as $row) {
            $status = isset($row->status) ? (string) $row->status : 'pending';
            $priority = isset($row->priority) ? (string) $row->priority : 'medium';
            $created_ts = $this->normalize_task_timestamp($row->created_at ?? null);
            $updated_ts = $this->normalize_task_timestamp($row->updated_at ?? null);
            $start_ts = $this->normalize_task_timestamp($row->start_date ?? null);
            $due_ts = $this->normalize_task_timestamp($row->due_date ?? null);
            $completed_ts = $this->normalize_task_timestamp($row->completed_at ?? null);
            $reference_updated_ts = $updated_ts ?: $created_ts;
            $days_since_created = $created_ts ? (int) floor(($now - $created_ts) / 86400) : 0;
            $days_since_updated = $reference_updated_ts ? (int) floor(($now - $reference_updated_ts) / 86400) : 0;
            $days_to_due = $due_ts ? (int) floor(($due_ts - $now) / 86400) : null;
            $days_overdue = ($due_ts && $due_ts < $now) ? (int) floor(($now - $due_ts) / 86400) : 0;
            $is_open = !in_array($status, array('done', 'canceled'), true);
            $is_pending = $status === 'pending';
            $has_start = (bool) $start_ts;
            $is_no_start = $is_pending && !$has_start;
            $is_urgent_not_started = ($priority === 'urgent' && $is_pending && $is_no_start && ($days_to_due === null || $days_to_due <= 2));
            $is_due_soon = $is_open && $due_ts && $days_to_due !== null && $days_to_due >= 0 && $days_to_due <= 2;
            $is_overdue = $is_open && $due_ts && $days_to_due !== null && $days_to_due < 0;
            $is_forgotten = $is_pending && $days_since_created >= 7 && $days_since_updated >= 5;
            $risk = $this->get_task_risk_profile($row, $now);

            if (in_array($status, $default_statuses, true)) {
                if (!isset($status_counts[$status])) {
                    $status_counts[$status] = 0;
                }
                $status_counts[$status]++;
            } else {
                if (!isset($status_counts[$status])) {
                    $status_counts[$status] = 0;
                }
                $status_counts[$status]++;
            }

            if (!isset($priority_counts[$priority])) {
                $priority_counts[$priority] = 0;
            }
            $priority_counts[$priority]++;

            $category_key = $row->category_title ?: app_lang('organizador_categories');
            if (!isset($category_counts[$category_key])) {
                $category_counts[$category_key] = array(
                    'count' => 0,
                    'overdue' => 0,
                    'label' => $category_key,
                );
            }

            if ($is_open) {
                $category_counts[$category_key]['count']++;
            }
            if ($is_overdue) {
                $category_counts[$category_key]['overdue']++;
            }

            if ($is_open && $due_ts && $days_to_due !== null && $days_to_due >= 0 && $days_to_due <= 2) {
                $summary['due_soon']++;
            }
            if ($is_overdue) {
                $summary['overdue']++;
            }
            if ($priority === 'urgent' && $is_open) {
                $summary['urgent']++;
            }
            if ($is_no_start) {
                $summary['no_start']++;
            }
            $risk_score = (int) get_array_value($risk, 'risk_score');
            if (!$risk_score) {
                $risk_score = (int) get_array_value($risk, 'score');
            }

            if ($risk_score >= 3) {
                $summary['procrastination']++;
            }
            if ($status === 'in_progress' && $days_since_updated >= 10) {
                $summary['in_progress_old']++;
            }
            if ($is_forgotten) {
                $summary['forgotten']++;
            }
            if ($reference_updated_ts && $days_since_updated >= 5 && $is_open) {
                $summary['no_update']++;
            }
            if (($due_ts && date('Y-m-d', $due_ts) === date('Y-m-d', $now) && $is_open) || ($start_ts && date('Y-m-d', $start_ts) === date('Y-m-d', $now) && $is_open)) {
                $summary['today']++;
            }

            if ($completed_ts && $completed_ts >= $week_start && $completed_ts <= $week_end) {
                $summary['completed_week']++;
            }

            if ($is_urgent_not_started) {
                $urgent_not_started[] = $this->build_task_intelligence_row($row, $risk, $days_since_updated, $days_to_due, $days_since_created, app_lang('organizador_dashboard_urgent_not_started_reason'));
            }

            if ($risk_score >= 3) {
                $procrastination_risks[] = $this->build_task_intelligence_row($row, $risk, $days_since_updated, $days_to_due, $days_since_created);
            }

            if ($is_forgotten) {
                $forgotten_tasks[] = $this->build_task_intelligence_row($row, $risk, $days_since_updated, $days_to_due, $days_since_created);
            }
        }

        $summary['today'] = (int) $summary['today'];
        $summary['completed_week'] = (int) $summary['completed_week'];

        $alerts = $this->build_dashboard_alerts($summary, $category_counts, $urgent_not_started, $procrastination_risks, $forgotten_tasks);
        $suggestions = $this->build_dashboard_suggestions($summary, $category_counts, $urgent_not_started, $procrastination_risks, $forgotten_tasks);
        $attention = $this->build_attention_line($summary);

        $priority_chart = array(
            'labels' => array(
                app_lang('organizador_priority_low'),
                app_lang('organizador_priority_medium'),
                app_lang('organizador_priority_high'),
                app_lang('organizador_priority_urgent'),
            ),
            'data' => array(
                (int) get_array_value($priority_counts, 'low'),
                (int) get_array_value($priority_counts, 'medium'),
                (int) get_array_value($priority_counts, 'high'),
                (int) get_array_value($priority_counts, 'urgent'),
            ),
            'colors' => array('#adb5bd', '#0d6efd', '#fd7e14', '#dc3545'),
        );

        $status_model = model('Organizador\\Models\\My_task_phases_model');
        $status_labels = array();
        $status_data = array();
        $status_colors = array();
        foreach ($status_model->get_all_phases() as $phase) {
            $status_labels[] = $phase->title ?: app_lang('organizador_status_' . $phase->key_name);
            $status_data[] = (int) get_array_value($status_counts, $phase->key_name);
            $status_colors[] = $phase->color ?: $status_model->get_color_by_key($phase->key_name);
        }

        $status_chart = array(
            'labels' => $status_labels,
            'data' => $status_data,
            'colors' => $status_colors,
        );

        uasort($category_counts, function ($a, $b) {
            $a_total = (int) get_array_value($a, 'count') + (int) get_array_value($a, 'overdue');
            $b_total = (int) get_array_value($b, 'count') + (int) get_array_value($b, 'overdue');
            if ($a_total === $b_total) {
                return strcmp((string) get_array_value($a, 'label'), (string) get_array_value($b, 'label'));
            }
            return $b_total <=> $a_total;
        });

        $category_ranking = array();
        foreach (array_slice($category_counts, 0, 6) as $category) {
            $category_ranking[] = array(
                'label' => $category['label'],
                'pending' => (int) $category['count'],
                'overdue' => (int) $category['overdue'],
                'total' => (int) $category['count'] + (int) $category['overdue'],
            );
        }

        return array(
            'summary' => $summary,
            'alerts' => $alerts,
            'attention' => $attention,
            'urgent_not_started' => array_slice($urgent_not_started, 0, 6),
            'procrastination_risks' => array_slice($procrastination_risks, 0, 8),
            'forgotten_tasks' => array_slice($forgotten_tasks, 0, 6),
            'suggestions' => $suggestions,
            'priority_chart' => $priority_chart,
            'status_chart' => $status_chart,
            'category_ranking' => $category_ranking,
        );
    }

    public function get_task_risk_profile($task, $now_ts = null)
    {
        $now_ts = $now_ts ?: time();
        $created_ts = $this->normalize_task_timestamp($task->created_at ?? null);
        $updated_ts = $this->normalize_task_timestamp($task->updated_at ?? null);
        $start_ts = $this->normalize_task_timestamp($task->start_date ?? null);
        $due_ts = $this->normalize_task_timestamp($task->due_date ?? null);
        $reference_updated_ts = $updated_ts ?: $created_ts;
        $status = isset($task->status) ? (string) $task->status : 'pending';
        $priority = isset($task->priority) ? (string) $task->priority : 'medium';

        $days_since_created = $created_ts ? (int) floor(($now_ts - $created_ts) / 86400) : 0;
        $days_since_updated = $reference_updated_ts ? (int) floor(($now_ts - $reference_updated_ts) / 86400) : 0;
        $days_to_due = $due_ts ? (int) floor(($due_ts - $now_ts) / 86400) : null;
        $days_overdue = ($due_ts && $due_ts < $now_ts) ? (int) floor(($now_ts - $due_ts) / 86400) : 0;

        $score = 0;
        $reasons = array();
        $open_status = !in_array($status, array('done', 'canceled'), true);
        $has_start = (bool) $start_ts;

        if ($status === 'pending' && $days_since_created >= 5) {
            $score += 2;
            $reasons[] = sprintf(app_lang('organizador_reason_pending_days'), $days_since_created);
        }
        if (in_array($priority, array('high', 'urgent'), true) && $status === 'pending' && !$has_start) {
            $score += 2;
            $reasons[] = app_lang('organizador_reason_high_without_start');
        }
        if ($open_status && $due_ts && $days_to_due !== null && $days_to_due <= 2 && $days_since_updated >= 2) {
            $score += 2;
            $reasons[] = app_lang('organizador_reason_due_soon_without_update');
        }
        if ($status === 'in_progress' && $days_since_updated >= 10) {
            $score += 2;
            $reasons[] = app_lang('organizador_reason_in_progress_too_long');
        }
        if ($status === 'pending' && $days_since_created >= 7 && $days_since_updated >= 5) {
            $score += 2;
            $reasons[] = app_lang('organizador_reason_old_pending');
        }
        if ($open_status && $due_ts && $days_to_due !== null && $days_to_due < 0 && $days_overdue >= 7) {
            $score += 2;
            $reasons[] = sprintf(app_lang('organizador_reason_overdue_days'), $days_overdue);
        }
        if ($open_status && $days_since_updated >= 5) {
            $score += 1;
            $reasons[] = app_lang('organizador_reason_no_recent_update');
        }
        if ($priority === 'urgent' && $status === 'pending' && (!$has_start || ($days_to_due !== null && $days_to_due <= 2))) {
            $score += 2;
            $reasons[] = app_lang('organizador_reason_urgent_unstarted');
        }

        $risk_level = 'low';
        if ($score >= 5 || ($priority === 'urgent' && $status === 'pending' && $days_overdue >= 1)) {
            $risk_level = 'critical';
        } elseif ($score >= 3) {
            $risk_level = 'attention';
        } elseif ($score >= 1) {
            $risk_level = 'info';
        }

        return array(
            'score' => $score,
            'risk_level' => $risk_level,
            'reasons' => array_values(array_unique(array_filter($reasons))),
            'days_since_created' => $days_since_created,
            'days_since_updated' => $days_since_updated,
            'days_to_due' => $days_to_due,
            'days_overdue' => $days_overdue,
            'is_open' => $open_status,
            'is_pending' => $status === 'pending',
            'is_urgent_not_started' => ($priority === 'urgent' && $status === 'pending' && !$has_start && ($days_to_due === null || $days_to_due <= 2)),
            'is_forgotten' => ($status === 'pending' && $days_since_created >= 7 && $days_since_updated >= 5),
            'is_procrastination' => ($score >= 3),
        );
    }

    private function normalize_task_timestamp($datetime)
    {
        if (!$datetime) {
            return 0;
        }

        $timestamp = strtotime($datetime);
        return $timestamp ?: 0;
    }

    private function build_task_intelligence_row($task, $risk, $days_since_updated, $days_to_due, $days_since_created, $reason_override = '')
    {
        $due_text = $task->due_date ? format_to_datetime($task->due_date) : '-';
        $reason = $reason_override;
        if (!$reason) {
            $reason = !empty($risk['reasons']) ? implode(' - ', $risk['reasons']) : app_lang('organizador_reason_no_recent_update');
        }

        return array(
            'id' => $task->id,
            'title' => $task->title,
            'priority' => $task->priority,
            'status' => $task->status,
            'category' => $task->category_title ?: '-',
            'assigned_to' => $task->assigned_to_name ?: '-',
            'created_by' => (int) ($task->created_by ?? 0),
            'created_at' => $task->created_at ? format_to_datetime($task->created_at) : '-',
            'updated_at' => $task->updated_at ? format_to_datetime($task->updated_at) : '-',
            'due_date' => $due_text,
            'days_without_update' => (int) $days_since_updated,
            'days_since_created' => (int) $days_since_created,
            'days_to_due' => $days_to_due,
            'reason' => $reason,
            'url' => get_uri('organizador/tasks/view/' . $task->id),
            'edit_url' => get_uri('organizador/tasks/modal_form'),
            'risk_level' => $risk['risk_level'],
            'risk_score' => (int) $risk['score'],
            'labels_html' => $this->get_labels_html($task->labels ?? ''),
        );
    }

    private function build_dashboard_alerts($summary, $category_counts, $urgent_not_started, $procrastination_risks, $forgotten_tasks)
    {
        $alerts = array();

        if (!empty($urgent_not_started)) {
            $alerts[] = array(
                'severity' => 'critical',
                'icon' => 'alert-triangle',
                'title' => app_lang('organizador_alert_urgent_not_started_title'),
                'message' => sprintf(app_lang('organizador_alert_urgent_not_started_message'), count($urgent_not_started)),
                'count' => count($urgent_not_started),
                'filter' => 'urgent_not_started',
            );
        }

        if ((int) $summary['overdue'] > 0) {
            $alerts[] = array(
                'severity' => 'critical',
                'icon' => 'clock',
                'title' => app_lang('organizador_alert_overdue_title'),
                'message' => sprintf(app_lang('organizador_alert_overdue_message'), (int) $summary['overdue']),
                'count' => (int) $summary['overdue'],
                'filter' => 'overdue',
            );
        }

        if ((int) $summary['procrastination'] > 0) {
            $alerts[] = array(
                'severity' => 'attention',
                'icon' => 'watch',
                'title' => app_lang('organizador_alert_procrastination_title'),
                'message' => sprintf(app_lang('organizador_alert_procrastination_message'), (int) $summary['procrastination']),
                'count' => (int) $summary['procrastination'],
                'filter' => 'procrastination',
            );
        }

        if ((int) $summary['in_progress_old'] > 0) {
            $alerts[] = array(
                'severity' => 'attention',
                'icon' => 'loader',
                'title' => app_lang('organizador_alert_in_progress_old_title'),
                'message' => sprintf(app_lang('organizador_alert_in_progress_old_message'), (int) $summary['in_progress_old']),
                'count' => (int) $summary['in_progress_old'],
                'filter' => 'procrastination',
            );
        }

        if ((int) $summary['forgotten'] > 0) {
            $alerts[] = array(
                'severity' => 'attention',
                'icon' => 'archive',
                'title' => app_lang('organizador_alert_forgotten_title'),
                'message' => sprintf(app_lang('organizador_alert_forgotten_message'), (int) $summary['forgotten']),
                'count' => (int) $summary['forgotten'],
                'filter' => 'forgotten',
            );
        }

        if (!empty($category_counts)) {
            $top_category = reset($category_counts);
            $top_total = (int) get_array_value($top_category, 'count') + (int) get_array_value($top_category, 'overdue');
            if ($top_total >= 5) {
                $alerts[] = array(
                    'severity' => 'informative',
                    'icon' => 'layers',
                    'title' => app_lang('organizador_alert_category_backlog_title'),
                    'message' => sprintf(app_lang('organizador_alert_category_backlog_message'), get_array_value($top_category, 'label'), $top_total),
                    'count' => $top_total,
                    'filter' => 'procrastination',
                );
            }
        }

        return array_slice($alerts, 0, 5);
    }

    private function build_dashboard_suggestions($summary, $category_counts, $urgent_not_started, $procrastination_risks, $forgotten_tasks)
    {
        $suggestions = array();

        if (!empty($urgent_not_started)) {
            $suggestions[] = app_lang('organizador_suggestion_urgent_not_started');
        }
        if ((int) $summary['overdue'] > 0) {
            $suggestions[] = app_lang('organizador_suggestion_overdue');
        }
        if ((int) $summary['in_progress_old'] > 0) {
            $suggestions[] = app_lang('organizador_suggestion_in_progress_old');
        }
        if (!empty($category_counts)) {
            $top_category = reset($category_counts);
            $top_total = (int) get_array_value($top_category, 'count') + (int) get_array_value($top_category, 'overdue');
            if ($top_total >= 5) {
                $suggestions[] = sprintf(app_lang('organizador_suggestion_category_backlog'), get_array_value($top_category, 'label'));
            }
        }
        if (!empty($procrastination_risks)) {
            $suggestions[] = app_lang('organizador_suggestion_procrastination');
        }
        if (!empty($forgotten_tasks)) {
            $suggestions[] = app_lang('organizador_suggestion_forgotten');
        }

        if (empty($suggestions)) {
            $suggestions[] = app_lang('organizador_suggestion_controlled');
        }

        return array_values(array_unique($suggestions));
    }

    private function build_attention_line($summary)
    {
        $score = ((int) $summary['overdue'] * 3)
            + ((int) $summary['urgent'] * 2)
            + ((int) $summary['procrastination'] * 2)
            + ((int) $summary['no_start'])
            + ((int) $summary['forgotten'] * 2);

        if ($score >= 18) {
            return array(
                'label' => app_lang('organizador_dashboard_attention_critical'),
                'severity' => 'critical',
                'score' => $score,
                'message' => app_lang('organizador_dashboard_attention_critical_message'),
            );
        }

        if ($score >= 10) {
            return array(
                'label' => app_lang('organizador_dashboard_attention_risk'),
                'severity' => 'danger',
                'score' => $score,
                'message' => app_lang('organizador_dashboard_attention_risk_message'),
            );
        }

        if ($score >= 5) {
            return array(
                'label' => app_lang('organizador_dashboard_attention_moderate'),
                'severity' => 'attention',
                'score' => $score,
                'message' => app_lang('organizador_dashboard_attention_moderate_message'),
            );
        }

        return array(
            'label' => app_lang('organizador_dashboard_attention_controlled'),
            'severity' => 'success',
            'score' => $score,
            'message' => app_lang('organizador_dashboard_attention_controlled_message'),
        );
    }
}
