import { createFileRoute, useNavigate, useRouterState } from "@tanstack/react-router";
import { useEffect, useState, type FormEvent } from "react";
import { Mail, Lock, Loader2, AlertCircle, Eye, EyeOff, Zap } from "lucide-react";
import { useAuth } from "@/contexts/AuthContext";
import alfahpLogo from "@/assets/alfahp-logo-v9.png.asset.json";

export const Route = createFileRoute("/login")({
  head: () => ({ meta: [{ title: "Entrar — AlfaHP Mobile" }] }),
  component: LoginPage,
});

function LoginPage() {
  const navigate = useNavigate();
  const { login, loginWithSaved, isLoading, error, isAuthenticated, clearError, hasSavedCredentials } = useAuth();
  const isHydrating = useRouterState({ select: (s) => s.isLoading });

  const [email, setEmail] = useState("");
  const [senha, setSenha] = useState("");
  const [showPass, setShowPass] = useState(false);
  const [remember, setRemember] = useState(true);

  useEffect(() => {
    if (isAuthenticated) navigate({ to: "/", replace: true });
  }, [isAuthenticated, navigate]);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    try {
      await login({ email, senha }, remember);
      navigate({ to: "/", replace: true });
    } catch {
      /* erro já exposto via context */
    }
  }

  async function handleAutoLogin() {
    try {
      await loginWithSaved();
      navigate({ to: "/", replace: true });
    } catch {
      /* erro já exposto via context */
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/10 via-background to-background px-5 py-10">
      <div className="w-full max-w-sm">
        <div className="flex flex-col items-center mb-8">
          <img src={alfahpLogo.url} alt="AlfaHP - Tecnologia Solar" className="h-[0.6rem] w-auto mb-1" />
          <p className="text-sm text-muted-foreground">Acesso para técnicos de campo</p>
        </div>

        {hasSavedCredentials && (
          <button
            type="button"
            onClick={handleAutoLogin}
            disabled={isLoading || isHydrating}
            className="w-full h-12 mb-4 rounded-xl bg-primary text-primary-foreground text-sm font-semibold shadow-lg shadow-primary/30 hover:bg-primary/90 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors"
          >
            {isLoading ? (
              <><Loader2 size={18} className="animate-spin" /> Entrando...</>
            ) : (
              <><Zap size={18} /> Entrar automaticamente</>
            )}
          </button>
        )}

        <form
          onSubmit={handleSubmit}
          className="bg-card border border-border rounded-2xl p-6 shadow-sm space-y-4"
        >
          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground">E-mail</label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" size={18} />
              <input
                type="email"
                inputMode="email"
                autoComplete="email"
                required
                value={email}
                onChange={(e) => { setEmail(e.target.value); if (error) clearError(); }}
                placeholder="seu.email@alfahp.com.br"
                className="w-full h-11 pl-10 pr-3 rounded-lg border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground">Senha</label>
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" size={18} />
              <input
                type={showPass ? "text" : "password"}
                autoComplete="current-password"
                required
                value={senha}
                onChange={(e) => { setSenha(e.target.value); if (error) clearError(); }}
                placeholder="••••••••"
                className="w-full h-11 pl-10 pr-10 rounded-lg border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
              />
              <button
                type="button"
                onClick={() => setShowPass((v) => !v)}
                className="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-muted-foreground hover:text-foreground"
                aria-label={showPass ? "Ocultar senha" : "Mostrar senha"}
              >
                {showPass ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>
          </div>

          <label className="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer select-none">
            <input
              type="checkbox"
              checked={remember}
              onChange={(e) => setRemember(e.target.checked)}
              className="h-4 w-4 rounded border-input accent-primary"
            />
            Lembrar credenciais para login automático
          </label>

          {error && (
            <div className="flex items-start gap-2 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-sm p-3">
              <AlertCircle size={16} className="mt-0.5 shrink-0" />
              <span>{error}</span>
            </div>
          )}

          <button
            type="submit"
            disabled={isLoading || isHydrating}
            className="w-full h-11 rounded-lg bg-primary text-primary-foreground text-sm font-semibold shadow hover:bg-primary/90 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors"
          >
            {isLoading ? (
              <>
                <Loader2 size={18} className="animate-spin" /> Autenticando...
              </>
            ) : (
              "Entrar"
            )}
          </button>

          <p className="text-[11px] text-center text-muted-foreground pt-2">
            Ao continuar você concorda com as políticas internas da AlfaHP.
          </p>
        </form>
      </div>
    </div>
  );
}
