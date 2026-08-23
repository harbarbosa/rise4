import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import {
  Clock,
  LogIn,
  LogOut,
  Coffee,
  Play,
  MapPin,
  AlertCircle,
  CheckCircle2,
  RefreshCw,
  Loader2,
} from "lucide-react";
import { Link } from "@tanstack/react-router";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { LoadingState, ErrorState } from "@/components/QueryState";
import { SelfieCapture } from "@/components/SelfieCapture";
import { usePontoMe, usePontoStatus, usePontoToday, useRegisterCheckin } from "@/hooks/usePonto";
import {
  STATUS_LABEL,
  getCurrentCoords,
  normalizePunchType,
  translatePunchStatus,
  translatePunchType,
  type PunchType,
} from "@/services/pontoService";
import { ApiError } from "@/services/api";

export const Route = createFileRoute("/ponto")({
  head: () => ({ meta: [{ title: "Marcação de Ponto — AlfaHP Mobile" }] }),
  component: PontoPage,
});

const ALL_PUNCHES: PunchType[] = ["entrada", "saida_intervalo", "retorno_intervalo", "saida"];

const PUNCH_ICON: Record<PunchType, typeof LogIn> = {
  entrada: LogIn,
  saida_intervalo: Coffee,
  retorno_intervalo: Play,
  saida: LogOut,
};

function formatMinutesAsHours(min?: number | null) {
  if (min == null || isNaN(min)) return "—";
  const h = Math.floor(min / 60);
  const m = min % 60;
  if (h === 0) return `${m}min`;
  if (m === 0) return `${h}h`;
  return `${h}h${String(m).padStart(2, "0")}`;
}
function fmtBR(iso?: string) {
  if (!iso) return "—";
  const [y, m, d] = iso.split("-");
  if (!y || !m || !d) return iso;
  return `${d}/${m}/${y}`;
}




function formatPontoError(
  primary?: unknown,
  secondary?: unknown,
  fallback = "Não foi possível carregar os dados de ponto.",
) {
  const error = primary ?? secondary;
  if (error instanceof ApiError) {
    if (error.status === 403 || /forbidden/i.test(error.message)) {
      return "Seu usuário não tem permissão para acessar ou registrar ponto. Solicite a liberação do módulo PontoRH ao RH/Admin.";
    }
    if (error.status >= 500) {
      return "O servidor de ponto está indisponível no momento. Tente novamente em instantes ou contate o RH.";
    }
    if (/invalid punch sequence/i.test(error.message)) {
      return "Esta marcação está fora da ordem esperada.";
    }
    if (/no active schedule/i.test(error.message)) {
      return "Nenhuma jornada de trabalho ativa vinculada ao seu usuário. Contate o RH.";
    }
    return error.message || fallback;
  }
  if (error instanceof Error) return error.message || fallback;
  return fallback;
}

