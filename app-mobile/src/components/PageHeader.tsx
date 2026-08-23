import { Link } from "@tanstack/react-router";
import { ArrowLeft } from "lucide-react";
import type { ReactNode } from "react";

interface PageHeaderProps {
  title: string;
  back?: string;
  onBack?: () => void;
  right?: ReactNode;
}

export function PageHeader({ title, back, onBack, right }: PageHeaderProps) {
  return (
    <header className="sticky top-0 z-30 bg-primary text-primary-foreground shadow-sm">
      <div className="h-14 px-3 flex items-center justify-between">
        <div className="w-10 flex items-center">
          {onBack ? (
            <button
              onClick={onBack}
              className="w-10 h-10 -ml-2 flex items-center justify-center rounded-full active:bg-white/10"
              aria-label="Voltar"
            >
              <ArrowLeft size={22} />
            </button>
          ) : back ? (
            <Link
              to={back as "/"}
              className="w-10 h-10 -ml-2 flex items-center justify-center rounded-full active:bg-white/10"
              aria-label="Voltar"
            >
              <ArrowLeft size={22} />
            </Link>
          ) : null}
        </div>
        <h1 className="text-base font-semibold tracking-tight">{title}</h1>
        <div className="w-10 flex items-center justify-end">{right}</div>
      </div>
    </header>
  );
}
