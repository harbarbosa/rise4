/**
 * Serviço de Timesheets (Lançamento de Atividades) do ProjectAnalizer.
 *
 * Endpoints reais:
 *   GET    /api/projectanalizer/timesheets/{project_id}
 *   POST   /api/projectanalizer/timesheets/{project_id}
 *   PUT    /api/projectanalizer/timesheets/{project_id}/{id}
 *   PATCH  /api/projectanalizer/timesheets/{project_id}/{id}
 *   DELETE /api/projectanalizer/timesheets/{project_id}/{id}
 *
 * Regras de payload:
 *  - `user_id` é obrigatório.
 *  - É obrigatório enviar `start_time` + `end_time` OU `hours`.
 *  - Se `task_id` for enviado, `percentage_executed` é obrigatório.
 */

import { api, USE_REAL_API, ApiError } from "@/services/api";
import { mockApi } from "@/services/mock-adapter";
import type { Timesheet, TimesheetInput } from "@/services/types";

export type { Timesheet, TimesheetInput };

export function validateTimesheetPayload(payload: TimesheetInput): void {
  if (!payload.user_id) {
    throw new ApiError("user_id é obrigatório no lançamento.", 400);
  }
  const hasInterval = Boolean(payload.start_time && payload.end_time);
  const hasHours = typeof payload.hours === "number" && payload.hours > 0;
  if (!hasInterval && !hasHours) {
    throw new ApiError(
      "Informe (start_time + end_time) ou hours para o lançamento.",
      400,
    );
  }
  if (
    payload.task_id &&
    (payload.percentage_executed === undefined || payload.percentage_executed === null)
  ) {
    throw new ApiError(
      "percentage_executed é obrigatório quando task_id é informado.",
      400,
    );
  }
}

const base = (projectId: string) =>
  `/api/projectanalizer/timesheets/${encodeURIComponent(projectId)}`;

const realSource = {
  list: (projectId: string) =>
    api.get<Timesheet[]>(base(projectId)).then((r) => r.data),
  create: (projectId: string, payload: TimesheetInput) =>
    api.post<Timesheet>(base(projectId), payload).then((r) => r.data),
  update: (projectId: string, id: string, payload: TimesheetInput) =>
    api
      .put<Timesheet>(`${base(projectId)}/${encodeURIComponent(id)}`, payload)
      .then((r) => r.data),
  patch: (projectId: string, id: string, payload: Partial<TimesheetInput>) =>
    api
      .patch<Timesheet>(`${base(projectId)}/${encodeURIComponent(id)}`, payload)
      .then((r) => r.data),
  remove: (projectId: string, id: string) =>
    api.delete<void>(`${base(projectId)}/${encodeURIComponent(id)}`).then(() => undefined),
};

const mockSource = {
  list: (projectId: string) => mockApi.timesheets(projectId),
  create: (projectId: string, payload: TimesheetInput) =>
    mockApi.createTimesheet(projectId, payload),
  update: async (projectId: string, id: string, payload: TimesheetInput): Promise<Timesheet> => {
    const created = await mockApi.createTimesheet(projectId, payload);
    return { ...created, id };
  },
  patch: async (
    projectId: string,
    id: string,
    payload: Partial<TimesheetInput>,
  ): Promise<Timesheet> => {
    const created = await mockApi.createTimesheet(projectId, {
      user_id: payload.user_id ?? "mock-user",
      ...payload,
    });
    return { ...created, id };
  },
  remove: async (_projectId: string, _id: string) => {
    /* noop no modo mock */
  },
};

const source = USE_REAL_API ? realSource : mockSource;

export async function fetchTimesheets(projectId: string): Promise<Timesheet[]> {
  if (!projectId) throw new ApiError("ID do projeto não informado", 400);
  return (await source.list(projectId)) ?? [];
}

export async function createTimesheet(
  projectId: string,
  payload: TimesheetInput,
): Promise<Timesheet> {
  if (!projectId) throw new ApiError("ID do projeto não informado", 400);
  validateTimesheetPayload(payload);
  return source.create(projectId, payload);
}

export async function updateTimesheet(
  projectId: string,
  id: string,
  payload: TimesheetInput,
): Promise<Timesheet> {
  if (!projectId || !id) throw new ApiError("Parâmetros inválidos para atualização", 400);
  validateTimesheetPayload(payload);
  return source.update(projectId, id, payload);
}

export async function patchTimesheet(
  projectId: string,
  id: string,
  payload: Partial<TimesheetInput>,
): Promise<Timesheet> {
  if (!projectId || !id) throw new ApiError("Parâmetros inválidos para atualização", 400);
  if (
    payload.task_id &&
    (payload.percentage_executed === undefined || payload.percentage_executed === null)
  ) {
    throw new ApiError(
      "percentage_executed é obrigatório quando task_id é informado.",
      400,
    );
  }
  return source.patch(projectId, id, payload);
}

export async function deleteTimesheet(projectId: string, id: string): Promise<void> {
  if (!projectId || !id) throw new ApiError("Parâmetros inválidos para exclusão", 400);
  return source.remove(projectId, id);
}
