import { createFileRoute } from "@tanstack/react-router";
import {
  Plus,
  UtensilsCrossed,
  Fuel,
  Receipt,
  BedDouble,
  MoreHorizontal,
  Camera,
  Trash2,
  Pencil,
  CircleCheck,
  CircleDashed,
  CircleX,
  ChevronDown,
} from "lucide-react";
import { useState, useRef, useCallback, useMemo, useEffect } from "react";
import { MobileShell } from "@/components/MobileShell";
import { PageHeader } from "@/components/PageHeader";
import { LoadingState, ErrorState, EmptyState } from "@/components/QueryState";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
} from "@/components/ui/sheet";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useTrips } from "@/hooks/useTrips";
import { useTripExpenses } from "@/hooks/useTripExpenses";
import {
  useCreateExpense,
  useUpdateExpense,
  useDeleteExpense,
} from "@/hooks/useCreateExpense";
import type {
  Expense,
  ExpenseCategory,
  ExpenseInput,
} from "@/services/travelRefundService";

export const Route = createFileRoute("/despesas")({
  head: () => ({ meta: [{ title: "Despesas de Viagem — AlfaHP" }] }),
  component: DespesasPage,
});

const categoriaMap: Record<
  ExpenseCategory,
  { label: string; Icon: typeof Plus; color: string; bg: string }
> = {
  alimentacao: { label: "Alimentação", Icon: UtensilsCrossed, color: "text-success", bg: "bg-success/10" },
  combustivel: { label: "Combustível", Icon: Fuel, color: "text-info", bg: "bg-info/10" },
  pedagio: { label: "Pedágio", Icon: Receipt, color: "text-[oklch(0.55_0.18_30)]", bg: "bg-[oklch(0.95_0.04_30)]" },
  hotel: { label: "Hotel", Icon: BedDouble, color: "text-primary", bg: "bg-primary/10" },
  outros: { label: "Outros", Icon: MoreHorizontal, color: "text-muted-foreground", bg: "bg-muted" },
};

const statusMap = {
  aprovado: { label: "Aprovado", color: "text-success", bg: "bg-success/10", Icon: CircleCheck },
  pendente: { label: "Pendente", color: "text-[oklch(0.5_0.15_60)]", bg: "bg-[oklch(0.97_0.03_80)]", Icon: CircleDashed },
  rejeitado: { label: "Rejeitado", color: "text-destructive", bg: "bg-destructive/10", Icon: CircleX },
} as const;

