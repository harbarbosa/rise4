<?php

// Menu
$lang['laudos_tecnicos_menu'] = 'Laudos Técnicos';
$lang['laudos_dashboard'] = 'Dashboard';
$lang['laudos_list'] = 'Laudos';
$lang['laudos_categories_title'] = 'Categorias';
$lang['laudos_types'] = 'Tipos de Laudo';
$lang['laudos_templates'] = 'Templates';
$lang['laudos_inspections'] = 'Inspeções';
$lang['laudos_settings'] = 'Configurações';

// Status
$lang['laudos_status_draft'] = 'Rascunho';
$lang['laudos_status_requested'] = 'Solicitação Recebida';
$lang['laudos_status_scheduled'] = 'Agendado';
$lang['laudos_status_inspecting'] = 'Em Inspeção';
$lang['laudos_status_in_progress'] = 'Em Andamento';
$lang['laudos_status_pending_review'] = 'Aguardando Revisão';
$lang['laudos_status_approved'] = 'Aprovado';
$lang['laudos_status_issued'] = 'Emitido';
$lang['laudos_status_expired'] = 'Vencido';
$lang['laudos_status_canceled'] = 'Cancelado';

// Permissões
$lang['laudos_permissions'] = 'Laudos Técnicos';
$lang['laudos_view_permission'] = 'Visualizar módulo';
$lang['laudos_create_permission'] = 'Criar laudos';
$lang['laudos_edit_permission'] = 'Editar laudos';
$lang['laudos_delete_draft_permission'] = 'Excluir rascunhos';
$lang['laudos_manage_types_permission'] = 'Gerenciar tipos de laudo';
$lang['laudos_manage_templates_permission'] = 'Gerenciar templates';
$lang['laudos_settings_permission'] = 'Gerenciar configurações';

// Dashboard
$lang['laudos_dashboard_title'] = 'Dashboard - Laudos Técnicos';
$lang['laudos_total'] = 'Total de Laudos';
$lang['laudos_drafts'] = 'Rascunhos';
$lang['laudos_in_progress'] = 'Em Andamento';
$lang['laudos_pending_review'] = 'Aguardando Revisão';
$lang['laudos_approved'] = 'Aprovados';
$lang['laudos_issued'] = 'Emitidos';
$lang['laudos_expired'] = 'Vencidos';
$lang['laudos_canceled'] = 'Cancelados';

// Campos do formulário
$lang['laudos_title'] = 'Título';
$lang['laudos_type'] = 'Tipo de Laudo';
$lang['laudos_category'] = 'Categoria';
$lang['laudos_client'] = 'Cliente';
$lang['laudos_description'] = 'Descrição';
$lang['laudos_status'] = 'Status';
$lang['laudos_version'] = 'Versão';
$lang['laudos_technician'] = 'Técnico';
$lang['laudos_reviewer'] = 'Revisor';
$lang['laudos_approver'] = 'Aprovador';
$lang['laudos_inspection_date'] = 'Data da Inspeção';
$lang['laudos_issue_date'] = 'Data de Emissão';
$lang['laudos_valid_until'] = 'Válido Até';
$lang['laudos_address'] = 'Endereço';
$lang['laudos_city'] = 'Cidade';
$lang['laudos_state'] = 'Estado';
$lang['laudos_observations'] = 'Observações';
$lang['laudos_internal_notes'] = 'Notas Internas';
$lang['laudos_file'] = 'Arquivo';
$lang['laudos_created_by'] = 'Criado Por';
$lang['laudos_created_at'] = 'Criado Em';
$lang['laudos_updated_at'] = 'Atualizado Em';

// Ações
$lang['laudos_add'] = 'Novo Laudo';
$lang['laudos_edit'] = 'Editar';
$lang['laudos_delete'] = 'Excluir';
$lang['laudos_view'] = 'Visualizar';
$lang['laudos_duplicate'] = 'Duplicar';
$lang['laudos_export_pdf'] = 'Exportar PDF';
$lang['laudos_send_email'] = 'Enviar por E-mail';
$lang['laudos_approve'] = 'Aprovar';
$lang['laudos_reject'] = 'Rejeitar';
$lang['laudos_issue'] = 'Emitir';
$lang['laudos_cancel'] = 'Cancelar';
$lang['laudos_restore'] = 'Restaurar';
$lang['laudos_schedule_inspection'] = 'Agendar Inspeção';
$lang['laudos_add_inspection'] = 'Nova Inspeção';

