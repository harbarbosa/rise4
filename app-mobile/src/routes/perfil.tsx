import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { useAuth } from "@/contexts/AuthContext";
import {
  FileText,
  LogOut,
  Settings,
  Mail,
  Phone,
  Briefcase,
  ShieldCheck,
  Calendar,
  Clock,
} from "lucide-react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { Avatar } from "@/components/Avatar";
import { LoadingState, ErrorState } from "@/components/QueryState";
import { useCurrentTeamMember } from "@/hooks/useCurrentTeamMember";
import type { TeamMemberProfile } from "@/services/teamMemberService";

export const Route = createFileRoute("/perfil")({
  head: () => ({ meta: [{ title: "Perfil — AlfaHP Mobile" }] }),
  component: PerfilPage,
});


function iniciais(nome: string) {
  return nome
    .trim()
    .split(/\s+/)
    .map((p) => p[0])
    .slice(0, 2)
    .join("")
    .toUpperCase() || "?";
}

function fmtData(iso?: string) {
  if (!iso) return "—";
  const [d] = iso.split("T");
  const [y, m, day] = d.split("-");
  if (!y || !m || !day) return iso;
  return `${day}/${m}/${y}`;
}

function fmtDateTime(iso?: string) {
  if (!iso) return "—";
  try {
    const dt = new Date(iso);
    if (isNaN(dt.getTime())) return iso;
    return dt.toLocaleString("pt-BR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return iso;
  }
}

function PerfilPage() {
  const { user: authUser } = useAuth();
  const query = useCurrentTeamMember();

  return (
    <MobileShell>
      <PageHeader
        title="Perfil"
        right={
          <button className="w-10 h-10 -mr-2 flex items-center justify-center rounded-full active:bg-white/10">
            <Settings size={20} />
          </button>
        }
      />

      <div className="p-4 space-y-4">
        {query.isLoading && <LoadingState label="Carregando perfil..." />}

        {query.isError && (
          <ErrorState
            message="Não foi possível carregar o perfil."
            onRetry={() => query.refetch()}
          />
        )}

        {!query.isLoading && !query.isError && (
          <ProfileContent
            member={
              query.data ?? {
                id: authUser?.id ?? "",
                nome: authUser?.nome ?? "—",
                email: authUser?.email ?? "—",
                cargo: authUser?.cargo,
                roleTitle: authUser?.role_title ?? undefined,
              }
            }
          />
        )}

        <DocumentosSection />

        <LogoutButton />
      </div>
    </MobileShell>
  );
}

function ProfileContent({ member }: { member: TeamMemberProfile }) {
  const infos: { icon: typeof Mail; label: string; value: string }[] = [
    { icon: Mail, label: "E-mail", value: member.email || "—" },
    {
      icon: Phone,
      label: "Telefone / WhatsApp",
      value: member.whatsapp || member.telefone || "—",
    },
    { icon: Briefcase, label: "Cargo", value: member.cargo || "—" },
    { icon: ShieldCheck, label: "Perfil de acesso", value: member.roleTitle || "—" },
    {
      icon: Calendar,
      label: "Data de contratação",
      value: fmtData(member.dataContratacao),
    },
    {
      icon: Clock,
      label: "Último acesso",
      value: fmtDateTime(member.ultimoAcesso),
    },
  ];

  return (
    <>
      <section className="bg-card rounded-2xl border border-border p-5 shadow-card flex items-center gap-4">
        <Avatar initials={iniciais(member.nome)} size={72} />
        <div className="min-w-0 flex-1">
          <h2 className="text-lg font-bold truncate">{member.nome || "—"}</h2>
          {member.cargo && (
            <p className="text-sm text-muted-foreground truncate">{member.cargo}</p>
          )}
          {member.roleTitle && (
            <span className="mt-1 inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary">
              {member.roleTitle}
            </span>
          )}
        </div>
      </section>

      <section className="bg-card rounded-2xl border border-border shadow-card overflow-hidden">
        <div className="px-5 pt-4 pb-2">
          <h3 className="text-sm font-semibold text-foreground">Informações</h3>
        </div>
        <ul>
          {infos.map((info, i) => {
            const Icon = info.icon;
            return (
              <li
                key={info.label}
                className={`flex items-center gap-3 px-5 py-3 ${
                  i < infos.length - 1 ? "border-b border-border" : ""
                }`}
              >
                <span className="w-9 h-9 rounded-lg bg-primary-soft text-primary flex items-center justify-center shrink-0">
                  <Icon size={16} />
                </span>
                <div className="flex-1 min-w-0">
                  <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
                    {info.label}
                  </p>
                  <p className="text-sm font-medium text-foreground truncate">
                    {info.value}
                  </p>
                </div>
              </li>
            );
          })}
        </ul>
      </section>
    </>
  );
}

/**
 * Seção de Documentos — placeholder.
 *
 * A API ainda não expõe endpoint específico de documentos do funcionário.
 * Quando disponível, substituir por um hook `useEmployeeDocuments(memberId)`
 * e renderizar a lista real.
 */
function DocumentosSection() {
  return (
    <section className="bg-card rounded-2xl border border-border shadow-card overflow-hidden">
      <div className="px-5 pt-4 pb-3">
        <h3 className="text-sm font-semibold text-foreground">Documentos</h3>
        <p className="text-[10px] text-muted-foreground">
          Integração com a API em breve
        </p>
      </div>
      <div className="px-5 pb-5">
        <div className="flex items-center gap-3 p-3 rounded-xl bg-muted/40 border border-dashed border-border">
          <span className="w-9 h-9 rounded-lg bg-muted text-muted-foreground flex items-center justify-center shrink-0">
            <FileText size={18} />
          </span>
          <p className="text-xs text-muted-foreground leading-snug">
            Nenhum documento disponível. A listagem aparecerá aqui assim que o
            endpoint for liberado pelo backend.
          </p>
        </div>
      </div>
    </section>
  );
}


function LogoutButton() {
  const { logout } = useAuth();
  const navigate = useNavigate();
  async function handle() {
    await logout();
    navigate({ to: "/login", replace: true });
  }
  return (
    <button
      onClick={handle}
      className="w-full h-12 rounded-xl bg-destructive/10 text-destructive font-semibold text-sm inline-flex items-center justify-center gap-2 active:opacity-90"
    >
      <LogOut size={18} /> Sair
    </button>
  );
}
