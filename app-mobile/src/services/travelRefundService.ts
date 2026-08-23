/**
 * Serviço TravelRefunds.
 *
 * Endpoints:
 *   GET    /api/travelrefunds/dashboard
 *   GET    /api/travelrefunds/trips
 *   GET    /api/travelrefunds/trips/{id}
 *   POST   /api/travelrefunds/trips/save
 *   POST   /api/travelrefunds/trips/save/{id}
 *   DELETE /api/travelrefunds/trips/{id}
 *
 *   GET    /api/travelrefunds/trips/{trip_id}/expenses
 *   GET    /api/travelrefunds/trips/{trip_id}/expenses/{id}
 *   POST   /api/travelrefunds/trips/{trip_id}/expenses/save
 *   POST   /api/travelrefunds/trips/{trip_id}/expenses/save/{id}
 *   DELETE /api/travelrefunds/trips/{trip_id}/expenses/{id}
 */

import { api, USE_REAL_API } from "@/services/api";
import { despesas as despesasMock, viagemAtual } from "@/lib/mock-data";

export type ExpenseCategory =
  | "alimentacao"
  | "combustivel"
  | "hotel"
  | "pedagio"
  | "outros";

export type ExpenseStatus = "aprovado" | "pendente" | "rejeitado";

export interface Trip {
  id: string;
  titulo: string;
  projeto?: string;
  periodo?: string;
  dataInicio?: string;
  dataFim?: string;
  status?: string;
  total?: number;
}

export interface Expense {
  id: string;
  tripId: string;
  categoria: ExpenseCategory;
  valor: number;
  data: string;
  status: ExpenseStatus;
  observacao?: string;
  notaFiscal?: string;
}

export interface ExpenseInput {
  categoria: ExpenseCategory;
  valor: number;
  data: string;
  observacao?: string;
  notaFiscal?: string;
}

export interface TripInput {
  titulo: string;
  projeto?: string;
  dataInicio?: string;
  dataFim?: string;
  status?: string;
}

/* ---------------- helpers ---------------- */

function pick<T = unknown>(o: Record<string, unknown>, ...keys: string[]): T | undefined {
  for (const k of keys) {
    const v = o[k];
    if (v !== undefined && v !== null && v !== "") return v as T;
  }
  return undefined;
}

function mapCategory(v: string | undefined): ExpenseCategory {
  const map: Record<string, ExpenseCategory> = {
    alimentacao: "alimentacao",
    food: "alimentacao",
    meal: "alimentacao",
    combustivel: "combustivel",
    fuel: "combustivel",
    gas: "combustivel",
    hotel: "hotel",
    hospedagem: "hotel",
    lodging: "hotel",
    pedagio: "pedagio",
    toll: "pedagio",
    outros: "outros",
    other: "outros",
  };
  return map[(v ?? "").toLowerCase()] ?? "outros";
}

function mapStatus(v: string | undefined): ExpenseStatus {
  const s = (v ?? "").toLowerCase();
  if (["aprovado", "approved", "ok"].includes(s)) return "aprovado";
  if (["rejeitado", "rejected", "negado"].includes(s)) return "rejeitado";
  return "pendente";
}

function fmtDate(v: string | undefined): string {
  if (!v) return "";
  const [d] = v.split("T");
  return d ?? v;
}

export function mapTrip(raw: Record<string, unknown>): Trip {
  return {
    id: (pick<string | number>(raw, "id", "uuid") ?? "").toString(),
    titulo: (pick<string>(raw, "titulo", "title", "name", "destino") ?? "Viagem").toString(),
    projeto: pick<string>(raw, "projeto", "project_name", "project"),
    periodo: pick<string>(raw, "periodo", "period"),
    dataInicio: fmtDate(pick<string>(raw, "data_inicio", "start_date", "inicio")),
    dataFim: fmtDate(pick<string>(raw, "data_fim", "end_date", "termino")),
    status: pick<string>(raw, "status"),
    total: pick<number>(raw, "total", "valor_total", "amount"),
  };
}

