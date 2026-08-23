import { createFileRoute } from "@tanstack/react-router";
import { ChevronRight, ChevronLeft, Inbox, ListChecks, Calendar, User } from "lucide-react";
import { useMemo, useState } from "react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { ProgressRing } from "@/components/ProgressRing";
import { StatusBadge } from "@/components/StatusBadge";
import { LoadingState, ErrorState, EmptyState } from "@/components/QueryState";
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from "@/components/ui/sheet";
import { useProject } from "@/hooks/useProject";
import { useProjectTasks } from "@/hooks/useProjectTasks";
import { useProjectMilestones } from "@/hooks/useProjectMilestones";
import { calcularProgressoProjeto, calcularProgressoEtapa, type MilestoneItem } from "@/services/milestoneService";
import type { TaskListItem } from "@/services/taskService";

export const Route = createFileRoute("/projetos/$id")({
  head: () => ({ meta: [{ title: "Etapas do Projeto — AlfaHP" }] }),
  component: ProjetoDetalhe,
});

function getIniciais(nome: string) {
  return nome
    .split(" ")
    .map((n) => n[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

function findMemberImage(members: { name: string; image?: string | null }[], responsavel: string): string | null {
  const nome = responsavel.trim().toLowerCase();
  const m = members.find((mem) => mem.name.trim().toLowerCase() === nome);
  return m?.image ?? null;
}

function norm(s: string) {
  return s.trim().toLowerCase();
}

function tarefasDaEtapa(milestone: MilestoneItem, tarefas: TaskListItem[]): TaskListItem[] {
  const titulo = norm(milestone.titulo);
  const porId = tarefas.filter((t) => t.milestoneId && t.milestoneId === milestone.id);
  if (porId.length) return porId;
  return tarefas.filter((t) => t.etapa && norm(t.etapa) === titulo);
}

function ProjetoDetalhe() {
  const { id } = Route.useParams();
  const { data: projeto, isLoading: loadingProjeto, isError: errorProjeto, error: errProjeto, refetch: refetchProjeto } = useProject(id);
  const { data: tarefasApi, isLoading: loadingTarefas, isError: errorTarefas, error: errTarefas, refetch: refetchTarefas } = useProjectTasks(id);
  const { data: milestonesApi, isLoading: loadingMilestones, isError: errorMilestones, error: errMilestones, refetch: refetchMilestones } = useProjectMilestones(id);

  const tarefas = tarefasApi ?? [];
  const milestones = milestonesApi ?? [];

  const [etapaSelecionadaId, setEtapaSelecionadaId] = useState<string | null>(null);
  const [tarefaDetalhe, setTarefaDetalhe] = useState<TaskListItem | null>(null);

  const etapaAtual = useMemo(
    () => (etapaSelecionadaId ? milestones.find((m) => m.id === etapaSelecionadaId) ?? null : null),
    [etapaSelecionadaId, milestones],
  );

  const tarefasEtapa = useMemo(
    () => (etapaAtual ? tarefasDaEtapa(etapaAtual, tarefas) : []),
    [etapaAtual, tarefas],
  );

  if (loadingProjeto) {
    return (
      <MobileShell>
        <PageHeader title="Projeto" back="/projetos" />
        <LoadingState label="Carregando projeto..." />
      </MobileShell>
    );
  }

  if (errorProjeto || !projeto) {
    return (
      <MobileShell>
        <PageHeader title="Projeto" back="/projetos" />
        <div className="p-4">
          <ErrorState
            message={errProjeto?.message ?? "Projeto não encontrado."}
            onRetry={() => refetchProjeto()}
          />
        </div>
      </MobileShell>
    );
  }

  const percentualProjeto = milestones.length
    ? calcularProgressoProjeto(milestones, (m) => tarefasDaEtapa(m, tarefas))
    : projeto.percentualExecutado ?? 0;

  const subtitulo = [projeto.cliente, projeto.cidade]
    .filter((v) => v && v !== "Não informado")
    .join(" • ");

  return (
    <MobileShell>
      <PageHeader
        title={etapaAtual ? "Tarefas" : "Etapas"}
        back={etapaAtual ? undefined : "/projetos"}
        onBack={etapaAtual ? () => setEtapaSelecionadaId(null) : undefined}
      />

      {/* Header do Projeto */}
      <div className="bg-primary text-primary-foreground px-5 pt-5 pb-6">
        <div className="flex items-start justify-between gap-4">
          <div className="flex-1 min-w-0">
            <h2 className="text-xl font-bold truncate">{projeto.titulo}</h2>
            {subtitulo && <p className="text-sm opacity-90 mt-0.5 truncate">{subtitulo}</p>}
            <div className="flex items-center gap-2 mt-3">
              <div className="flex-1 h-2 rounded-full bg-white/20 overflow-hidden">
                <div className="h-full rounded-full bg-white transition-all" style={{ width: `${percentualProjeto}%` }} />
              </div>
              <span className="text-sm font-bold">{percentualProjeto}%</span>
            </div>
          </div>
          <div className="shrink-0">
            <div className="bg-white rounded-full p-1.5">
              <ProgressRing value={percentualProjeto} size={56} stroke={5} />
            </div>
          </div>
        </div>
      </div>

      {/* Breadcrumb para etapa selecionada */}
      {etapaAtual && (
        <div className="px-4 py-3 border-b border-border bg-card flex items-center gap-2">
          <button
            onClick={() => setEtapaSelecionadaId(null)}
            className="text-primary text-sm font-semibold inline-flex items-center gap-1"
          >
            <ChevronLeft size={16} /> Etapas
          </button>
          <ChevronRight size={14} className="text-muted-foreground" />
          <span className="text-sm font-semibold text-foreground truncate">{etapaAtual.titulo}</span>
        </div>
      )}

      {/* Lista de Etapas (milestones) */}
      {!etapaAtual && (
        <div className="p-4 space-y-3">
          {loadingMilestones && <LoadingState label="Carregando etapas..." />}

          {!loadingMilestones && errorMilestones && (
            <ErrorState
              message={errMilestones?.message ?? "Não foi possível carregar as etapas."}
              onRetry={() => refetchMilestones()}
            />
          )}

          {!loadingMilestones && !errorMilestones && milestones.length === 0 && (
            <EmptyState
              title="Nenhuma etapa"
              description="Este projeto ainda não possui etapas cadastradas."
              icon={<Inbox className="h-8 w-8 text-muted-foreground" />}
            />
          )}

          {!loadingMilestones &&
            !errorMilestones &&
            milestones.map((m) => {
              const tarefasM = tarefasDaEtapa(m, tarefas);
              const progressoEtapa = calcularProgressoEtapa(m, tarefasM);
              const contribuicao = Math.round((m.peso * progressoEtapa) / 100);
              return (
                <button
                  key={m.id}
                  onClick={() => setEtapaSelecionadaId(m.id)}
                  className="w-full bg-card rounded-xl border border-border shadow-card p-4 flex items-center gap-3 active:opacity-80 text-left"
                >
                  <div className="shrink-0">
                    <ProgressRing value={progressoEtapa} size={48} stroke={4} />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <p className="text-sm font-bold text-foreground truncate flex-1">{m.titulo}</p>
                      <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary shrink-0">
                        Peso {m.peso}%
                      </span>
                    </div>
                    <div className="flex items-center gap-2 mt-1.5 text-[11px] text-muted-foreground flex-wrap">
                      <ListChecks size={12} />
                      <span>{m.totalTarefas} tarefa{m.totalTarefas !== 1 ? "s" : ""}</span>
                      <span>•</span>
                      <span>{m.tarefasConcluidas} concluída{m.tarefasConcluidas !== 1 ? "s" : ""}</span>
                      <span>•</span>
                      <span className="font-semibold text-primary">+{contribuicao}% no projeto</span>
                    </div>
                    <div className="mt-2 h-1.5 w-full rounded-full bg-primary/10 overflow-hidden">
                      <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${progressoEtapa}%` }} />
                    </div>
                  </div>
                  <ChevronRight size={18} className="text-muted-foreground shrink-0" />
                </button>
              );
            })}
        </div>
      )}

      {/* Lista de Tarefas da Etapa */}
      {etapaAtual && (
        <div className="p-4 space-y-3">
          {loadingTarefas && <LoadingState label="Carregando tarefas..." />}

          {!loadingTarefas && errorTarefas && (
            <ErrorState
              message={errTarefas?.message ?? "Não foi possível carregar as tarefas."}
              onRetry={() => refetchTarefas()}
            />
          )}

          {!loadingTarefas && !errorTarefas && tarefasEtapa.length === 0 && (
            <EmptyState
              title="Nenhuma tarefa"
              description="Esta etapa ainda não possui tarefas."
              icon={<Inbox className="h-8 w-8 text-muted-foreground" />}
            />
          )}

          {!loadingTarefas &&
            !errorTarefas &&
            tarefasEtapa.map((t) => (
              <button
                key={t.id}
                onClick={() => setTarefaDetalhe(t)}
                className="w-full bg-card rounded-xl border border-border shadow-card p-3.5 text-left active:opacity-80"
              >
                <div className="flex items-start gap-3">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start gap-2">
                      <p className="text-sm font-semibold leading-snug text-foreground flex-1">{t.titulo}</p>
                      {t.peso > 0 && (
                        <span className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary shrink-0">
                          Peso {t.peso}%
                        </span>
                      )}
                    </div>
                    <div className="mt-2 flex items-center gap-2 flex-wrap">
                      <div className="flex items-center gap-1.5">
                        {(() => {
                          const img = t.responsavelImage || findMemberImage(projeto?.members ?? [], t.responsavel);
                          if (img) {
                            return (
                              <img
                                src={img}
                                alt={t.responsavel}
                                className="w-5 h-5 rounded-full object-cover border border-border"
                                onError={(e) => { (e.target as HTMLImageElement).style.display = "none"; }}
                              />
                            );
                          }
                          return (
                            <div className="w-5 h-5 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[9px] font-bold">
                              {getIniciais(t.responsavel)}
                            </div>
                          );
                        })()}
                        <span className="text-[11px] text-muted-foreground">{t.responsavel}</span>
                      </div>
                      <StatusBadge
                        variant={t.concluida ? "concluida" : t.status === "em_andamento" ? "em_andamento" : t.status === "pendente" ? "programada" : t.status}
                        label={t.concluida ? "Concluída" : undefined}
                      />
                      {t.dataPrevista && (
                        <span className="text-[10px] text-muted-foreground bg-muted px-1.5 py-0.5 rounded">
                          Prev: {t.dataPrevista}
                        </span>
                      )}
                    </div>
                    <div className="mt-2.5">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[10px] text-muted-foreground font-medium">% Executado</span>
                        <span className="text-[10px] font-bold text-primary">{t.percentual}%</span>
                      </div>
                      <div className="h-1.5 w-full rounded-full bg-primary/10 overflow-hidden">
                        <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${t.percentual}%` }} />
                      </div>
                    </div>
                  </div>
                  <ChevronRight size={18} className="text-muted-foreground shrink-0 mt-1" />
                </div>
              </button>
            ))}
        </div>
      )}

      {/* Sheet com detalhes da tarefa */}
      <Sheet open={!!tarefaDetalhe} onOpenChange={(open) => !open && setTarefaDetalhe(null)}>
        <SheetContent side="bottom" className="rounded-t-2xl max-h-[85vh] overflow-y-auto">
          {tarefaDetalhe && (
            <>
              <SheetHeader className="text-left">
                <SheetTitle className="text-base">{tarefaDetalhe.titulo}</SheetTitle>
                {tarefaDetalhe.etapa && (
                  <SheetDescription>Etapa: {tarefaDetalhe.etapa}</SheetDescription>
                )}
              </SheetHeader>

              <div className="mt-4 space-y-4">
                <div>
                  <div className="flex items-center justify-between mb-1.5">
                    <span className="text-xs font-medium text-muted-foreground">% Executado</span>
                    <span className="text-sm font-bold text-primary">{tarefaDetalhe.percentual}%</span>
                  </div>
                  <div className="h-2 w-full rounded-full bg-primary/10 overflow-hidden">
                    <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${tarefaDetalhe.percentual}%` }} />
                  </div>
                </div>

                <div className="grid grid-cols-1 gap-3">
                  <div className="flex items-center gap-2 text-sm">
                    <User size={16} className="text-muted-foreground" />
                    <span className="text-muted-foreground">Responsável:</span>
                    <span className="font-medium text-foreground">{tarefaDetalhe.responsavel}</span>
                  </div>
                  {tarefaDetalhe.dataPrevista && (
                    <div className="flex items-center gap-2 text-sm">
                      <Calendar size={16} className="text-muted-foreground" />
                      <span className="text-muted-foreground">Previsão:</span>
                      <span className="font-medium text-foreground">{tarefaDetalhe.dataPrevista}</span>
                    </div>
                  )}
                  <div className="flex items-center gap-2 text-sm">
                    <span className="text-muted-foreground">Status:</span>
                    <StatusBadge
                      variant={tarefaDetalhe.concluida ? "concluida" : tarefaDetalhe.status === "em_andamento" ? "em_andamento" : "programada"}
                      label={tarefaDetalhe.concluida ? "Concluída" : undefined}
                    />
                  </div>
                </div>
              </div>
            </>
          )}
        </SheetContent>
      </Sheet>
    </MobileShell>
  );
}
