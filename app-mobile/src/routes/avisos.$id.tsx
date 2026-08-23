import { createFileRoute } from "@tanstack/react-router";
import { Calendar, Users } from "lucide-react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { useAnnouncement } from "@/hooks/useAnnouncements";

export const Route = createFileRoute("/avisos/$id")({
  head: () => ({ meta: [{ title: "Aviso — AlfaHP" }] }),
  component: AvisoDetailPage,
});

function formatDate(s: string) {
  if (!s) return "—";
  return new Date(s + "T00:00:00").toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}

function AvisoDetailPage() {
  const { id } = Route.useParams();
  const { data: aviso, isLoading } = useAnnouncement(id);

  return (
    <MobileShell>
      <PageHeader title="Aviso" back="/avisos" />

      <div className="p-4">
        {isLoading && (
          <p className="text-sm text-muted-foreground py-4">Carregando aviso...</p>
        )}

        {!isLoading && !aviso && (
          <div className="text-center py-12">
            <p className="text-sm text-muted-foreground">Aviso não encontrado.</p>
          </div>
        )}

        {aviso && (
          <article className="bg-card rounded-2xl p-5 shadow-card border border-border space-y-4">
            <header>
              <h1 className="text-lg font-bold leading-tight">{aviso.title}</h1>
              <div className="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[11px] text-muted-foreground">
                <span className="flex items-center gap-1">
                  <Calendar size={12} className="text-primary" />
                  {formatDate(aviso.start_date)}
                  {aviso.end_date && aviso.end_date !== aviso.start_date && (
                    <> — {formatDate(aviso.end_date)}</>
                  )}
                </span>
                <span className="flex items-center gap-1">
                  <Users size={12} className="text-primary" />
                  {aviso.share_with === "all_members" ? "Todos" : aviso.share_with}
                </span>
              </div>
            </header>

            <div
              className="text-sm text-foreground leading-relaxed prose prose-sm max-w-none [&_p]:my-2"
              dangerouslySetInnerHTML={{ __html: aviso.description || "" }}
            />
          </article>
        )}
      </div>
    </MobileShell>
  );
}