export function mapExpense(raw: Record<string, unknown>, tripId: string): Expense {
  const valorRaw = pick<number | string>(
    raw,
    "valor",
    "amount",
    "value",
    "total",
    "price",
    "expense_value",
    "expense_amount",
    "vlr",
  );
  let valor = 0;
  if (typeof valorRaw === "number") {
    valor = valorRaw;
  } else if (typeof valorRaw === "string") {
    // suporta "1.234,56", "1234.56", "R$ 1.234,56"
    const cleaned = valorRaw.replace(/[^\d,.-]/g, "");
    const normalized =
      cleaned.includes(",") && cleaned.lastIndexOf(",") > cleaned.lastIndexOf(".")
        ? cleaned.replace(/\./g, "").replace(",", ".")
        : cleaned.replace(/,/g, "");
    valor = parseFloat(normalized) || 0;
  }
  return {
    id: (pick<string | number>(raw, "id", "uuid", "expense_id") ?? "").toString(),
    tripId,
    categoria: mapCategory(
      pick<string>(
        raw,
        "categoria",
        "category",
        "tipo",
        "category_name",
        "expense_category",
        "tipo_despesa",
        "type",
      ),
    ),
    valor,
    data: fmtDate(
      pick<string>(raw, "data", "date", "expense_date", "data_despesa", "dt"),
    ) || new Date().toISOString().slice(0, 10),
    status: mapStatus(pick<string>(raw, "status", "situacao")),
    observacao: pick<string>(
      raw,
      "observacao",
      "description",
      "descricao",
      "notes",
      "obs",
    ),
    notaFiscal: pick<string>(
      raw,
      "nota_fiscal",
      "receipt",
      "receipt_url",
      "foto",
      "image",
      "image_url",
      "anexo",
    ),
  };
}


/* ---------------- mock source ---------------- */

const mockTrips: Trip[] = [
  {
    id: "t1",
    titulo: viagemAtual.projeto,
    projeto: viagemAtual.projeto,
    periodo: viagemAtual.periodo,
    status: viagemAtual.status,
  },
];

const mockExpensesByTrip: Record<string, Expense[]> = {
  t1: despesasMock.map((d) => ({
    id: d.id,
    tripId: "t1",
    categoria: d.categoria,
    valor: d.valor,
    data: d.data,
    status: d.status,
    observacao: d.observacao,
    notaFiscal: d.notaFiscal,
  })),
};

const delay = <T,>(v: T) => new Promise<T>((r) => setTimeout(() => r(v), 200));

const mockSource = {
  listTrips: () => delay(mockTrips),
  getTrip: (id: string) => delay(mockTrips.find((t) => t.id === id) ?? null),
  saveTrip: async (input: TripInput, id?: string): Promise<Trip> => {
    const trip: Trip = { id: id ?? `t${Date.now()}`, ...input };
    if (id) {
      const idx = mockTrips.findIndex((t) => t.id === id);
      if (idx >= 0) mockTrips[idx] = { ...mockTrips[idx], ...trip };
    } else {
      mockTrips.push(trip);
    }
    return delay(trip);
  },
  deleteTrip: async (id: string) => {
    const idx = mockTrips.findIndex((t) => t.id === id);
    if (idx >= 0) mockTrips.splice(idx, 1);
    delete mockExpensesByTrip[id];
    return delay({ ok: true });
  },
  listExpenses: (tripId: string) => delay(mockExpensesByTrip[tripId] ?? []),
  saveExpense: async (tripId: string, input: ExpenseInput, id?: string): Promise<Expense> => {
    const list = mockExpensesByTrip[tripId] ?? (mockExpensesByTrip[tripId] = []);
    if (id) {
      const idx = list.findIndex((e) => e.id === id);
      const updated: Expense = { ...list[idx], ...input, id, tripId, status: list[idx]?.status ?? "pendente" };
      if (idx >= 0) list[idx] = updated;
      return delay(updated);
    }
    const created: Expense = {
      id: `d${Date.now()}`,
      tripId,
      status: "pendente",
      ...input,
    };
    list.unshift(created);
    return delay(created);
  },
  deleteExpense: async (tripId: string, id: string) => {
    const list = mockExpensesByTrip[tripId] ?? [];
    const idx = list.findIndex((e) => e.id === id);
    if (idx >= 0) list.splice(idx, 1);
    return delay({ ok: true });
  },
};

/* ---------------- real source ---------------- */

function unwrapList<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    for (const key of ["data", "rows", "items", "result", "results"]) {
      const v = p[key];
      if (Array.isArray(v)) return v as T[];
    }
  }
  return [];
}

function unwrapOne<T>(payload: unknown): T | null {
  if (payload == null) return null;
  if (payload && typeof payload === "object") {
    const p = payload as Record<string, unknown>;
    if ("data" in p && p.data && typeof p.data === "object") return p.data as T;
  }
  return payload as T;
}

