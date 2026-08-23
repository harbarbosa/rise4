import { createFileRoute, Link } from "@tanstack/react-router";
import { ClipboardList, MapPin, RefreshCw, Wrench } from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { ErrorState, LoadingState } from "@/components/QueryState";
import { serviceOrderService, type ServiceOrder } from "@/services/serviceOrderService";

export const Route = createFileRoute("/ordens-servico/")({
  head: () => ({ meta: [{ title: "Ordens de Serviço — AlfaHP" }] }),
  component: ServiceOrdersPage,
});

function statusLabel(status: string) {
  return (
    (
      {
        aberta: "Aberta",
        em_andamento: "Em andamento",
        fechada: "Concluída",
        cancelada: "Cancelada",
      } as Record<string, string>
    )[status] ?? status
  );
}

function statusClass(status: string) {
  if (status === "fechada") return "bg-emerald-100 text-emerald-700";
  if (status === "em_andamento") return "bg-blue-100 text-blue-700";
  if (status === "cancelada") return "bg-red-100 text-red-700";
  return "bg-amber-100 text-amber-700";
}

function OrderCard({ order }: { order: ServiceOrder }) {
  return (
    <Link
      to="/ordens-servico/$id"
      params={{ id: order.id }}
      className="block rounded-2xl border border-border bg-card p-4 shadow-card active:scale-[0.99]"
    >
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            OS #{order.id}
          </p>
          <h2 className="mt-1 truncate text-base font-bold">
            {order.titulo || "Ordem de serviço"}
          </h2>
        </div>
        <span
          className={`shrink-0 rounded-full px-2 py-1 text-[10px] font-bold ${statusClass(order.status)}`}
        >
          {statusLabel(order.status)}
        </span>
      </div>
      <div className="mt-3 space-y-1 text-sm text-muted-foreground">
        <p className="flex items-center gap-2">
          <MapPin size={15} /> {order.client_name || "Cliente não informado"}
        </p>
        {order.tipo_title && (
          <p className="flex items-center gap-2">
            <Wrench size={15} /> {order.tipo_title}
          </p>
        )}
      </div>
    </Link>
  );
}

function ServiceOrdersPage() {
  const query = useQuery({
    queryKey: ["service-orders"],
    queryFn: () => serviceOrderService.list(),
  });
  const orders = query.data ?? [];

  return (
    <MobileShell>
      <PageHeader title="Ordens de serviço" back="/" />
      <div className="space-y-4 p-4">
        <div className="rounded-2xl bg-primary p-4 text-primary-foreground">
          <div className="flex items-center gap-3">
            <ClipboardList size={24} />
            <div>
              <p className="text-xs opacity-80">Atendimento em campo</p>
              <p className="text-lg font-bold">Minhas ordens de serviço</p>
            </div>
          </div>
          <p className="mt-2 text-xs opacity-80">
            Abra uma OS, registre o atendimento e envie as evidências do serviço.
          </p>
        </div>

        {query.isLoading && <LoadingState label="Carregando ordens de serviço..." />}
        {query.isError && (
          <ErrorState
            message="Não foi possível carregar as ordens de serviço."
            onRetry={() => query.refetch()}
          />
        )}
        {!query.isLoading && !query.isError && orders.length === 0 && (
          <div className="rounded-2xl border border-dashed border-border p-10 text-center text-muted-foreground">
            <ClipboardList className="mx-auto mb-3" size={32} />
            <p className="text-sm font-semibold">Nenhuma OS atribuída</p>
            <p className="mt-1 text-xs">
              Quando uma ordem for atribuída a você, ela aparecerá aqui.
            </p>
          </div>
        )}
        <div className="space-y-3">
          {orders.map((order) => (
            <OrderCard key={order.id} order={order} />
          ))}
        </div>
        {orders.length > 0 && (
          <button
            onClick={() => query.refetch()}
            className="mx-auto flex items-center gap-2 text-xs font-semibold text-primary"
          >
            <RefreshCw size={14} /> Atualizar
          </button>
        )}
      </div>
    </MobileShell>
  );
}
