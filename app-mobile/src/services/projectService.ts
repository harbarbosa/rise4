/**
 * Serviço de Projetos.
 *
 * Endpoints reais:
 *   GET /api/projects
 *   GET /api/projects/{id}
 *   GET /api/projects/search/{key}
 *
 * Os retornos são normalizados em `ProjectListItem` para uso nos cards
 * e telas de detalhe. Enquanto VITE_USE_REAL_API não estiver ativo, os
 * dados vêm do mock-adapter — o contrato exposto é idêntico.
 */

import { api, USE_REAL_API, ApiError } from "@/services/api";
import { mockApi } from "@/services/mock-adapter";
import { projetos as projetosMock } from "@/lib/mock-data";
import type { Project, ProjectStatus } from "@/services/types";

export interface ProjectMember {
  id: string; // user_id (usado em mutations)
  memberRecordId?: string; // id do registro em members
  name: string;
  jobTitle?: string;
  userType?: string;
  isLeader: boolean;
  image?: string | null;
}

export interface ProjectListItem {
  id: string;
  titulo: string;
  cliente: string;
  cidade: string;
  tipo: string;
  dataInicio: string;
  prazo: string;
  status: ProjectStatus;
  percentualExecutado: number | null;
  labels: string[];
  responsavel?: string;
  members: ProjectMember[];
}

const FALLBACK = "Não informado";

function pick<T = unknown>(obj: Record<string, unknown>, ...keys: string[]): T | undefined {
  for (const k of keys) {
    const v = obj[k];
    if (v !== undefined && v !== null && v !== "") return v as T;
  }
  return undefined;
}

function mapStatus(value: unknown): ProjectStatus {
  const raw = (value ?? "").toString().toLowerCase().trim();
  if (!raw) return "programada";
  if (/(conclu|finaliz|encerr|complet|done|fechad)/.test(raw)) return "concluida";
  if (/(atras|late|overdue|vencid)/.test(raw)) return "atrasada";
  if (/(andamento|execu|ativo|active|in[_-]?progress|iniciad|progresso|aberto|open|started|executando|running)/.test(raw))
    return "em_andamento";
  if (/(program|pend|scheduled|agendad|planejad|aguard|novo|backlog)/.test(raw))
    return "programada";
  return "programada";
}

function formatDate(value: string | undefined): string {
  if (!value) return "";
  // Aceita "YYYY-MM-DD", ISO completo ou "DD/MM/YYYY"
  if (/^\d{2}\/\d{2}\/\d{4}/.test(value)) return value.slice(0, 10);
  const [d] = value.split("T");
  return d ?? value;
}

