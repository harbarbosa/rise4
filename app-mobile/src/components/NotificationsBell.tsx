import { useMemo } from "react";
import { Bell, CheckCheck, Inbox, AlertCircle, Info, CheckCircle2, AlertTriangle, Briefcase, Clock, Wallet, Calendar, Megaphone, ListChecks } from "lucide-react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { ScrollArea } from "@/components/ui/scroll-area";
import { notificationService, type AppNotification, type NotificationsListResult } from "@/services/notificationService";
import { useAuth } from "@/contexts/AuthContext";

const NOTIFICATIONS_QK = ["notifications"] as const;
const EMPTY_RESULT: NotificationsListResult = {
  items: [], unreadCount: 0, total: 0, limit: 0, offset: 0, hasMore: false, nextCursor: null,
};

function iconForCategory(categoria?: string | null, tipo?: string) {
  switch ((categoria ?? "").toLowerCase()) {
    case "projeto": case "project": return Briefcase;
    case "ponto": case "timeclock": return Clock;
    case "despesa": case "expense": case "reembolso": return Wallet;
    case "agenda": case "atividade": case "schedule": return Calendar;
    case "aviso": case "notice": return Megaphone;
    case "pendencia": case "task": return ListChecks;
    default: return iconFor(tipo);
  }
}

function iconFor(tipo?: string) {
  switch (tipo) {
    case "success": return CheckCircle2;
    case "warning": return AlertTriangle;
    case "error": return AlertCircle;
    default: return Info;
  }
}

function toneFor(tipo?: string) {
  switch (tipo) {
    case "success": return "text-success bg-success/10";
    case "warning": return "text-warning bg-warning/10";
    case "error": return "text-destructive bg-destructive/10";
    default: return "text-info bg-info/10";
  }
}

function formatWhen(iso?: string | null): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  const diffMs = Date.now() - d.getTime();
  const min = Math.floor(diffMs / 60_000);
  if (min < 1) return "agora";
  if (min < 60) return `${min} min`;
  const h = Math.floor(min / 60);
  if (h < 24) return `${h}h`;
  const days = Math.floor(h / 24);
  if (days < 7) return `${days}d`;
  return d.toLocaleDateString("pt-BR", { day: "2-digit", month: "2-digit" });
}