// Tipos de Laudo
$lang['laudos_types_title'] = 'Tipos de Laudo';
$lang['laudos_type_add'] = 'Novo Tipo';
$lang['laudos_type_name'] = 'Nome';
$lang['laudos_type_prefix'] = 'Prefixo';
$lang['laudos_type_require_inspection'] = 'Exige Inspeção';
$lang['laudos_type_require_approval'] = 'Exige Aprovação';
$lang['laudos_type_validity_days'] = 'Validade (dias)';

// Categorias
$lang['laudos_categories_title'] = 'Categorias de Laudo';
$lang['laudos_category_add'] = 'Nova Categoria';
$lang['laudos_category_name'] = 'Nome';
$lang['laudos_category_name_placeholder'] = 'Ex: Engenharia Elétrica';
$lang['laudos_category_description_placeholder'] = 'Descrição opcional da categoria';
$lang['laudos_category_color'] = 'Cor';
$lang['laudos_category_icon'] = 'Ícone';

// Status
$lang['laudos_status_title'] = 'Status dos Laudos';
$lang['laudos_status_add'] = 'Novo Status';
$lang['laudos_status_initial'] = 'Inicial';
$lang['laudos_status_final'] = 'Final';
$lang['laudos_status_cancel'] = 'Cancelamento';
$lang['laudos_status_code_help'] = 'Código único (sem espaços, minúsculas)';
$lang['laudos_status_initial_help'] = 'Primeiro status do laudo';
$lang['laudos_status_final_help'] = 'Último status possível';
$lang['laudos_status_cancel_help'] = 'Indica cancelamento';

// Transições
$lang['laudos_transitions_title'] = 'Transições de Status';
$lang['laudos_transition_add'] = 'Nova Transição';
$lang['laudos_from_status'] = 'Status de Origem';
$lang['laudos_to_status'] = 'Status de Destino';
$lang['laudos_all_status'] = 'Todos os Status';
$lang['laudos_select_status'] = 'Selecione...';
$lang['laudos_same_status_error'] = 'O status de origem e destino devem ser diferentes';

// Campos de Status e Transições
$lang['laudos_sort_order'] = 'Ordem';
$lang['laudos_initial'] = 'Inicial';
$lang['laudos_final'] = 'Final';
$lang['laudos_cancel'] = 'Cancelamento';
$lang['laudos_allow_edit'] = 'Permite edição';
$lang['laudos_allow_delete'] = 'Permite exclusão';
$lang['laudos_allow_issue'] = 'Permite emissão';
$lang['laudos_require_comment'] = 'Exige comentário';
$lang['laudos_notify'] = 'Notificar';
$lang['laudos_create_task'] = 'Criar tarefa';

// Tipos de Laudo (atualizado)
$lang['laudos_types_title'] = 'Tipos de Laudo';
$lang['laudos_type_add'] = 'Novo Tipo';
$lang['laudos_type_name'] = 'Nome';
$lang['laudos_type_prefix'] = 'Prefixo';
$lang['laudos_type_code'] = 'Código';
$lang['laudos_type_require_inspection'] = 'Exige Inspeção';
$lang['laudos_type_require_approval'] = 'Exige Aprovação';
$lang['laudos_type_require_review'] = 'Exige Revisão';
$lang['laudos_type_require_technician'] = 'Exige Técnico Responsável';
$lang['laudos_type_require_signature'] = 'Exige Assinatura';
$lang['laudos_type_require_equipment'] = 'Exige Equipamento Calibrado';
$lang['laudos_type_allow_mobile'] = 'Acesso Mobile';
$lang['laudos_type_validity_days'] = 'Validade (dias)';
$lang['laudos_type_default_template'] = 'Template Padrão';

// Mais permissões
$lang['laudos_manage_categories_permission'] = 'Gerenciar categorias';
$lang['laudos_manage_status_permission'] = 'Gerenciar status';
$lang['laudos_manage_transitions_permission'] = 'Gerenciar transições';
$lang['laudos_change_status_permission'] = 'Alterar status de laudos';

