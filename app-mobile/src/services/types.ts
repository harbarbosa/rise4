/**
 * Tipos compartilhados entre a API REST e a UI.
 * Espelham os contratos esperados dos endpoints do backend AlfaHP.
 */

export type ProjectStatus =
  | "programada"
  | "em_andamento"
  | "concluida"
  | "atrasada";

export interface TeamMember {
  id: string;
  nome: string;
  cargo: string;
  email: string;
  telefone?: string;
  avatar_url?: string | null;
  ativo: boolean;
}

export interface Project {
  id: string;
  nome: string;
  cliente: string;
  cidade: string;
  data_inicio: string; // ISO
  data_previsao: string; // ISO
  percentual: number;
  status: ProjectStatus;
  responsavel_id?: string;
}

export interface ExecutionSchedule {
  id: string;
  project_id: string;
  projeto: string;
  cliente: string;
  cidade: string;
  endereco?: string;
  responsavel: string;
  data: string; // YYYY-MM-DD (data de início)
  data_fim?: string; // YYYY-MM-DD (data de término, quando o agendamento tem período)
  hora_inicio: string; // HH:mm
  hora_fim: string; // HH:mm
  status: ProjectStatus;
  descricao?: string;
  // Pessoas envolvidas (da própria agenda)
  member_names?: string[];
  member_names_text?: string;
  schedule_members?: { user_id: string; member_name: string }[];
  leader_id?: string;
  leader_name?: string;
  notes?: string;
}

export interface ProjectTask {
  id: string;
  project_id: string;
  nome: string;
  responsavel: string;
  percentual: number;
  status: "pendente" | "em_andamento" | "concluida";
}

export interface Timesheet {
  id: string;
  project_id: string;
  task_id?: string | null;
  user_id: string;
  data: string; // YYYY-MM-DD
  hora_inicio?: string;
  hora_fim?: string;
  start_time?: string;
  end_time?: string;
  hours?: number;
  horas_trabalhadas?: number;
  percentual_executado?: number;
  descricao?: string;
  observacoes?: string;
  fotos?: string[];
  created_at?: string;
}

/**
 * Payload de criação/atualização de timesheet.
 *
 * Regras do backend:
 *  - `user_id` é obrigatório.
 *  - É obrigatório enviar (`start_time` + `end_time`) OU `hours`.
 *  - Se `task_id` for enviado, `percentage_executed` é obrigatório.
 */
export interface TimesheetInput {
  user_id: string | string[];
  project_id?: string;
  task_id?: string | null;
  date?: string;
  start_time?: string;
  end_time?: string;
  hours?: number | string;
  percentage_executed?: number;
  description?: string;
  note?: string;
  notes?: string;
  atividade_realizada?: string;
  observacoes?: string;
  tempo_manha?: "claro" | "nublado" | "chuvoso" | "n/a";
  tempo_tarde?: "claro" | "nublado" | "chuvoso" | "n/a";
  tempo_noite?: "claro" | "nublado" | "chuvoso" | "n/a";
  participant_ids?: string[];
}

