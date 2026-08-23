import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import {
  login as loginRequest,
  logoutRequest,
  saveSession,
  clearSession,
  loadStoredUser,
  hasStoredToken,
  saveCredentials,
  clearCredentials,
  hasStoredCredentials,
  loadCredentials,
  type AuthUser,
  type LoginPayload,
} from "@/services/authService";
import { SESSION_EXPIRED_EVENT } from "@/services/api";

interface AuthContextValue {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  isInitializing: boolean;
  error: string | null;
  hasSavedCredentials: boolean;
  login: (payload: LoginPayload, remember?: boolean) => Promise<void>;
  loginWithSaved: () => Promise<void>;
  logout: () => Promise<void>;
  clearError: () => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isInitializing, setIsInitializing] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [hasSavedCredentials, setHasSavedCredentials] = useState(false);

  useEffect(() => {
    const savedCreds = hasStoredCredentials();
    setHasSavedCredentials(savedCreds);

    if (hasStoredToken()) {
      const stored = loadStoredUser();
      setUser(stored);
      // Valida o token de fundo: se 401 chegar, o interceptor dispara session-expired.
      // Faz um ping leve para detectar token expirado já no boot.
      import("@/services/api").then(({ api }) => {
        api.get("/api/team_members").catch(() => { /* tratado pelo interceptor */ });
      });
    }
    setIsInitializing(false);
  }, []);

  useEffect(() => {
    function onExpired() {
      clearSession();
      // Mantém credenciais salvas — interceptor já tentou reauth e falhou,
      // mas o usuário pode ter mudado a senha. Limpamos apenas se realmente inválidas:
      // reauthenticate() já limpa credentials em caso de falha de login.
      setHasSavedCredentials(hasStoredCredentials());
      setUser(null);
      setError("Sua sessão expirou. Faça login novamente.");
    }
    window.addEventListener(SESSION_EXPIRED_EVENT, onExpired);
    return () => window.removeEventListener(SESSION_EXPIRED_EVENT, onExpired);
  }, []);

  const login = useCallback(async (payload: LoginPayload, remember = false) => {
    setIsLoading(true);
    setError(null);
    try {
      const session = await loginRequest(payload);
      saveSession(session);
      if (remember) {
        saveCredentials(payload);
        setHasSavedCredentials(true);
      } else {
        clearCredentials();
        setHasSavedCredentials(false);
      }
      setUser(session.user);
    } catch (e) {
      const msg = e instanceof Error ? e.message : "Falha ao autenticar.";
      setError(msg);
      throw e;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const loginWithSaved = useCallback(async () => {
    const creds = loadCredentials();
    if (!creds) {
      setError("Nenhuma credencial salva.");
      throw new Error("Nenhuma credencial salva.");
    }
    await login(creds, true);
  }, [login]);

  const logout = useCallback(async () => {
    await logoutRequest();
    clearSession();
    clearCredentials();
    setHasSavedCredentials(false);
    setUser(null);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isAuthenticated: !!user,
      isLoading,
      isInitializing,
      error,
      hasSavedCredentials,
      login,
      loginWithSaved,
      logout,
      clearError: () => setError(null),
    }),
    [user, isLoading, isInitializing, error, hasSavedCredentials, login, loginWithSaved, logout],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth deve ser usado dentro de <AuthProvider>");
  return ctx;
}
