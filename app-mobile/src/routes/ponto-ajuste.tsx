import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Send, CheckCircle2, AlertCircle, ClipboardList } from "lucide-react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { LoadingState } from "@/components/QueryState";
import {
  createAdjustment,
  fetchAdjustments,
  PUNCH_LABEL,
  type AdjustmentPayload,
  type AdjustmentRequest,
  type PunchType,
} from "@/services/pontoService";
import { ApiError } from "@/services/api";
import { runOrEnqueue } from "@/services/offlineQueueService";

export const Route = createFileRoute("/ponto-ajuste")({
  head: () => ({ meta: [{ title: "Ajuste de Ponto — AlfaHP Mobile" }] }),
  component: AjustePage,
});

const PUNCH_OPTIONS: PunchType[] = ["entrada", "saida_intervalo", "retorno_intervalo", "saida"];

const STATUS_PT: Record<string, { label: string; cls: string }> = {
  pending: { label: "Pendente", cls: "bg-warning/10 text-warning border-warning/30" },
  approved: { label: "Aprovado", cls: "bg-success/10 text-success border-success/30" },
  rejected: { label: "Recusado", cls: "bg-destructive/10 text-destructive border-destructive/30" },
};

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

function fmtBR(dateString: string) {
  if (!dateString) return "—";
  const [y, m, d] = dateString.split("-");
  return `${d}/${m}/${y}`;
}

