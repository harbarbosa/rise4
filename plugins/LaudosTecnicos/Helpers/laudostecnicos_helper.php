<?php

if (!function_exists('get_current_utc_time')) {
    function get_current_utc_time($format = 'Y-m-d H:i:s')
    {
        $timestamp = time();
        if ($format === null || $format === '') {
            $format = 'Y-m-d H:i:s';
        }

        return gmdate($format, $timestamp);
    }
}

if (!function_exists('laudostecnicos_safe_json')) {
    function laudostecnicos_safe_json($value)
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '[]' : $json;
    }
}

if (!function_exists('laudostecnicos_permission_keys')) {
    function laudostecnicos_permission_keys()
    {
        return array(
            'laudostecnicos_access',
            'laudostecnicos_view_dashboard',
            'laudostecnicos_view_laudos',
            'laudostecnicos_create_laudos',
            'laudostecnicos_edit_laudos',
            'laudostecnicos_change_status',
            'laudostecnicos_delete_drafts',
            'laudostecnicos_view_inspections',
            'laudostecnicos_manage_categories',
            'laudostecnicos_manage_types',
            'laudostecnicos_manage_statuses',
            'laudostecnicos_manage_transitions',
            'laudostecnicos_manage_templates',
            'laudostecnicos_manage_checklists',
            'laudostecnicos_manage_measurements',
            'laudostecnicos_manage_equipments',
            'laudostecnicos_manage_norms',
            'laudostecnicos_manage_inspections',
            'laudostecnicos_manage_nonconformities',
            'laudostecnicos_manage_risk_matrix',
            'laudostecnicos_manage_action_plans',
            'laudostecnicos_manage_settings',
            'laudostecnicos_manage_api',
            'laudostecnicos_manage_ai',
            'laudostecnicos_view_reports',
            'laudostecnicos_manage_reports',
            'laudostecnicos_manage_automations',
        );
    }
}

if (!function_exists('laudostecnicos_default_settings')) {
    function laudostecnicos_default_settings()
    {
        return array(
            'module_name' => 'Laudos Tecnicos',
            'laudo_prefix' => 'LT-',
            'numbering_format' => '{PREFIX}{SEQ}',
            'next_number' => '1',
            'sequence_padding' => '6',
            'logo_path' => '',
            'main_color' => '#0d6efd',
            'pdf_font_family' => 'helvetica',
            'pdf_margin_top' => '14',
            'pdf_margin_bottom' => '16',
            'pdf_margin_left' => '12',
            'pdf_margin_right' => '12',
            'pdf_header_text' => 'Laudos Tecnicos',
            'pdf_footer_text' => 'Documento controlado por versao',
            'pdf_watermark_text' => 'CONFIDENCIAL',
            'pdf_confidentiality_text' => 'A distribuicao deste documento depende de autorizacao.',
            'pdf_cover_enabled' => '1',
            'pdf_paper' => 'A4',
            'pdf_orientation' => 'P',
            'pdf_enable_qr' => '1',
            'portal_enabled' => '1',
            'public_validation_enabled' => '1',
            'default_document_variant' => 'full',
            'api_require_https' => '0',
            'api_rate_limit_per_minute' => '60',
            'api_access_token_lifetime' => '7200',
            'api_refresh_token_lifetime' => '2592000',
            'api_cors_origins' => '*',
            'ai_provider' => '',
            'ai_endpoint_url' => '',
            'ai_api_token' => '',
            'ai_model' => '',
            'ai_temperature' => '0.2',
            'ai_token_limit' => '1500',
            'ai_timeout' => '60',
            'ai_prompt_template' => '',
            'ai_allowed_resources' => 'objective,scope,methodology,diagnosis,conclusion,recommendations,executive_summary',
            'ai_user_limit' => '0',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'portuguese',
            'date_format' => 'd/m/Y',
            'module_enabled' => '1',
            'detailed_logs' => '1',
            'default_status' => 'draft',
            'default_priority' => 'normal',
            'default_template_id' => '',
        );
    }
}

