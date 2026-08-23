/**
 * Estrutura de permissões do AlfaHP Mobile (front-end).
 *
 * Mesmo que o backend ainda não retorne uma lista detalhada de permissões,
 * derivamos o conjunto de permissões a partir do `role` do usuário logado
 * (campo `role_title` na API). Quando o backend passar a enviar permissões
 * explícitas, basta repassá-las para `hasPermission` que elas terão prioridade
 * sobre o mapa derivado do papel.
 */

export type Role = "admin" | "supervisor" | "tecnico" | "financeiro";

export const ROLES: Role[] = ["admin", "supervisor", "tecnico", "financeiro"];

/** Catálogo único de permissões usadas no app. */
export type Permission =
  // Agenda
  | "agenda.ver"
  | "agenda.ver_equipe"
  // Projetos
  | "projetos.ver"
  | "projetos.ver_atribuidos"
  | "projetos.editar"
  // Tarefas
  | "tarefas.ver"
  | "tarefas.editar"
  // Apontamentos / Timesheets
  | "atividades.lancar"
  | "timesheets.ver"
  | "timesheets.aprovar"
  // Despesas
  | "despesas.lancar"
  | "despesas.ver"
  | "despesas.aprovar"
  // Pendências
  | "pendencias.ver"
  | "pendencias.aprovar"
  // Ordens de serviço / atendimento em campo
  | "ordens.ver"
  | "ordens.atender"
  // Administração
  | "usuarios.gerenciar"
  | "config.gerenciar";

/** Curinga: papel com acesso total. */
const ALL = "*" as const;

/**
 * Mapa Role -> permissões.
 * `"*"` significa acesso total (qualquer permissão é concedida).
 */
export const ROLE_PERMISSIONS: Record<Role, Permission[] | typeof ALL> = {
  admin: ALL,

  tecnico: [
    "agenda.ver",
    "projetos.ver_atribuidos",
    "tarefas.ver",
    "atividades.lancar",
    "despesas.lancar",
    "despesas.ver",
    "pendencias.ver",
    "ordens.ver",
    "ordens.atender",
  ],

  supervisor: [
    "agenda.ver",
    "agenda.ver_equipe",
    "projetos.ver",
    "projetos.ver_atribuidos",
    "tarefas.ver",
    "tarefas.editar",
    "timesheets.ver",
    "timesheets.aprovar",
    "pendencias.ver",
    "pendencias.aprovar",
    "atividades.lancar",
    "ordens.ver",
    "ordens.atender",
  ],

  financeiro: ["despesas.ver", "despesas.aprovar", "pendencias.ver"],
};

/** Normaliza o `role_title` recebido do backend para um {@link Role}. */
export function normalizeRole(input?: string | null): Role {
  const v = (input ?? "").toString().trim().toLowerCase();
  if (!v) return "tecnico";
  if (v.includes("admin")) return "admin";
  if (v.includes("super") || v.includes("coord") || v.includes("gerente")) return "supervisor";
  if (v.includes("financ") || v.includes("rh") || v.includes("contab")) return "financeiro";
  return "tecnico";
}

/**
 * Verifica se um usuário (definido por seu role + lista opcional de permissões
 * explícitas vindas do backend) possui uma dada permissão.
 */
export function hasPermission(
  role: Role | undefined | null,
  permission: Permission,
  explicit?: Permission[] | null,
): boolean {
  if (explicit && explicit.includes(permission)) return true;
  if (!role) return false;
  const map = ROLE_PERMISSIONS[role];
  if (map === ALL) return true;
  return map.includes(permission);
}

export function hasAnyPermission(
  role: Role | undefined | null,
  permissions: Permission[],
  explicit?: Permission[] | null,
): boolean {
  return permissions.some((p) => hasPermission(role, p, explicit));
}

export function hasAllPermissions(
  role: Role | undefined | null,
  permissions: Permission[],
  explicit?: Permission[] | null,
): boolean {
  return permissions.every((p) => hasPermission(role, p, explicit));
}

export const ROLE_LABEL: Record<Role, string> = {
  admin: "Administrador",
  supervisor: "Supervisor",
  tecnico: "Técnico de Campo",
  financeiro: "Financeiro",
};
