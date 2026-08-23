/**
 * Serviço de Etapas (Milestones) do Projeto.
 *
 * Endpoint real:
 *   GET /api/projectanalizer/milestones/{project_id}
 *
 * Cada milestone possui:
 *  - percentage: peso da etapa no projeto (0-100)
 *  - progress_percentage: % concluído da etapa
 *  - contribuicao no projeto = (percentage * progress_percentage) / 100
 */

import { api, USE_REAL_API, ApiError } from "@/services/api";

export interface MilestoneItem {
  id: string;
  projectId: string;
  titulo: string;
  descricao?: string;
  dueDate?: string;
  peso: number; // percentage (peso da etapa no projeto)
  progresso: number; // progress_percentage (% concluído da etapa)
  totalPontos: number;
  totalTarefas: number;
  pontosConcluidos: number;
  tarefasConcluidas: number;
}

function pick<T = unknown>(obj: Record<string, unknown>, ...keys: string[]): T | undefined {
  for (const k of keys) {
    const v = obj[k];
    if (v !== undefined && v !== null && v !== "") return v as T;
  }
  return undefined;
}

function num(v: unknown, fallback = 0): number {
  if (typeof v === "number" && Number.isFinite(v)) return v;
  if (typeof v === "string") {
    const n = Number(v);
    if (Number.isFinite(n)) return n;
  }
  return fallback;
}

function mapMilestone(raw: Record<string, unknown>): MilestoneItem {
  return {
    id: (pick<string | number>(raw, "id", "milestone_id") ?? "").toString(),
    projectId: (pick<string | number>(raw, "project_id", "projectId") ?? "").toString(),
    titulo: (pick<string>(raw, "title", "titulo", "name", "nome") ?? "Etapa").toString(),
    descricao: pick<string>(raw, "description", "descricao") ?? undefined,
    dueDate: pick<string>(raw, "due_date", "data_prevista", "deadline") ?? undefined,
    peso: num(pick(raw, "percentage", "peso", "weight")),
    progresso: num(pick(raw, "progress_percentage", "progresso", "progress")),
    totalPontos: num(pick(raw, "total_points")),
    totalTarefas: num(pick(raw, "total_tasks")),
    pontosConcluidos: num(pick(raw, "completed_points")),
    tarefasConcluidas: num(pick(raw, "completed_tasks")),
  };
}

function unwrapList<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    for (const key of ["data", "rows", "items", "result", "results", "milestones", "etapas"]) {
      const v = p[key];
      if (Array.isArray(v)) return v as T[];
    }
  }
  return [];
}

export async function fetchProjectMilestones(projectId: string): Promise<MilestoneItem[]> {
  if (!projectId) throw new ApiError("ID do projeto não informado", 400);
  if (!USE_REAL_API) return [];
  const r = await api.get(`/api/projectanalizer/milestones/${encodeURIComponent(projectId)}`);
  return unwrapList<Record<string, unknown>>(r.data).map(mapMilestone);
}

/**
 * Calcula o progresso ponderado de uma etapa com base no peso de cada tarefa
 * dentro dela: Σ(peso_tarefa * % executado) / Σ peso_tarefa.
 *
 * Caso nenhuma tarefa tenha peso, faz média simples dos % executados.
 * Se não houver tarefas, cai no progresso retornado pelo backend.
 */
export function calcularProgressoEtapa(
  milestone: MilestoneItem,
  tarefas: { peso: number; percentual: number }[],
): number {
  if (!tarefas.length) return Math.round(milestone.progresso);
  const somaPesos = tarefas.reduce((s, t) => s + (t.peso || 0), 0);
  if (somaPesos <= 0) {
    const media = tarefas.reduce((s, t) => s + (t.percentual || 0), 0) / tarefas.length;
    return Math.round(media);
  }
  const ponderado = tarefas.reduce((s, t) => s + (t.peso || 0) * (t.percentual || 0), 0) / somaPesos;
  return Math.round(ponderado);
}

/**
 * Calcula o percentual total do projeto ponderando duas vezes:
 *  - peso de cada tarefa dentro da etapa
 *  - peso de cada etapa dentro do projeto
 *
 * Σ (peso_etapa * progresso_etapa_ponderado) / Σ peso_etapa
 */
export function calcularProgressoProjeto(
  milestones: MilestoneItem[],
  tarefasPorEtapa?: (m: MilestoneItem) => { peso: number; percentual: number }[],
): number {
  if (!milestones.length) return 0;
  const somaPesos = milestones.reduce((s, m) => s + m.peso, 0);
  const progressoEtapa = (m: MilestoneItem) =>
    tarefasPorEtapa ? calcularProgressoEtapa(m, tarefasPorEtapa(m)) : m.progresso;
  if (somaPesos <= 0) {
    const media = milestones.reduce((s, m) => s + progressoEtapa(m), 0) / milestones.length;
    return Math.round(media);
  }
  const ponderado = milestones.reduce((s, m) => s + m.peso * progressoEtapa(m), 0) / somaPesos;
  return Math.round(ponderado);
}

