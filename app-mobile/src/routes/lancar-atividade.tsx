import { createFileRoute, useNavigate, useSearch } from "@tanstack/react-router";
import { Camera, X, Check, FileText, Clock, Loader2, Users } from "lucide-react";
import { useEffect, useMemo, useRef, useState, type ChangeEvent } from "react";
import { toast } from "sonner";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { ErrorState } from "@/components/QueryState";
import { useProjects } from "@/hooks/useProjects";
import { useProjectTasks } from "@/hooks/useProjectTasks";
import { useCreateTimesheet } from "@/hooks/useCreateTimesheet";
import { useAuth } from "@/contexts/AuthContext";
import { uploadPhotos } from "@/services/photoService";
import { enqueueTimesheet } from "@/services/offlineQueueService";
import { useOfflineQueue } from "@/hooks/useOfflineQueue";
import type { TimesheetInput } from "@/services/types";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";


interface LancarAtividadeSearch {
  projetoId?: string;
  tarefaId?: string;
  participantes?: string;
}

export const Route = createFileRoute("/lancar-atividade")({
  head: () => ({ meta: [{ title: "Lançar Atividade — AlfaHP" }] }),
  validateSearch: (s: Record<string, unknown>): LancarAtividadeSearch => ({
    projetoId: typeof s.projetoId === "string" ? s.projetoId : undefined,
    tarefaId: typeof s.tarefaId === "string" ? s.tarefaId : undefined,
    participantes: typeof s.participantes === "string" ? s.participantes : undefined,
  }),
  component: LancarAtividade,
});

function calcHorasHHMM(ini: string, fim: string): string {
  if (!ini || !fim) return "00:00";
  const [ih, im] = ini.split(":").map(Number);
  const [fh, fm] = fim.split(":").map(Number);
  let mins = fh * 60 + fm - (ih * 60 + im);
  if (mins < 0) mins += 24 * 60;
  const h = Math.floor(mins / 60);
  const m = mins % 60;
  return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
}

function calcHorasDecimal(ini: string, fim: string): number {
  if (!ini || !fim) return 0;
  const [ih, im] = ini.split(":").map(Number);
  const [fh, fm] = fim.split(":").map(Number);
  let mins = fh * 60 + fm - (ih * 60 + im);
  if (mins < 0) mins += 24 * 60;
  return Number((mins / 60).toFixed(2));
}

function todayISO(): string {
  return new Date().toISOString().slice(0, 10);
}

interface FotoLocal {
  file: File;
  previewUrl: string;
}

type CondicaoClimatica = "claro" | "nublado" | "chuvoso" | "n/a";

const opcoesClima: { value: CondicaoClimatica; label: string }[] = [
  { value: "claro", label: "Claro" },
  { value: "nublado", label: "Nublado" },
  { value: "chuvoso", label: "Chuvoso" },
  { value: "n/a", label: "N/A" },
];

