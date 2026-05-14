<?php

namespace GED\Libraries;

class GedNotificationService
{
    private $Documents_model;
    private $Submissions_model;
    private $Notification_logs_model;
    private $Settings_model;
    private $Users_model;

    public function __construct()
    {
        helper(array('ged_expiration', 'notifications'));

        $this->Documents_model = model('GED\\Models\\Ged_documents_model');
        $this->Submissions_model = model('GED\\Models\\Ged_document_submissions_model');
        $this->Notification_logs_model = model('GED\\Models\\Ged_notification_logs_model');
        $this->Settings_model = model('GED\\Models\\Ged_settings_model');
        $this->Users_model = model('App\\Models\\Users_model');
    }

    public function run($options = array())
    {
        $result = array(
            'success' => true,
            'processed_documents' => 0,
            'processed_submissions' => 0,
            'sent_notifications' => 0,
            'skipped_notifications' => 0,
            'messages' => array(),
        );

        if (!$this->isEnabled()) {
            $result['messages'][] = 'GED notifications are disabled.';
            return $result;
        }

        $alert_days = $this->getAlertDays();
        if (!count($alert_days)) {
            $alert_days = array(30, 15, 7, 0);
        }

        $max_days = max($alert_days);
        $recipient_cache = $this->getRecipientCache();

        $documents = $this->Documents_model->get_notification_candidates($max_days);
        foreach ($documents as $document) {
            $result['processed_documents']++;
            $event_info = $this->resolveDocumentEvent($document, $alert_days);
            if (!$event_info) {
                continue;
            }

            $sent = $this->sendDocumentNotification($document, $event_info, $recipient_cache);
            $result['sent_notifications'] += $sent['sent'];
            $result['skipped_notifications'] += $sent['skipped'];
            $result['messages'] = array_merge($result['messages'], $sent['messages']);
        }

        $submissions = $this->Submissions_model->get_notification_candidates($max_days);
        foreach ($submissions as $submission) {
            $result['processed_submissions']++;
            $event_info = $this->resolveSubmissionEvent($submission, $alert_days);
            if (!$event_info) {
                continue;
            }

            $sent = $this->sendSubmissionNotification($submission, $event_info, $recipient_cache);
            $result['sent_notifications'] += $sent['sent'];
            $result['skipped_notifications'] += $sent['skipped'];
            $result['messages'] = array_merge($result['messages'], $sent['messages']);
        }

        return $result;
    }

    private function value($source, $key, $default = null)
    {
        if (is_array($source) && array_key_exists($key, $source)) {
            return $source[$key];
        }

        if (is_object($source) && isset($source->{$key})) {
            return $source->{$key};
        }

        return $default;
    }

    private function isEnabled()
    {
        return ged_setting_bool('enable_native_notifications', true);
    }

    private function getAlertDays()
    {
        return ged_setting_alert_days();
    }

    private function getRecipientCache()
    {
        $cache = array(
            'admins' => array(),
        );

        $admin_rows = $this->Users_model->get_all_where(array(
            'deleted' => 0,
            'status' => 'active',
            'user_type' => 'staff',
            'is_admin' => 1,
        ))->getResult();

        foreach ($admin_rows as $row) {
            $cache['admins'][] = (int) $row->id;
        }

        return $cache;
    }

    private function resolveDocumentEvent($document, $alert_days)
    {
        $status = get_expiration_status($document->expiration_date ?? null);
        $days_before = $this->daysBeforeFromStatus($status);

        if ($status === 'no_expiration' || $status === 'valid') {
            return null;
        }

        if ($status !== 'expired' && !in_array($days_before, $alert_days, true)) {
            return null;
        }

        return array(
            'event' => $this->documentEventForStatus($status),
            'days_before' => $days_before,
            'status' => $status,
        );
    }

    private function resolveSubmissionEvent($submission, $alert_days)
    {
        $status = get_expiration_status($submission->document_expiration_date ?? null);
        $days_before = $this->daysBeforeFromStatus($status);

        if ($status === 'no_expiration' || $status === 'valid') {
            return null;
        }

        if ($status !== 'expired' && !in_array($days_before, $alert_days, true)) {
            return null;
        }

        return array(
            'event' => $this->submissionEventForStatus($status),
            'days_before' => $days_before,
            'status' => $status,
        );
    }

    private function documentEventForStatus($status)
    {
        switch ($status) {
            case 'expiring_30':
                return 'ged_document_due_30';
            case 'expiring_15':
                return 'ged_document_due_15';
            case 'expiring_7':
                return 'ged_document_due_7';
            case 'expires_today':
                return 'ged_document_due_today';
            case 'expired':
                return 'ged_document_overdue';
            default:
                return '';
        }
    }

    private function submissionEventForStatus($status)
    {
        switch ($status) {
            case 'expiring_30':
                return 'ged_submission_due_30';
            case 'expiring_15':
                return 'ged_submission_due_15';
            case 'expiring_7':
                return 'ged_submission_due_7';
            case 'expires_today':
                return 'ged_submission_due_today';
            case 'expired':
                return 'ged_submission_overdue';
            default:
                return '';
        }
    }

    private function daysBeforeFromStatus($status)
    {
        switch ($status) {
            case 'expiring_30':
                return 30;
            case 'expiring_15':
                return 15;
            case 'expiring_7':
                return 7;
            case 'expires_today':
                return 0;
            case 'expired':
                return -1;
            default:
                return null;
        }
    }

