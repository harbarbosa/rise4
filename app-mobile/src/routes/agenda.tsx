import { useState, useMemo } from "react";
import { createFileRoute, useNavigate } from "@tanstack/react-router";
import {
  Filter,
  MapPin,
  Clock,
  Building2,
  ChevronRight,
  X,
  Play,
  CalendarDays,
} from "lucide-react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { StatusBadge } from "@/components/StatusBadge";
import { Avatar } from "@/components/Avatar";
import { LoadingState, ErrorState } from "@/components/QueryState";
import { useExecutionSchedules } from "@/hooks/use-api";

import type { ExecutionSchedule } from "@/services/types";

export const Route = createFileRoute("/agenda")({
  head: () => ({ meta: [{ title: "Agenda — AlfaHP Mobile" }] }),
  component: AgendaPage,
});

const diasSemana = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
const meses = [
  "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
  "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro",
];

function toLocalISO(d: Date) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function parseLocalDate(iso: string) {
  // Trata "YYYY-MM-DD" como data local (evita shift de timezone)
  return new Date(iso.slice(0, 10) + "T00:00:00");
}

function gerarDias(base: Date, quantidade: number) {
  const lista = [];
  for (let i = -2; i < quantidade; i++) {
    const d = new Date(base);
    d.setDate(base.getDate() + i);
    lista.push({
      dataObj: d,
      dataISO: toLocalISO(d),
      diaSemana: diasSemana[d.getDay()],
      dia: d.getDate(),
      mes: d.getMonth(),
      ano: d.getFullYear(),
    });
  }
  return lista;
}

type StatusFiltro = "todas" | "programada" | "em_andamento" | "concluida" | "atrasada";

const STATUS_OPCOES: { value: StatusFiltro; label: string }[] = [
  { value: "todas", label: "Todas" },
  { value: "programada", label: "Programadas" },
  { value: "em_andamento", label: "Em andamento" },
  { value: "concluida", label: "Concluídas" },
  { value: "atrasada", label: "Atrasadas" },
];

