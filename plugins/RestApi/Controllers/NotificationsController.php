<?php

namespace RestApi\Controllers;

use App\Models\Notifications_model;
use App\Models\Users_model;

#[\AllowDynamicProperties]
class NotificationsController extends ModuleApiController
{
    protected Notifications_model $notificationsModel;
    protected Users_model $usersModel;

    public function __construct()
    {
        parent::__construct();
        helper('notifications');

        $this->notificationsModel = model('App\Models\Notifications_model');
        $this->usersModel = model('App\Models\Users_model');
    }

    public function index()
    {
        $user = $this->getCurrentStaffUser();
        if (!$user) {
            return $this->respondForbidden();
        }

        $limit = $this->normalizeLimit($this->request->getGet('limit'));
        $offset = $this->normalizeOffset($this->request->getGet('offset'), $this->request->getGet('cursor'), $limit);
        $unreadOnly = $this->toBool($this->request->getGet('unread'));

        $result = $this->notificationsModel->get_api_notifications($user->id, $offset, $limit, $unreadOnly);
        $items = array();

        foreach (($result->result ?? array()) as $notification) {
            $items[] = $this->formatNotification($notification);
        }

        return $this->respond(array(
            'status' => true,
            'resource' => 'notifications',
            'count' => (int) ($result->found_rows ?? 0),
            'data' => $items,
            'unread_count' => $this->notificationsModel->count_unread_notifications($user->id),
            'limit' => $limit,
            'offset' => $offset,
            'next_cursor' => $offset + count($items),
            'has_more' => (($offset + count($items)) < (int) ($result->found_rows ?? 0)),
        ));
    }

    public function unreadCount()
    {
        $user = $this->getCurrentStaffUser();
        if (!$user) {
            return $this->respondForbidden();
        }

        return $this->respond(array(
            'status' => true,
            'resource' => 'notifications_unread_count',
            'data' => array(
                'count' => $this->notificationsModel->count_unread_notifications($user->id),
            ),
        ));
    }

    public function read($id = 0)
    {
        $user = $this->getCurrentStaffUser();
        if (!$user) {
            return $this->respondForbidden();
        }

        $notification = $this->notificationsModel->get_api_notification((int) $id, $user->id);
        if (!$notification) {
            return $this->respondNotFound('Notification not found.');
        }

        $this->notificationsModel->set_notification_status_as_read((int) $id, $user->id);

        return $this->respond(array(
            'status' => true,
            'resource' => 'notification_read',
            'data' => array(
                'notification' => $this->formatNotification($notification),
                'unread_count' => $this->notificationsModel->count_unread_notifications($user->id),
            ),
        ));
    }

    public function readAll()
    {
        $user = $this->getCurrentStaffUser();
        if (!$user) {
            return $this->respondForbidden();
        }

        $this->notificationsModel->set_notification_status_as_read(0, $user->id);

        return $this->respond(array(
            'status' => true,
            'resource' => 'notifications_read_all',
            'data' => array(
                'unread_count' => 0,
            ),
        ));
    }

    protected function getCurrentStaffUser()
    {
        $apiUser = $this->api_user ?? null;
        $email = strtolower(trim((string) ($apiUser->user ?? '')));
        if ($email === '') {
            return null;
        }

        return $this->usersModel->get_one_where(array(
            'email' => $email,
            'deleted' => 0,
            'status' => 'active',
            'disable_login' => 0,
            'user_type' => 'staff',
        ));
    }

    protected function formatNotification(object $notification): array
    {
        $notificationId = (int) ($notification->id ?? 0);
        $event = (string) ($notification->event ?? '');
        $urlAttributes = function_exists('get_notification_url_attributes') ? get_notification_url_attributes($notification) : array();
        $link = get_array_value($urlAttributes, 'url');
        if (!$link) {
            $link = get_array_value($urlAttributes, 'app_modal_url');
        }
        if (!$link) {
            $link = get_array_value($urlAttributes, 'ajax_modal_url');
        }

        $title = $this->resolveNotificationTitle($notification);
        $message = $this->resolveNotificationMessage($notification);

        return array(
            'id' => $notificationId,
            'type' => $this->resolveNotificationType($event),
            'category' => $this->resolveNotificationCategory($notification),
            'title' => $title,
            'message' => $message,
            'link' => $link ?: null,
            'entity' => $this->resolveNotificationEntity($notification),
            'read' => !empty($notification->is_read),
            'created_at' => $this->formatDateTimeValue($notification->created_at ?? ''),
            'created_at_utc' => trim((string) ($notification->created_at ?? '')) ?: null,
            'read_at' => null,
            'expires_at' => null,
            'priority' => $this->resolveNotificationPriority($event),
            'origin_user_id' => isset($notification->user_id) ? (int) $notification->user_id : null,
            'source' => 'system',
        );
    }

    protected function resolveNotificationTitle(object $notification): string
    {
        $event = (string) ($notification->event ?? '');
        $translationKey = 'notification_' . $event;
        $translated = app_lang($translationKey);

        if ($translated && $translated !== $translationKey) {
            $toUserName = trim((string) ($notification->to_user_name ?? ''));
            if (strpos($translated, '%') !== false) {
                try {
                    return trim((string) vsprintf($translated, array($toUserName ?: $this->getFallbackNotificationName($notification))));
                } catch (\Throwable $e) {
                    return trim($translated);
                }
            }

            return trim($translated);
        }

        return $this->getFallbackNotificationName($notification);
    }

