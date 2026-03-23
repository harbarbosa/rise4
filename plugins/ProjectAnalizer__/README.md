# ProjectAnalizer — Evolução Físico‑Financeira

## Fase 1 (aba do projeto)
- Nova aba no projeto: `evolucao_ff` (Evolução Físico‑Financeira)
- Rota: `projects/{project_id}/projectanalizer/evolucao`
- View inicial: cards + tabela de etapas (milestones) com progresso físico real (ponderado)
- A aba nativa `gantt` (Evolutivo) é removida do `project_tab_order` e também ocultada via CSS (fallback)

## Fase 2 (tabelas auxiliares)
O plugin cria/garante as tabelas auxiliares (equivalentes ao que seria `pa_*`):
- `projectanalizer_task_metrics` (baseline/peso/distribuição por task)
- `projectanalizer_task_costs` (custos planejados por task e categoria)
- `projectanalizer_cost_realized` (custos realizados por projeto/task)
- `projectanalizer_project_snapshots` (snapshots planejado x real para histórico/gráficos)

Todos os registros usam `deleted=0/1` (soft delete), compatível com `Crud_model`.

## Models
Models do plugin (CRUD básico + `get_details()`):
- `ProjectAnalizer\\Models\\Task_metrics_model`
- `ProjectAnalizer\\Models\\Task_costs_model`
- `ProjectAnalizer\\Models\\Cost_realized_model`
- `ProjectAnalizer\\Models\\Project_snapshots_model`

## Service
- `ProjectAnalizer\\Libraries\\ProjectAnalizerEvolutionService`
  - `get_physical_summary($project_id)` (progresso físico real ponderado por task/milestone)

## Instalação / atualização
1) Copie a pasta `ProjectAnalizer` para `plugins/`
2) Ative o plugin no RiseCRM
3) Clique em **Atualizar** no gerenciador de plugins (executa o `register_update_hook` e garante as tabelas/colunas)

## Fase 4 (baseline e planejado)
- Na aba Evolucao Fisico-Financeira, use o botao **Gerar baseline**.
- Informe a data base do baseline ou marque **Usar hoje**.
- O baseline nao altera as datas reais das tasks; grava apenas em `projectanalizer_task_metrics`.
- Os cards mostram **Planejado hoje** e **Desvio** (p.p.) e a tabela mostra **Planejado** vs **Progresso** por etapa.

## Fase 5 (replanejamento por inicio real)
- Botao **Definir inicio real do projeto** na aba Evolucao Fisico-Financeira.
- Modo **Delta** desloca datas por diferenca entre data de referencia e nova data.
- Modo **Dependencias** tenta recalcular por predecessoras; se nao houver dependencias, aplica delta.
- Opcao para ajustar etapas (milestones).
- Log registrado em `projectanalizer_project_reschedule_log`.

## Fase 6 (cronograma fisico-financeiro planejado)
- Cadastro de custos planejados por task e tipo na aba Evolucao Fisico-Financeira.
- Distribuicao por task: linear, inicio, fim, curva S ou manual.
- Planejado financeiro ate hoje aparece em card e por etapa.

## Fase 7 (realizado + indicadores)
- Lancamentos de custos realizados por projeto (opcional por task).
- Cards: Realizado, Desvio financeiro, SPI e CPI.
- Graficos: planejado financeiro acumulado vs realizado acumulado e fisico planejado vs real.

## Fase 8 (snapshots)
- Snapshots diarios/semanais para historico e performance.
- Endpoint de cron: `projectanalizer/cron-snapshots?key=SEU_TOKEN`
- Quando houver snapshots, os graficos usam os dados salvos por periodo (30/90/180 dias).
- Defina a chave no setting `projectanalizer_cron_key` (tabela `settings`).

## Fase 9 (acabamento)
- Listas: top atrasos e tarefas bloqueadas.
- Exportacao: PDF (print-friendly) e CSV de custos.
- Auditoria: baseline, replanejamento, custos e realizados.
