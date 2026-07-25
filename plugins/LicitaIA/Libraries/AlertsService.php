<?php

namespace LicitaIA\Libraries;

class AlertsService
{
    private $alertSettings = array();
    private $Opportunity_model;
    private $Alert_log_model;
    private $Settings_model;
    private $Users_model;
    private $Notifications_model;

    public function __construct()
    {
        helper(array('general'));

        $this->Opportunity_model = model('LicitaIA\\Models\\Opportunity_model');
        $this->Alert_log_model = model('LicitaIA\\Models\\Alert_log_model');
        $this->Settings_model = model('LicitaIA\\Models\\Licitaia_settings_model');
        $this->Users_model = model('App\\Models\\Users_model');
        $this->Notifications_model = model('App\\Models\\Notifications_model');
    }

    public function run($options = array())
    {
        $result = array(
            'success' => true,
            'processed_opportunities' => 0,
            'sent_alerts' => 0,
            'skipped_alerts' => 0,
            'messages' => array(),
        );

        $settings = $this->Settings_model->get_alert_settings();
        $this->alertSettings = $settings;
        if (!((string) get_array_value($settings, 'alerts_enabled', '1') === '1')) {
            $result['messages'][] = app_lang('licitaia_alerts_disabled');
            return $result;
        }

        $recipient_ids = $this->resolveRecipientIds($settings);
        if (!count($recipient_ids)) {
            $result['messages'][] = app_lang('licitaia_alerts_no_recipients');
            return $result;
        }

        $recipient_users = $this->getRecipientUsers($recipient_ids);
        $today = get_today_date();

        $opening_days = $this->parseDaysList(get_array_value($settings, 'alerts_days_before_opening', '7,3,1'));
        $submission_days = $this->parseDaysList(get_array_value($settings, 'alerts_days_before_submission', '7,3,1'));

        $this->processNewImportedOpportunities($recipient_users, $today, $result);
        $this->processDueSoonOpportunities($recipient_users, $opening_days, 'opening_soon', 'opening_date', $result);
        $this->processDueSoonOpportunities($recipient_users, $submission_days, 'submission_soon', 'submission_deadline', $result);
        $this->processMissingResponsible($recipient_users, $result);
        $this->processMissingAiAnalysis($recipient_users, $result);
        $this->processPendingChecklist($recipient_users, $result);
        $this->processParticipateWithoutProposal($recipient_users, $result);

        return $result;
    }

    private function processNewImportedOpportunities($recipient_users, $today, &$result)
    {
        $opportunities = $this->Opportunity_model->get_imported_today();
        foreach ($opportunities as $opportunity) {
            $result['processed_opportunities']++;
            $this->dispatchAlert('new_imported', 'new_imported', $opportunity, $recipient_users, $today, $result, array(
                'title' => app_lang('licitaia_alert_type_new_imported'),
            ));
        }
    }

    private function processDueSoonOpportunities($recipient_users, $days_list, $alert_type, $date_field, &$result)
    {
        if (!count($days_list)) {
            return;
        }

        $max_days = max($days_list);
        $method = $date_field === 'opening_date' ? 'get_opening_due_soon' : 'get_submission_due_soon';
        $opportunities = $this->Opportunity_model->{$method}($max_days);

        foreach ($opportunities as $opportunity) {
            $result['processed_opportunities']++;
            $date_value = trim((string) ($opportunity->{$date_field} ?? ''));
            $days_remaining = $this->daysUntil($date_value);
            if ($days_remaining === null || !in_array($days_remaining, $days_list, true)) {
                continue;
            }

            $this->dispatchAlert($alert_type, $alert_type . '_' . $days_remaining, $opportunity, $recipient_users, get_today_date(), $result, array(
                'days_remaining' => $days_remaining,
                'date_field' => $date_field,
                'title' => app_lang('licitaia_alert_type_' . $alert_type),
            ));
        }
    }

    private function processMissingResponsible($recipient_users, &$result)
    {
        $opportunities = $this->Opportunity_model->get_without_responsible();
        foreach ($opportunities as $opportunity) {
            $result['processed_opportunities']++;
            $this->dispatchAlert('no_responsible', 'no_responsible', $opportunity, $recipient_users, get_today_date(), $result, array(
                'title' => app_lang('licitaia_alert_type_no_responsible'),
            ));
        }
    }

    private function processMissingAiAnalysis($recipient_users, &$result)
    {
        $opportunities = $this->Opportunity_model->get_without_ai_analysis();
        foreach ($opportunities as $opportunity) {
            $result['processed_opportunities']++;
            $this->dispatchAlert('no_ai_analysis', 'no_ai_analysis', $opportunity, $recipient_users, get_today_date(), $result, array(
                'title' => app_lang('licitaia_alert_type_no_ai_analysis'),
            ));
        }
    }

