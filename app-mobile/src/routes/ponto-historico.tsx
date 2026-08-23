import { createFileRoute } from "@tanstack/react-router";
import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Calendar, Filter, MapPin, LogIn, LogOut, Coffee, Play, Clock } from "lucide-react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { LoadingState, ErrorState } from "@/components/QueryState";
import { fetchPontoHistory, normalizePunchType, translatePunchStatus, translatePunchType, type PontoPunch, type PunchType } from "@/services/pontoService";
import { ApiError } from "@/services/api";

export const Route = createFileRoute("/ponto-historico")({
  head: () => ({ meta: [{ title: "Histórico de Ponto — AlfaHP Mobile" }] }),
  component: HistoricoPage,
});

const PUNCH_ICON: Record<PunchType, typeof LogIn> = {
  entrada: LogIn,
  saida_intervalo: Coffee,
  retorno_intervalo: Play,
  saida: LogOut,
};

const FILTERS: { k: PunchType | "all"; l: string }[] = [
  { k: "all", l: "Todas" },
  { k: "entrada", l: "Entrada" },
  { k: "saida_intervalo", l: "S. intervalo" },
  { k: "retorno_intervalo", l: "R. intervalo" },
  { k: "saida", l: "Saída" },
];

function todayISO() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}
function daysAgoISO(n: number) {
  const d = new Date();
  d.setDate(d.getDate() - n);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function HistoricoPage() {
  const [start, setStart] = useState(daysAgoISO(7));
  const [end, setEnd] = useState(todayISO());
  const [typeFilter, setTypeFilter] = useState<PunchType | "all">("all");

  const q = useQuery<PontoPunch[], ApiError>({
    queryKey: ["ponto", "history", start, end],
    queryFn: async () => (await fetchPontoHistory(start, end)) as PontoPunch[],
    enabled: !!start && !!end,
  });

  const filtered = useMemo(() => {
    const list = q.data ?? [];
    const f = typeFilter === "all" ? list : list.filter((p) => normalizePunchType(p.type) === typeFilter);
    return [...f].sort((a, b) => `${b.date} ${b.time}`.localeCompare(`${a.date} ${a.time}`));
  }, [q.data, typeFilter]);

  const grouped = useMemo(() => {
    const map = new Map<string, PontoPunch[]>();
    filtered.forEach((p) => {
      const k = p.date;
      if (!map.has(k)) map.set(k, []);
      map.get(k)!.push(p);
    });
    return Array.from(map.entries());
  }, [filtered]);

  return (
    <MobileShell>
      <PageHeader title="Histórico de Ponto" back="/ponto" />

      <div className="p-4 space-y-4">
        <section className="bg-card rounded-2xl border border-border shadow-card p-4 space-y-3">
          <div className="flex items-center gap-2">
            <Calendar size={16} className="text-muted-foreground" />
            <p className="text-[11px] uppercase tracking-wide font-semibold text-muted-foreground">
              Período
            </p>
          </div>
          <div className="grid grid-cols-2 gap-2">
            <label className="text-xs text-muted-foreground">
              De
              <input
                type="date"
                value={start}
                max={end}
                onChange={(e) => setStart(e.target.value)}
                className="mt-1 w-full h-10 rounded-lg border border-border bg-background px-2 text-sm"
              />
            </label>
            <label className="text-xs text-muted-foreground">
              Até
              <input
                type="date"
                value={end}
                min={start}
                max={todayISO()}
                onChange={(e) => setEnd(e.target.value)}
                className="mt-1 w-full h-10 rounded-lg border border-border bg-background px-2 text-sm"
              />
            </label>
          </div>
          <div className="flex gap-1.5 flex-wrap pt-1">
            {[
              { l: "Hoje", s: todayISO(), e: todayISO() },
              { l: "7 dias", s: daysAgoISO(7), e: todayISO() },
              { l: "30 dias", s: daysAgoISO(30), e: todayISO() },
            ].map((p) => (
              <button
                key={p.l}
                onClick={() => {
                  setStart(p.s);
                  setEnd(p.e);
                }}
                className="text-[11px] px-2.5 py-1 rounded-full border border-border bg-muted/40 active:bg-muted"
              >
                {p.l}
              </button>
            ))}
          </div>

          <div className="flex items-center gap-2 pt-2 border-t border-border">
            <Filter size={14} className="text-muted-foreground" />
            <div className="flex gap-1.5 flex-wrap">
              {FILTERS.map((f) => (
                <button
                  key={f.k}
                  onClick={() => setTypeFilter(f.k)}
                  className={`text-[11px] px-2.5 py-1 rounded-full border ${
                    typeFilter === f.k
                      ? "border-primary bg-primary text-primary-foreground"
                      : "border-border bg-muted/40"
                  }`}
                >
                  {f.l}
                </button>
              ))}
            </div>
          </div>
        </section>

        {q.isLoading && <LoadingState label="Carregando histórico..." />}
        {q.isError && (
          <ErrorState
            message={q.error?.message ?? "Não foi possível carregar o histórico."}
            onRetry={() => q.refetch()}
          />
        )}

        {!q.isLoading && !q.isError && grouped.length === 0 && (
          <p className="text-center text-sm text-muted-foreground py-8">
            Nenhuma marcação encontrada no período.
          </p>
        )}

        {grouped.map(([date, items]) => (
          <section
            key={date}
            className="bg-card rounded-2xl border border-border shadow-card overflow-hidden"
          >
            <div className="px-5 pt-4 pb-2 flex items-center justify-between">
              <h3 className="text-sm font-semibold">{formatBRDate(date)}</h3>
              <span className="text-[11px] text-muted-foreground">{items.length} marcação(ões)</span>
            </div>
            <ul>
              {items.map((p, i) => {
                const type = normalizePunchType(p.type) ?? "entrada";
                const Icon = PUNCH_ICON[type] ?? Clock;
                return (
                  <li
                    key={p.id}
                    className={`flex items-center gap-3 px-5 py-3 ${
                      i < items.length - 1 ? "border-b border-border" : ""
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
                        {p.time}
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
          </section>
        ))}
      </div>
    </MobileShell>
  );
}

function formatBRDate(iso: string) {
  const [y, m, d] = iso.split("-");
  if (!y || !m || !d) return iso;
  return `${d}/${m}/${y}`;
}
