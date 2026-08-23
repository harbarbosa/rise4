import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import {
  updateTimesheet,
  patchTimesheet,
  deleteTimesheet,
} from "@/services/timesheetService";
import type { Timesheet, TimesheetInput } from "@/services/types";
import { timesheetsQueryKey } from "@/hooks/useTimesheets";

interface UpdateArgs {
  projectId: string;
  id: string;
  payload: TimesheetInput;
}
interface PatchArgs {
  projectId: string;
  id: string;
  payload: Partial<TimesheetInput>;
}
interface DeleteArgs {
  projectId: string;
  id: string;
}

export function useUpdateTimesheet() {
  const qc = useQueryClient();
  return useMutation<Timesheet, ApiError, UpdateArgs>({
    mutationFn: ({ projectId, id, payload }) =>
      updateTimesheet(projectId, id, payload),
    onSuccess: (_d, { projectId }) => {
      qc.invalidateQueries({ queryKey: timesheetsQueryKey(projectId) });
      qc.invalidateQueries({ queryKey: ["project-tasks", projectId] });
    },
  });
}

export function usePatchTimesheet() {
  const qc = useQueryClient();
  return useMutation<Timesheet, ApiError, PatchArgs>({
    mutationFn: ({ projectId, id, payload }) =>
      patchTimesheet(projectId, id, payload),
    onSuccess: (_d, { projectId }) => {
      qc.invalidateQueries({ queryKey: timesheetsQueryKey(projectId) });
    },
  });
}

export function useDeleteTimesheet() {
  const qc = useQueryClient();
  return useMutation<void, ApiError, DeleteArgs>({
    mutationFn: ({ projectId, id }) => deleteTimesheet(projectId, id),
    onSuccess: (_d, { projectId }) => {
      qc.invalidateQueries({ queryKey: timesheetsQueryKey(projectId) });
    },
  });
}
