import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import { createTimesheet } from "@/services/timesheetService";
import type { Timesheet, TimesheetInput } from "@/services/types";
import { timesheetsQueryKey } from "@/hooks/useTimesheets";

interface CreateArgs {
  projectId: string;
  payload: TimesheetInput;
}

export function useCreateTimesheet() {
  const qc = useQueryClient();
  return useMutation<Timesheet, ApiError, CreateArgs>({
    mutationFn: ({ projectId, payload }) => createTimesheet(projectId, payload),
    onSuccess: (_d, { projectId }) => {
      qc.invalidateQueries({ queryKey: timesheetsQueryKey(projectId) });
      qc.invalidateQueries({ queryKey: ["project-tasks", projectId] });
    },
  });
}
