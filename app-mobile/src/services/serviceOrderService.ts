import { api, ApiError } from "@/services/api";

export type ServiceOrderStatus = "aberta" | "em_andamento" | "fechada" | "cancelada" | string;

export interface ServiceOrder {
  id: string;
  titulo?: string | null;
  descricao?: string | null;
  status: ServiceOrderStatus;
  cliente_id?: number | null;
  client_name?: string | null;
  tecnico_id?: number | null;
  tech_name?: string | null;
  tipo_title?: string | null;
  motivo_title?: string | null;
  data_abertura?: string | null;
  data_fechamento?: string | null;
  is_assigned_to_me?: boolean;
}

export interface ServiceAttendance {
  id: string;
  os_id: string;
  start_datetime: string;
  end_datetime?: string | null;
  notes?: string | null;
  defeito_apresentado?: string | null;
  diagnostico?: string | null;
  solucao_encontrada?: string | null;
  causa_raiz?: string | null;
  materiais_utilizados?: string | null;
  member_ids?: number[];
}

export interface ServiceChecklistItem {
  id: number;
  title: string;
  required?: boolean | number;
}

export interface ServiceChecklistAnswer {
  item_id: number;
  status: "pending" | "ok" | "not_ok" | "na";
  notes: string;
}

export interface ServiceOrderFile {
  id: string;
  original_file_name?: string | null;
  url: string;
}

function unwrapList<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  if (payload && typeof payload === "object") {
    const data = (payload as Record<string, unknown>).data;
    if (Array.isArray(data)) return data as T[];
  }
  return [];
}

function unwrapOne<T>(payload: unknown): T {
  if (payload && typeof payload === "object") {
    const data = (payload as Record<string, unknown>).data;
    if (data && typeof data === "object") return data as T;
  }
  return payload as T;
}

function normalizeOrder(raw: Record<string, unknown>): ServiceOrder {
  return {
    ...(raw as unknown as ServiceOrder),
    id: String(raw.id ?? ""),
    status: String(raw.status ?? "aberta"),
  };
}

export const serviceOrderService = {
  async list(status?: string): Promise<ServiceOrder[]> {
    const response = await api.get<unknown>("/api/ordemservico/orders", {
      params: status ? { status } : undefined,
    });
    return unwrapList<Record<string, unknown>>(response.data).map(normalizeOrder);
  },

  async get(id: string): Promise<ServiceOrder> {
    const response = await api.get<unknown>(`/api/ordemservico/orders/${encodeURIComponent(id)}`);
    return normalizeOrder(unwrapOne<Record<string, unknown>>(response.data));
  },

  async start(id: string): Promise<ServiceOrder> {
    const response = await api.post<unknown>(
      `/api/ordemservico/orders/${encodeURIComponent(id)}/start`,
    );
    return normalizeOrder(unwrapOne<Record<string, unknown>>(response.data));
  },

  async finish(id: string): Promise<ServiceOrder> {
    const response = await api.post<unknown>(
      `/api/ordemservico/orders/${encodeURIComponent(id)}/finish`,
    );
    return normalizeOrder(unwrapOne<Record<string, unknown>>(response.data));
  },

  async attendances(id: string): Promise<ServiceAttendance[]> {
    const response = await api.get<unknown>(
      `/api/ordemservico/orders/${encodeURIComponent(id)}/attendances`,
    );
    return unwrapList<ServiceAttendance>(response.data);
  },

  async checklist(id: string): Promise<ServiceChecklistItem[]> {
    const response = await api.get<unknown>(
      `/api/ordemservico/orders/${encodeURIComponent(id)}/checklist`,
    );
    return unwrapList<ServiceChecklistItem>(response.data);
  },

  async createAttendance(
    id: string,
    payload: Pick<ServiceAttendance, "start_datetime" | "end_datetime" | "notes"> & {
      member_ids?: number[];
      defeito_apresentado?: string;
      diagnostico?: string;
      solucao_encontrada?: string;
      causa_raiz?: string;
      materiais_utilizados?: string;
      checklist?: ServiceChecklistAnswer[];
    },
  ): Promise<ServiceAttendance> {
    const response = await api.post<unknown>(
      `/api/ordemservico/orders/${encodeURIComponent(id)}/attendances`,
      payload,
    );
    return unwrapOne<ServiceAttendance>(response.data);
  },

  async files(id: string): Promise<ServiceOrderFile[]> {
    const response = await api.get<unknown>(
      `/api/ordemservico/orders/${encodeURIComponent(id)}/files`,
    );
    return unwrapList<ServiceOrderFile>(response.data);
  },

  async uploadFiles(id: string, files: File[]): Promise<ServiceOrderFile[]> {
    if (!files.length) return [];
    const form = new FormData();
    files.forEach((file) => form.append("files", file, file.name));
    const response = await api.post<unknown>(
      `/api/ordemservico/orders/${encodeURIComponent(id)}/files`,
      form,
    );
    return unwrapList<ServiceOrderFile>(response.data);
  },

  async createComment(id: string, comment: string) {
    if (!comment.trim()) throw new ApiError("Comentário vazio", 400);
    return api.post(`/api/ordemservico/orders/${encodeURIComponent(id)}/comments`, { comment });
  },
};
