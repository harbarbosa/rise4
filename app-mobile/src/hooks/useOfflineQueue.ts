import { useEffect, useSyncExternalStore } from "react";
import {
  getPendingCount,
  isOnline,
  subscribe,
  initOfflineQueue,
  flushQueue,
} from "@/services/offlineQueueService";

let onlineSubscribers = 0;
const onlineListeners = new Set<() => void>();

function ensureOnlineListeners() {
  if (typeof window === "undefined") return;
  if (onlineSubscribers === 0) {
    const notify = () => onlineListeners.forEach((fn) => fn());
    window.addEventListener("online", notify);
    window.addEventListener("offline", notify);
  }
}

function subscribeOnline(cb: () => void) {
  ensureOnlineListeners();
  onlineSubscribers++;
  onlineListeners.add(cb);
  return () => {
    onlineListeners.delete(cb);
    onlineSubscribers--;
  };
}

function subscribeQueue(cb: () => void) {
  return subscribe(cb);
}

const getOnlineSnapshot = () => isOnline();
const getOnlineServerSnapshot = () => true;

const getPendingSnapshot = () => getPendingCount();
const getPendingServerSnapshot = () => 0;

/**
 * Hook utilitário para componentes — expõe estado online + nº de pendentes
 * e garante que a fila esteja inicializada.
 */
export function useOfflineQueue() {
  const online = useSyncExternalStore(
    subscribeOnline,
    getOnlineSnapshot,
    getOnlineServerSnapshot,
  );
  const pending = useSyncExternalStore(
    subscribeQueue,
    getPendingSnapshot,
    getPendingServerSnapshot,
  );

  useEffect(() => {
    initOfflineQueue();
  }, []);

  return {
    online,
    pending,
    flush: flushQueue,
  };
}
