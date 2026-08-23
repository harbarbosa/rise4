import { Link } from "@tanstack/react-router";
import { Megaphone, ChevronRight } from "lucide-react";
import { useAnnouncements } from "@/hooks/useAnnouncements";
import { useAuth } from "@/contexts/AuthContext";

function formatRange(start: string, end: string) {
  const fmt = (s: string) => {
    const d = new Date(s + "T00:00:00");
    return d.toLocaleDateString("pt-BR", { day: "2-digit", month: "2-digit" });
  };
  if (!end || start === end) return fmt(start);
  return `${fmt(start)} — ${fmt(end)}`;
}

export function AnnouncementsCard() {
  const { data, isLoading } = useAnnouncements();
  const { user } = useAuth();
  const userId = user?.id ? String(user.id) : "";

  const announcements = (data ?? []).filter((a) => Number(a.deleted) === 0);
  const visiveis = announcements.slice(0, 3);

  return (
    <section className="bg-card rounded-2xl p-4 shadow-card border border-border">
      <div className="flex items-center justify-between mb-3">
        <h2 className="text-sm font-semibold flex items-center gap-2">
          <Megaphone size={16} className="text-primary" />
          Avisos
        </h2>
        {announcements.length > 0 && (
          <span className="text-[11px] text-muted-foreground">
            {announcements.length} {announcements.length === 1 ? "aviso" : "avisos"}
          </span>
        )}
      </div>

      {isLoading && (
        <p className="text-sm text-muted-foreground py-2">Carregando avisos...</p>
      )}

      {!isLoading && announcements.length === 0 && (
        <p className="text-sm text-muted-foreground py-2">Nenhum aviso no momento.</p>
      )}

      <div className="space-y-2.5">
        {visiveis.map((a) => {
          const readIds = (a.read_by || "").split(",").map((s) => s.trim()).filter(Boolean);
          const isUnread = userId ? !readIds.includes(userId) : false;
          return (
            <Link
              key={a.id}
              to="/avisos/$id"
              params={{ id: String(a.id) }}
              className={`block rounded-xl border p-3 active:scale-[0.99] transition-transform ${
                isUnread
                  ? "border-primary/30 bg-primary-soft/40"
                  : "border-border bg-muted/40"
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

      {announcements.length > 0 && (
        <Link
          to="/avisos"
          className="mt-3 w-full inline-flex items-center justify-center gap-1 h-10 rounded-xl border border-border text-sm font-semibold text-primary active:opacity-80"
        >
          Ver avisos <ChevronRight size={14} />
        </Link>
      )}
    </section>
  );
}