// Templates
$lang['laudos_templates_title'] = 'Templates de Laudo';
$lang['laudos_template_add'] = 'Novo Template';
$lang['laudos_template_name'] = 'Nome do Template';
$lang['laudos_template_content'] = 'Conteúdo';
$lang['laudos_template_default'] = 'Template Padrão';
$lang['laudos_template_select_type'] = 'Selecione o tipo de laudo';

// Inspeções
$lang['laudos_inspections_title'] = 'Inspeções';
$lang['laudos_inspection_add'] = 'Nova Inspeção';
$lang['laudos_inspection_date'] = 'Data/Hora';
$lang['laudos_inspection_technician'] = 'Técnico Responsável';
$lang['laudos_inspection_findings'] = 'Constatações';
$lang['laudos_inspection_recommendations'] = 'Recomendações';
$lang['laudos_inspection_photos'] = 'Fotos';
$lang['laudos_inspection_signature'] = 'Assinatura';
$lang['laudos_inspection_status'] = 'Status';
$lang['laudos_inspection_scheduled'] = 'Agendada';
$lang['laudos_inspection_completed'] = 'Concluída';
$lang['laudos_inspection_cancelled'] = 'Cancelada';

// Configurações
$lang['laudos_settings_title'] = 'Configurações do Módulo';
$lang['laudos_settings_general'] = 'Geral';
$lang['laudos_settings_module_name'] = 'Nome do Módulo';
$lang['laudos_settings_prefix'] = 'Prefixo dos Laudos';
$lang['laudos_settings_number_format'] = 'Formato de Numeração';
$lang['laudos_settings_next_number'] = 'Próximo Número';
$lang['laudos_settings_logo'] = 'Logotipo';
$lang['laudos_settings_primary_color'] = 'Cor Principal';
$lang['laudos_settings_timezone'] = 'Fuso Horário';
$lang['laudos_settings_language'] = 'Idioma';
$lang['laudos_settings_date_format'] = 'Formato de Data';
$lang['laudos_settings_module_active'] = 'Módulo Ativo';
$lang['laudos_settings_enable_logs'] = 'Ativar Logs Detalhados';
$lang['laudos_settings_default_validity'] = 'Validade Padrão (dias)';
$lang['laudos_settings_require_inspection'] = 'Exigir Inspeção';
$lang['laudos_settings_require_approval'] = 'Exigir Aprovação';
$lang['laudos_settings_notify_client'] = 'Notificar Cliente Automaticamente';
$lang['laudos_settings_save'] = 'Salvar Configurações';

// Mensagens
$lang['laudos_saved'] = 'Laudo salvo com sucesso';
$lang['laudos_deleted'] = 'Laudo excluído com sucesso';
$lang['laudos_error'] = 'Erro ao processar laudo';
$lang['laudos_confirm_delete'] = 'Tem certeza que deseja excluir este laudo?';
$lang['laudos_no_records'] = 'Nenhum registro encontrado';

// Validação
$lang['laudos_field_required'] = 'Este campo é obrigatório';
$lang['laudos_title_required'] = 'O título é obrigatório';
$lang['laudos_type_required'] = 'Selecione o tipo de laudo';
$lang['laudos_requirements'] = 'Requisitos';

// Auditoria
$lang['laudos_audit_created'] = 'Laudo criado';
$lang['laudos_audit_updated'] = 'Laudo atualizado';
$lang['laudos_audit_deleted'] = 'Laudo excluído';
$lang['laudos_audit_approved'] = 'Laudo aprovado';
$lang['laudos_audit_rejected'] = 'Laudo rejeitado';
$lang['laudos_audit_issued'] = 'Laudo emitido';
$lang['laudos_audit_canceled'] = 'Laudo cancelado';
$lang['laudos_audit_viewed'] = 'Laudo visualizado';
$lang['laudos_audit_downloaded'] = 'Laudo baixado';
$lang['laudos_audit_inspection_added'] = 'Inspeção adicionada';
$lang['laudos_audit_inspection_completed'] = 'Inspeção concluída';
$lang['laudos_audit_comment_added'] = 'Comentário adicionado';
$lang['laudos_audit_file_uploaded'] = 'Arquivo enviado';
$lang['laudos_audit_signature_added'] = 'Assinatura adicionada';
$lang['laudos_audit_status_changed'] = 'Status alterado';
$lang['laudos_audit_field_changed'] = 'Campo alterado';