    protected function resolveNotificationMessage(object $notification): string
    {
        try {
            $html = view('notifications/notification_description', array(
                'notification' => $notification,
            ));
            $message = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));
            if ($message !== '') {
                return html_entity_decode($message, ENT_QUOTES, 'UTF-8');
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }

        $parts = array();
        foreach (array('project_title', 'task_title', 'ticket_title', 'announcement_title', 'expense_title', 'event_title', 'company_name', 'lead_company_name') as $field) {
            $value = trim((string) ($notification->{$field} ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return $parts ? implode(' - ', $parts) : trim((string) ($notification->event ?? ''));
    }

    protected function resolveNotificationEntity(object $notification): array
    {
        $map = array(
            'announcement_id' => 'announcement',
            'project_id' => 'project',
            'task_id' => 'task',
            'ticket_id' => 'ticket',
            'expense_id' => 'expense',
            'leave_id' => 'leave',
            'invoice_id' => 'invoice',
            'proposal_id' => 'proposal',
            'estimate_id' => 'estimate',
            'order_id' => 'order',
            'contract_id' => 'contract',
            'event_id' => 'event',
            'post_id' => 'post',
            'client_id' => 'client',
            'lead_id' => 'lead',
            'subscription_id' => 'subscription',
        );

        foreach ($map as $field => $type) {
            if (!empty($notification->{$field})) {
                return array(
                    'type' => $type,
                    'id' => (int) $notification->{$field},
                );
            }
        }

        return array(
            'type' => 'notification',
            'id' => (int) ($notification->id ?? 0),
        );
    }

    protected function resolveNotificationCategory(object $notification): string
    {
        $event = strtolower(trim((string) ($notification->event ?? '')));

        if (str_contains($event, 'announcement')) {
            return 'aviso';
        }
        if (str_contains($event, 'leave') || str_contains($event, 'event')) {
            return 'agenda';
        }
        if (str_contains($event, 'expense')) {
            return 'despesa';
        }
        if (str_contains($event, 'timesheet') || str_contains($event, 'time')) {
            return 'timesheet';
        }
        if (str_contains($event, 'project') || str_contains($event, 'task')) {
            return 'projeto';
        }
        if (str_contains($event, 'invoice') || str_contains($event, 'proposal') || str_contains($event, 'estimate') || str_contains($event, 'order') || str_contains($event, 'contract')) {
            return 'pendencia';
        }
        if (str_contains($event, 'ticket') || str_contains($event, 'message') || str_contains($event, 'post') || str_contains($event, 'comment')) {
            return 'sistema';
        }

        return 'sistema';
    }

    protected function resolveNotificationType(string $event): string
    {
        $event = strtolower($event);
        foreach (array('rejected', 'failed', 'overdue', 'expired', 'blocked') as $needle) {
            if (str_contains($event, $needle)) {
                return 'error';
            }
        }

        foreach (array('approved', 'completed', 'success', 'done') as $needle) {
            if (str_contains($event, $needle)) {
                return 'success';
            }
        }

        foreach (array('reminder', 'pending', 'soon', 'due') as $needle) {
            if (str_contains($event, $needle)) {
                return 'warning';
            }
        }

        return 'info';
    }

    protected function resolveNotificationPriority(string $event): string
    {
        $type = $this->resolveNotificationType($event);
        return $type === 'error' ? 'high' : 'normal';
    }

    protected function getFallbackNotificationName(object $notification): string
    {
        foreach (array('announcement_title', 'project_title', 'task_title', 'ticket_title', 'expense_title', 'event_title', 'subscription_title') as $field) {
            $value = trim((string) ($notification->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $event = trim((string) ($notification->event ?? ''));
        if ($event !== '') {
            return ucwords(str_replace(array('_', '-'), ' ', $event));
        }

        return 'Notification';
    }

    protected function formatDateTimeValue($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return convert_date_utc_to_local($value, 'Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    protected function normalizeLimit($value): int
    {
        $limit = (int) $value;
        if ($limit <= 0) {
            $limit = 20;
        }

        return min(100, $limit);
    }

    protected function normalizeOffset($offsetValue, $cursorValue, int $limit): int
    {
        if ($cursorValue !== null && $cursorValue !== '') {
            $cursor = (int) $cursorValue;
            if ($cursor >= 0) {
                return $cursor;
            }
        }

        $offset = (int) $offsetValue;
        if ($offset > 0) {
            return $offset;
        }

        $page = (int) $this->request->getGet('page');
        if ($page > 1) {
            return ($page - 1) * $limit;
        }

        return 0;
    }

    protected function respondForbidden()
    {
        return $this->respond(array(
            'status' => false,
            'message' => 'Forbidden.',
        ), 403);
    }

    protected function respondNotFound(string $message)
    {
        return $this->respond(array(
            'status' => false,
            'message' => $message,
        ), 404);
    }
}
