<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: WhatsAppNotifications
  Description: Envia notificacoes do RISE via WhatsApp usando o fluxo nativo de notificacoes.
  Version: 0.1.0
  Requires at least: 3.0
*/

if (!defined('WHATSAPP_NOTIFICATIONS_MODULE')) {
    define('WHATSAPP_NOTIFICATIONS_MODULE', 'WhatsAppNotifications');
}

app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu["plugins"][] = array(
        "name" => "whatsapp_notifications",
        "url" => "whatsapp_notifications_settings"
    );

    return $settings_menu;
});

app_hooks()->add_action('app_hook_post_notification', function ($notification_id) {
    try {
        if (!(int) $notification_id || !get_setting("whatsapp.enabled")) {
            return;
        }

        $service = new \WhatsAppNotifications\Libraries\WhatsAppNotificationsService();
        $service->send_for_notification((int) $notification_id);
    } catch (\Throwable $e) {
        log_message('error', '[WhatsAppNotifications] Post notification hook failed: ' . $e->getMessage());
    }
});

register_installation_hook(WHATSAPP_NOTIFICATIONS_MODULE, function () {
    require __DIR__ . '/install.php';
});

register_update_hook(WHATSAPP_NOTIFICATIONS_MODULE, function () {
    require __DIR__ . '/install.php';
});
