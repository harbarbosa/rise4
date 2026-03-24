<?php

namespace WhatsAppNotifications\Libraries;

class WhatsAppNotificationsService
{
    protected $db;
    protected $Notifications_model;
    protected $timeout = 15;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->Notifications_model = model('App\\Models\\Notifications_model');
    }

    public function send_for_notification(int $notification_id): bool
    {
        $api_url = $this->get_message_api_url();
        $token = $this->get_api_token();
        $school_id = $this->get_school_id();

        if (!$api_url || !$token || !$school_id) {
            log_message('error', '[WhatsAppNotifications] Missing WhatsApp configuration.');
            return false;
        }

        $notification = $this->Notifications_model->get_email_notification($notification_id);
        if (!$notification) {
            return false;
        }

        $recipient_ids = $this->extract_recipient_ids($notification);
        if (!$recipient_ids) {
            return false;
        }

        $message = $this->build_message($notification);
        if (!$message) {
            return false;
        }

        $users = $this->get_users($recipient_ids);
        if (!$users) {
            return false;
        }

        $sent_phones = array();
        $sent = false;

        foreach ($users as $user) {
            $phone = $this->extract_phone($user);
            if (!$phone || isset($sent_phones[$phone])) {
                continue;
            }

            $sent_phones[$phone] = true;
            if ($this->dispatch_message($api_url, $token, $school_id, $phone, $message, $notification_id, $user->id)) {
                $sent = true;
            }
        }

        return $sent;
    }

    public function connect_session(): array
    {
        return $this->send_gateway_request('POST', $this->build_session_url('connect'));
    }

    public function get_session_status(): array
    {
        return $this->send_gateway_request('GET', $this->build_session_url('status'));
    }

    public function get_session_qr(): array
    {
        return $this->send_gateway_request('GET', $this->build_session_url('qr'));
    }

    public function disconnect_session(): array
    {
        return $this->send_gateway_request('POST', $this->build_session_url('disconnect'));
    }

    protected function extract_recipient_ids($notification): array
    {
        $recipient_ids = array();

        if (!empty($notification->notify_to)) {
            foreach (explode(",", $notification->notify_to) as $id) {
                $id = (int) trim($id);
                if ($id) {
                    $recipient_ids[$id] = $id;
                }
            }
        }

        if (!empty($notification->to_user_id)) {
            $recipient_ids[(int) $notification->to_user_id] = (int) $notification->to_user_id;
        }

        return array_values($recipient_ids);
    }

    protected function get_users(array $recipient_ids): array
    {
        if (!$recipient_ids) {
            return array();
        }

        $users_table = $this->db->prefixTable('users');

        return $this->db->table($users_table)
            ->select('id, first_name, last_name, phone, whatsapp, status, deleted')
            ->whereIn('id', $recipient_ids)
            ->where('deleted', 0)
            ->where('status', 'active')
            ->get()
            ->getResult();
    }

    protected function build_message($notification): string
    {
        $title = app_lang("notification_" . $notification->event);
        if (strpos($title, '%') !== false) {
            $title = sprintf($title, $notification->to_user_name ?: "");
        }

        $description_html = view("notifications/notification_description", array(
            "notification" => $notification,
            "changes_array" => array()
        ));

        $description_text = $this->html_to_text($description_html);

        $info = get_notification_config($notification->event, "info", $notification);
        $url = is_array($info) ? get_array_value($info, "url") : "";

        $parts = array_filter(array(
            $this->clean_text($title),
            $description_text,
            $url
        ));

        return trim(implode("\n", $parts));
    }

    protected function html_to_text(string $html): string
    {
        $html = preg_replace('#<a.*?>(.*?)</a>#i', '$1', $html);
        $html = preg_replace('#</div>#i', "\n", $html);
        $html = preg_replace('#</li>#i', "\n", $html);
        $html = preg_replace('#<br\s*/?>#i', "\n", $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        return $this->clean_text($text, true);
    }

    protected function clean_text(string $text, bool $preserve_lines = false): string
    {
        $text = str_replace(array("\r\n", "\r"), "\n", $text);

        if ($preserve_lines) {
            $lines = array();
            foreach (explode("\n", $text) as $line) {
                $line = trim(preg_replace('/\s+/u', ' ', $line));
                if ($line !== '') {
                    $lines[] = $line;
                }
            }

            return implode("\n", $lines);
        }

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    protected function extract_phone($user): string
    {
        $candidate = trim((string) ($user->whatsapp ?: $user->phone));
        if (!$candidate) {
            return "";
        }

        $decoded = urldecode($candidate);

        if (preg_match('~wa\.me/([0-9]+)~i', $decoded, $matches)) {
            return $matches[1];
        }

        if (preg_match('~phone=([0-9]+)~i', $decoded, $matches)) {
            return $matches[1];
        }

        return preg_replace('/\D+/', '', $decoded);
    }

    protected function dispatch_message(string $api_url, string $token, int $school_id, string $phone, string $message, int $notification_id, int $user_id): bool
    {
        $result = $this->send_gateway_request('POST', $api_url, array(
            'school_id' => $school_id,
            'phone' => $phone,
            'message' => $message
        ));

        if (get_array_value($result, 'success')) {
            return true;
        }

        log_message(
            'error',
            '[WhatsAppNotifications] Gateway error. Notification ID: ' . $notification_id .
            ', User ID: ' . $user_id .
            ', HTTP: ' . get_array_value($result, 'status_code') .
            ', Response: ' . json_encode(get_array_value($result, 'data'))
        );

        return false;
    }

    protected function get_message_api_url(): string
    {
        return rtrim(trim((string) get_setting("whatsapp.apiUrl")), '/');
    }

    protected function get_api_token(): string
    {
        return trim((string) get_setting("whatsapp.token"));
    }

    protected function get_school_id(): int
    {
        return (int) (get_setting("whatsapp.id") ?: 260687);
    }

    protected function get_gateway_base_url(): string
    {
        $api_url = $this->get_message_api_url();
        if (!$api_url) {
            return "";
        }

        if (substr($api_url, -18) === '/api/messages/send') {
            return substr($api_url, 0, -18);
        }

        return $api_url;
    }

    protected function build_session_url(string $action): string
    {
        $base_url = $this->get_gateway_base_url();
        $school_id = $this->get_school_id();

        if (!$base_url || !$school_id) {
            return "";
        }

        return rtrim($base_url, '/') . '/api/sessions/' . $school_id . '/' . $action;
    }

    protected function send_gateway_request(string $method, string $url, ?array $payload = null): array
    {
        $token = $this->get_api_token();
        if (!$url || !$token) {
            return array(
                'success' => false,
                'status_code' => 500,
                'data' => array(
                    'success' => false,
                    'error' => 'Configuracao do gateway incompleta.'
                )
            );
        }

        try {
            $client = \Config\Services::curlrequest(array(
                'timeout' => $this->timeout,
                'http_errors' => false,
                'headers' => array(
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                )
            ));

            $options = array();
            if ($payload !== null) {
                $options['json'] = $payload;
            }

            $response = $client->request($method, $url, $options);
            $status_code = $response->getStatusCode();
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if (!is_array($decoded)) {
                $decoded = array(
                    'success' => $status_code >= 200 && $status_code < 300,
                    'raw' => $body
                );
            }

            return array(
                'success' => $status_code >= 200 && $status_code < 300 && get_array_value($decoded, 'success') !== false,
                'status_code' => $status_code,
                'data' => $decoded
            );
        } catch (\Throwable $e) {
            return array(
                'success' => false,
                'status_code' => 500,
                'data' => array(
                    'success' => false,
                    'error' => $e->getMessage()
                )
            );
        }
    }
}
