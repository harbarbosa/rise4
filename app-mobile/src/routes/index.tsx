import { createFileRoute, Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import {
  Calendar,
  FolderKanban,
  ClipboardCheck,
  Wallet,
  MapPin,
  Play,
  Clock,
  AlertCircle,
  Briefcase,
  CheckCircle2,
  ChevronRight,
} from "lucide-react";
import { Avatar } from "@/components/Avatar";
import { MobileShell } from "@/components/MobileShell";
import { NotificationsBell } from "@/components/NotificationsBell";
import { AnnouncementsCard } from "@/components/AnnouncementsCard";
import { useAuth } from "@/contexts/AuthContext";
import { useExecutionSchedules } from "@/hooks/use-api";
import { useProjects } from "@/hooks/useProjects";
import { usePontoStatus, usePontoMe } from "@/hooks/usePonto";
import { buildAvatarUrl } from "@/services/authService";
import alfahpLogo from "@/assets/alfahp-logo-v9.png.asset.json";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "AlfaHP Mobile — Início" },
      { name: "description", content: "Dashboard do técnico AlfaHP: resumo do dia e acesso rápido." },
    ],
  }),
  component: Index,
});

const quickActions = [
  { to: "/ponto", label: "Ponto", icon: Clock },
  { to: "/agenda", label: "Agenda", icon: Calendar },
  { to: "/projetos", label: "Projetos", icon: FolderKanban },
  { to: "/lancar-atividade", label: "Apontar", icon: ClipboardCheck },
  { to: "/despesas", label: "Despesas", icon: Wallet },
];

function getSaudacao(h: number) {
  if (h < 12) return "Bom dia";
  if (h < 18) return "Boa tarde";
  return "Boa noite";
}


