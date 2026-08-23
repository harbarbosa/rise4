/**
 * Serviço de notificações.
 *
 * Endpoints REST esperados:
 *   GET   /api/notifications?limit=&offset=&unread=
 *   GET   /api/notifications/unread-count
 *   POST  /api/notifications/{id}/read
 *   POST  /api/notifications/read-all
 *
 * Resposta esperada (listagem):
 *   {
 *     status: true,
 *     resource: "notifications",
 *     count, data: [...], unread_count, limit, offset, next_cursor, has_more
 *   }
 *
 * Se o backend ainda não expõe esses endpoints (404), o serviço retorna
 * uma lista vazia silenciosamente para não quebrar a UI.
 */

import { api, ApiError } from "./api";

export type NotificationType = "info" | "success" | "warning" | "error" | string;
export type NotificationPriority = "low" | "normal" | "high" | "urgent" | string;

export interface NotificationEntity {
  type?: string | null;
  id?: string | number | null;
}

export interface AppNotification {
  id: string;
  titulo: string;
  mensagem?: string | null;
  tipo?: NotificationType;
  categoria?: string | null;
  lida: boolean;
  criada_em?: string | null;
  criada_em_utc?: string | null;
  lida_em?: string | null;
  expira_em?: string | null;
  prioridade?: NotificationPriority | null;
  origem_usuario_id?: string | number | null;
  source?: string | null;
  link?: string | null;
  entity?: NotificationEntity | null;
}

export interface NotificationsListResult {
  items: AppNotification[];
  unreadCount: number;
  total: number;
  limit: number;
  offset: number;
  hasMore: boolean;
  nextCursor: number | string | null;
}

function asArray(data: unknown): unknown[] {
  if (Array.isArray(data)) return data;
  if (data && typeof data === "object") {
    const obj = data as Record<string, unknown>;
    if (Array.isArray(obj.data)) return obj.data as unknown[];
    if (Array.isArray(obj.items)) return obj.items as unknown[];
    if (Array.isArray(obj.notifications)) return obj.notifications as unknown[];
    if (Array.isArray(obj.results)) return obj.results as unknown[];
  }
  return [];
}

function pick<T = unknown>(obj: Record<string, unknown>, ...keys: string[]): T | undefined {
  for (const k of keys) if (obj[k] !== undefined && obj[k] !== null) return obj[k] as T;
  return undefined;
}

function toBool(v: unknown): boolean {
  return v === true || v === 1 || v === "1" || v === "true";
}

function toNum(v: unknown, fallback = 0): number {
  if (typeof v === "number" && Number.isFinite(v)) return v;
  if (typeof v === "string") {
    const n = Number(v);
    if (Number.isFinite(n)) return n;
  }
  return fallback;
}

/**
 * Mapeia o link retornado pelo backend (ex.: "projects/view/28") para
 * uma rota interna do app quando possível. Caso não haja mapeamento,
 * devolve o link cru (pode ser uma URL absoluta).
 */