function AgendaPage() {
  const hoje = new Date();
  const hojeISO = toLocalISO(hoje);
  const [diaSelecionado, setDiaSelecionado] = useState<string | null>(null);
  const [detalhe, setDetalhe] = useState<ExecutionSchedule | null>(null);
  const [filtrosAbertos, setFiltrosAbertos] = useState(false);
  const [statusFiltro, setStatusFiltro] = useState<StatusFiltro>("todas");
  const [busca, setBusca] = useState("");

  const dias = useMemo(() => gerarDias(hoje, 14), []);
  const query = useExecutionSchedules();
  const agenda = query.data ?? [];

  const filtrar = (lista: ExecutionSchedule[]) => {
    const termo = busca.trim().toLowerCase();
    return lista.filter((a) => {
      if (statusFiltro !== "todas" && a.status !== statusFiltro) return false;
      if (!termo) return true;
      return [a.projeto, a.cliente, a.cidade, a.endereco, a.responsavel]
        .filter(Boolean)
        .some((v) => String(v).toLowerCase().includes(termo));
    });
  };

  const scheduleEnd = (a: ExecutionSchedule) => a.data_fim || a.data;
  const inRange = (iso: string, a: ExecutionSchedule) => iso >= a.data && iso <= scheduleEnd(a);

  const atividadesDoDia = diaSelecionado
    ? filtrar(agenda.filter((a) => inRange(diaSelecionado, a)))
    : filtrar(agenda.filter((a) => scheduleEnd(a) >= hojeISO));
  const atividadesAtrasadas = filtrar(
    agenda.filter(
      (a) =>
        a.status === "atrasada" &&
        (!diaSelecionado || !inRange(diaSelecionado, a)) &&
        scheduleEnd(a) < hojeISO,
    ),
  );

  const filtrosAtivos =
    (statusFiltro !== "todas" ? 1 : 0) + (busca.trim() ? 1 : 0) + (diaSelecionado ? 1 : 0);

  const dataRef = parseLocalDate(diaSelecionado ?? hojeISO);
  const mesAtual = meses[dataRef.getMonth()];
  const anoAtual = dataRef.getFullYear();


  return (
    <MobileShell>
      <PageHeader
        title="Agenda"
        right={
          <button
            onClick={() => setFiltrosAbertos((v) => !v)}
            className={`relative w-10 h-10 -mr-2 flex items-center justify-center rounded-full active:bg-white/10 ${filtrosAbertos ? "bg-white/15" : ""}`}
            aria-label="Filtros"
          >
            <Filter size={20} />
            {filtrosAtivos > 0 && (
              <span className="absolute top-1 right-1 min-w-[16px] h-[16px] px-1 rounded-full bg-white text-primary text-[10px] font-bold flex items-center justify-center">
                {filtrosAtivos}
              </span>
            )}
          </button>
        }
      />


      {/* Calendário */}
      <div className="bg-primary text-primary-foreground px-4 pb-5">
        <div className="flex items-center justify-between mb-3">
          <p className="text-sm font-semibold">{mesAtual} {anoAtual}</p>
          <CalendarDays size={16} className="opacity-70" />
        </div>
        <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-hide -mx-4 px-4" style={{ scrollbarWidth: "none" }}>
          {dias.map((d) => {
            const ativo = d.dataISO === diaSelecionado;
            const temAtividade = agenda.some((a) => inRange(d.dataISO, a));
            const isHoje = d.dataISO === hojeISO;
            return (
              <button
                key={d.dataISO}
                onClick={() => setDiaSelecionado((curr) => (curr === d.dataISO ? null : d.dataISO))}
                className={`flex flex-col items-center gap-1 py-2.5 px-3 rounded-2xl min-w-[52px] shrink-0 transition-all ${
                  ativo
                    ? "bg-white text-primary shadow-lg"
                    : isHoje
                    ? "bg-white/15 text-primary-foreground ring-1 ring-white/30"
                    : "text-primary-foreground/70"
                }`}
              >
                <span className="text-[10px] font-medium leading-none">{d.diaSemana}</span>
                <span className="text-base font-bold leading-none mt-0.5">{d.dia}</span>
                {temAtividade && !ativo && (
                  <span className="w-1 h-1 rounded-full bg-white/60 mt-0.5" />
                )}
              </button>
            );
          })}
        </div>
      </div>

      {/* Painel de filtros */}
      {filtrosAbertos && (
        <div className="px-4 py-4 bg-muted/30 border-b border-border space-y-3 animate-slide-up">
          <div>
            <label className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide">Buscar</label>
            <input
              type="text"
              value={busca}
              onChange={(e) => setBusca(e.target.value)}
              placeholder="Projeto, cliente, cidade..."
              className="mt-1 w-full h-10 rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>
          <div>
            <label className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide">Status</label>
            <div className="mt-1 flex flex-wrap gap-2">
              {STATUS_OPCOES.map((opt) => {
                const ativo = statusFiltro === opt.value;
                return (
                  <button
                    key={opt.value}
                    onClick={() => setStatusFiltro(opt.value)}
                    className={`px-3 h-8 rounded-full text-xs font-semibold border transition-colors ${
                      ativo
                        ? "bg-primary text-primary-foreground border-primary"
                        : "bg-background text-foreground border-border"
                    }`}
                  >
                    {opt.label}
                  </button>
                );
              })}
            </div>
          </div>
          {filtrosAtivos > 0 && (
            <button
              onClick={() => { setStatusFiltro("todas"); setBusca(""); setDiaSelecionado(null); }}
              className="text-xs font-semibold text-primary"
            >
              Limpar filtros
            </button>
          )}
        </div>
      )}

      {/* Lista */}
      <div className="px-4 py-4 space-y-4">
        {query.isLoading && <LoadingState label="Carregando agenda..." />}
        {query.isError && (
          <ErrorState
            message="Não foi possível carregar a agenda."
            onRetry={() => query.refetch()}
          />
        )}

        {!query.isLoading && !query.isError && atividadesDoDia.length === 0 && atividadesAtrasadas.length === 0 && (
          <div className="text-center py-12">
            <CalendarDays size={40} className="mx-auto text-muted-foreground/30 mb-3" />
            <p className="text-sm text-muted-foreground">Nenhuma atividade programada</p>
          </div>
        )}

        {atividadesDoDia.length > 0 && (
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <h2 className="text-sm font-semibold text-muted-foreground">
                {!diaSelecionado
                  ? "Próximas atividades"
                  : diaSelecionado === hojeISO
                  ? "Hoje"
                  : parseLocalDate(diaSelecionado).toLocaleDateString("pt-BR")}
              </h2>
              {diaSelecionado && (
                <button
                  onClick={() => setDiaSelecionado(null)}
                  className="text-xs font-semibold text-primary inline-flex items-center gap-1"
                >
                  <X size={12} /> Limpar filtro
                </button>
              )}
            </div>
            {atividadesDoDia.map((a) => (
              <AgendaCard key={a.id} a={a} onClick={() => setDetalhe(a)} />
            ))}
          </div>
        )}

        {atividadesAtrasadas.length > 0 && (
          <div className="space-y-3">
            <h2 className="text-sm font-semibold text-destructive pt-2">Atrasadas</h2>
            {atividadesAtrasadas.map((a) => (
              <AgendaCard key={a.id} a={a} onClick={() => setDetalhe(a)} />
            ))}
          </div>
        )}
      </div>

      {/* Drawer de detalhes */}
      {detalhe && (
        <div className="fixed inset-0 z-50 flex flex-col justify-end">
          <div
            className="absolute inset-0 bg-black/40 backdrop-blur-sm"
            onClick={() => setDetalhe(null)}
          />
          <div className="relative bg-card rounded-t-3xl shadow-2xl animate-slide-up max-h-[85vh] overflow-y-auto">
            <div className="sticky top-0 bg-card z-10 px-5 pt-5 pb-3 flex items-center justify-between border-b border-border">
              <h2 className="text-lg font-bold">Detalhes da atividade</h2>
              <button
                onClick={() => setDetalhe(null)}
                className="w-9 h-9 rounded-full bg-muted flex items-center justify-center active:scale-95"
              >
                <X size={18} />
              </button>
            </div>

            <DetalheBody detalhe={detalhe} />
          </div>
        </div>
      )}
    </MobileShell>
  );
}

