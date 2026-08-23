import { createFileRoute } from "@tanstack/react-router";
import { Plus, ClipboardList } from "lucide-react";
import { useState } from "react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";

export const Route = createFileRoute("/pendencias")({
  head: () => ({ meta: [{ title: "Pendências — AlfaHP" }] }),
  component: PendenciasPage,
});

function PendenciasPage() {
  const [tab, setTab] = useState<"abertas" | "andamento" | "concluidas">("abertas");
  return (
    <MobileShell>
      <PageHeader title="Pendências" back="/" />
      <div className="bg-card border-b border-border">
        <div className="flex">
          {[
            { k: "abertas" as const, l: "Abertas" },
            { k: "andamento" as const, l: "Em andamento" },
            { k: "concluidas" as const, l: "Concluídas" },
          ].map((t) => (
            <button
              key={t.k}
              onClick={() => setTab(t.k)}
              className={`flex-1 py-3 text-xs font-semibold relative ${
                tab === t.k ? "text-primary" : "text-muted-foreground"
              }`}
            >
              {t.l}
              {tab === t.k && (
                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-full" />
              )}
            </button>
          ))}
        </div>
      </div>

      <div className="p-4 space-y-4">
        <div className="text-center py-12 px-6">
          <div className="w-16 h-16 mx-auto rounded-full bg-muted flex items-center justify-center mb-4">
            <ClipboardList size={28} className="text-muted-foreground" />
          </div>
          <h3 className="text-sm font-semibold mb-1">Nenhuma pendência</h3>
          <p className="text-xs text-muted-foreground leading-relaxed">
            Integração com a API de pendências ainda não disponível. Assim que
            o endpoint for liberado, suas pendências aparecerão aqui.
          </p>
        </div>
        <button className="w-full h-12 rounded-xl bg-primary text-primary-foreground font-semibold text-sm inline-flex items-center justify-center gap-2 active:opacity-90">
          <Plus size={18} /> Nova pendência
        </button>
      </div>
    </MobileShell>
  );
}
