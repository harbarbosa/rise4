type Variant = "em_andamento" | "programada" | "atrasada" | "concluida" | "concluido" | "ativo" | "pausado" | "atrasado" | "alta" | "media" | "baixa";

const styles: Record<Variant, string> = {
  em_andamento: "bg-info/10 text-info",
  programada: "bg-primary/10 text-primary",
  atrasada: "bg-destructive/10 text-destructive",
  concluida: "bg-success/10 text-success",
  concluido: "bg-success/10 text-success",
  ativo: "bg-success/10 text-success",
  pausado: "bg-warning/15 text-[oklch(0.5_0.15_60)]",
  atrasado: "bg-destructive/10 text-destructive",
  alta: "bg-destructive/10 text-destructive",
  media: "bg-warning/15 text-[oklch(0.5_0.15_60)]",
  baixa: "bg-info/10 text-info",
};

const labels: Record<Variant, string> = {
  em_andamento: "Em andamento",
  programada: "Programada",
  atrasada: "Atrasada",
  concluida: "Concluída",
  concluido: "Concluído",
  ativo: "Ativo",
  pausado: "Pausado",
  atrasado: "Atrasado",
  alta: "Alta",
  media: "Média",
  baixa: "Baixa",
};

export function StatusBadge({ variant, label }: { variant: Variant; label?: string }) {
  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${styles[variant]}`}>
      {label ?? labels[variant]}
    </span>
  );
}
