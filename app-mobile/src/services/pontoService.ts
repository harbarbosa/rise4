/**
 * Serviço da API PontoRH.
 *
 * Todos os endpoints rodam por baixo do mesmo RestApi (header authtoken
 * já é injetado automaticamente pelo interceptor em src/services/api.ts).
 *
 * Documentação de referência: seção 15 — PontoRH mobile API.
 */

import { api, getAuthToken } from "@/services/api";

export type PunchType = "entrada" | "saida_intervalo" | "retorno_intervalo" | "saida";

export type PontoStatus = "nao_iniciado" | "em_trabalho" | "em_intervalo" | "encerrado" | string;

export interface WorkSchedule {
  id: number;
  name: string;
  schedule_type: string;
  start_time: string;
  end_time: string;
  break_minutes: number;
  tolerance_minutes: number;
  extra_tolerance_minutes: number;
  bank_hours: number;
  active: number;
}

export interface PontoMe {
  id: number;
  team_member_id: number;
  name: string;
  job_title: string;
  photo?: string | null;
  role?: string | null;
  work_schedule?: WorkSchedule | null;
  current_status: PontoStatus;
  last_record?: {
    id: number;
    punch_type: string;
    punch_time: string;
  } | null;
}

export interface PontoStatusData {
  status: PontoStatus;
  entry_recorded_at: string | null;
  interval_started_at: string | null;
  interval_finished_at: string | null;
  exit_recorded_at: string | null;
  worked_minutes: number;
  worked_hours: string;
  remaining_minutes: number;
  remaining_hours: string;
  bank_minutes: number;
  bank_hours: string;
  extra_minutes: number;
  extra_hours: string;
  late_minutes: number;
  late_hours: string;
  entries: string[];
  exits: string[];
  next_expected_action: PunchType | string | null;
}

export interface PontoPunch {
  id: number;
  type: PunchType | string;
  time: string;
  date: string;
  latitude?: string | null;
  longitude?: string | null;
  status: string;
  source?: string | null;
  location?: string | null;
  team_member_name?: string;
}

export interface PontoDashboard {
  status: PontoStatus;
  worked_hours: string;
  remaining_hours: string;
  bank_hours: string;
  pending_adjustments: number;
  next_expected_action: PunchType | string | null;
  last_record?: Record<string, unknown>;
}

export interface CheckinPayload {
  latitude: string;
  longitude: string;
  device_id?: string;
  device_name?: string;
  battery_level?: number;
  photo?: string;
}

export interface AdjustmentRequest {
  id: number;
  date: string;
  requested_time: string;
  requested_type: string;
  reason: string;
  status: string;
  approver?: string;
}

export interface AdjustmentPayload {
  record_date: string;
  requested_time: string;
  requested_type: PunchType;
  reason: string;
}

/* ---------- helpers ---------- */

interface EnvelopedList<T> {
  status: boolean;
  data?: T[];
  count?: number;
}
interface EnvelopedOne<T> {
  status: boolean;
  data?: T;
  message?: string;
  id?: number;
}

function unwrapList<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  const env = payload as EnvelopedList<T> | undefined;
  return env?.data ?? [];
}

function unwrapOne<T>(payload: unknown): T | null {
  const env = payload as EnvelopedOne<T> | undefined;
  if (env && typeof env === "object" && "data" in env) return env.data ?? null;
  return (payload as T) ?? null;
}

function pontoHeaders() {
  const token = getAuthToken();
  return token ? { headers: { authtoken: token, Authorization: `Bearer ${token}` } } : undefined;
}

/* ---------- endpoints ---------- */

export async function fetchPontoMe(): Promise<PontoMe | null> {
  const { data } = await api.get("/api/pontorh/me");
  return unwrapOne<PontoMe>(data);
}

export async function fetchPontoStatus(): Promise<PontoStatusData | null> {
  const { data } = await api.get("/api/pontorh/status");
  return unwrapOne<PontoStatusData>(data);
}

/** Data local (YYYY-MM-DD) — evita o shift de timezone do toISOString(). */
export function localDateISO(d: Date = new Date()): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

export async function fetchPontoToday(): Promise<PontoPunch[]> {
  // Passa a data local para o backend evitar que ele use UTC e considere o dia seguinte.
  const date = localDateISO();
  const { data } = await api.get("/api/pontorh/today", { params: { date } });
  const list = unwrapList<PontoPunch>(data);
  // Defesa extra: filtra por data local caso o backend ignore o parâmetro.
  const filtered = list.filter((p) => !p.date || p.date === date);
  return filtered.length ? filtered : list;
}

export async function fetchPontoMonth(
  month: number,
  year: number,
): Promise<{ summary: Record<string, unknown>; data: unknown[] }> {
  const { data } = await api.get("/api/pontorh/month", {
    params: { month, year },
  });
  const env = (data ?? {}) as { summary?: Record<string, unknown>; data?: unknown[] };
  return { summary: env.summary ?? {}, data: env.data ?? [] };
}

