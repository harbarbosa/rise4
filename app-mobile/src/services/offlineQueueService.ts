/**
 * offlineQueueService — fila genérica de operações pendentes para
 * funcionamento offline. Persiste em localStorage e tenta reenviar
 * automaticamente quando a conexão é restabelecida.
 *
 * Tipos de operação suportados:
 *   - timesheet.create
 *   - ponto.checkin
 *   - ponto.adjustment
 *   - expense.create
 *   - expense.update
 *   - expense.delete
 *
 * Para enfileirar use `enqueueOp(type, payload, label?)` ou os
 * helpers específicos (`enqueueTimesheet`, etc).
 *
 * Helper `runOrEnqueue` tenta executar online e cai para a fila em
 * caso de falha de rede.
 */

import { ApiError } from "@/services/api";
import { createTimesheet } from "@/services/timesheetService";
import type { TimesheetInput } from "@/services/types";
import {
  registerCheckin,
  createAdjustment,
  type CheckinPayload,
  type AdjustmentPayload,
} from "@/services/pontoService";
import {
  saveExpense,
  deleteExpense,
  type ExpenseInput,
} from "@/services/travelRefundService";

export type PendingOpType =
  | "timesheet.create"
  | "ponto.checkin"
  | "ponto.adjustment"
  | "expense.create"
  | "expense.update"
  | "expense.delete";

export interface PendingOp {
  id: string;
  type: PendingOpType;
  createdAt: string;
  attempts: number;
  lastError?: string;
  label?: string;
  payload: unknown;
}

const OFFLINE_QUEUE_KEY = "alfahp.offline.queue.v2";

type Listener = () => void;
const listeners = new Set<Listener>();
let onlineListener: Listener = () => {};
let initialized = false;

function emit() {
  listeners.forEach((l) => {
    try {
      l();
    } catch {
      /* ignore */
    }
  });
}

function readQueue(): PendingOp[] {
  if (typeof window === "undefined") return [];
  try {
    const raw = window.localStorage.getItem(OFFLINE_QUEUE_KEY);
    return raw ? (JSON.parse(raw) as PendingOp[]) : [];
  } catch {
    return [];
  }
}

function writeQueue(ops: PendingOp[]) {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(ops));
  emit();
}

function genId() {
  return `op_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
}

/* ---------- API pública ---------- */

export function isOnline(): boolean {
  if (typeof navigator === "undefined") return true;
  return navigator.onLine !== false;
}

/** Heurística — verdadeiro quando o erro foi de rede (sem resposta do servidor). */
export function isNetworkError(e: unknown): boolean {
  if (!isOnline()) return true;
  if (e instanceof ApiError) {
    if (e.status === 0) return true;
    if (/network|failed to fetch|timeout|offline/i.test(e.message)) return true;
  } else if (e instanceof Error) {
    if (/network|failed to fetch|timeout|offline/i.test(e.message)) return true;
  }
  return false;
}

export function getPendingOps(): PendingOp[] {
  return readQueue();
}

export function getPendingCount(): number {
  return readQueue().length;
}

export function subscribe(listener: Listener): () => void {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

export function enqueueOp(
  type: PendingOpType,
  payload: unknown,
  label?: string,
): PendingOp {
  const op: PendingOp = {
    id: genId(),
    type,
    createdAt: new Date().toISOString(),
    attempts: 0,
    label,
    payload,
  };
  const queue = readQueue();
  queue.push(op);
  writeQueue(queue);
  return op;
}

/** Helpers específicos (mantidos para compatibilidade com chamadores antigos). */
export function enqueueTimesheet(projectId: string, data: TimesheetInput): PendingOp {
  return enqueueOp("timesheet.create", { projectId, data }, "Lançamento de atividade");
}

export function removeOp(id: string) {
  writeQueue(readQueue().filter((op) => op.id !== id));
}

export interface FlushResult {
  attempted: number;
  succeeded: number;
  failed: number;
}

let flushing = false;

async function dispatchOp(op: PendingOp): Promise<void> {
  switch (op.type) {
    case "timesheet.create": {
      const p = op.payload as { projectId: string; data: TimesheetInput };
      await createTimesheet(p.projectId, p.data);
      return;
    }
    case "ponto.checkin": {
      await registerCheckin(op.payload as CheckinPayload);
      return;
    }
    case "ponto.adjustment": {
      await createAdjustment(op.payload as AdjustmentPayload);
      return;
    }
    case "expense.create": {
      const p = op.payload as { tripId: string; input: ExpenseInput };
      await saveExpense(p.tripId, p.input);
      return;
    }
    case "expense.update": {
      const p = op.payload as { tripId: string; id: string; input: ExpenseInput };
      await saveExpense(p.tripId, p.input, p.id);
      return;
    }
    case "expense.delete": {
      const p = op.payload as { tripId: string; id: string };
      await deleteExpense(p.tripId, p.id);
      return;
    }
    default:
      throw new Error(`Operação desconhecida: ${(op as PendingOp).type}`);
  }
}

/**
 * Tenta enviar todos os itens pendentes. Falhas mantêm o item na fila
 * com `attempts` e `lastError` incrementados.
 */
export async function flushQueue(): Promise<FlushResult> {
  if (flushing || !isOnline()) {
    return { attempted: 0, succeeded: 0, failed: 0 };
  }
  flushing = true;
  let succeeded = 0;
  let failed = 0;
  try {
    const ops = readQueue();
    for (const op of ops) {
      try {
        await dispatchOp(op);
        writeQueue(readQueue().filter((o) => o.id !== op.id));
        succeeded++;
      } catch (e) {
        failed++;
        const msg = e instanceof Error ? e.message : "Falha desconhecida";
        writeQueue(
          readQueue().map((o) =>
            o.id === op.id
              ? { ...o, attempts: o.attempts + 1, lastError: msg }
              : o,
          ),
        );
        // Se ficou offline durante o flush, interrompe.
        if (!isOnline()) break;
      }
    }
    return { attempted: ops.length, succeeded, failed };
  } finally {
    flushing = false;
  }
}

/**
 * Executa `run` se estiver online. Em caso de falha de rede (ou se já
 * estiver offline), enfileira a operação para envio posterior.
 *
 * Retorna `{ queued: true }` quando enfileirou.
 */
export async function runOrEnqueue<T>(
  type: PendingOpType,
  payload: unknown,
  run: () => Promise<T>,
  label?: string,
): Promise<{ queued: false; result: T } | { queued: true; op: PendingOp }> {
  if (!isOnline()) {
    const op = enqueueOp(type, payload, label);
    return { queued: true, op };
  }
  try {
    const result = await run();
    return { queued: false, result };
  } catch (e) {
    if (isNetworkError(e)) {
      const op = enqueueOp(type, payload, label);
      return { queued: true, op };
    }
    throw e;
  }
}

export function initOfflineQueue(onSync?: (r: FlushResult) => void) {
  if (initialized || typeof window === "undefined") return;
  initialized = true;

  onlineListener = async () => {
    if (getPendingCount() === 0) {
      emit();
      return;
    }
    const result = await flushQueue();
    emit();
    if (onSync) onSync(result);
  };

  window.addEventListener("online", onlineListener);
  window.addEventListener("offline", emit);

  if (isOnline() && getPendingCount() > 0) {
    void onlineListener();
  }
}

export function teardownOfflineQueue() {
  if (typeof window === "undefined" || !initialized) return;
  window.removeEventListener("online", onlineListener);
  window.removeEventListener("offline", emit);
  initialized = false;
}
