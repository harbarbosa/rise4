import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { fetchProjectMilestones, type MilestoneItem } from "@/services/milestoneService";

export const projectMilestonesQueryKey = (projectId: string) =>
  ["project-milestones", projectId] as const;

export function useProjectMilestones(
  projectId: string,
  options?: Omit<UseQueryOptions<MilestoneItem[], ApiError>, "queryKey" | "queryFn">,
) {
  return useQuery<MilestoneItem[], ApiError>({
    queryKey: projectMilestonesQueryKey(projectId),
    queryFn: () => fetchProjectMilestones(projectId),
    enabled: !!projectId,
    staleTime: 60_000,
    ...options,
  });
}
