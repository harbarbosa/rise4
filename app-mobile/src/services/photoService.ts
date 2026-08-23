/**
 * Serviço de Fotos de Apontamentos (Timelogs) do ProjectAnalizer.
 *
 * Endpoint disponível:
 *   GET /api/projectanalizer/timelogs/{id}/photos
 *
 * ⚠️ Endpoint de upload de fotos precisa ser confirmado na API.
 *    Enquanto isso, `uploadPhotos()` está preparado como TODO e NÃO
 *    quebra o fluxo principal de lançamento — falhas são logadas e
 *    a função resolve com lista vazia.
 */

import { api, USE_REAL_API, ApiError } from "@/services/api";

export interface TimelogPhoto {
  id: string;
  url: string;
  thumbnail_url?: string;
  filename?: string;
  content_type?: string;
  size?: number;
  created_at?: string;
}

/* ---------------- Normalização ---------------- */

function pick<T = unknown>(obj: Record<string, unknown>, ...keys: string[]): T | undefined {
  for (const k of keys) {
    const v = obj[k];
    if (v !== undefined && v !== null && v !== "") return v as T;
  }
  return undefined;
}

export function mapPhoto(raw: Record<string, unknown>, index = 0): TimelogPhoto {
  const url =
    pick<string>(raw, "url", "file_url", "photo_url", "path", "src") ?? "";
  return {
    id: (pick<string>(raw, "id", "uuid", "photo_id") ?? `photo_${index}`).toString(),
    url,
    thumbnail_url: pick<string>(raw, "thumbnail_url", "thumb_url", "thumb"),
    filename: pick<string>(raw, "filename", "name", "file_name"),
    content_type: pick<string>(raw, "content_type", "mime_type", "type"),
    size: pick<number>(raw, "size", "file_size"),
    created_at: pick<string>(raw, "created_at", "uploaded_at"),
  };
}

/* ---------------- Fontes ---------------- */

const realSource = {
  byTimelog: (timelogId: string) =>
    api
      .get<unknown[]>(
        `/api/projectanalizer/timelogs/${encodeURIComponent(timelogId)}/photos`,
      )
      .then((r) => r.data ?? []),
};

const mockSource = {
  byTimelog: async (_timelogId: string): Promise<unknown[]> => [],
};

const source = USE_REAL_API ? realSource : mockSource;

/* ---------------- API pública ---------------- */

export async function fetchTimelogPhotos(timelogId: string): Promise<TimelogPhoto[]> {
  if (!timelogId) throw new ApiError("ID do apontamento não informado", 400);
  const data = await source.byTimelog(timelogId);
  return (data as Record<string, unknown>[]).map((raw, i) => mapPhoto(raw, i));
}

/**
 * Envia fotos para um apontamento.
 *
 * ⚠️ Endpoint de upload de fotos precisa ser confirmado na API.
 *
 * TODO(API): substituir esta implementação pelo endpoint real quando
 * estiver documentado. Exemplo provável:
 *
 *   POST /api/projectanalizer/timelogs/{id}/photos
 *   Content-Type: multipart/form-data
 *   Body: FormData com campo "files" (múltiplos arquivos)
 *
 * Por enquanto:
 *  - No modo mock: resolve com fotos "fake" geradas a partir do File local
 *    (object URL) para não quebrar a UI.
 *  - No modo real: faz log do aviso e resolve com lista vazia, evitando
 *    quebrar o fluxo de finalização do lançamento.
 */
export async function uploadPhotos(
  timelogId: string,
  files: File[],
): Promise<TimelogPhoto[]> {
  if (!files || files.length === 0) return [];

  if (USE_REAL_API) {
    // eslint-disable-next-line no-console
    console.warn(
      "[photoService] Upload de fotos não implementado — endpoint precisa ser confirmado na API. timelogId=",
      timelogId,
    );
    return [];
  }

  // Mock: cria object URLs locais apenas para visualização.
  return files.map((file, i) => ({
    id: `local_${Date.now()}_${i}`,
    url: typeof URL !== "undefined" ? URL.createObjectURL(file) : "",
    filename: file.name,
    content_type: file.type,
    size: file.size,
  }));
}