export function mapBackendLink(link?: string | null, entity?: NotificationEntity | null): string | null {
  if (!link && !entity?.type) return null;

  // Remove protocolo/host (ex.: https://intranet.alfahp.com.br/index.php/projects/view/28)
  let raw = (link ?? "").trim();
  raw = raw.replace(/^https?:\/\/[^/]+\/?/i, "");
  raw = raw.replace(/^index\.php\/?/i, "");
  raw = raw.replace(/^\/+/, "");

  if (entity?.type && entity?.id != null) {
    const id = encodeURIComponent(String(entity.id));
    switch (entity.type) {
      case "project": return `/projetos/${id}`;
      case "task": return `/projetos/${id}`;
      case "expense": return `/reembolso`;
      case "trip": return `/reembolso/${id}`;
      case "announcement":
      case "aviso":
      case "notice": return `/avisos`;
      case "ponto":
      case "timeclock": return `/ponto`;
      case "schedule":
      case "atividade": return `/agenda`;
      case "pendencia": return `/pendencias`;
      case "timesheet": return `/timesheet`;
    }
  }

  if (raw) {
    let m: RegExpExecArray | null;
    if ((m = /^projects\/view\/(\d+)/.exec(raw))) return `/projetos/${m[1]}`;
    if (/^announcements?\//.test(raw)) return `/avisos`;
    if (/^expenses?\//.test(raw)) return `/reembolso`;
    if (/^timesheets?\//.test(raw)) return `/timesheet`;
    return `/${raw}`;
  }

  return null;
}

function translateTitle(title: string): string {
  if (!title) return title;
  let t = title.trim();

  let m = /^Added (.+) in a project\.?$/i.exec(t);
  if (m) return `${m[1]} adicionado ao projeto.`;
  m = /^Removed (.+) from (?:a|the) project\.?$/i.exec(t);
  if (m) return `${m[1]} removido do projeto.`;
  m = /^Assigned (.+) to (?:a|the) task\.?$/i.exec(t);
  if (m) return `${m[1]} atribuído à tarefa.`;

  const map: Record<string, string> = {
    "Created an announcement.": "Novo aviso criado.",
    "Updated an announcement.": "Aviso atualizado.",
    "Created a project.": "Novo projeto criado.",
    "Updated a project.": "Projeto atualizado.",
    "Created a task.": "Nova tarefa criada.",
    "Updated a task.": "Tarefa atualizada.",
    "Completed a task.": "Tarefa concluída.",
    "Reopened a task.": "Tarefa reaberta.",
    "Commented on a task.": "Comentou em uma tarefa.",
    "Added a comment.": "Novo comentário.",
    "Created an expense.": "Nova despesa registrada.",
    "Created an invoice.": "Nova fatura criada.",
  };
  if (map[t]) return map[t];

  t = t.replace(/\bproject\b/gi, "projeto")
       .replace(/\btask\b/gi, "tarefa")
       .replace(/\bannouncement\b/gi, "aviso")
       .replace(/\bexpense\b/gi, "despesa")
       .replace(/\binvoice\b/gi, "fatura")
       .replace(/\bCreated\b/g, "Criou")
       .replace(/\bUpdated\b/g, "Atualizou")
       .replace(/\bAdded\b/g, "Adicionou")
       .replace(/\bRemoved\b/g, "Removeu")
       .replace(/\bAssigned\b/g, "Atribuiu")
       .replace(/\bCompleted\b/g, "Concluiu")
       .replace(/\bReopened\b/g, "Reabriu")
       .replace(/\bCommented on\b/gi, "Comentou em");
  return t;
}

function normalize(raw: unknown, idx: number): AppNotification {
  const o = (raw && typeof raw === "object" ? raw : {}) as Record<string, unknown>;
  const id = String(pick(o, "id", "uuid", "_id") ?? `n-${idx}`);
  const titulo = translateTitle(String(pick(o, "title", "titulo", "subject", "name") ?? "Notificação"));
  const mensagem = (pick(o, "message", "mensagem", "body", "description", "text") as string | undefined) ?? null;
  const lida = toBool(pick(o, "read", "lida", "is_read", "seen"));
  const criada_em = (pick(o, "created_at", "criada_em", "createdAt", "data", "timestamp") as string | undefined) ?? null;
  const criada_em_utc = (pick(o, "created_at_utc", "createdAtUtc") as string | undefined) ?? null;
  const lida_em = (pick(o, "read_at", "lida_em", "readAt") as string | undefined) ?? null;
  const expira_em = (pick(o, "expires_at", "expira_em", "expiresAt") as string | undefined) ?? null;
  const tipo = (pick(o, "type", "tipo", "level", "severity") as string | undefined) ?? "info";
  const categoria = (pick(o, "category", "categoria") as string | undefined) ?? null;
  const prioridade = (pick(o, "priority", "prioridade") as string | undefined) ?? null;
  const origem_usuario_id = (pick(o, "origin_user_id", "originUserId") as string | number | undefined) ?? null;
  const source = (pick(o, "source") as string | undefined) ?? null;

  const entityRaw = pick<Record<string, unknown>>(o, "entity");
  const entity: NotificationEntity | null = entityRaw
    ? {
        type: (entityRaw.type as string | undefined) ?? null,
        id: (entityRaw.id as string | number | undefined) ?? null,
      }
    : null;

  const linkRaw = (pick(o, "link", "url", "href", "action_url") as string | undefined) ?? null;
  const link = mapBackendLink(linkRaw, entity);

  return {
    id,
    titulo,
    mensagem,
    tipo,
    categoria,
    lida,
    criada_em,
    criada_em_utc,
    lida_em,
    expira_em,
    prioridade,
    origem_usuario_id,
    source,
    link,
    entity,
  };
}

const NOTIFICATIONS_DISABLED_KEY = "alfahp.notifications.disabled";

function markDisabled() {
  try { window.sessionStorage.setItem(NOTIFICATIONS_DISABLED_KEY, "1"); } catch { /* ignore */ }
}
function isDisabled(): boolean {
  try { return window.sessionStorage.getItem(NOTIFICATIONS_DISABLED_KEY) === "1"; } catch { return false; }
}

function isNotImplemented(err: unknown): boolean {
  const status = err instanceof ApiError ? err.status : (err as { response?: { status?: number } })?.response?.status;
  return status === 404 || status === 405 || status === 501;
}

function emptyResult(): NotificationsListResult {
  return { items: [], unreadCount: 0, total: 0, limit: 0, offset: 0, hasMore: false, nextCursor: null };
}

export interface ListNotificationsOptions {
  limit?: number;
  offset?: number;
  unread?: boolean;
}

export const notificationService = {
  async list(opts: ListNotificationsOptions = {}): Promise<NotificationsListResult> {
    if (typeof window !== "undefined" && isDisabled()) return emptyResult();

    const params = new URLSearchParams();
    if (opts.limit != null) params.set("limit", String(opts.limit));
    if (opts.offset != null) params.set("offset", String(opts.offset));
    if (opts.unread) params.set("unread", "1");
    const qs = params.toString();
    const url = qs ? `/api/notifications?${qs}` : `/api/notifications`;

    try {
      const res = await api.get<unknown>(url);
      const body = res.data as Record<string, unknown> | undefined;
      const items = asArray(body).map(normalize);
      const unreadCount = toNum(pick(body ?? {}, "unread_count", "unreadCount"), items.filter((n) => !n.lida).length);
      const total = toNum(pick(body ?? {}, "count", "total"), items.length);
      const limit = toNum(pick(body ?? {}, "limit"), opts.limit ?? items.length);
      const offset = toNum(pick(body ?? {}, "offset"), opts.offset ?? 0);
      const hasMore = toBool(pick(body ?? {}, "has_more", "hasMore"));
      const nextCursor = (pick(body ?? {}, "next_cursor", "nextCursor") as number | string | undefined) ?? null;
      return { items, unreadCount, total, limit, offset, hasMore, nextCursor };
    } catch (err) {
      if (isNotImplemented(err)) {
        markDisabled();
        return emptyResult();
      }
      throw err;
    }
  },

  async unreadCount(): Promise<number> {
    if (typeof window !== "undefined" && isDisabled()) return 0;
    try {
      const res = await api.get<unknown>(`/api/notifications/unread-count`);
      const body = res.data as Record<string, unknown> | undefined;
      const data = (body?.data as Record<string, unknown> | undefined) ?? body ?? {};
      return toNum(pick(data, "count", "unread_count"), 0);
    } catch (err) {
      if (isNotImplemented(err)) {
        markDisabled();
        return 0;
      }
      throw err;
    }
  },

  async markRead(id: string): Promise<{ unreadCount: number | null; notification: AppNotification | null }> {
    try {
      const res = await api.post<unknown>(`/api/notifications/${encodeURIComponent(id)}/read`);
      const body = res.data as Record<string, unknown> | undefined;
      const data = (body?.data as Record<string, unknown> | undefined) ?? {};
      const unreadRaw = pick(data, "unread_count", "unreadCount");
      const notifRaw = pick<Record<string, unknown>>(data, "notification");
      return {
        unreadCount: unreadRaw != null ? toNum(unreadRaw, 0) : null,
        notification: notifRaw ? normalize(notifRaw, 0) : null,
      };
    } catch {
      // silencioso: não bloquear a UX se o endpoint não existir
      return { unreadCount: null, notification: null };
    }
  },

  async markAllRead(): Promise<{ unreadCount: number | null }> {
    try {
      const res = await api.post<unknown>(`/api/notifications/read-all`);
      const body = res.data as Record<string, unknown> | undefined;
      const data = (body?.data as Record<string, unknown> | undefined) ?? {};
      const unreadRaw = pick(data, "unread_count", "unreadCount");
      return { unreadCount: unreadRaw != null ? toNum(unreadRaw, 0) : 0 };
    } catch {
      return { unreadCount: null };
    }
  },
};