export function NotificationsBell() {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const enabled = !!user;

  const { data = EMPTY_RESULT, isLoading } = useQuery<NotificationsListResult>({
    queryKey: NOTIFICATIONS_QK,
    queryFn: () => notificationService.list({ limit: 20, offset: 0 }),
    enabled,
    staleTime: 60_000,
    refetchInterval: 2 * 60_000,
    refetchOnWindowFocus: true,
  });

  const items = data.items;
  const unread = useMemo(
    () => (data.unreadCount ?? items.filter((n) => !n.lida).length),
    [data.unreadCount, items],
  );

  const patchUnread = (count: number | null) => {
    if (count == null) return;
    queryClient.setQueryData<NotificationsListResult>(NOTIFICATIONS_QK, (old) =>
      old ? { ...old, unreadCount: count } : old,
    );
  };

  const markRead = useMutation({
    mutationFn: (id: string) => notificationService.markRead(id),
    onMutate: async (id) => {
      await queryClient.cancelQueries({ queryKey: NOTIFICATIONS_QK });
      const prev = queryClient.getQueryData<NotificationsListResult>(NOTIFICATIONS_QK);
      queryClient.setQueryData<NotificationsListResult>(NOTIFICATIONS_QK, (old) => {
        if (!old) return old;
        const wasUnread = !!old.items.find((n) => n.id === id && !n.lida);
        return {
          ...old,
          items: old.items.map((n) => (n.id === id ? { ...n, lida: true } : n)),
          unreadCount: Math.max(0, (old.unreadCount ?? 0) - (wasUnread ? 1 : 0)),
        };
      });
      return { prev };
    },
    onError: (_e, _id, ctx) => {
      if (ctx?.prev) queryClient.setQueryData(NOTIFICATIONS_QK, ctx.prev);
    },
    onSuccess: (res) => patchUnread(res.unreadCount),
  });

  const markAll = useMutation({
    mutationFn: () => notificationService.markAllRead(),
    onMutate: async () => {
      await queryClient.cancelQueries({ queryKey: NOTIFICATIONS_QK });
      const prev = queryClient.getQueryData<NotificationsListResult>(NOTIFICATIONS_QK);
      queryClient.setQueryData<NotificationsListResult>(NOTIFICATIONS_QK, (old) =>
        old
          ? { ...old, items: old.items.map((n) => ({ ...n, lida: true })), unreadCount: 0 }
          : old,
      );
      return { prev };
    },
    onError: (_e, _v, ctx) => {
      if (ctx?.prev) queryClient.setQueryData(NOTIFICATIONS_QK, ctx.prev);
    },
    onSuccess: (res) => patchUnread(res.unreadCount ?? 0),
  });

  const handleOpenLink = (n: AppNotification) => {
    if (!n.lida) markRead.mutate(n.id);
    const link = n.link;
    if (!link) return;
    if (/^https?:\/\//i.test(link)) {
      window.open(link, "_blank", "noopener,noreferrer");
    } else {
      try {
        navigate({ to: link as never });
      } catch {
        window.location.href = link;
      }
    }
  };

  return (
    <Popover>
      <PopoverTrigger asChild>
        <button
          type="button"
          aria-label={`Notificações${unread > 0 ? ` (${unread} não lidas)` : ""}`}
          className="relative w-10 h-10 flex items-center justify-center rounded-full active:bg-white/10"
        >
          <Bell size={20} />
          {unread > 0 && (
            <span className="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-destructive text-destructive-foreground text-[10px] font-bold flex items-center justify-center ring-2 ring-primary">
              {unread > 9 ? "9+" : unread}
            </span>
          )}
        </button>
      </PopoverTrigger>
      <PopoverContent
        align="end"
        sideOffset={8}
        className="w-[88vw] max-w-sm p-0 overflow-hidden"
      >
        <div className="flex items-center justify-between px-4 py-3 border-b border-border">
          <div>
            <p className="text-sm font-semibold text-foreground">Notificações</p>
            <p className="text-[11px] text-muted-foreground">
              {unread > 0 ? `${unread} não lida${unread > 1 ? "s" : ""}` : "Tudo em dia"}
            </p>
          </div>
          {unread > 0 && (
            <button
              type="button"
              onClick={() => markAll.mutate()}
              className="text-[11px] font-medium text-primary inline-flex items-center gap-1 active:opacity-70"
            >
              <CheckCheck size={13} /> Marcar todas
            </button>
          )}
        </div>

        <ScrollArea className="max-h-[60vh]">
          {isLoading && (
            <div className="p-6 text-center text-sm text-muted-foreground">
              Carregando…
            </div>
          )}

          {!isLoading && items.length === 0 && (
            <div className="p-8 text-center">
              <div className="mx-auto w-12 h-12 rounded-full bg-muted grid place-items-center mb-2">
                <Inbox size={20} className="text-muted-foreground" />
              </div>
              <p className="text-sm font-medium text-foreground">Sem notificações</p>
              <p className="text-[11px] text-muted-foreground mt-1">
                Você verá aqui avisos do sistema.
              </p>
            </div>
          )}

          {!isLoading && items.length > 0 && (
            <ul className="divide-y divide-border">
              {items.map((n) => {
                const Icon = iconForCategory(n.categoria, n.tipo);
                const isUrgent = n.prioridade === "urgent" || n.prioridade === "high";
                return (
                  <li key={n.id}>
                    <button
                      type="button"
                      onClick={() => handleOpenLink(n)}
                      className={`w-full text-left flex gap-3 px-4 py-3 active:bg-muted/60 transition-colors ${n.lida ? "" : "bg-primary/[0.04]"}`}
                    >
                      <span className={`w-9 h-9 rounded-lg grid place-items-center shrink-0 ${toneFor(n.tipo)}`}>
                        <Icon size={16} />
                      </span>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-start justify-between gap-2">
                          <p className={`text-sm leading-tight ${n.lida ? "font-medium text-foreground/80" : "font-semibold text-foreground"}`}>
                            {n.titulo}
                          </p>
                          {!n.lida && (
                            <span className="mt-1 w-2 h-2 rounded-full bg-primary shrink-0" aria-hidden />
                          )}
                        </div>
                        {n.mensagem && (
                          <p className="text-[12px] text-muted-foreground mt-0.5 line-clamp-2">
                            {n.mensagem}
                          </p>
                        )}
                        <div className="flex items-center gap-2 mt-1">
                          {n.categoria && (
                            <span className="text-[10px] uppercase tracking-wide text-muted-foreground/80 font-medium">
                              {n.categoria}
                            </span>
                          )}
                          {isUrgent && (
                            <span className="text-[10px] px-1.5 rounded-full bg-destructive/10 text-destructive font-semibold">
                              {n.prioridade === "urgent" ? "URGENTE" : "ALTA"}
                            </span>
                          )}
                          {n.criada_em && (
                            <span className="text-[10px] text-muted-foreground ml-auto">{formatWhen(n.criada_em)}</span>
                          )}
                        </div>
                      </div>
                    </button>
                  </li>
                );
              })}
            </ul>
          )}
        </ScrollArea>
      </PopoverContent>
    </Popover>
  );
}
