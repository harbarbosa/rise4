<?php

namespace LaudosTecnicos\Controllers;

class Settings extends LaudosTecnicos_Base_Controller
{
    public function index()
    {
        $this->ensureSettingsAccess();

        return $this->template->rander('LaudosTecnicos\\Views\\settings\\index', array(
            'settings' => $this->settings_model->get_all_settings_with_defaults(),
            'can_manage_settings' => \LaudosTecnicos\Plugin::canManageSettings($this->login_user),
        ));
    }

    public function save()
    {
        $this->ensureSettingsAccess();

        $payload = array(
            'module_name' => trim((string) $this->request->getPost('module_name')),
            'laudo_prefix' => trim((string) $this->request->getPost('laudo_prefix')),
            'numbering_format' => trim((string) $this->request->getPost('numbering_format')),
            'next_number' => (string) max(1, (int) $this->request->getPost('next_number')),
            'sequence_padding' => (string) max(1, (int) $this->request->getPost('sequence_padding')),
            'logo_path' => trim((string) $this->request->getPost('logo_path')),
            'main_color' => trim((string) $this->request->getPost('main_color')),
            'pdf_font_family' => trim((string) $this->request->getPost('pdf_font_family')),
            'pdf_margin_top' => (string) max(0, (int) $this->request->getPost('pdf_margin_top')),
            'pdf_margin_bottom' => (string) max(0, (int) $this->request->getPost('pdf_margin_bottom')),
            'pdf_margin_left' => (string) max(0, (int) $this->request->getPost('pdf_margin_left')),
            'pdf_margin_right' => (string) max(0, (int) $this->request->getPost('pdf_margin_right')),
            'pdf_header_text' => trim((string) $this->request->getPost('pdf_header_text')),
            'pdf_footer_text' => trim((string) $this->request->getPost('pdf_footer_text')),
            'pdf_watermark_text' => trim((string) $this->request->getPost('pdf_watermark_text')),
            'pdf_confidentiality_text' => trim((string) $this->request->getPost('pdf_confidentiality_text')),
            'pdf_cover_enabled' => $this->request->getPost('pdf_cover_enabled') ? '1' : '0',
            'pdf_paper' => trim((string) $this->request->getPost('pdf_paper')),
            'pdf_orientation' => trim((string) $this->request->getPost('pdf_orientation')),
            'pdf_enable_qr' => $this->request->getPost('pdf_enable_qr') ? '1' : '0',
            'portal_enabled' => $this->request->getPost('portal_enabled') ? '1' : '0',
            'public_validation_enabled' => $this->request->getPost('public_validation_enabled') ? '1' : '0',
            'default_document_variant' => trim((string) $this->request->getPost('default_document_variant')),
            'timezone' => trim((string) $this->request->getPost('timezone')),
            'language' => trim((string) $this->request->getPost('language')),
            'date_format' => trim((string) $this->request->getPost('date_format')),
            'module_enabled' => $this->request->getPost('module_enabled') ? '1' : '0',
            'detailed_logs' => $this->request->getPost('detailed_logs') ? '1' : '0',
            'default_status' => trim((string) $this->request->getPost('default_status')),
            'default_priority' => trim((string) $this->request->getPost('default_priority')),
            'default_template_id' => (string) max(0, (int) $this->request->getPost('default_template_id')),
            'api_require_https' => $this->request->getPost('api_require_https') ? '1' : '0',
            'api_rate_limit_per_minute' => (string) max(1, (int) $this->request->getPost('api_rate_limit_per_minute')),
            'api_access_token_lifetime' => (string) max(600, (int) $this->request->getPost('api_access_token_lifetime')),
            'api_refresh_token_lifetime' => (string) max(600, (int) $this->request->getPost('api_refresh_token_lifetime')),
            'api_cors_origins' => trim((string) $this->request->getPost('api_cors_origins')),
        );

        foreach ($payload as $key => $value) {
            $this->settings_model->save_setting($key, $value);
        }

        $this->logAudit('settings', 0, 'update', 'Module settings updated', array(), $payload);

        echo json_encode(array(
            'success' => true,
            'message' => app_lang('record_saved'),
        ));
        exit;
    }
}
