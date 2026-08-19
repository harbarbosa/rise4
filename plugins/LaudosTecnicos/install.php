<?php

namespace LaudosTecnicos\install;

function laudostecnicos_install()
{
    \LaudosTecnicos\Plugin::runMigrations();

    try {
        $db = db_connect('default');
        $settings_table = $db->prefixTable('laudo_settings');
        $categories_table = $db->prefixTable('laudo_categories');
        $types_table = $db->prefixTable('laudo_types');
        $statuses_table = $db->prefixTable('laudo_statuses');
        $transitions_table = $db->prefixTable('laudo_status_transitions');
        $templates_table = $db->prefixTable('laudo_templates');
        $checklists_table = $db->prefixTable('laudo_checklists');
        $measurement_types_table = $db->prefixTable('laudo_measurement_types');

        if (
            !$db->tableExists($settings_table) ||
            !$db->tableExists($categories_table) ||
            !$db->tableExists($types_table) ||
            !$db->tableExists($statuses_table) ||
            !$db->tableExists($transitions_table) ||
            !$db->tableExists($templates_table) ||
            !$db->tableExists($checklists_table) ||
            !$db->tableExists($measurement_types_table)
        ) {
            log_message('error', '[LaudosTecnicos] Install skipped because required tables are missing after migration run.');
            return true;
        }

        $now = \get_current_utc_time();

        $defaults = laudostecnicos_default_settings();
        foreach ($defaults as $key => $value) {
            $row = $db->table($settings_table)->where('setting_name', $key)->get()->getRow();
            if ($row) {
                continue;
            }

            $db->table($settings_table)->insert(array(
                'setting_name' => $key,
                'setting_value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }

        $seed_categories = array(
            array('name' => 'Engenharia eletrica', 'code' => 'ENG-ELE', 'description' => 'Categoria base para projetos eletricos', 'color' => '#0d6efd', 'icon' => 'zap', 'sort' => 1),
            array('name' => 'SPDA', 'code' => 'SPDA', 'description' => 'Sistemas de protecao contra descargas atmosfericas', 'color' => '#198754', 'icon' => 'shield', 'sort' => 2),
            array('name' => 'Seguranca eletronica', 'code' => 'SEG-EL', 'description' => 'CFTV, controle de acesso e alarmes', 'color' => '#6f42c1', 'icon' => 'camera', 'sort' => 3),
            array('name' => 'CFTV', 'code' => 'CFTV', 'description' => 'Circuito fechado de televisao', 'color' => '#6610f2', 'icon' => 'video', 'sort' => 4),
            array('name' => 'Controle de acesso', 'code' => 'CTRL-ACESSO', 'description' => 'Sistemas de acesso e portaria', 'color' => '#20c997', 'icon' => 'lock', 'sort' => 5),
            array('name' => 'Redes', 'code' => 'REDES', 'description' => 'Infraestrutura de dados e redes', 'color' => '#0dcaf0', 'icon' => 'wifi', 'sort' => 6),
            array('name' => 'Cabeamento estruturado', 'code' => 'CABEAMENTO', 'description' => 'Infraestrutura de cabeamento e pontos', 'color' => '#fd7e14', 'icon' => 'git-branch', 'sort' => 7),
            array('name' => 'Fibra optica', 'code' => 'FIBRA', 'description' => 'Enlaces e certificacao de fibra', 'color' => '#6c757d', 'icon' => 'radio', 'sort' => 8),
            array('name' => 'Energia fotovoltaica', 'code' => 'FV', 'description' => 'Sistemas solares fotovoltaicos', 'color' => '#ffc107', 'icon' => 'sun', 'sort' => 9),
            array('name' => 'Manutencao', 'code' => 'MANUT', 'description' => 'Manutencao preventiva e corretiva', 'color' => '#dc3545', 'icon' => 'tool', 'sort' => 10),
            array('name' => 'Inspecoes', 'code' => 'INSP', 'description' => 'Inspecoes tecnicas em campo', 'color' => '#343a40', 'icon' => 'search', 'sort' => 11),
            array('name' => 'Pareceres tecnicos', 'code' => 'PARECER', 'description' => 'Pareceres e laudos conclusivos', 'color' => '#198754', 'icon' => 'file-text', 'sort' => 12),
        );

        foreach ($seed_categories as $category) {
            $exists = $db->table($categories_table)->where('code', $category['code'])->where('deleted', 0)->get()->getRow();
            if ($exists) {
                continue;
            }

            $db->table($categories_table)->insert(array_merge($category, array(
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            )));
        }

        $seed_types = array(
            array('name' => 'Laudo de SPDA', 'code' => 'LT-SPDA', 'category_code' => 'SPDA', 'prefix' => 'SPDA-', 'validity_days' => 365),
            array('name' => 'Inspecao de SPDA', 'code' => 'INSP-SPDA', 'category_code' => 'SPDA', 'prefix' => 'INSP-SPDA-', 'validity_days' => 365),
            array('name' => 'Laudo de instalacoes eletricas', 'code' => 'LT-ELE', 'category_code' => 'ENG-ELE', 'prefix' => 'ELE-', 'validity_days' => 365),
            array('name' => 'Laudo de aterramento', 'code' => 'LT-ATERR', 'category_code' => 'ENG-ELE', 'prefix' => 'ATERR-', 'validity_days' => 365),
            array('name' => 'Laudo termografico', 'code' => 'LT-TERM', 'category_code' => 'ENG-ELE', 'prefix' => 'TERM-', 'validity_days' => 365),
            array('name' => 'Laudo de CFTV', 'code' => 'LT-CFTV', 'category_code' => 'CFTV', 'prefix' => 'CFTV-', 'validity_days' => 365),
            array('name' => 'Laudo de controle de acesso', 'code' => 'LT-ACESSO', 'category_code' => 'CTRL-ACESSO', 'prefix' => 'ACESSO-', 'validity_days' => 365),
            array('name' => 'Laudo de cabeamento estruturado', 'code' => 'LT-CABEAMENTO', 'category_code' => 'CABEAMENTO', 'prefix' => 'CAB-', 'validity_days' => 365),
            array('name' => 'Laudo de fibra optica', 'code' => 'LT-FIBRA', 'category_code' => 'FIBRA', 'prefix' => 'FIBRA-', 'validity_days' => 365),
            array('name' => 'Laudo de energia fotovoltaica', 'code' => 'LT-FV', 'category_code' => 'FV', 'prefix' => 'FV-', 'validity_days' => 365),
            array('name' => 'Relatorio de manutencao preventiva', 'code' => 'RMP', 'category_code' => 'MANUT', 'prefix' => 'RMP-', 'validity_days' => 365),
            array('name' => 'Relatorio de manutencao corretiva', 'code' => 'RMC', 'category_code' => 'MANUT', 'prefix' => 'RMC-', 'validity_days' => 365),
            array('name' => 'Relatorio fotografico', 'code' => 'RFOTO', 'category_code' => 'INSP', 'prefix' => 'RF-', 'validity_days' => 365),
            array('name' => 'Relatorio de visita tecnica', 'code' => 'RVT', 'category_code' => 'INSP', 'prefix' => 'RVT-', 'validity_days' => 365),
            array('name' => 'Parecer tecnico', 'code' => 'PTEC', 'category_code' => 'PARECER', 'prefix' => 'PTEC-', 'validity_days' => 365),
            array('name' => 'Termo de aceite', 'code' => 'TA', 'category_code' => 'PARECER', 'prefix' => 'TA-', 'validity_days' => 365),
            array('name' => 'Laudo de demonstracao', 'code' => 'LT-DEMO', 'category_code' => 'DEMO', 'prefix' => 'DEMO-', 'validity_days' => 180),
        );

        foreach ($seed_types as $type) {
            $exists = $db->table($types_table)->where('code', $type['code'])->where('deleted', 0)->get()->getRow();
            if ($exists) {
                continue;
            }

            $category_row = $db->table($categories_table)
                ->where('code', $type['category_code'])
                ->where('deleted', 0)
                ->get()
                ->getRow();

            $db->table($types_table)->insert(array(
                'name' => $type['name'],
                'code' => $type['code'],
                'category_id' => $category_row ? (int) $category_row->id : null,
                'description' => '',
                'prefix' => $type['prefix'],
                'default_template_id' => null,
                'validity_days' => (int) $type['validity_days'],
                'require_technical_responsible' => 1,
                'require_review' => 1,
                'require_approval' => 1,
                'require_signature' => 1,
                'require_inspection' => 1,
                'require_calibrated_equipment' => 0,
                'allow_mobile' => 1,
                'is_active' => 1,
                'sort' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            ));
        }

        $notification_settings_table = $db->prefixTable('notification_settings');
        if ($db->tableExists($notification_settings_table)) {
            $notification_events = array(
                'laudo_document_emitted',
                'laudo_document_sent',
                'laudo_document_viewed',
                'laudo_document_downloaded',
                'laudo_document_accepted',
                'laudo_document_rejected',
                'laudo_document_feedback_added',
                'laudo_document_link_expiring',
            );

            foreach ($notification_events as $index => $event) {
            $exists = $db->table($notification_settings_table)->where('event', $event)->where('deleted', 0)->get()->getRow();
                if ($exists) {
                    continue;
                }

                $db->table($notification_settings_table)->insert(array(
                    'event' => $event,
                    'category' => 'laudostecnicos',
                    'enable_email' => 1,
                    'enable_web' => 1,
                    'enable_slack' => 0,
                    'notify_to_team' => 1,
                    'notify_to_team_members' => '',
                    'notify_to_terms' => 'team_members,team',
                    'sort' => 900 + $index,
                    'deleted' => 0,
                ));
            }
        }

        $seed_checklists = array(
            array(
                'code' => 'CHK-SPDA-BASICO',
                'name' => 'Checklist de SPDA basico',
                'category_code' => 'SPDA',
                'type_code' => 'LT-SPDA',
                'description' => 'Checklist inicial para inspecoes de SPDA.',
                'version' => 1,
                'status' => 'published',
                'is_default' => 1,
                'structure' => array(
                    'groups' => array(
                        array('key' => 'visual', 'title' => 'Inspecao visual', 'description' => '', 'sort' => 1, 'active' => 1),
                        array('key' => 'medicoes', 'title' => 'Medições', 'description' => '', 'sort' => 2, 'active' => 1),
                    ),
                    'items' => array(
                        array('group_key' => 'visual', 'code' => 'VIS-001', 'question' => 'Captor e condutores estao fixados?', 'guidance' => 'Verificar fixacao e integridade.', 'response_type' => 'conforme', 'expected_response' => 'conforme', 'criticality' => 'media', 'weight' => 1, 'required' => 1, 'evidence_required' => 0, 'photo_required' => 1, 'measurement_required' => 0, 'observation_required' => 1, 'related_norm' => 'NBR 5419', 'generates_nc' => 1, 'sort' => 1, 'active' => 1),
                        array('group_key' => 'medicoes', 'code' => 'MED-001', 'question' => 'Resistencia de aterramento dentro do limite?', 'guidance' => 'Registrar medicao e comparar com o limite aplicavel.', 'response_type' => 'numero', 'expected_response' => 'ok', 'criticality' => 'alta', 'weight' => 3, 'required' => 1, 'evidence_required' => 1, 'photo_required' => 1, 'measurement_required' => 1, 'observation_required' => 1, 'related_norm' => 'NBR 5419', 'generates_nc' => 1, 'sort' => 2, 'active' => 1),
                    ),
                ),
            ),
        );

        foreach ($seed_checklists as $checklist) {
            $exists = $db->table($checklists_table)->where('code', $checklist['code'])->where('deleted', 0)->get()->getRow();
            if ($exists) {
                continue;
            }

            $type_row = $db->table($types_table)->where('code', $checklist['type_code'])->where('deleted', 0)->get()->getRow();
            $category_row = $db->table($categories_table)->where('code', $checklist['category_code'])->where('deleted', 0)->get()->getRow();

            $db->table($checklists_table)->insert(array(
                'name' => $checklist['name'],
                'code' => $checklist['code'],
                'category_id' => $category_row ? (int) $category_row->id : null,
                'type_id' => $type_row ? (int) $type_row->id : null,
                'description' => $checklist['description'],
                'version' => (int) $checklist['version'],
                'status' => $checklist['status'],
                'is_active' => 1,
                'is_default' => !empty($checklist['is_default']) ? 1 : 0,
                'responsible_id' => 1,
                'structure_json' => json_encode($checklist['structure'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'published_at' => $now,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            ));
        }

        $seed_measurement_types = array(
            array('name' => 'Resistencia de aterramento', 'quantity' => 'ohm', 'unit' => 'Ohm', 'min_value' => 0, 'max_value' => 10, 'reference_value' => 0, 'tolerance_value' => 2, 'decimal_places' => 2, 'auto_classification' => 1),
            array('name' => 'Tensao', 'quantity' => 'volt', 'unit' => 'V', 'min_value' => 110, 'max_value' => 127, 'reference_value' => 127, 'tolerance_value' => 10, 'decimal_places' => 2, 'auto_classification' => 1),
            array('name' => 'Corrente', 'quantity' => 'ampere', 'unit' => 'A', 'min_value' => 0, 'max_value' => 32, 'reference_value' => null, 'tolerance_value' => 0, 'decimal_places' => 2, 'auto_classification' => 1),
            array('name' => 'Temperatura', 'quantity' => 'celsius', 'unit' => 'C', 'min_value' => -10, 'max_value' => 60, 'reference_value' => null, 'tolerance_value' => 5, 'decimal_places' => 1, 'auto_classification' => 1),
        );

        foreach ($seed_measurement_types as $measurement_type) {
            $exists = $db->table($measurement_types_table)->where('name', $measurement_type['name'])->where('deleted', 0)->get()->getRow();
            if ($exists) {
                continue;
            }

            $db->table($measurement_types_table)->insert(array_merge($measurement_type, array(
                'description' => '',
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            )));
        }

        $seed_statuses = array(
            array('name' => 'Rascunho', 'code' => 'draft', 'sort' => 1, 'is_initial' => 1, 'allow_edit' => 1, 'allow_delete' => 1),
            array('name' => 'Solicitacao recebida', 'code' => 'received', 'sort' => 2),
            array('name' => 'Aguardando agendamento', 'code' => 'awaiting_schedule', 'sort' => 3),
            array('name' => 'Visita agendada', 'code' => 'scheduled', 'sort' => 4),
            array('name' => 'Em inspecao', 'code' => 'inspection', 'sort' => 5),
            array('name' => 'Coleta concluida', 'code' => 'collection_completed', 'sort' => 6),
            array('name' => 'Aguardando informacoes', 'code' => 'awaiting_information', 'sort' => 7),
            array('name' => 'Em elaboracao', 'code' => 'drafting', 'sort' => 8),
            array('name' => 'Aguardando revisao', 'code' => 'awaiting_review', 'sort' => 9),
            array('name' => 'Em correcao', 'code' => 'in_correction', 'sort' => 10),
            array('name' => 'Aguardando aprovacao', 'code' => 'awaiting_approval', 'sort' => 11),
            array('name' => 'Aprovado', 'code' => 'approved', 'sort' => 12),
            array('name' => 'Assinado', 'code' => 'signed', 'sort' => 13),
            array('name' => 'Emitido', 'code' => 'issued', 'sort' => 14, 'is_final' => 1),
            array('name' => 'Enviado ao cliente', 'code' => 'sent_to_client', 'sort' => 15),
            array('name' => 'Aceito pelo cliente', 'code' => 'accepted_by_client', 'sort' => 16, 'is_final' => 1),
            array('name' => 'Reprovado', 'code' => 'rejected', 'sort' => 17),
            array('name' => 'Vencido', 'code' => 'overdue', 'sort' => 18),
            array('name' => 'Substituido', 'code' => 'replaced', 'sort' => 19),
            array('name' => 'Cancelado', 'code' => 'canceled', 'sort' => 20, 'is_cancellation' => 1, 'is_final' => 1),
        );

        $status_colors = array(
            'draft' => '#6c757d',
            'received' => '#0dcaf0',
            'awaiting_schedule' => '#adb5bd',
            'scheduled' => '#20c997',
            'inspection' => '#0d6efd',
            'collection_completed' => '#198754',
            'awaiting_information' => '#fd7e14',
            'drafting' => '#6610f2',
            'awaiting_review' => '#ffc107',
            'in_correction' => '#dc3545',
            'awaiting_approval' => '#6f42c1',
            'approved' => '#198754',
            'signed' => '#0d6efd',
            'issued' => '#212529',
            'sent_to_client' => '#20c997',
            'accepted_by_client' => '#198754',
            'rejected' => '#dc3545',
            'overdue' => '#b02a37',
            'replaced' => '#6c757d',
            'canceled' => '#dc3545',
        );

        foreach ($seed_statuses as $status) {
            $exists = $db->table($statuses_table)->where('code', $status['code'])->where('deleted', 0)->get()->getRow();
            if ($exists) {
                continue;
            }

            $db->table($statuses_table)->insert(array(
                'name' => $status['name'],
                'code' => $status['code'],
                'color' => get_array_value($status_colors, $status['code']),
                'icon' => 'circle',
                'sort' => (int) $status['sort'],
                'status_initial' => !empty($status['is_initial']) ? 1 : 0,
                'status_final' => !empty($status['is_final']) ? 1 : 0,
                'status_cancellation' => !empty($status['is_cancellation']) ? 1 : 0,
                'allow_edit' => isset($status['allow_edit']) ? (int) $status['allow_edit'] : 1,
                'allow_delete' => isset($status['allow_delete']) ? (int) $status['allow_delete'] : 0,
                'allow_issue' => in_array($status['code'], array('approved', 'signed'), true) ? 1 : 0,
                'require_comment' => in_array($status['code'], array('in_correction', 'rejected', 'canceled'), true) ? 1 : 0,
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            ));
        }

        $seed_transitions = array(
            array('from_code' => 'draft', 'to_code' => 'received', 'sort' => 1),
            array('from_code' => 'scheduled', 'to_code' => 'inspection', 'sort' => 2),
            array('from_code' => 'drafting', 'to_code' => 'awaiting_review', 'sort' => 3),
            array('from_code' => 'awaiting_review', 'to_code' => 'in_correction', 'sort' => 4),
            array('from_code' => 'awaiting_review', 'to_code' => 'awaiting_approval', 'sort' => 5),
            array('from_code' => 'approved', 'to_code' => 'signed', 'sort' => 6),
            array('from_code' => 'signed', 'to_code' => 'issued', 'sort' => 7),
            array('from_code' => 'issued', 'to_code' => 'sent_to_client', 'sort' => 8),
        );

        foreach ($seed_transitions as $transition) {
            $exists = $db->table($transitions_table)
                ->where('from_status_code', $transition['from_code'])
                ->where('to_status_code', $transition['to_code'])
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if ($exists) {
                continue;
            }

            $db->table($transitions_table)->insert(array(
                'from_status_code' => $transition['from_code'],
                'to_status_code' => $transition['to_code'],
                'allowed_roles_json' => '[]',
                'required_permissions_json' => '[]',
                'require_comment' => in_array($transition['to_code'], array('in_correction', 'awaiting_approval'), true) ? 1 : 0,
                'required_validations_json' => '[]',
                'send_notification' => 1,
                'auto_create_task' => 0,
                'task_title' => '',
                'task_description' => '',
                'sort' => (int) $transition['sort'],
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            ));
        }

        $template_structure = array(
            'sections' => array(
                array('key' => 'capa', 'title' => 'Capa', 'description' => '', 'sort' => 1, 'page_break' => 0, 'numbering' => 0, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'identificacao', 'title' => 'Identificacao', 'description' => '', 'sort' => 2, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'dados_cliente', 'title' => 'Dados do cliente', 'description' => '', 'sort' => 3, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'objetivo', 'title' => 'Objetivo', 'description' => '', 'sort' => 4, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'escopo', 'title' => 'Escopo', 'description' => '', 'sort' => 5, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'metodologia', 'title' => 'Metodologia', 'description' => '', 'sort' => 6, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'conclusao', 'title' => 'Conclusao', 'description' => '', 'sort' => 7, 'page_break' => 0, 'numbering' => 1, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
                array('key' => 'assinaturas', 'title' => 'Assinaturas', 'description' => '', 'sort' => 8, 'page_break' => 0, 'numbering' => 0, 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'required' => 0, 'hidden' => 0),
            ),
            'fields' => array(
                array('key' => 'title', 'section_key' => 'identificacao', 'type' => 'text', 'name' => 'title', 'label' => 'Titulo', 'description' => '', 'placeholder' => 'Titulo do laudo', 'default_value' => '', 'required' => 1, 'sort' => 1, 'width' => '12', 'validation' => '', 'mask' => '', 'help' => '', 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'read_only' => 0, 'generated_ai' => 0),
                array('key' => 'objective', 'section_key' => 'objetivo', 'type' => 'text_long', 'name' => 'objective', 'label' => 'Objetivo', 'description' => '', 'placeholder' => '', 'default_value' => '', 'required' => 0, 'sort' => 2, 'width' => '12', 'validation' => '', 'mask' => '', 'help' => '', 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'read_only' => 0, 'generated_ai' => 0),
                array('key' => 'conclusion', 'section_key' => 'conclusao', 'type' => 'text_long', 'name' => 'conclusion', 'label' => 'Conclusao', 'description' => '', 'placeholder' => '', 'default_value' => '', 'required' => 0, 'sort' => 3, 'width' => '12', 'validation' => '', 'mask' => '', 'help' => '', 'visible_web' => 1, 'visible_mobile' => 1, 'visible_pdf' => 1, 'read_only' => 0, 'generated_ai' => 0),
            ),
            'rules' => array(
                array('name' => 'Foto quando nao conforme', 'trigger_field' => 'resultado', 'operator' => 'equals', 'trigger_value' => 'nao_conforme', 'action_type' => 'require_field', 'action_target' => 'foto', 'message' => '', 'sort' => 1, 'active' => 1),
            ),
        );

        $template_seeds = array(
            array('template_key' => 'TEMPLATE-GENERICO', 'name' => 'Template base de laudo', 'code' => 'TPL-GENERICO', 'type_code' => null, 'category_code' => null, 'version' => 1, 'status' => 'published', 'is_default' => 1),
            array('template_key' => 'TEMPLATE-SPDA', 'name' => 'Template base SPDA', 'code' => 'TPL-SPDA', 'type_code' => 'LT-SPDA', 'category_code' => 'SPDA', 'version' => 1, 'status' => 'published', 'is_default' => 0),
            array('template_key' => 'TEMPLATE-CFTV', 'name' => 'Template base CFTV', 'code' => 'TPL-CFTV', 'type_code' => 'LT-CFTV', 'category_code' => 'CFTV', 'version' => 1, 'status' => 'published', 'is_default' => 0),
            array('template_key' => 'TEMPLATE-PARECER', 'name' => 'Template base parecer tecnico', 'code' => 'TPL-PARECER', 'type_code' => 'PTEC', 'category_code' => 'PARECER', 'version' => 1, 'status' => 'published', 'is_default' => 0),
        );

        $template_ids = array();
        foreach ($template_seeds as $template) {
            $exists = $db->table($templates_table)
                ->where('template_key', $template['template_key'])
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if ($exists) {
                $template_ids[$template['template_key']] = (int) $exists->id;
                continue;
            }

            $db->table($templates_table)->insert(array(
                'template_key' => $template['template_key'],
                'name' => $template['name'],
                'code' => $template['code'],
                'description' => '',
                'type_id' => null,
                'category_id' => null,
                'version' => (int) $template['version'],
                'status' => $template['status'],
                'is_active' => 1,
                'is_default' => !empty($template['is_default']) ? 1 : 0,
                'structure_json' => json_encode($template_structure, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'published_at' => $now,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            ));
            $template_ids[$template['template_key']] = (int) $db->insertID();
        }

        $type_default_map = array(
            'LT-SPDA' => 'TEMPLATE-SPDA',
            'INSP-SPDA' => 'TEMPLATE-SPDA',
            'LT-CFTV' => 'TEMPLATE-CFTV',
            'PTEC' => 'TEMPLATE-PARECER',
            'TA' => 'TEMPLATE-PARECER',
            'LT-DEMO' => 'TEMPLATE-GENERICO',
        );

        $generic_template_id = get_array_value($template_ids, 'TEMPLATE-GENERICO');

        foreach ($seed_types as $type) {
            $type_row = $db->table($types_table)
                ->where('code', $type['code'])
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$type_row || !$type_row->id) {
                continue;
            }

            $template_key = get_array_value($type_default_map, $type['code']);
            $template_id = $template_key && isset($template_ids[$template_key]) ? (int) $template_ids[$template_key] : (int) $generic_template_id;

            $db->table($types_table)
                ->where('id', (int) $type_row->id)
                ->update(array(
                    'default_template_id' => $template_id,
                    'updated_at' => $now,
                ));
        }

        $demo_category_row = $db->table($categories_table)
            ->where('code', 'DEMO')
            ->where('deleted', 0)
            ->get()
            ->getRow();
        if (!$demo_category_row) {
            $db->table($categories_table)->insert(array(
                'name' => 'Demonstracao',
                'code' => 'DEMO',
                'description' => 'Categoria de exemplo para homologacao do plugin',
                'color' => '#6610f2',
                'icon' => 'box',
                'sort' => 999,
                'is_active' => 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            ));
            $demo_category_row = $db->table($categories_table)
                ->where('code', 'DEMO')
                ->where('deleted', 0)
                ->get()
                ->getRow();
        }

        $demo_type_row = $db->table($types_table)
            ->where('code', 'LT-DEMO')
            ->where('deleted', 0)
            ->get()
            ->getRow();
        if (!$demo_type_row) {
            $db->table($types_table)->insert(array(
                'name' => 'Laudo de demonstracao',
                'code' => 'LT-DEMO',
                'category_id' => $demo_category_row ? (int) $demo_category_row->id : null,
                'description' => 'Tipo de exemplo para homologacao do plugin',
                'prefix' => 'DEMO-',
                'default_template_id' => $generic_template_id ? (int) $generic_template_id : null,
                'validity_days' => 180,
                'require_technical_responsible' => 1,
                'require_review' => 1,
                'require_approval' => 1,
                'require_signature' => 1,
                'require_inspection' => 1,
                'require_calibrated_equipment' => 0,
                'allow_mobile' => 1,
                'is_active' => 1,
                'sort' => 999,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted' => 0,
            ));
            $demo_type_row = $db->table($types_table)
                ->where('code', 'LT-DEMO')
                ->where('deleted', 0)
                ->get()
                ->getRow();
        }

        $clients_table = $db->prefixTable('clients');
        $projects_table = $db->prefixTable('projects');
        $demo_client_row = $db->tableExists($clients_table)
            ? $db->table($clients_table)->where('deleted', 0)->orderBy('id', 'ASC')->limit(1)->get()->getRow()
            : null;
        $demo_project_row = $db->tableExists($projects_table)
            ? $db->table($projects_table)->where('deleted', 0)->orderBy('id', 'ASC')->limit(1)->get()->getRow()
            : null;

        $demo_laudo_exists = $db->table($db->prefixTable('laudos'))
            ->where('deleted', 0)
            ->groupStart()
                ->where('custom_code', 'DEMO-LAUDO-001')
                ->orWhere('title', 'Laudo completo de demonstracao')
            ->groupEnd()
            ->get()
            ->getRow();
        $demo_laudo_id = $demo_laudo_exists ? (int) ($demo_laudo_exists->id ?? 0) : 0;

        if (!$demo_laudo_exists && $demo_type_row && $demo_category_row) {
            $laudos_model = model(\LaudosTecnicos\Models\Laudos_model::class);
            if ($laudos_model) {
                $demo_laudo_id = (int) $laudos_model->save_from_post(array(
                    'custom_code' => 'DEMO-LAUDO-001',
                    'revision' => '01',
                    'title' => 'Laudo completo de demonstracao',
                    'type_id' => (int) $demo_type_row->id,
                    'category_id' => (int) $demo_category_row->id,
                    'template_id' => $generic_template_id ? (int) $generic_template_id : null,
                    'client_id' => $demo_client_row ? (int) $demo_client_row->id : null,
                    'project_id' => $demo_project_row ? (int) $demo_project_row->id : null,
                    'status' => 'issued',
                    'priority' => 'high',
                    'request_date' => date('Y-m-d', strtotime('-15 days')),
                    'scheduled_date' => date('Y-m-d', strtotime('-14 days')),
                    'visit_date' => date('Y-m-d', strtotime('-13 days')),
                    'inspection_date' => date('Y-m-d', strtotime('-12 days')),
                    'issue_date' => date('Y-m-d', strtotime('-10 days')),
                    'validity_date' => date('Y-m-d', strtotime('+355 days')),
                    'unit_name' => 'Unidade Demonstracao',
                    'address' => 'Rua Exemplo, 100 - Centro',
                    'inspection_location' => 'Cobertura e sala tecnica',
                    'commercial_responsible_id' => null,
                    'technical_responsible_id' => null,
                    'inspection_team' => json_encode(array(
                        array('name' => 'Tecnico 1', 'role' => 'Inspetor'),
                        array('name' => 'Tecnico 2', 'role' => 'Apoio'),
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'objective' => 'Demonstrar o fluxo completo de cadastro, emissao e validacao do laudo.',
                    'scope' => 'Inspecao visual, medicoes basicas, registro fotografico e consolidacao de evidencias.',
                    'methodology' => 'Levantamento em campo, conferencia documental, medicao e analise tecnica.',
                    'premises' => 'Edificacao em uso regular e acesso liberado para demonstracao.',
                    'limitations' => 'Seed de exemplo sem vinculacao com cliente real.',
                    'installation_description' => 'Instalacao eletrica, infraestrutura de redes e sistema de seguranca eletronica.',
                    'results' => 'Conforme para os itens avaliados, com observacoes pontuais registradas no corpo do laudo.',
                    'diagnosis' => 'Sistema operacional, com oportunidades de melhoria em organizacao e sinalizacao.',
                    'conclusion' => 'Laudo emitido para fins de demonstração da plataforma.',
                    'recommendations' => 'Manter rotina de inspeções e atualizar registros periodicamente.',
                    'internal_notes' => 'Seed criado automaticamente na instalacao do plugin.',
                    'tags' => 'demo,homologacao,spda',
                    'cost_center' => 'CC-DEMO',
                    'proposal_number' => 'PROP-DEMO-001',
                    'contract_number' => 'CT-DEMO-001',
                    'external_reference' => 'REF-DEMO-001',
                    'confidentiality' => 'internal',
                    'client_observations' => 'Cliente ficticio apenas para demonstracao do fluxo.',
                    'created_by' => 1,
                    'updated_by' => 1,
                ));
            }
        }

        if ($demo_laudo_id > 0) {
            $demo_equipment_model = model(\LaudosTecnicos\Models\LaudoEquipments_model::class);
            $demo_equipment_row = $db->table($db->prefixTable('laudo_equipments'))
                ->where('serial_number', 'EQ-DEMO-001')
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$demo_equipment_row && $demo_equipment_model) {
                $demo_equipment_model->save_from_post(array(
                    'name' => 'Multimetro digital demo',
                    'equipment_type' => 'Medição eletrica',
                    'manufacturer' => 'Demo Instruments',
                    'model' => 'DM-1000',
                    'serial_number' => 'EQ-DEMO-001',
                    'patrimony_number' => 'PAT-DEMO-001',
                    'acquisition_date' => date('Y-m-d', strtotime('-400 days')),
                    'last_calibration' => date('Y-m-d', strtotime('-20 days')),
                    'next_calibration' => date('Y-m-d', strtotime('+335 days')),
                    'certificate' => 'CERT-DEMO-001',
                    'laboratory' => 'Laboratorio Demo',
                    'status' => 'available',
                    'observations' => 'Equipamento criado para o seed de demonstracao.',
                    'created_by' => 1,
                    'updated_by' => 1,
                ));
                $demo_equipment_row = $db->table($db->prefixTable('laudo_equipments'))
                    ->where('serial_number', 'EQ-DEMO-001')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
            }
            $demo_equipment_id = $demo_equipment_row ? (int) $demo_equipment_row->id : 0;

            $demo_inspection_model = model(\LaudosTecnicos\Models\LaudoInspections_model::class);
            $demo_inspection_row = $db->table($db->prefixTable('laudo_inspections'))
                ->where('code', 'INS-DEMO-001')
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$demo_inspection_row && $demo_inspection_model) {
                $demo_inspection_model->save_from_post(array(
                    'code' => 'INS-DEMO-001',
                    'laudo_id' => $demo_laudo_id,
                    'client_id' => $demo_client_row ? (int) $demo_client_row->id : null,
                    'unit_name' => 'Unidade Demonstracao',
                    'location_name' => 'Cobertura tecnica',
                    'inspection_type' => 'Rotina',
                    'inspection_date' => date('Y-m-d', strtotime('-12 days')),
                    'start_time' => '08:30:00',
                    'end_time' => '10:00:00',
                    'duration_minutes' => 90,
                    'responsible_id' => null,
                    'team_json' => json_encode(array(
                        array('name' => 'Tecnico Demo 1', 'role' => 'Inspetor'),
                        array('name' => 'Tecnico Demo 2', 'role' => 'Apoio'),
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'vehicle' => 'Veiculo Demo 01',
                    'equipments_json' => json_encode($demo_equipment_id ? array($demo_equipment_id) : array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'observations' => 'Inspecao de demonstracao gerada automaticamente pelo seed.',
                    'status' => 'concluida',
                    'progress_percent' => 100,
                    'source' => 'seed',
                    'address' => 'Rua Exemplo, 100 - Centro',
                    'latitude' => -23.550520,
                    'longitude' => -46.633308,
                    'created_by' => 1,
                    'updated_by' => 1,
                ));
                $demo_inspection_row = $db->table($db->prefixTable('laudo_inspections'))
                    ->where('code', 'INS-DEMO-001')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
            }
            $demo_inspection_id = $demo_inspection_row ? (int) $demo_inspection_row->id : 0;

            $demo_checkin_model = model(\LaudosTecnicos\Models\LaudoInspectionCheckins_model::class);
            if ($demo_checkin_model && $demo_inspection_id) {
                $existing_checkin = $db->table($db->prefixTable('laudo_inspection_checkins'))
                    ->where('inspection_id', $demo_inspection_id)
                    ->where('check_type', 'checkin')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
                if (!$existing_checkin) {
                    $demo_checkin_model->log_checkin(array(
                        'inspection_id' => $demo_inspection_id,
                        'laudo_id' => $demo_laudo_id,
                        'checked_at' => date('Y-m-d H:i:s', strtotime('-12 days 08:20')),
                        'latitude' => -23.550520,
                        'longitude' => -46.633308,
                        'accuracy' => 12.5,
                        'user_id' => null,
                        'device' => 'Seed demo mobile',
                        'distance_meters' => 18.0,
                        'observation' => 'Check-in de demonstracao.',
                        'source' => 'seed',
                        'ip_address' => '127.0.0.1',
                        'created_by' => 1,
                        'updated_by' => 1,
                    ), 'checkin');
                }
            }

            $demo_photo_model = model(\LaudosTecnicos\Models\LaudoInspectionPhotos_model::class);
            if ($demo_photo_model && $demo_inspection_id) {
                $existing_photo = $db->table($db->prefixTable('laudo_inspection_photos'))
                    ->where('inspection_id', $demo_inspection_id)
                    ->where('file_path', 'uploads/laudos/demo/inspection-001.jpg')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
                if (!$existing_photo) {
                    $demo_photo_model->save_photo(array(
                        'inspection_id' => $demo_inspection_id,
                        'laudo_id' => $demo_laudo_id,
                        'file_path' => 'uploads/laudos/demo/inspection-001.jpg',
                        'thumbnail_path' => 'uploads/laudos/demo/inspection-001-thumb.jpg',
                        'original_file_name' => 'inspection-001.jpg',
                        'caption' => 'Vista geral da area inspecionada',
                        'photo_number' => 1,
                        'taken_at' => date('Y-m-d H:i:s', strtotime('-12 days 08:45')),
                        'user_id' => null,
                        'latitude' => -23.550520,
                        'longitude' => -46.633308,
                        'location_text' => 'Cobertura tecnica',
                        'sector' => 'Infraestrutura',
                        'equipment_id' => $demo_equipment_id ?: null,
                        'checklist_id' => null,
                        'measurement_id' => null,
                        'nonconformity_id' => null,
                        'observation' => 'Foto criada automaticamente pelo seed.',
                        'hash_value' => sha1('uploads/laudos/demo/inspection-001.jpg'),
                        'is_cover' => 1,
                        'is_before' => 1,
                        'is_after' => 0,
                        'sort' => 1,
                        'metadata' => array(
                            'seed' => true,
                            'source' => 'installation',
                        ),
                        'created_by' => 1,
                        'updated_by' => 1,
                    ));
                }
            }

            $demo_checklist_row = $db->table($db->prefixTable('laudo_checklists'))
                ->where('code', 'CHK-SPDA-BASICO')
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if ($demo_checklist_row && $demo_inspection_id) {
                $existing_response = $db->table($db->prefixTable('laudo_checklist_responses'))
                    ->where('inspection_id', $demo_inspection_id)
                    ->where('checklist_id', (int) $demo_checklist_row->id)
                    ->where('group_key', 'visual')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
                if (!$existing_response) {
                    $db->table($db->prefixTable('laudo_checklist_responses'))->insert(array(
                        'laudo_id' => $demo_laudo_id,
                        'inspection_id' => $demo_inspection_id,
                        'checklist_id' => (int) $demo_checklist_row->id,
                        'group_key' => 'visual',
                        'item_id' => null,
                        'response' => 'conforme',
                        'observation' => 'Resposta de demonstracao para o seed completo.',
                        'user_id' => null,
                        'source' => 'seed',
                        'photos_json' => json_encode(array('uploads/laudos/demo/inspection-001.jpg'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'measurements_json' => '[]',
                        'nonconformity_id' => null,
                        'answered_at' => $now,
                        'ip_address' => '127.0.0.1',
                        'created_by' => 1,
                        'updated_by' => 1,
                        'deleted' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ));
                }
            }

            $demo_measurement_type_row = $db->table($db->prefixTable('laudo_measurement_types'))
                ->where('name', 'Resistencia de aterramento')
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$demo_measurement_type_row) {
                $measurement_types_model = model(\LaudosTecnicos\Models\LaudoMeasurementTypes_model::class);
                if ($measurement_types_model) {
                    $measurement_types_model->save_from_post(array(
                        'name' => 'Resistencia de aterramento',
                        'quantity' => 'ohm',
                        'unit' => 'Ohm',
                        'min_value' => 0,
                        'max_value' => 10,
                        'reference_value' => 0,
                        'tolerance_value' => 2,
                        'decimal_places' => 2,
                        'auto_classification' => 1,
                        'description' => 'Seed de demonstracao.',
                        'status' => 1,
                        'created_by' => 1,
                        'updated_by' => 1,
                    ));
                    $demo_measurement_type_row = $db->table($db->prefixTable('laudo_measurement_types'))
                        ->where('name', 'Resistencia de aterramento')
                        ->where('deleted', 0)
                        ->get()
                        ->getRow();
                }
            }

            $demo_measurement_model = model(\LaudosTecnicos\Models\LaudoMeasurements_model::class);
            if ($demo_measurement_model && $demo_measurement_type_row) {
                $existing_measurement = $db->table($db->prefixTable('laudo_measurements'))
                    ->where('laudo_id', $demo_laudo_id)
                    ->where('measurement_type_id', (int) $demo_measurement_type_row->id)
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
                if (!$existing_measurement) {
                    $demo_measurement_model->save_from_post(array(
                        'measurement_type_id' => (int) $demo_measurement_type_row->id,
                        'laudo_id' => $demo_laudo_id,
                        'value' => 4.8,
                        'unit' => 'Ohm',
                        'measured_at' => date('Y-m-d H:i:s', strtotime('-12 days 09:10')),
                        'location' => 'Ponto de aterramento principal',
                        'equipment_id' => $demo_equipment_id ?: null,
                        'responsible_id' => null,
                        'photo' => 'uploads/laudos/demo/measurement-001.jpg',
                        'observation' => 'Medição de demonstracao dentro do limite esperado.',
                        'gps_lat' => -23.550520,
                        'gps_lng' => -46.633308,
                        'gps_text' => 'Unidade demonstracao',
                        'created_by' => 1,
                        'updated_by' => 1,
                    ));
                }
            }

            $demo_nonconformity_model = model(\LaudosTecnicos\Models\LaudoNonconformities_model::class);
            $demo_nc_row = $db->table($db->prefixTable('laudo_nonconformities'))
                ->where('code', 'NC-DEMO-001')
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$demo_nc_row && $demo_nonconformity_model) {
                $demo_nonconformity_model->save_from_post(array(
                    'code' => 'NC-DEMO-001',
                    'title' => 'Sinalizacao de area tecnica insuficiente',
                    'description' => 'Exemplo de nao conformidade para o fluxo de risco e acao corretiva.',
                    'client_id' => $demo_client_row ? (int) $demo_client_row->id : null,
                    'laudo_id' => $demo_laudo_id,
                    'inspection_id' => $demo_inspection_id ?: null,
                    'location_text' => 'Cobertura tecnica',
                    'sector' => 'Infraestrutura',
                    'equipment_id' => $demo_equipment_id ?: null,
                    'checklist_id' => null,
                    'checklist_item_id' => null,
                    'norm_id' => null,
                    'evidence_json' => json_encode(array(
                        array('type' => 'photo', 'caption' => 'Vista geral da area'),
                        array('type' => 'measurement', 'value' => 4.8, 'unit' => 'Ohm'),
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'photos_json' => json_encode(array('uploads/laudos/demo/nc-001.jpg'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'classification' => 'moderada',
                    'probability' => 3,
                    'impact' => 3,
                    'risk_level' => 'moderada',
                    'risk_color' => '#ffc107',
                    'recommendation' => 'Instalar sinalizacao e revisar padronizacao da area.',
                    'suggested_deadline' => date('Y-m-d', strtotime('+15 days')),
                    'responsible_id' => null,
                    'validator_id' => null,
                    'status' => 'open',
                    'identified_at' => $now,
                    'created_by' => 1,
                    'updated_by' => 1,
                ));
                $demo_nc_row = $db->table($db->prefixTable('laudo_nonconformities'))
                    ->where('code', 'NC-DEMO-001')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
            }
            $demo_nc_id = $demo_nc_row ? (int) $demo_nc_row->id : 0;

            $demo_plan_model = model(\LaudosTecnicos\Models\LaudoActionPlans_model::class);
            if ($demo_plan_model && $demo_nc_id) {
                $existing_plan = $db->table($db->prefixTable('laudo_action_plans'))
                    ->where('nonconformity_id', $demo_nc_id)
                    ->where('code', 'AP-DEMO-001')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
                if (!$existing_plan) {
                    $demo_plan_model->save_from_post(array(
                        'nonconformity_id' => $demo_nc_id,
                        'code' => 'AP-DEMO-001',
                        'action' => 'Instalar sinalizacao e revisar organizacao da area tecnica.',
                        'motive' => 'Regularizar a nao conformidade identificada no seed de demonstracao.',
                        'location_text' => 'Cobertura tecnica',
                        'responsible_id' => null,
                        'company_name' => 'Cliente Demo',
                        'method' => '5W2H com validacao documental e evidencias fotograficas.',
                        'deadline' => date('Y-m-d', strtotime('+7 days')),
                        'estimated_cost' => 1500.00,
                        'priority' => 'high',
                        'status' => 'open',
                        'evidence_json' => json_encode(array(
                            array('type' => 'photo', 'file' => 'uploads/laudos/demo/nc-001.jpg'),
                        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'completion_date' => null,
                        'validator_id' => null,
                        'auto_create_task' => 0,
                        'task_sync_enabled' => 1,
                        'what_field' => 'Instalar sinalizacao e revisar a area.',
                        'why_field' => 'Mitigar risco operacional e atender boas praticas.',
                        'where_field' => 'Cobertura tecnica.',
                        'when_field' => 'Em ate 7 dias.',
                        'who_field' => 'Equipe de manutencao.',
                        'how_field' => 'Execucao assistida e validacao tecnica.',
                        'how_much_field' => 'R$ 1.500,00',
                        'created_by' => 1,
                        'updated_by' => 1,
                    ));
                    $existing_plan = $db->table($db->prefixTable('laudo_action_plans'))
                        ->where('nonconformity_id', $demo_nc_id)
                        ->where('code', 'AP-DEMO-001')
                        ->where('deleted', 0)
                        ->get()
                        ->getRow();
                }
            }

            $demo_norm_model = model(\LaudosTecnicos\Models\LaudoNorms_model::class);
            $demo_norm_row = $db->table($db->prefixTable('laudo_norms'))
                ->where('code', 'NBR-5419-DEMO')
                ->where('deleted', 0)
                ->get()
                ->getRow();
            if (!$demo_norm_row && $demo_norm_model) {
                $demo_norm_model->save_from_post(array(
                    'code' => 'NBR-5419-DEMO',
                    'title' => 'Protecao contra descargas atmosfericas - referencia demo',
                    'institution' => 'ABNT',
                    'category' => 'Seguranca eletrica',
                    'edition' => '1',
                    'year' => (int) date('Y'),
                    'description' => 'Referencia demo para vinculacao tecnica.',
                    'link' => 'https://www.abnt.org.br',
                    'authorized_file' => '',
                    'status' => 1,
                    'observation' => 'Registro de demonstracao criado pelo seed.',
                    'created_by' => 1,
                    'updated_by' => 1,
                ));
                $demo_norm_row = $db->table($db->prefixTable('laudo_norms'))
                    ->where('code', 'NBR-5419-DEMO')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
            }

            if ($demo_norm_row) {
                $norm_links_table = $db->prefixTable('laudo_norm_links');
                $norm_link_exists = $db->table($norm_links_table)
                    ->where('norm_id', (int) $demo_norm_row->id)
                    ->where('entity_type', 'laudo')
                    ->where('entity_id', $demo_laudo_id)
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
                if (!$norm_link_exists) {
                    $db->table($norm_links_table)->insert(array(
                        'norm_id' => (int) $demo_norm_row->id,
                        'entity_type' => 'laudo',
                        'entity_id' => $demo_laudo_id,
                        'notes' => 'Norma vinculada ao laudo de demonstracao.',
                        'created_by' => 1,
                        'updated_by' => 1,
                        'deleted' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ));
                }
            }

            $demo_risk_matrix_table = $db->prefixTable('laudo_risk_matrix');
            if ($db->tableExists($demo_risk_matrix_table)) {
                $risk_rows = array(
                    array('name' => 'Demo Baixo 1x1', 'probability' => 1, 'impact' => 1, 'result' => 1, 'classification' => 'observacao', 'color' => '#6c757d', 'suggested_deadline_days' => 30, 'sort' => 1),
                    array('name' => 'Demo Baixo 2x1', 'probability' => 2, 'impact' => 1, 'result' => 2, 'classification' => 'baixa', 'color' => '#198754', 'suggested_deadline_days' => 30, 'sort' => 2),
                    array('name' => 'Demo Moderado 2x2', 'probability' => 2, 'impact' => 2, 'result' => 4, 'classification' => 'moderada', 'color' => '#ffc107', 'suggested_deadline_days' => 15, 'sort' => 3),
                    array('name' => 'Demo Alto 3x4', 'probability' => 3, 'impact' => 4, 'result' => 12, 'classification' => 'alta', 'color' => '#fd7e14', 'suggested_deadline_days' => 7, 'sort' => 4),
                    array('name' => 'Demo Critico 4x4', 'probability' => 4, 'impact' => 4, 'result' => 16, 'classification' => 'critica', 'color' => '#dc3545', 'suggested_deadline_days' => 3, 'sort' => 5),
                    array('name' => 'Demo Emergencial 5x5', 'probability' => 5, 'impact' => 5, 'result' => 25, 'classification' => 'emergencial', 'color' => '#7a1f1f', 'suggested_deadline_days' => 1, 'sort' => 6),
                );
                foreach ($risk_rows as $risk_row) {
                    $risk_exists = $db->table($demo_risk_matrix_table)
                        ->where('name', $risk_row['name'])
                        ->where('deleted', 0)
                        ->get()
                        ->getRow();
                    if ($risk_exists) {
                        continue;
                    }

                    $db->table($demo_risk_matrix_table)->insert(array(
                        'name' => $risk_row['name'],
                        'category_id' => $demo_category_row ? (int) $demo_category_row->id : null,
                        'probability' => (int) $risk_row['probability'],
                        'impact' => (int) $risk_row['impact'],
                        'result' => (int) $risk_row['result'],
                        'classification' => $risk_row['classification'],
                        'color' => $risk_row['color'],
                        'suggested_deadline_days' => (int) $risk_row['suggested_deadline_days'],
                        'is_default' => $risk_row['classification'] === 'moderada' ? 1 : 0,
                        'sort' => (int) $risk_row['sort'],
                        'is_active' => 1,
                        'created_by' => 1,
                        'updated_by' => 1,
                        'deleted' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ));
                }
            }

            $demo_documents_model = model(\LaudosTecnicos\Models\LaudoDocuments_model::class);
            if ($demo_documents_model) {
                $demo_document_row = $db->table($db->prefixTable('laudo_document_versions'))
                    ->where('laudo_id', $demo_laudo_id)
                    ->where('document_code', 'DOC-DEMO-001')
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
                $demo_document_id = $demo_document_row ? (int) $demo_document_row->id : 0;

                if (!$demo_document_row) {
                    $demo_html = '<html><body><h1>Laudo completo de demonstracao</h1><p>Seed demo do plugin LaudosTecnicos.</p></body></html>';
                    $demo_document_id = $demo_documents_model->create_version(array(
                        'laudo_id' => $demo_laudo_id,
                        'variant' => 'full',
                        'document_code' => 'DOC-DEMO-001',
                        'public_key' => 'DEMO-PUB-001',
                        'document_hash' => sha1($demo_html),
                        'html_snapshot' => $demo_html,
                        'pdf_path' => '',
                        'pdf_file_name' => '',
                        'issued_at' => $now,
                        'issued_by' => 1,
                        'status_snapshot' => 'issued',
                        'revision_snapshot' => '01',
                        'visibility' => 'public',
                        'qr_payload' => json_encode(array(
                            'code' => 'DEMO-PUB-001',
                            'laudo_id' => $demo_laudo_id,
                        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'share_token' => 'DEMO-SHARE-001',
                        'share_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                        'created_by' => 1,
                        'updated_by' => 1,
                    ));
                }

                if ($demo_document_id > 0) {
                    $demo_share_row = $db->table($db->prefixTable('laudo_document_shares'))
                        ->where('share_token', 'DEMO-SHARE-001')
                        ->where('deleted', 0)
                        ->get()
                        ->getRow();
                    if (!$demo_share_row) {
                        $demo_share_id = $demo_documents_model->create_share_link(array(
                            'laudo_id' => $demo_laudo_id,
                            'document_version_id' => (int) $demo_document_id,
                            'share_token' => 'DEMO-SHARE-001',
                            'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
                            'visitor_label' => 'Acesso demo',
                            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                            'max_accesses' => 10,
                            'allow_download' => 1,
                            'allow_comments' => 1,
                            'require_visitor_id' => 0,
                            'created_by' => 1,
                            'updated_by' => 1,
                        ));

                        if ($demo_share_id) {
                            $demo_documents_model->log_access(array(
                                'laudo_id' => $demo_laudo_id,
                                'document_version_id' => (int) $demo_document_id,
                                'share_id' => (int) $demo_share_id,
                                'visitor_label' => 'Acesso demo',
                                'event_type' => 'view',
                                'document_variant' => 'full',
                                'downloaded' => 1,
                                'commented' => 1,
                                'visitor_id' => 'demo-user',
                                'ip_address' => '127.0.0.1',
                                'user_agent' => 'Seed demo',
                                'created_by' => 1,
                            ));

                            $demo_documents_model->save_feedback(array(
                                'laudo_id' => $demo_laudo_id,
                                'document_version_id' => (int) $demo_document_id,
                                'share_id' => (int) $demo_share_id,
                                'action' => 'accepted',
                                'comment' => 'Documento de demonstracao validado.',
                                'evidence_json' => '[]',
                                'visitor_label' => 'Acesso demo',
                                'visitor_email' => 'demo@example.com',
                                'accepted' => 1,
                                'rejected' => 0,
                                'ip_address' => '127.0.0.1',
                                'user_agent' => 'Seed demo',
                                'created_by' => 1,
                            ));
                        }
                    }
                }
            }

            $audit_logs_model = model(\LaudosTecnicos\Models\LaudoAuditLogs_model::class);
            if ($audit_logs_model) {
                $audit_exists = $db->table($db->prefixTable('laudo_audit_logs'))
                    ->where('entity_type', 'laudo')
                    ->where('entity_id', $demo_laudo_id)
                    ->where('action', 'seed_demo')
                    ->get()
                    ->getRow();
                if (!$audit_exists) {
                    $audit_logs_model->log_action(array(
                        'entity_type' => 'laudo',
                        'entity_id' => $demo_laudo_id,
                        'action' => 'seed_demo',
                        'user_id' => 1,
                        'ip_address' => '127.0.0.1',
                        'source' => 'seed',
                        'old_values_json' => '[]',
                        'new_values_json' => json_encode(array('demo' => true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'description' => 'Seed de demonstracao completo criado na instalacao do plugin.',
                        'created_by' => 1,
                        'created_at' => $now,
                    ));
                }
            }
        }
    } catch (\Throwable $e) {
        log_message('error', '[LaudosTecnicos] Install hook error: ' . $e->getMessage());
    }

    return true;
}
