# Manual de Operacao - Modulo Compras (Purchases)

Este manual descreve o uso do modulo de Compras no RiseCRM do ponto de vista do usuario final.
Foco: operacao do sistema, fluxos e boas praticas de uso.

## 1) Visao geral do modulo

O modulo de Compras organiza o processo completo:
- Requisicao de Compra (RC)
- Cotacao com fornecedores
- Aprovacoes
- Pedido de Compra (PC)
- Recebimento de materiais
- Relatorios

Principais beneficios:
- Controle de status
- Comparacao de fornecedores
- Aprovacoes com limite financeiro
- Rastreabilidade de status e notificacoes

## 2) Acesso e permissoes

### 2.1 Onde configurar permissoes
- Menu: Configuracoes > Funcoes/Permissoes (Roles)
- Local: Painel de permissoes da funcao do usuario

### 2.2 Permissoes do modulo
- purchases_view: visualizar compras
- purchases_manage: gerenciar compras (criar/editar, cotar, gerar PC, receber)
- purchases_approve: aprovar requisicoes (requisitante)
- purchases_financial_approve: aprovacao financeira
- purchases_financial_limit: limite de aprovacao financeira (campo numerico)

### 2.3 Limite de aprovacao financeira
- Ao marcar a permissao "aprovacao financeira", aparece um campo para limite
- Se o total da cotacao exceder o limite, o usuario nao pode aprovar

## 3) Fluxo principal (status)

### 3.1 Requisicao de Compra (RC)
1. Rascunho (draft)
2. Enviada para cotacao (sent_to_quotation)
3. Em cotacao (quotation_in_progress)
4. Cotacao finalizada (quotation_finalized)
5. Aguardando aprovacao (awaiting_approval)
6. Aprovada para compra (approved_for_po)
7. Reprovada (rejected)
8. Pedido de compra criado (po_created)
9. Pedido enviado (po_sent)
10. Recebimento parcial (partial_received)
11. Recebimento total (received)

Observacao:
- Ao reenviar uma RC reprovada, a cotacao vinculada volta para rascunho.

## 4) Operacao por perfil

### 4.1 Requisitante
Objetivo: Criar RC e acompanhar o fluxo.

Passos:
1. Menu Compras > Requisicoes
2. Clique em "Nova Requisicao"
3. Preencha:
   - Projeto ou OS (ou Interno)
   - Prioridade
   - Observacao (opcional)
4. Itens:
   - Selecione o material
   - A descricao sera preenchida automaticamente com o nome do material
   - Preencha quantidade, unidade e data desejada (obrigatoria)
5. Salve como rascunho
6. Clique em "Enviar para cotacao"

Acompanhamento:
- Recebe notificacoes a cada mudanca de status
- Pode visualizar a cotacao e o status de aprovacao

### 4.2 Comprador
Objetivo: Criar cotacao, comparar fornecedores e gerar PC.

Passos:
1. Menu Compras > Requisicoes
2. Filtre por status "Enviada para cotacao" ou "Em cotacao"
3. Abra a RC e clique em "Criar cotacao"
4. Selecione 1 a 3 fornecedores
5. Na cotacao (rascunho):
   - Preencha preco unitario, frete, data de entrega e observacoes
   - Marque o vencedor por item
   - Salve a cotacao
6. Clique em "Finalizar cotacao"
   - A RC vai para "Aguardando aprovacao"

Apos aprovacoes:
- Quando a RC estiver "Aprovada para compra", aparece o botao "Gerar PC"
- Ao gerar PC, o sistema direciona para a tela do pedido

### 4.3 Financeiro (Aprovador)
Objetivo: Aprovar compras conforme limite.

Passos:
1. Menu Compras > Aprovacoes
2. Abra a RC pendente
3. Verifique a tabela de cotacoes e vencedor
4. Clique em "Aprovar como financeiro" ou "Reprovar"

Regras:
- Se o valor total exceder o limite, o sistema bloqueia a aprovacao

### 4.4 Administrador
- Pode acessar tudo, configurar permissoes e limites
- Pode revisar quaisquer RCs

## 5) Tela da Requisicao (RC)

Elementos principais:
- Informacoes gerais (projeto/OS/interno, prioridade, solicitante)
- Itens
- Historico de status
- Cotacao (matriz de precos e vencedor)
- Aprovacoes (painel de aprovacoes)
- Acoes (enviar para cotacao, gerar PC quando aprovado)

## 6) Cotacao

### 6.1 Rascunho
- Permite editar fornecedores
- Permite editar precos e vencedores

### 6.2 Finalizada
- Bloqueia edicao
- Aciona fluxo de aprovacao

## 7) Pedido de Compra (PC)

Status:
- open, sent, partial_received, received, canceled

Acoes:
- Marcar como enviado
- Registrar recebimento parcial
- Registrar recebimento total
- Imprimir

## 8) Recebimentos

Ao registrar recebimento:
- Informe data, recebedor, numero da NF (opcional)
- Informe quantidades por item
- O sistema calcula saldo pendente

Status do PC:
- Se receber tudo: received
- Se receber parte: partial_received

## 9) Notificacoes

Eventos principais:
- Enviada para cotacao
- Cotacao finalizada
- Aguardando aprovacao
- Aprovada para compra
- Reprovada
- Pedido criado
- Pedido enviado
- Recebimento parcial/total

Os usuarios recebem notificacoes no sistema e por email, conforme configuracao.

## 10) Relatorios

Menu: Compras > Relatorios
- Compras por periodo/obra/fornecedor
- Pedidos em aberto e atrasados
- Itens mais comprados

## 11) Dicas e boas praticas

- Sempre preencha a data desejada do item
- Use descricao padronizada para facilitar comparacao
- Mantenha os fornecedores atualizados
- Finalize a cotacao somente quando todos os itens tiverem vencedor

## 12) Solucao de problemas comuns

- "Sem permissao": verifique permissoes da funcao
- "Limite financeiro excedido": ajuste o limite do aprovador
- "Sem resultados no material": digite o item nao cadastrado na descricao

---

Fim do manual.