// Botões
$lang['save'] = 'Salvar';
$lang['cancel'] = 'Cancelar';
$lang['delete'] = 'Excluir';
$lang['edit'] = 'Editar';
$lang['add'] = 'Adicionar';
$lang['close'] = 'Fechar';
$lang['back'] = 'Voltar';
$lang['confirm'] = 'Confirmar';
$lang['search'] = 'Pesquisar';
$lang['filter'] = 'Filtrar';
$lang['clear'] = 'Limpar';
$lang['export'] = 'Exportar';
$lang['import'] = 'Importar';
$lang['print'] = 'Imprimir';
$lang['download'] = 'Baixar';
$lang['upload'] = 'Enviar';
$lang['preview'] = 'Pré-visualizar';
$lang['send'] = 'Enviar';
$lang['refresh'] = 'Atualizar';
$lang['select_all'] = 'Selecionar Todos';
$lang['deselect_all'] = 'Desmarcar Todos';

// Laudos - Listagem e Detalhes
$lang['laudos_number'] = 'Número';
$lang['laudos_revision'] = 'Rev.';
$lang['laudos_auto_generate'] = '(Automático)';
$lang['laudos_custom_code'] = 'Código Personalizado';
$lang['laudos_tab_identification'] = 'Identificação';
$lang['laudos_tab_dates'] = 'Datas';
$lang['laudos_tab_team'] = 'Equipe';
$lang['laudos_tab_technical'] = 'Técnico';
$lang['laudos_tab_observations'] = 'Observações';
$lang['laudos_tab_summary'] = 'Resumo';
$lang['laudos_tab_history'] = 'Histórico';
$lang['laudos_tab_files'] = 'Arquivos';
$lang['laudos_tab_checklists'] = 'Checklists';
$lang['laudos_tab_photos'] = 'Fotos';
$lang['laudos_tab_signatures'] = 'Assinaturas';
$lang['laudos_identification'] = 'Identificação';
$lang['laudos_dates'] = 'Datas';
$lang['laudos_team'] = 'Equipe';

// Campos de Data
$lang['laudos_request_date'] = 'Data Solicitação';
$lang['laudos_scheduled_date'] = 'Data Agendamento';
$lang['laudos_inspection_date'] = 'Data Inspeção';
$lang['laudos_issue_date'] = 'Data Emissão';
$lang['laudos_valid_until'] = 'Validade';
$lang['start_date'] = 'Data Início';
$lang['end_date'] = 'Data Fim';

// Responsáveis
$lang['laudos_commercial_responsible'] = 'Responsável Comercial';
$lang['laudos_technician'] = 'Inspetor/Técnico';
$lang['laudos_reviewer'] = 'Revisor';
$lang['laudos_approver'] = 'Aprovador';

// Endereço
$lang['laudos_address'] = 'Endereço';
$lang['laudos_city'] = 'Cidade';
$lang['laudos_state'] = 'Estado';
$lang['laudos_location'] = 'Local da Inspeção';

// Conteúdo Técnico
$lang['laudos_objective'] = 'Objetivo';
$lang['laudos_scope'] = 'Escopo';
$lang['laudos_methodology'] = 'Metodologia';
$lang['laudos_assumptions'] = 'Premissas';
$lang['laudos_limitations'] = 'Limitações';
$lang['laudos_installation_description'] = 'Descrição da Instalação';
$lang['laudos_results'] = 'Resultados';
$lang['laudos_diagnosis'] = 'Diagnóstico';
$lang['laudos_conclusion'] = 'Conclusão';
$lang['laudos_recommendations'] = 'Recomendações';

// Observações
$lang['laudos_observations'] = 'Observações';
$lang['laudos_internal_notes'] = 'Notas Internas';
$lang['laudos_client_observations'] = 'Observações para o Cliente';
$lang['laudos_no_technical_content'] = 'Nenhum conteúdo técnico adicionado ainda.';