    private function sendDocumentNotification($document, $event_info, $recipient_cache)
    {
        $result = array(
            'sent' => 0,
            'skipped' => 0,
            'messages' => array(),
        );

        $event = $this->value($event_info, 'event');
        if (!$event) {
            return $result;
        }

        $recipient_ids = $this->getDocumentRecipientIds($document, $recipient_cache);
        if (!count($recipient_ids)) {
            $result['messages'][] = 'No recipients found for document #' . (int) $document->id;
            return $result;
        }

        foreach ($recipient_ids as $recipient_id) {
            if ($this->Notification_logs_model->has_log($document->id, $event, $this->value($event_info, 'days_before'), $recipient_id, 0)) {
                $result['skipped']++;
                continue;
            }

            $payload = $this->buildDocumentPayload($document, $event_info, $recipient_id);
            log_notification($event, $payload, 0);

            $this->Notification_logs_model->add_log(array(
                'document_id' => $document->id,
                'submission_id' => null,
                'user_id' => $recipient_id,
                'notification_type' => $event,
                'days_before' => $this->value($event_info, 'days_before'),
                'sent_at' => get_my_local_time(),
                'created_at' => get_my_local_time(),
            ));

            $result['sent']++;
        }

        return $result;
    }

    private function sendSubmissionNotification($submission, $event_info, $recipient_cache)
    {
        $result = array(
            'sent' => 0,
            'skipped' => 0,
            'messages' => array(),
        );

        $event = $this->value($event_info, 'event');
        if (!$event) {
            return $result;
        }

        $recipient_ids = $this->getSubmissionRecipientIds($submission, $recipient_cache);
        if (!count($recipient_ids)) {
            $result['messages'][] = 'No recipients found for submission #' . (int) $submission->id;
            return $result;
        }

        foreach ($recipient_ids as $recipient_id) {
            if ($this->Notification_logs_model->has_log($submission->document_id, $event, $this->value($event_info, 'days_before'), $recipient_id, $submission->id)) {
                $result['skipped']++;
                continue;
            }

            $payload = $this->buildSubmissionPayload($submission, $event_info, $recipient_id);
            log_notification($event, $payload, 0);

            $this->Notification_logs_model->add_log(array(
                'document_id' => $submission->document_id,
                'submission_id' => $submission->id,
                'user_id' => $recipient_id,
                'notification_type' => $event,
                'days_before' => $this->value($event_info, 'days_before'),
                'sent_at' => get_my_local_time(),
                'created_at' => get_my_local_time(),
            ));

            $result['sent']++;
        }

        return $result;
    }

    private function getDocumentRecipientIds($document, $recipient_cache)
    {
        $recipient_ids = array();

        if (ged_setting_bool('notify_admins', true)) {
            $recipient_ids = array_merge($recipient_ids, $this->value($recipient_cache, 'admins', array()) ?: array());
        }

        if (ged_setting_bool('notify_document_creator', true) && !empty($document->created_by)) {
            $recipient_ids[] = (int) $document->created_by;
        }

        if (!empty($document->responsible_user_id)) {
            $recipient_ids[] = (int) $document->responsible_user_id;
        }

        return array_values(array_unique(array_filter($recipient_ids)));
    }

    private function getSubmissionRecipientIds($submission, $recipient_cache)
    {
        $recipient_ids = array();

        if (ged_setting_bool('notify_admins', true)) {
            $recipient_ids = array_merge($recipient_ids, $this->value($recipient_cache, 'admins', array()) ?: array());
        }

        $document_creator_id = (int) $this->value($submission, 'document_created_by', 0);
        if (ged_setting_bool('notify_document_creator', true) && $document_creator_id) {
            $recipient_ids[] = $document_creator_id;
        }

        if (!empty($submission->document_responsible_user_id)) {
            $recipient_ids[] = (int) $submission->document_responsible_user_id;
        }

        return array_values(array_unique(array_filter($recipient_ids)));
    }

    private function buildDocumentPayload($document, $event_info, $recipient_id)
    {
        return array(
            'plugin_document_id' => $document->id,
            'plugin_document_creator_id' => (int) ($document->created_by ?? 0),
            'plugin_document_title' => $document->title,
            'plugin_document_type_name' => $document->document_type_name ?? '',
            'plugin_document_status' => get_expiration_status($document->expiration_date ?? null),
            'plugin_expiration_date' => $document->expiration_date,
            'plugin_days_before' => $this->value($event_info, 'days_before'),
            'plugin_recipient_user_id' => $recipient_id,
            'plugin_link_url' => get_uri('ged/documents/view/' . $document->id),
        );
    }

    private function buildSubmissionPayload($submission, $event_info, $recipient_id)
    {
        return array(
            'plugin_document_id' => $submission->document_id,
            'plugin_submission_id' => $submission->id,
            'plugin_submission_creator_id' => (int) ($submission->created_by ?? 0),
            'plugin_document_title' => $submission->document_title,
            'plugin_document_type_name' => $submission->document_type_name ?? '',
            'plugin_document_status' => get_expiration_status($submission->document_expiration_date ?? null),
            'plugin_submission_status' => $submission->portal_status,
            'plugin_expiration_date' => $submission->document_expiration_date,
            'plugin_portal_reference' => $submission->portal_reference ?? ($submission->supplier_name ?? ''),
            'plugin_days_before' => $this->value($event_info, 'days_before'),
            'plugin_recipient_user_id' => $recipient_id,
            'plugin_link_url' => get_uri('ged/submissions/view/' . $submission->id),
        );
    }
}
