/**
 * Serviço de Tarefas do Projeto.
 *
 * Endpoint real:
 *   GET /api/projectanalizer/tasks/{project_id}
 *
 * Normaliza o retorno da API em `TaskListItem` para uso nos cards
 * de tarefas. Enquanto VITE_USE_REAL_API não estiver ativo, os
 * dados vêm do mock-adapter — o contrato exposto é idêntico.
 */

import { api, USE_REAL_API, ApiError } from "@/services/api";
import { mockApi } from "@/services/mock-adapter";
import { tarefasProjeto } from "@/lib/mock-data";
import type { ProjectTask } from "@/services/types";

export type TaskStatus = "pendente" | "em_andamento" | "concluida";

export interface TaskListItem {
  id: string;
  projectId: string;
  milestoneId?: string;
  titulo: string;
  responsavel: string;
  responsavelImage?: string | null;
  percentual: number; // % executado da tarefa
  peso: number; // peso (%) da tarefa dentro da etapa
  status: TaskStatus;
  concluida: boolean;
  dataPrevista?: string;
  etapa?: string;
}

const FALLBACK = "Não informado";

function pick<T = unknown>(obj: Record<string, unknown>, ...keys: string[]): T | undefined {
  for (const k of keys) {
    const v = obj[k];
    if (v !== undefined && v !== null && v !== "") return v as T;
  }
  return undefined;
}

function mapTaskStatus(value: string | undefined): TaskStatus {
  const map: Record<string, TaskStatus> = {
    pendente: "pendente",
    em_andamento: "em_andamento",
    andamento: "em_andamento",
    in_progress: "em_andamento",
    concluida: "concluida",
    concluido: "concluida",
    completed: "concluida",
    done: "concluida",
  };
  return map[(value ?? "").toLowerCase()] ?? "pendente";
}

export function mapTask(raw: ProjectTask | Record<string, unknown>): TaskListItem {
  const r = raw as Record<string, unknown>;

  const status = mapTaskStatus(pick<string>(r, "status_key_name", "status"));
  const percentual = pick<number | string>(r, "execution_percentage", "percentual", "percentual_executado", "progress", "progresso");
  const peso = pick<number | string>(r, "percentage", "peso", "weight", "task_weight");

  const toNum = (v: unknown) => {
    if (typeof v === "number" && Number.isFinite(v)) return v;
    if (typeof v === "string") {
      const n = Number(v);
      if (Number.isFinite(n)) return n;
    }
    return 0;
  };

  return {
    id: (pick<string>(r, "id", "uuid", "task_id") ?? "").toString(),
    projectId: (pick<string>(r, "project_id", "projectId", "projeto_id") ?? "").toString(),
    milestoneId: pick<string | number>(r, "milestone_id", "milestoneId")?.toString(),
    titulo: (pick<string>(r, "nome", "title", "name", "titulo", "descricao") ?? FALLBACK).toString(),
    responsavel: (pick<string>(r, "responsavel", "owner", "assigned_to", "usuario") ?? FALLBACK).toString(),
    responsavelImage: pick<string>(r, "responsavel_image", "assigned_to_avatar", "member_image", "avatar_url", "avatar", "user_image", "photo_url") ?? undefined,
    percentual: toNum(percentual),
    peso: toNum(peso),
    status,
    concluida: status === "concluida",
    dataPrevista: pick<string>(r, "data_prevista", "due_date", "deadline", "data_previsao") ?? undefined,
    etapa: pick<string>(r, "etapa", "stage", "phase", "fase", "grupo", "group", "etapa_nome", "stage_name", "milestone_title", "milestone") ?? undefined,
  };
}


/* ---------------- Fontes ---------------- */

function unwrapList<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    for (const key of ["data", "rows", "items", "result", "results", "tasks", "tarefas"]) {
      const v = p[key];
      if (Array.isArray(v)) return v as T[];
    }
  }
  return [];
}

const realSource = {
  byProject: (projectId: string) =>
    api
      .get(`/api/projectanalizer/tasks/${encodeURIComponent(projectId)}`)
      .then((r) => unwrapList<ProjectTask>(r.data)),
};

const mockSource = {
  byProject: async (projectId: string): Promise<ProjectTask[]> => {
    // Tenta primeiro o mock adapter padronizado
    const tasks = await mockApi.projectTasks(projectId);
    if (tasks.length > 0) return tasks;

    // Fallback para dados mockados legados
    const legacy = tarefasProjeto[projectId] ?? [];
    return legacy.map((t) => ({
      id: t.id,
      project_id: projectId,
      nome: t.titulo,
      responsavel: t.responsavel,
      percentual: t.progresso,
      status: t.concluida
        ? ("concluida" as const)
        : t.status === "em_andamento"
          ? ("em_andamento" as const)
          : ("pendente" as const),
    }));
  },
};

const source = USE_REAL_API ? realSource : mockSource;

export async function fetchProjectTasks(projectId: string): Promise<TaskListItem[]> {
  if (!projectId) throw new ApiError("ID do projeto não informado", 400);
  const data = await source.byProject(projectId);
  return (data ?? []).map(mapTask);
}
