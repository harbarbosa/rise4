import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ApiError } from "@/services/api";
import {
  fetchAdjustments,
  fetchPontoDashboard,
  fetchPontoMe,
  fetchPontoStatus,
  fetchPontoToday,
  registerCheckin,
  PUNCH_LABEL,
  type AdjustmentRequest,
  type CheckinPayload,
  type PontoDashboard,
  type PontoMe,
  type PontoPunch,
  type PontoStatusData,
  type PunchType,
} from "@/services/pontoService";
import { runOrEnqueue } from "@/services/offlineQueueService";

export const pontoKeys = {
  me: ["ponto", "me"] as const,
  status: ["ponto", "status"] as const,
  today: ["ponto", "today"] as const,
  dashboard: ["ponto", "dashboard"] as const,
  adjustments: ["ponto", "adjustments"] as const,
};

export function usePontoMe() {
  return useQuery<PontoMe | null, ApiError>({
    queryKey: pontoKeys.me,
    queryFn: fetchPontoMe,
    staleTime: 60_000,
  });
}

export function usePontoStatus() {
  return useQuery<PontoStatusData | null, ApiError>({
    queryKey: pontoKeys.status,
    queryFn: fetchPontoStatus,
    staleTime: 30_000,
    refetchInterval: 60_000,
  });
}

export function usePontoToday() {
  return useQuery<PontoPunch[], ApiError>({
    queryKey: pontoKeys.today,
    queryFn: fetchPontoToday,
    staleTime: 30_000,
  });
}

export function usePontoDashboard() {
  return useQuery<PontoDashboard | null, ApiError>({
    queryKey: pontoKeys.dashboard,
    queryFn: fetchPontoDashboard,
    staleTime: 30_000,
  });
}

export function usePontoAdjustments() {
  return useQuery<AdjustmentRequest[], ApiError>({
    queryKey: pontoKeys.adjustments,
    queryFn: fetchAdjustments,
    staleTime: 60_000,
  });
}

export interface CheckinResult {
  queued: boolean;
  punch: PontoPunch | null;
}

export function useRegisterCheckin() {
  const qc = useQueryClient();
  return useMutation<CheckinResult, ApiError, CheckinPayload>({
    mutationFn: async (payload) => {
      const label = "Marcação de ponto";
      const r = await runOrEnqueue<PontoPunch | null>(
        "ponto.checkin",
        payload,
        () => registerCheckin(payload),
        label,
      );
      return r.queued
        ? { queued: true, punch: null }
        : { queued: false, punch: r.result };
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: pontoKeys.status });
      qc.invalidateQueries({ queryKey: pontoKeys.today });
      qc.invalidateQueries({ queryKey: pontoKeys.dashboard });
      qc.invalidateQueries({ queryKey: pontoKeys.me });
    },
  });
}