export async function fetchPontoHistory(startDate: string, endDate: string): Promise<unknown[]> {
  const { data } = await api.get("/api/pontorh/history", {
    params: { start_date: startDate, end_date: endDate },
  });
  return unwrapList<unknown>(data);
}

export async function fetchPontoDashboard(): Promise<PontoDashboard | null> {
  const { data } = await api.get("/api/pontorh/dashboard");
  return unwrapOne<PontoDashboard>(data);
}

export async function registerCheckin(payload: CheckinPayload): Promise<PontoPunch | null> {
  const form = new URLSearchParams();
  Object.entries(payload).forEach(([k, v]) => {
    if (v !== undefined && v !== null) form.append(k, String(v));
  });
  const base = pontoHeaders();
  const { data } = await api.post("/api/pontorh/checkin", form.toString(), {
    ...(base ?? {}),
    headers: {
      ...(base?.headers ?? {}),
      "Content-Type": "application/x-www-form-urlencoded",
    },
  });
  return unwrapOne<PontoPunch>(data);
}

export async function fetchAdjustments(): Promise<AdjustmentRequest[]> {
  const { data } = await api.get("/api/pontorh/adjustments");
  return unwrapList<AdjustmentRequest>(data);
}

export async function createAdjustment(
  payload: AdjustmentPayload,
): Promise<{ id?: number; message?: string }> {
  const { data } = await api.post("/api/pontorh/adjustments", payload);
  return (data ?? {}) as { id?: number; message?: string };
}

export async function registerDevice(payload: {
  device_id: string;
  device_name: string;
  platform: string;
  app_version: string;
}): Promise<{ id?: number; message?: string }> {
  const { data } = await api.post("/api/pontorh/device/register", payload);
  return (data ?? {}) as { id?: number; message?: string };
}

/* ---------- utilidades ---------- */

export const PUNCH_LABEL: Record<PunchType, string> = {
  entrada: "Entrada",
  saida_intervalo: "Saída p/ intervalo",
  retorno_intervalo: "Retorno do intervalo",
  saida: "Saída",
};

/** Aliases vindos do backend (en) → chave canônica (pt). */
const PUNCH_TYPE_ALIASES: Record<string, PunchType> = {
  in: "entrada",
  check_in: "entrada",
  checkin: "entrada",
  entrada: "entrada",
  out: "saida",
  check_out: "saida",
  checkout: "saida",
  saida: "saida",
  lunch_out: "saida_intervalo",
  break_out: "saida_intervalo",
  saida_intervalo: "saida_intervalo",
  lunch_return: "retorno_intervalo",
  lunch_in: "retorno_intervalo",
  break_return: "retorno_intervalo",
  retorno_intervalo: "retorno_intervalo",
};

export function normalizePunchType(t?: string | null): PunchType | null {
  if (!t) return null;
  return PUNCH_TYPE_ALIASES[t.toLowerCase()] ?? null;
}

export function translatePunchType(t?: string | null): string {
  const norm = normalizePunchType(t);
  if (norm) return PUNCH_LABEL[norm];
  return t ?? "—";
}

const PUNCH_STATUS_PT: Record<string, string> = {
  valid: "Válida",
  invalid: "Inválida",
  pending: "Pendente",
  approved: "Aprovada",
  rejected: "Rejeitada",
  regular: "Regular",
  late: "Atraso",
  early: "Antecipada",
  adjusted: "Ajustada",
  manual: "Manual",
  outside_area: "Fora da área",
  inside_area: "Dentro da área",
  on_time: "No horário",
  ok: "OK",
};

export function translatePunchStatus(s?: string | null): string {
  if (!s) return "";
  return PUNCH_STATUS_PT[s.toLowerCase()] ?? s;
}

export const STATUS_LABEL: Record<string, string> = {
  nao_iniciado: "Não iniciado",
  em_trabalho: "Em trabalho",
  em_intervalo: "Em intervalo",
  encerrado: "Encerrado",
  not_started: "Não iniciado",
  working: "Em trabalho",
  on_break: "Em intervalo",
  finished: "Encerrado",
};

/** Solicita a geolocalização do dispositivo. */
export function getCurrentCoords(): Promise<{ latitude: string; longitude: string }> {
  return new Promise((resolve, reject) => {
    if (typeof navigator === "undefined" || !navigator.geolocation) {
      reject(new Error("Geolocalização não disponível neste dispositivo."));
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) =>
        resolve({
          latitude: pos.coords.latitude.toFixed(6),
          longitude: pos.coords.longitude.toFixed(6),
        }),
      (err) => reject(new Error(err.message || "Falha ao obter GPS.")),
      { enableHighAccuracy: true, timeout: 15_000, maximumAge: 30_000 },
    );
  });
}
