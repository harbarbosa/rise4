import axios, { AxiosError, type AxiosInstance, type InternalAxiosRequestConfig } from "axios";

/**
 * Camada de integração REST do aplicativo AlfaHP.
 *
 * Esta instância do Axios concentra:
 *  - URL base configurável via VITE_API_BASE_URL
 *  - Injeção automática do JWT Bearer Token (interceptor de request)
 *  - Tratamento centralizado de erros (interceptor de response)
 *  - Helpers para gerenciamento do token em localStorage
 *
 * Enquanto a API real não estiver disponível os hooks continuam consumindo
 * os dados mockados de src/lib/mock-data.ts. Para ligar a integração real
 * basta definir VITE_API_BASE_URL e VITE_USE_REAL_API=true.
 */

const TOKEN_STORAGE_KEY = "alfahp.auth.token";

export const API_BASE_URL =
  (typeof import.meta !== "undefined" && import.meta.env?.VITE_API_BASE_URL) || "";

/**
 * Integração real com a API REST está sempre ativa.
 * Mocks/fallbacks foram removidos — todos os dados vêm do backend.
 */
export const USE_REAL_API = true;

function parseJsonResponse(data: unknown) {
  if (typeof data !== "string") return data;
  const text = data.replace(/^\uFEFF/, "");
  if (!text.trim()) return null;
  return JSON.parse(text);
}

export function getAuthToken(): string | null {
  if (typeof window === "undefined") return null;
  return normalizeAuthToken(window.localStorage.getItem(TOKEN_STORAGE_KEY));
}

export function setAuthToken(token: string | null) {
  if (typeof window === "undefined") return;
  const normalized = normalizeAuthToken(token);
  if (normalized) {
    window.localStorage.setItem(TOKEN_STORAGE_KEY, normalized);
  } else {
    window.localStorage.removeItem(TOKEN_STORAGE_KEY);
  }
}

export const SESSION_EXPIRED_EVENT = "alfahp:session-expired";

function notifySessionExpired() {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.removeItem("alfahp.auth.user");
  } catch {
    /* ignore */
  }
  window.dispatchEvent(new CustomEvent(SESSION_EXPIRED_EVENT));
}

export class ApiError extends Error {
  status: number;
  data: unknown;
  constructor(message: string, status: number, data?: unknown) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.data = data;
  }
}

function normalizeAuthToken(token: string | null | undefined): string | null {
  const cleaned = token?.trim().replace(/^['"]|['"]$/g, "") ?? "";
  if (!cleaned) return null;
  if (cleaned.startsWith("{")) {
    try {
      const parsed = JSON.parse(cleaned) as {
        token?: string;
        access_token?: string;
        authtoken?: string;
      };
      return normalizeAuthToken(parsed.token || parsed.access_token || parsed.authtoken);
    } catch {
      return null;
    }
  }
  return cleaned.replace(/^Bearer\s+/i, "").trim() || null;
}

export const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30_000,
  headers: { "Content-Type": "application/json", Accept: "application/json" },
  transformResponse: [parseJsonResponse],
});

api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getAuthToken();
  if (token) {
    // Backend aceita ambos os formatos — enviamos os dois para máxima compatibilidade.
    config.headers.set("authtoken", token);
    config.headers.set("Authorization", `Bearer ${token}`);
  }
  return config;
});

// Refaz login automaticamente quando o token expira (401), usando credenciais salvas.
// Importação dinâmica para evitar ciclo com authService.
let refreshing: Promise<string | null> | null = null;

api.interceptors.response.use(
  async (res) => {
    const payload = res.data as { status?: unknown; message?: string; error?: string } | null;
    if (payload && typeof payload === "object" && payload.status === false) {
      const message = payload.message || payload.error || "Erro retornado pelo servidor";
      // O backend legado pode devolver `{status:false, message: "Token..."}`
      // com HTTP 200. Para a API local, "Token not found" também significa
      // que o JWT não pertence a esta instalação e precisa ser renovado.
      if (/token/i.test(message) && (res.status === 401 || res.status === 200)) {
        const original = res.config as InternalAxiosRequestConfig & { _retry?: boolean };
        if (!original._retry) {
          original._retry = true;
          let newToken: string | null = null;
          try {
            const { reauthenticate } = await import("./authService");
            if (!refreshing) {
              refreshing = reauthenticate().finally(() => {
                setTimeout(() => {
                  refreshing = null;
                }, 0);
              });
            }
            newToken = await refreshing;
          } catch {
            newToken = null;
          }
          if (newToken) {
            original.headers = original.headers ?? {};
            (original.headers as Record<string, string>).authtoken = newToken;
            (original.headers as Record<string, string>).Authorization = `Bearer ${newToken}`;
            return api.request(original);
          }
        }
        setAuthToken(null);
        notifySessionExpired();
      }
      throw new ApiError(message, res.status, payload);
    }
    return res;
  },
  async (error: AxiosError<{ message?: string; error?: string }>) => {
    const status = error.response?.status ?? 0;
    const payload = error.response?.data;
    const message =
      payload?.message || payload?.error || error.message || "Erro de comunicação com o servidor";

    const original = error.config as
      | (InternalAxiosRequestConfig & { _retry?: boolean })
      | undefined;

    if (status === 401 && original && !original._retry) {
      original._retry = true;
      let newToken: string | null = null;
      try {
        const { reauthenticate } = await import("./authService");
        if (!refreshing) {
          refreshing = reauthenticate().finally(() => {
            setTimeout(() => {
              refreshing = null;
            }, 0);
          });
        }
        newToken = await refreshing;
      } catch {
        newToken = null;
      }
      if (newToken) {
        original.headers = original.headers ?? {};
        (original.headers as Record<string, string>).authtoken = newToken;
        (original.headers as Record<string, string>).Authorization = `Bearer ${newToken}`;
        return api.request(original);
      }
      setAuthToken(null);
      notifySessionExpired();
    } else if (status === 401) {
      setAuthToken(null);
      notifySessionExpired();
    }
    return Promise.reject(new ApiError(message, status, payload));
  },
);

/* ---------- Endpoints ---------- */

import type {
  TeamMember,
  Project,
  ExecutionSchedule,
  ProjectTask,
  Timesheet,
  TimesheetInput,
} from "./types";

function unwrapList<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    for (const key of ["data", "rows", "items", "result", "results"]) {
      const v = p[key];
      if (Array.isArray(v)) return v as T[];
    }
  }
  return [];
}

function unwrapOne<T>(payload: unknown): T {
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    if ("data" in p && p.data && typeof p.data === "object") return p.data as T;
  }
  return payload as T;
}

export const endpoints = {
  teamMembers: () => api.get("/api/team_members").then((r) => unwrapList<TeamMember>(r.data)),
  projects: () => api.get("/api/projects").then((r) => unwrapList<Project>(r.data)),
  executionSchedules: () =>
    api
      .get("/api/projectanalizer/execution-schedules")
      .then((r) => unwrapList<ExecutionSchedule>(r.data)),
  projectTasks: (projectId: string) =>
    api.get(`/api/projectanalizer/tasks/${projectId}`).then((r) => unwrapList<ProjectTask>(r.data)),
  timesheets: (projectId: string) =>
    api
      .get(`/api/projectanalizer/timesheets/${projectId}`)
      .then((r) => unwrapList<Timesheet>(r.data)),
  createTimesheet: (projectId: string, payload: TimesheetInput) =>
    api
      .post(`/api/projectanalizer/timesheets/${projectId}`, payload)
      .then((r) => unwrapOne<Timesheet>(r.data)),
};
