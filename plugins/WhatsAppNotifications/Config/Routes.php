<?php

namespace Config;

$routes = Services::routes();

$routes->get('whatsapp_notifications_settings', 'WhatsAppNotificationsSettings::index', ['namespace' => 'WhatsAppNotifications\Controllers']);
$routes->post('whatsapp_notifications_settings/save', 'WhatsAppNotificationsSettings::save', ['namespace' => 'WhatsAppNotifications\Controllers']);
$routes->post('whatsapp_notifications_settings/connect_session', 'WhatsAppNotificationsSettings::connect_session', ['namespace' => 'WhatsAppNotifications\Controllers']);
$routes->get('whatsapp_notifications_settings/session_status', 'WhatsAppNotificationsSettings::session_status', ['namespace' => 'WhatsAppNotifications\Controllers']);
$routes->get('whatsapp_notifications_settings/session_qr', 'WhatsAppNotificationsSettings::session_qr', ['namespace' => 'WhatsAppNotifications\Controllers']);
$routes->post('whatsapp_notifications_settings/disconnect_session', 'WhatsAppNotificationsSettings::disconnect_session', ['namespace' => 'WhatsAppNotifications\Controllers']);