function PontoPage() {
  const me = usePontoMe();
  const status = usePontoStatus();
  const today = usePontoToday();
  const checkin = useRegisterCheckin();

  const [feedback, setFeedback] = useState<{ kind: "success" | "error"; message: string } | null>(
    null,
  );
  const [gettingGps, setGettingGps] = useState(false);
  const [selfieOpen, setSelfieOpen] = useState(false);

  function requestPunch() {
    setFeedback(null);
    setSelfieOpen(true);
  }

  async function handleCheckin(photo: string) {
    setFeedback(null);
    setGettingGps(true);
    let coords: { latitude: string; longitude: string };
    try {
      coords = await getCurrentCoords();
    } catch {
      coords = { latitude: "0", longitude: "0" };
    }
    setGettingGps(false);
    try {
      const result = await checkin.mutateAsync({
        latitude: coords.latitude,
        longitude: coords.longitude,
        device_name:
          typeof navigator !== "undefined" ? navigator.userAgent.slice(0, 80) : undefined,
        photo,
      });
      setFeedback({
        kind: "success",
        message: result.queued
          ? "Marcação salva offline. Será enviada quando a conexão voltar."
          : "Marcação registrada com sucesso.",
      });
    } catch (e) {
      setFeedback({
        kind: "error",
        message: formatPontoError(e, undefined, "Erro ao registrar ponto."),
      });
    }
  }

  const isLoading = me.isLoading || status.isLoading;
  const isError = me.isError || status.isError;
  const submitting = checkin.isPending || gettingGps;

  return (
    <MobileShell>
      <PageHeader
        title="Marcação de Ponto"
        right={
          <button
            onClick={() => {
              status.refetch();
              today.refetch();
              me.refetch();
            }}
            className="w-10 h-10 -mr-2 flex items-center justify-center rounded-full active:bg-white/10"
            aria-label="Atualizar"
          >
            <RefreshCw size={18} />
          </button>
        }
      />

      <div className="p-4 space-y-4">
        {isLoading && <LoadingState label="Carregando informações..." />}

        {isError && (
          <ErrorState
            message={formatPontoError(me.error, status.error)}
            onRetry={() => {
              me.refetch();
              status.refetch();
            }}
          />
        )}

        {!isLoading && !isError && (
          <>
            {/* Card status */}
            <section className="bg-card rounded-2xl border border-border shadow-card p-5">
              <div className="flex items-center justify-between mb-3">
                <div className="min-w-0">
                  <p className="text-[10px] uppercase tracking-wide font-semibold text-muted-foreground">
                    Status atual
                  </p>
                  <h2 className="text-lg font-bold truncate">
                    {STATUS_LABEL[status.data?.status ?? ""] ?? status.data?.status ?? "—"}
                  </h2>
                </div>
                <span className="w-12 h-12 rounded-xl bg-primary-soft text-primary grid place-items-center shrink-0">
                  <Clock size={22} />
                </span>
              </div>

              <div className="grid grid-cols-2 gap-2.5">
                <Stat label="Trabalhadas" value={status.data?.worked_hours ?? "—"} />
                <Stat label="Restantes" value={status.data?.remaining_hours ?? "—"} />
                <Stat label="Banco" value={status.data?.bank_hours ?? "—"} />
                <Stat label="Atrasos" value={status.data?.late_hours ?? "—"} />
              </div>

              {me.data?.work_schedule && (
                <p className="text-[11px] text-muted-foreground mt-3">
                  Jornada: <strong>{me.data.work_schedule.name}</strong> ·{" "}
                  {me.data.work_schedule.start_time} — {me.data.work_schedule.end_time} (
                  {formatMinutesAsHours(me.data.work_schedule.break_minutes)} de intervalo)
                </p>
              )}
            </section>

            {/* Botão único de registro */}
            <section className="bg-card rounded-2xl border border-border shadow-card p-5">
              <button
                onClick={requestPunch}
                disabled={submitting}
                className="w-full h-14 rounded-2xl bg-primary text-primary-foreground font-semibold text-base inline-flex items-center justify-center gap-2 shadow-elevated active:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {submitting ? (
                  <>
                    <Loader2 size={20} className="animate-spin" />
                    {gettingGps ? "Obtendo GPS..." : "Registrando..."}
                  </>
                ) : (
                  <>
                    <MapPin size={18} />
                    Registrar
                  </>
                )}
              </button>

              {feedback && (
                <div
                  className={`mt-3 flex items-start gap-2 rounded-lg p-3 text-sm border ${
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
            </section>


            {/* Marcações do dia */}
            <section className="bg-card rounded-2xl border border-border shadow-card overflow-hidden">
              <div className="px-5 pt-4 pb-2 flex items-center justify-between">
                <h3 className="text-sm font-semibold">Marcações de hoje</h3>
                <span className="text-[11px] text-muted-foreground">
                  {today.data?.length ?? 0} registro(s)
                </span>
              </div>

              {today.isLoading && (
                <p className="px-5 pb-5 text-sm text-muted-foreground">Carregando...</p>
              )}

              {!today.isLoading && (today.data?.length ?? 0) === 0 && (
                <p className="px-5 pb-5 text-sm text-muted-foreground">
                  Nenhuma marcação registrada hoje.
                </p>
              )}

              {(today.data?.length ?? 0) > 0 && (
                <ul>
                  {today.data!.map((p, i) => {
                    const type = normalizePunchType(p.type) ?? "entrada";
                    const Icon = PUNCH_ICON[type] ?? Clock;
                    return (
                      <li
                        key={p.id}
                        className={`flex items-center gap-3 px-5 py-3 ${
                          i < today.data!.length - 1 ? "border-b border-border" : ""
                        }`}
                      >
                        <span className="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center shrink-0">
                          <Icon size={16} />
                        </span>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-semibold truncate">
                            {translatePunchType(p.type)}
                          </p>
                          <p className="text-[11px] text-muted-foreground">
                            {fmtBR(p.date)} às {p.time}
                            {p.status ? ` · ${translatePunchStatus(p.status)}` : ""}
                          </p>
                        </div>
                        {(p.latitude || p.longitude) && (
                          <MapPin size={14} className="text-muted-foreground shrink-0" />
                        )}
                      </li>
                    );
                  })}
                </ul>
              )}
            </section>

            {/* Atalhos */}
            <section className="grid grid-cols-2 gap-3">
              <Link
                to={"/ponto-historico" as "/"}
                className="rounded-2xl border border-border bg-card p-4 shadow-card active:opacity-80"
              >
                <p className="text-sm font-semibold">Histórico</p>
                <p className="text-[11px] text-muted-foreground mt-0.5">
                  Consulte marcações por período
                </p>
              </Link>
              <Link
                to={"/ponto-ajuste" as "/"}
                className="rounded-2xl border border-border bg-card p-4 shadow-card active:opacity-80"
              >
                <p className="text-sm font-semibold">Solicitar ajuste</p>
                <p className="text-[11px] text-muted-foreground mt-0.5">
                  Marcação esquecida ou correção
                </p>
              </Link>
            </section>
          </>
        )}
      </div>

      <SelfieCapture
        open={selfieOpen}
        title="Selfie de confirmação"
        onCancel={() => setSelfieOpen(false)}
        onCapture={(dataUrl) => {
          setSelfieOpen(false);
          void handleCheckin(dataUrl);
        }}
      />
    </MobileShell>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-xl border border-border p-3 bg-muted/30">
      <p className="text-[10px] uppercase tracking-wide font-semibold text-muted-foreground">
        {label}
      </p>
      <p className="text-base font-bold leading-tight mt-1">{value}</p>
    </div>
  );
}
