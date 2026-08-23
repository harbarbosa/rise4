import { useEffect, useState } from "react";

interface AvatarProps {
  initials: string;
  src?: string | null;
  size?: number;
  className?: string;
}

export function Avatar({ initials, src, size = 44, className = "" }: AvatarProps) {
  const [showImage, setShowImage] = useState(!!src);

  useEffect(() => {
    setShowImage(!!src);
  }, [src]);

  if (src && showImage) {
    return (
      <img
        src={src}
        alt={initials}
        className={`rounded-full object-cover shadow-card ring-2 ring-white ${className}`}
        style={{ width: size, height: size }}
        onError={() => setShowImage(false)}
      />
    );
  }

  return (
    <div
      className={`rounded-full bg-gradient-to-br from-primary to-[oklch(0.5_0.18_258)] text-primary-foreground font-semibold flex items-center justify-center shadow-card ring-2 ring-white ${className}`}
      style={{ width: size, height: size, fontSize: size * 0.38 }}
    >
      {initials}
    </div>
  );
}