function AjustePage() {
  const qc = useQueryClient();
  const [date, setDate] = useState(todayISO());
  const [time, setTime] = useState("08:00");
  const [type, setType] = useState<PunchType>("entrada");
  const [reason, setReason] = useState("");
  const [feedback, setFeedback] = useState<{ kind: "success" | "error"; message: string } | null>(
    null,
  );

  const list = useQuery<AdjustmentRequest[], ApiError>({
    queryKey: ["ponto", "adjustments"],
    queryFn: fetchAdjustments,
  });

  const mut = useMutation({
    mutationFn: async (payload: AdjustmentPayload) => {
      const r = await runOrEnqueue(
        "ponto.adjustment",
        payload,
        () => createAdjustment(payload),
        "Solicitação de ajuste de ponto",
      );
      return { queued: r.queued };
    },
    onSuccess: (data) => {
      setFeedback({
        kind: "success",
        message: data.queued
          ? "Sem conexão. Solicitação salva e será enviada ao voltar online."
          : "Solicitação enviada com sucesso.",
      });
      setReason("");
      qc.invalidateQueries({ queryKey: ["ponto", "adjustments"] });
    },
    onError: (e: unknown) => {
      const msg = e instanceof Error ? e.message : "Falha ao enviar solicitação.";
      setFeedback({ kind: "error", message: msg });
    },
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    setFeedback(null);
    if (!reason.trim()) {
      setFeedback({ kind: "error", message: "Informe o motivo do ajuste." });
      return;
    }
    mut.mutate({
      record_date: date,
      requested_time: time,
      requested_type: type,
      reason: reason.trim(),
    });
  }

  return (
    <MobileShell>
      <PageHeader title="Ajuste de Ponto" back="/ponto" />

      <div className="p-4 space-y-4">
        <form
          onSubmit={submit}
          className="bg-card rounded-2xl border border-border shadow-card p-5 space-y-3"
        >
          <p className="text-[10px] uppercase tracking-wide font-semibold text-muted-foreground">
            Nova solicitação
          </p>

          <div className="grid grid-cols-2 gap-2">
            <label className="text-xs text-muted-foreground">
              Data
              <input
                type="date"
                value={date}
                max={todayISO()}
                onChange={(e) => setDate(e.target.value)}
                className="mt-1 w-full h-10 rounded-lg border border-border bg-background px-2 text-sm"
                required
              />
            </label>
            <label className="text-xs text-muted-foreground">
              Horário
              <input
                type="time"
                value={time}
                onChange={(e) => setTime(e.target.value)}
                className="mt-1 w-full h-10 rounded-lg border border-border bg-background px-2 text-sm"
                required
              />
            </label>
          </div>

          <label className="block text-xs text-muted-foreground">
            Tipo de marcação
            <select
              value={type}
              onChange={(e) => setType(e.target.value as PunchType)}
              className="mt-1 w-full h-10 rounded-lg border border-border bg-background px-2 text-sm"
            >
              {PUNCH_OPTIONS.map((t) => (
                <option key={t} value={t}>
                  {PUNCH_LABEL[t]}
                </option>
              ))}
            </select>
          </label>

          <label className="block text-xs text-muted-foreground">
            Motivo / justificativa
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              rows={4}
              placeholder="Ex.: Esqueci de bater o ponto na saída para o intervalo."
              className="mt-1 w-full rounded-lg border border-border bg-background p-2 text-sm resize-none"
              required
            />
          </label>

          {feedback && (
            <div
              className={`flex items-start gap-2 rounded-lg p-3 text-sm border ${
                feedback.kind === "success"
                  ? "bg-success/10 border-success/30 text-success"
                  : "bg-destructive/10 border-destructive/30 text-destructive"
              }`}
            >
              {feedback.kind === "success" ? (
                <CheckCircle2 size={16} className="mt-0.5 shrink-0" />
              ) : (
                <AlertCircle size={16} className="mt-0.5 shrink-0" />
              )}
              <span>{feedback.message}</span>
            </div>
          )}

          <button
            type="submit"
            disabled={mut.isPending}
            className="w-full h-12 rounded-xl bg-primary text-primary-foreground font-semibold inline-flex items-center justify-center gap-2 shadow-elevated active:opacity-90 disabled:opacity-60"
          >
            <Send size={16} />
            {mut.isPending ? "Enviando..." : "Enviar solicitação"}
          </button>
        </form>

        <section className="bg-card rounded-2xl border border-border shadow-card overflow-hidden">
          <div className="px-5 pt-4 pb-2 flex items-center justify-between">
            <h3 className="text-sm font-semibold inline-flex items-center gap-2">
              <ClipboardList size={16} /> Minhas solicitações
            </h3>
            <span className="text-[11px] text-muted-foreground">
              {list.data?.length ?? 0} registro(s)
            </span>
          </div>

          {list.isLoading && <LoadingState label="Carregando..." />}
          {!list.isLoading && (list.data?.length ?? 0) === 0 && (
            <p className="px-5 pb-5 text-sm text-muted-foreground">
              Nenhuma solicitação enviada.
            </p>
          )}
          {(list.data?.length ?? 0) > 0 && (
            <ul>
              {list.data!.map((a, i) => {
                const st = STATUS_PT[a.status?.toLowerCase()] ?? {
                  label: a.status ?? "—",
                  cls: "bg-muted text-muted-foreground border-border",
                };
                return (
                  <li
                    key={a.id}
                    className={`px-5 py-3 ${
                      i < list.data!.length - 1 ? "border-b border-border" : ""
                    }`}
                  >
                    <div className="flex items-start justify-between gap-2">
                      <div className="min-w-0">
                        <p className="text-sm font-semibold">
                          {PUNCH_LABEL[a.requested_type as PunchType] ?? a.requested_type}
                          {" · "}
                          {a.requested_time}
                        </p>
                        <p className="text-[11px] text-muted-foreground mt-0.5">{fmtBR(a.date)}</p>
                        {a.reason && (
                          <p className="text-xs mt-1.5 text-foreground/80 line-clamp-3">
                            {a.reason}
                          </p>
                        )}
                      </div>
                      <span
                        className={`text-[10px] font-semibold px-2 py-1 rounded-full border whitespace-nowrap ${st.cls}`}
                      >
                        {st.label}
                      </span>
                    </div>
                  </li>
                );
              })}
            </ul>
          )}
        </section>
      </div>
    </MobileShell>
  );
}
