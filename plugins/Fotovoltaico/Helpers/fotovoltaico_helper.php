<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

use Fotovoltaico\Plugin;

if (!function_exists('fotovoltaico_login_user')) {
    function fotovoltaico_login_user($login_user = null)
    {
        if ($login_user) {
            return $login_user;
        }

        $ci = new \App\Controllers\Security_Controller(false);
        return $ci->login_user ?? null;
    }
}

if (!function_exists('fotovoltaico_can_access_module')) {
    function fotovoltaico_can_access_module($login_user = null)
    {
        return Plugin::canAccessModule(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_view_dashboard')) {
    function fotovoltaico_can_view_dashboard($login_user = null)
    {
        return Plugin::canViewDashboard(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_manage_products')) {
    function fotovoltaico_can_manage_products($login_user = null)
    {
        return Plugin::canManageProducts(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_view_products')) {
    function fotovoltaico_can_view_products($login_user = null)
    {
        return Plugin::canViewProducts(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_manage_kits')) {
    function fotovoltaico_can_manage_kits($login_user = null)
    {
        return Plugin::canManageKits(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_view_kits')) {
    function fotovoltaico_can_view_kits($login_user = null)
    {
        return Plugin::canViewKits(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_create_proposals')) {
    function fotovoltaico_can_create_proposals($login_user = null)
    {
        return Plugin::canCreateProposals(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_manage_proposals')) {
    function fotovoltaico_can_manage_proposals($login_user = null)
    {
        return Plugin::canManageProposals(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_approve_proposals')) {
    function fotovoltaico_can_approve_proposals($login_user = null)
    {
        return Plugin::canApproveProposals(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_view_tariffs')) {
    function fotovoltaico_can_view_tariffs($login_user = null)
    {
        return Plugin::canViewTariffs(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_manage_tariffs')) {
    function fotovoltaico_can_manage_tariffs($login_user = null)
    {
        return Plugin::canManageTariffs(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_view_integrations')) {
    function fotovoltaico_can_view_integrations($login_user = null)
    {
        return Plugin::canViewIntegrations(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_manage_integrations')) {
    function fotovoltaico_can_manage_integrations($login_user = null)
    {
        return Plugin::canManageIntegrations(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_generate_pdf')) {
    function fotovoltaico_can_generate_pdf($login_user = null)
    {
        return Plugin::canGeneratePdf(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_view_audit')) {
    function fotovoltaico_can_view_audit($login_user = null)
    {
        return Plugin::canViewAudit(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_can_manage_settings')) {
    function fotovoltaico_can_manage_settings($login_user = null)
    {
        return Plugin::canManageSettings(fotovoltaico_login_user($login_user));
    }
}

if (!function_exists('fotovoltaico_proposal_status_label')) {
    function fotovoltaico_proposal_status_label($status = '')
    {
        $map = array(
            'draft' => app_lang('fotovoltaico_proposal_status_draft'),
            'in_progress' => app_lang('fotovoltaico_proposal_status_in_progress'),
            'sent' => app_lang('fotovoltaico_proposal_status_sent'),
            'reviewed' => app_lang('fotovoltaico_proposal_status_reviewed'),
            'approved' => app_lang('fotovoltaico_proposal_status_approved'),
            'lost' => app_lang('fotovoltaico_proposal_status_lost'),
            'canceled' => app_lang('fotovoltaico_proposal_status_canceled'),
        );

        return get_array_value($map, $status) ?: $status;
    }
}

if (!function_exists('fotovoltaico_proposal_status_badge')) {
    function fotovoltaico_proposal_status_badge($status = '')
    {
        $class = 'bg-secondary';
        if ($status === 'in_progress') {
            $class = 'bg-info';
        } else if ($status === 'sent') {
            $class = 'bg-primary';
        } else if ($status === 'reviewed') {
            $class = 'bg-warning text-dark';
        } else if ($status === 'approved') {
            $class = 'bg-success';
        } else if ($status === 'lost' || $status === 'canceled') {
            $class = 'bg-danger';
        }

        return "<span class='badge $class'>" . esc(fotovoltaico_proposal_status_label($status)) . "</span>";
    }
}