function brl(v: number) {
  return v.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

function fmtData(iso: string) {
  if (!iso) return "—";
  const [y, m, d] = iso.split("-");
  if (!y || !m || !d) return iso;
  return `${d}/${m}/${y}`;
}

function DespesasPage() {
  const tripsQuery = useTrips();
  const trips = tripsQuery.data ?? [];

  const [selectedTripId, setSelectedTripId] = useState<string | undefined>();

  useEffect(() => {
    if (!selectedTripId && trips.length > 0) {
      setSelectedTripId(trips[0].id);
    }
  }, [trips, selectedTripId]);

  const selectedTrip = trips.find((t) => t.id === selectedTripId);

  const expensesQuery = useTripExpenses(selectedTripId);
  const expenses = expensesQuery.data ?? [];

  const [abrirSheet, setAbrirSheet] = useState(false);
  const [editing, setEditing] = useState<Expense | null>(null);
  const [filtro, setFiltro] = useState<"todos" | ExpenseCategory>("todos");
  const [deleteTarget, setDeleteTarget] = useState<Expense | null>(null);

  const createExpense = useCreateExpense(selectedTripId ?? "");
  const updateExpense = useUpdateExpense(selectedTripId ?? "");
  const deleteExpense = useDeleteExpense(selectedTripId ?? "");

  const total = useMemo(
    () => expenses.reduce((s, d) => s + (d.valor ?? 0), 0),
    [expenses],
  );

  const filtradas =
    filtro === "todos" ? expenses : expenses.filter((d) => d.categoria === filtro);

  const abrirNova = () => {
    setEditing(null);
    setAbrirSheet(true);
  };
  const abrirEdicao = (e: Expense) => {
    setEditing(e);
    setAbrirSheet(true);
  };

  const handleSave = async (input: ExpenseInput) => {
    if (!selectedTripId) return;
    if (editing) {
      await updateExpense.mutateAsync({ id: editing.id, input });
    } else {
      await createExpense.mutateAsync(input);
    }
    setAbrirSheet(false);
    setEditing(null);
  };

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    await deleteExpense.mutateAsync(deleteTarget.id);
    setDeleteTarget(null);
  };

  return (
    <MobileShell>
      <PageHeader title="Despesas de Viagem" back="/" />

      <div className="p-4 pb-2 space-y-3">
        {/* Seletor de Viagem */}
        {tripsQuery.isLoading ? (
          <LoadingState label="Carregando viagens..." />
        ) : tripsQuery.isError ? (
          <ErrorState
            message="Não foi possível carregar as viagens."
            onRetry={() => tripsQuery.refetch()}
          />
        ) : trips.length === 0 ? (
          <EmptyState
            title="Nenhuma viagem"
            description="Cadastre uma viagem para lançar despesas."
          />
        ) : (
          <section className="bg-card rounded-2xl border border-border p-4 shadow-card">
            <div className="flex items-center justify-between mb-2">
              <p className="text-xs text-muted-foreground">Viagem</p>
              {selectedTrip?.status && (
                <span className="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-info/10 text-info">
                  {selectedTrip.status}
                </span>
              )}
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button className="w-full flex items-center justify-between gap-2 text-left">
                  <div className="min-w-0">
                    <p className="text-base font-bold truncate">
                      {selectedTrip?.titulo ?? "Selecione uma viagem"}
                    </p>
                    <p className="text-xs text-muted-foreground truncate">
                      {selectedTrip?.periodo ??
                        (selectedTrip?.dataInicio
                          ? `${fmtData(selectedTrip.dataInicio)}${
                              selectedTrip.dataFim ? " - " + fmtData(selectedTrip.dataFim) : ""
                            }`
                          : "—")}
                    </p>
                  </div>
                  <ChevronDown size={18} className="text-muted-foreground shrink-0" />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-72">
                {trips.map((t) => (
                  <DropdownMenuItem
                    key={t.id}
                    onClick={() => setSelectedTripId(t.id)}
                    className="flex flex-col items-start gap-0.5"
                  >
                    <span className="text-sm font-semibold">{t.titulo}</span>
                    {t.periodo && (
                      <span className="text-[11px] text-muted-foreground">{t.periodo}</span>
                    )}
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
            <div className="mt-3 pt-3 border-t border-border flex items-center justify-between">
              <span className="text-sm text-muted-foreground">Total de despesas</span>
              <span className="text-lg font-bold text-primary">{brl(total)}</span>
            </div>
          </section>
        )}

        {/* Filtro categoria */}
        {selectedTripId && (
          <div className="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
            <button
              onClick={() => setFiltro("todos")}
              className={`shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors ${
                filtro === "todos"
                  ? "bg-primary text-primary-foreground border-primary"
                  : "bg-card text-muted-foreground border-border"
              }`}
            >
              Todos
            </button>
            {(Object.keys(categoriaMap) as ExpenseCategory[]).map((cat) => {
              const ativo = filtro === cat;
              return (
                <button
                  key={cat}
                  onClick={() => setFiltro(ativo ? "todos" : cat)}
                  className={`shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors ${
                    ativo
                      ? "bg-primary text-primary-foreground border-primary"
                      : "bg-card text-muted-foreground border-border"
                  }`}
                >
                  {categoriaMap[cat].label}
                </button>
              );
            })}
          </div>
        )}
      </div>

      {/* Lista despesas */}
      {selectedTripId && (
        <div className="px-4 pb-4 space-y-3">
          {expensesQuery.isLoading ? (
            <LoadingState label="Carregando despesas..." />
          ) : expensesQuery.isError ? (
            <ErrorState
              message="Não foi possível carregar as despesas."
              onRetry={() => expensesQuery.refetch()}
            />
          ) : filtradas.length === 0 ? (
            <EmptyState
              title="Nenhuma despesa"
              description="Cadastre a primeira despesa desta viagem."
            />
          ) : (
            filtradas.map((d) => {
              const cat = categoriaMap[d.categoria];
              const st = statusMap[d.status];
              const StatusIcon = st.Icon;
              return (
                <article
                  key={d.id}
                  className="bg-card rounded-2xl border border-border p-3.5 shadow-card flex items-start gap-3"
                >
                  <span
                    className={`w-11 h-11 rounded-xl ${cat.bg} ${cat.color} flex items-center justify-center shrink-0 mt-0.5`}
                  >
                    <cat.Icon size={20} />
                  </span>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-2">
                      <p className="text-sm font-semibold truncate">{cat.label}</p>
                      <span
                        className={`shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold ${st.bg} ${st.color}`}
                      >
                        <StatusIcon size={10} />
                        {st.label}
                      </span>
                    </div>
                    <p className="text-[11px] text-muted-foreground mt-0.5">{fmtData(d.data)}</p>
                    {d.observacao && (
                      <p className="text-[11px] text-muted-foreground mt-1 truncate">
                        {d.observacao}
                      </p>
                    )}
                    {d.notaFiscal && (
                      <div className="mt-2 rounded-lg overflow-hidden border border-border w-20 h-14">
                        <img
                          src={d.notaFiscal}
                          alt="Nota fiscal"
                          className="w-full h-full object-cover"
                          loading="lazy"
                        />
                      </div>
                    )}
                    <div className="mt-2 flex gap-3">
                      <button
                        onClick={() => abrirEdicao(d)}
                        className="text-[11px] font-semibold text-primary inline-flex items-center gap-1 active:opacity-70"
                      >
                        <Pencil size={12} /> Editar
                      </button>
                      <button
                        onClick={() => setDeleteTarget(d)}
                        className="text-[11px] font-semibold text-destructive inline-flex items-center gap-1 active:opacity-70"
                      >
                        <Trash2 size={12} /> Excluir
                      </button>
                    </div>
                  </div>
                  <p className="text-sm font-bold shrink-0 mt-0.5">{brl(d.valor)}</p>
                </article>
              );
            })
          )}

          <button
            onClick={abrirNova}
            disabled={!selectedTripId}
            className="mt-2 w-full h-12 rounded-xl bg-primary text-primary-foreground font-semibold text-sm inline-flex items-center justify-center gap-2 active:opacity-90 disabled:opacity-50"
          >
            <Plus size={18} /> Nova despesa
          </button>
        </div>
      )}

      <NovaDespesaSheet
        open={abrirSheet}
        editing={editing}
        saving={createExpense.isPending || updateExpense.isPending}
        onClose={() => {
          setAbrirSheet(false);
          setEditing(null);
        }}
        onSave={handleSave}
      />

      <AlertDialog
        open={!!deleteTarget}
        onOpenChange={(o) => !o && setDeleteTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir despesa?</AlertDialogTitle>
            <AlertDialogDescription>
              Esta ação não pode ser desfeita. A despesa será removida permanentemente.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={confirmDelete}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </MobileShell>
  );
}

/* ------------------------------------------------------------------ */
/* Sheet cadastro / edição                                            */
/* ------------------------------------------------------------------ */

function NovaDespesaSheet({
  open,
  editing,
  saving,
  onClose,
  onSave,
}: {
  open: boolean;
  editing: Expense | null;
  saving: boolean;
  onClose: () => void;
  onSave: (input: ExpenseInput) => void | Promise<void>;
}) {
  const [categoria, setCategoria] = useState<ExpenseCategory>("alimentacao");
  const [valor, setValor] = useState("");
  const [observacao, setObservacao] = useState("");
  const [foto, setFoto] = useState<string | undefined>(undefined);
  const [data, setData] = useState(() => new Date().toISOString().slice(0, 10));
  const fileRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (open) {
      if (editing) {
        setCategoria(editing.categoria);
        setValor(String(editing.valor));
        setObservacao(editing.observacao ?? "");
        setFoto(editing.notaFiscal);
        setData(editing.data || new Date().toISOString().slice(0, 10));
      } else {
        setCategoria("alimentacao");
        setValor("");
        setObservacao("");
        setFoto(undefined);
        setData(new Date().toISOString().slice(0, 10));
      }
    }
  }, [open, editing]);

  const handleFile = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) setFoto(URL.createObjectURL(file));
  }, []);

  const salvar = () => {
    const v = parseFloat(valor.replace(",", "."));
    if (!v || v <= 0) return;
    onSave({
      categoria,
      valor: v,
      data,
      observacao,
      notaFiscal: foto,
    });
  };

  const categorias = Object.keys(categoriaMap) as ExpenseCategory[];

  return (
    <Sheet open={open} onOpenChange={(o) => !o && onClose()}>
      <SheetContent
        side="bottom"
        className="h-[92dvh] max-h-[92dvh] rounded-t-2xl p-0 flex flex-col gap-0"
      >
        <SheetHeader className="px-5 py-3 border-b border-border shrink-0 text-left">
          <SheetTitle className="text-base">
            {editing ? "Editar Despesa" : "Nova Despesa"}
          </SheetTitle>
          <SheetDescription className="text-xs">
            Preencha os dados da despesa e anexe a nota fiscal.
          </SheetDescription>
        </SheetHeader>

        <div className="flex-1 min-h-0 overflow-y-auto px-5 py-4 space-y-4">
          <div>
            <label className="text-xs font-semibold text-foreground mb-2 block">
              Categoria
            </label>
            <div className="grid grid-cols-3 gap-2">
              {categorias.map((cat) => {
                const info = categoriaMap[cat];
                const ativo = categoria === cat;
                return (
                  <button
                    key={cat}
                    onClick={() => setCategoria(cat)}
                    className={`flex flex-col items-center gap-1.5 py-3 rounded-xl border transition-colors ${
                      ativo ? "border-primary bg-primary/5" : "border-border bg-card"
                    }`}
                  >
                    <span className={`w-9 h-9 rounded-lg ${info.bg} ${info.color} flex items-center justify-center`}>
                      <info.Icon size={18} />
                    </span>
                    <span className={`text-[10px] font-semibold ${ativo ? "text-primary" : "text-muted-foreground"}`}>
                      {info.label}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          <div>
            <label className="text-xs font-semibold text-foreground mb-1.5 block">
              Valor (R$)
            </label>
            <input
              type="number"
              step="0.01"
              min="0"
              inputMode="decimal"
              placeholder="0,00"
              value={valor}
              onChange={(e) => setValor(e.target.value)}
              className="w-full h-12 rounded-xl border border-border bg-card px-4 text-sm font-medium text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
            />
          </div>

          <div>
            <label className="text-xs font-semibold text-foreground mb-1.5 block">Data</label>
            <input
              type="date"
              value={data}
              onChange={(e) => setData(e.target.value)}
              className="w-full h-12 rounded-xl border border-border bg-card px-4 text-sm font-medium text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
            />
          </div>

          <div>
            <label className="text-xs font-semibold text-foreground mb-1.5 block">
              Observação
            </label>
            <textarea
              rows={3}
              placeholder="Descreva a despesa..."
              value={observacao}
              onChange={(e) => setObservacao(e.target.value)}
              className="w-full rounded-xl border border-border bg-card px-4 py-3 text-sm font-medium text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all resize-none"
            />
          </div>

          <div>
            <label className="text-xs font-semibold text-foreground mb-2 block">
              Nota Fiscal
            </label>
            {foto ? (
              <div className="relative rounded-xl overflow-hidden border border-border w-full h-36">
                <img src={foto} alt="Nota fiscal" className="w-full h-full object-cover" />
                <button
                  onClick={() => setFoto(undefined)}
                  className="absolute top-2 right-2 w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center"
                >
                  <Trash2 size={14} />
                </button>
              </div>
            ) : (
              <button
                onClick={() => fileRef.current?.click()}
                className="w-full h-20 rounded-xl border-2 border-dashed border-border bg-card flex flex-col items-center justify-center gap-1.5 text-muted-foreground active:bg-muted transition-colors"
              >
                <Camera size={20} />
                <span className="text-xs font-medium">Tirar foto ou anexar nota fiscal</span>
              </button>
            )}
            <input
              ref={fileRef}
              type="file"
              accept="image/*"
              capture="environment"
              onChange={handleFile}
              className="hidden"
            />
          </div>
        </div>

        <div className="shrink-0 p-4 bg-card/95 backdrop-blur border-t border-border flex gap-3 pb-[calc(1rem+env(safe-area-inset-bottom))]">
          <button
            onClick={onClose}
            disabled={saving}
            className="flex-1 h-12 rounded-xl border border-border bg-background text-foreground font-semibold text-sm active:bg-muted transition-colors disabled:opacity-50"
          >
            Cancelar
          </button>
          <button
            onClick={salvar}
            disabled={saving || !valor || parseFloat(valor.replace(",", ".")) <= 0}
            className="flex-1 h-12 rounded-xl bg-primary text-primary-foreground font-semibold text-sm inline-flex items-center justify-center gap-2 disabled:opacity-50 active:opacity-90 transition-opacity"
          >
            {saving ? "Salvando..." : editing ? "Salvar alterações" : "Salvar Despesa"}
          </button>
        </div>
      </SheetContent>
    </Sheet>

  );
}
