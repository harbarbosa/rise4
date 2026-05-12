<?php

use GED\Libraries\GedExpirationService;

if (!function_exists('ged_expiration_service')) {
    function ged_expiration_service()
    {
        static $service = null;
        if ($service === null) {
            $service = new GedExpirationService();
        }

        return $service;
    }
}

if (!function_exists('get_days_to_expire')) {
    function get_days_to_expire($expiration_date)
    {
        return ged_expiration_service()->getDaysToExpire($expiration_date);
    }
}

if (!function_exists('is_expired')) {
    function is_expired($expiration_date)
    {
        return ged_expiration_service()->isExpired($expiration_date);
    }
}

if (!function_exists('is_expiring')) {
    function is_expiring($expiration_date, $days = 30)
    {
        return ged_expiration_service()->isExpiring($expiration_date, $days);
    }
}

if (!function_exists('get_expiration_status')) {
    function get_expiration_status($expiration_date)
    {
        return ged_expiration_service()->getExpirationStatus($expiration_date);
    }
}

if (!function_exists('get_expiration_badge')) {
    function get_expiration_badge($expiration_date)
    {
        return ged_expiration_service()->getExpirationBadge($expiration_date);
    }
}

if (!function_exists('get_document_status_label')) {
    function get_document_status_label($status)
    {
        return ged_expiration_service()->getDocumentStatusLabel($status);
    }
}

if (!function_exists('get_portal_status_label')) {
    function get_portal_status_label($portal_status)
    {
        return ged_expiration_service()->getPortalStatusLabel($portal_status);
    }
}
