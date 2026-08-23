import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { fetchProject, type ProjectListItem } from "@/services/projectService";

export const projectQueryKey = (id: string) => ["projects", id] as const;

export function useProject(
  id: string,
  options?: Omit<UseQueryOptions<ProjectListItem, ApiError>, "queryKey" | "queryFn">,
) {
  return useQuery<ProjectListItem, ApiError>({
    queryKey: projectQueryKey(id),
    queryFn: () => fetchProject(id),
    enabled: !!id,
    staleTime: 60_000,
    ...options,
  });
}