// Info Complementar
$lang['laudos_tags'] = 'Tags';
$lang['laudos_cost_center'] = 'Centro de Custo';
$lang['laudos_proposal_number'] = 'Nº Proposta';
$lang['laudos_contract_number'] = 'Nº Contrato';
$lang['laudos_external_reference'] = 'Referência Externa';
$lang['laudos_confidential'] = 'Confidencial';

// Prioridade
$lang['laudos_priority_low'] = 'Baixa';
$lang['laudos_priority_normal'] = 'Normal';
$lang['laudos_priority_high'] = 'Alta';
$lang['laudos_priority_urgent'] = 'Urgente';
$lang['laudos_all'] = 'Todos';

// Status
$lang['laudos_change_status'] = 'Alterar Status';
$lang['laudos_comment'] = 'Comentário';
$lang['laudos_no_history'] = 'Nenhum histórico disponível.';

// Arquivos
$lang['laudos_files_coming_soon'] = 'Funcionalidade de arquivos em breve.';
$lang['laudos_coming_soon'] = 'Em breve.';

// Erros
$lang['laudos_cannot_delete'] = 'Este laudo não pode ser excluído.';

// Templates
$lang['laudos_template_info'] = 'Informações do Template';
$lang['laudos_template_sections'] = 'Seções';
$lang['laudos_template_rules'] = 'Regras Condicionais';
$lang['laudos_template_default'] = 'Template Padrão';
$lang['laudos_template_add'] = 'Novo Template';
$lang['laudos_add_section'] = 'Adicionar Seção';
$lang['laudos_add_rule'] = 'Adicionar Regra';
$lang['laudos_no_sections'] = 'Nenhuma seção adicionada';
$lang['laudos_no_rules'] = 'Nenhuma regra condicional';
$lang['laudos_section_page_break'] = 'Quebra de página';
$lang['laudos_field_types'] = 'Tipos de Campo';
$lang['laudos_publish'] = 'Publicar';
$lang['laudos_unpublish'] = 'Despublicar';
$lang['laudos_clone'] = 'Clonar';
$lang['laudos_new_version'] = 'Nova Versão';
$lang['laudos_apply_template'] = 'Aplicar Template';
$lang['laudos_template_applied'] = 'Template aplicado com sucesso';
$lang['laudos_version'] = 'Versão';
$lang['laudos_default'] = 'Padrão';
$lang['laudos_published'] = 'Publicado';
$lang['laudos_draft'] = 'Rascunho';
$lang['laudos_archived'] = 'Arquivado';

// Tipos de Campo
$lang['laudos_field_text'] = 'Texto Simples';
$lang['laudos_field_textarea'] = 'Texto Longo';
$lang['laudos_field_rich_text'] = 'Texto Rico';
$lang['laudos_field_number'] = 'Número';
$lang['laudos_field_decimal'] = 'Decimal';
$lang['laudos_field_currency'] = 'Moeda';
$lang['laudos_field_percentage'] = 'Percentual';
$lang['laudos_field_date'] = 'Data';
$lang['laudos_field_time'] = 'Hora';
$lang['laudos_field_datetime'] = 'Data e Hora';
$lang['laudos_field_yes_no'] = 'Sim ou Não';
$lang['laudos_field_select'] = 'Seleção Única';
$lang['laudos_field_multi_select'] = 'Seleção Múltipla';
$lang['laudos_field_checkbox'] = 'Checkbox';
$lang['laudos_field_dynamic_list'] = 'Lista Dinâmica';
$lang['laudos_field_image'] = 'Imagem';
$lang['laudos_field_file'] = 'Arquivo';
$lang['laudos_field_signature'] = 'Assinatura';
$lang['laudos_field_gps'] = 'Localização GPS';
$lang['laudos_field_measurement'] = 'Medição';
$lang['laudos_field_dynamic_table'] = 'Tabela Dinâmica';
$lang['laudos_field_calculated'] = 'Campo Calculado';
$lang['laudos_field_read_only'] = 'Somente Leitura';
$lang['laudos_field_ai_text'] = 'Texto IA';

