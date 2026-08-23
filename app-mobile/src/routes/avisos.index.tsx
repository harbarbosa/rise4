import { createFileRoute, Link } from "@tanstack/react-router";
import { Megaphone } from "lucide-react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { useAnnouncements } from "@/hooks/useAnnouncements";
import { useAuth } from "@/contexts/AuthContext";

export const Route = createFileRoute("/avisos/")({
  head: () => ({ meta: [{ title: "Avisos — AlfaHP" }] }),
  component: AvisosPage,
});

function formatRange(start: string, end: string) {
  const fmt = (s: string) =>
    new Date(s + "T00:00:00").toLocaleDateString("pt-BR", {
      day: "2-digit",
      month: "2-digit",
      year: "2-digit",
    });
  if (!end || start === end) return fmt(start);
  return `${fmt(start)} — ${fmt(end)}`;
}

function AvisosPage() {
  const { data, isLoading } = useAnnouncements();
  const { user } = useAuth();
  const userId = user?.id ? String(user.id) : "";

  const announcements = (data ?? []).filter((a) => Number(a.deleted) === 0);

  return (
    <MobileShell>
      <PageHeader title="Avisos" back="/" />

      <div className="p-4 space-y-3">
        {isLoading && (
          <p className="text-sm text-muted-foreground py-2">Carregando avisos...</p>
        )}

        {!isLoading && announcements.length === 0 && (
          <div className="text-center py-12 px-6">
            <div className="w-16 h-16 mx-auto rounded-full bg-muted flex items-center justify-center mb-4">
              <Megaphone size={28} className="text-muted-foreground" />
            </div>
            <h3 className="text-sm font-semibold mb-1">Nenhum aviso</h3>
            <p className="text-xs text-muted-foreground leading-relaxed">
              Quando houver novos avisos, eles aparecerão aqui.
            </p>
          </div>
        )}

        {announcements.map((a) => {
          const readIds = (a.read_by || "")
            .split(",")
            .map((s) => s.trim())
            .filter(Boolean);
          const isUnread = userId ? !readIds.includes(userId) : false;
          return (
            <Link
              key={a.id}
              to="/avisos/$id"
              params={{ id: String(a.id) }}
              className={`block rounded-2xl border p-4 shadow-card active:scale-[0.99] transition-transform ${
                isUnread
                  ? "border-primary/30 bg-primary-soft/40"
                  : "border-border bg-card"
              }`}
            >
              <div className="flex items-center justify-between mb-1">
                <h3 className="text-sm font-bold leading-tight flex items-center gap-2">
                  {a.title}
                  {isUnread && (
                    <span className="w-1.5 h-1.5 rounded-full bg-primary inline-block" />
                  )}
                </h3>
                <span className="text-[10px] font-semibold text-muted-foreground shrink-0">
                  {formatRange(a.start_date, a.end_date)}
                </span>
              </div>
              <div
                className="text-xs text-muted-foreground leading-snug line-clamp-2 [&_p]:m-0"
                dangerouslySetInnerHTML={{ __html: a.description || "" }}
              />
            </Link>
          );
        })}
      </div>
    </MobileShell>
  );
}
