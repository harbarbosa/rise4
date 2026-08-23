import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { fetchProjectTasks, type TaskListItem } from "@/services/taskService";

export const projectTasksQueryKey = (projectId: string) =>
  ["project-tasks", projectId] as const;

export function useProjectTasks(
  projectId: string,
  options?: Omit<UseQueryOptions<TaskListItem[], ApiError>, "queryKey" | "queryFn">,
) {
  return useQuery<TaskListItem[], ApiError>({
    queryKey: projectTasksQueryKey(projectId),
    queryFn: () => fetchProjectTasks(projectId),
    enabled: !!projectId,
    staleTime: 60_000,
    ...options,
  });
}