// Módulos Técnicos
$lang['laudos_technical'] = 'Mód. Técnicos';
$lang['laudos_equipment_type'] = 'Tipo';
$lang['laudos_serial_number'] = 'Nº Série';
$lang['laudos_patrimony'] = 'Patrimônio';
$lang['laudos_next_calibration'] = 'Próxima Calibração';
$lang['laudos_institution'] = 'Instituição';
$lang['laudos_year'] = 'Ano';
$lang['laudos_title'] = 'Título';

// Inspeções
$lang['laudos_inspections_menu'] = 'Inspeções';
$lang['laudos_inspection_add'] = 'Nova Inspeção';
$lang['laudos_inspection_type'] = 'Tipo de Inspeção';
$lang['laudos_checkin'] = 'Check-in';
$lang['laudos_checkout'] = 'Check-out';
$lang['laudos_calendar'] = 'Agenda';
$lang['laudos_duration'] = 'Duração';
$lang['laudos_vehicle'] = 'Veículo';
$lang['laudos_time'] = 'Horário';

// Não Conformidades
$lang['laudos_nonconformities'] = 'Não Conformidades';
$lang['laudos_nc_add'] = 'Nova NC';
$lang['laudos_nc_code'] = 'Código';
$lang['laudos_nc_title'] = 'Título';
$lang['laudos_nc_classification'] = 'Classificação';
$lang['laudos_nc_risk'] = 'Risco';
$lang['laudos_nc_probability'] = 'Probabilidade';
$lang['laudos_nc_impact'] = 'Impacto';
$lang['laudos_nc_recommendation'] = 'Recomendação';
$lang['laudos_nc_deadline'] = 'Prazo';
$lang['laudos_nc_responsible'] = 'Responsável';
$lang['laudos_nc_evidence'] = 'Evidência';
$lang['laudos_nc_validate'] = 'Validar';
$lang['laudos_nc_reject'] = 'Rejeitar';
$lang['laudos_nc_action_plan'] = 'Plano de Ação';
$lang['laudos_nc_status'] = 'Status';
$lang['laudos_nc_location'] = 'Local';
$lang['laudos_nc_sector'] = 'Setor';
$lang['laudos_nc_identified_at'] = 'Data Identificação';
$lang['laudos_nc_corrected_at'] = 'Data Correção';
$lang['laudos_nc_observation'] = 'Observação';
$lang['laudos_nc_improvement'] = 'Oportunidade de melhoria';
$lang['laudos_nc_low'] = 'Baixa';
$lang['laudos_nc_moderate'] = 'Moderada';
$lang['laudos_nc_high'] = 'Alta';
$lang['laudos_nc_critical'] = 'Crítica';
$lang['laudos_nc_emergential'] = 'Emergencial';
$lang['laudos_nc_open'] = 'Aberta';
$lang['laudos_nc_in_analysis'] = 'Em análise';
$lang['laudos_nc_waiting_correction'] = 'Aguardando correção';
$lang['laudos_nc_in_correction'] = 'Em correção';
$lang['laudos_nc_corrected'] = 'Corrigida';
$lang['laudos_nc_waiting_validation'] = 'Aguardando validação';
$lang['laudos_nc_validated'] = 'Validada';
$lang['laudos_nc_rejected'] = 'Rejeitada';
$lang['laudos_nc_canceled'] = 'Cancelada';

// Planos de Ação (5W2H)
$lang['laudos_action_plan'] = 'Plano de Ação';
$lang['laudos_action_what'] = 'O que?';
$lang['laudos_action_why'] = 'Por que?';
$lang['laudos_action_where'] = 'Onde?';
$lang['laudos_action_when'] = 'Quando?';
$lang['laudos_action_who'] = 'Quem?';
$lang['laudos_action_how'] = 'Como?';
$lang['laudos_action_how_much'] = 'Quanto?';
$lang['laudos_action_method'] = 'Método';
$lang['laudos_action_company'] = 'Empresa';
$lang['laudos_action_estimated_cost'] = 'Custo Estimado';
$lang['laudos_action_priority'] = 'Prioridade';
$lang['laudos_action_status'] = 'Status';
$lang['laudos_action_completed'] = 'Concluído';
$lang['laudos_action_pending'] = 'Pendente';
$lang['laudos_action_in_progress'] = 'Em Andamento';
$lang['laudos_action_create_task'] = 'Criar Tarefa';