function DetalheBody({ detalhe }: { detalhe: ExecutionSchedule }) {
  const initials = (name: string) =>
    name.split(" ").map((p) => p[0]).filter(Boolean).slice(0, 2).join("").toUpperCase();

  const hasPeriodo = detalhe.data_fim && detalhe.data_fim !== detalhe.data;

  // Pega as pessoas da própria agenda
  const todosNomes = detalhe.member_names ?? [];
  const liderNome = detalhe.leader_name;
  const participantes = liderNome
    ? todosNomes.filter((n) => n !== liderNome)
    : todosNomes;
  const notas = detalhe.notes ?? detalhe.descricao;

  return (
    <div className="px-5 py-4 space-y-5">
      <div className="flex items-center justify-between">
        <StatusBadge variant={detalhe.status} />
      </div>

      <div>
        <p className="text-xs text-muted-foreground mb-1">Projeto</p>
        <h3 className="text-lg font-bold leading-tight">{detalhe.projeto}</h3>
      </div>

      <div className="flex items-start gap-3 p-3.5 rounded-xl bg-muted/50 border border-border">
        <div className="w-10 h-10 rounded-lg bg-primary-soft text-primary grid place-items-center shrink-0">
          <Building2 size={18} />
        </div>
        <div className="min-w-0">
          <p className="text-xs text-muted-foreground">Cliente</p>
          <p className="text-sm font-semibold">{detalhe.cliente}</p>
        </div>
      </div>

      <div className={`grid ${hasPeriodo ? "grid-cols-2" : "grid-cols-1"} gap-3`}>
        <InfoTile
          icon={CalendarDays}
          label="Data de início"
          value={parseLocalDate(detalhe.data).toLocaleDateString("pt-BR")}
        />
        {hasPeriodo && (
          <InfoTile
            icon={CalendarDays}
            label="Data de fim"
            value={parseLocalDate(detalhe.data_fim!).toLocaleDateString("pt-BR")}
          />
        )}
      </div>

      <div>
        <p className="text-xs text-muted-foreground mb-2">Pessoas envolvidas</p>
        {todosNomes.length === 0 && (
          <p className="text-sm text-muted-foreground">{detalhe.responsavel || "Nenhum membro vinculado"}</p>
        )}
        {todosNomes.length > 0 && (
          <div className="space-y-2">
            {liderNome && (
              <div className="flex items-center gap-3 p-3 rounded-xl bg-primary-soft/60 border border-primary/20">
                <Avatar initials={initials(liderNome)} size={36} />
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-semibold leading-tight truncate">{liderNome}</p>
                </div>
                <span className="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-full bg-primary text-primary-foreground">
                  Líder
                </span>
              </div>
            )}
            {participantes.map((nome) => (
              <div key={nome} className="flex items-center gap-3 p-3 rounded-xl bg-muted/50">
                <Avatar initials={initials(nome)} size={32} />
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium leading-tight truncate">{nome}</p>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {notas && (
        <div>
          <p className="text-xs text-muted-foreground mb-1">Notas</p>
          <p className="text-sm text-muted-foreground leading-relaxed whitespace-pre-wrap">{notas}</p>
        </div>
      )}

      <LancarAtividadeButton detalhe={detalhe} />
    </div>
  );
}

function LancarAtividadeButton({ detalhe }: { detalhe: ExecutionSchedule }) {
  const navigate = useNavigate();
  const participantes = (detalhe.schedule_members ?? [])
    .map((m) => m.user_id)
    .filter(Boolean);
  return (
    <button
      onClick={() =>
        navigate({
          to: "/lancar-atividade",
          search: {
            projetoId: detalhe.project_id,
            participantes: participantes.length > 0 ? participantes.join(",") : undefined,
          },
        })
      }
      className="w-full h-13 rounded-xl bg-primary text-primary-foreground font-semibold text-sm flex items-center justify-center gap-2 shadow-elevated active:opacity-90"
    >
      <Play size={18} fill="currentColor" /> Lançar Atividade
    </button>
  );
}


function AgendaCard({ a, onClick }: { a: ExecutionSchedule; onClick: () => void }) {
  const accentMap: Record<string, string> = {
    em_andamento: "border-l-info",
    programada: "border-l-primary",
    atrasada: "border-l-destructive",
    concluida: "border-l-success",
  };
  return (
    <button
      onClick={onClick}
      className={`w-full text-left bg-card rounded-2xl border border-border border-l-4 ${accentMap[a.status]} p-4 shadow-card active:scale-[0.99] transition-transform`}
    >
      <div className="flex items-center justify-between mb-2">
        <div className="flex items-center gap-2">
          <Clock size={14} className="text-primary" />
          <span className="text-sm font-semibold text-primary">{a.hora_inicio} — {a.hora_fim}</span>
        </div>
        <StatusBadge variant={a.status} />
      </div>

      <h3 className="text-base font-bold leading-tight mb-0.5">{a.projeto}</h3>
      <p className="text-sm text-muted-foreground mb-2">{a.cliente}</p>

      <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
        <span className="inline-flex items-center gap-1">
          <MapPin size={12} /> {a.cidade}
        </span>
        {a.data_fim && a.data_fim !== a.data && (
          <span className="inline-flex items-center gap-1">
            <CalendarDays size={12} />
            {parseLocalDate(a.data).toLocaleDateString("pt-BR")} — {parseLocalDate(a.data_fim).toLocaleDateString("pt-BR")}
          </span>
        )}
      </div>

      <div className="mt-3 flex items-center justify-between border-t border-border pt-3">
        <div className="flex items-center gap-2">
          <Avatar
            initials={a.responsavel.split(" ").map((p) => p[0]).slice(0, 2).join("")}
            size={28}
          />
          <span className="text-xs font-medium text-foreground">{a.responsavel}</span>
        </div>
        <ChevronRight size={18} className="text-muted-foreground" />
      </div>
    </button>
  );
}

function InfoTile({
  icon: Icon,
  label,
  value,
  avatar,
}: {
  icon: typeof Clock;
  label: string;
  value: string;
  avatar?: string;
}) {
  return (
    <div className="flex items-start gap-2.5 p-3 rounded-xl bg-muted/50">
      {avatar ? (
        <Avatar initials={avatar} size={28} />
      ) : (
        <div className="w-8 h-8 rounded-lg bg-primary-soft text-primary grid place-items-center shrink-0">
          <Icon size={15} />
        </div>
      )}
      <div className="min-w-0">
        <p className="text-[11px] text-muted-foreground">{label}</p>
        <p className="text-sm font-semibold leading-tight">{value}</p>
      </div>
    </div>
  );
}
