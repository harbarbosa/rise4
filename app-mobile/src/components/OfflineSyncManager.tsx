import { useEffect, useSyncExternalStore } from "react";
import { toast } from "sonner";
import { useQueryClient } from "@tanstack/react-query";
import { WifiOff, CloudUpload } from "lucide-react";
import {
  initOfflineQueue,
  flushQueue,
  isOnline,
  getPendingCount,
  subscribe,
} from "@/services/offlineQueueService";

const getPendingSnapshot = () => getPendingCount();
const getPendingServer = () => 0;

const onlineListeners = new Set<() => void>();
let onlineHooked = false;
function subscribeOnline(cb: () => void) {
  if (typeof window !== "undefined" && !onlineHooked) {
    onlineHooked = true;
    const notify = () => onlineListeners.forEach((fn) => fn());
    window.addEventListener("online", notify);
    window.addEventListener("offline", notify);
  }
  onlineListeners.add(cb);
  return () => {
    onlineListeners.delete(cb);
  };
}
const getOnlineSnapshot = () => isOnline();
const getOnlineServer = () => true;

/**
 * Gerencia a fila offline e exibe um banner persistente quando o usuário
 * está sem conexão ou tem lançamentos aguardando envio.
 */
export function OfflineSyncManager() {
  const qc = useQueryClient();
  const pending = useSyncExternalStore(subscribe, getPendingSnapshot, getPendingServer);
  const online = useSyncExternalStore(subscribeOnline, getOnlineSnapshot, getOnlineServer);

  useEffect(() => {
    let wasOffline = !isOnline();

    initOfflineQueue((result) => {
      if (result.succeeded > 0) {
        toast.success(
          result.succeeded === 1
            ? "1 lançamento offline sincronizado."
            : `${result.succeeded} lançamentos offline sincronizados.`,
        );
        qc.invalidateQueries({ queryKey: ["timesheets"] });
        qc.invalidateQueries({ queryKey: ["project-tasks"] });
        qc.invalidateQueries({ queryKey: ["ponto"] });
        qc.invalidateQueries({ queryKey: ["trip-expenses"] });
        qc.invalidateQueries({ queryKey: ["trips"] });
      }
      if (result.failed > 0) {
        toast.warning(`${result.failed} lançamento(s) ainda não puderam ser enviados.`);
      }
    });

    const onOnline = () => {
      if (wasOffline) {
        wasOffline = false;
        if (getPendingCount() > 0) {
          toast.info("Conexão restabelecida. Sincronizando dados...");
        }
      }
    };
    const onOffline = () => {
      wasOffline = true;
      toast.warning("Você está offline. Seus lançamentos serão enviados quando voltar.");
    };
    window.addEventListener("online", onOnline);
    window.addEventListener("offline", onOffline);

    if (isOnline() && getPendingCount() > 0) {
      void flushQueue();
    }

    return () => {
      window.removeEventListener("online", onOnline);
      window.removeEventListener("offline", onOffline);
    };
  }, [qc]);

  if (online && pending === 0) return null;

  const offlineMode = !online;
  return (
    <div
      className={`fixed top-0 left-0 right-0 z-[60] text-xs font-medium px-3 py-1.5 flex items-center justify-center gap-2 shadow-sm ${
        offlineMode
          ? "bg-destructive text-destructive-foreground"
          : "bg-warning text-warning-foreground"
      }`}
      role="status"
    >
      {offlineMode ? <WifiOff size={14} /> : <CloudUpload size={14} />}
      <span>
        {offlineMode
          ? pending > 0
            ? `Sem conexão · ${pending} lançamento(s) pendente(s)`
            : "Você está offline"
          : `Sincronizando ${pending} lançamento(s)...`}
      </span>
      {online && pending > 0 && (
        <button
          onClick={() => void flushQueue()}
          className="ml-2 underline underline-offset-2"
        >
          tentar agora
        </button>
      )}
    </div>
  );
}