function LancarAtividade() {
  const navigate = useNavigate();
  const search = useSearch({ from: "/lancar-atividade" });
  const { user } = useAuth();

  const projectsQuery = useProjects();
  const projetos = useMemo(
    () => (projectsQuery.data ?? []).filter((p) => p.status !== "concluida"),
    [projectsQuery.data],
  );

  const [projetoId, setProjetoId] = useState<string>(search.projetoId ?? "");
  const [etapa, setEtapa] = useState<string>("");
  const [tarefaId, setTarefaId] = useState<string>(search.tarefaId ?? "");
  const [data, setData] = useState<string>(todayISO());
  const [hIni, setHIni] = useState("09:00");
  const [hFim, setHFim] = useState("17:00");
  const [pctTotal, setPctTotal] = useState(0);
  const [descricao, setDescricao] = useState("");
  const [observacoes, setObservacoes] = useState("");
  const [tempoManha, setTempoManha] = useState<CondicaoClimatica>("claro");
  const [tempoTarde, setTempoTarde] = useState<CondicaoClimatica>("claro");
  const [tempoNoite, setTempoNoite] = useState<CondicaoClimatica>("n/a");
  const [fotos, setFotos] = useState<FotoLocal[]>([]);
  const [participantes, setParticipantes] = useState<string[]>(
    search.participantes ? search.participantes.split(",").filter(Boolean) : [],
  );
  const fileRef = useRef<HTMLInputElement>(null);

  // Se o projeto pré-selecionado estiver concluído (ou ausente), pega o primeiro disponível
  useEffect(() => {
    if (projetos.length === 0) return;
    if (!projetoId || !projetos.some((p) => p.id === projetoId)) {
      setProjetoId(projetos[0].id);
    }
  }, [projetoId, projetos]);

  const tasksQuery = useProjectTasks(projetoId);
  const todasTarefas = tasksQuery.data ?? [];

  // Etapas disponíveis no projeto
  const etapas = useMemo(() => {
    const set = new Set<string>();
    todasTarefas.forEach((t) => {
      if (t.etapa && t.etapa.trim()) set.add(t.etapa.trim());
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b, "pt-BR"));
  }, [todasTarefas]);

  // Reseta etapa e participantes quando o usuário troca de projeto (não no mount inicial)
  const prevProjetoIdRef = useRef<string>(projetoId);
  useEffect(() => {
    if (prevProjetoIdRef.current && prevProjetoIdRef.current !== projetoId) {
      setEtapa("");
      setParticipantes([]);
    }
    prevProjetoIdRef.current = projetoId;
  }, [projetoId]);

  // Membros disponíveis (exclui o próprio usuário)
  const projetoSelecionado = useMemo(
    () => projetos.find((p) => p.id === projetoId),
    [projetos, projetoId],
  );
  const membrosDisponiveis = useMemo(
    () => (projetoSelecionado?.members ?? []).filter((m) => m.id !== String(user?.id ?? "")),
    [projetoSelecionado, user?.id],
  );

  function toggleParticipante(id: string) {
    setParticipantes((prev) =>
      prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id],
    );
  }

  // Limpa etapa se não existir mais na lista
  useEffect(() => {
    if (etapa && !etapas.includes(etapa)) {
      setEtapa("");
    }
  }, [etapas, etapa]);

  // Tarefas filtradas pela etapa selecionada (só mostra após escolher etapa)
  const tarefas = useMemo(() => {
    if (etapas.length === 0) return todasTarefas;
    if (!etapa) return [];
    return todasTarefas.filter((t) => (t.etapa ?? "") === etapa);
  }, [todasTarefas, etapa, etapas.length]);

  // Ajusta tarefa selecionada quando a lista mudar
  useEffect(() => {
    if (!projetoId) return;
    if (tarefas.length === 0) {
      setTarefaId("");
      return;
    }
    if (!tarefas.some((t) => t.id === tarefaId)) {
      setTarefaId(tarefas[0].id);
    }
  }, [projetoId, tarefas, tarefaId]);

  const tarefaSelecionada = useMemo(
    () => tarefas.find((t) => t.id === tarefaId),
    [tarefas, tarefaId],
  );
  const pctExecutado = tarefaSelecionada?.percentual ?? 0;
  const pctLancado = Math.max(0, pctTotal - pctExecutado);
  const pctTotalAposLancamento = pctTotal;

  // Inicia o slider no percentual já executado da tarefa
  useEffect(() => {
    setPctTotal(pctExecutado);
  }, [tarefaId, pctExecutado]);


  // Libera object URLs das fotos locais ao desmontar
  useEffect(() => {
    return () => {
      fotos.forEach((f) => URL.revokeObjectURL(f.previewUrl));
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const horasLabel = useMemo(() => calcHorasHHMM(hIni, hFim), [hIni, hFim]);
  const horasDecimal = useMemo(() => calcHorasDecimal(hIni, hFim), [hIni, hFim]);

  const createMutation = useCreateTimesheet();
  const { online, pending } = useOfflineQueue();


  function handlePickFiles(e: ChangeEvent<HTMLInputElement>) {
    const files = Array.from(e.target.files ?? []);
    if (files.length === 0) return;
    const novos: FotoLocal[] = files.map((file) => ({
      file,
      previewUrl: URL.createObjectURL(file),
    }));
    setFotos((prev) => [...prev, ...novos]);
    // Reseta input para permitir reescolher o mesmo arquivo
    e.target.value = "";
  }

  function removerFoto(index: number) {
    setFotos((prev) => {
      const removida = prev[index];
      if (removida) URL.revokeObjectURL(removida.previewUrl);
      return prev.filter((_, i) => i !== index);
    });
  }

  function resetFormulario() {
    fotos.forEach((f) => URL.revokeObjectURL(f.previewUrl));
    setFotos([]);
    setHIni("09:00");
    setHFim("17:00");
    setPctTotal(0);
    setDescricao("");
    setObservacoes("");
    setTempoManha("n/a");
    setTempoTarde("n/a");
    setTempoNoite("n/a");
    setParticipantes([]);
  }

  async function handleFinalizar() {
    if (!user?.id) {
      toast.error("Sessão expirada. Faça login novamente.");
      return;
    }
    if (!projetoId) {
      toast.error("Selecione um projeto.");
      return;
    }
    if (horasDecimal <= 0) {
      toast.error("Informe um intervalo de horas válido.");
      return;
    }
    if (!descricao.trim()) {
      toast.error("Descreva o serviço realizado.");
      return;
    }

    const allUserIds = [user.id, ...participantes];
    const payload: TimesheetInput = {
      user_id: allUserIds.length > 1 ? allUserIds : user.id,
      project_id: projetoId,
      start_time: `${data} ${hIni}:00`,
      end_time: `${data} ${hFim}:00`,
      hours: "0",
      atividade_realizada: descricao.trim(),
      observacoes: observacoes.trim() || undefined,
      tempo_manha: tempoManha,
      tempo_tarde: tempoTarde,
      tempo_noite: tempoNoite,
    };

    if (tarefaId) {
      if (pctTotal <= pctExecutado) {
        toast.error("Ajuste o percentual executado.");
        return;
      }
      if (pctTotal > 100) {
        toast.error("O total não pode ultrapassar 100%.");
        return;
      }
      payload.task_id = tarefaId;
      payload.percentage_executed = pctLancado; // delta lançado hoje
    }


    // Modo offline: enfileira para sincronizar quando voltar a internet.
    if (!online) {
      enqueueTimesheet(projetoId, payload);
      toast.success(
        "Registro salvo no aparelho e será sincronizado quando houver internet.",
      );
      resetFormulario();
      navigate({ to: "/projetos/$id", params: { id: projetoId } });
      return;
    }

    try {
      const created = await createMutation.mutateAsync({
        projectId: projetoId,
        payload,
      });

      // Upload das fotos selecionadas.
      // ⚠️ Endpoint de upload de fotos precisa ser confirmado na API.
      // Por isso o erro do upload NÃO interrompe o fluxo principal.
      if (fotos.length > 0) {
        try {
          await uploadPhotos(
            created.id,
            fotos.map((f) => f.file),
          );
        } catch (err) {
          // eslint-disable-next-line no-console
          console.warn("[lancar-atividade] Falha ao enviar fotos:", err);
          toast.warning(
            "Atividade salva, mas não foi possível enviar as fotos agora.",
          );
        }
      }

      toast.success("Atividade lançada com sucesso!");
      resetFormulario();
      navigate({ to: "/projetos/$id", params: { id: projetoId } });
    } catch (e) {
      // Falha de rede em tempo de execução: cai para fila offline.
      const isNetworkErr =
        e instanceof TypeError ||
        (e instanceof Error && /network|fetch|timeout/i.test(e.message));
      if (isNetworkErr || !navigator.onLine) {
        enqueueTimesheet(projetoId, payload);
        toast.success(
          "Registro salvo no aparelho e será sincronizado quando houver internet.",
        );
        resetFormulario();
        navigate({ to: "/projetos/$id", params: { id: projetoId } });
        return;
      }
      const msg =
        e instanceof Error ? e.message : "Não foi possível lançar a atividade.";
      toast.error(msg);
    }
  }


  const carregandoProjetos = projectsQuery.isLoading;
  const enviando = createMutation.isPending;

  return (
    <MobileShell>
      <PageHeader title="Lançar Atividade" back="/" />
      <div className="p-4 space-y-4">
        {(!online || pending > 0) && (
          <div
            className={`rounded-xl border px-3 py-2 text-xs font-medium flex items-center gap-2 ${
              !online
                ? "border-[oklch(0.85_0.05_60)] bg-[oklch(0.97_0.03_80)] text-[oklch(0.4_0.18_60)]"
                : "border-info/30 bg-info/10 text-info"
            }`}
          >
            <span
              className={`inline-block w-2 h-2 rounded-full ${
                !online ? "bg-[oklch(0.55_0.18_30)]" : "bg-info animate-pulse"
              }`}
            />
            <span className="flex-1">
              {!online
                ? "Você está offline. Os registros serão salvos no aparelho."
                : `Sincronizando ${pending} lançamento${pending > 1 ? "s" : ""} pendente${pending > 1 ? "s" : ""}...`}
            </span>
            {pending > 0 && (
              <span className="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-white/60 text-[oklch(0.4_0.18_60)]">
                {pending} pendente{pending > 1 ? "s" : ""}
              </span>
            )}
          </div>
        )}


        {projectsQuery.isError && (
          <ErrorState
            message="Não foi possível carregar projetos."
            onRetry={() => projectsQuery.refetch()}
          />
        )}

        {/* Projeto */}
        <Field label="Projeto" required>
          <select
            value={projetoId}
            onChange={(e) => setProjetoId(e.target.value)}
            disabled={carregandoProjetos || projetos.length === 0}
            className="w-full h-12 rounded-xl bg-card border border-border px-3 text-sm font-medium outline-none focus:border-primary disabled:opacity-60"
          >
            {carregandoProjetos && <option>Carregando...</option>}
            {!carregandoProjetos && projetos.length === 0 && (
              <option value="">Nenhum projeto disponível</option>
            )}
            {projetos.map((p) => (
              <option key={p.id} value={p.id}>
                {p.titulo}
              </option>
            ))}
          </select>
        </Field>

        {/* Etapa */}
        {etapas.length > 0 && (
          <Field label="Etapa" required>
            <select
              value={etapa}
              onChange={(e) => setEtapa(e.target.value)}
              disabled={tasksQuery.isLoading}
              className="w-full h-12 rounded-xl bg-card border border-border px-3 text-sm font-medium outline-none focus:border-primary disabled:opacity-60"
            >
              <option value="">Selecione uma etapa...</option>
              {etapas.map((et) => (
                <option key={et} value={et}>
                  {et}
                </option>
              ))}
            </select>
          </Field>
        )}

        {/* Tarefa — só aparece depois de escolher a etapa (ou se o projeto não tem etapas) */}
        {(etapas.length === 0 || etapa) && (
          <Field label="Tarefa">
            <select
              value={tarefaId}
              onChange={(e) => setTarefaId(e.target.value)}
              disabled={tasksQuery.isLoading || tarefas.length === 0}
              className="w-full h-12 rounded-xl bg-card border border-border px-3 text-sm font-medium outline-none focus:border-primary disabled:opacity-60"
            >
              {tasksQuery.isLoading && <option>Carregando tarefas...</option>}
              {!tasksQuery.isLoading && tarefas.length === 0 && (
                <option value="">Sem tarefas — lançamento avulso</option>
              )}
              {tarefas.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.titulo} {typeof t.percentual === "number" ? `(${t.percentual}%)` : ""}
                </option>
              ))}
            </select>
            {tarefaId && (
              <p className="mt-1 text-[11px] text-muted-foreground">
                Tarefa selecionada exige percentual executado.
              </p>
            )}
          </Field>
        )}

        {/* Participantes adicionais */}
        {projetoId && (
          <Field label={`Participantes (${participantes.length + 1})`}>
            <div className="bg-card border border-border rounded-2xl p-3 space-y-2">
              <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <Users size={14} />
                <span>Você está incluído automaticamente. Marque outros membros que participaram.</span>
              </div>
              {membrosDisponiveis.length === 0 ? (
                <p className="text-[11px] text-muted-foreground italic px-1 py-2">
                  Nenhum outro membro disponível neste projeto.
                </p>
              ) : (
                <div className="space-y-1.5">
                  {membrosDisponiveis.map((m) => {
                    const checked = participantes.includes(m.id);
                    return (
                      <button
                        type="button"
                        key={m.id}
                        onClick={() => toggleParticipante(m.id)}
                        className={`w-full flex items-center gap-3 rounded-xl border px-3 py-2 text-left transition-colors ${
                          checked
                            ? "border-primary bg-primary/5"
                            : "border-border bg-card active:bg-muted"
                        }`}
                      >
                        <div
                          className={`w-5 h-5 rounded border-2 flex items-center justify-center shrink-0 ${
                            checked ? "bg-primary border-primary" : "border-border bg-card"
                          }`}
                        >
                          {checked && <Check size={12} className="text-primary-foreground" />}
                        </div>
                        <div className="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[11px] font-bold shrink-0">
                          {m.name
                            .split(" ")
                            .map((n) => n[0])
                            .join("")
                            .slice(0, 2)
                            .toUpperCase()}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-semibold truncate">{m.name}</p>
                          {m.jobTitle && (
                            <p className="text-[11px] text-muted-foreground truncate">{m.jobTitle}</p>
                          )}
                        </div>
                        {m.isLeader && (
                          <span className="text-[9px] font-bold uppercase tracking-wide bg-primary/10 text-primary px-1.5 py-0.5 rounded">
                            Líder
                          </span>
                        )}
                      </button>
                    );
                  })}
                </div>
              )}
            </div>
          </Field>
        )}


        {/* Data */}
        <Field label="Data" required>
          <input
            type="date"
            value={data}
            onChange={(e) => setData(e.target.value)}
            className="w-full h-12 rounded-xl bg-card border border-border px-3 text-sm font-medium outline-none focus:border-primary"
          />
        </Field>

        {/* Horários */}
        <div className="grid grid-cols-2 gap-3">
          <Field label="Hora início" required>
            <input
              type="time"
              value={hIni}
              onChange={(e) => setHIni(e.target.value)}
              className="w-full h-12 rounded-xl bg-card border border-border px-3 text-sm font-medium outline-none focus:border-primary"
            />
          </Field>
          <Field label="Hora fim" required>
            <input
              type="time"
              value={hFim}
              onChange={(e) => setHFim(e.target.value)}
              className="w-full h-12 rounded-xl bg-card border border-border px-3 text-sm font-medium outline-none focus:border-primary"
            />
          </Field>
        </div>

        {/* Horas calculadas */}
        <div className="bg-gradient-to-br from-primary to-[oklch(0.32_0.14_258)] rounded-2xl p-4 shadow-card text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center">
              <Clock size={22} />
            </div>
            <div>
              <p className="text-[11px] uppercase tracking-wide opacity-80">Horas trabalhadas</p>
              <p className="text-2xl font-bold tabular-nums leading-tight">{horasLabel}</p>
            </div>
          </div>
          <span className="text-[10px] bg-white/15 px-2 py-1 rounded-full font-medium">AUTO</span>
        </div>

        {/* Percentual */}
        {tarefaId && (
          <Field label="Percentual executado hoje *">
            <div className="bg-card border border-border rounded-2xl p-4 space-y-3">
              <div className="grid grid-cols-3 gap-2 text-center">
                <div className="rounded-xl bg-muted/50 p-2">
                  <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Já executado</p>
                  <p className="text-base font-bold tabular-nums">{pctExecutado}%</p>
                </div>
                <div className="rounded-xl bg-primary/10 p-2">
                  <p className="text-[10px] uppercase tracking-wide text-primary">Lançando hoje</p>
                  <p className="text-base font-bold text-primary tabular-nums">+{pctLancado}%</p>
                </div>
                <div className="rounded-xl bg-muted/50 p-2">
                  <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Total</p>
                  <p className="text-base font-bold tabular-nums">{pctTotalAposLancamento}%</p>
                </div>
              </div>
              <div className="relative w-full h-6 flex items-center">
                {/* Barra de progresso visual */}
                <div className="absolute inset-0 flex items-center">
                  <div className="w-full h-2.5 rounded-full bg-muted/50 overflow-hidden">
                    <div
                      className="h-full rounded-full bg-primary transition-all"
                      style={{ width: `${pctTotal}%` }}
                    />
                  </div>
                </div>
                {/* Slider nativo (invisível visualmente, mantém interação) */}
                <input
                  type="range"
                  min={0}
                  max={100}
                  step={1}
                  value={pctTotal}
                  onChange={(e) =>
                    setPctTotal(Math.max(pctExecutado, Number(e.target.value)))
                  }
                  disabled={pctExecutado >= 100}
                  className="relative w-full h-6 opacity-0 cursor-pointer disabled:cursor-not-allowed"
                />
              </div>
              <div className="flex justify-between text-[10px] text-muted-foreground">
                <span>0%</span>
                <span>100%</span>
              </div>
              {pctExecutado >= 100 && (
                <p className="text-[11px] text-muted-foreground">
                  Esta tarefa já está 100% executada.
                </p>
              )}
            </div>
          </Field>
        )}


        {/* Descrição */}
        <Field label="Descrição do serviço" required>
          <textarea
            value={descricao}
            onChange={(e) => setDescricao(e.target.value)}
            placeholder="Descreva o serviço realizado..."
            maxLength={1000}
            className="w-full min-h-[96px] rounded-xl bg-card border border-border px-3 py-3 text-sm outline-none focus:border-primary resize-none"
          />
        </Field>

        {/* Observações */}
        <Field label="Observações">
          <textarea
            value={observacoes}
            onChange={(e) => setObservacoes(e.target.value)}
            placeholder="Observações adicionais, pendências, intercorrências..."
            maxLength={1000}
            className="w-full min-h-[72px] rounded-xl bg-card border border-border px-3 py-3 text-sm outline-none focus:border-primary resize-none"
          />
        </Field>

        {/* Condições climáticas */}
        <Field label="Condições climáticas">
          <div className="space-y-4 rounded-2xl border border-border bg-card p-4">
            <WeatherPeriod label="Manhã" value={tempoManha} onChange={setTempoManha} />
            <WeatherPeriod label="Tarde" value={tempoTarde} onChange={setTempoTarde} />
            <WeatherPeriod label="Noite" value={tempoNoite} onChange={setTempoNoite} />
          </div>
        </Field>

        {/* Fotos — miniaturas locais antes do envio */}
        <Field label={`Fotos (${fotos.length})`}>
          <div className="grid grid-cols-4 gap-2">
            {fotos.map((f, i) => (
              <div
                key={`${f.file.name}-${i}`}
                className="relative aspect-square rounded-xl border border-border overflow-hidden bg-muted"
              >
                <img
                  src={f.previewUrl}
                  alt={f.file.name}
                  className="w-full h-full object-cover"
                />
                <button
                  onClick={() => removerFoto(i)}
                  className="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white flex items-center justify-center"
                  aria-label="Remover foto"
                >
                  <X size={12} />
                </button>
                <div className="absolute bottom-1 left-1 text-[9px] text-white/90 bg-black/40 px-1.5 rounded">
                  {String(i + 1).padStart(2, "0")}
                </div>
              </div>
            ))}
            <button
              type="button"
              onClick={() => fileRef.current?.click()}
              className="aspect-square rounded-xl bg-card border-2 border-dashed border-border flex flex-col items-center justify-center text-muted-foreground active:bg-muted gap-1"
            >
              <Camera size={20} />
              <span className="text-[9px] font-medium">Adicionar</span>
            </button>
            <input
              ref={fileRef}
              type="file"
              accept="image/*"
              capture="environment"
              multiple
              onChange={handlePickFiles}
              className="hidden"
            />
          </div>
          {/* ⚠️ Endpoint de upload de fotos precisa ser confirmado na API.
              As fotos selecionadas só serão enviadas quando a função
              uploadPhotos() estiver ligada ao endpoint real. */}
          <p className="mt-2 text-[10px] text-muted-foreground">
            As fotos serão enviadas junto com o lançamento.
          </p>
        </Field>

        {/* Botões */}
        <div className="grid grid-cols-2 gap-3 pt-2">
          <button
            disabled={enviando}
            className="h-12 rounded-xl border border-border text-foreground font-semibold text-sm active:bg-muted flex items-center justify-center gap-2 disabled:opacity-60"
          >
            <FileText size={16} />
            Salvar rascunho
          </button>
          <button
            onClick={handleFinalizar}
            disabled={enviando || !projetoId}
            className="h-12 rounded-xl bg-primary text-primary-foreground font-semibold text-sm active:opacity-90 flex items-center justify-center gap-2 shadow-card disabled:opacity-60"
          >
            {enviando ? <Loader2 size={16} className="animate-spin" /> : <Check size={16} />}
            {enviando ? "Enviando..." : "Finalizar"}
          </button>
        </div>
      </div>
    </MobileShell>
  );
}

