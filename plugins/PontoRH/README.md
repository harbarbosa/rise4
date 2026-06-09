# PontoRH

Plugin de controle de ponto integrado ao RiseCRM, usando os usuários, funcionários, papéis e permissões nativos do sistema.

## Instalação

1. Copie a pasta `plugins/PontoRH` para o diretório de plugins do RiseCRM.
2. Acesse o painel de plugins do RiseCRM e ative `PontoRH`.
3. Na ativação, o plugin executa as migrations e cria as tabelas e configurações iniciais.
4. Após ativar, revise as permissões dos papéis em `Configurações > Funções e permissões`.

## Estrutura

```text
plugins/PontoRH/
  Controllers/
  Models/
  Views/
  Helpers/
  Libraries/
  Config/
  Language/
  Database/Migrations/
  install.php
  uninstall.php
  plugin.json
  README.md
```

## Rotas

### Principal

- `GET pontorh`
- `GET pontorh/espelho`
- `GET pontorh/espelho/export_pdf`
- `GET pontorh/espelho/export_excel`
- `GET pontorh/relatorios`
- `GET pontorh/auditoria`

### Registros

- `GET pontorh/registros`
- `POST pontorh/registros/list_data`
- `GET pontorh/registros/detalhes/{id}`
- `POST pontorh/registros/view_modal`
- `GET pontorh/registros/view_modal/{id}`
- `POST pontorh/registros/modal_form`
- `GET pontorh/registros/modal_form/{id}`
- `POST pontorh/registros/save`
- `POST pontorh/registros/delete`

### Jornadas

- `GET pontorh/jornadas`
- `POST pontorh/jornadas/list_data`
- `POST pontorh/jornadas/modal_form`
- `GET pontorh/jornadas/modal_form/{id}`
- `POST pontorh/jornadas/save`
- `POST pontorh/jornadas/toggle_active`
- `POST pontorh/jornadas/delete`

### Ajustes

- `GET pontorh/ajustes`
- `POST pontorh/ajustes/list_data`
- `GET pontorh/ajustes/detalhes/{id}`
- `POST pontorh/ajustes/view_modal`
- `GET pontorh/ajustes/view_modal/{id}`
- `POST pontorh/ajustes/modal_form`
- `GET pontorh/ajustes/modal_form/{id}`
- `POST pontorh/ajustes/save`
- `POST pontorh/ajustes/review`
- `POST pontorh/ajustes/delete`

### Configurações

- `GET pontorh/configuracoes`
- `POST pontorh/configuracoes/save`

### Auditoria

- `GET pontorh/auditoria`
- `POST pontorh/auditoria/list_data`
- `GET pontorh/auditoria/detalhes/{id}`
- `POST pontorh/auditoria/view_modal`
- `GET pontorh/auditoria/view_modal/{id}`

## API

O plugin foi preparado para integração futura com automações, folha de pagamento, ProjectAnalizer e o aplicativo mobile da AlfaHP.

Hoje o plugin já registra:

- auditoria de criação
- auditoria de alteração
- auditoria de aprovação
- auditoria de rejeição
- auditoria de tentativa inválida
- evento `login_api` na trilha de auditoria, para uso por endpoints futuros

### Estratégia recomendada para a futura API

- autenticação por token da aplicação
- associação direta com `team_members.id`
- logs em `pontorh_audit_logs`
- sincronização incremental por `created_at` e `updated_at`
- respostas em JSON com suporte a offline-first

## Permissões

Permissões nativas do plugin:

- `pontorh_view_own`
- `pontorh_create_record`
- `pontorh_request_adjustment`
- `pontorh_view_team`
- `pontorh_approve_adjustment`
- `pontorh_manage_schedules`
- `pontorh_view_reports`
- `pontorh_manage_settings`
- `pontorh_admin`

Regras de acesso:

- Funcionário: vê apenas seus próprios dados
- Gestor: vê a equipe
- RH: vê tudo quando o papel receber as permissões adequadas
- Administrador: acesso total

## Banco de dados

Tabelas criadas pelo plugin:

- `pontorh_records`
- `pontorh_work_schedules`
- `pontorh_schedule_days`
- `pontorh_adjustment_requests`
- `pontorh_devices`
- `pontorh_locations`
- `pontorh_monthly_summaries`
- `pontorh_audit_logs`
- `pontorh_settings`

Regras principais:

- vínculo com funcionário sempre via `team_member_id`
- vínculo com usuário operacional via `user_id`
- registros com `hash`, `created_at`, `created_by`
- soft delete com campo `deleted`
- foreign keys sempre que possível

## Fluxos

### Registro de ponto

1. O usuário acessa `Registros`.
2. O sistema valida permissões antes de exibir ou salvar.
3. A marcação é salva em `pontorh_records`.
4. Toda alteração gera log em `pontorh_audit_logs`.

### Ajuste

1. Funcionário solicita ajuste.
2. Gestor ou RH aprova/rejeita.
3. A decisão gera auditoria e notificação nativa.

### Jornada

1. RH cria ou edita jornada.
2. A jornada pode ser vinculada a um funcionário via `team_member_id`.
3. Ativação e inativação são soft actions, sem exclusão física.

### Espelho e relatórios

1. O usuário seleciona funcionário, mês e ano.
2. O plugin consolida entradas, saídas, faltas, atrasos, horas extras e banco de horas.
3. Exportação disponível em PDF e Excel.

### Auditoria

1. Ações sensíveis são registradas automaticamente.
2. A tela de logs mostra o histórico com filtros.
3. Tentativas inválidas também entram no trilho de auditoria.

## Integrações futuras

O desenho atual deixa a base pronta para:

- folha de pagamento
- ProjectAnalizer
- mobile AlfaHP

Pontos de integração já previstos:

- `team_members.id` como vínculo principal de funcionário
- exportação mensal por período
- trilha de auditoria unificada
- armazenamento de GPS, selfie e modo offline nas configurações