function formatDataExtenso() {
  return new Date().toLocaleDateString("pt-BR", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}

function iniciaisDe(nome: string) {
  return (
    nome
      .trim()
      .split(/\s+/)
      .map((p) => p[0])
      .slice(0, 2)
      .join("")
      .toUpperCase() || "?"
  );
}

function Index() {
  const [saudacao, setSaudacao] = useState("Olá");
  const [dataExtenso, setDataExtenso] = useState("");
  useEffect(() => {
    setSaudacao(getSaudacao(new Date().getHours()));
    setDataExtenso(formatDataExtenso());
  }, []);

  const { user: authUser } = useAuth();
  const schedules = useExecutionSchedules();
  const projects = useProjects();
  const pontoStatus = usePontoStatus();
  const pontoMe = usePontoMe();

  const agenda = schedules.data ?? [];
  const projetos = projects.data ?? [];

  const _hoje0 = new Date();
  const hojeISO = `${_hoje0.getFullYear()}-${String(_hoje0.getMonth() + 1).padStart(2, "0")}-${String(_hoje0.getDate()).padStart(2, "0")}`;
  const atividadesHoje = agenda.filter((a) => {
    const ini = a.data;
    const fim = a.data_fim || a.data;
    return ini && hojeISO >= ini && hojeISO <= fim;
  }).length;
  const projetosAndamento = projetos.filter((p) => p.status === "em_andamento").length;
  const pendenciasAbertas = 0;

  // Próximas 3 atividades da semana (ordenadas por data/hora)
  // Considera intervalo [data, data_fim] sobrepondo a janela [hoje, hoje+7]
  const hoje = new Date();
  hoje.setHours(0, 0, 0, 0);
  const fimSemana = new Date(hoje);
  fimSemana.setDate(hoje.getDate() + 7);

  const proximasAtividades = agenda
    .filter((a) => {
      if (!a.data) return false;
      const ini = new Date(a.data + "T00:00:00");
      const fim = new Date((a.data_fim || a.data) + "T00:00:00");
      // sobreposição com [hoje, fimSemana)
      return fim >= hoje && ini < fimSemana;
    })
    .sort((a, b) => {
      const dtA = new Date(a.data + "T" + (a.hora_inicio || "00:00"));
      const dtB = new Date(b.data + "T" + (b.hora_inicio || "00:00"));
      return dtA.getTime() - dtB.getTime();
    })
    .slice(0, 3);


  const nomeUsuario = authUser?.nome ?? "Técnico";
  const cargoUsuario = authUser?.cargo ?? authUser?.role_title ?? "—";
  const avatarUrl = authUser?.avatar_url || buildAvatarUrl(pontoMe.data?.photo) || null;

  return (
    <MobileShell>
      {/* Cabeçalho */}
      <header className="bg-primary text-primary-foreground px-5 pt-3 pb-6 rounded-b-3xl shadow-elevated">
        <div className="flex items-start justify-between">
          <img src={alfahpLogo.url} alt="AlfaHP" className="h-[1.05rem] w-auto" />
          <NotificationsBell />
        </div>
        <div className="mt-4 flex items-center justify-between gap-3">
          <div className="min-w-0">
            <p className="text-sm opacity-90">{saudacao},</p>
            <h1 className="text-2xl font-bold leading-tight truncate">
              {nomeUsuario.split(" ")[0]} 👋
            </h1>
            <p className="text-xs opacity-80 mt-0.5">{cargoUsuario}</p>
          </div>
          <Link to="/perfil" aria-label="Abrir perfil" className="rounded-full active:scale-95 transition-transform">
            <Avatar initials={iniciaisDe(nomeUsuario)} src={avatarUrl} size={58} />
          </Link>
        </div>
      </header>

      <div className="px-4 -mt-6 space-y-4">
        {/* Card Resumo */}
        <section className="bg-card rounded-2xl p-4 shadow-card border border-border">
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-sm font-semibold">Resumo do dia</h2>
            <span className="text-[11px] text-muted-foreground capitalize">{dataExtenso}</span>
          </div>
          <div className="grid grid-cols-2 gap-2.5">
            <StatTile icon={CheckCircle2} value={String(atividadesHoje)} label="Atividades do dia" tone="primary" />
            <StatTile icon={Clock} value={pontoStatus.data?.worked_hours ?? "00:00"} label="Horas trabalhadas" tone="info" />
            <StatTile icon={Briefcase} value={String(projetosAndamento)} label="Projetos em andamento" tone="success" />
            <StatTile icon={AlertCircle} value={String(pendenciasAbertas)} label="Pendências" tone="destructive" />
          </div>
        </section>

        {/* Avisos */}
        <AnnouncementsCard />


        {/* Próximas atividades da semana */}
        <section className="bg-card rounded-2xl p-4 shadow-card border border-border">
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-sm font-semibold">Próximas atividades</h2>
            <Link to="/agenda" className="text-[11px] font-medium text-primary flex items-center gap-0.5">
              Ver agenda <ChevronRight size={12} />
            </Link>
          </div>

          {schedules.isLoading && (
            <p className="text-sm text-muted-foreground py-2">Carregando agenda...</p>
          )}

          {!schedules.isLoading && proximasAtividades.length === 0 && (
            <p className="text-sm text-muted-foreground py-2">
              Nenhuma atividade programada para esta semana.
            </p>
          )}

          <div className="space-y-2.5">
            {proximasAtividades.map((a) => (
              <div
                key={a.id}
                className="rounded-xl border border-border p-3 bg-muted/40"
              >
                <div className="flex items-center justify-between mb-1">
                  <span className="text-[11px] font-semibold text-primary">
                    {(() => {
                      const d = new Date(a.data + "T00:00:00");
                      if (a.data === hojeISO) return "Hoje";
                      const semana = d.toLocaleDateString("pt-BR", { weekday: "short" }).replace(".", "");
                      const dia = d.toLocaleDateString("pt-BR", { day: "2-digit", month: "2-digit" });
                      return `${semana}, ${dia}`;
                    })()}
                    {" · "}{a.hora_inicio} - {a.hora_fim}
                  </span>
                  <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-full ${a.status === "em_andamento" ? "bg-info/10 text-info" : "bg-muted text-muted-foreground"}`}>
                    {a.status === "em_andamento" ? "Em andamento" : "Programada"}
                  </span>
                </div>
                <h3 className="text-sm font-bold leading-tight">{a.projeto}</h3>
                <p className="text-xs text-muted-foreground">Cliente: {a.cliente}</p>
                {(a.endereco || a.cidade) && (
                  <div className="flex items-center gap-1 mt-1.5 text-[11px] text-muted-foreground">
                    <MapPin size={11} className="text-primary shrink-0" />
                    <span className="truncate">
                      {a.endereco ? `${a.endereco} — ` : ""}{a.cidade}
                    </span>
                  </div>
                )}
              </div>
            ))}
          </div>

          {proximasAtividades.length > 0 && (
            <Link
              to="/lancar-atividade"
              className="mt-3 w-full inline-flex items-center justify-center gap-2 h-11 rounded-xl bg-primary text-primary-foreground font-semibold text-sm active:opacity-90 shadow-elevated"
            >
              <Play size={15} fill="currentColor" /> Lançar atividade
            </Link>
          )}
        </section>

        {/* Acesso rápido */}
        <section className="bg-card rounded-2xl p-4 shadow-card border border-border">
          <h2 className="text-sm font-semibold mb-3">Acesso rápido</h2>
          <div className="grid grid-cols-5 gap-2">
            {quickActions.map((a) => {
              const Icon = a.icon;
              return (
                <Link
                  key={a.label}
                  to={a.to as "/"}
                  className="flex flex-col items-center gap-1.5 group"
                >
                  <span className="w-12 h-12 rounded-xl bg-primary-soft text-primary flex items-center justify-center group-active:scale-95 transition-transform">
                    <Icon size={20} />
                  </span>
                  <span className="text-[10px] font-medium text-muted-foreground text-center leading-tight">
                    {a.label}
                  </span>
                </Link>
              );
            })}
          </div>
        </section>
      </div>
    </MobileShell>
  );
}

const toneStyles: Record<string, { bg: string; text: string }> = {
  primary: { bg: "bg-primary-soft", text: "text-primary" },
  info: { bg: "bg-info/10", text: "text-info" },
  success: { bg: "bg-success/10", text: "text-success" },
  destructive: { bg: "bg-destructive/10", text: "text-destructive" },
};

function StatTile({
  icon: Icon,
  value,
  label,
  tone,
}: {
  icon: typeof Clock;
  value: string;
  label: string;
  tone: keyof typeof toneStyles;
}) {
  const t = toneStyles[tone];
  return (
    <div className="rounded-xl border border-border p-3 flex items-center gap-3 bg-card">
      <span className={`w-10 h-10 rounded-lg ${t.bg} ${t.text} grid place-items-center shrink-0`}>
        <Icon size={18} />
      </span>
      <div className="min-w-0">
        <p className="text-lg font-bold leading-none">{value}</p>
        <p className="text-[11px] text-muted-foreground leading-tight mt-1">{label}</p>
      </div>
    </div>
  );
}