    private function processPendingChecklist($recipient_users, &$result)
    {
        $opportunities = $this->Opportunity_model->get_with_pending_checklist();
        foreach ($opportunities as $opportunity) {
            $result['processed_opportunities']++;
            $this->dispatchAlert('pending_checklist', 'pending_checklist', $opportunity, $recipient_users, get_today_date(), $result, array(
                'title' => app_lang('licitaia_alert_type_pending_checklist'),
            ));
        }
    }

    private function processParticipateWithoutProposal($recipient_users, &$result)
    {
        $opportunities = $this->Opportunity_model->get_participate_without_proposal();
        foreach ($opportunities as $opportunity) {
            $result['processed_opportunities']++;
            $this->dispatchAlert('participate_without_proposal', 'participate_without_proposal', $opportunity, $recipient_users, get_today_date(), $result, array(
                'title' => app_lang('licitaia_alert_type_participate_without_proposal'),
            ));
        }
    }

    private function dispatchAlert($alert_type, $alert_key, $opportunity, $recipient_users, $alert_date, &$result, $context = array())
    {
        $opportunity = is_array($opportunity) ? (object) $opportunity : $opportunity;
        if (!$opportunity || empty($opportunity->id)) {
            return;
        }

        $message = $this->buildMessage($alert_type, $opportunity, $context);
        $link_url = get_uri('licitaia/opportunities/view/' . $opportunity->id);
        $subject = $this->buildSubject($alert_type, $opportunity);
        $payload = array(
            'alert_type' => $alert_type,
            'alert_key' => $alert_key,
            'opportunity_id' => (int) $opportunity->id,
            'opportunity_title' => $opportunity->title ?? '',
            'link_url' => $link_url,
            'message' => $message,
            'context' => $context,
        );

        foreach ($recipient_users as $recipient) {
            if (!$recipient || empty($recipient->id)) {
                continue;
            }

            $recipient_id = (int) $recipient->id;
            if ($this->Alert_log_model->has_sent_today($alert_key, $opportunity->id, $recipient_id, $alert_date)) {
                $result['skipped_alerts']++;
                continue;
            }

            $can_email = $this->canSendEmailToRecipient($recipient);
            $email_sent = false;
            $whatsapp_prepared = false;

            $notification_data = array(
                'user_id' => 0,
                'description' => '',
                'created_at' => get_current_utc_time(),
                'notify_to' => (string) $recipient_id,
                'read_by' => '',
                'event' => 'licitaia_alert',
                'to_user_id' => $recipient_id,
                'plugin_alert_type' => $alert_type,
                'plugin_opportunity_id' => (int) $opportunity->id,
                'plugin_alert_key' => $alert_key,
                'plugin_link_url' => $link_url,
                'plugin_message' => $message,
                'plugin_payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'plugin_channel_web' => 1,
                'plugin_channel_email' => $can_email ? 1 : 0,
                'plugin_channel_whatsapp' => 0,
            );
            $notification_id = $this->Notifications_model->ci_save($notification_data);

            if ($notification_id) {
                if ($can_email) {
                    $email_sent = $this->sendEmailToRecipient($recipient, $subject, $message, $link_url);
                }
            }

            if ($notification_id && $this->shouldPrepareWhatsapp()) {
                $whatsapp_prepared = true;
            }

            $this->Alert_log_model->log_alert(array(
                'alert_type' => $alert_type,
                'alert_key' => $alert_key,
                'opportunity_id' => (int) $opportunity->id,
                'recipient_user_id' => $recipient_id,
                'notification_id' => $notification_id ?: null,
                'alert_date' => $alert_date,
                'channel_web' => 1,
                'channel_email' => $email_sent ? 1 : 0,
                'channel_whatsapp' => $whatsapp_prepared ? 1 : 0,
                'status' => $notification_id ? 'completed' : 'failed',
                'subject' => $subject,
                'message' => $message,
                'payload_json' => $payload,
                'sent_at' => get_my_local_time(),
            ));

            $result['sent_alerts']++;
        }
    }

    private function buildSubject($alert_type, $opportunity)
    {
        $alert_label = app_lang('licitaia_alert_type_' . $alert_type);
        $title = $opportunity->title ?? '';
        return trim('LicitaIA - ' . $alert_label . ($title ? ' - ' . $title : ''));
    }

