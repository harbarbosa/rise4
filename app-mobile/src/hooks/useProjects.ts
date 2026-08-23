import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { useAuth } from "@/contexts/AuthContext";
import {
  fetchProjects,
  searchProjects,
  type ProjectListItem,
} from "@/services/projectService";

export const projectsQueryKey = (userId?: string) =>
  ["projects", userId ?? "all"] as const;
export const projectsSearchKey = (q: string, userId?: string) =>
  ["projects", "search", q, userId ?? "all"] as const;

/**
 * Quando o usuário NÃO for admin, filtra projetos pelo user_id —
 * técnicos só veem os projetos aos quais estão associados.
 */
function useScopedUserId(): string | undefined {
  const { user } = useAuth();
  if (!user) return undefined;
  return user.role === "admin" ? undefined : user.id;
}

export function useProjects(
  options?: Omit<UseQueryOptions<ProjectListItem[], ApiError>, "queryKey" | "queryFn">,
) {
  const userId = useScopedUserId();
  return useQuery<ProjectListItem[], ApiError>({
    queryKey: projectsQueryKey(userId),
    queryFn: () => fetchProjects(userId),
    staleTime: 60_000,
    ...options,
  });
}

/**
 * Busca projetos pelo endpoint GET /api/projects/search/{key}.
 * Só é executada quando `key` tem ao menos 2 caracteres.
 */
export function useProjectsSearch(
  key: string,
  options?: Omit<UseQueryOptions<ProjectListItem[], ApiError>, "queryKey" | "queryFn">,
) {
  const userId = useScopedUserId();
  const term = key.trim();
  return useQuery<ProjectListItem[], ApiError>({
    queryKey: projectsSearchKey(term, userId),
    queryFn: () => searchProjects(term, userId),
    enabled: term.length >= 2,
    staleTime: 30_000,
    ...options,
  });
}