function WeatherPeriod({
  label,
  value,
  onChange,
}: {
  label: string;
  value: CondicaoClimatica;
  onChange: (value: CondicaoClimatica) => void;
}) {
  return (
    <div className="space-y-2">
      <p className="text-sm font-semibold text-foreground">{label}</p>
      <RadioGroup
        value={value}
        onValueChange={(next) => onChange(next as CondicaoClimatica)}
        className="grid grid-cols-2 gap-2 sm:grid-cols-4"
        aria-label={`Condição climática no período da ${label.toLowerCase()}`}
      >
        {opcoesClima.map((opcao) => (
          <label
            key={opcao.value}
            className="flex min-h-10 cursor-pointer items-center gap-2 rounded-xl border border-border px-3 text-sm has-[[data-state=checked]]:border-primary has-[[data-state=checked]]:bg-primary/5"
          >
            <RadioGroupItem value={opcao.value} />
            {opcao.label}
          </label>
        ))}
      </RadioGroup>
    </div>
  );
}

function Field({ label, children, required }: { label: string; children: React.ReactNode; required?: boolean }) {
  return (
    <div>
      <label className="block text-xs font-semibold text-muted-foreground mb-1.5 uppercase tracking-wide">
        {label} {required && <span className="text-destructive normal-case">*</span>}
      </label>
      {children}
    </div>
  );
}
