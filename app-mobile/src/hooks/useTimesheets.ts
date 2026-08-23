import { useQuery, type UseQueryOptions } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { fetchTimesheets } from "@/services/timesheetService";
import type { Timesheet } from "@/services/types";

export const timesheetsQueryKey = (projectId: string) =>
  ["timesheets", projectId] as const;

export function useTimesheets(
  projectId: string,
  options?: Omit<UseQueryOptions<Timesheet[], ApiError>, "queryKey" | "queryFn">,
) {
  return useQuery<Timesheet[], ApiError>({
    queryKey: timesheetsQueryKey(projectId),
    queryFn: () => fetchTimesheets(projectId),
    enabled: Boolean(projectId),
    staleTime: 60_000,
    ...options,
  });
}
