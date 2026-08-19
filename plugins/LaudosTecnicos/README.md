# Laudos Tecnicos - Fase 1

## Objetivo

Base inicial do modulo de gestao de laudos tecnicos no Rise CRM.

## O que foi criado

- Estrutura do plugin com bootstrap, rotas, controllers, models, views, helper e library de auditoria
- Menu lateral do Rise CRM
- Permissoes de acesso
- Migrations iniciais
- Configuracoes basicas
- Dashboard inicial
- Telas placeholders para laudos, tipos, categorias, templates e inspecoes

## Arquitetura inicial

- `LaudosTecnicos/Plugin.php` registra menus, permissoes, migrations e configuracoes padrao
- `LaudosTecnicos/Controllers/*` atende as telas iniciais
- `LaudosTecnicos/Models/*` encapsula acesso aos dados
- `LaudosTecnicos/Database/Migrations/*` cria a base de dados
- `LaudosTecnicos/Views/*` segue o padrao visual do Rise CRM
- `LaudosTecnicos/Libraries/AuditService.php` registra auditoria reutilizavel

## Tabelas criadas

- `laudos`
- `laudo_types`
- `laudo_categories`
- `laudo_settings`
- `laudo_audit_logs`

## Permissoes

- `laudostecnicos_access`
- `laudostecnicos_view_dashboard`
- `laudostecnicos_view_laudos`
- `laudostecnicos_create_laudos`
- `laudostecnicos_edit_laudos`
- `laudostecnicos_delete_drafts`
- `laudostecnicos_view_inspections`
- `laudostecnicos_manage_categories`
- `laudostecnicos_manage_types`
- `laudostecnicos_manage_templates`
- `laudostecnicos_manage_settings`

## Rotas

- `laudostecnicos`
- `laudostecnicos/laudos`
- `laudostecnicos/tipos`
- `laudostecnicos/categorias`
- `laudostecnicos/templates`
- `laudostecnicos/inspecoes`
- `laudostecnicos/configuracoes`
- `laudostecnicos/configuracoes/save`

## Proximos passos

1. Cadastro completo de tipos, categorias e status
2. Cadastro e listagem de laudos
3. Construtor de templates
4. Checklists, medições e inspeções
5. Revisao, aprovacao, assinaturas e PDF
6. API mobile, offline, IA e relatórios
