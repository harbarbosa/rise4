/**
 * Serviço de autenticação do AlfaHP Mobile.
 *
 * Backend real (Rise CRM-like):
 *   POST {AUTH_BASE_URL}/api/auth/login
 *   Content-Type: application/x-www-form-urlencoded
 *   Body: email=...&password=...
 *
 * Resposta:
 *   {
 *     status: true, message, token_type: "Bearer",
 *     token: "<jwt>", expires_at, user: {
 *       id, first_name, last_name, email, phone, image,
 *       user_type, client_id, job_title, role_id, status, is_admin
 *     }
 *   }
 */

import axios from "axios";
import { setAuthToken, getAuthToken } from "./api";
import { normalizeRole, type Permission, type Role } from "@/config/permissions";

const INTRANET_BASE_URL =
  (typeof import.meta !== "undefined" && import.meta.env?.VITE_AUTH_BASE_URL) ||
  "http://rise4.test/index.php";

export const AUTH_BASE_URL = typeof window === "undefined" ? INTRANET_BASE_URL : "";
export const AUTH_ASSET_BASE_URL = INTRANET_BASE_URL;

const USER_STORAGE_KEY = "alfahp.auth.user";
const CREDENTIALS_STORAGE_KEY = "alfahp.auth.credentials";

export interface AuthUser {
  id: string;
  nome: string;
  email: string;
  cargo: string;
  role: Role;
  role_title?: string | null;
  permissions?: Permission[] | null;
  avatar_url?: string | null;
}

export interface LoginPayload {
  email: string;
  senha: string;
}

export interface LoginResponse {
  token: string;
  user: AuthUser;
}

/** Shape bruto do usuário retornado pelo backend. */
interface RawApiUser {
  id: number | string;
  first_name?: string;
  last_name?: string;
  name?: string;
  email: string;
  phone?: string;
  image?: string | null;
  user_type?: string;
  client_id?: number | string;
  job_title?: string | null;
  role_id?: number | string;
  status?: string;
  is_admin?: number | boolean;
}

interface RawLoginResponse {
  status: boolean;
  message?: string;
  token_type?: string;
  token?: string;
  expires_at?: string;
  user?: RawApiUser;
}

function parseJsonResponse(data: unknown) {
  if (typeof data !== "string") return data;
  const text = data.replace(/^\uFEFF/, "");
  if (!text.trim()) return null;
  return JSON.parse(text);
}

/** Extrai file_name de strings PHP serializadas como:
 *  a:1:{s:9:"file_name";s:29:"_file6a2a123eb871b-avatar.png";}
 */
function extractPhpFilename(value: string | null | undefined): string | null {
  if (!value) return null;
  const m = value.match(/"file_name"[^"]*"([^"]+)"/);
  if (m?.[1]) return m[1];
  // Se não for serializado e parecer um filename/arquivo
  if (/^[_a-zA-Z0-9-]+\.\w+$/.test(value)) return value;
  // Se for uma URL completa
  if (/^https?:\/\//.test(value)) return value;
  return null;
}

/** Monta URL completa do avatar usando a base da API. */
export function buildAvatarUrl(filename: string | null | undefined): string | null {
  if (!filename) return null;
  const cleanFilename = extractPhpFilename(filename);
  if (!cleanFilename) return null;
  if (/^https?:\/\//.test(cleanFilename)) return cleanFilename;
  const base = AUTH_ASSET_BASE_URL.replace(/\/$/, "");
  return `${base}/files/profile_images/${cleanFilename}`;
}

/** Cliente Axios dedicado à autenticação. */
const authClient = axios.create({
  baseURL: AUTH_BASE_URL,
  timeout: 20_000,
  headers: { Accept: "application/json" },
  transformResponse: [parseJsonResponse],
});

function mapUser(raw: RawApiUser): AuthUser {
  const fullName =
    raw.name || [raw.first_name, raw.last_name].filter(Boolean).join(" ").trim() || raw.email;
  const isAdmin = raw.is_admin === 1 || raw.is_admin === true;
  const roleTitle = raw.job_title ?? (isAdmin ? "Admin" : (raw.user_type ?? null));
  const role: Role = isAdmin ? "admin" : normalizeRole(roleTitle);
  const filename = extractPhpFilename(raw.image);
  const avatarUrl = buildAvatarUrl(filename);

  return {
    id: String(raw.id),
    nome: fullName,
    email: raw.email,
    cargo: raw.job_title || raw.user_type || "",
    role,
    role_title: roleTitle,
    permissions: null,
    avatar_url: avatarUrl,
  };
}

export async function login(payload: LoginPayload): Promise<LoginResponse> {
  const body = new URLSearchParams();
  body.set("email", payload.email);
  body.set("password", payload.senha);

  const { data } = await authClient.post<RawLoginResponse>("/api/auth/login", body, {
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
  });

  if (!data?.status || !data.token || !data.user) {
    throw new Error(data?.message || "Credenciais inválidas.");
  }

  return { token: data.token, user: mapUser(data.user) };
}

export async function logoutRequest(): Promise<void> {
  try {
    const token = getAuthToken() ?? "";
    await authClient.post("/api/auth/logout", null, {
      headers: {
        authtoken: token,
        Authorization: `Bearer ${token}`,
      },
    });
  } catch {
    /* ignora erro de logout remoto */
  }
}

/* ---------- Persistência local ---------- */

export function saveSession(session: LoginResponse) {
  setAuthToken(session.token);
  if (typeof window !== "undefined") {
    window.localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(session.user));
  }
}

export function clearSession() {
  setAuthToken(null);
  if (typeof window !== "undefined") {
    window.localStorage.removeItem(USER_STORAGE_KEY);
  }
}

export function loadStoredUser(): AuthUser | null {
  if (typeof window === "undefined") return null;
  const raw = window.localStorage.getItem(USER_STORAGE_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as AuthUser;
  } catch {
    return null;
  }
}

export function hasStoredToken(): boolean {
  return !!getAuthToken();
}

/* ---------- Credenciais para login automático ---------- */

function encode(value: string): string {
  if (typeof window === "undefined") return value;
  try {
    return window.btoa(unescape(encodeURIComponent(value)));
  } catch {
    return value;
  }
}

function decode(value: string): string {
  if (typeof window === "undefined") return value;
  try {
    return decodeURIComponent(escape(window.atob(value)));
  } catch {
    return value;
  }
}

export function saveCredentials(payload: LoginPayload) {
  if (typeof window === "undefined") return;
  const encoded = encode(JSON.stringify(payload));
  window.localStorage.setItem(CREDENTIALS_STORAGE_KEY, encoded);
}

export function loadCredentials(): LoginPayload | null {
  if (typeof window === "undefined") return null;
  const raw = window.localStorage.getItem(CREDENTIALS_STORAGE_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(decode(raw)) as LoginPayload;
  } catch {
    return null;
  }
}

export function clearCredentials() {
  if (typeof window === "undefined") return;
  window.localStorage.removeItem(CREDENTIALS_STORAGE_KEY);
}

export function hasStoredCredentials(): boolean {
  return !!loadCredentials();
}

/** Refaz o login usando credenciais salvas. Retorna o novo token ou null. */
export async function reauthenticate(): Promise<string | null> {
  const creds = loadCredentials();
  if (!creds) return null;
  try {
    const session = await login(creds);
    saveSession(session);
    return session.token;
  } catch {
    clearCredentials();
    return null;
  }
}
