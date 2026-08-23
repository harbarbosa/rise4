import { createFileRoute, Link } from "@tanstack/react-router";
import { Search, Calendar, MapPin, Loader2 } from "lucide-react";
import { useState, useMemo, useEffect } from "react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { StatusBadge } from "@/components/StatusBadge";
import { ProgressRing } from "@/components/ProgressRing";
import { LoadingState, ErrorState, EmptyState } from "@/components/QueryState";
import { useProjects, useProjectsSearch } from "@/hooks/useProjects";
import type { ProjectListItem } from "@/services/projectService";

export const Route = createFileRoute("/projetos/")({
  head: () => ({ meta: [{ title: "Projetos — AlfaHP Mobile" }] }),
  component: ProjetosPage,
});

type Filtro = "todos" | "em_andamento" | "concluidos" | "atrasados";

const filtros: { key: Filtro; label: string }[] = [
  { key: "todos", label: "Todos" },
  { key: "em_andamento", label: "Em andamento" },
  { key: "concluidos", label: "Concluídos" },
  { key: "atrasados", label: "Atrasados" },
];

function getCorBarra(status: ProjectListItem["status"]) {
  switch (status) {
    case "em_andamento":
      return "bg-success";
    case "concluida":
      return "bg-primary";
    case "atrasada":
      return "bg-destructive";
    default:
      return "bg-muted";
  }
}

function useDebounced<T>(value: T, delay = 400) {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(t);
  }, [value, delay]);
  return debounced;
}

function ProjetosPage() {
  const [filtro, setFiltro] = useState<Filtro>("todos");
  const [busca, setBusca] = useState("");
  const buscaDebounced = useDebounced(busca, 400);

  const listQuery = useProjects();
  const searchQuery = useProjectsSearch(buscaDebounced);

  const usandoBusca = buscaDebounced.trim().length >= 2;
  const isLoading = usandoBusca ? searchQuery.isLoading : listQuery.isLoading;
  const isError = usandoBusca ? searchQuery.isError : listQuery.isError;
  const error = usandoBusca ? searchQuery.error : listQuery.error;
  const refetch = usandoBusca ? searchQuery.refetch : listQuery.refetch;
  const source = (usandoBusca ? searchQuery.data : listQuery.data) ?? [];

  const projetosFiltrados = useMemo(() => {
    let lista = source;
    if (filtro === "em_andamento") lista = lista.filter((p) => p.status === "em_andamento");
    if (filtro === "concluidos") lista = lista.filter((p) => p.status === "concluida");
    if (filtro === "atrasados") lista = lista.filter((p) => p.status === "atrasada");
    return lista;
  }, [source, filtro]);

  return (
    <MobileShell>
      <PageHeader title="Projetos" />

      {/* Barra de busca */}
      <div className="bg-primary px-4 pb-4">
        <div className="relative">
          <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            placeholder="Buscar projetos, clientes..."
            value={busca}
            onChange={(e) => setBusca(e.target.value)}
            className="w-full h-11 pl-10 pr-10 rounded-xl bg-card text-foreground placeholder:text-muted-foreground text-sm outline-none"
          />
          {usandoBusca && searchQuery.isFetching && (
            <Loader2
              size={16}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground animate-spin"
            />
          )}
        </div>
      </div>

      {/* Filtros */}
      <div className="px-4 pt-3 pb-1 overflow-x-auto">
        <div className="flex gap-2 min-w-max">
          {filtros.map((f) => {
            const ativo = filtro === f.key;
            return (
              <button
                key={f.key}
                onClick={() => setFiltro(f.key)}
                className={`px-3.5 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap transition-colors ${
                  ativo
                    ? "bg-primary text-primary-foreground"
                    : "bg-card text-muted-foreground border border-border"
                }`}
              >
                {f.label}
              </button>
            );
          })}
        </div>
      </div>

      {/* Contador */}
      {!isLoading && !isError && (
        <div className="px-4 pt-2 pb-1">
          <p className="text-xs text-muted-foreground font-medium">
            {projetosFiltrados.length} projeto{projetosFiltrados.length !== 1 ? "s" : ""} encontrado
            {projetosFiltrados.length !== 1 ? "s" : ""}
          </p>
        </div>
      )}

      {/* Estados */}
      <div className="px-4 py-2 space-y-3">
        {isLoading && <LoadingState label="Carregando projetos..." />}

        {isError && !isLoading && (
          <ErrorState
            message={error?.message ?? "Não foi possível carregar os projetos."}
            onRetry={() => refetch()}
          />
        )}

        {!isLoading && !isError && projetosFiltrados.length === 0 && (
          <EmptyState
            title="Nenhum projeto encontrado"
            description={
              usandoBusca
                ? `Não há resultados para "${buscaDebounced}".`
                : "Ajuste os filtros ou tente outra busca."
            }
          />
        )}

        {!isLoading &&
          !isError &&
          projetosFiltrados.map((p) => (
            <Link key={p.id} to="/projetos/$id" params={{ id: p.id }} className="block">
              <article className="bg-card rounded-2xl border border-border shadow-card overflow-hidden active:scale-[0.99] transition-transform">
                <div className="flex">
                  <div className={`w-1.5 ${getCorBarra(p.status)} flex-shrink-0`} />
                  <div className="flex-1 p-4">
                    {/* Título + progress ring */}
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex-1 min-w-0">
                        <h3 className="text-[15px] font-bold truncate leading-snug">
                          {p.titulo}
                        </h3>
                        <div className="flex items-center gap-1.5 mt-1">
                          <MapPin size={12} className="text-muted-foreground shrink-0" />
                          <span className="text-xs text-muted-foreground truncate">
                            {p.cliente}
                          </span>
                        </div>
                      </div>
                      {p.percentualExecutado !== null && (
                        <div className="flex flex-col items-center shrink-0">
                          <ProgressRing value={p.percentualExecutado} size={48} stroke={4} />
                          <span className="text-[10px] font-semibold text-primary mt-0.5">
                            {p.percentualExecutado}%
                          </span>
                        </div>
                      )}
                    </div>

                    {/* Tipo */}

                    {/* Labels */}
                    {Array.isArray(p.labels) && p.labels.length > 0 && (
                      <div className="mt-2 flex flex-wrap gap-1">
                        {p.labels.map((l) => (
                          <span
                            key={l}
                            className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary"
                          >
                            {l}
                          </span>
                        ))}
                      </div>
                    )}

                    {/* Datas */}
                    <div className="mt-3 flex items-center gap-3 text-[11px] text-muted-foreground flex-wrap">
                      <div className="flex items-center gap-1">
                        <Calendar size={12} />
                        <span>Início {p.dataInicio || "—"}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <Calendar size={12} />
                        <span>Prazo {p.prazo || "—"}</span>
                      </div>
                    </div>

                    {/* Barra de progresso */}
                    {p.percentualExecutado !== null && (
                      <div className="mt-3">
                        <div className="h-1.5 w-full rounded-full bg-primary/10 overflow-hidden">
                          <div
                            className="h-full rounded-full bg-primary transition-all"
                            style={{ width: `${p.percentualExecutado}%` }}
                          />
                        </div>
                      </div>
                    )}

                    {/* Status */}
                    <div className="mt-3 flex items-center justify-between">
                      <StatusBadge variant={p.status} />
                      {p.percentualExecutado !== null && (
                        <span className="text-[10px] text-muted-foreground">
                          {p.percentualExecutado}% executado
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              </article>
            </Link>
          ))}
      </div>
    </MobileShell>
  );
}