if (!function_exists('laudostecnicos_status_labels')) {
    function laudostecnicos_status_labels()
    {
        return array(
            'draft' => 'Rascunho',
            'received' => 'Solicitacao recebida',
            'scheduled' => 'Visita agendada',
            'inspection' => 'Em inspeccao',
            'collection_completed' => 'Coleta concluida',
            'drafting' => 'Em elaboracao',
            'awaiting_information' => 'Aguardando informacoes',
            'awaiting_review' => 'Aguardando revisao',
            'in_correction' => 'Em correcao',
            'awaiting_approval' => 'Aguardando aprovacao',
            'approved' => 'Aprovado',
            'signed' => 'Assinado',
            'issued' => 'Emitido',
            'sent_to_client' => 'Enviado ao cliente',
            'accepted_by_client' => 'Aceito pelo cliente',
            'rejected' => 'Reprovado',
            'overdue' => 'Vencido',
            'replaced' => 'Substituido',
            'canceled' => 'Cancelado',
        );
    }
}

if (!function_exists('laudostecnicos_dashboard_status_cards')) {
    function laudostecnicos_dashboard_status_cards(array $stats)
    {
        return array(
            array('key' => 'total', 'title' => app_lang('laudostecnicos_total_laudos'), 'value' => (int) get_array_value($stats, 'total'), 'class' => 'bg-primary text-white'),
            array('key' => 'draft', 'title' => app_lang('laudostecnicos_status_draft'), 'value' => (int) get_array_value($stats, 'draft'), 'class' => 'bg-secondary text-white'),
            array('key' => 'drafting', 'title' => app_lang('laudostecnicos_status_drafting'), 'value' => (int) get_array_value($stats, 'drafting'), 'class' => 'bg-info text-white'),
            array('key' => 'awaiting_review', 'title' => app_lang('laudostecnicos_status_awaiting_review'), 'value' => (int) get_array_value($stats, 'awaiting_review'), 'class' => 'bg-warning text-dark'),
            array('key' => 'approved', 'title' => app_lang('laudostecnicos_status_approved'), 'value' => (int) get_array_value($stats, 'approved'), 'class' => 'bg-success text-white'),
            array('key' => 'issued', 'title' => app_lang('laudostecnicos_status_issued'), 'value' => (int) get_array_value($stats, 'issued'), 'class' => 'bg-dark text-white'),
            array('key' => 'overdue', 'title' => app_lang('laudostecnicos_status_overdue'), 'value' => (int) get_array_value($stats, 'overdue'), 'class' => 'bg-danger text-white'),
            array('key' => 'canceled', 'title' => app_lang('laudostecnicos_status_canceled'), 'value' => (int) get_array_value($stats, 'canceled'), 'class' => 'bg-light text-dark'),
        );
    }
}

if (!function_exists('laudostecnicos_nonconformity_status_labels')) {
    function laudostecnicos_nonconformity_status_labels()
    {
        return array(
            'open' => 'Aberta',
            'analysis' => 'Em analise',
            'awaiting_correction' => 'Aguardando correcao',
            'in_correction' => 'Em correcao',
            'corrected' => 'Corrigida',
            'awaiting_validation' => 'Aguardando validacao',
            'validated' => 'Validada',
            'rejected' => 'Rejeitada',
            'canceled' => 'Cancelada',
        );
    }
}

if (!function_exists('laudostecnicos_risk_classification_options')) {
    function laudostecnicos_risk_classification_options()
    {
        return array(
            'observacao' => 'Observacao',
            'oportunidade_melhoria' => 'Oportunidade de melhoria',
            'baixa' => 'Baixa',
            'moderada' => 'Moderada',
            'alta' => 'Alta',
            'critica' => 'Critica',
            'emergencial' => 'Emergencial',
        );
    }
}

