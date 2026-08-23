/**
 * Adapter que converte os dados mockados de src/lib/mock-data.ts
 * para o formato esperado pela API REST.
 *
 * É usado pelos hooks enquanto VITE_USE_REAL_API não estiver ativo.
 */

import { agenda, projetos, tarefasProjeto } from "@/lib/mock-data";
import type {
  ExecutionSchedule,
  Project,
  ProjectStatus,
  ProjectTask,
  TeamMember,
  Timesheet,
  TimesheetInput,
} from "./types";

const MOCK_DELAY = 350;
const wait = <T>(value: T): Promise<T> =>
  new Promise((r) => setTimeout(() => r(value), MOCK_DELAY));

const mockMembers: TeamMember[] = [
  { id: "u1", nome: "Henrique Oliveira", cargo: "Técnico Instalador", email: "henrique@alfahp.com.br", ativo: true },
  { id: "u2", nome: "Carlos Eduardo", cargo: "Técnico de Manutenção", email: "carlos@alfahp.com.br", ativo: true },
  { id: "u3", nome: "Marcos Vinicius", cargo: "Eletricista", email: "marcos@alfahp.com.br", ativo: true },
];

const mapStatus = (s: string): ProjectStatus => {
  switch (s) {
    case "ativo":
      return "em_andamento";
    case "concluido":
      return "concluida";
    case "atrasado":
      return "atrasada";
    default:
      return "programada";
  }
};

export const mockApi = {
  teamMembers: () => wait(mockMembers),

  projects: (): Promise<Project[]> =>
    wait(
      projetos.map((p) => ({
        id: p.id,
        nome: p.nome,
        cliente: p.cliente,
        cidade: "",
        data_inicio: p.inicio,
        data_previsao: p.termino,
        percentual: p.progresso,
        status: mapStatus(p.status),
      })),
    ),

  executionSchedules: (): Promise<ExecutionSchedule[]> =>
    wait(
      agenda.map((a) => ({
        id: a.id,
        project_id: a.id,
        projeto: a.projeto,
        cliente: a.cliente,
        cidade: a.cidade,
        endereco: a.endereco,
        responsavel: a.responsavel,
        data: a.data,
        hora_inicio: a.inicio,
        hora_fim: a.fim,
        status: a.status,
        descricao: a.descricao,
      })),
    ),

  projectTasks: (projectId: string): Promise<ProjectTask[]> => {
    const tasks = tarefasProjeto[projectId] ?? [];
    return wait(
      tasks.map((t) => ({
        id: t.id,
        project_id: projectId,
        nome: t.titulo,
        responsavel: t.responsavel,
        percentual: t.progresso,
        status:
          t.status === "concluida"
            ? ("concluida" as const)
            : t.status === "em_andamento"
              ? ("em_andamento" as const)
              : ("pendente" as const),
      })),
    );
  },

  timesheets: (_projectId: string): Promise<Timesheet[]> => wait([]),

  createTimesheet: (projectId: string, payload: TimesheetInput): Promise<Timesheet> =>
    wait({
      id: `ts_${Date.now()}`,
      project_id: projectId,
      user_id: Array.isArray(payload.user_id) ? payload.user_id[0] : payload.user_id,
      task_id: payload.task_id ?? null,
      data: payload.date ?? new Date().toISOString().slice(0, 10),
      start_time: payload.start_time,
      end_time: payload.end_time,
      hours: typeof payload.hours === "string" ? Number(payload.hours) : payload.hours,
      percentual_executado: payload.percentage_executed,
      descricao: payload.atividade_realizada ?? payload.description,
      observacoes: payload.observacoes ?? payload.notes,
      created_at: new Date().toISOString(),
    }),
};