export function mapProject(raw: Project | Record<string, unknown>): ProjectListItem {
  const r = raw as Record<string, unknown>;

  const labelsRaw = pick<unknown>(r, "labels", "tags");
  const labels = Array.isArray(labelsRaw)
    ? labelsRaw
        .map((l) => (typeof l === "string" ? l : (l as Record<string, unknown>)?.name))
        .filter((l): l is string => !!l)
    : [];

  const percentualRaw = pick<number | string>(
    r,
    "percentual",
    "percentual_executado",
    "execution_percentage",
    "percentage_executed",
    "percentage",
    "progress",
    "progresso",
    "completion",
    "completion_percentage",
  );
  const percentual =
    typeof percentualRaw === "number"
      ? percentualRaw
      : percentualRaw !== undefined && percentualRaw !== null && percentualRaw !== ""
      ? Number(percentualRaw)
      : NaN;

  const membersRaw = pick<unknown>(r, "members", "team_members", "collaborators");
  const members: ProjectMember[] = Array.isArray(membersRaw)
    ? (membersRaw as Record<string, unknown>[])
        .filter((m) => String(m?.deleted ?? "0") !== "1")
        .map((m) => {
          const userId = (pick<string | number>(m, "user_id", "userId", "id") ?? "").toString();
          const recordId = pick<string | number>(m, "id");
          return {
            id: userId,
            memberRecordId: recordId !== undefined ? String(recordId) : undefined,
            name: (pick<string>(m, "member_name", "name", "full_name", "nome") ?? "").toString(),
            jobTitle: pick<string>(m, "job_title", "cargo", "role"),
            userType: pick<string>(m, "user_type", "type"),
            isLeader: String(pick<string | number>(m, "is_leader") ?? "0") === "1",
            image: pick<string>(m, "avatar_url", "avatar", "photo_url") ?? null,
          };
        })
        .filter((m) => m.id && m.name)
    : [];

  return {
    id: (pick<string>(r, "id", "uuid") ?? "").toString(),
    titulo: (pick<string>(r, "nome", "title", "name", "project_name", "project_title") ?? FALLBACK).toString(),
    cliente: (pick<string>(r, "cliente", "client", "customer", "client_name", "customer_name", "nome_cliente", "client_project", "project_client", "client_title", "client_company", "company", "company_name") ?? FALLBACK).toString(),
    cidade: (pick<string>(r, "cidade", "city", "local") ?? "").toString(),
    tipo: (pick<string>(r, "tipo", "type", "category", "project_type") ?? "").toString(),
    dataInicio: formatDate(pick<string>(r, "data_inicio", "start_date", "inicio")),
    prazo: formatDate(pick<string>(r, "data_previsao", "due_date", "deadline", "termino", "end_date")),
    status: mapStatus(pick<string>(r, "status_title", "status_key_name", "status")),
    percentualExecutado: Number.isFinite(percentual) ? Math.round(percentual) : null,
    labels,
    responsavel: pick<string>(r, "responsavel", "owner", "assigned_to"),
    members,
  };
}

/* ---------------- Fontes ---------------- */

function unwrapList<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    for (const key of ["data", "rows", "items", "result", "results", "projects"]) {
      const v = p[key];
      if (Array.isArray(v)) return v as T[];
    }
  }
  return [];
}

function unwrapOne<T>(payload: unknown): T {
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    if (p.data && typeof p.data === "object" && !Array.isArray(p.data)) return p.data as T;
  }
  return payload as T;
}

const realSource = {
  list: (userId?: string) =>
    api
      .get("/api/projects", { params: userId ? { user_id: userId } : undefined })
      .then((r) => unwrapList<Project>(r.data)),
  byId: (id: string) => api.get(`/api/projects/${id}`).then((r) => unwrapOne<Project>(r.data)),
  search: (key: string, userId?: string) =>
    api
      .get(`/api/projects/search/${encodeURIComponent(key)}`, {
        params: userId ? { user_id: userId } : undefined,
      })
      .then((r) => unwrapList<Project>(r.data)),
};

const mockSource = {
  list: () => mockApi.projects(),
  byId: async (id: string): Promise<Project> => {
    const list = await mockApi.projects();
    const found = list.find((p) => p.id === id);
    if (!found) {
      const raw = projetosMock.find((p) => p.id === id);
      if (!raw) throw new ApiError("Projeto não encontrado", 404);
      return {
        id: raw.id,
        nome: raw.nome,
        cliente: raw.cliente,
        cidade: "",
        data_inicio: raw.inicio,
        data_previsao: raw.termino,
        percentual: raw.progresso,
        status: "em_andamento",
      };
    }
    return found;
  },
  search: async (key: string) => {
    const list = await mockApi.projects();
    const q = key.toLowerCase();
    return list.filter(
      (p) =>
        p.nome.toLowerCase().includes(q) ||
        p.cliente.toLowerCase().includes(q),
    );
  },
};

const source = USE_REAL_API ? realSource : mockSource;

export async function fetchProjects(userId?: string): Promise<ProjectListItem[]> {
  const data = await source.list(userId);
  return (data ?? []).map(mapProject);
}

export async function fetchProject(id: string): Promise<ProjectListItem> {
  const data = await source.byId(id);
  return mapProject(data);
}

export async function searchProjects(key: string, userId?: string): Promise<ProjectListItem[]> {
  const data = await source.search(key, userId);
  return (data ?? []).map(mapProject);
}
