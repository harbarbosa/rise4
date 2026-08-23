/**
 * Serviço de Agenda / Execution Schedules.
 *
 * Endpoint real:
 *   GET /api/projectanalizer/execution-schedules
 *
 * O backend pode devolver os registros com chaves variadas (português ou
 * inglês). Este serviço normaliza tudo no formato `ExecutionSchedule` usado
 * pelo app, e suporta filtro por técnico (user_id) — quando o backend não
 * filtra, ainda aplicamos o filtro client-side.
 */

import { api, USE_REAL_API } from "@/services/api";
import { mockApi } from "@/services/mock-adapter";
import type { ExecutionSchedule, ProjectStatus } from "@/services/types";

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
  if (/(program|pend|scheduled|agendad|planejad|aguard|novo|backlog)/.test(raw)) return "programada";
  return "programada";
}

function toDateISO(value: unknown): string {
  const v = (value ?? "").toString();
  if (!v) return "";
  if (/^\d{4}-\d{2}-\d{2}/.test(v)) return v.slice(0, 10);
  if (/^\d{2}\/\d{2}\/\d{4}/.test(v)) {
    const [d, m, y] = v.slice(0, 10).split("/");
    return `${y}-${m}-${d}`;
  }
  const d = new Date(v);
  if (!isNaN(d.getTime())) {
    // Usa data local (evita shift de timezone ao formatar como ISO UTC)
    const y = d.getFullYear();
    const mo = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${mo}-${day}`;
  }
  return v;
}

function toTime(value: unknown): string {
  const v = (value ?? "").toString();
  if (!v) return "";
  // "HH:mm" ou "HH:mm:ss"
  const m = v.match(/(\d{1,2}):(\d{2})/);
  if (m) return `${m[1].padStart(2, "0")}:${m[2]}`;
  // ISO datetime — extrai hora
  const d = new Date(v);
  if (!isNaN(d.getTime())) {
    const hh = d.getHours().toString().padStart(2, "0");
    const mm = d.getMinutes().toString().padStart(2, "0");
    return `${hh}:${mm}`;
  }
  return v;
}

function asObj(v: unknown): Record<string, unknown> | undefined {
  return v && typeof v === "object" && !Array.isArray(v) ? (v as Record<string, unknown>) : undefined;
}

export function mapSchedule(raw: Record<string, unknown>): ExecutionSchedule {
  const projObj = asObj(raw["project"]) ?? asObj(raw["projeto"]);
  const cliObj =
    asObj(raw["client"]) ??
    asObj(raw["cliente"]) ??
    asObj(raw["customer"]) ??
    asObj(projObj?.["client"]) ??
    asObj(projObj?.["cliente"]) ??
    asObj(projObj?.["customer"]);

  const projetoNome =
    pick<string>(raw, "project_title", "projeto", "project_name", "nome_projeto", "nome", "titulo") ||
    (projObj ? pick<string>(projObj, "nome", "name", "title", "project_name", "titulo") : undefined) ||
    "";

  const clienteNome =
    pick<string>(raw, "client_name", "customer_name", "nome_cliente", "cliente", "client", "customer") ||
    (cliObj ? pick<string>(cliObj, "nome", "name", "razao_social", "fantasia", "client_name") : undefined) ||
    "";

  const cidade =
    pick<string>(raw, "cidade", "city", "local") ||
    (projObj ? pick<string>(projObj, "cidade", "city", "local") : undefined) ||
    "";

  // schedule_members: [{ user_id, member_name }]
  const scheduleMembersRaw = raw["schedule_members"];
  const scheduleMembers: { user_id: string; member_name: string }[] = Array.isArray(scheduleMembersRaw)
    ? (scheduleMembersRaw as Array<Record<string, unknown>>)
        .map((m) => ({
          user_id: (pick<string | number>(m, "user_id", "id", "userId") ?? "").toString(),
          member_name: (pick<string>(m, "member_name", "name", "nome", "user_name") ?? "").toString(),
        }))
        .filter((m) => m.member_name)
    : [];

  return {
    id: (pick<string | number>(raw, "id", "uuid", "schedule_id") ?? "").toString(),
    project_id: (
      pick<string | number>(raw, "project_id", "projeto_id", "projectId") ??
      (projObj ? pick<string | number>(projObj, "id", "uuid") : undefined) ??
      ""
    ).toString(),
    projeto: (typeof projetoNome === "string" ? projetoNome : "").toString(),
    cliente: (typeof clienteNome === "string" ? clienteNome : "").toString(),
    cidade: (typeof cidade === "string" ? cidade : "").toString(),
    endereco:
      pick<string>(raw, "endereco", "address", "logradouro") ??
      (projObj ? pick<string>(projObj, "endereco", "address", "logradouro") : undefined),
    responsavel: (pick<string>(raw, "responsavel", "owner", "assigned_to", "user_name", "tecnico", "usuario", "member_name", "leader_name") ?? "").toString(),
    data: toDateISO(pick(raw, "start_date", "data", "date", "scheduled_date", "data_execucao", "data_inicio")),
    data_fim: toDateISO(pick(raw, "end_date", "data_fim", "data_termino", "data_final", "finish_date", "data_fim_execucao")) || undefined,
    hora_inicio: toTime(pick(raw, "hora_inicio", "start_time", "start", "horario_inicio", "inicio")),
    hora_fim: toTime(pick(raw, "hora_fim", "end_time", "end", "horario_fim", "fim", "termino")),
    status: mapStatus(pick(raw, "status", "situacao")),
    descricao: pick<string>(raw, "descricao", "description", "observacao", "obs"),
    member_names: (() => {
      if (scheduleMembers.length > 0) return scheduleMembers.map((m) => m.member_name);
      const v = raw["member_names"] ?? raw["memberNames"] ?? raw["members"] ?? raw["participants"];
      if (Array.isArray(v)) return v.filter((x): x is string => typeof x === "string");
      const txt = pick<string>(raw, "member_names_text", "memberNamesText", "participants_text");
      if (txt) return txt.split(",").map((s) => s.trim()).filter(Boolean);
      const single = pick<string>(raw, "member_name");
      if (single) return [single];
      return undefined;
    })(),
    schedule_members: scheduleMembers.length > 0 ? scheduleMembers : undefined,
    leader_id: pick<string | number>(raw, "leader_id", "leaderId", "responsible_id")?.toString(),
    leader_name: pick<string>(raw, "leader_name", "leaderName", "responsible_name")?.toString(),
    notes: pick<string>(raw, "notes", "notas", "observacoes", "observacoes_agenda"),
  };
}

function scheduleUserId(raw: Record<string, unknown>): string | undefined {
  const v = pick<string | number>(
    raw,
    "user_id",
    "userId",
    "responsavel_id",
    "responsible_id",
    "assigned_to_id",
    "tecnico_id",
  );
  return v !== undefined ? v.toString() : undefined;
}

function unwrapList(payload: unknown): Record<string, unknown>[] {
  if (Array.isArray(payload)) return payload as Record<string, unknown>[];
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    for (const k of ["data", "rows", "items", "result", "results", "schedules"]) {
      const v = p[k];
      if (Array.isArray(v)) return v as Record<string, unknown>[];
    }
  }
  return [];
}

export async function fetchSchedules(userId?: string): Promise<ExecutionSchedule[]> {
  if (!USE_REAL_API) {
    return mockApi.executionSchedules();
  }
  const res = await api.get("/api/projectanalizer/execution-schedules", {
    params: userId ? { user_id: userId } : undefined,
  });
  const list = unwrapList(res.data);

  // Filtro client-side de segurança: se o backend não respeitar user_id,
  // mantemos só os agendamentos do técnico logado.
  const filtered = userId
    ? list.filter((r) => {
        const sid = scheduleUserId(r);
        return sid ? sid === userId : true;
      })
    : list;

  const mapped = filtered.map(mapSchedule).filter((s) => s.data);

  // Enriquece projeto/cliente/cidade quando vieram vazios, buscando em /api/projects.
  const needsEnrich = mapped.some((s) => s.project_id && (!s.projeto || !s.cliente));
  if (needsEnrich) {
    try {
      const projRes = await api.get("/api/projects", {
        params: userId ? { user_id: userId } : undefined,
      });
      const projects = unwrapList(projRes.data);
      const byId = new Map<string, Record<string, unknown>>();
      for (const p of projects) {
        const id = (pick<string | number>(p, "id", "uuid") ?? "").toString();
        if (id) byId.set(id, p);
      }
      for (const s of mapped) {
        if (!s.project_id) continue;
        const p = byId.get(s.project_id);
        if (!p) continue;
        if (!s.projeto) s.projeto = (pick<string>(p, "nome", "title", "name", "project_name") ?? "").toString();
        if (!s.cliente) s.cliente = (pick<string>(p, "cliente", "client", "customer") ?? "").toString();
        if (!s.cidade) s.cidade = (pick<string>(p, "cidade", "city", "local") ?? "").toString();
        if (!s.endereco) s.endereco = pick<string>(p, "endereco", "address", "logradouro");
      }
    } catch {
      // silencioso — se /api/projects falhar, mantemos o que temos
    }
  }

  return mapped;
}
