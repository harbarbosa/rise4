import { useMutation, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { ApiError } from "@/services/api";
import {
  saveExpense,
  deleteExpense,
  type Expense,
  type ExpenseInput,
} from "@/services/travelRefundService";
import { runOrEnqueue } from "@/services/offlineQueueService";
import { tripExpensesKey } from "./useTripExpenses";

type CreateResult = { queued: boolean; expense: Expense | null };

export function useCreateExpense(tripId: string) {
  const qc = useQueryClient();
  return useMutation<CreateResult, ApiError, ExpenseInput>({
    mutationFn: async (input) => {
      const r = await runOrEnqueue<Expense>(
        "expense.create",
        { tripId, input },
        () => saveExpense(tripId, input),
        "Nova despesa",
      );
      return r.queued
        ? { queued: true, expense: null }
        : { queued: false, expense: r.result };
    },
    onSuccess: (data) => {
      qc.invalidateQueries({ queryKey: tripExpensesKey(tripId) });
      if (data.queued) {
        toast.info("Sem conexão. Despesa será enviada quando voltar online.");
      } else {
        toast.success("Despesa cadastrada com sucesso.");
      }
    },
    onError: (err) => toast.error(err.message ?? "Falha ao salvar despesa."),
  });
}

export function useUpdateExpense(tripId: string) {
  const qc = useQueryClient();
  return useMutation<CreateResult, ApiError, { id: string; input: ExpenseInput }>({
    mutationFn: async ({ id, input }) => {
      const r = await runOrEnqueue<Expense>(
        "expense.update",
        { tripId, id, input },
        () => saveExpense(tripId, input, id),
        "Atualização de despesa",
      );
      return r.queued
        ? { queued: true, expense: null }
        : { queued: false, expense: r.result };
    },
    onSuccess: (data) => {
      qc.invalidateQueries({ queryKey: tripExpensesKey(tripId) });
      if (data.queued) {
        toast.info("Sem conexão. Atualização será enviada quando voltar online.");
      } else {
        toast.success("Despesa atualizada.");
      }
    },
    onError: (err) => toast.error(err.message ?? "Falha ao atualizar despesa."),
  });
}

export function useDeleteExpense(tripId: string) {
  const qc = useQueryClient();
  return useMutation<{ queued: boolean }, ApiError, string>({
    mutationFn: async (id) => {
      const r = await runOrEnqueue<void>(
        "expense.delete",
        { tripId, id },
        () => deleteExpense(tripId, id),
        "Exclusão de despesa",
      );
      return { queued: r.queued };
    },
    onSuccess: (data) => {
      qc.invalidateQueries({ queryKey: tripExpensesKey(tripId) });
      if (data.queued) {
        toast.info("Sem conexão. Exclusão será enviada quando voltar online.");
      } else {
        toast.success("Despesa excluída.");
      }
    },
    onError: (err) => toast.error(err.message ?? "Falha ao excluir despesa."),
  });
}
