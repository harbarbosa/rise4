import { Link, useRouterState, useNavigate } from "@tanstack/react-router";
import {
  Home,
  Calendar,
  FolderKanban,
  Wallet,
  Plus,
  ClipboardList,
  Receipt,
  Clock,
  Wrench,
} from "lucide-react";
import type { ReactNode } from "react";
import { useState } from "react";
import { useCan } from "@/components/Can";
import type { Permission } from "@/config/permissions";
import { Sheet, SheetContent, SheetHeader, SheetTitle } from "@/components/ui/sheet";

interface MobileShellProps {
  children: ReactNode;
}

type Tab = {
  to: string;
  label: string;
  icon: typeof Home;
  exact?: boolean;
  permission?: Permission;
};

// Ordem: Início | Agenda | [+] | Projetos | Despesas
const leftTabs: Tab[] = [
  { to: "/", label: "Início", icon: Home, exact: true },
  { to: "/agenda", label: "Agenda", icon: Calendar, permission: "agenda.ver" },
];
const rightTabs: Tab[] = [
  { to: "/projetos", label: "Projetos", icon: FolderKanban, permission: "projetos.ver_atribuidos" },
  { to: "/despesas", label: "Despesas", icon: Wallet, permission: "despesas.ver" },
];

type QuickAction = {
  label: string;
  icon: typeof Plus;
  to: string;
  permission?: Permission;
};

const quickActions: QuickAction[] = [
  { label: "Lançar Atividade", icon: ClipboardList, to: "/lancar-atividade" },
  { label: "Adicionar Despesa", icon: Receipt, to: "/despesas", permission: "despesas.ver" },
  { label: "Registrar Ponto", icon: Clock, to: "/ponto" },
  { label: "Ordens de Serviço", icon: Wrench, to: "/ordens-servico", permission: "ordens.ver" },
];

function TabLink({ tab, path }: { tab: Tab; path: string }) {
  const active = tab.exact ? path === tab.to : path.startsWith(tab.to);
  const Icon = tab.icon;
  return (
    <Link to={tab.to as "/"} className="flex flex-col items-center justify-center gap-1 relative">
      {active && <span className="absolute top-0 h-0.5 w-8 rounded-full bg-primary" />}
      <Icon size={22} className={active ? "text-primary" : "text-muted-foreground"} />
      <span
        className={`text-[11px] font-medium ${active ? "text-primary" : "text-muted-foreground"}`}
      >
        {tab.label}
      </span>
    </Link>
  );
}

export function MobileShell({ children }: MobileShellProps) {
  const path = useRouterState({ select: (s) => s.location.pathname });
  const [sheetOpen, setSheetOpen] = useState(false);
  const navigate = useNavigate();
  const { can } = useCan();

  const visibleLeft = leftTabs.filter((t) => !t.permission || can(t.permission));
  const visibleRight = rightTabs.filter((t) => !t.permission || can(t.permission));
  const visibleActions = quickActions.filter((a) => !a.permission || can(a.permission));

  const handleAction = (to: string) => {
    setSheetOpen(false);
    navigate({ to: to as "/" });
  };

  return (
    <div className="min-h-screen bg-background flex justify-center">
      <div className="w-full min-h-screen relative flex flex-col bg-background">
        <main className="flex-1 pb-24">{children}</main>

        <nav className="fixed bottom-0 left-0 right-0 w-full z-40 bg-card/95 backdrop-blur border-t border-border shadow-[0_-4px_16px_-4px_rgba(0,0,0,0.06)]">
          <div className="grid grid-cols-5 h-[68px] pb-1 items-center">
            {visibleLeft.map((t) => (
              <TabLink key={t.to} tab={t} path={path} />
            ))}

            {/* Botão central + no lugar de Projetos */}
            <div className="flex items-center justify-center">
              <button
                onClick={() => setSheetOpen(true)}
                className="h-12 w-12 rounded-full bg-primary text-primary-foreground shadow-[0_4px_14px_rgba(0,59,142,0.45)] flex items-center justify-center transition-transform active:scale-90"
                aria-label="Adicionar"
              >
                <Plus size={24} strokeWidth={2.5} />
              </button>
            </div>

            {visibleRight.map((t) => (
              <TabLink key={t.to} tab={t} path={path} />
            ))}
          </div>
        </nav>

        {/* Bottom sheet de ações rápidas */}
        <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
          <SheetContent side="bottom" className="rounded-t-2xl pb-6 pt-2">
            <div className="mx-auto mb-3 h-1 w-10 rounded-full bg-muted" />
            <SheetHeader className="mb-3">
              <SheetTitle className="text-center text-base">Novo lançamento</SheetTitle>
            </SheetHeader>
            <div className="grid grid-cols-2 gap-2">
              {visibleActions.map((action) => {
                const Icon = action.icon;
                return (
                  <button
                    key={action.to}
                    onClick={() => handleAction(action.to)}
                    className="flex flex-col items-center gap-1.5 rounded-xl border border-border bg-muted/40 p-3 transition-colors active:bg-muted"
                  >
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                      <Icon size={20} className="text-primary" />
                    </div>
                    <span className="text-xs font-medium">{action.label}</span>
                  </button>
                );
              })}
            </div>
          </SheetContent>
        </Sheet>
      </div>
    </div>
  );
}
