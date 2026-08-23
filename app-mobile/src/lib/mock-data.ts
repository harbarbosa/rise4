export const user = {
  nome: "Henrique Oliveira",
  cargo: "Técnico Instalador",
  email: "henrique@alfahp.com.br",
  iniciais: "HO",
  bancoHoras: "+12:30",
  horasMes: "96:30",
  feriasDias: 12,
};

export type ActivityStatus = "em_andamento" | "programada" | "atrasada" | "concluida";

export interface AgendaItem {
  id: string;
  inicio: string;
  fim: string;
  projeto: string;
  cliente: string;
  cidade: string;
  endereco: string;
  responsavel: string;
  status: ActivityStatus;
  data: string;
  descricao: string;
}

export const agenda: AgendaItem[] = [
  {
    id: "a1",
    inicio: "08:00",
    fim: "17:00",
    projeto: "Instalação Fotovoltaica",
    cliente: "Coopercitrus",
    cidade: "Bebedouro - SP",
    endereco: "Rod. Anhanguera, km 442, Zona Rural",
    responsavel: "Henrique Oliveira",
    status: "em_andamento",
    data: "2026-06-03",
    descricao: "Instalação do sistema fotovoltaico de 500kWp. Fase de montagem da estrutura de fixação e início do cabeamento CC.",
  },
  {
    id: "a2",
    inicio: "13:00",
    fim: "17:00",
    projeto: "Manutenção CFTV",
    cliente: "BRF Brotas",
    cidade: "Brotas - SP",
    endereco: "Av. Industrial, 1500, Distrito Industrial",
    responsavel: "Carlos Eduardo",
    status: "programada",
    data: "2026-06-03",
    descricao: "Manutenção preventiva do sistema de CFTV. Verificação das câmeras do setor de processamento e substituição de cabos danificados.",
  },
  {
    id: "a3",
    inicio: "08:00",
    fim: "12:00",
    projeto: "Manutenção Preventiva",
    cliente: "CPFL Araraquara",
    cidade: "Araraquara - SP",
    endereco: "Rua das Energias, 280, Jardim das Oliveiras",
    responsavel: "Marcos Vinicius",
    status: "atrasada",
    data: "2026-06-02",
    descricao: "Manutenção preventiva trimestral do sistema elétrico de alta tensão. Testes de resistência e ajuste dos disjuntores principais.",
  },
  {
    id: "a4",
    inicio: "09:00",
    fim: "16:00",
    projeto: "Instalação Elétrica",
    cliente: "Village Dahma",
    cidade: "São Carlos - SP",
    endereco: "Rua das Palmeiras, 45, Condomínio Village Dahma",
    responsavel: "Henrique Oliveira",
    status: "programada",
    data: "2026-06-04",
    descricao: "Instalação completa do sistema elétrico do lote 12. Passagem de eletrodutos, quadro de distribuição e tomadas.",
  },
  {
    id: "a5",
    inicio: "07:30",
    fim: "15:30",
    projeto: "Instalação Fotovoltaica",
    cliente: "Coopercitrus",
    cidade: "Bebedouro - SP",
    endereco: "Rod. Anhanguera, km 442, Zona Rural",
    responsavel: "Henrique Oliveira",
    status: "programada",
    data: "2026-06-04",
    descricao: "Continuação da instalação fotovoltaica. Conexão dos inversores e configuração do monitoramento remoto.",
  },
  {
    id: "a6",
    inicio: "08:00",
    fim: "18:00",
    projeto: "Manutenção CFTV",
    cliente: "BRF Brotas",
    cidade: "Brotas - SP",
    endereco: "Av. Industrial, 1500, Distrito Industrial",
    responsavel: "Carlos Eduardo",
    status: "concluida",
    data: "2026-06-01",
    descricao: "Troca completa do DVR antigo por modelo IP. Reconfiguração de todas as 32 câmeras e ajuste das gravações.",
  },
];

