import { useEffect, useRef, useState } from "react";
import { Camera, X, RefreshCw, Check, Loader2 } from "lucide-react";

interface SelfieCaptureProps {
  open: boolean;
  title?: string;
  onCancel: () => void;
  onCapture: (dataUrl: string) => void;
}

export function SelfieCapture({ open, title, onCancel, onCapture }: SelfieCaptureProps) {
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [starting, setStarting] = useState(false);
  const [snapshot, setSnapshot] = useState<string | null>(null);

  useEffect(() => {
    if (!open) return;
    let cancelled = false;
    setError(null);
    setSnapshot(null);
    setStarting(true);

    (async () => {
      try {
        if (!navigator.mediaDevices?.getUserMedia) {
          throw new Error("Câmera não disponível neste dispositivo.");
        }
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: "user", width: { ideal: 720 }, height: { ideal: 720 } },
          audio: false,
        });
        if (cancelled) {
          stream.getTracks().forEach((t) => t.stop());
          return;
        }
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          await videoRef.current.play().catch(() => {});
        }
      } catch (e) {
        setError(e instanceof Error ? e.message : "Não foi possível acessar a câmera.");
      } finally {
        if (!cancelled) setStarting(false);
      }
    })();

    return () => {
      cancelled = true;
      streamRef.current?.getTracks().forEach((t) => t.stop());
      streamRef.current = null;
    };
  }, [open]);

  function takeShot() {
    const video = videoRef.current;
    if (!video) return;
    const w = video.videoWidth || 480;
    const h = video.videoHeight || 480;
    const canvas = document.createElement("canvas");
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;
    // espelha horizontalmente (selfie)
    ctx.translate(w, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, w, h);
    const dataUrl = canvas.toDataURL("image/jpeg", 0.7);
    setSnapshot(dataUrl);
  }

  function confirm() {
    if (snapshot) onCapture(snapshot);
  }

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 bg-black/90 flex flex-col">
      <div className="flex items-center justify-between p-4 text-white">
        <h2 className="text-base font-semibold">{title ?? "Selfie de confirmação"}</h2>
        <button
          onClick={onCancel}
          className="w-10 h-10 -mr-2 flex items-center justify-center rounded-full active:bg-white/10"
          aria-label="Cancelar"
        >
          <X size={20} />
        </button>
      </div>

      <div className="flex-1 flex items-center justify-center px-4">
        <div className="relative w-full max-w-sm aspect-square rounded-2xl overflow-hidden bg-black border border-white/10">
          {error ? (
            <div className="absolute inset-0 flex items-center justify-center text-center text-sm text-white/90 p-6">
              {error}
            </div>
          ) : snapshot ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={snapshot} alt="Selfie" className="w-full h-full object-cover" />
          ) : (
            <>
              <video
                ref={videoRef}
                playsInline
                muted
                className="w-full h-full object-cover"
                style={{ transform: "scaleX(-1)" }}
              />
              {starting && (
                <div className="absolute inset-0 flex items-center justify-center text-white">
                  <Loader2 className="animate-spin" />
                </div>
              )}
            </>
          )}
        </div>
      </div>

      <div className="p-6 pb-8 flex items-center justify-center gap-6">
        {error ? (
          <button
            onClick={onCancel}
            className="h-12 px-6 rounded-full bg-white text-black font-semibold"
          >
            Fechar
          </button>
        ) : snapshot ? (
          <>
            <button
              onClick={() => setSnapshot(null)}
              className="h-12 w-12 rounded-full bg-white/10 text-white flex items-center justify-center"
              aria-label="Refazer"
            >
              <RefreshCw size={20} />
            </button>
            <button
              onClick={confirm}
              className="h-14 px-8 rounded-full bg-primary text-primary-foreground font-semibold inline-flex items-center gap-2"
            >
              <Check size={20} />
              Confirmar
            </button>
          </>
        ) : (
          <button
            onClick={takeShot}
            disabled={starting}
            className="h-16 w-16 rounded-full bg-white text-black flex items-center justify-center disabled:opacity-50"
            aria-label="Tirar foto"
          >
            <Camera size={26} />
          </button>
        )}
      </div>
    </div>
  );
}