    private function buildMessage($alert_type, $opportunity, $context = array())
    {
        $lines = array();
        $lines[] = '<p><strong>' . esc(app_lang('licitaia_alert_type_' . $alert_type)) . '</strong></p>';
        $lines[] = '<p><strong>' . esc(app_lang('licitaia_opportunity')) . ':</strong> #' . (int) $opportunity->id . ($opportunity->title ? ' - ' . esc($opportunity->title) : '') . '</p>';

        if (!empty($opportunity->public_agency)) {
            $lines[] = '<p><strong>' . esc(app_lang('licitaia_public_body')) . ':</strong> ' . esc($opportunity->public_agency) . '</p>';
        }

        if (!empty($opportunity->notice_number)) {
            $lines[] = '<p><strong>' . esc(app_lang('licitaia_edital_number')) . ':</strong> ' . esc($opportunity->notice_number) . '</p>';
        }

        if (!empty($opportunity->process_number)) {
            $lines[] = '<p><strong>' . esc(app_lang('licitaia_process_number')) . ':</strong> ' . esc($opportunity->process_number) . '</p>';
        }

        if (!empty($opportunity->opening_date)) {
            $lines[] = '<p><strong>' . esc(app_lang('licitaia_opening_date')) . ':</strong> ' . esc(format_to_date($opportunity->opening_date, false)) . '</p>';
        }

        if (!empty($opportunity->submission_deadline)) {
            $lines[] = '<p><strong>' . esc(app_lang('licitaia_deadline')) . ':</strong> ' . esc(format_to_date($opportunity->submission_deadline, false)) . '</p>';
        }

        if (isset($context['days_remaining'])) {
            $lines[] = '<p><strong>' . esc(app_lang('licitaia_alert_days_remaining')) . ':</strong> ' . (int) $context['days_remaining'] . '</p>';
        }

        $lines[] = '<p><a href="' . esc(get_uri('licitaia/opportunities/view/' . $opportunity->id)) . '" target="_blank">' . esc(app_lang('licitaia_alert_view_opportunity')) . '</a></p>';

        return implode('', $lines);
    }

    private function sendEmailToRecipient($recipient, $subject, $message, $link_url)
    {
        if (!$this->canSendEmailToRecipient($recipient)) {
            return false;
        }

        $body = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6">'
            . $message
            . '<p><a href="' . esc($link_url) . '" target="_blank">' . esc(app_lang('licitaia_alert_view_opportunity')) . '</a></p>'
            . '</div>';

        return send_app_mail($recipient->email, $subject, $body);
    }

    private function canSendEmailToRecipient($recipient)
    {
        if ((string) get_array_value($this->alertSettings, 'alerts_email_enabled', '1') !== '1') {
            return false;
        }

        if (empty($recipient->email) || empty($recipient->enable_email_notification)) {
            return false;
        }

        return true;
    }

    private function shouldPrepareWhatsapp()
    {
        return (string) get_array_value($this->alertSettings, 'alerts_whatsapp_enabled', '0') === '1';
    }

    private function resolveRecipientIds($settings)
    {
        $raw_ids = trim((string) get_array_value($settings, 'alerts_recipient_user_ids', ''));
        $recipient_ids = array();

        if ($raw_ids !== '') {
            foreach (explode(',', $raw_ids) as $id) {
                if (is_numeric($id)) {
                    $recipient_ids[] = (int) $id;
                }
            }
        }

        if (!count($recipient_ids)) {
            $admins = $this->Users_model->get_all_where(array(
                'deleted' => 0,
                'status' => 'active',
                'user_type' => 'staff',
                'is_admin' => 1,
            ))->getResult();

            foreach ($admins as $admin) {
                $recipient_ids[] = (int) $admin->id;
            }
        }

        return array_values(array_unique(array_filter($recipient_ids)));
    }

    private function getRecipientUsers($recipient_ids)
    {
        if (!count($recipient_ids)) {
            return array();
        }

        return $this->Users_model->get_all_where(array(
            'deleted' => 0,
            'status' => 'active',
            'user_type' => 'staff',
            'where_in' => array('id' => $recipient_ids),
        ))->getResult();
    }

    private function parseDaysList($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return array();
        }

        $days = array();
        foreach (explode(',', $value) as $item) {
            $item = trim($item);
            if ($item !== '' && is_numeric($item)) {
                $days[] = (int) $item;
            }
        }

        $days = array_values(array_unique($days));
        sort($days);
        return $days;
    }

    private function daysUntil($date_value)
    {
        $timestamp = $this->parseDateToTimestamp($date_value);
        if (!$timestamp) {
            return null;
        }

        $today = strtotime(get_today_date());
        return (int) floor(($timestamp - $today) / 86400);
    }

    private function parseDateToTimestamp($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return $timestamp;
        }

        foreach (array('Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d') as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime) {
                return $date->getTimestamp();
            }
        }

        return null;
    }
}