if (!function_exists('laudostecnicos_risk_palette')) {
    function laudostecnicos_risk_palette()
    {
        return array(
            'observacao' => '#6c757d',
            'oportunidade_melhoria' => '#0dcaf0',
            'baixa' => '#198754',
            'moderada' => '#ffc107',
            'alta' => '#fd7e14',
            'critica' => '#dc3545',
            'emergencial' => '#7a1f1f',
        );
    }
}

if (!function_exists('laudostecnicos_action_plan_status_labels')) {
    function laudostecnicos_action_plan_status_labels()
    {
        return array(
            'draft' => 'Rascunho',
            'open' => 'Aberto',
            'in_progress' => 'Em andamento',
            'waiting' => 'Aguardando',
            'done' => 'Concluido',
            'validated' => 'Validado',
            'canceled' => 'Cancelado',
        );
    }
}

if (!function_exists('laudostecnicos_nc_should_create_auto')) {
    function laudostecnicos_nc_should_create_auto(array $payload): bool
    {
        return (bool) get_array_value($payload, 'auto_create');
    }
}

if (!function_exists('laudostecnicos_create_nonconformity_from_event')) {
    function laudostecnicos_create_nonconformity_from_event(array $payload)
    {
        try {
            $model = model('LaudosTecnicos\\Models\\LaudoNonconformities_model');
            if (!$model) {
                return false;
            }

            return $model->create_automatic($payload);
        } catch (\Throwable $e) {
            log_message('error', '[LaudosTecnicos] Auto nonconformity error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('laudostecnicos_sync_action_plan_task')) {
    function laudostecnicos_sync_action_plan_task(array $payload)
    {
        try {
            $model = model('LaudosTecnicos\\Models\\LaudoActionPlans_model');
            if (!$model) {
                return false;
            }

            return $model->sync_task_from_plan($payload);
        } catch (\Throwable $e) {
            log_message('error', '[LaudosTecnicos] Action plan task sync error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('laudostecnicos_generate_token')) {
    function laudostecnicos_generate_token($length = 16)
    {
        $length = max(8, (int) $length);
        try {
            return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
        } catch (\Throwable $e) {
            return substr(sha1(uniqid((string) mt_rand(), true) . microtime(true)), 0, $length);
        }
    }
}

if (!function_exists('laudostecnicos_generate_qr_svg_data_uri')) {
    function laudostecnicos_generate_qr_svg_data_uri(string $content, int $size = 4): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        if (!class_exists('TCPDF2DBarcode')) {
            require_once APPPATH . 'ThirdParty/tcpdf/tcpdf_barcodes_2d.php';
        }

        try {
            $barcode = new \TCPDF2DBarcode($content, 'QRCODE,H');
            $svg = $barcode->getBarcodeSVG($size, $size, '#111111');
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            log_message('error', '[LaudosTecnicos] QR generation error: ' . $e->getMessage());
            return '';
        }
    }
}

if (!function_exists('laudostecnicos_normalize_document_variant')) {
    function laudostecnicos_normalize_document_variant(string $variant = 'full'): string
    {
        $variant = trim(strtolower($variant));
        $allowed = array('full', 'executive', 'photo', 'nc', 'action-plan', 'acceptance', 'certificate');
        return in_array($variant, $allowed, true) ? $variant : 'full';
    }
}

if (!function_exists('laudostecnicos_document_variant_titles')) {
    function laudostecnicos_document_variant_titles(): array
    {
        return array(
            'full' => 'Laudo completo',
            'executive' => 'Resumo executivo',
            'photo' => 'Relatorio fotografico',
            'nc' => 'Lista de nao conformidades',
            'action-plan' => 'Plano de acao',
            'acceptance' => 'Termo de aceite',
            'certificate' => 'Certificado',
        );
    }
}

if (!function_exists('laudostecnicos_document_storage_path')) {
    function laudostecnicos_document_storage_path(): string
    {
        $path = rtrim(WRITEPATH, "\\/") . DIRECTORY_SEPARATOR . 'laudostecnicos' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR;
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        return $path;
    }
}

if (!function_exists('laudostecnicos_safe_html')) {
    function laudostecnicos_safe_html($value = ''): string
    {
        return nl2br(esc((string) $value));
    }
}

if (!function_exists('laudostecnicos_render_document_html')) {
    function laudostecnicos_render_document_html(array $view_data = array()): string
    {
        $view_data['variant_titles'] = laudostecnicos_document_variant_titles();
        $view_data['settings'] = get_array_value($view_data, 'settings') ?: array();
        $view_data['model_info'] = get_array_value($view_data, 'model_info') ?: (object) array();
        $view_data['document_version'] = get_array_value($view_data, 'document_version') ?: null;
        return view('LaudosTecnicos\\Views\\laudos\\document', $view_data);
    }
}

if (!function_exists('laudostecnicos_public_validation_url')) {
    function laudostecnicos_public_validation_url($laudo_id = 0, $public_key = '')
    {
        $laudo_id = (int) $laudo_id;
        $public_key = trim((string) $public_key);
        if (!$laudo_id || $public_key === '') {
            return '';
        }

        return get_uri('laudostecnicos/laudos/public/' . $laudo_id . '/' . $public_key);
    }
}

if (!function_exists('laudostecnicos_build_document_code')) {
    function laudostecnicos_build_document_code($laudo_number = '', $revision = '00', $version = 1): string
    {
        $laudo_number = trim((string) $laudo_number);
        $revision = trim((string) $revision);
        $version = max(1, (int) $version);
        return strtoupper($laudo_number ?: 'LT') . '-R' . str_pad($revision !== '' ? $revision : '00', 2, '0', STR_PAD_LEFT) . '-V' . str_pad((string) $version, 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('laudostecnicos_ai_config')) {
    function laudostecnicos_ai_config(): array
    {
        return array(
            'provider' => trim((string) get_setting('ai_provider')),
            'endpoint_url' => trim((string) get_setting('ai_endpoint_url')),
            'api_token' => trim((string) get_setting('ai_api_token')),
            'model' => trim((string) get_setting('ai_model')),
            'temperature' => (float) get_setting('ai_temperature'),
            'token_limit' => (int) get_setting('ai_token_limit'),
            'timeout' => max(5, (int) get_setting('ai_timeout')),
            'prompt_template' => trim((string) get_setting('ai_prompt_template')),
            'allowed_resources' => array_values(array_filter(array_map('trim', explode(',', (string) get_setting('ai_allowed_resources'))))),
            'user_limit' => (int) get_setting('ai_user_limit'),
        );
    }
}

if (!function_exists('laudostecnicos_render_ai_prompt')) {
    function laudostecnicos_render_ai_prompt(string $template, array $context = array()): string
    {
        $replacements = array(
            '{{cliente}}' => trim((string) get_array_value($context, 'cliente')),
            '{{tipo_laudo}}' => trim((string) get_array_value($context, 'tipo_laudo')),
            '{{objetivo}}' => trim((string) get_array_value($context, 'objetivo')),
            '{{escopo}}' => trim((string) get_array_value($context, 'escopo')),
            '{{anotacoes_campo}}' => trim((string) get_array_value($context, 'anotacoes_campo')),
            '{{checklists}}' => trim((string) get_array_value($context, 'checklists')),
            '{{medicoes}}' => trim((string) get_array_value($context, 'medicoes')),
            '{{nao_conformidades}}' => trim((string) get_array_value($context, 'nao_conformidades')),
            '{{fotografias}}' => trim((string) get_array_value($context, 'fotografias')),
            '{{normas}}' => trim((string) get_array_value($context, 'normas')),
            '{{responsavel_tecnico}}' => trim((string) get_array_value($context, 'responsavel_tecnico')),
        );

        return strtr($template, $replacements);
    }
}