const realSource = {
  listTrips: () =>
    api.get<unknown>("/api/travelrefunds/trips").then((r) => unwrapList<unknown>(r.data)),
  getTrip: (id: string) =>
    api.get<unknown>(`/api/travelrefunds/trips/${id}`).then((r) => unwrapOne<unknown>(r.data)),
  saveTrip: (input: TripInput, id?: string) => {
    const url = id
      ? `/api/travelrefunds/trips/save/${id}`
      : `/api/travelrefunds/trips/save`;
    return api.post<unknown>(url, input).then((r) => unwrapOne<unknown>(r.data));
  },
  deleteTrip: (id: string) =>
    api.delete(`/api/travelrefunds/trips/${id}`).then((r) => r.data),
  listExpenses: (tripId: string) =>
    api
      .get<unknown>(`/api/travelrefunds/trips/${tripId}/expenses`)
      .then((r) => unwrapList<unknown>(r.data)),
  saveExpense: (tripId: string, input: ExpenseInput, id?: string) => {
    const url = id
      ? `/api/travelrefunds/trips/${tripId}/expenses/save/${id}`
      : `/api/travelrefunds/trips/${tripId}/expenses/save`;
    // Envia tanto os nomes legados (categoria/valor/data/...) quanto os
    // nomes esperados pelo backend PHP (category_id/amount/expense_date/...).
    // notaFiscal pode vir como blob: URL local — não enviar pro backend.
    const isBlob = typeof input.notaFiscal === "string" && input.notaFiscal.startsWith("blob:");
    const receipt = isBlob ? undefined : input.notaFiscal;
    const payload: Record<string, unknown> = {
      ...input,
      category: input.categoria,
      category_id: input.categoria,
      category_name: input.categoria,
      amount: input.valor,
      expense_date: input.data,
      description: input.observacao,
      notes: input.observacao,
      receipt_file: receipt,
      attachment: receipt,
      has_invoice: receipt ? 1 : 0,
      notaFiscal: receipt,
    };
    return api.post<unknown>(url, payload).then((r) => unwrapOne<unknown>(r.data));
  },
  deleteExpense: (tripId: string, id: string) =>
    api
      .delete(`/api/travelrefunds/trips/${tripId}/expenses/${id}`)
      .then((r) => r.data),
};

/* ---------------- API pública ---------------- */

export async function fetchTrips(): Promise<Trip[]> {
  if (USE_REAL_API) {
    const data = await realSource.listTrips();
    if (!Array.isArray(data)) return [];
    return (data as Record<string, unknown>[]).map(mapTrip);
  }
  const data = await mockSource.listTrips();
  return data;
}

export async function fetchTrip(id: string): Promise<Trip | null> {
  if (USE_REAL_API) {
    const data = await realSource.getTrip(id);
    return data ? mapTrip(data as Record<string, unknown>) : null;
  }
  return mockSource.getTrip(id);
}

export async function saveTrip(input: TripInput, id?: string): Promise<Trip> {
  if (USE_REAL_API) {
    const data = await realSource.saveTrip(input, id);
    return mapTrip((data ?? {}) as Record<string, unknown>);
  }
  return mockSource.saveTrip(input, id);
}

export async function deleteTrip(id: string): Promise<void> {
  if (USE_REAL_API) {
    await realSource.deleteTrip(id);
    return;
  }
  await mockSource.deleteTrip(id);
}

export async function fetchTripExpenses(tripId: string): Promise<Expense[]> {
  if (USE_REAL_API) {
    const data = await realSource.listExpenses(tripId);
    if (!Array.isArray(data)) return [];
    return (data as Record<string, unknown>[]).map((r) => mapExpense(r, tripId));
  }
  return mockSource.listExpenses(tripId);
}

export async function saveExpense(
  tripId: string,
  input: ExpenseInput,
  id?: string,
): Promise<Expense> {
  if (USE_REAL_API) {
    const data = await realSource.saveExpense(tripId, input, id);
    return mapExpense((data ?? {}) as Record<string, unknown>, tripId);
  }
  return mockSource.saveExpense(tripId, input, id);
}

export async function deleteExpense(tripId: string, id: string): Promise<void> {
  if (USE_REAL_API) {
    await realSource.deleteExpense(tripId, id);
    return;
  }
  await mockSource.deleteExpense(tripId, id);
}

