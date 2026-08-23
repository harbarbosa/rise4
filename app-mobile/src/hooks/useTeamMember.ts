import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import {
  fetchTeamMember,
  type TeamMemberProfile,
} from "@/services/teamMemberService";

export const teamMemberKey = (id: string) => ["team-member", id] as const;

export function useTeamMember(
  id: string | undefined,
  options?: Omit<UseQueryOptions<TeamMemberProfile, ApiError>, "queryKey" | "queryFn">,
) {
  return useQuery<TeamMemberProfile, ApiError>({
    queryKey: teamMemberKey(id ?? ""),
    queryFn: () => fetchTeamMember(id!),
    enabled: !!id,
    staleTime: 60_000,
    ...options,
  });
}
