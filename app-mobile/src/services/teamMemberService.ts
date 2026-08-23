/**
 * Serviço de Team Members.
 *
 * Endpoints:
 *   GET /api/team_members
 *   GET /api/team_members/{id}
 */

import { api, USE_REAL_API } from "@/services/api";

export interface TeamMemberProfile {
  id: string;
  nome: string;
  email: string;
  telefone?: string;
  whatsapp?: string;
  cargo?: string;
  roleTitle?: string;
  dataContratacao?: string;
  ultimoAcesso?: string;
  avatarUrl?: string | null;
  ativo?: boolean;
}

function pick<T = unknown>(o: Record<string, unknown>, ...keys: string[]): T | undefined {
  for (const k of keys) {
    const v = o[k];
    if (v !== undefined && v !== null && v !== "") return v as T;
  }
  return undefined;
}

function fmtDate(v: string | undefined): string | undefined {
  if (!v) return undefined;
  const [d] = v.split("T");
  return d ?? v;
}

export function mapTeamMember(raw: Record<string, unknown>): TeamMemberProfile {
  const firstName = pick<string>(raw, "first_name") ?? "";
  const lastName = pick<string>(raw, "last_name") ?? "";
  return {
    id: (pick<string | number>(raw, "id", "uuid") ?? "").toString(),
    nome: (pick<string>(raw, "nome", "name", "full_name") ?? `${firstName} ${lastName}`).trim(),
    email: (pick<string>(raw, "email") ?? "").toString(),
    telefone: pick<string>(raw, "telefone", "phone"),
    whatsapp: pick<string>(raw, "whatsapp", "whats_app", "celular", "mobile"),
    cargo: pick<string>(raw, "cargo", "position", "job_title"),
    roleTitle: pick<string>(raw, "role_title", "role"),
    dataContratacao: fmtDate(
      pick<string>(
        raw,
        "data_contratacao",
        "data_admissao",
        "hired_at",
        "hire_date",
        "admission_date",
      ),
    ),
    ultimoAcesso: pick<string>(raw, "ultimo_acesso", "last_login", "last_access", "last_seen_at"),
    avatarUrl: pick<string>(raw, "avatar_url", "avatar", "photo_url") ?? null,
    ativo: pick<boolean>(raw, "ativo", "active", "is_active"),
  };
}

/* ---------------- sources ---------------- */

const realSource = {
  list: () =>
    api.get<unknown>("/api/team_members").then((r) => {
      if (Array.isArray(r.data)) return r.data;
      if (
        r.data &&
        typeof r.data === "object" &&
        Array.isArray((r.data as { data?: unknown }).data)
      ) {
        return (r.data as { data: unknown[] }).data;
      }
      return [];
    }),
  byId: (id: string) => api.get<unknown>(`/api/team_members/${id}`).then((r) => r.data),
};

const mockSource = {
  list: async (): Promise<Record<string, unknown>[]> => [
    {
      id: "u-mock-1",
      nome: "Henrique Oliveira",
      email: "henrique@alfahp.com.br",
      telefone: "(16) 99999-0000",
      whatsapp: "(16) 99999-0000",
      cargo: "Técnico Instalador",
      role_title: "Técnico de Campo",
      data_contratacao: "2023-03-15",
      last_login: new Date().toISOString(),
    },
  ],
  byId: async (id: string) => {
    const list = await mockSource.list();
    return list.find((m) => (m.id as string) === id) ?? list[0];
  },
};

const source = USE_REAL_API ? realSource : mockSource;

export async function fetchTeamMembers(): Promise<TeamMemberProfile[]> {
  const data = await source.list();
  return (data as Record<string, unknown>[]).map(mapTeamMember);
}

export async function fetchTeamMember(id: string): Promise<TeamMemberProfile> {
  const data = await source.byId(id);
  return mapTeamMember(data as Record<string, unknown>);
}

/**
 * Busca o perfil do usuário autenticado de forma resiliente:
 *  1. Tenta GET /api/team_members/{id}
 *  2. Em caso de falha ou retorno vazio, lista todos e filtra por e-mail.
 */
export async function fetchCurrentTeamMember(params: {
  id: string;
  email?: string;
}): Promise<TeamMemberProfile | null> {
  const { id, email } = params;

  // 1) tenta direto
  try {
    const data = await source.byId(id);
    if (data && (data as Record<string, unknown>).id !== undefined) {
      return mapTeamMember(data as Record<string, unknown>);
    }
  } catch {
    /* segue para fallback */
  }

  // 2) fallback por e-mail
  if (email) {
    try {
      const list = await fetchTeamMembers();
      const match = list.find((m) => m.email?.toLowerCase() === email.toLowerCase());
      if (match) return match;
    } catch {
      /* ignora */
    }
  }

  return null;
}
