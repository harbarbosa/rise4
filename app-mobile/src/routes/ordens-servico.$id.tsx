import { createFileRoute } from "@tanstack/react-router";
import { Camera, CheckCircle2, Clock3, FileText, MapPin, Upload, Wrench } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { ErrorState, LoadingState } from "@/components/QueryState";
import { useAuth } from "@/contexts/AuthContext";
import { serviceOrderService } from "@/services/serviceOrderService";
import { fetchTeamMembers, type TeamMemberProfile } from "@/services/teamMemberService";

export const Route = createFileRoute("/ordens-servico/$id")({
  head: () => ({ meta: [{ title: "Atendimento de OS — AlfaHP" }] }),
  component: ServiceOrderDetailPage,
});

function toLocalDateTime(value: Date) {
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}T${pad(value.getHours())}:${pad(value.getMinutes())}`;
}

function formatDateTime(value?: string | null) {
  if (!value) return "—";
  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
  if (match) return `${match[3]}/${match[2]}/${match[1]} ${match[4]}:${match[5]}`;
  return value;
}

function isTechnician(member: TeamMemberProfile) {
  const role = `${member.cargo ?? ""} ${member.roleTitle ?? ""}`.toLowerCase();
  return /t[eé]cnico|technician|instalador|eletricista|campo/.test(role);
}

function ServiceOrderDetailPage() {
  const { id } = Route.useParams();
  const { user } = useAuth();
  const qc = useQueryClient();
  const fileInput = useRef<HTMLInputElement>(null);
  const [notes, setNotes] = useState("");
  const [startDateTime, setStartDateTime] = useState(() => toLocalDateTime(new Date()));
  const [endDateTime, setEndDateTime] = useState(() => toLocalDateTime(new Date()));
  const [selectedMemberIds, setSelectedMemberIds] = useState<number[]>([]);
  const [report, setReport] = useState({
    defeito_apresentado: "",
    diagnostico: "",
    solucao_encontrada: "",
    causa_raiz: "",
    materiais_utilizados: "",
  });
  const [checklistAnswers, setChecklistAnswers] = useState<
    Record<number, { status: "pending" | "ok" | "not_ok" | "na"; notes: string }>
  >({});
  const orderQuery = useQuery({
    queryKey: ["service-order", id],
    queryFn: () => serviceOrderService.get(id),
  });
  const attendancesQuery = useQuery({
    queryKey: ["service-order-attendances", id],
    queryFn: () => serviceOrderService.attendances(id),
    enabled: !!orderQuery.data,
  });
  const filesQuery = useQuery({
    queryKey: ["service-order-files", id],
    queryFn: () => serviceOrderService.files(id),
    enabled: !!orderQuery.data,
  });
  const checklistQuery = useQuery({
    queryKey: ["service-order-checklist", id],
    queryFn: () => serviceOrderService.checklist(id),
    enabled: !!orderQuery.data,
  });
  const membersQuery = useQuery<TeamMemberProfile[]>({
    queryKey: ["service-order-team-members"],
    queryFn: fetchTeamMembers,
    enabled: !!orderQuery.data,
  });
  useEffect(() => {
    const loggedUserId = Number(user?.id);
    const loggedUserIsTechnician = membersQuery.data?.some(
      (member) => Number(member.id) === loggedUserId && isTechnician(member),
    );
    if (loggedUserId > 0 && loggedUserIsTechnician && selectedMemberIds.length === 0) {
      setSelectedMemberIds([loggedUserId]);
    }
  }, [user?.id, membersQuery.data]);
  const finishMutation = useMutation({
    mutationFn: async () => {
      if (!startDateTime || !endDateTime) {
        throw new Error("Informe a data e hora inicial e final do atendimento.");
      }
      if (new Date(endDateTime) < new Date(startDateTime)) {
        throw new Error("A data/hora final não pode ser anterior à inicial.");
      }
      await serviceOrderService.createAttendance(id, {
        start_datetime: startDateTime,
        end_datetime: endDateTime,
        notes,
        ...report,
        member_ids: selectedMemberIds.length ? selectedMemberIds : undefined,
        checklist: checklistQuery.data?.map((item) => ({
          item_id: item.id,
          status: checklistAnswers[item.id]?.status ?? "pending",
          notes: checklistAnswers[item.id]?.notes ?? "",
        })),
      });
      return serviceOrderService.finish(id);
    },
    onSuccess: (data) => {
      qc.setQueryData(["service-order", id], data);
      qc.invalidateQueries({ queryKey: ["service-order-attendances", id] });
      toast.success("OS concluída");
    },
    onError: (error) => {
      toast.error(error instanceof Error ? error.message : "Não foi possível finalizar a OS.");
    },
  });
  const uploadMutation = useMutation({
    mutationFn: (files: File[]) => serviceOrderService.uploadFiles(id, files),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["service-order-files", id] });
      toast.success("Evidência enviada");
    },
  });
  const order = orderQuery.data;
  const isClosed = order?.status === "fechada" || order?.status === "cancelada";
  const canFinish = !!order && !isClosed;
  const lastAttendance = useMemo(
    () => (attendancesQuery.data ?? []).at(-1),
    [attendancesQuery.data],
  );

  if (orderQuery.isLoading)
    return (
      <MobileShell>
        <PageHeader title="Ordem de serviço" back="/ordens-servico" />
        <LoadingState label="Carregando OS..." />
      </MobileShell>
    );
  if (orderQuery.isError || !order)
    return (
      <MobileShell>
        <PageHeader title="Ordem de serviço" back="/ordens-servico" />
        <ErrorState
          message="Não foi possível carregar esta ordem de serviço."
          onRetry={() => orderQuery.refetch()}
        />
      </MobileShell>
    );

  return (
    <MobileShell>
      <PageHeader title={`OS #${order.id}`} back="/ordens-servico" />
      <div className="space-y-4 p-4 pb-8">
        <section className="rounded-2xl border border-border bg-card p-4 shadow-card">
          <div className="flex items-start justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {order.tipo_title || "Ordem de serviço"}
              </p>
              <h1 className="mt-1 text-xl font-bold">{order.titulo || "Atendimento em campo"}</h1>
            </div>
            <span className="rounded-full bg-primary/10 px-2 py-1 text-[10px] font-bold text-primary">
              {order.status.replace("_", " ")}
            </span>
          </div>
          <div className="mt-4 space-y-2 text-sm">
            <p className="flex gap-2">
              <MapPin size={17} className="text-primary" />{" "}
              {order.client_name || "Cliente não informado"}
            </p>
            {order.motivo_title && (
              <p className="flex gap-2">
                <Wrench size={17} className="text-primary" /> {order.motivo_title}
              </p>
            )}
            {order.descricao && (
              <p className="flex gap-2 text-muted-foreground">
                <FileText size={17} /> {order.descricao}
              </p>
            )}
          </div>
        </section>

        {!isClosed && (
          <section className="rounded-2xl border border-border bg-card p-4 shadow-card">
            <h2 className="mb-3 font-bold">Atendimento</h2>
            <div className="grid gap-3 sm:grid-cols-2">
              <label className="text-xs font-semibold text-muted-foreground">
                Data e hora inicial
                <input
                  type="datetime-local"
                  value={startDateTime}
                  onChange={(e) => setStartDateTime(e.target.value)}
                  className="mt-1 w-full rounded-xl border border-border bg-background p-3 text-sm font-normal text-foreground outline-none focus:border-primary"
                />
              </label>
              <label className="text-xs font-semibold text-muted-foreground">
                Data e hora final
                <input
                  type="datetime-local"
                  value={endDateTime}
                  onChange={(e) => setEndDateTime(e.target.value)}
                  className="mt-1 w-full rounded-xl border border-border bg-background p-3 text-sm font-normal text-foreground outline-none focus:border-primary"
                />
              </label>
            </div>
            {membersQuery.data && membersQuery.data.filter(isTechnician).length > 0 && (
              <div className="mt-3">
                <p className="text-xs font-semibold text-muted-foreground">Membros da equipe</p>
                <select
                  multiple
                  value={selectedMemberIds.map(String)}
                  onChange={(e) =>
                    setSelectedMemberIds(
                      Array.from(e.target.selectedOptions).map((option) => Number(option.value)),
                    )
                  }
                  className="mt-1 min-h-28 w-full rounded-xl border border-border bg-background p-2 text-sm outline-none focus:border-primary"
                >
                  {membersQuery.data.filter(isTechnician).map((member) => (
                    <option key={member.id} value={member.id}>
                      {member.nome || member.email}
                    </option>
                  ))}
                </select>
                <p className="mt-1 text-[11px] text-muted-foreground">
                  Use Ctrl/Cmd para selecionar mais de um técnico.
                </p>
              </div>
            )}
            <div className="mt-3 space-y-3">
              {[
                [
                  "defeito_apresentado",
                  "Defeito apresentado",
                  "Descreva o problema relatado pelo cliente",
                ],
                ["diagnostico", "Diagnóstico", "Informe a causa ou diagnóstico técnico"],
                [
                  "solucao_encontrada",
                  "Solução encontrada",
                  "Descreva o serviço realizado e a solução aplicada",
                ],
                ["causa_raiz", "Causa raiz", "Informe a causa raiz identificada"],
                [
                  "materiais_utilizados",
                  "Materiais utilizados",
                  "Peças, materiais ou ferramentas utilizados",
                ],
              ].map(([field, label, placeholder]) => (
                <label key={field} className="block text-xs font-semibold text-muted-foreground">
                  {label}
                  <textarea
                    value={report[field as keyof typeof report]}
                    onChange={(e) =>
                      setReport((current) => ({ ...current, [field]: e.target.value }))
                    }
                    placeholder={placeholder}
                    className="mt-1 min-h-16 w-full rounded-xl border border-border bg-background p-3 text-sm font-normal text-foreground outline-none focus:border-primary"
                  />
                </label>
              ))}
            </div>
            {checklistQuery.data && checklistQuery.data.length > 0 && (
              <div className="mt-3 rounded-xl border border-border p-3">
                <p className="text-sm font-bold">Checklist da manutenção</p>
                <div className="mt-3 space-y-3">
                  {checklistQuery.data.map((item) => {
                    const answer = checklistAnswers[item.id] ?? {
                      status: "pending" as const,
                      notes: "",
                    };
                    return (
                      <div key={item.id} className="rounded-lg bg-muted/50 p-3">
                        <p className="text-sm font-semibold">
                          {item.title}{" "}
                          {item.required ? <span className="text-amber-600">*</span> : null}
                        </p>
                        <div className="mt-2 grid gap-2 sm:grid-cols-2">
                          <select
                            value={answer.status}
                            onChange={(e) =>
                              setChecklistAnswers((current) => ({
                                ...current,
                                [item.id]: {
                                  ...answer,
                                  status: e.target.value as typeof answer.status,
                                },
                              }))
                            }
                            className="rounded-lg border border-border bg-background p-2 text-sm"
                          >
                            <option value="pending">Pendente</option>
                            <option value="ok">OK</option>
                            <option value="not_ok">Não OK</option>
                            <option value="na">Não se aplica</option>
                          </select>
                          <input
                            value={answer.notes}
                            onChange={(e) =>
                              setChecklistAnswers((current) => ({
                                ...current,
                                [item.id]: { ...answer, notes: e.target.value },
                              }))
                            }
                            placeholder="Observação"
                            className="rounded-lg border border-border bg-background p-2 text-sm"
                          />
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Descreva o serviço realizado..."
              className="mt-3 min-h-24 w-full rounded-xl border border-border bg-background p-3 text-sm outline-none focus:border-primary"
            />
            <button
              disabled={!canFinish || !startDateTime || !endDateTime || finishMutation.isPending}
              onClick={() => finishMutation.mutate()}
              className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 p-3 text-sm font-bold text-white disabled:opacity-50"
            >
              <CheckCircle2 size={17} /> Registrar atendimento e finalizar
            </button>
            {lastAttendance && (
              <p className="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                <Clock3 size={14} /> Último atendimento:{" "}
                {formatDateTime(lastAttendance.start_datetime)}
              </p>
            )}
          </section>
        )}

        <section className="rounded-2xl border border-border bg-card p-4 shadow-card">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-bold">Evidências</h2>
            <button
              onClick={() => fileInput.current?.click()}
              disabled={uploadMutation.isPending}
              className="flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-xs font-bold text-primary"
            >
              <Camera size={15} /> Anexar
            </button>
          </div>
          <input
            ref={fileInput}
            type="file"
            accept="image/*,.pdf"
            multiple
            className="hidden"
            onChange={(e) => {
              const files = Array.from(e.target.files ?? []);
              if (files.length) uploadMutation.mutate(files);
              e.currentTarget.value = "";
            }}
          />
          {(filesQuery.data ?? []).length === 0 ? (
            <p className="text-sm text-muted-foreground">Nenhuma evidência enviada.</p>
          ) : (
            <div className="space-y-2">
              {filesQuery.data?.map((file) => (
                <a
                  key={file.id}
                  href={file.url}
                  target="_blank"
                  rel="noreferrer"
                  className="flex items-center gap-2 rounded-lg bg-muted p-3 text-sm"
                >
                  <Upload size={15} /> {file.original_file_name || "Arquivo"}
                </a>
              ))}
            </div>
          )}
        </section>
      </div>
    </MobileShell>
  );
}