export type ProjetoStatus = "ativo" | "pausado" | "concluido" | "atrasado";
export const projetos = [
  {
    id: "p1",
    nome: "Coopercitrus Bebedouro",
    cliente: "Coopercitrus",
    tipo: "Instalação Fotovoltaica",
    progresso: 65,
    inicio: "10/05/2026",
    termino: "20/06/2026",
    status: "ativo" as ProjetoStatus,
  },
  {
    id: "p2",
    nome: "BRF Brotas",
    cliente: "BRF",
    tipo: "Manutenção CFTV",
    progresso: 40,
    inicio: "28/05/2026",
    termino: "15/06/2026",
    status: "ativo" as ProjetoStatus,
  },
  {
    id: "p3",
    nome: "Village Dahma",
    cliente: "Village Dahma",
    tipo: "Instalação Elétrica",
    progresso: 20,
    inicio: "01/06/2026",
    termino: "30/06/2026",
    status: "ativo" as ProjetoStatus,
  },
  {
    id: "p4",
    nome: "CPFL Araraquara",
    cliente: "CPFL",
    tipo: "Manutenção Preventiva",
    progresso: 80,
    inicio: "15/05/2026",
    termino: "10/06/2026",
    status: "atrasado" as ProjetoStatus,
  },
];

export interface Tarefa {
  id: string;
  titulo: string;
  responsavel: string;
  progresso: number;
  concluida: boolean;
  status: ActivityStatus;
}

export const tarefasProjeto: Record<string, Tarefa[]> = {
  p1: [
    { id: "t1", titulo: "Instalar estrutura de fixação", responsavel: "Henrique Oliveira", progresso: 100, concluida: true, status: "concluida" },
    { id: "t2", titulo: "Instalar módulos FV - String 01", responsavel: "Henrique Oliveira", progresso: 100, concluida: true, status: "concluida" },
    { id: "t3", titulo: "Instalar módulos FV - String 02", responsavel: "Marcos Vinicius", progresso: 60, concluida: false, status: "em_andamento" },
    { id: "t4", titulo: "Passar cabeamento CC", responsavel: "Henrique Oliveira", progresso: 50, concluida: false, status: "em_andamento" },
    { id: "t5", titulo: "Conectar inversores", responsavel: "Marcos Vinicius", progresso: 0, concluida: false, status: "programada" },
    { id: "t6", titulo: "Configuração e testes", responsavel: "Carlos Eduardo", progresso: 0, concluida: false, status: "programada" },
    { id: "t7", titulo: "Comissionamento do sistema", responsavel: "Henrique Oliveira", progresso: 0, concluida: false, status: "programada" },
    { id: "t8", titulo: "Documentação e As Built", responsavel: "Carlos Eduardo", progresso: 0, concluida: false, status: "programada" },
  ],
  p2: [
    { id: "t9", titulo: "Inspeção das câmeras externas", responsavel: "Carlos Eduardo", progresso: 100, concluida: true, status: "concluida" },
    { id: "t10", titulo: "Teste de funcionamento DVR", responsavel: "Carlos Eduardo", progresso: 100, concluida: true, status: "concluida" },
    { id: "t11", titulo: "Substituição de cabos danificados", responsavel: "Henrique Oliveira", progresso: 40, concluida: false, status: "em_andamento" },
    { id: "t12", titulo: "Ajuste de foco e ângulo", responsavel: "Marcos Vinicius", progresso: 0, concluida: false, status: "programada" },
    { id: "t13", titulo: "Relatório fotográfico", responsavel: "Carlos Eduardo", progresso: 0, concluida: false, status: "programada" },
  ],
  p3: [
    { id: "t14", titulo: "Passagem de eletrodutos", responsavel: "Henrique Oliveira", progresso: 30, concluida: false, status: "em_andamento" },
    { id: "t15", titulo: "Instalação do quadro de distribuição", responsavel: "Marcos Vinicius", progresso: 0, concluida: false, status: "programada" },
    { id: "t16", titulo: "Instalação de tomadas e interruptores", responsavel: "Henrique Oliveira", progresso: 0, concluida: false, status: "programada" },
    { id: "t17", titulo: "Testes de continuidade e isolamento", responsavel: "Carlos Eduardo", progresso: 0, concluida: false, status: "programada" },
    { id: "t18", titulo: "Laudo de instalação elétrica", responsavel: "Carlos Eduardo", progresso: 0, concluida: false, status: "programada" },
  ],
  p4: [
    { id: "t19", titulo: "Testes de resistência de isolamento", responsavel: "Marcos Vinicius", progresso: 100, concluida: true, status: "concluida" },
    { id: "t20", titulo: "Ajuste dos disjuntores principais", responsavel: "Marcos Vinicius", progresso: 100, concluida: true, status: "concluida" },
    { id: "t21", titulo: "Verificação dos transformadores", responsavel: "Henrique Oliveira", progresso: 100, concluida: true, status: "concluida" },
    { id: "t22", titulo: "Relatório de manutenção preventiva", responsavel: "Carlos Eduardo", progresso: 20, concluida: false, status: "em_andamento" },
    { id: "t23", titulo: "Entrega de documentação ao cliente", responsavel: "Carlos Eduardo", progresso: 0, concluida: false, status: "atrasada" },
  ],
};

