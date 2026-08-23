import type { ReactNode } from "react";
import { useAuth } from "@/contexts/AuthContext";
import {
  hasPermission,
  hasAnyPermission,
  hasAllPermissions,
  type Permission,
  type Role,
} from "@/config/permissions";

/**
 * Hook conveniente para checagem de permissões na UI.
 *
 * Uso:
 *   const { can, role } = useCan();
 *   if (can("despesas.aprovar")) { ... }
 */
export function useCan() {
  const { user } = useAuth();
  const role = user?.role ?? null;
  const explicit = user?.permissions ?? null;

  return {
    role,
    user,
    can: (permission: Permission) => hasPermission(role, permission, explicit),
    canAny: (permissions: Permission[]) => hasAnyPermission(role, permissions, explicit),
    canAll: (permissions: Permission[]) => hasAllPermissions(role, permissions, explicit),
    isRole: (r: Role) => role === r,
  };
}

interface CanProps {
  /** Permissão única exigida. */
  permission?: Permission;
  /** Lista de permissões — exige PELO MENOS UMA (any). */
  anyOf?: Permission[];
  /** Lista de permissões — exige TODAS. */
  allOf?: Permission[];
  /** Restrição direta por papel. */
  role?: Role | Role[];
  /** Conteúdo exibido quando a checagem passa. */
  children: ReactNode;
  /** Conteúdo alternativo exibido quando a checagem falha. */
  fallback?: ReactNode;
}

/**
 * Renderiza `children` apenas quando o usuário atual satisfaz a regra
 * declarada. Use para esconder botões, itens de menu, blocos inteiros, etc.
 *
 * Exemplos:
 *   <Can permission="despesas.aprovar"><Button>Aprovar</Button></Can>
 *   <Can anyOf={["timesheets.aprovar", "pendencias.aprovar"]}>...</Can>
 *   <Can role="admin">Painel administrativo</Can>
 */
export function Can({
  permission,
  anyOf,
  allOf,
  role,
  children,
  fallback = null,
}: CanProps) {
  const { can, canAny, canAll, role: currentRole } = useCan();

  let allowed = true;

  if (permission && !can(permission)) allowed = false;
  if (allowed && anyOf && !canAny(anyOf)) allowed = false;
  if (allowed && allOf && !canAll(allOf)) allowed = false;
  if (allowed && role) {
    const list = Array.isArray(role) ? role : [role];
    if (!currentRole || !list.includes(currentRole)) allowed = false;
  }

  return <>{allowed ? children : fallback}</>;
}
