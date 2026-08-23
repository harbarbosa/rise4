/**
 * Hooks customizados de acesso a dados.
 *
 * Cada hook encapsula a chamada via Axios (endpoints reais) ou via mockApi,
 * de acordo com a flag USE_REAL_API. Toda a integração já está pronta para
 * trocar de fonte sem alterar componentes.
 */

import {
  useMutation,
  useQuery,
  useQueryClient,
  type UseMutationOptions,
  type UseQueryOptions,
} from "@tanstack/react-query";
import { endpoints, USE_REAL_API, ApiError } from "@/services/api";
import { mockApi } from "@/services/mock-adapter";
import { fetchSchedules } from "@/services/scheduleService";
import { useAuth } from "@/contexts/AuthContext";
import type {
  ExecutionSchedule,
  Project,
  ProjectTask,
  TeamMember,
  Timesheet,
  TimesheetInput,
} from "@/services/types";

const src = USE_REAL_API ? endpoints : mockApi;

export const queryKeys = {
  teamMembers: ["team-members"] as const,
  projects: ["projects"] as const,
  schedules: ["execution-schedules"] as const,
  tasks: (projectId: string) => ["project-tasks", projectId] as const,
  timesheets: (projectId: string) => ["timesheets", projectId] as const,
};

type QOpts<T> = Omit<UseQueryOptions<T, ApiError>, "queryKey" | "queryFn">;

export function useTeamMembers(options?: QOpts<TeamMember[]>) {
  return useQuery<TeamMember[], ApiError>({
    queryKey: queryKeys.teamMembers,
    queryFn: () => src.teamMembers(),
    staleTime: 5 * 60_000,
    ...options,
  });
}

export function useProjects(options?: QOpts<Project[]>) {
  return useQuery<Project[], ApiError>({
    queryKey: queryKeys.projects,
    queryFn: () => src.projects(),
    staleTime: 60_000,
    ...options,
  });
}

export function useExecutionSchedules(options?: QOpts<ExecutionSchedule[]>) {
  const { user } = useAuth();
  const userId = user?.role === "admin" ? undefined : user?.id;
  return useQuery<ExecutionSchedule[], ApiError>({
    queryKey: [...queryKeys.schedules, userId ?? "all"],
    queryFn: () => fetchSchedules(userId),
    staleTime: 60_000,
    ...options,
  });
}

export function useProjectTasks(projectId: string, options?: QOpts<ProjectTask[]>) {
  return useQuery<ProjectTask[], ApiError>({
    queryKey: queryKeys.tasks(projectId),
    queryFn: () => src.projectTasks(projectId),
    enabled: !!projectId,
    staleTime: 30_000,
    ...options,
  });
}

export function useTimesheets(projectId: string, options?: QOpts<Timesheet[]>) {
  return useQuery<Timesheet[], ApiError>({
    queryKey: queryKeys.timesheets(projectId),
    queryFn: () => src.timesheets(projectId),
    enabled: !!projectId,
    ...options,
  });
}

export function useCreateTimesheet(
  projectId: string,
  options?: UseMutationOptions<Timesheet, ApiError, TimesheetInput>,
) {
  const qc = useQueryClient();
  return useMutation<Timesheet, ApiError, TimesheetInput>({
    mutationFn: (payload) => src.createTimesheet(projectId, payload),
    onSuccess: (...args) => {
      qc.invalidateQueries({ queryKey: queryKeys.timesheets(projectId) });
      options?.onSuccess?.(...args);
    },
    ...options,
  });
}