export interface DespesaItem {
  id: string;
  categoria: "alimentacao" | "combustivel" | "hotel" | "pedagio" | "outros";
  valor: number;
  data: string;
  status: "aprovado" | "pendente" | "rejeitado";
  observacao: string;
  notaFiscal?: string;
}

export const despesas: DespesaItem[] = [
  {
    id: "d1",
    categoria: "combustivel",
    valor: 245.8,
    data: "2026-06-02",
    status: "aprovado",
    observacao: "Abastecimento Posto Shell - Anhanguera km 420",
    notaFiscal: "https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&h=300&fit=crop",
  },
  {
    id: "d2",
    categoria: "alimentacao",
    valor: 48.5,
    data: "2026-06-02",
    status: "aprovado",
    observacao: "Almoço - Restaurante Mineiro",
    notaFiscal: "https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&h=300&fit=crop",
  },
  {
    id: "d3",
    categoria: "hotel",
    valor: 180.0,
    data: "2026-06-02",
    status: "pendente",
    observacao: "Hotel Bebedouro Palace - 1 diária",
    notaFiscal: "https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&h=300&fit=crop",
  },
  {
    id: "d4",
    categoria: "pedagio",
    valor: 24.5,
    data: "2026-06-03",
    status: "aprovado",
    observacao: "Pedágio Anhanguera - sentido interior",
  },
  {
    id: "d5",
    categoria: "alimentacao",
    valor: 35.0,
    data: "2026-06-03",
    status: "pendente",
    observacao: "Jantar - Lanchonete 24h",
  },
  {
    id: "d6",
    categoria: "outros",
    valor: 65.0,
    data: "2026-06-03",
    status: "aprovado",
    observacao: "Estacionamento cliente - 2 dias",
  },
  {
    id: "d7",
    categoria: "combustivel",
    valor: 198.4,
    data: "2026-06-03",
    status: "pendente",
    observacao: "Abastecimento retorno - Posto Ipiranga",
  },
];

export const viagemAtual = {
  projeto: "Coopercitrus Bebedouro",
  periodo: "02/06/2026 - 04/06/2026",
  status: "Em andamento",
};

export type PendenciaPrioridade = "alta" | "media" | "baixa";
export const pendencias = [
  {
    id: "pe1",
    prioridade: "alta" as PendenciaPrioridade,
    titulo: "Material não entregue",
    projeto: "Coopercitrus Bebedouro",
    aberta: "02/06/2026",
    responsavel: "Cliente",
    status: "aberta",
  },
  {
    id: "pe2",
    prioridade: "media" as PendenciaPrioridade,
    titulo: "Ajuste no quadro DC",
    projeto: "BRF Brotas",
    aberta: "03/06/2026",
    responsavel: "AlfaHP",
    status: "aberta",
  },
  {
    id: "pe3",
    prioridade: "baixa" as PendenciaPrioridade,
    titulo: "Limpeza da área",
    projeto: "Village Dahma",
    aberta: "03/06/2026",
    responsavel: "Cliente",
    status: "aberta",
  },
];

export interface Documento {
  id: string;
  nome: string;
  validade: string;
  status: "valido" | "proximo_vencimento" | "vencido";
  diasRestantes: number;
}

export const documentos: Documento[] = [
  { id: "doc1", nome: "ASO", validade: "10/08/2026", status: "valido", diasRestantes: 68 },
  { id: "doc2", nome: "NR10", validade: "15/09/2026", status: "valido", diasRestantes: 104 },
  { id: "doc3", nome: "NR35", validade: "20/10/2026", status: "proximo_vencimento", diasRestantes: 138 },
  { id: "doc4", nome: "CNH", validade: "05/07/2026", status: "proximo_vencimento", diasRestantes: 32 },
  { id: "doc5", nome: "NR12", validade: "15/05/2026", status: "vencido", diasRestantes: -19 },
];
