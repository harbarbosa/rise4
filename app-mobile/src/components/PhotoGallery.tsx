import { useEffect, useState } from "react";
import { X, ImageIcon } from "lucide-react";
import { useTimelogPhotos } from "@/hooks/useTimelogPhotos";
import { LoadingState, ErrorState, EmptyState } from "@/components/QueryState";
import type { TimelogPhoto } from "@/services/photoService";

interface PhotoGalleryProps {
  timelogId: string;
  title?: string;
}

/**
 * Galeria de fotos de um apontamento.
 * Consome GET /api/projectanalizer/timelogs/{id}/photos
 * e permite abrir cada foto em tela cheia.
 */
export function PhotoGallery({ timelogId, title = "Fotos do apontamento" }: PhotoGalleryProps) {
  const { data, isLoading, isError, error, refetch } = useTimelogPhotos(timelogId);
  const [openIndex, setOpenIndex] = useState<number | null>(null);

  const photos = data ?? [];

  if (isLoading) return <LoadingState label="Carregando fotos..." />;
  if (isError) {
    return (
      <ErrorState
        message={error?.message ?? "Não foi possível carregar as fotos."}
        onRetry={() => refetch()}
      />
    );
  }
  if (photos.length === 0) {
    return (
      <EmptyState
        title="Sem fotos"
        description="Este apontamento não possui fotos registradas."
        icon={<ImageIcon className="h-8 w-8" />}
      />
    );
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-2">
        <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
          {title} ({photos.length})
        </h3>
      </div>
      <div className="grid grid-cols-3 gap-2">
        {photos.map((p, i) => (
          <button
            key={p.id}
            onClick={() => setOpenIndex(i)}
            className="relative aspect-square rounded-xl border border-border overflow-hidden bg-muted active:opacity-80"
            aria-label={`Abrir foto ${i + 1}`}
          >
            <img
              src={p.thumbnail_url || p.url}
              alt={p.filename ?? `Foto ${i + 1}`}
              loading="lazy"
              className="w-full h-full object-cover"
            />
          </button>
        ))}
      </div>

      {openIndex !== null && (
        <Lightbox
          photos={photos}
          startIndex={openIndex}
          onClose={() => setOpenIndex(null)}
        />
      )}
    </div>
  );
}

function Lightbox({
  photos,
  startIndex,
  onClose,
}: {
  photos: TimelogPhoto[];
  startIndex: number;
  onClose: () => void;
}) {
  const [index, setIndex] = useState(startIndex);
  const current = photos[index];

  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") onClose();
      if (e.key === "ArrowRight") setIndex((i) => Math.min(i + 1, photos.length - 1));
      if (e.key === "ArrowLeft") setIndex((i) => Math.max(i - 1, 0));
    }
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [onClose, photos.length]);

  if (!current) return null;

  return (
    <div
      className="fixed inset-0 z-50 bg-black/95 flex flex-col"
      role="dialog"
      aria-modal="true"
      onClick={onClose}
    >
      <div className="flex items-center justify-between p-3 text-white">
        <span className="text-xs opacity-80">
          {index + 1} / {photos.length}
        </span>
        <button
          onClick={onClose}
          className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center"
          aria-label="Fechar"
        >
          <X size={20} />
        </button>
      </div>
      <div
        className="flex-1 flex items-center justify-center p-4"
        onClick={(e) => e.stopPropagation()}
      >
        <img
          src={current.url}
          alt={current.filename ?? `Foto ${index + 1}`}
          className="max-w-full max-h-full object-contain"
        />
      </div>
      {photos.length > 1 && (
        <div
          className="flex gap-2 p-3 overflow-x-auto"
          onClick={(e) => e.stopPropagation()}
        >
          {photos.map((p, i) => (
            <button
              key={p.id}
              onClick={() => setIndex(i)}
              className={`flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden border-2 ${
                i === index ? "border-white" : "border-transparent opacity-60"
              }`}
            >
              <img
                src={p.thumbnail_url || p.url}
                alt=""
                className="w-full h-full object-cover"
              />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
